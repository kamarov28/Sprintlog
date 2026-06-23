<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->reportData($request);

        return view('be.financial-reports.index', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->reportData($request, true);
        $fileBranch = Str::slug($data['hubLabel']) ?: 'semua-hub';
        $filename = 'laporan-keuangan-'.$fileBranch.'-'.$data['startDate'].'-'.$data['endDate'].'.pdf';

        $pdf = Pdf::loadView('be.financial-reports.pdf', $data)
            ->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    private function reportData(Request $request, bool $forPdf = false): array
    {
        $user = auth()->user();
        $branches = collect();
        $selectedBranch = null;
        $selectedBranches = collect();
        $branchIds = [];
        $branchId = null;

        if ($user->role === 'admin') {
            $branches = Branch::query()->select(['id', 'name', 'city'])->orderBy('name')->get();
            $availableBranchIds = $branches->pluck('id')->map(fn ($id) => (int) $id)->all();
            $hubScope = $this->resolveHubScope($request);

            if ($hubScope === 'single') {
                $branchId = $request->integer('branch_id') ?: null;

                if ($branchId && in_array($branchId, $availableBranchIds, true)) {
                    $branchIds = [$branchId];
                    $selectedBranch = $branches->firstWhere('id', $branchId);
                    $selectedBranches = collect([$selectedBranch])->filter();
                } else {
                    $branchId = null;
                    $hubScope = 'all';
                }
            } elseif ($hubScope === 'selected') {
                $branchIds = collect((array) $request->input('branch_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => in_array($id, $availableBranchIds, true))
                    ->unique()
                    ->values()
                    ->all();

                if ($branchIds === []) {
                    $hubScope = 'all';
                } else {
                    $selectedBranches = $branches->whereIn('id', $branchIds)->values();
                    $selectedBranch = $selectedBranches->count() === 1 ? $selectedBranches->first() : null;
                }
            }
        } else {
            if (! $user->branch_id) {
                abort(403, 'Akun staf belum terhubung ke cabang.');
            }

            $hubScope = 'single';
            $branchId = $user->branch_id;
            $branchIds = [(int) $branchId];
            $selectedBranch = $user->branch;
            $selectedBranches = collect([$selectedBranch])->filter();
        }

        [$period, $startDateCarbon, $endDateCarbon] = $this->resolvePeriod($request);
        $startDate = $startDateCarbon->toDateString();
        $endDate = $endDateCarbon->toDateString();
        $periodOptions = $this->periodOptions();
        $presetDates = $this->presetDates();
        $hubLabel = $this->hubLabel($hubScope, $selectedBranch, $selectedBranches);
        $filterQuery = $this->filterQuery($period, $startDate, $endDate, $hubScope, $branchId, $branchIds);

        $baseQuery = Payment::query()
            ->where('payment_status', 'paid')
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($branchIds !== []) {
            $baseQuery->whereHas('shipment', function ($q) use ($branchIds) {
                $q->whereIn('origin_branch_id', $branchIds);
            });
        }

        $summaryRow = (clone $baseQuery)
            ->selectRaw(
                'COALESCE(SUM(amount), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN amount ELSE 0 END), 0) as cash_payments,
                COALESCE(SUM(CASE WHEN payment_method = ? THEN amount ELSE 0 END), 0) as transfer_payments,
                COUNT(*) as package_count',
                ['cash', 'transfer']
            )
            ->first();

        $totalRevenue = (float) $summaryRow->total_revenue;
        $cashPayments = (float) $summaryRow->cash_payments;
        $transferPayments = (float) $summaryRow->transfer_payments;
        $otherPayments = max(0, $totalRevenue - $cashPayments - $transferPayments);
        $packageCount = (int) $summaryRow->package_count;
        $averageRevenue = $packageCount > 0 ? $totalRevenue / $packageCount : 0;

        $paymentRelations = $forPdf
            ? ['shipment.originBranch', 'shipment.destinationBranch', 'shipment.sender', 'shipment.receiver']
            : ['shipment:id,tracking_number'];

        $paymentsQuery = (clone $baseQuery)
            ->with($paymentRelations)
            ->latest('payment_date');

        $payments = $forPdf
            ? $paymentsQuery->get()
            : $paymentsQuery->paginate(20)->withQueryString();

        $chartGranularity = $this->chartGranularity($period, $startDateCarbon, $endDateCarbon);
        $chartData = $this->chartData((clone $baseQuery), $startDateCarbon, $endDateCarbon, $chartGranularity);
        $chartLabel = $chartGranularity === 'month' ? 'Pendapatan Bulanan' : 'Pendapatan Harian';

        return compact(
            'branches',
            'payments',
            'totalRevenue',
            'cashPayments',
            'transferPayments',
            'otherPayments',
            'packageCount',
            'averageRevenue',
            'chartData',
            'chartLabel',
            'chartGranularity',
            'startDate',
            'endDate',
            'period',
            'periodOptions',
            'presetDates',
            'hubScope',
            'hubLabel',
            'branchId',
            'branchIds',
            'selectedBranch',
            'selectedBranches',
            'filterQuery'
        );
    }

    private function resolveHubScope(Request $request): string
    {
        $scope = $request->input('hub_scope');

        if (in_array($scope, ['all', 'single', 'selected'], true)) {
            return $scope;
        }

        if ($request->filled('branch_id')) {
            return 'single';
        }

        if ($request->filled('branch_ids')) {
            return 'selected';
        }

        return 'all';
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->input('period');

        if (! array_key_exists($period, $this->periodOptions())) {
            $period = $request->filled('start_date') || $request->filled('end_date') ? 'custom' : 'month';
        }

        $today = now();

        [$startDate, $endDate] = match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'week' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY)],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [
                $this->parseDate($request->input('start_date'), $today->copy()->startOfMonth()),
                $this->parseDate($request->input('end_date'), $today),
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };

        if ($startDate->greaterThan($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$period, $startDate->startOfDay(), $endDate->startOfDay()];
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if (! $value) {
            return $fallback;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function periodOptions(): array
    {
        return [
            'today' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => 'Custom',
        ];
    }

    private function presetDates(): array
    {
        $today = now();

        return [
            'today' => [
                'start' => $today->copy()->toDateString(),
                'end' => $today->copy()->toDateString(),
            ],
            'week' => [
                'start' => $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'end' => $today->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ],
            'month' => [
                'start' => $today->copy()->startOfMonth()->toDateString(),
                'end' => $today->copy()->endOfMonth()->toDateString(),
            ],
            'year' => [
                'start' => $today->copy()->startOfYear()->toDateString(),
                'end' => $today->copy()->endOfYear()->toDateString(),
            ],
        ];
    }

    private function hubLabel(string $hubScope, ?Branch $selectedBranch, $selectedBranches): string
    {
        if ($hubScope === 'single' && $selectedBranch) {
            return $selectedBranch->name;
        }

        if ($hubScope === 'selected' && $selectedBranches->isNotEmpty()) {
            return $selectedBranches->count().' hub dipilih';
        }

        return 'Semua Hub';
    }

    private function filterQuery(
        string $period,
        string $startDate,
        string $endDate,
        string $hubScope,
        ?int $branchId,
        array $branchIds
    ): array {
        $query = [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'hub_scope' => $hubScope,
        ];

        if ($hubScope === 'single' && $branchId) {
            $query['branch_id'] = $branchId;
        }

        if ($hubScope === 'selected' && $branchIds !== []) {
            $query['branch_ids'] = $branchIds;
        }

        return $query;
    }

    private function chartGranularity(string $period, Carbon $startDate, Carbon $endDate): string
    {
        if ($period === 'year' || $startDate->diffInDays($endDate) > 62) {
            return 'month';
        }

        return 'day';
    }

    private function chartData($query, Carbon $startDate, Carbon $endDate, string $granularity): array
    {
        $payments = $query
            ->select(['payment_date', 'amount'])
            ->orderBy('payment_date')
            ->get();

        $groupedRevenue = $payments->groupBy(function (Payment $payment) use ($granularity) {
            $date = Carbon::parse($payment->payment_date);

            return $granularity === 'month'
                ? $date->format('Y-m')
                : $date->toDateString();
        })->map(fn ($items) => (float) $items->sum('amount'));

        $cursor = $granularity === 'month'
            ? $startDate->copy()->startOfMonth()
            : $startDate->copy();

        $end = $granularity === 'month'
            ? $endDate->copy()->startOfMonth()
            : $endDate->copy();

        $data = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $granularity === 'month' ? $cursor->format('Y-m') : $cursor->toDateString();
            $data[] = [
                'key' => $key,
                'label' => $granularity === 'month' ? $cursor->format('M Y') : $cursor->format('d M'),
                'revenue' => (float) ($groupedRevenue[$key] ?? 0),
            ];

            $granularity === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return $data;
    }
}
