<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cash_collected_at' => 'datetime',
        'cash_handover_at' => 'datetime',
        'auto_assignment_score' => 'float',
        'auto_assignment_meta' => 'array',
    ];

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function senderCity()
    {
        return $this->belongsTo(Location::class, 'sender_city_id');
    }

    public function receiverCity()
    {
        return $this->belongsTo(Location::class, 'receiver_city_id');
    }

    public function statusAudits()
    {
        return $this->hasMany(PickupStatusAudit::class, 'pickup_request_id')->latest();
    }

    public function latestStatusAudit()
    {
        return $this->hasOne(PickupStatusAudit::class, 'pickup_request_id')->latestOfMany();
    }

    public function cashCollector()
    {
        return $this->belongsTo(User::class, 'cash_collected_by');
    }

    public function cashVerifier()
    {
        return $this->belongsTo(User::class, 'cash_verified_by');
    }

    public function nextActionHint(?string $role = null): array
    {
        if ($this->status === 'cancelled') {
            return [
                'label' => 'Tidak ada aksi',
                'description' => 'Request sudah dibatalkan.',
                'tone' => 'neutral',
            ];
        }

        if ($this->shipment_id || $this->shipment) {
            return [
                'label' => 'Lihat shipment',
                'description' => 'Pickup sudah berubah menjadi shipment aktif.',
                'tone' => 'success',
            ];
        }

        if ($role === 'courier') {
            return match ($this->status) {
                'assigned' => [
                    'label' => 'Ambil paket',
                    'description' => 'Hub sudah menugaskan pickup ini. Hubungi customer, buka map, lalu upload bukti pickup.',
                    'tone' => 'primary',
                ],
                'picked_up' => [
                    'label' => 'Antar ke hub',
                    'description' => 'Paket sudah diambil. Serahkan paket dan cash, jika ada, ke hub.',
                    'tone' => 'accent',
                ],
                'hub_received' => [
                    'label' => 'Selesai pickup',
                    'description' => 'Hub sudah menerima paket. Tunggu assignment berikutnya.',
                    'tone' => 'success',
                ],
                default => [
                    'label' => 'Menunggu assignment',
                    'description' => 'Belum ada aksi lapangan untuk pickup ini.',
                    'tone' => 'neutral',
                ],
            };
        }

        if (in_array($role, ['manager', 'cashier'], true)) {
            if (! $this->courier_id && $this->status === 'pending') {
                return [
                    'label' => 'Assign courier',
                    'description' => 'Pilih kurir pickup dari hub, lalu kurir akan mengambil paket.',
                    'tone' => 'primary',
                ];
            }

            if ($this->status === 'assigned') {
                return [
                    'label' => 'Tunggu pickup',
                    'description' => 'Kurir sudah ditugaskan. Pantau bukti pickup dari lapangan.',
                    'tone' => 'neutral',
                ];
            }

            if ($this->status === 'picked_up') {
                return [
                    'label' => 'Terima di hub',
                    'description' => 'Konfirmasi paket sudah sampai hub sebelum aktivasi shipment.',
                    'tone' => 'accent',
                ];
            }

            if ($this->status === 'hub_received' && $this->payment_status !== 'paid') {
                return [
                    'label' => 'Bereskan pembayaran',
                    'description' => $this->payment_method === 'cash_on_pickup'
                        ? 'Verifikasi setoran tunai kurir dulu, baru shipment bisa diaktifkan.'
                        : 'Review bukti transfer dulu, baru shipment bisa diaktifkan.',
                    'tone' => 'danger',
                ];
            }

            if ($this->status === 'hub_received') {
                return [
                    'label' => 'Aktifkan shipment',
                    'description' => 'Payment clear dan paket sudah di hub. Buat shipment agar nomor resi aktif.',
                    'tone' => 'success',
                ];
            }
        }

        return [
            'label' => 'Pantau progres',
            'description' => 'Ikuti status pickup, payment, lalu aktivasi shipment.',
            'tone' => 'neutral',
        ];
    }
}
