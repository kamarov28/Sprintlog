<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PickupRequest;
use App\Models\PickupStatusAudit;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Services\CourierAssignmentService;
use App\Services\RouteDistanceService;
use App\Services\ShippingCostService;
use App\Services\ShipmentNotifier;
use App\Services\ShipmentRoutePlanner;
use App\Support\RouteEstimate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PickupController extends Controller
{
    private function assertPickupAccess(PickupRequest $pickup): void
    {
        $user = auth()->user();
        $branchId = $user->branch_id ? (int) $user->branch_id : null;
        $pickupBranchId = $pickup->branch_id ? (int) $pickup->branch_id : null;

        if (in_array($user->role, ['manager', 'cashier'], true) && $branchId === null) {
            abort(403, 'Akun staf belum terhubung ke cabang.');
        }

        if ($user->role === 'courier') {
            if ((int) $pickup->courier_id !== (int) $user->id) {
                abort(403, 'Unauthorized');
            }

            return;
        }

        if ($branchId !== null && $pickupBranchId !== null && $pickupBranchId !== $branchId) {
            abort(403, 'Pickup berada di cabang lain.');
        }
    }

    private function resolveBranchId(Request $request): ?int
    {
        // 1) If staff creates request, trust their branch first.
        if (auth()->check() && auth()->user()->branch_id) {
            return (int) auth()->user()->branch_id;
        }

        // 2) If geolocation available, pick nearest branch with coordinates.
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        if (is_numeric($lat) && is_numeric($lng)) {
            $lat = (float) $lat;
            $lng = (float) $lng;

            $branches = Branch::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get(['id', 'latitude', 'longitude']);

            $nearest = null;
            $best = INF;
            foreach ($branches as $branch) {
                $d = ($lat - (float) $branch->latitude) ** 2 + ($lng - (float) $branch->longitude) ** 2;
                if ($d < $best) {
                    $best = $d;
                    $nearest = $branch;
                }
            }

            if ($nearest) {
                return (int) $nearest->id;
            }
        }

        // 3) Fallback: infer from address text by city name.
        $address = strtolower((string) $request->input('pickup_address', ''));
        if ($address !== '') {
            $byCity = Branch::query()->get(['id', 'city']);
            foreach ($byCity as $branch) {
                $city = strtolower((string) $branch->city);
                if ($city !== '' && str_contains($address, $city)) {
                    return (int) $branch->id;
                }
            }
        }

        return null;
    }

    // Public store
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'pickup_source' => 'nullable|in:profile,custom',
            'pickup_address' => 'required_if:pickup_source,custom|nullable|string',
            'pickup_date' => 'required|date|after_or_equal:today',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'receiver_name' => 'required|string',
            'receiver_phone' => 'required|string',
            'receiver_address' => 'required|string',
        ]);

        $pickupSource = $request->input('pickup_source', 'custom');
        $pickupAddress = (string) $request->input('pickup_address', '');
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        if ($pickupSource === 'profile' && auth()->check()) {
            $profileAddress = (string) auth()->user()->address;
            if ($profileAddress !== '') {
                $pickupAddress = $profileAddress;
                $lat = auth()->user()->latitude;
                $lng = auth()->user()->longitude;
            }
        }

        if (trim($pickupAddress) === '') {
            return back()->withErrors(['pickup_address' => 'Alamat pickup wajib diisi.'])->withInput();
        }

        $data = [
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'pickup_address' => $pickupAddress,
            'pickup_date' => $request->pickup_date,
            'latitude' => $lat,
            'longitude' => $lng,
            'receiver_name' => $request->receiver_name,
            'receiver_phone' => $request->receiver_phone,
            'receiver_address' => $request->receiver_address,
            'sender_name' => $request->customer_name,
            'sender_phone' => $request->customer_phone,
            'sender_address' => $pickupAddress,
            'sender_latitude' => $lat,
            'sender_longitude' => $lng,
        ];
        if (auth()->check()) {
            $data['user_id'] = auth()->id();
        }
        if (Schema::hasColumn('pickup_requests', 'branch_id')) {
            $data['branch_id'] = $this->resolveBranchId($request);
        }

        $pickup = PickupRequest::create($data);
        $this->auditPickup($request, $pickup, 'created', null, $pickup->status, null, $pickup->payment_status, 'Pickup request created.');

        return back()->with('pickup_success', true);
    }

    /** Antrian pickup per cabang — hanya diakses manajer/kasir (lihat routes). */
    public function index()
    {
        $query = PickupRequest::with('courier', 'user', 'branch', 'shipment.courier', 'senderCity', 'receiverCity', 'cashCollector', 'cashVerifier', 'latestStatusAudit');
        $hasBranchColumn = Schema::hasColumn('pickup_requests', 'branch_id');

        if (in_array(auth()->user()->role, ['manager', 'cashier'], true) && ! auth()->user()->branch_id) {
            abort(403, 'Akun staf belum terhubung ke cabang.');
        }

        if ($hasBranchColumn && auth()->user()->branch_id) {
            $branchId = (int) auth()->user()->branch_id;
            $query->where(function ($q) use ($branchId) {
                // Include unassigned (legacy/new public requests) so hub can process them.
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        if (auth()->user()->role === 'courier') {
            $query->where('courier_id', auth()->id());
        }

        $pickups = $query->latest()->paginate(15);
        $couriers = User::with('vehicle')->where('role', 'courier');

        if (auth()->user()->branch_id) {
            $couriers->where('branch_id', auth()->user()->branch_id);
        }

        $couriers = $couriers->get();

        return view('be.pickups.index', compact('pickups', 'couriers'));
    }

    public function show(PickupRequest $pickup)
    {
        $this->assertPickupAccess($pickup);

        $pickup->load([
            'courier',
            'user',
            'branch',
            'shipment',
            'senderCity',
            'receiverCity',
            'cashCollector',
            'cashVerifier',
            'statusAudits.user',
        ]);

        $couriers = User::with('vehicle')->where('role', 'courier');
        if (auth()->user()->branch_id) {
            $couriers->where('branch_id', auth()->user()->branch_id);
        }

        $hub = $pickup->branch ?: auth()->user()->branch;
        $hubPoint = app(RouteDistanceService::class)->pointForBranch($hub);

        $routeEstimate = RouteEstimate::make([
            [
                'label' => 'Hub awal',
                'address' => $hubPoint['address'] ?? ($hub?->name.' - '.$hub?->city),
                'lat' => $hubPoint['lat'] ?? null,
                'lng' => $hubPoint['lng'] ?? null,
            ],
            [
                'label' => 'Titik pickup',
                'address' => $pickup->sender_address ?: $pickup->pickup_address,
                'lat' => $pickup->sender_latitude ?: $pickup->latitude,
                'lng' => $pickup->sender_longitude ?: $pickup->longitude,
            ],
            [
                'label' => 'Alamat penerima',
                'address' => $pickup->receiver_address,
                'lat' => $pickup->receiver_latitude,
                'lng' => $pickup->receiver_longitude,
            ],
        ], [
            'label' => 'Pickup to destination',
            'speed_kmh' => 28,
            'note' => 'Estimasi rute kurir dari hub ke titik pickup lalu ke alamat penerima. Buka Google Maps untuk navigasi real-time.',
        ]);

        $assignmentRecommendation = $canRecommend = null;
        if (in_array(auth()->user()->role, ['manager', 'cashier'], true) && ! $pickup->courier) {
            $assignmentRecommendation = app(CourierAssignmentService::class)->recommendForPickup($pickup);
            $canRecommend = (bool) $assignmentRecommendation;
        }

        return view('be.pickups.show', [
            'pickup' => $pickup,
            'couriers' => $couriers->get(),
            'routeEstimate' => $routeEstimate,
            'assignmentRecommendation' => $assignmentRecommendation,
            'canAutoAssign' => $canRecommend,
        ]);
    }

    public function assign(Request $request, PickupRequest $pickup)
    {
        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Pickup assignment hanya bisa dilakukan Manager Hub atau Kasir Hub.');
        }

        $branchId = auth()->user()->branch_id;
        if (! $branchId) {
            abort(403, 'Akun staf belum terhubung ke cabang.');
        }

        $hasBranchColumn = Schema::hasColumn('pickup_requests', 'branch_id');
        if ($hasBranchColumn && $branchId && $pickup->branch_id && (int) $pickup->branch_id !== (int) $branchId) {
            abort(403);
        }

        $request->validate(['courier_id' => 'required|exists:users,id']);

        $courier = User::with('vehicle')->where('id', $request->courier_id)->where('role', 'courier')->firstOrFail();
        if ($branchId && $courier->branch_id && (int) $courier->branch_id !== (int) $branchId) {
            abort(403);
        }

        $assignmentError = app(CourierAssignmentService::class)->validatePickupCourier($pickup, $courier);
        if ($assignmentError) {
            return back()->withErrors(['courier_id' => $assignmentError]);
        }

        $payload = [
            'courier_id' => $request->courier_id,
            'status' => 'assigned',
        ];
        // If pickup came in without branch assignment, claim it into current hub on first assign.
        if ($hasBranchColumn && $branchId && ! $pickup->branch_id) {
            $payload['branch_id'] = $branchId;
        }

        $previousStatus = $pickup->status;
        $pickup->update($payload);
        $this->auditPickup($request, $pickup->fresh(), 'courier_assigned', $previousStatus, 'assigned', null, null, 'Courier assigned to pickup.');

        return back()->with('success', 'Courier assigned to pickup unit.');
    }

    public function autoAssign(Request $request, PickupRequest $pickup)
    {
        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Pickup assignment hanya bisa dilakukan Manager Hub atau Kasir Hub.');
        }

        $branchId = auth()->user()->branch_id;
        if (! $branchId) {
            abort(403, 'Akun staf belum terhubung ke cabang.');
        }

        $hasBranchColumn = Schema::hasColumn('pickup_requests', 'branch_id');
        if ($hasBranchColumn && $pickup->branch_id && (int) $pickup->branch_id !== (int) $branchId) {
            abort(403);
        }

        if ($pickup->courier_id) {
            return back()->with('success', 'Pickup ini sudah punya kurir.');
        }

        if ($hasBranchColumn && ! $pickup->branch_id) {
            $pickup->update(['branch_id' => $branchId]);
            $pickup->refresh();
        }

        $previousStatus = $pickup->status;
        $recommendation = app(CourierAssignmentService::class)->assignRecommended($pickup);

        if (! $recommendation) {
            return back()->withErrors(['courier_id' => 'Belum ada kurir available dengan kendaraan aktif dan kapasitas cukup.']);
        }

        $description = 'Auto assigned to '.$recommendation['courier']->name
            .' using '.$recommendation['vehicle']->label()
            .'. Score: '.$recommendation['score']
            .($recommendation['distance_km'] ? '. Estimasi ke pickup: '.$recommendation['distance_km'].' KM' : '');

        $this->auditPickup($request, $pickup->fresh(), 'courier_auto_assigned', $previousStatus, 'assigned', null, null, $description);

        return back()->with('success', 'Auto assign memilih '.$recommendation['courier']->name.' untuk pickup ini.');
    }

    public function updateStatus(Request $request, PickupRequest $pickup)
    {
        $this->assertPickupAccess($pickup);

        $request->validate(['status' => 'required|in:picked_up,hub_received,cancelled']);

        $branchId = auth()->user()->branch_id ? (int) auth()->user()->branch_id : null;
        $baseUpdate = [];
        if ($branchId && ! $pickup->branch_id && auth()->user()->role !== 'courier') {
            $baseUpdate['branch_id'] = $branchId;
        }

        // Couriers must upload proof when confirming pickup
        if ($request->status === 'picked_up') {
            $rules = ['proof_image' => 'required|image|max:5120'];
            if ($pickup->payment_method === 'cash_on_pickup') {
                $rules['cash_received_amount'] = 'required|numeric|min:0';
            }
            $request->validate($rules);

            $path = $request->file('proof_image')->store('pickups', 'public');
            $payload = array_merge($baseUpdate, [
                'status' => 'picked_up',
                'proof_of_pickup' => $path,
            ]);

            if ($pickup->payment_method === 'cash_on_pickup') {
                $payload['payment_status'] = 'cash_collected_by_courier';
                $payload['cash_received_amount'] = $request->cash_received_amount;
                $payload['cash_collected_at'] = now();
                $payload['cash_collected_by'] = auth()->id();
            }

            $previousStatus = $pickup->status;
            $previousPaymentStatus = $pickup->payment_status;
            $pickup->update($payload);
            $this->auditPickup(
                $request,
                $pickup->fresh(),
                'picked_up',
                $previousStatus,
                'picked_up',
                $previousPaymentStatus,
                $payload['payment_status'] ?? $previousPaymentStatus,
                'Courier confirmed pickup with photo proof.'
            );

            return back()->with('success', 'Pickup dikonfirmasi dengan bukti foto.');
        }

        // hub_received: only manager/cashier
        if ($request->status === 'hub_received') {
            if (! in_array(auth()->user()->role, ['manager', 'cashier'])) {
                abort(403, 'Unauthorized');
            }
            $previousStatus = $pickup->status;
            $pickup->update(array_merge($baseUpdate, ['status' => 'hub_received']));
            $this->auditPickup($request, $pickup->fresh(), 'hub_received', $previousStatus, 'hub_received', null, null, 'Pickup received at hub.');

            return back()->with('success', 'Paket diterima di hub.');
        }

        $previousStatus = $pickup->status;
        $pickup->update(array_merge($baseUpdate, ['status' => $request->status]));
        $this->auditPickup($request, $pickup->fresh(), $request->status, $previousStatus, $request->status, null, null, 'Pickup status updated.');

        return back()->with('success', 'Pickup status updated successfully.');
    }

    public function updatePayment(Request $request, PickupRequest $pickup)
    {
        $this->assertPickupAccess($pickup);

        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Unauthorized');
        }

        if ($pickup->payment_method !== 'cash_on_pickup') {
            abort(422, 'Pembayaran pickup ini bukan tunai.');
        }

        if ($pickup->payment_status !== 'cash_collected_by_courier') {
            return back()->with('error', 'Status pembayaran belum siap diverifikasi kasir.');
        }

        $request->validate([
            'verified_cash_amount' => 'required|numeric|min:0',
        ]);

        $reportedAmount = round((float) $pickup->cash_received_amount, 2);
        $verifiedAmount = round((float) $request->verified_cash_amount, 2);

        if ($verifiedAmount !== $reportedAmount) {
            return back()->with('error', 'Nominal setor tidak cocok dengan uang yang dilaporkan kurir. Mohon cek sebelum verifikasi.');
        }

        $previousPaymentStatus = $pickup->payment_status;
        $pickup->update([
            'payment_status' => 'paid',
            'cash_handover_at' => now(),
            'cash_verified_by' => auth()->id(),
        ]);
        $this->auditPickup($request, $pickup->fresh(), 'cash_verified', null, null, $previousPaymentStatus, 'paid', 'Cash handover verified by hub cashier.');

        return back()->with('success', 'Setoran tunai pickup berhasil diverifikasi oleh kasir hub.');
    }

    public function verifyTransfer(Request $request, PickupRequest $pickup)
    {
        $this->assertPickupAccess($pickup);

        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Unauthorized');
        }

        if ($pickup->payment_method !== 'transfer') {
            abort(422, 'Pembayaran pickup ini bukan transfer.');
        }

        if (! $pickup->payment_proof) {
            return back()->with('error', 'Bukti transfer belum tersedia.');
        }

        $request->validate([
            'decision' => 'required|in:approve,reject',
            'note' => 'nullable|string|max:255',
        ]);

        $previousPaymentStatus = $pickup->payment_status;
        $nextStatus = $request->decision === 'approve' ? 'paid' : 'transfer_rejected';

        $pickup->update(['payment_status' => $nextStatus]);
        $this->auditPickup(
            $request,
            $pickup->fresh(),
            $request->decision === 'approve' ? 'transfer_approved' : 'transfer_rejected',
            null,
            null,
            $previousPaymentStatus,
            $nextStatus,
            $request->note ?: 'Transfer proof reviewed by hub staff.'
        );

        return back()->with('success', $request->decision === 'approve' ? 'Transfer pickup disetujui.' : 'Transfer pickup ditolak.');
    }

    public function activateShipment(Request $request, PickupRequest $pickup)
    {
        $this->assertPickupAccess($pickup);

        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Unauthorized');
        }

        if ($pickup->shipment_id) {
            return redirect()
                ->route('be.shipments.show', $pickup->shipment_id)
                ->with('success', 'Pickup ini sudah memiliki shipment aktif.');
        }

        if ($pickup->status !== 'hub_received') {
            return back()->with('error', 'Pickup harus diterima di hub sebelum shipment diaktifkan.');
        }

        if (! $this->isPaymentClearForActivation($pickup)) {
            return back()->with('error', 'Pembayaran belum clear. Untuk tunai, kasir harus verifikasi setoran kurir dulu.');
        }

        if (! $pickup->weight || ! $pickup->service_type || ! $pickup->total_price || ! $pickup->sender_city_id || ! $pickup->receiver_city_id) {
            return back()->with('error', 'Data pickup belum lengkap untuk diubah menjadi shipment.');
        }

        $originBranch = $pickup->branch ?: auth()->user()->branch;
        if (! $originBranch) {
            return back()->with('error', 'Pickup belum terhubung ke hub asal.');
        }

        $destinationBranch = $this->resolveDestinationBranch($pickup, $originBranch);
        $rate = $this->resolvePickupRate($pickup);
        $shippingEstimate = null;

        $pickup->loadMissing(['senderCity', 'receiverCity']);
        if ($pickup->senderCity && $pickup->receiverCity) {
            $shippingEstimate = app(ShippingCostService::class)->estimateFromCities(
                $pickup->senderCity,
                $pickup->receiverCity,
                (float) $pickup->weight,
                (string) $pickup->service_type
            );
        }

        if (! $shippingEstimate && $pickup->total_price) {
            $shippingEstimate = [
                'total_price' => (float) $pickup->total_price,
                'total_price_fmt' => 'Rp '.number_format((float) $pickup->total_price, 0, ',', '.'),
                'price_per_kg' => (float) $pickup->total_price / max(0.1, (float) $pickup->weight),
                'estimated_days' => $rate?->estimated_days,
                'service_type' => (string) $pickup->service_type,
                'source' => 'pickup_snapshot',
                'rate_id' => $rate?->id,
                'quote_payload' => [
                    'source' => 'pickup_snapshot',
                    'pickup_request_id' => $pickup->id,
                ],
            ];
        }

        if (! $shippingEstimate) {
            return back()->with('error', 'Ongkir pickup belum tersedia dari RajaOngkir maupun snapshot order.');
        }

        // Apply small pricing adjustment when shipment will traverse multiple legs.
        // Add 10% per extra leg (beyond the first leg) to account for multi-hop handling.
        try {
            $routeBranches = app(\App\Services\ShipmentRoutePlanner::class)->branchesFor($originBranch, $destinationBranch);
            $extraLegs = max(0, $routeBranches->count() - 2);
            if ($extraLegs > 0 && isset($shippingEstimate['total_price'])) {
                $multiplier = 1 + (0.10 * $extraLegs);
                $shippingEstimate['total_price'] = (float) round(((float) $shippingEstimate['total_price']) * $multiplier, 2);
                $shippingEstimate['total_price_fmt'] = 'Rp '.number_format($shippingEstimate['total_price'], 0, ',', '.');
                $shippingEstimate['price_per_kg'] = (float) $shippingEstimate['total_price'] / max(0.1, (float) $pickup->weight);
                $shippingEstimate['legs_count'] = max(1, $routeBranches->count() - 1);
                $shippingEstimate['pricing_multiplier'] = $multiplier;
            }
        } catch (\Throwable) {
            // Non-fatal: keep original estimate if planner fails.
        }

        $totalPrice = (float) $shippingEstimate['total_price'];

        try {
            DB::beginTransaction();

            $senderPhone = $pickup->sender_phone ?: $pickup->customer_phone;
            $sender = Customer::firstOrCreate(
                ['phone' => $senderPhone],
                [
                    'name' => $pickup->sender_name ?: $pickup->customer_name,
                    'city' => optional($pickup->senderCity)->name ?: $originBranch->city,
                    'email' => $senderPhone.'@sprintlog.local',
                    'password' => bcrypt(Str::random(12)),
                    'address' => $pickup->sender_address ?: $pickup->pickup_address,
                ]
            );

            $senderUpdates = [];
            if (($pickup->sender_name ?: $pickup->customer_name) && $sender->name !== ($pickup->sender_name ?: $pickup->customer_name)) {
                $senderUpdates['name'] = $pickup->sender_name ?: $pickup->customer_name;
            }
            if (($pickup->sender_address ?: $pickup->pickup_address) && $sender->address !== ($pickup->sender_address ?: $pickup->pickup_address)) {
                $senderUpdates['address'] = $pickup->sender_address ?: $pickup->pickup_address;
            }
            if ($senderUpdates) {
                $sender->update($senderUpdates);
            }

            $receiver = Customer::firstOrCreate(
                ['phone' => $pickup->receiver_phone],
                [
                    'name' => $pickup->receiver_name,
                    'city' => optional($pickup->receiverCity)->name ?: $destinationBranch->city,
                    'email' => $pickup->receiver_phone.'@sprintlog.local',
                    'password' => bcrypt(Str::random(12)),
                    'address' => $pickup->receiver_address ?: '',
                ]
            );

            if ($pickup->receiver_address && ! $receiver->address) {
                $receiver->update(['address' => $pickup->receiver_address]);
            }

            $shipment = Shipment::create([
                'user_id' => $pickup->user_id,
                'tracking_number' => 'SPRINT-'.date('Ymd').'-'.strtoupper(Str::random(4)).'_'.$pickup->service_type,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'origin_branch_id' => $originBranch->id,
                'destination_branch_id' => $destinationBranch->id,
                'courier_id' => null,
                'rate_id' => $shippingEstimate['rate_id'] ?? $rate?->id,
                'total_weight' => $pickup->weight,
                'total_price' => $totalPrice,
                ...$this->shippingSnapshotAttributes($shippingEstimate),
                'status' => 'pending',
                'shipment_date' => now(),
            ]);

            Payment::create([
                'shipment_id' => $shipment->id,
                'amount' => $totalPrice,
                'payment_method' => $pickup->payment_method === 'cash_on_pickup' ? 'cash' : 'transfer',
                'payment_status' => 'paid',
                'payment_date' => now(),
                'amount_received' => $pickup->payment_method === 'cash_on_pickup' ? $pickup->cash_received_amount : null,
                'change_amount' => 0,
                'proof_file' => $pickup->payment_method === 'transfer' ? $pickup->payment_proof : null,
            ]);

            ShipmentItem::create([
                'shipment_id' => $shipment->id,
                'item_name' => 'Paket pickup #'.$pickup->id,
                'quantity' => 1,
                'weight' => $pickup->weight,
            ]);

            $shipment->trackings()->create([
                'location' => $originBranch->name,
                'description' => 'Pickup sudah diterima hub dan shipment aktif.',
                'status' => 'pending',
                'tracked_at' => now(),
            ]);

            $shipment->statusAudits()->create([
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => 'pending',
                'location' => $originBranch->name,
                'description' => 'Shipment dibuat dari pickup request #'.$pickup->id.'.',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]);

            app(ShipmentNotifier::class)->record(
                $shipment,
                'shipment_activated',
                'Shipment aktif',
                'Nomor resi '.$shipment->tracking_number.' sudah aktif dan bisa dilacak.'
            );

            app(ShipmentRoutePlanner::class)->createLegsFor($shipment);

            $pickup->update([
                'shipment_id' => $shipment->id,
                'total_price' => $totalPrice,
            ]);
            $this->auditPickup($request, $pickup->fresh(), 'shipment_activated', null, null, null, null, 'Shipment '.$shipment->tracking_number.' activated from pickup.');

            DB::commit();

            return redirect()
                ->route('be.shipments.show', $shipment)
                ->with('success', 'Shipment berhasil diaktifkan dari pickup request.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal mengaktifkan shipment: '.$e->getMessage());
        }
    }

    private function isPaymentClearForActivation(PickupRequest $pickup): bool
    {
        if ($pickup->payment_method === 'cash_on_pickup') {
            return $pickup->payment_status === 'paid';
        }

        if ($pickup->payment_method === 'transfer') {
            return $pickup->payment_status === 'paid' && (bool) $pickup->payment_proof;
        }

        return false;
    }

    private function shippingSnapshotAttributes(array $estimate): array
    {
        $map = [
            'shipping_cost_source' => $estimate['source'] ?? null,
            'shipping_courier_code' => $estimate['courier'] ?? null,
            'shipping_courier_name' => $estimate['courier_name'] ?? null,
            'shipping_courier_service' => $estimate['courier_service'] ?? null,
            'shipping_courier_description' => $estimate['courier_description'] ?? null,
            'shipping_origin_ro_id' => $estimate['origin_ro_id'] ?? null,
            'shipping_destination_ro_id' => $estimate['destination_ro_id'] ?? null,
            'shipping_estimated_days' => $estimate['estimated_days'] ?? null,
            'shipping_quote_payload' => $estimate['quote_payload'] ?? $estimate,
        ];

        return collect($map)
            ->filter(fn ($value, string $column) => Schema::hasColumn('shipments', $column))
            ->all();
    }

    private function resolvePickupRate(PickupRequest $pickup): ?Rate
    {
        $originCity = $pickup->senderCity;
        $destinationCity = $pickup->receiverCity;

        if (! $originCity || ! $destinationCity) {
            return null;
        }

        return Rate::where('origin_zone', $originCity->zone)
            ->where('destination_zone', $destinationCity->zone)
            ->first();
    }

    private function resolveDestinationBranch(PickupRequest $pickup, Branch $fallbackBranch): Branch
    {
        if ($pickup->receiver_latitude && $pickup->receiver_longitude) {
            $nearest = $this->nearestBranch((float) $pickup->receiver_latitude, (float) $pickup->receiver_longitude);
            if ($nearest) {
                return $nearest;
            }
        }

        $receiverCity = $pickup->receiverCity;
        if ($receiverCity) {
            $provinceName = $receiverCity->parentLocation?->name;
            if ($provinceName) {
                $branch = Branch::where('city', $provinceName)
                    ->orWhere('name', 'like', '%'.$provinceName.'%')
                    ->first();

                if ($branch) {
                    return $branch;
                }
            }

            $cityName = preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $receiverCity->name);
            $branch = Branch::where('city', 'like', '%'.$cityName.'%')->first()
                ?: Branch::query()->select(['id', 'name', 'city', 'address', 'phone', 'latitude', 'longitude'])->get()->first(function (Branch $branch) use ($receiverCity) {
                    return str_contains(strtolower($receiverCity->name), strtolower((string) $branch->city));
                });

            if ($branch) {
                return $branch;
            }
        }

        return $fallbackBranch;
    }

    private function nearestBranch(float $lat, float $lng): ?Branch
    {
        $branches = Branch::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $nearest = null;
        $best = INF;

        foreach ($branches as $branch) {
            $distance = ($lat - (float) $branch->latitude) ** 2 + ($lng - (float) $branch->longitude) ** 2;
            if ($distance < $best) {
                $best = $distance;
                $nearest = $branch;
            }
        }

        return $nearest;
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
            'user_id' => auth()->id(),
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

    public function printReceipt(PickupRequest $pickup)
    {
        if (! in_array(auth()->user()->role, ['manager', 'cashier'])) {
            abort(403);
        }

        $branch = $pickup->branch ?? auth()->user()->branch ?? null;
        $pdf = Pdf::loadView('be.pickups.pdf', compact('pickup', 'branch'))
            ->setPaper([0, 0, 226.77, 600], 'portrait'); // 80mm wide thermal

        return $pdf->stream('resi-'.$pickup->id.'.pdf');
    }
}
