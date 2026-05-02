<?php

namespace App\Support;

use App\Models\User;

class RoleProfile
{
    public static function for(?User $user): array
    {
        $role = $user?->role ?? 'guest';
        $hubName = $user?->branch?->name;

        return match ($role) {
            'admin' => [
                'label' => 'Admin Utama',
                'code' => 'ADMIN_HQ',
                'scope' => 'National platform',
                'summary' => 'Mengatur struktur sistem dan memantau performa ekonomi tiap hub tanpa masuk ke operasi paket harian.',
                'primary_job' => 'Platform and economy oversight',
                'hub' => 'All hubs',
                'responsibilities' => [
                    'Kelola landing CMS dan konten publik.',
                    'Kelola daftar hub nasional dan data lokasi.',
                    'Assign manager ke tiap hub.',
                    'Pantau revenue, payment mix, dan performa ekonomi per hub.',
                ],
                'quick_actions' => [
                    ['label' => 'ECONOMY WATCH', 'route' => 'be.financial-reports.index', 'tone' => 'primary'],
                    ['label' => 'LANDING CMS', 'route' => 'be.landing-sections.index', 'tone' => 'accent'],
                    ['label' => 'HUB NETWORK', 'route' => 'be.branches.index', 'tone' => 'primary'],
                    ['label' => 'MANAGER ROSTER', 'route' => 'be.users.index', 'tone' => 'neutral'],
                ],
            ],
            'manager' => [
                'label' => 'Manager Hub',
                'code' => 'HUB_OPS',
                'scope' => $hubName ?: 'Hub belum terhubung',
                'summary' => 'Memimpin operasi hub: penugasan kurir, keberangkatan paket, rekap finance, dan penanganan kendala.',
                'primary_job' => 'Hub operations lead',
                'hub' => $hubName ?: 'No hub assigned',
                'responsibilities' => [
                    'Atur kurir pickup dan delivery.',
                    'Buat manifest dan scan keberangkatan hub.',
                    'Pantau kendala, gagal antar, dan kesehatan rute.',
                    'Kelola kasir/kurir hub serta finance recap.',
                ],
                'quick_actions' => [
                    ['label' => 'READY DISPATCH', 'route' => 'be.shipments.index', 'params' => ['preset' => 'ready_dispatch'], 'tone' => 'primary'],
                    ['label' => 'PICKUP QUEUE', 'route' => 'be.pickups.index', 'tone' => 'accent'],
                    ['label' => 'FINANCE RECAP', 'route' => 'be.finance.index', 'tone' => 'neutral'],
                    ['label' => 'HUB CREW', 'route' => 'be.users.index', 'tone' => 'neutral'],
                ],
            ],
            'cashier' => [
                'label' => 'Kasir Hub',
                'code' => 'COUNTER',
                'scope' => $hubName ?: 'Hub belum terhubung',
                'summary' => 'Mengurus input paket, verifikasi pembayaran, aktivasi pengiriman, dan resi.',
                'primary_job' => 'Counter and payment desk',
                'hub' => $hubName ?: 'No hub assigned',
                'responsibilities' => [
                    'Buat pengiriman walk-in dari counter.',
                    'Verifikasi pembayaran pickup dan transfer.',
                    'Terima pickup di hub lalu aktifkan pengiriman.',
                    'Cetak resi dan bantu customer cek status.',
                ],
                'quick_actions' => [
                    ['label' => 'KASIR POS', 'route' => 'be.shipments.create', 'tone' => 'accent'],
                    ['label' => 'PAYMENT QUEUE', 'route' => 'be.pickups.index', 'tone' => 'primary'],
                    ['label' => 'SHIPMENTS', 'route' => 'be.shipments.index', 'tone' => 'neutral'],
                    ['label' => 'COURIER DIRECTORY', 'route' => 'be.users.index', 'tone' => 'neutral'],
                ],
            ],
            'courier' => [
                'label' => 'Kurir',
                'code' => 'FIELD_CREW',
                'scope' => $hubName ?: 'Hub belum terhubung',
                'summary' => 'Menjalankan pickup dan delivery lapangan lengkap dengan bukti foto dan status.',
                'primary_job' => 'Field pickup and delivery',
                'hub' => $hubName ?: 'No hub assigned',
                'responsibilities' => [
                    'Ambil paket dari customer sesuai assignment.',
                    'Upload bukti pickup dan catat cash collection.',
                    'Update status shipment yang ditugaskan.',
                    'Konfirmasi delivered atau failed delivery di lapangan.',
                ],
                'quick_actions' => [
                    ['label' => 'MY PICKUPS', 'route' => 'be.pickups.index', 'tone' => 'primary'],
                    ['label' => 'MY SHIPMENTS', 'route' => 'be.shipments.index', 'tone' => 'accent'],
                ],
            ],
            default => [
                'label' => 'Staff',
                'code' => 'STAFF',
                'scope' => 'Backend',
                'summary' => 'Backend staff.',
                'primary_job' => 'Staff operation',
                'hub' => $hubName ?: '-',
                'responsibilities' => [],
                'quick_actions' => [],
            ],
        };
    }
}
