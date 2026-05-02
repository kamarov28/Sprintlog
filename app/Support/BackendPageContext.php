<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class BackendPageContext
{
    public static function for(Request $request, ?User $user): array
    {
        $role = $user?->role ?? 'staff';
        $roleProfile = RoleProfile::for($user);

        $context = [
            'section' => str_replace('_', ' ', $roleProfile['code']),
            'title' => 'Ringkasan Sistem',
            'description' => $roleProfile['summary'],
            'tone' => 'neutral',
            'actions' => [],
            'hints' => ['Tekan / untuk mencari'],
        ];

        if ($request->routeIs('be.dashboard')) {
            return array_replace($context, [
                'title' => $role === 'admin' ? 'Monitor Ekonomi' : 'Dashboard Operasi',
                'description' => $role === 'admin'
                    ? 'Pantau kesehatan ekonomi hub, payment mix, dan manager network.'
                    : 'Ringkasan kerja role Anda hari ini.',
                'tone' => $role === 'admin' ? 'primary' : 'accent',
            ]);
        }

        if ($request->routeIs('be.search')) {
            return array_replace($context, [
                'title' => 'Pencarian Global',
                'description' => 'Cari resi, pickup, hub, atau personel dari satu tempat.',
                'tone' => 'accent',
            ]);
        }

        if ($request->routeIs('be.financial-reports.*')) {
            return array_replace($context, [
                'section' => $role === 'admin' ? 'Ekonomi Hub' : 'Rekap Finance',
                'title' => 'Laporan Keuangan',
                'description' => $role === 'admin'
                    ? 'Bandingkan revenue dan payment channel antar hub.'
                    : 'Pantau pendapatan hub dan transaksi yang sudah lunas.',
                'tone' => 'primary',
            ]);
        }

        if ($request->routeIs('be.landing-sections.*')) {
            return array_replace($context, [
                'section' => 'Kontrol Platform',
                'title' => 'LANDING CMS',
                'description' => 'Kelola copy homepage tanpa menyentuh Blade.',
                'tone' => 'accent',
                'actions' => [
                    ['label' => 'Tambah Modul', 'href' => route('be.landing-sections.create'), 'tone' => 'accent'],
                    ['label' => 'Lihat Home', 'href' => route('home'), 'tone' => 'neutral'],
                ],
            ]);
        }

        if ($request->routeIs('be.branches.*')) {
            return array_replace($context, [
                'section' => 'Kontrol Platform',
                'title' => 'Jaringan Hub',
                'description' => 'Kelola coverage hub, koordinat, dan manager assignment.',
                'tone' => 'primary',
                'actions' => [
                    ['label' => 'Tambah Hub', 'href' => route('be.branches.create'), 'tone' => 'primary'],
                ],
            ]);
        }

        if ($request->routeIs('be.users.*')) {
            return array_replace($context, [
                'section' => $role === 'admin' ? 'Roster Manager' : ($role === 'cashier' ? 'Direktori Kurir' : 'Tim Hub'),
                'title' => $role === 'cashier' ? 'Direktori Kurir' : 'Direktori Personel',
                'description' => $role === 'admin'
                    ? 'Assign manager ke hub dan pantau ownership tiap cabang.'
                    : ($role === 'cashier' ? 'Kontak kurir hub untuk koordinasi counter.' : 'Kelola kasir dan kurir dalam hub Anda.'),
                'tone' => 'neutral',
                'actions' => in_array($role, ['admin', 'manager'], true)
                    ? [['label' => $role === 'admin' ? 'Tambah Manager' : 'Tambah Tim', 'href' => route('be.users.create'), 'tone' => 'accent']]
                    : [],
            ]);
        }

        if ($request->routeIs('be.shipments.create')) {
            return array_replace($context, [
                'section' => 'Counter',
                'title' => 'KASIR POS',
                'description' => 'Input shipment walk-in, payment, dan data penerima.',
                'tone' => 'accent',
                'actions' => [
                    ['label' => 'Daftar Paket', 'href' => route('be.shipments.index'), 'tone' => 'neutral'],
                ],
            ]);
        }

        if ($request->routeIs('be.shipments.show')) {
            return array_replace($context, [
                'section' => $role === 'courier' ? 'Delivery Lapangan' : 'Operasi Paket',
                'title' => 'Detail Paket',
                'description' => 'Status, rute, kendala, dan timeline pengiriman.',
                'tone' => 'primary',
                'actions' => [
                    ['label' => 'Kembali ke Paket', 'href' => route('be.shipments.index'), 'tone' => 'neutral'],
                ],
            ]);
        }

        if ($request->routeIs('be.shipments.*')) {
            return array_replace($context, [
                'section' => $role === 'courier' ? 'Delivery Lapangan' : 'Operasi Paket',
                'title' => $role === 'courier' ? 'Paket Saya' : 'Manifest Paket',
                'description' => $role === 'manager'
                    ? 'Kirim, terima, pantau kendala, dan buat manifest batch.'
                    : ($role === 'cashier' ? 'Cari pengiriman dan bantu customer dari counter.' : 'Daftar pengiriman yang ditugaskan ke Anda.'),
                'tone' => 'primary',
                'actions' => $role === 'cashier'
                    ? [['label' => 'KASIR POS', 'href' => route('be.shipments.create'), 'tone' => 'accent']]
                    : [],
                'hints' => $role === 'manager' ? ['Tekan / untuk mencari', 'Alt+A pilih paket siap kirim'] : ['Tekan / untuk mencari'],
            ]);
        }

        if ($request->routeIs('be.pickups.*')) {
            return array_replace($context, [
                'section' => $role === 'courier' ? 'Pickup Lapangan' : 'Pickup Hub',
                'title' => $role === 'courier' ? 'Pickup Saya' : 'Antrean Pickup',
                'description' => $role === 'manager'
                    ? 'Assign kurir dan monitor pickup yang masuk ke hub.'
                    : ($role === 'cashier' ? 'Verifikasi payment, terima pickup di hub, dan aktifkan shipment.' : 'Pickup lapangan dan upload bukti ambil.'),
                'tone' => 'accent',
            ]);
        }

        if ($request->routeIs('be.finance.*')) {
            return array_replace($context, [
                'section' => 'Operasi Hub',
                'title' => 'Rekap Finance',
                'description' => 'Rekap kas dan digital payment untuk hub Anda.',
                'tone' => 'primary',
            ]);
        }

        return $context;
    }
}
