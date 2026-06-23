<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NationalBranchSeeder extends Seeder
{
    private const HUB_PROFILES = [
        'Aceh' => ['address' => 'Kantor Gubernur Aceh, Jl. T. Nyak Arief, Banda Aceh', 'latitude' => 5.5700, 'longitude' => 95.3670],
        'Sumatera Utara' => ['address' => 'Kantor Gubernur Sumatera Utara, Jl. Pangeran Diponegoro No.30, Medan', 'latitude' => 3.5904, 'longitude' => 98.6739],
        'Sumatera Barat' => ['address' => 'Kantor Gubernur Sumatera Barat, Jl. Jenderal Sudirman No.51, Padang', 'latitude' => -0.9471, 'longitude' => 100.4172],
        'Riau' => ['address' => 'Kantor Gubernur Riau, Jl. Jenderal Sudirman No.460, Pekanbaru', 'latitude' => 0.5071, 'longitude' => 101.4478],
        'Kepulauan Riau' => ['address' => 'Kantor Gubernur Kepulauan Riau, Pulau Dompak, Tanjungpinang', 'latitude' => 0.9186, 'longitude' => 104.4665],
        'Jambi' => ['address' => 'Kantor Gubernur Jambi, Jl. Jenderal Ahmad Yani No.1, Telanaipura, Jambi', 'latitude' => -1.6101, 'longitude' => 103.6131],
        'Sumatera Selatan' => ['address' => 'Kantor Gubernur Sumatera Selatan, Jl. Kapten A. Rivai No.3, Palembang', 'latitude' => -2.9761, 'longitude' => 104.7754],
        'Kepulauan Bangka Belitung' => ['address' => 'Kantor Gubernur Kepulauan Bangka Belitung, Air Itam, Pangkalpinang', 'latitude' => -2.1296, 'longitude' => 106.1138],
        'Bengkulu' => ['address' => 'Kantor Gubernur Bengkulu, Jl. Pembangunan No.1, Padang Harapan, Bengkulu', 'latitude' => -3.7928, 'longitude' => 102.2608],
        'Lampung' => ['address' => 'Kantor Gubernur Lampung, Jl. Wolter Monginsidi No.69, Bandar Lampung', 'latitude' => -5.4292, 'longitude' => 105.2611],
        'Banten' => ['address' => 'Kawasan Pusat Pemerintahan Provinsi Banten, Jl. Syech Nawawi Al-Bantani, Serang', 'latitude' => -6.1201, 'longitude' => 106.1503],
        'DKI Jakarta' => ['address' => 'Balai Kota DKI Jakarta, Jl. Medan Merdeka Selatan No.8-9, Jakarta Pusat', 'latitude' => -6.1805, 'longitude' => 106.8284],
        'Jawa Barat' => ['address' => 'Gedung Sate, Jl. Diponegoro No.22, Bandung', 'latitude' => -6.9025, 'longitude' => 107.6187],
        'Jawa Tengah' => ['address' => 'Kantor Gubernur Jawa Tengah, Jl. Pahlawan No.9, Semarang', 'latitude' => -6.9904, 'longitude' => 110.4218],
        'DI Yogyakarta' => ['address' => 'Kompleks Kepatihan, Jl. Malioboro No.14, Yogyakarta', 'latitude' => -7.7956, 'longitude' => 110.3695],
        'Jawa Timur' => ['address' => 'Gedung Negara Grahadi, Jl. Gubernur Suryo No.7, Surabaya', 'latitude' => -7.2630, 'longitude' => 112.7425],
        'Bali' => ['address' => 'Kantor Gubernur Bali, Jl. Basuki Rahmat No.1, Denpasar', 'latitude' => -8.6705, 'longitude' => 115.2126],
        'Nusa Tenggara Barat' => ['address' => 'Kantor Gubernur Nusa Tenggara Barat, Jl. Pejanggik No.12, Mataram', 'latitude' => -8.5833, 'longitude' => 116.1167],
        'Nusa Tenggara Timur' => ['address' => 'Kantor Gubernur Nusa Tenggara Timur, Jl. El Tari, Kupang', 'latitude' => -10.1772, 'longitude' => 123.6070],
        'Kalimantan Barat' => ['address' => 'Kantor Gubernur Kalimantan Barat, Jl. Ahmad Yani, Pontianak', 'latitude' => -0.0263, 'longitude' => 109.3425],
        'Kalimantan Tengah' => ['address' => 'Kantor Gubernur Kalimantan Tengah, Jl. RTA Milono No.1, Palangka Raya', 'latitude' => -2.2096, 'longitude' => 113.9108],
        'Kalimantan Selatan' => ['address' => 'Setdaprov Kalimantan Selatan, Jl. Dharma Praja, Banjarbaru', 'latitude' => -3.4424, 'longitude' => 114.8324],
        'Kalimantan Timur' => ['address' => 'Kantor Gubernur Kalimantan Timur, Jl. Gajah Mada No.2, Samarinda', 'latitude' => -0.5022, 'longitude' => 117.1536],
        'Kalimantan Utara' => ['address' => 'Kantor Gubernur Kalimantan Utara, Tanjung Selor', 'latitude' => 2.8375, 'longitude' => 117.3653],
        'Sulawesi Utara' => ['address' => 'Kantor Gubernur Sulawesi Utara, Jl. 17 Agustus, Manado', 'latitude' => 1.4748, 'longitude' => 124.8421],
        'Gorontalo' => ['address' => 'Kantor Gubernur Gorontalo, Jl. Sapta Marga, Gorontalo', 'latitude' => 0.5435, 'longitude' => 123.0568],
        'Sulawesi Tengah' => ['address' => 'Kantor Gubernur Sulawesi Tengah, Jl. Sam Ratulangi, Palu', 'latitude' => -0.9003, 'longitude' => 119.8779],
        'Sulawesi Barat' => ['address' => 'Kantor Gubernur Sulawesi Barat, Rangas, Mamuju', 'latitude' => -2.6748, 'longitude' => 118.8950],
        'Sulawesi Selatan' => ['address' => 'Kantor Gubernur Sulawesi Selatan, Jl. Urip Sumoharjo No.269, Makassar', 'latitude' => -5.1477, 'longitude' => 119.4327],
        'Sulawesi Tenggara' => ['address' => 'Kantor Gubernur Sulawesi Tenggara, Kompleks Bumi Praja Anduonohu, Kendari', 'latitude' => -3.9985, 'longitude' => 122.5120],
        'Maluku' => ['address' => 'Kantor Gubernur Maluku, Jl. Pattimura No.1, Ambon', 'latitude' => -3.6954, 'longitude' => 128.1814],
        'Maluku Utara' => ['address' => 'Kantor Gubernur Maluku Utara, Sofifi, Tidore Kepulauan', 'latitude' => 0.7893, 'longitude' => 127.3842],
        'Papua Barat' => ['address' => 'Kantor Gubernur Papua Barat, Arfai, Manokwari', 'latitude' => -0.8615, 'longitude' => 134.0620],
        'Papua Barat Daya' => ['address' => 'Kantor Gubernur Papua Barat Daya, Sorong', 'latitude' => -0.8629, 'longitude' => 131.2545],
        'Papua' => ['address' => 'Kantor Gubernur Papua, Jl. Soa Siu Dok II, Jayapura', 'latitude' => -2.5916, 'longitude' => 140.6690],
        'Papua Tengah' => ['address' => 'Kantor Gubernur Papua Tengah, Nabire', 'latitude' => -3.3667, 'longitude' => 135.5000],
        'Papua Pegunungan' => ['address' => 'Kantor Gubernur Papua Pegunungan, Wamena', 'latitude' => -4.0000, 'longitude' => 138.9500],
        'Papua Selatan' => ['address' => 'Kantor Gubernur Papua Selatan, Merauke', 'latitude' => -8.4932, 'longitude' => 140.4018],
    ];

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
                    $this->branchPayload($province),
                );

                $provinceBranches->put($province->name, $branch);
                $branch->wasRecentlyCreated ? $created++ : $updated++;
            });

        $pruned = $this->compactCityHubs($desiredHubNames, $provinceBranches);

        $this->command?->info("Province hubs synced. Created: {$created}. Updated: {$updated}. City hubs compacted: {$pruned}.");
    }

    private function branchPayload(Location $province): array
    {
        $profile = self::HUB_PROFILES[$province->name] ?? null;
        $payload = [
            'city' => $province->name,
            'address' => $profile['address'] ?? 'Kantor Gubernur '.$province->name,
            'phone' => $this->phoneForProvince((int) $province->id),
        ];

        if ($profile && Schema::hasColumn('branches', 'latitude')) {
            $payload['latitude'] = $profile['latitude'];
        }

        if ($profile && Schema::hasColumn('branches', 'longitude')) {
            $payload['longitude'] = $profile['longitude'];
        }

        return $payload;
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
                            ->whereIn('role', ['manager', 'cashier', 'courier'])
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
