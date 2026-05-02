<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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
        $branchName = $data['selectedBranch']?->name ?? 'Semua Hub';
        $fileBranch = Str::slug($branchName) ?: 'semua-hub';
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

        if ($user->role === 'admin') {
            $branches = Branch::query()->select(['id', 'name'])->orderBy('name')->get();
            $branchId = $request->get('branch_id');
            $selectedBranch = $branchId ? $branches->firstWhere('id', (int) $branchId) : null;
        } else {
            if (! $user->branch_id) {
                abort(403, 'Akun staf belum terhubung ke cabang.');
            }

            $branchId = $user->branch_id;
            $selectedBranch = $user->branch;
        }

        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $baseQuery = Payment::query()
            ->where('payment_status', 'paid')
            ->whereBetween('payment_date', [$startDate, $endDate]);

        if ($branchId) {
            $baseQuery->whereHas('shipment', function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId);
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

        // Data for chart (monthly revenue)
        $monthlyData = (clone $baseQuery)
            ->selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return compact(
            'branches',
            'payments',
            'totalRevenue',
            'cashPayments',
            'transferPayments',
            'otherPayments',
            'packageCount',
            'averageRevenue',
            'monthlyData',
            'startDate',
            'endDate',
            'branchId',
            'selectedBranch'
        );
    }
}
