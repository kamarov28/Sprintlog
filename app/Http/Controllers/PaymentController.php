<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private function assertCanAccessPayment(Payment $payment): void
    {
        $user = auth()->user();
        $shipment = $payment->shipment;

        if (! $shipment) {
            abort(404);
        }

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'courier') {
            if ((int) $shipment->courier_id !== (int) $user->id) {
                abort(403);
            }

            return;
        }

        if (in_array($user->role, ['manager', 'cashier'], true)) {
            if (! $user->branch_id) {
                abort(403, 'Akun staf belum terhubung ke cabang.');
            }

            $branchId = (int) $user->branch_id;
            if ((int) $shipment->origin_branch_id !== $branchId && (int) $shipment->destination_branch_id !== $branchId) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    public function uploadProof(Request $request, Payment $payment)
    {
        $this->assertCanAccessPayment($payment);

        $request->validate([
            'proof_file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
        ]);

        if ($request->hasFile('proof_file')) {
            $file = $request->file('proof_file');
            $filename = 'proof_'.$payment->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('proofs', $filename, 'public');

            $payment->update([
                'proof_file' => $path,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'payment_status' => 'pending_verification',
            ]);
        }

        return back()->with('success', 'Bukti pembayaran berhasil diupload dan menunggu verifikasi.');
    }

    public function verify(Request $request, Payment $payment)
    {
        $this->assertCanAccessPayment($payment);

        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Unauthorized');
        }

        if ((int) $payment->shipment->origin_branch_id !== (int) auth()->user()->branch_id) {
            abort(403, 'Verifikasi transfer hanya dapat dilakukan oleh hub asal pembayaran.');
        }

        if ($payment->payment_method !== 'transfer') {
            abort(422, 'Pembayaran ini bukan transfer.');
        }

        if (! $payment->proof_file) {
            return back()->with('error', 'Bukti transfer belum tersedia.');
        }

        $request->validate([
            'decision' => 'required|in:approve,reject',
        ]);

        $payment->update([
            'payment_status' => $request->decision === 'approve' ? 'paid' : 'failed',
            'payment_date' => $request->decision === 'approve' ? now() : null,
        ]);

        return back()->with('success', $request->decision === 'approve' ? 'Transfer disetujui.' : 'Transfer ditolak.');
    }

    public function printProof(Payment $payment)
    {
        $this->assertCanAccessPayment($payment);

        $pdf = Pdf::loadView('be.payments.proof', compact('payment'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('bukti-pembayaran-'.$payment->id.'.pdf');
    }
}
