<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'manager' || ! $user->branch_id) {
            abort(403, 'Akses terbatas untuk Manajer Cabang.');
        }

        $branchId = $user->branch_id;

        // Get daily stats (today)
        $today = now()->format('Y-m-d');

        $dailyPayments = Payment::whereHas('shipment', function ($q) use ($branchId) {
            $q->where('origin_branch_id', $branchId);
        })
            ->whereDate('payment_date', $today)
            ->where('payment_status', 'paid')
            ->get();

        $dailyCash = $dailyPayments->where('payment_method', 'cash')->sum('amount');
        $dailyDigital = $dailyPayments->whereIn('payment_method', ['transfer', 'e-wallet'])->sum('amount');
        $dailyOmset = $dailyCash + $dailyDigital;

        // Fetch paginated history limit to hub
        $payments = Payment::with(['shipment.originBranch', 'shipment.destinationBranch'])
            ->whereHas('shipment', function ($q) use ($branchId) {
                $q->where('origin_branch_id', $branchId);
            })
            ->where('payment_status', 'paid')
            ->latest('payment_date')
            ->paginate(20);

        return view('be.finance.index', compact('dailyCash', 'dailyDigital', 'dailyOmset', 'payments'));
    }
}
