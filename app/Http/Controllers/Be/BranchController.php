<?php

namespace App\Http\Controllers\Be;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                abort(403, 'Hanya admin yang bisa mengelola branch.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'coordinates' => (string) $request->query('coordinates', ''),
        ];

        $branchesQuery = Branch::query()
            ->withCount([
                'users as manager_count' => fn ($query) => $query->where('role', 'manager'),
                'users as cashier_count' => fn ($query) => $query->where('role', 'cashier'),
                'users as courier_count' => fn ($query) => $query->where('role', 'courier'),
            ])
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['coordinates'] === 'ready', function ($query) {
                $query->whereNotNull('latitude')->whereNotNull('longitude');
            })
            ->when($filters['coordinates'] === 'missing', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('latitude')->orWhereNull('longitude');
                });
            });

        $branches = $branchesQuery
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $totalBranches = Branch::count();
        $coordinateReady = Branch::whereNotNull('latitude')->whereNotNull('longitude')->count();
        $crewReady = DB::query()
            ->fromSub(
                User::query()
                    ->select('branch_id')
                    ->whereNotNull('branch_id')
                    ->whereIn('role', ['manager', 'cashier', 'courier'])
                    ->groupBy('branch_id')
                    ->havingRaw('COUNT(DISTINCT role) = 3'),
                'crew_ready_branches'
            )
            ->count();

        return view('be.branches.index', compact(
            'branches',
            'filters',
            'totalBranches',
            'coordinateReady',
            'crewReady',
        ));
    }

    public function create()
    {
        $provinces = Location::where('type', 'provinsi')->orderBy('name')->get();

        return view('be.branches.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        $validation = $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        Branch::create($request->except(['_token', 'province_id']));

        return redirect()->route('be.branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        $provinces = Location::where('type', 'provinsi')->orderBy('name')->get();
        // Determine the province of the current city to pre-select it
        $currentCity = Location::where('type', 'kota')->where('name', $branch->city)->first();
        $currentProvId = $currentCity ? $currentCity->parent_id : null;

        return view('be.branches.edit', compact('branch', 'provinces', 'currentProvId'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validation = $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $branch->update($request->except(['_token', '_method', 'province_id']));

        return redirect()->route('be.branches.index')->with('success', 'Branch updated successfully.');
    }

    /**
     * Assign or unassign a manager for the branch.
     */
    public function assignManager(Request $request, Branch $branch)
    {
        $request->validate([
            'manager_id' => 'nullable|exists:users,id',
        ]);

        $managerId = $request->input('manager_id');

        DB::transaction(function () use ($branch, $managerId) {
            // Unassign previous manager for this branch if different
            $previous = User::where('branch_id', $branch->id)->where('role', 'manager')->first();
            if ($previous && (! $managerId || $previous->id != $managerId)) {
                $previous->branch_id = null;
                $previous->save();
            }

            if ($managerId) {
                $user = User::findOrFail($managerId);
                $user->role = 'manager';
                $user->branch_id = $branch->id;
                $user->save();
            }
        });

        return redirect()->back()->with('success', 'Manager assignment updated.');
    }
}
