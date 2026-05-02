<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NationalBranchSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;
        $updated = 0;
        $desiredHubNames = [];
        $provinceBranches = collect();

        Location::query()
            ->where('type', 'provinsi')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->each(function (Location $province) use (&$created, &$desiredHubNames, &$provinceBranches, &$updated): void {
                $hubName = $this->provinceHubName($province->name);
                $desiredHubNames[] = $hubName;

                $branch = Branch::updateOrCreate(
                    ['name' => $hubName],
                    [
                        'city' => $province->name,
                        'address' => 'Jl. Operasional SprintLog, '.$province->name,
                        'phone' => $this->phoneForProvince((int) $province->id),
                    ],
                );

                $provinceBranches->put($province->name, $branch);
                $branch->wasRecentlyCreated ? $created++ : $updated++;
            });

        $pruned = $this->compactCityHubs($desiredHubNames, $provinceBranches);

        $this->command?->info("Province hubs synced. Created: {$created}. Updated: {$updated}. City hubs compacted: {$pruned}.");
    }

    private function compactCityHubs(array $desiredHubNames, Collection $provinceBranches): int
    {
        $cityProvinceMap = $this->cityProvinceMap();
        $pruned = 0;

        Branch::query()
            ->whereNotIn('name', $desiredHubNames)
            ->orderBy('id')
            ->chunkById(100, function ($branches) use ($cityProvinceMap, $provinceBranches, &$pruned): void {
                foreach ($branches as $branch) {
                    $targetBranch = $this->resolveProvinceBranch($branch, $cityProvinceMap, $provinceBranches);

                    if (! $targetBranch || (int) $targetBranch->id === (int) $branch->id) {
                        continue;
                    }

                    DB::transaction(function () use ($branch, $targetBranch, &$pruned): void {
                        User::query()
                            ->where('branch_id', $branch->id)
                            ->where('email', 'like', 'hub%.%@sprintlog.local')
                            ->delete();

                        User::query()
                            ->where('branch_id', $branch->id)
                            ->update(['branch_id' => $targetBranch->id]);

                        DB::table('shipments')
                            ->where('origin_branch_id', $branch->id)
                            ->update(['origin_branch_id' => $targetBranch->id]);

                        DB::table('shipments')
                            ->where('destination_branch_id', $branch->id)
                            ->update(['destination_branch_id' => $targetBranch->id]);

                        DB::table('pickup_requests')
                            ->where('branch_id', $branch->id)
                            ->update(['branch_id' => $targetBranch->id]);

                        DB::table('bank_accounts')
                            ->where('branch_id', $branch->id)
                            ->update(['branch_id' => $targetBranch->id]);

                        $branch->delete();
                        $pruned++;
                    });
                }
            });

        return $pruned;
    }

    private function resolveProvinceBranch(Branch $branch, array $cityProvinceMap, Collection $provinceBranches): ?Branch
    {
        $cityKey = $this->normalizeLocationName((string) $branch->city);
        $provinceName = $cityProvinceMap[$cityKey] ?? null;

        if (! $provinceName && $cityKey !== '') {
            foreach ($cityProvinceMap as $knownCity => $knownProvince) {
                if (str_contains($knownCity, $cityKey) || str_contains($cityKey, $knownCity)) {
                    $provinceName = $knownProvince;
                    break;
                }
            }
        }

        if (! $provinceName) {
            $haystack = $this->normalizeLocationName($branch->name.' '.$branch->address.' '.$branch->city);
            $provinceName = $provinceBranches
                ->keys()
                ->first(fn (string $name) => str_contains($haystack, $this->normalizeLocationName($name)));
        }

        return $provinceName ? $provinceBranches->get($provinceName) : null;
    }

    private function cityProvinceMap(): array
    {
        $map = [];

        Location::query()
            ->with('parentLocation:id,name')
            ->where('type', 'kota')
            ->get(['id', 'name', 'parent_id'])
            ->each(function (Location $city) use (&$map): void {
                if (! $city->parentLocation) {
                    return;
                }

                $map[$this->normalizeLocationName($city->name)] = $city->parentLocation->name;
                $map[$this->normalizeLocationName($this->stripCityPrefix($city->name))] = $city->parentLocation->name;
            });

        return $map;
    }

    private function provinceHubName(string $provinceName): string
    {
        return 'SprintLog Hub '.trim($provinceName);
    }

    private function phoneForProvince(int $provinceId): string
    {
        return '1500-P'.str_pad((string) $provinceId, 3, '0', STR_PAD_LEFT);
    }

    private function normalizeLocationName(string $name): string
    {
        return strtolower(trim($this->stripCityPrefix($name)));
    }

    private function stripCityPrefix(string $name): string
    {
        return (string) preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', trim($name));
    }
}
