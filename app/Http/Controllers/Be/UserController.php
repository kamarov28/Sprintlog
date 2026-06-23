<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $query = User::with(['branch', 'vehicle']);

        if ($currentUser->role == 'admin') {
            $filters = [
                'search' => trim((string) $request->query('search', '')),
            ];

            $branches = Branch::query()
                ->select(['id', 'name', 'city'])
                ->with('manager:id,name,email,branch_id')
                ->when($filters['search'] !== '', function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString();

            $visibleManagerIds = $branches->getCollection()
                ->pluck('manager.id')
                ->filter()
                ->values();

            $managers = User::query()
                ->select(['id', 'name', 'email', 'branch_id'])
                ->with('branch:id,name')
                ->where('role', 'manager')
                ->where(function ($query) use ($visibleManagerIds) {
                    $query->whereNull('branch_id')
                        ->when($visibleManagerIds->isNotEmpty(), fn ($query) => $query->orWhereIn('id', $visibleManagerIds));
                })
                ->orderBy('name')
                ->get();

            return view('be.users.index', compact('branches', 'filters', 'managers'));
        } elseif ($currentUser->role == 'manager') {
            // Manager can see Cashiers and Couriers in the same Hub
            $query->where('branch_id', $currentUser->branch_id)
                ->whereIn('role', ['cashier', 'courier']);
        } elseif ($currentUser->role == 'cashier') {
            // Cashiers only need to see couriers in their hub
            $query->where('branch_id', $currentUser->branch_id)
                ->where('role', 'courier');
        } else {
            // Others see nothing in personnel list by default for safety
            $query->where('id', 0);
        }

        $users = $query->latest()->paginate(25);

        return view('be.users.index', compact('users'));
    }

    public function create()
    {
        $currentUser = Auth::user();
        $branches = Branch::query()->select(['id', 'name', 'city'])->orderBy('name')->get();

        // If manager, they can only recruit for their own Hub
        if ($currentUser->role == 'manager') {
            $branches = Branch::query()->where('id', $currentUser->branch_id)->get(['id', 'name', 'city']);
        }

        $selectedBranchId = request()->query('branch_id', null);

        return view('be.users.create', compact('branches', 'selectedBranchId'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $allowedRoles = $currentUser->role === 'admin' ? ['manager'] : ['cashier', 'courier'];

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
            'branch_id' => 'nullable|exists:branches,id',
            'phone' => 'nullable|string|max:20',
            'vehicle_plate_number' => 'required_if:role,courier|nullable|string|max:32',
            'vehicle_type' => 'required_if:role,courier|nullable|in:motor,mobil,truck',
            'vehicle_capacity_kg' => 'required_if:role,courier|nullable|numeric|min:0.1',
            'vehicle_capacity_packages' => 'required_if:role,courier|nullable|integer|min:1',
        ]);

        // Manager hanya boleh assign ke hub-nya sendiri
        $branchId = $request->branch_id;
        if ($currentUser->role === 'manager') {
            $branchId = $currentUser->branch_id;
        }
        $branch = Branch::find($branchId);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'branch_id' => $branchId,
            'phone' => $request->phone,
            'city' => $branch?->city,
            'address' => $branch?->address,
        ]);
        $this->replaceBranchManager($user, $branchId);

        $this->syncCourierVehicle($request, $user, $branchId);

        return redirect()->route('be.users.index')->with('success', 'Personel berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $currentUser = Auth::user();

        // Authorization: Manager can't edit someone from another hub or an admin/manager
        if ($currentUser->role === 'admin' && $user->role !== 'manager') {
            return redirect()->route('be.users.index')->with('error', 'Admin hanya mengedit manager dari halaman personel.');
        }

        if ($currentUser->role == 'manager') {
            if ($user->branch_id != $currentUser->branch_id || ! in_array($user->role, ['cashier', 'courier'])) {
                return redirect()->route('be.users.index')->with('error', 'Unauthorized access to personnel data.');
            }
            $branches = Branch::query()->where('id', $currentUser->branch_id)->get(['id', 'name', 'city']);
        } else {
            $branches = Branch::query()->select(['id', 'name', 'city'])->orderBy('name')->get();
        }

        $user->load('vehicle');

        return view('be.users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();

        $allowedRoles = $currentUser->role === 'admin' ? ['manager'] : ['cashier', 'courier'];

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => ['required', 'in:'.implode(',', $allowedRoles)],
            'branch_id' => 'nullable|exists:branches,id',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|min:8',
            'vehicle_plate_number' => 'required_if:role,courier|nullable|string|max:32',
            'vehicle_type' => 'required_if:role,courier|nullable|in:motor,mobil,truck',
            'vehicle_capacity_kg' => 'required_if:role,courier|nullable|numeric|min:0.1',
            'vehicle_capacity_packages' => 'required_if:role,courier|nullable|integer|min:1',
        ]);

        // Manager tidak boleh edit user di luar hub-nya
        $this->authorizePersonnelMutation($user);

        $branchId = $currentUser->role === 'manager' ? $currentUser->branch_id : $request->branch_id;
        $branch = Branch::find($branchId);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->branch_id = $branchId;
        $user->phone = $request->phone;
        $user->city = $branch?->city;
        $user->address = $branch?->address;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        $this->replaceBranchManager($user, $branchId);
        $this->syncCourierVehicle($request, $user, $branchId);

        return redirect()->route('be.users.index')->with('success', 'Data personel diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->authorizePersonnelMutation($user);

        if ((int) $user->id === (int) Auth::id()) {
            return back()->with('error', 'Akun sendiri tidak bisa dipecat dari halaman ini.');
        }

        $name = $user->name;

        try {
            DB::transaction(function () use ($user): void {
                $user->vehicle()->delete();
                $user->delete();
            });

            return redirect()->route('be.users.index')->with('success', 'Personel '.$name.' berhasil dihapus.');
        } catch (QueryException) {
            $this->terminateReferencedUser($user);

            return redirect()->route('be.users.index')->with('success', 'Personel '.$name.' dinonaktifkan dan dilepas dari hub karena masih punya riwayat transaksi.');
        }
    }

    private function authorizePersonnelMutation(User $user): void
    {
        $currentUser = Auth::user();

        if ($currentUser->role === 'admin') {
            if ($user->role !== 'manager') {
                abort(403, 'Admin hanya mengelola manager dari halaman personel.');
            }

            return;
        }

        if ($currentUser->role === 'manager') {
            if ((int) $user->branch_id !== (int) $currentUser->branch_id || ! in_array($user->role, ['cashier', 'courier'], true)) {
                abort(403, 'Manager hanya bisa mengelola kasir dan kurir di hub sendiri.');
            }

            return;
        }

        abort(403);
    }

    private function syncCourierVehicle(Request $request, User $user, ?int $branchId): void
    {
        if ($user->role !== 'courier') {
            $user->vehicle()->delete();

            return;
        }

        Vehicle::updateOrCreate(
            ['courier_id' => $user->id],
            [
                'plate_number' => $request->vehicle_plate_number,
                'type' => $request->vehicle_type,
                'capacity_kg' => (float) $request->vehicle_capacity_kg,
                'capacity_packages' => (int) $request->vehicle_capacity_packages,
                'status' => 'active',
                'branch_id' => $branchId,
            ],
        );
    }

    private function replaceBranchManager(User $user, ?int $branchId): void
    {
        if ($user->role !== 'manager' || ! $branchId) {
            return;
        }

        User::query()
            ->where('role', 'manager')
            ->where('branch_id', $branchId)
            ->where('id', '!=', $user->id)
            ->update(['branch_id' => null]);
    }

    private function terminateReferencedUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->vehicle()->update(['status' => 'inactive', 'courier_id' => null]);

            $payload = [
                'name' => $user->name.' (Nonaktif)',
                'email' => 'terminated-'.$user->id.'-'.time().'@sprintlog.local',
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'branch_id' => null,
                'remember_token' => null,
            ];

            if (Schema::hasColumn('users', 'courier_status')) {
                $payload['courier_status'] = 'unavailable';
            }

            $user->update($payload);
        });
    }
}
