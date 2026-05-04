<?php

namespace App\Http\Controllers\Fe;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Location;
use App\Models\PickupRequest;
use App\Models\PickupStatusAudit;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $provinces = Location::query()
            ->where('type', 'provinsi')
            ->selectRaw('MIN(id) as id, name, zone')
            ->groupBy('name', 'zone')
            ->orderBy('name')
            ->get();

        return view('fe.order_create', compact('user', 'provinces'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_phone' => 'required|string|max:20',
            'sender_address' => 'required|string',
            'sender_city_id' => 'required|exists:locations,id',
            'sender_latitude' => 'nullable|numeric',
            'sender_longitude' => 'nullable|numeric',

            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string',
            'receiver_latitude' => 'nullable|numeric',
            'receiver_longitude' => 'nullable|numeric',
            'receiver_city_id' => 'required|exists:locations,id',

            'weight' => 'required|numeric|min:0.1',
            'service_type' => 'required|in:BEST,REGULAR,KARGO',
            'payment_method' => 'required|in:transfer,cash_on_pickup',
            'payment_proof' => 'required_if:payment_method,transfer|nullable|image|mimes:jpg,jpeg,png|max:2048',
            'pickup_date' => 'required|date|after_or_equal:today',
        ]);

        if ($request->service_type === 'KARGO' && (float) $request->weight < 10) {
            return back()
                ->withErrors(['weight' => 'Layanan KARGO membutuhkan berat minimal 10 KG.'])
                ->withInput();
        }

        $pricing = $this->calculatePrice(
            (int) $request->sender_city_id,
            (int) $request->receiver_city_id,
            (float) $request->weight,
            (string) $request->service_type
        );

        if (! $pricing) {
            return back()
                ->withErrors(['receiver_city_id' => 'Rute pengiriman belum tersedia untuk kombinasi kota tersebut.'])
                ->withInput();
        }

        $data = $request->only([
            'sender_name',
            'sender_phone',
            'sender_address',
            'sender_city_id',
            'sender_latitude',
            'sender_longitude',

            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'receiver_city_id',
            'receiver_latitude',
            'receiver_longitude',

            'weight',
            'service_type',
            'payment_method',
            'pickup_date',
        ]);
        $data['user_id'] = Auth::id();
        $data['customer_name'] = $request->sender_name;
        $data['customer_phone'] = $request->sender_phone;
        $data['pickup_address'] = $request->sender_address;
        $data['latitude'] = $request->sender_latitude;
        $data['longitude'] = $request->sender_longitude;
        $data['status'] = 'pending'; // In a real app maybe 'waiting_verification'
        $data['pickup_date'] = $request->pickup_date;
        $data['total_price'] = $pricing['total_price'];
        $data['payment_status'] = $request->payment_method === 'transfer' ? 'pending_transfer_verification' : 'awaiting_pickup_cash';
        $data['cash_received_amount'] = null;
        $data['cash_collected_at'] = null;
        $data['cash_collected_by'] = null;
        $data['cash_handover_at'] = null;
        $data['cash_verified_by'] = null;

        if ($request->payment_method === 'transfer' && $request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payments', 'public');
            $data['payment_proof'] = $path;
        } else {
            $data['payment_proof'] = null;
        }

        // --- NEAREST HUB CALCULATION ---
        if ($request->sender_latitude && $request->sender_longitude) {
            $data['branch_id'] = $this->calculateNearestBranch(
                $request->sender_latitude,
                $request->sender_longitude
            );
        }

        // We map the new fields into the pickup_requests table.
        $pickup = PickupRequest::create($data);
        $this->auditPickup($request, $pickup, 'customer_order_created', null, 'pending', null, $pickup->payment_status, 'Customer created pickup-first shipment request.');

        return redirect()
            ->route('order.confirmation', $pickup)
            ->with('success', 'ORDER REQUEST QUEUED SUCCESSFULLY. OUR TEAM WILL VERIFY YOUR PICKUP AND ACTIVATE THE SHIPMENT FLOW.');
    }

    public function confirmation(PickupRequest $pickup)
    {
        $this->authorizeCustomerPickup($pickup);

        $pickup->load(['senderCity', 'receiverCity', 'branch', 'shipment']);

        return view('fe.order_confirmation', compact('pickup'));
    }

    public function reschedule(Request $request, PickupRequest $pickup)
    {
        $this->authorizeCustomerPickup($pickup);

        if ($pickup->status !== 'pending' || $pickup->shipment_id) {
            return back()->withErrors(['pickup_date' => 'Jadwal hanya bisa diubah sebelum kurir di-assign.']);
        }

        $request->validate([
            'pickup_date' => 'required|date|after_or_equal:today',
        ]);

        $oldDate = $pickup->pickup_date;
        $pickup->update(['pickup_date' => $request->pickup_date]);
        $this->auditPickup($request, $pickup->fresh(), 'customer_rescheduled', $pickup->status, $pickup->status, null, null, 'Customer changed pickup date from '.$oldDate.' to '.$request->pickup_date.'.');

        return back()->with('success', 'Pickup schedule updated.');
    }

    public function cancel(Request $request, PickupRequest $pickup)
    {
        $this->authorizeCustomerPickup($pickup);

        if ($pickup->status !== 'pending' || $pickup->shipment_id) {
            return back()->withErrors(['order' => 'Order hanya bisa dibatalkan sebelum kurir di-assign.']);
        }

        $previousStatus = $pickup->status;
        $pickup->update(['status' => 'cancelled']);
        $this->auditPickup($request, $pickup->fresh(), 'customer_cancelled', $previousStatus, 'cancelled', null, null, 'Customer cancelled pickup request before courier assignment.');

        return back()->with('success', 'Order request cancelled.');
    }

    public function replacePaymentProof(Request $request, PickupRequest $pickup)
    {
        $this->authorizeCustomerPickup($pickup);

        if ($pickup->payment_method !== 'transfer') {
            return back()->withErrors(['payment_proof' => 'Order ini menggunakan metode pembayaran tunai.']);
        }

        if ($pickup->payment_status !== 'transfer_rejected') {
            return back()->withErrors(['payment_proof' => 'Upload ulang hanya tersedia ketika bukti transfer ditolak.']);
        }

        if ($pickup->status === 'cancelled' || $pickup->shipment_id) {
            return back()->withErrors(['payment_proof' => 'Bukti transfer tidak bisa diubah untuk order ini.']);
        }

        $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $path = $request->file('payment_proof')->store('payments', 'public');
        $previousPaymentStatus = $pickup->payment_status;
        $pickup->update([
            'payment_proof' => $path,
            'payment_status' => 'pending_transfer_verification',
        ]);
        $this->auditPickup($request, $pickup->fresh(), 'customer_reuploaded_transfer', null, null, $previousPaymentStatus, 'pending_transfer_verification', 'Customer uploaded replacement transfer proof.');

        return back()->with('success', 'Transfer proof uploaded again. Hub cashier will review it.');
    }

    private function calculatePrice(int $originCityId, int $destinationCityId, float $weight, string $serviceType): ?array
    {
        $originCity = Location::find($originCityId);
        $destinationCity = Location::find($destinationCityId);

        if (! $originCity || ! $destinationCity) {
            return null;
        }

        $rate = Rate::where('origin_zone', $originCity->zone)
            ->where('destination_zone', $destinationCity->zone)
            ->first();

        if (! $rate) {
            return null;
        }

        $multiplier = 1.0;
        if ($serviceType === 'BEST') {
            $multiplier = 1.3;
        } elseif ($serviceType === 'KARGO') {
            $multiplier = 0.7;
        }

        return [
            'total_price' => ($rate->price_per_kg * $multiplier) * max(0.1, $weight),
            'estimated_days' => $serviceType === 'BEST' ? 1 : $rate->estimated_days,
        ];
    }

    /**
     * Haversine formula to find the nearest branch
     */
    private function calculateNearestBranch($lat, $lng)
    {
        $branches = Branch::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'name', 'city', 'address', 'phone', 'latitude', 'longitude']);
        $closestBranch = null;
        $minDistance = INF;

        foreach ($branches as $branch) {
            $distance = $this->haversineDistance(
                $lat, $lng,
                $branch->latitude, $branch->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestBranch = $branch;
            }
        }

        return $closestBranch ? $closestBranch->id : null;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function authorizeCustomerPickup(PickupRequest $pickup): void
    {
        abort_unless((int) $pickup->user_id === (int) Auth::id(), 403);
    }

    private function auditPickup(
        Request $request,
        PickupRequest $pickup,
        string $event,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $fromPaymentStatus = null,
        ?string $toPaymentStatus = null,
        ?string $description = null
    ): void {
        PickupStatusAudit::create([
            'pickup_request_id' => $pickup->id,
            'user_id' => Auth::id(),
            'event' => $event,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'from_payment_status' => $fromPaymentStatus,
            'to_payment_status' => $toPaymentStatus,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);
    }
}
