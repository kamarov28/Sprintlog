<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $query = User::with('branch');

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
        ]);

        // Manager hanya boleh assign ke hub-nya sendiri
        $branchId = $request->branch_id;
        if ($currentUser->role === 'manager') {
            $branchId = $currentUser->branch_id;
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'branch_id' => $branchId,
        ]);

        return redirect()->route('be.users.index')->with('success', 'Personel berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $currentUser = Auth::user();

        // Authorization: Manager can't edit someone from another hub or an admin/manager
        if ($currentUser->role == 'manager') {
            if ($user->branch_id != $currentUser->branch_id || ! in_array($user->role, ['cashier', 'courier'])) {
                return redirect()->route('be.users.index')->with('error', 'Unauthorized access to personnel data.');
            }
            $branches = Branch::query()->where('id', $currentUser->branch_id)->get(['id', 'name', 'city']);
        } else {
            $branches = Branch::query()->select(['id', 'name', 'city'])->orderBy('name')->get();
        }

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
        ]);

        // Manager tidak boleh edit user di luar hub-nya
        if ($currentUser->role === 'manager' && $user->branch_id != $currentUser->branch_id) {
            abort(403);
        }

        $branchId = $currentUser->role === 'manager' ? $currentUser->branch_id : $request->branch_id;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->branch_id = $branchId;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('be.users.index')->with('success', 'Data personel diperbarui.');
    }
}
