<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\PickupRequest;
use App\Models\Shipment;
use App\Models\User;
use App\Support\RoleProfile;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isHubOps = in_array($user->role, ['manager', 'cashier'], true);
        $isCashier = $user->role === 'cashier';
        $isAdmin = $user->role === 'admin';
        $roleProfile = RoleProfile::for($user->loadMissing('branch'));

        $activeShipments = 0;
        $couriersCount = 0;
        $recentOperations = collect();
        $shipmentLinksEnabled = false;
        $recentPickups = collect();
        $pendingPickups = 0;
        $assignedPickups = 0;
        $stat1Label = '';
        $stat1Value = 0;
        $stat2Label = '';
        $stat2Value = 0;
        $todayOps = [
            'registered_today' => 0,
            'outbound_today' => 0,
            'inbound_waiting' => 0,
            'failed_delivery' => 0,
            'open_exceptions' => 0,
        ];
        $adminEconomy = [];
        $hubEconomics = collect();

        if (! $isAdmin) {
            $activeBase = Shipment::query()->where('status', '!=', 'delivered');
            $recentBase = Shipment::with(['originBranch', 'destinationBranch', 'payment', 'legs', 'exceptions'])->latest();
            $todayBase = Shipment::query();

            if ($user->role === 'courier') {
                $activeBase->where('courier_id', $user->id);
                $recentBase->where('courier_id', $user->id);
                $todayBase->where('courier_id', $user->id);
            } elseif ($user->branch_id && in_array($user->role, ['manager', 'cashier'], true)) {
                $bid = (int) $user->branch_id;
                $hubScope = function ($q) use ($bid) {
                    $q->where('origin_branch_id', $bid)
                        ->orWhere('destination_branch_id', $bid)
                        ->orWhereHas('legs', function ($query) use ($bid) {
                            $query->where('origin_branch_id', $bid)
                                ->orWhere('destination_branch_id', $bid);
                        });
                };
                $activeBase->where($hubScope);
                $recentBase->where($hubScope);
                $todayBase->where($hubScope);
            }

            $activeShipments = $activeBase->count();

            $couriersQuery = User::where('role', 'courier');
            if ($user->branch_id && in_array($user->role, ['manager', 'cashier'], true)) {
                $couriersQuery->where('branch_id', $user->branch_id);
            }
            $couriersCount = $couriersQuery->count();

            $recentOperations = $recentBase->limit(5)->get();

            $shipmentLinksEnabled = in_array($user->role, ['manager', 'cashier', 'courier'], true);

            $pickupBase = PickupRequest::query();
            if ($user->branch_id && $isHubOps) {
                $pickupBase->where(function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
                });
            }

            $pendingPickups = (clone $pickupBase)->where('status', 'pending')->count();
            $assignedPickups = (clone $pickupBase)->where('status', 'assigned')->count();
            $recentPickups = (clone $pickupBase)->latest()->limit(5)->get();

            $stat1Label = $isCashier ? 'PENDING PICKUPS // HUB QUEUE' : 'ACTIVE SHIPMENTS // HUB SCOPE';
            $stat1Value = $isCashier ? $pendingPickups : $activeShipments;

            $stat2Label = $isCashier ? 'ASSIGNED PICKUPS' : 'DEPLOYED COURIERS';
            $stat2Value = $isCashier ? $assignedPickups : $couriersCount;

            $todayOps = [
                'registered_today' => (clone $todayBase)->whereDate('created_at', today())->count(),
                'outbound_today' => (clone $todayBase)->whereHas('legs', function ($query) use ($user) {
                    $query->whereDate('departed_at', today())
                        ->when($user->branch_id && in_array($user->role, ['manager', 'cashier'], true), fn ($query) => $query->where('origin_branch_id', $user->branch_id));
                })->count(),
                'inbound_waiting' => (clone $todayBase)->whereHas('legs', function ($query) use ($user) {
                    $query->where('status', 'departed')
                        ->when($user->branch_id && in_array($user->role, ['manager', 'cashier'], true), fn ($query) => $query->where('destination_branch_id', $user->branch_id));
                })->count(),
                'failed_delivery' => (clone $todayBase)->whereIn('status', ['delivery_failed', 'rescheduled', 'returned_to_hub'])->count(),
                'open_exceptions' => (clone $todayBase)->where(function ($query) {
                    $query->whereIn('status', ['held', 'damaged', 'lost', 'exception'])
                        ->orWhereHas('exceptions', fn ($query) => $query->where('status', 'open'));
                })->count(),
            ];
        }

        if ($isAdmin) {
            $monthStart = now()->startOfMonth()->format('Y-m-d');
            $monthEnd = now()->format('Y-m-d');

            $paidHubRows = Payment::query()
                ->join('shipments', 'payments.shipment_id', '=', 'shipments.id')
                ->join('branches', 'shipments.origin_branch_id', '=', 'branches.id')
                ->selectRaw('
                    branches.id as branch_id,
                    branches.name as branch_name,
                    branches.city as branch_city,
                    SUM(payments.amount) as revenue,
                    SUM(CASE WHEN payments.payment_method = ? THEN payments.amount ELSE 0 END) as cash,
                    SUM(CASE WHEN payments.payment_method IN (?, ?) THEN payments.amount ELSE 0 END) as digital,
                    COUNT(*) as paid_packages
                ', ['cash', 'transfer', 'e-wallet'])
                ->where('payment_status', 'paid')
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->groupBy('branches.id', 'branches.name', 'branches.city')
                ->orderByDesc('revenue')
                ->get();

            $pendingByHub = Payment::query()
                ->join('shipments', 'payments.shipment_id', '=', 'shipments.id')
                ->selectRaw('shipments.origin_branch_id as branch_id, COUNT(*) as pending_payments')
                ->where('payment_status', '!=', 'paid')
                ->groupBy('shipments.origin_branch_id')
                ->pluck('pending_payments', 'branch_id');

            $hubEconomics = $paidHubRows
                ->map(function ($row) use ($pendingByHub) {
                    return [
                        'branch' => (object) [
                            'id' => (int) $row->branch_id,
                            'name' => $row->branch_name,
                            'city' => $row->branch_city,
                        ],
                        'revenue' => (float) $row->revenue,
                        'cash' => (float) $row->cash,
                        'transfer' => (float) $row->digital,
                        'paid_packages' => (int) $row->paid_packages,
                        'pending_payments' => (int) ($pendingByHub[(int) $row->branch_id] ?? 0),
                    ];
                })
                ->values()
                ->take(10);

            $totalRevenue = $paidHubRows->sum(fn ($row) => (float) $row->revenue);
            $cashRevenue = $paidHubRows->sum(fn ($row) => (float) $row->cash);
            $digitalRevenue = $paidHubRows->sum(fn ($row) => (float) $row->digital);
            $paidPackageCount = $paidHubRows->sum(fn ($row) => (int) $row->paid_packages);
            $activeHubCount = $paidHubRows->count();

            $adminEconomy = [
                'month_label' => now()->format('F Y'),
                'total_revenue' => $totalRevenue,
                'cash_revenue' => $cashRevenue,
                'digital_revenue' => $digitalRevenue,
                'paid_packages' => $paidPackageCount,
                'active_hubs' => $activeHubCount,
                'avg_revenue_per_hub' => $activeHubCount > 0 ? (int) round($totalRevenue / $activeHubCount) : 0,
            ];
        }

        return view('be.dashboard', compact(
            'activeShipments',
            'couriersCount',
            'recentOperations',
            'shipmentLinksEnabled',
            'recentPickups',
            'pendingPickups',
            'assignedPickups',
            'isHubOps',
            'isCashier',
            'stat1Label',
            'stat1Value',
            'stat2Label',
            'stat2Value',
            'todayOps',
            'roleProfile',
            'isAdmin',
            'adminEconomy',
            'hubEconomics'
        ));
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return redirect()->route('be.dashboard');
        }

        $user = auth()->user();

        $shipments = Shipment::query()
            ->with(['sender', 'receiver', 'originBranch', 'destinationBranch', 'payment', 'legs', 'exceptions'])
            ->where(function ($query) use ($term) {
                $query->where('tracking_number', 'like', "%{$term}%")
                    ->orWhereHas('sender', function ($query) use ($term) {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%");
                    })
                    ->orWhereHas('receiver', function ($query) use ($term) {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%");
                    });
            });
        $this->scopeShipmentsForUser($shipments, $user);
        $shipments = $shipments->latest()->limit(10)->get();

        if ($shipments->count() === 1 && str_contains(strtoupper($shipments->first()->tracking_number), strtoupper($term))) {
            return redirect()->route('be.shipments.show', $shipments->first());
        }

        $pickups = PickupRequest::query()
            ->with(['branch', 'courier'])
            ->where(function ($query) use ($term) {
                $query->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%")
                    ->orWhere('sender_name', 'like', "%{$term}%")
                    ->orWhere('sender_phone', 'like', "%{$term}%")
                    ->orWhere('receiver_name', 'like', "%{$term}%")
                    ->orWhere('receiver_phone', 'like', "%{$term}%")
                    ->orWhere('pickup_address', 'like', "%{$term}%")
                    ->orWhere('receiver_address', 'like', "%{$term}%");
            });
        $this->scopePickupsForUser($pickups, $user);
        $pickups = $pickups->latest()->limit(8)->get();

        $branches = Branch::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->when($user->role !== 'admin' && $user->branch_id, fn ($query) => $query->where('id', $user->branch_id))
            ->limit(8)
            ->get();

        $users = User::query()
            ->with('branch')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->when($user->role !== 'admin' && $user->branch_id, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->limit(8)
            ->get();

        return view('be.search', compact('term', 'shipments', 'pickups', 'branches', 'users'));
    }

    private function scopeShipmentsForUser($query, User $user): void
    {
        if ($user->role === 'courier') {
            $query->where('courier_id', $user->id);
        } elseif ($user->branch_id && in_array($user->role, ['manager', 'cashier'], true)) {
            $bid = (int) $user->branch_id;
            $query->where(function ($query) use ($bid) {
                $query->where('origin_branch_id', $bid)
                    ->orWhere('destination_branch_id', $bid)
                    ->orWhereHas('legs', function ($query) use ($bid) {
                        $query->where('origin_branch_id', $bid)
                            ->orWhere('destination_branch_id', $bid);
                    });
            });
        }
    }

    private function scopePickupsForUser($query, User $user): void
    {
        if ($user->role === 'courier') {
            $query->where('courier_id', $user->id);
        } elseif ($user->branch_id && in_array($user->role, ['manager', 'cashier'], true)) {
            $query->where(function ($query) use ($user) {
                $query->where('branch_id', $user->branch_id)
                    ->orWhereNull('branch_id');
            });
        }
    }
}
