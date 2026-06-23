<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Payment;
use App\Models\PickupRequest;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\ShipmentManifest;
use App\Models\ShipmentManifestItem;
use App\Models\ShipmentStatusAudit;
use App\Models\User;
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

class ShipmentController extends Controller
{
    private const SHIPMENT_STATUSES = [
        'pending',
        'picked_up',
        'in_transit',
        'arrived_at_branch',
        'out_for_delivery',
        'delivered',
        'cancelled',
        'delivery_failed',
        'rescheduled',
        'returned_to_hub',
        'held',
        'damaged',
        'lost',
        'exception',
    ];

    private const COURIER_TRANSITIONS = [
        'pending' => ['picked_up', 'in_transit'],
        'picked_up' => ['in_transit'],
        'in_transit' => ['arrived_at_branch'],
        'arrived_at_branch' => ['out_for_delivery'],
        'out_for_delivery' => ['delivered', 'delivery_failed', 'returned_to_hub'],
        'delivery_failed' => ['rescheduled', 'out_for_delivery', 'returned_to_hub'],
        'rescheduled' => ['out_for_delivery', 'returned_to_hub'],
        'returned_to_hub' => ['out_for_delivery', 'cancelled'],
        'held' => ['rescheduled', 'returned_to_hub', 'cancelled'],
        'damaged' => ['returned_to_hub', 'cancelled'],
        'lost' => ['cancelled'],
        'exception' => ['rescheduled', 'returned_to_hub', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    protected function hubBranchId(): ?int
    {
        $id = auth()->user()->branch_id;

        return $id !== null ? (int) $id : null;
    }

    protected function assertCanAccessShipment(Shipment $shipment): void
    {
        $user = auth()->user();

        if ($user->role === 'courier') {
            if ((int) $shipment->courier_id !== (int) $user->id) {
                abort(403);
            }

            return;
        }

        if (in_array($user->role, ['manager', 'cashier'], true)) {
            $bid = $this->hubBranchId();
            if ($bid === null) {
                abort(403, 'Akun staf belum terhubung ke cabang.');
            }
            $touches = (int) $shipment->origin_branch_id === $bid
                || (int) $shipment->destination_branch_id === $bid
                || $shipment->legs()->where(function ($query) use ($bid) {
                    $query->where('origin_branch_id', $bid)->orWhere('destination_branch_id', $bid);
                })->exists();

            if (! $touches) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    public function index(Request $request)
    {
        $query = Shipment::with([
            'sender',
            'receiver',
            'originBranch',
            'destinationBranch',
            'courier.vehicle',
            'payment',
            'legs.originBranch',
            'legs.destinationBranch',
            'exceptions',
        ]);

        if (auth()->user()->role === 'courier') {
            $bid = $this->hubBranchId();
            // Kurir bisa lihat: shipment yang di-assign ke dia, atau shipment pending di branch-nya yang belum ada courier
            $query->where(function ($q) use ($bid) {
                $q->where('courier_id', auth()->id())
                    ->when($bid !== null, function ($q) use ($bid) {
                        $q->orWhere(function ($subq) use ($bid) {
                            $subq->where('origin_branch_id', $bid)
                                ->where('status', 'pending')
                                ->whereNull('courier_id');
                        });
                    });
            });
        } elseif (in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            $bid = $this->hubBranchId();
            if ($bid === null) {
                abort(403, 'Akun staf belum terhubung ke cabang.');
            }

            $query->where(function ($q) use ($bid) {
                $q->where('origin_branch_id', $bid)
                    ->orWhere('destination_branch_id', $bid)
                    ->orWhereHas('legs', function ($query) use ($bid) {
                        $query->where('origin_branch_id', $bid)->orWhere('destination_branch_id', $bid);
                    });
            });
        }

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'preset' => trim((string) $request->query('preset', '')),
        ];

        $query
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('tracking_number', 'like', "%{$search}%")
                        ->orWhereHas('sender', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('receiver', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('originBranch', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%");
                        })
                        ->orWhereHas('destinationBranch', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%");
                        });
                });
            });

        $this->applyShipmentPreset($query, $filters['preset']);

        $shipments = $query->latest()->paginate(15);
        $truckCouriers = collect();

        if (auth()->user()->role === 'manager' && auth()->user()->branch_id) {
            $truckCouriers = User::query()
                ->with('vehicle')
                ->where('role', 'courier')
                ->where('branch_id', auth()->user()->branch_id)
                ->whereHas('vehicle', function ($query) {
                    $query->where('type', 'truck')->where('status', 'active');
                })
                ->orderBy('name')
                ->get();
        }

        return view('be.shipments.index', compact('shipments', 'filters', 'truckCouriers'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->role !== 'cashier') {
            abort(403, 'Akses terbatas hanya untuk Kasir.');
        }
        if (! $this->hubBranchId()) {
            abort(403, 'Akun kasir belum terhubung ke cabang.');
        }

        $branches = Branch::query()
            ->select(['id', 'name', 'city'])
            ->orderBy('name')
            ->get();
        $couriers = User::query()
            ->with('vehicle')
            ->select(['id', 'name'])
            ->where('role', 'courier');
        if ($bid = $this->hubBranchId()) {
            $couriers->where('branch_id', $bid);
        }
        $couriers = $couriers->orderBy('name')->get();
        $branchZones = $this->resolveBranchZones($branches);
        $originBranch = Branch::find($this->hubBranchId());
        $originCityId = $originBranch ? $this->resolveBranchCityLocation($originBranch)?->id : null;
        $bankAccounts = auth()->user()->branch ? auth()->user()->branch->bankAccounts : [];
        $provinces = Location::query()
            ->where('type', 'provinsi')
            ->selectRaw('MIN(id) as id, name, zone')
            ->groupBy('name', 'zone')
            ->orderBy('name')
            ->get();

        return view('be.shipments.create', compact('branches', 'couriers', 'branchZones', 'originCityId', 'bankAccounts', 'provinces'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'cashier') {
            abort(403, 'Akses terbatas hanya untuk Kasir.');
        }
        if (! $this->hubBranchId()) {
            abort(403, 'Akun kasir belum terhubung ke cabang.');
        }

        $request->validate([
            'sender_name' => 'required|string',
            'sender_phone' => 'required|string',
            'receiver_name' => 'required|string',
            'receiver_phone' => 'required|string',
            'receiver_province_id' => 'required|exists:locations,id',
            'receiver_city_id' => 'required|exists:locations,id',
            'receiver_address' => 'required|string',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'weight' => 'required|numeric|min:0.1',
            'item_name' => 'required|string',
            'payment_method' => 'required|in:cash,transfer,e-wallet',
            'amount_received' => 'nullable|numeric|min:0',
            'bank_id' => 'nullable|exists:bank_accounts,id',
            'proof_file' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'courier_id' => 'nullable|exists:users,id',
            'service_type' => 'required|in:BEST,REGULAR,KARGO',
        ]);

        if ($request->service_type === 'KARGO' && $request->weight < 10) {
            return back()->withErrors(['weight' => 'Layanan KARGO membutuhkan berat minimal 10 KG.'])->withInput();
        }

        $receiverProvince = Location::query()
            ->where('id', $request->receiver_province_id)
            ->where('type', 'provinsi')
            ->first();
        $receiverCity = Location::query()
            ->where('id', $request->receiver_city_id)
            ->where('type', 'kota')
            ->first();

        if (! $receiverProvince || ! $receiverCity || (int) $receiverCity->parent_id !== (int) $receiverProvince->id) {
            return back()
                ->withErrors(['receiver_city_id' => 'Kota/Kabupaten tujuan wajib dipilih dari daftar lokasi.'])
                ->withInput();
        }

        // Pastikan origin branch = hub user (manager & kasir tidak boleh input atas nama hub lain)
        if (($bid = $this->hubBranchId()) !== null && (int) $request->origin_branch_id !== $bid) {
            abort(403);
        }

        if ($request->filled('courier_id')) {
            $courier = User::query()
                ->with('vehicle')
                ->where('id', $request->courier_id)
                ->where('role', 'courier')
                ->where('branch_id', $request->origin_branch_id)
                ->first();

            if (! $courier) {
                return back()
                    ->withErrors(['courier_id' => 'Kurir harus berasal dari hub asal dan memiliki role courier.'])
                    ->withInput();
            }

            $vehicleError = $this->vehicleAssignmentError(
                $courier,
                (float) $request->weight,
                1,
                (int) $request->origin_branch_id !== (int) $request->destination_branch_id
            );

            if ($vehicleError) {
                return back()
                    ->withErrors(['courier_id' => $vehicleError])
                    ->withInput();
            }
        }

        // Determine rate before opening the transaction so validation failures do not leave it dangling.
        $originBranch = Branch::findOrFail($request->origin_branch_id);
        $destinationBranch = Branch::findOrFail($request->destination_branch_id);
        $originCity = $this->resolveBranchCityLocation($originBranch);

        if (! $originCity) {
            return back()
                ->withErrors(['origin_branch_id' => 'Kota asal hub belum bisa dipetakan ke data lokasi.'])
                ->withInput();
        }

        $rate = $this->resolveBranchRate($originBranch, $destinationBranch)
            ?? $this->resolveLocationRate($originCity, $receiverCity);

        $shippingEstimate = app(ShippingCostService::class)->estimateFromCities(
            $originCity,
            $receiverCity,
            (float) $request->weight,
            (string) $request->service_type
        );

        if (! $shippingEstimate) {
            return back()
                ->withErrors(['receiver_city_id' => 'Ongkir untuk tujuan ini belum tersedia dari RajaOngkir maupun fallback lokal.'])
                ->withInput();
        }

        // Apply small pricing adjustment when shipment will traverse multiple legs.
        // Add 10% per extra leg (beyond the first leg) to account for multi-hop handling.
        try {
            $routeBranches = app(ShipmentRoutePlanner::class)->branchesFor($originBranch, $destinationBranch);
            $extraLegs = max(0, $routeBranches->count() - 2);
            if ($extraLegs > 0 && isset($shippingEstimate['total_price'])) {
                $multiplier = 1 + (0.10 * $extraLegs);
                $shippingEstimate['total_price'] = (float) round(((float) $shippingEstimate['total_price']) * $multiplier, 2);
                $shippingEstimate['total_price_fmt'] = 'Rp '.number_format($shippingEstimate['total_price'], 0, ',', '.');
                $shippingEstimate['price_per_kg'] = (float) $shippingEstimate['total_price'] / max(0.1, (float) $request->weight);
                $shippingEstimate['legs_count'] = max(1, $routeBranches->count() - 1);
                $shippingEstimate['pricing_multiplier'] = $multiplier;
            }
        } catch (\Throwable) {
            // Non-fatal: if planner fails, fall back to original estimate.
        }

        $totalPrice = (float) $shippingEstimate['total_price'];
        $amountReceived = $request->amount_received ?: 0;

        if ($request->payment_method === 'cash' && $amountReceived < $totalPrice) {
            return back()
                ->withErrors(['amount_received' => 'Nominal tunai diterima belum mencukupi total tagihan.'])
                ->withInput();
        }

        if ($request->payment_method === 'transfer') {
            if (! $request->filled('bank_id')) {
                return back()
                    ->withErrors(['bank_id' => 'Rekening tujuan wajib dipilih untuk pembayaran transfer.'])
                    ->withInput();
            }

            if (! $request->hasFile('proof_file')) {
                return back()
                    ->withErrors(['proof_file' => 'Bukti transfer wajib diupload sebelum shipment dibuat.'])
                    ->withInput();
            }

            $bankBelongsToOrigin = BankAccount::where('id', $request->bank_id)
                ->where('branch_id', $request->origin_branch_id)
                ->exists();

            if (! $bankBelongsToOrigin) {
                return back()
                    ->withErrors(['bank_id' => 'Rekening transfer harus milik hub asal.'])
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // 1. Find or Create Customers
            $sender = Customer::firstOrCreate(
                ['phone' => $request->sender_phone],
                [
                    'name' => $request->sender_name,
                    'city' => 'Unknown',
                    'email' => $request->sender_phone.'@sprintlog.local',
                    'password' => bcrypt(Str::random(12)),
                    'address' => '',
                ]
            );
            // Update sender name if it changed
            if ($sender->name !== $request->sender_name) {
                $sender->update(['name' => $request->sender_name]);
            }

            $receiver = Customer::firstOrCreate(
                ['phone' => $request->receiver_phone],
                [
                    'name' => $request->receiver_name,
                    'city' => $receiverCity->name,
                    'address' => $request->receiver_address,
                    'email' => $request->receiver_phone.'@sprintlog.local',
                    'password' => bcrypt(Str::random(12)),
                ]
            );
            // Update receiver name if it changed
            if ($receiver->name !== $request->receiver_name) {
                $receiver->update(['name' => $request->receiver_name]);
            }

            $receiverUpdates = [];
            if ($request->receiver_address && $receiver->address !== $request->receiver_address) {
                $receiverUpdates['address'] = $request->receiver_address;
            }
            if ($receiverCity->name && in_array($receiver->city, ['', 'Unknown', null], true)) {
                $receiverUpdates['city'] = $receiverCity->name;
            }
            if ($receiverUpdates !== []) {
                $receiver->update($receiverUpdates);
            }

            // 3. Create Shipment
            $shipment = Shipment::create([
                'tracking_number' => $this->generateTrackingNumber($request->service_type),
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'origin_branch_id' => $request->origin_branch_id,
                'destination_branch_id' => $request->destination_branch_id,
                'courier_id' => $request->courier_id,
                'rate_id' => $shippingEstimate['rate_id'] ?? $rate?->id,
                'total_weight' => $request->weight,
                'total_price' => $totalPrice,
                ...$this->shippingSnapshotAttributes($shippingEstimate),
                'status' => 'pending',
                'shipment_date' => now(),
            ]);

            // Save Payment
            $changeAmount = $request->payment_method === 'cash' ? max(0, $amountReceived - $totalPrice) : 0;

            $proofFilePath = null;
            if ($request->payment_method === 'transfer' && $request->hasFile('proof_file')) {
                $file = $request->file('proof_file');
                $filename = 'proof_'.$shipment->id.'_'.time().'.'.$file->getClientOriginalExtension();
                $proofFilePath = $file->storeAs('proofs', $filename, 'public');
            }

            $bankAccount = null;
            if ($request->payment_method === 'transfer' && $request->bank_id) {
                $bankAccount = BankAccount::find($request->bank_id);
            }

            // Transfer proof still needs hub verification before becoming paid.
            $paymentStatus = 'pending';
            $paymentDate = null;
            if ($request->payment_method === 'cash') {
                $paymentStatus = 'paid';
                $paymentDate = now();
            } elseif ($request->payment_method === 'transfer' && $proofFilePath) {
                $paymentStatus = 'pending_verification';
            }

            Payment::create([
                'shipment_id' => $shipment->id,
                'amount' => $totalPrice,
                'payment_method' => $request->payment_method,
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentDate,
                'amount_received' => $request->payment_method === 'cash' ? $amountReceived : null,
                'change_amount' => $request->payment_method === 'cash' ? $changeAmount : null,
                'bank_name' => $bankAccount ? $bankAccount->bank_name : null,
                'account_number' => $bankAccount ? $bankAccount->account_number : null,
                'proof_file' => $proofFilePath,
            ]);

            // 4. Create Item
            ShipmentItem::create([
                'shipment_id' => $shipment->id,
                'item_name' => $request->item_name,
                'quantity' => 1,
                'weight' => $request->weight,
            ]);

            // 5. Initial Tracking Log
            $shipment->trackings()->create([
                'location' => $originBranch->name,
                'description' => 'Paket telah didaftarkan di sistem.',
                'status' => 'pending',
                'tracked_at' => now(),
            ]);

            $shipment->statusAudits()->create([
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => 'pending',
                'location' => $originBranch->name,
                'description' => 'Paket telah didaftarkan di sistem.',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]);

            app(ShipmentRoutePlanner::class)->createLegsFor($shipment);

            DB::commit();

            return redirect()->route('be.shipments.show', $shipment)->with('success', 'Shipment berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    protected function generateTrackingNumber(string $serviceType): string
    {
        do {
            $trackingNumber = 'SPRINT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)).'_'.$serviceType;
        } while (Shipment::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    protected function resolveBranchRate(Branch $originBranch, Branch $destinationBranch): ?Rate
    {
        $originZone = $this->resolveBranchZone($originBranch);
        $destinationZone = $this->resolveBranchZone($destinationBranch);

        if (! $originZone || ! $destinationZone) {
            return null;
        }

        return Rate::where('origin_zone', $originZone)
            ->where('destination_zone', $destinationZone)
            ->first();
    }

    protected function resolveLocationRate(Location $originCity, Location $destinationCity): ?Rate
    {
        if (! $originCity->zone || ! $destinationCity->zone) {
            return null;
        }

        return Rate::where('origin_zone', $originCity->zone)
            ->where('destination_zone', $destinationCity->zone)
            ->first();
    }

    protected function resolveBranchZones(iterable $branches): array
    {
        $zonesByCity = Location::query()
            ->whereIn('type', ['provinsi', 'kota'])
            ->get(['name', 'zone'])
            ->reduce(function (array $carry, Location $location) {
                $name = $this->normalizeCityName((string) $location->name);

                if ($name !== '' && ! array_key_exists($name, $carry)) {
                    $carry[$name] = $location->zone;
                }

                return $carry;
            }, []);

        $zones = [];
        foreach ($branches as $branch) {
            $zones[$branch->id] = $zonesByCity[$this->normalizeCityName((string) $branch->city)] ?? null;
        }

        return $zones;
    }

    protected function resolveBranchZone(Branch $branch): ?int
    {
        $city = trim((string) $branch->city);
        if ($city === '') {
            return null;
        }

        $normalizedCity = preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $city);

        return Location::query()
            ->whereIn('type', ['provinsi', 'kota'])
            ->where(function ($query) use ($city, $normalizedCity) {
                $query->where('name', $city)
                    ->orWhere('name', 'like', '%'.$normalizedCity.'%');
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$city])
            ->value('zone');
    }

    protected function resolveBranchCityLocation(Branch $branch): ?Location
    {
        $city = trim((string) $branch->city);
        if ($city === '') {
            return null;
        }

        $normalizedCity = preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $city);

        $cityLocation = Location::query()
            ->where('type', 'kota')
            ->where(function ($query) use ($city, $normalizedCity) {
                $query->where('name', $city)
                    ->orWhere('name', 'like', '%'.$normalizedCity.'%');
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$city])
            ->first();

        if ($cityLocation) {
            return $cityLocation;
        }

        $province = Location::query()
            ->where('type', 'provinsi')
            ->where(function ($query) use ($city, $normalizedCity) {
                $query->where('name', $city)
                    ->orWhere('name', 'like', '%'.$normalizedCity.'%');
            })
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$city])
            ->first();

        return $province
            ? Location::query()
                ->where('type', 'kota')
                ->where('parent_id', $province->id)
                ->orderByRaw("CASE WHEN name LIKE 'Kota %' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->first()
            : null;
    }

    protected function normalizeCityName(string $city): string
    {
        return strtolower(trim((string) preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $city)));
    }

    protected function shippingSnapshotAttributes(array $estimate): array
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

    public function show(Shipment $shipment)
    {
        $this->assertCanAccessShipment($shipment);

        $shipment->load([
            'sender',
            'receiver',
            'originBranch',
            'destinationBranch',
            'items',
            'trackings',
            'courier.vehicle',
            'payment',
            'legs.originBranch',
            'legs.destinationBranch',
            'legs.handler',
            'legs.vehicle',
            'exceptions.leg.originBranch',
            'exceptions.leg.destinationBranch',
            'exceptions.reporter',
            'notifications',
        ]);

        $deliveryCouriers = collect();
        if (in_array(auth()->user()->role, ['manager', 'cashier'], true) && auth()->user()->branch_id) {
            $deliveryCouriers = User::query()
                ->with('vehicle')
                ->select(['id', 'name', 'email', 'branch_id'])
                ->where('role', 'courier')
                ->where('branch_id', auth()->user()->branch_id)
                ->orderBy('name')
                ->get();
        }

        $linkedPickup = PickupRequest::query()
            ->with('branch')
            ->where('shipment_id', $shipment->id)
            ->first();
        $useCourierCurrentLocation = auth()->user()->role === 'courier';
        $distanceService = app(RouteDistanceService::class);
        $linkedPickupHubPoint = $linkedPickup ? $distanceService->pointForBranch($linkedPickup->branch) : null;
        $originHubPoint = $distanceService->pointForBranch($shipment->originBranch);
        $destinationHubPoint = $distanceService->pointForBranch($shipment->destinationBranch);

        $routeEstimate = $linkedPickup
            ? RouteEstimate::make([
                [
                    'label' => 'Hub awal',
                    'address' => $linkedPickupHubPoint['address'] ?? ($linkedPickup->branch?->name.' - '.$linkedPickup->branch?->city),
                    'lat' => $linkedPickupHubPoint['lat'] ?? null,
                    'lng' => $linkedPickupHubPoint['lng'] ?? null,
                ],
                [
                    'label' => 'Titik pickup',
                    'address' => $linkedPickup->sender_address ?: $linkedPickup->pickup_address,
                    'lat' => $linkedPickup->sender_latitude ?: $linkedPickup->latitude,
                    'lng' => $linkedPickup->sender_longitude ?: $linkedPickup->longitude,
                ],
                [
                    'label' => 'Alamat penerima',
                    'address' => $linkedPickup->receiver_address,
                    'lat' => $linkedPickup->receiver_latitude,
                    'lng' => $linkedPickup->receiver_longitude,
                ],
            ], [
                'label' => 'Courier delivery route',
                'speed_kmh' => 28,
                'use_current_location_origin' => $useCourierCurrentLocation,
                'note' => $useCourierCurrentLocation
                    ? 'Google Maps memakai posisi kurir saat ini sebagai titik awal. Jarak estimasi di kartu ini tetap perkiraan sistem dari titik pickup/hub ke alamat penerima.'
                    : 'Estimasi rute berdasarkan titik pickup dan alamat penerima dari request pelanggan.',
            ])
            : RouteEstimate::make([
                [
                    'label' => 'Hub asal',
                    'address' => $originHubPoint['address'] ?? ($shipment->originBranch?->name.' - '.$shipment->originBranch?->city),
                    'lat' => $originHubPoint['lat'] ?? null,
                    'lng' => $originHubPoint['lng'] ?? null,
                ],
                [
                    'label' => 'Hub tujuan',
                    'address' => $destinationHubPoint['address'] ?? ($shipment->destinationBranch?->name.' - '.$shipment->destinationBranch?->city),
                    'lat' => $destinationHubPoint['lat'] ?? null,
                    'lng' => $destinationHubPoint['lng'] ?? null,
                ],
            ], [
                'label' => 'Hub to hub route',
                'speed_kmh' => 42,
                'use_current_location_origin' => $useCourierCurrentLocation,
                'note' => $useCourierCurrentLocation
                    ? 'Google Maps memakai posisi kurir saat ini sebagai titik awal. Jarak estimasi di kartu ini tetap perkiraan sistem antar hub.'
                    : 'Estimasi rute antar hub. Lengkapi koordinat pickup/penerima untuk navigasi kurir yang lebih detail.',
            ]);

        return view('be.shipments.show', compact('shipment', 'deliveryCouriers', 'routeEstimate', 'linkedPickup'));
    }

    public function updateStatus(Request $request, Shipment $shipment)
    {
        $this->assertCanAccessShipment($shipment);

        if (auth()->user()->role !== 'courier') {
            abort(403, 'Hanya kurir yang ditugaskan yang dapat mengubah status shipment.');
        }

        $request->validate([
            'status' => 'required|in:'.implode(',', self::SHIPMENT_STATUSES),
            'location' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'photo' => 'nullable|image|max:2048',
        ]);

        $previousStatus = $shipment->status;
        $nextStatus = $request->status;

        // Arrival at a branch must be recorded by hub staff via hub scan.
        // Disallow couriers from directly setting 'arrived_at_branch' here so
        // the hub can register receipt and assign the next courier.
        if ($nextStatus === 'arrived_at_branch') {
            // Allow couriers to record arrival only when they are the handler
            // (or the assigned shipment courier) for the active leg. Otherwise
            // require hub staff to perform the Hub Scan.
            $leg = $shipment->legs()
                ->whereIn('status', ['departed', 'pending'])
                ->orderByRaw("CASE WHEN status = 'departed' THEN 0 ELSE 1 END")
                ->orderBy('sequence')
                ->first();

            if (! $leg) {
                return back()->withErrors(['status' => 'Tidak ada leg inbound yang bisa diterima. Hub staff harus melakukan Hub Scan.']);
            }

            $userId = auth()->id();
            if (((int) $leg->handler_id !== (int) $userId) && ((int) $shipment->courier_id !== (int) $userId)) {
                return back()->withErrors(['status' => 'Hanya kurir yang membawa paket (atau Hub staff melalui Hub Scan) yang bisa menandai kedatangan di hub.']);
            }
        }

        if ($previousStatus === $nextStatus) {
            return back()->withErrors(['status' => 'Status baru harus berbeda dari status saat ini.']);
        }

        if (! in_array($nextStatus, self::COURIER_TRANSITIONS[$previousStatus] ?? [], true)) {
            return back()->withErrors(['status' => "Transisi status {$previousStatus} -> {$nextStatus} tidak valid."]);
        }

        if ($shipment->payment && $shipment->payment->payment_status !== 'paid') {
            return back()->withErrors(['status' => 'Shipment belum bisa berjalan sebelum pembayaran diverifikasi lunas.']);
        }

        if ($nextStatus === 'in_transit' && (int) $shipment->origin_branch_id !== (int) $shipment->destination_branch_id) {
            $courier = auth()->user()->load('vehicle');
            $vehicleError = $this->vehicleAssignmentError($courier, (float) $shipment->total_weight, 1, true);

            if ($vehicleError) {
                return back()->withErrors(['status' => $vehicleError]);
            }
        }

        if ($nextStatus === 'delivered' && ! $request->hasFile('photo')) {
            return back()->withErrors(['photo' => 'Foto bukti serah terima wajib diupload saat status delivered.']);
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('shipments', 'public');
            $shipment->photo = $path;
        }

        $shipment->status = $nextStatus;
        $shipment->save();

        app(ShipmentRoutePlanner::class)->applyShipmentStatus($shipment, $nextStatus);

        // If courier marked arrival and this leg is the final destination hub,
        // record the recipient's name into `received_by` for clarity at hub.
        if ($nextStatus === 'arrived_at_branch') {
            $activeLeg = $shipment->legs()
                ->whereIn('status', ['arrived', 'departed', 'pending'])
                ->orderByRaw("CASE WHEN status = 'departed' THEN 0 ELSE 1 END")
                ->orderBy('sequence')
                ->first();

            if ($activeLeg && (int) $activeLeg->destination_branch_id === (int) $shipment->destination_branch_id) {
                $shipment->loadMissing('receiver');
                $shipment->update(['received_by' => $shipment->receiver?->name]);
            }
        }

        $shipment->trackings()->create([
            'location' => $request->location,
            'description' => $request->description,
            'status' => $nextStatus,
            'tracked_at' => now(),
        ]);

        ShipmentStatusAudit::create([
            'shipment_id' => $shipment->id,
            'user_id' => auth()->id(),
            'from_status' => $previousStatus,
            'to_status' => $nextStatus,
            'location' => $request->location,
            'description' => $request->description,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        app(ShipmentNotifier::class)->record(
            $shipment,
            'status_updated',
            'Status shipment berubah',
            'Status paket berubah menjadi '.strtoupper($nextStatus).'.'
        );

        // Notify customer when package is delivered to recipient at destination.
        if ($nextStatus === 'delivered') {
            $shipment->loadMissing('receiver');
            $receivedBy = $shipment->receiver?->name ?: null;

            // Prefer explicit receiver name; fallback to description provided by courier.
            if (! $receivedBy && ! empty($request->description)) {
                // Try to extract a name from description, otherwise use the raw text.
                $receivedBy = trim($request->description);
            }

            if ($receivedBy) {
                // Persist received_by if not set or different.
                if ($shipment->received_by !== $receivedBy) {
                    $shipment->update(['received_by' => $receivedBy]);
                }
            }

            app(ShipmentNotifier::class)->record(
                $shipment,
                'delivered',
                'Paket telah diterima',
                'Paket Anda telah diterima oleh '.($receivedBy ?? 'penerima').'.'
            );
        }

        return back()->with('success', 'Status updated successfully.');
    }

    public function hubScan(Request $request, Shipment $shipment)
    {
        $this->assertCanAccessShipment($shipment);

        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Hanya staff hub yang bisa melakukan scan hub.');
        }

        $branchId = $this->hubBranchId();
        if (! $branchId) {
            abort(403, 'Akun staff belum terhubung ke hub.');
        }

        $request->validate([
            'scan_type' => 'required|in:depart,receive',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($request->scan_type === 'depart' && ! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Departure hub hanya bisa dilakukan Manager Hub atau Kasir Hub.');
        }

        if ($shipment->payment && $shipment->payment->payment_status !== 'paid') {
            return back()->withErrors(['scan_type' => 'Shipment belum bisa bergerak sebelum pembayaran lunas.']);
        }

        $shipment->loadMissing(['legs.originBranch', 'legs.destinationBranch']);

        return $request->scan_type === 'depart'
            ? $this->scanDepartHub($request, $shipment, $branchId)
            : $this->scanReceiveHub($request, $shipment, $branchId);
    }

    private function scanDepartHub(Request $request, Shipment $shipment, int $branchId)
    {
        $allLegs = $shipment->legs()
            ->get(['id', 'sequence', 'status']);

        $leg = $shipment->legs()
            ->with(['originBranch', 'destinationBranch'])
            ->where('origin_branch_id', $branchId)
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->get()
            ->first(function ($candidate) use ($allLegs) {
                $previousLeg = $allLegs->firstWhere('sequence', $candidate->sequence - 1);

                return ! $previousLeg || $previousLeg->status === 'arrived';
            });

        if (! $leg) {
            return back()->withErrors(['scan_type' => 'Tidak ada leg yang siap diberangkatkan dari hub ini.']);
        }

        $courier = $shipment->courier()->with('vehicle')->first();
        if (! $courier) {
            return back()->withErrors(['scan_type' => 'Assign kurir truk dulu sebelum outbound antar hub diberangkatkan.']);
        }

        $vehicleError = $this->vehicleAssignmentError($courier, (float) $shipment->total_weight, 1, true);
        if ($vehicleError) {
            return back()->withErrors(['scan_type' => $vehicleError]);
        }

        $vehicle = $courier->vehicle;
        $previousStatus = $shipment->status;
        $nextStatus = 'in_transit';
        $now = now();

        // Enforce: hub departure should not be performed by hub staff when the
        // shipment is already marked as 'picked_up' by a courier. This keeps
        // responsibility with the courier who picked the package.
        if ($shipment->status === 'picked_up') {
            return back()->withErrors(['scan_type' => 'Hub departure tidak bisa dilakukan oleh staff ketika paket sudah di-pickup oleh kurir.']);
        }

        DB::transaction(function () use ($leg, $nextStatus, $now, $previousStatus, $request, $shipment, $courier, $vehicle): void {
            $leg->update([
                'status' => 'departed',
                'handler_id' => $courier->id,
                'vehicle_id' => $vehicle->id,
                'departed_at' => $now,
                'notes' => $request->note,
            ]);

            $shipment->update(['status' => $nextStatus]);

            $description = $request->note ?: 'Paket diberangkatkan dari '.$leg->originBranch->name.' menuju '.$leg->destinationBranch->name.'.';

            $shipment->trackings()->create([
                'location' => $leg->originBranch->name,
                'description' => $description,
                'status' => $nextStatus,
                'tracked_at' => $now,
            ]);

            ShipmentStatusAudit::create([
                'shipment_id' => $shipment->id,
                'user_id' => auth()->id(),
                'from_status' => $previousStatus,
                'to_status' => $nextStatus,
                'location' => $leg->originBranch->name,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]);

            app(ShipmentNotifier::class)->record(
                $shipment,
                'hub_departed',
                'Paket berangkat dari hub',
                $description
            );
        });

        return back()->with('success', 'Hub departure scan recorded.');
    }

    private function scanReceiveHub(Request $request, Shipment $shipment, int $branchId)
    {
        $leg = $shipment->legs()
            ->with(['originBranch', 'destinationBranch'])
            ->where('destination_branch_id', $branchId)
            ->where('status', 'departed')
            ->orderBy('sequence')
            ->first();

        if (! $leg) {
            return back()->withErrors(['scan_type' => 'Tidak ada leg inbound yang bisa diterima di hub ini.']);
        }

        $previousStatus = $shipment->status;
        $nextStatus = 'arrived_at_branch';
        $now = now();

        DB::transaction(function () use ($leg, $nextStatus, $now, $previousStatus, $request, $shipment): void {
            $leg->update([
                'status' => 'arrived',
                // Preserve existing handler (courier) if present so the origin
                // courier remains recorded as the handler for this leg.
                'handler_id' => $leg->handler_id ?: auth()->id(),
                'departed_at' => $leg->departed_at ?: $now,
                'arrived_at' => $now,
                'notes' => $request->note,
            ]);

            // If this arrival is at the shipment's final destination hub,
            // record the intended recipient's name into `received_by` so
            // downstream staff see who the package is for (e.g., "Syamil").
            $receivedBy = null;
            if ((int) $leg->destination_branch_id === (int) $shipment->destination_branch_id) {
                $shipment->loadMissing('receiver');
                $receivedBy = $shipment->receiver?->name;
            }

            $updatePayload = ['status' => $nextStatus];
            if ($receivedBy !== null) {
                $updatePayload['received_by'] = $receivedBy;
            }

            $shipment->update($updatePayload);

            $description = $request->note ?: 'Paket diterima di '.$leg->destinationBranch->name.' dari '.$leg->originBranch->name.'.';

            $shipment->trackings()->create([
                'location' => $leg->destinationBranch->name,
                'description' => $description,
                'status' => $nextStatus,
                'tracked_at' => $now,
            ]);

            ShipmentStatusAudit::create([
                'shipment_id' => $shipment->id,
                'user_id' => auth()->id(),
                'from_status' => $previousStatus,
                'to_status' => $nextStatus,
                'location' => $leg->destinationBranch->name,
                'description' => $description,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]);

            app(ShipmentNotifier::class)->record(
                $shipment,
                'hub_received',
                'Paket diterima hub',
                $description
            );
        });

        return back()->with('success', 'Hub receive scan recorded.');
    }

    public function assignDeliveryCourier(Request $request, Shipment $shipment)
    {
        $this->assertCanAccessShipment($shipment);

        if (! in_array(auth()->user()->role, ['manager', 'cashier'], true)) {
            abort(403, 'Delivery assignment hanya bisa dilakukan Manager Hub atau Kasir Hub.');
        }

        $branchId = $this->hubBranchId();
        if (! $branchId) {
            abort(403, 'Akun staff belum terhubung ke hub.');
        }

        $canAssignOutbound = (int) $shipment->origin_branch_id === $branchId
            && in_array($shipment->status, ['pending', 'in_transit'], true)
            && $shipment->legs()
                ->where('origin_branch_id', $branchId)
                ->whereIn('status', ['pending', 'departed'])
                ->exists();

        $canAssignLastMile = (int) $shipment->destination_branch_id === $branchId
            && $shipment->status === 'arrived_at_branch'
            && $shipment->legs()
            ->where('destination_branch_id', $branchId)
            ->where('status', 'arrived')
            ->exists();

        if (! $canAssignOutbound && ! $canAssignLastMile) {
            return back()->withErrors(['courier_id' => 'Shipment belum berada di state yang bisa di-assign dari hub ini.']);
        }

        $request->validate([
            'courier_id' => 'required|exists:users,id',
        ]);

        $courier = User::query()
            ->with('vehicle')
            ->where('id', $request->courier_id)
            ->where('role', 'courier')
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $vehicleError = $this->vehicleAssignmentError(
            $courier,
            (float) $shipment->total_weight,
            1,
            $canAssignOutbound
        );

        if ($vehicleError) {
            return back()->withErrors(['courier_id' => $vehicleError]);
        }

        $previousCourierId = $shipment->courier_id;
        DB::transaction(function () use ($shipment, $courier, $canAssignOutbound, $branchId): void {
            $shipment->update(['courier_id' => $courier->id]);

            if ($canAssignOutbound) {
                $leg = $shipment->legs()
                    ->where('origin_branch_id', $branchId)
                    ->whereIn('status', ['pending', 'departed'])
                    ->orderBy('sequence')
                    ->first();

                $leg?->update([
                    'handler_id' => $courier->id,
                    'vehicle_id' => $courier->vehicle->id,
                ]);
            }
        });

        ShipmentStatusAudit::create([
            'shipment_id' => $shipment->id,
            'user_id' => auth()->id(),
            'from_status' => $shipment->status,
            'to_status' => $shipment->status,
            'location' => optional(auth()->user()->branch)->name,
            'description' => 'Delivery courier assigned: '.$courier->name.'. Previous courier ID: '.($previousCourierId ?: 'none').'.',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
        ]);

        app(ShipmentNotifier::class)->record(
            $shipment,
            'delivery_assigned',
            $canAssignOutbound ? 'Kurir outbound ditugaskan' : 'Kurir delivery ditugaskan',
            'Paket ditugaskan ke kurir '.$courier->name.'.'
        );

        return back()->with('success', $canAssignOutbound ? 'Outbound courier assigned.' : 'Delivery courier assigned.');
    }

    public function manifestDispatch(Request $request)
    {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'Manifest dispatch hanya bisa dilakukan Manager Hub.');
        }

        $branchId = $this->hubBranchId();
        if (! $branchId) {
            abort(403, 'Akun staff belum terhubung ke hub.');
        }

        $request->validate([
            'shipment_ids' => 'required|array|min:1',
            'shipment_ids.*' => 'integer|exists:shipments,id',
            'courier_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $courier = User::query()
            ->with('vehicle')
            ->where('id', $request->courier_id)
            ->where('role', 'courier')
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $shipments = Shipment::query()
            ->with(['payment', 'legs.originBranch', 'legs.destinationBranch'])
            ->whereIn('id', $request->shipment_ids)
            ->get();

        if ($shipments->count() !== count(array_unique($request->shipment_ids))) {
            return back()->withErrors(['shipment_ids' => 'Ada shipment yang tidak ditemukan.']);
        }

        foreach ($shipments as $shipment) {
            $this->assertCanAccessShipment($shipment);

            if ($shipment->payment && $shipment->payment->payment_status !== 'paid') {
                return back()->withErrors(['shipment_ids' => 'Shipment '.$shipment->tracking_number.' belum lunas.']);
            }
        }

        $legs = $shipments->mapWithKeys(function (Shipment $shipment) use ($branchId) {
            $leg = $shipment->legs
                ->where('origin_branch_id', $branchId)
                ->where('status', 'pending')
                ->sortBy('sequence')
                ->first();

            return [$shipment->id => $leg];
        });

        if ($legs->contains(null)) {
            return back()->withErrors(['shipment_ids' => 'Semua shipment terpilih harus punya leg pending dari hub ini.']);
        }

        $destinationIds = $legs->map(fn ($leg) => (int) $leg->destination_branch_id)->unique()->values();
        if ($destinationIds->count() !== 1) {
            return back()->withErrors(['shipment_ids' => 'Batch manifest harus menuju hub berikutnya yang sama.']);
        }

        $totalWeight = (float) $shipments->sum(fn (Shipment $shipment) => (float) $shipment->total_weight);
        $packageCount = $shipments->count();
        $vehicleError = $this->vehicleAssignmentError($courier, $totalWeight, $packageCount, true);

        if ($vehicleError) {
            return back()->withErrors(['courier_id' => $vehicleError]);
        }

        $vehicle = $courier->vehicle;
        $now = now();

        $manifest = DB::transaction(function () use ($request, $shipments, $legs, $branchId, $destinationIds, $now, $courier, $vehicle, $packageCount, $totalWeight) {
            $manifest = ShipmentManifest::create([
                'manifest_number' => $this->generateManifestNumber(),
                'origin_branch_id' => $branchId,
                'destination_branch_id' => $destinationIds->first(),
                'created_by' => auth()->id(),
                'courier_id' => $courier->id,
                'vehicle_id' => $vehicle->id,
                'package_count' => $packageCount,
                'total_weight' => $totalWeight,
                'status' => 'dispatched',
                'departed_at' => $now,
                'notes' => $request->notes,
            ]);

            foreach ($shipments as $shipment) {
                $leg = $legs[$shipment->id];
                $previousStatus = $shipment->status;
                $description = $request->notes ?: 'Paket diberangkatkan via manifest '.$manifest->manifest_number.' dari '.$leg->originBranch->name.' menuju '.$leg->destinationBranch->name.'.';

                ShipmentManifestItem::create([
                    'shipment_manifest_id' => $manifest->id,
                    'shipment_id' => $shipment->id,
                    'shipment_leg_id' => $leg->id,
                    'status' => 'loaded',
                ]);

                $leg->update([
                    'status' => 'departed',
                    'handler_id' => $courier->id,
                    'vehicle_id' => $vehicle->id,
                    'departed_at' => $now,
                    'notes' => $request->notes,
                ]);

                $shipment->update(['status' => 'in_transit']);

                $shipment->trackings()->create([
                    'location' => $leg->originBranch->name,
                    'description' => $description,
                    'status' => 'in_transit',
                    'tracked_at' => $now,
                ]);

                ShipmentStatusAudit::create([
                    'shipment_id' => $shipment->id,
                    'user_id' => auth()->id(),
                    'from_status' => $previousStatus,
                    'to_status' => 'in_transit',
                    'location' => $leg->originBranch->name,
                    'description' => $description,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 512),
                ]);

                app(ShipmentNotifier::class)->record(
                    $shipment,
                    'manifest_dispatched',
                    'Paket masuk manifest',
                    $description
                );
            }

            return $manifest;
        });

        return back()
            ->with('success', 'Manifest dispatch recorded: '.$manifest->manifest_number.'.')
            ->with('manifest_print_url', route('be.shipments.manifest-print', $manifest));
    }

    public function recordException(Request $request, Shipment $shipment)
    {
        $this->assertCanAccessShipment($shipment);

        if (! in_array(auth()->user()->role, ['manager', 'cashier', 'courier'], true)) {
            abort(403, 'Role ini tidak bisa mencatat exception shipment.');
        }

        $request->validate([
            'type' => 'required|in:damaged,lost,hold,address_issue,delay,other',
            'severity' => 'required|in:low,medium,high,critical',
            'location' => 'nullable|string|max:255',
            'shipment_leg_id' => 'nullable|exists:shipment_legs,id',
            'description' => 'required|string|max:2000',
        ]);

        if ($request->filled('shipment_leg_id')) {
            $legBelongsToShipment = $shipment->legs()
                ->where('id', $request->shipment_leg_id)
                ->exists();

            if (! $legBelongsToShipment) {
                return back()->withErrors(['shipment_leg_id' => 'Leg tidak cocok dengan shipment ini.']);
            }
        }

        $statusByType = [
            'damaged' => 'damaged',
            'lost' => 'lost',
            'hold' => 'held',
            'address_issue' => 'delivery_failed',
            'delay' => 'exception',
            'other' => 'exception',
        ];

        $previousStatus = $shipment->status;
        $nextStatus = $statusByType[$request->type];
        $location = $request->location ?: optional(auth()->user()->branch)->name ?: 'Field operation';

        DB::transaction(function () use ($request, $shipment, $previousStatus, $nextStatus, $location): void {
            ShipmentException::create([
                'shipment_id' => $shipment->id,
                'shipment_leg_id' => $request->shipment_leg_id,
                'reported_by' => auth()->id(),
                'type' => $request->type,
                'severity' => $request->severity,
                'status' => 'open',
                'location' => $location,
                'description' => $request->description,
            ]);

            if ($request->type === 'delay' && $request->filled('shipment_leg_id')) {
                $shipment->legs()
                    ->where('id', $request->shipment_leg_id)
                    ->update([
                        'delay_reason' => $request->description,
                        'updated_at' => now(),
                    ]);
            }

            $shipment->update(['status' => $nextStatus]);

            $shipment->trackings()->create([
                'location' => $location,
                'description' => $request->description,
                'status' => $nextStatus,
                'tracked_at' => now(),
            ]);

            ShipmentStatusAudit::create([
                'shipment_id' => $shipment->id,
                'user_id' => auth()->id(),
                'from_status' => $previousStatus,
                'to_status' => $nextStatus,
                'location' => $location,
                'description' => 'Exception '.$request->type.': '.$request->description,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512),
            ]);

            app(ShipmentNotifier::class)->record(
                $shipment,
                'exception_reported',
                'Exception shipment tercatat',
                $request->description
            );
        });

        return back()->with('success', 'Exception shipment recorded.');
    }

    public function printReceipt(Shipment $shipment)
    {
        $this->assertCanAccessShipment($shipment);

        // Kasir thermal format (80mm width)
        $pdf = Pdf::loadView('be.shipments.receipt', compact('shipment'))
            ->setPaper([0, 0, 226.77, 600], 'portrait');

        return $pdf->stream('resi-kasir-'.$shipment->tracking_number.'.pdf');
    }

    private function vehicleAssignmentError(User $courier, float $weightKg, int $packageCount, bool $requiresTruck): ?string
    {
        $vehicle = $courier->vehicle;

        if (! $vehicle) {
            return 'Kurir '.$courier->name.' belum punya kendaraan terdaftar.';
        }

        if (! $vehicle->isActive()) {
            return 'Kendaraan '.$vehicle->plate_number.' sedang tidak aktif.';
        }

        if ($vehicle->branch_id && (int) $vehicle->branch_id !== (int) $courier->branch_id) {
            return 'Kendaraan '.$vehicle->plate_number.' tidak terdaftar di hub kurir ini.';
        }

        if ($requiresTruck && $vehicle->type !== 'truck') {
            return 'Pengiriman antar hub wajib memakai kurir dengan kendaraan truck.';
        }

        if ((float) $vehicle->capacity_kg < $weightKg) {
            return 'Kapasitas kendaraan '.$vehicle->plate_number.' hanya '
                .number_format((float) $vehicle->capacity_kg, 0, ',', '.').' KG.';
        }

        if ((int) $vehicle->capacity_packages < $packageCount) {
            return 'Kapasitas kendaraan '.$vehicle->plate_number.' hanya '
                .number_format((int) $vehicle->capacity_packages, 0, ',', '.').' paket.';
        }

        return null;
    }

    public function printManifest(ShipmentManifest $manifest)
    {
        if (auth()->user()->role !== 'manager') {
            abort(403, 'Manifest print hanya bisa dilakukan Manager Hub.');
        }

        $branchId = $this->hubBranchId();
        if (! $branchId || ! in_array((int) $branchId, [(int) $manifest->origin_branch_id, (int) $manifest->destination_branch_id], true)) {
            abort(403, 'Manifest ini bukan milik hub Anda.');
        }

        $manifest->load([
            'originBranch',
            'destinationBranch',
            'createdBy',
            'courier.vehicle',
            'vehicle',
            'items.shipment.sender',
            'items.shipment.receiver',
            'items.leg.originBranch',
            'items.leg.destinationBranch',
        ]);

        $pdf = Pdf::loadView('be.shipments.manifest', compact('manifest'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('manifest-'.$manifest->manifest_number.'.pdf');
    }

    protected function generateManifestNumber(): string
    {
        do {
            $manifestNumber = 'MAN-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
        } while (ShipmentManifest::where('manifest_number', $manifestNumber)->exists());

        return $manifestNumber;
    }

    protected function applyShipmentPreset($query, string $preset): void
    {
        $branchId = $this->hubBranchId();

        match ($preset) {
            'need_payment' => $query->whereHas('payment', function ($query) {
                $query->where('payment_status', '!=', 'paid');
            }),
            'ready_dispatch' => $query
                ->whereHas('payment', function ($query) {
                    $query->where('payment_status', 'paid');
                })
                ->whereHas('legs', function ($query) use ($branchId) {
                    $query->where('status', 'pending')
                        ->when($branchId, fn ($query) => $query->where('origin_branch_id', $branchId));
                }),
            'inbound_today' => $query->whereHas('legs', function ($query) use ($branchId) {
                $query->where(function ($query) {
                    $query->whereDate('planned_arrival_at', today())
                        ->orWhereDate('arrived_at', today());
                })->when($branchId, fn ($query) => $query->where('destination_branch_id', $branchId));
            }),
            'failed_delivery' => $query->whereIn('status', ['delivery_failed', 'rescheduled', 'returned_to_hub']),
            'exception_open' => $query->where(function ($query) {
                $query->whereIn('status', ['held', 'damaged', 'lost', 'exception'])
                    ->orWhereHas('exceptions', fn ($query) => $query->where('status', 'open'));
            }),
            default => null,
        };
    }
}
