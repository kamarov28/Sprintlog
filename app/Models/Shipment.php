<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shipment_date' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }

    public function originBranch()
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function courier()
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class, 'shipment_id');
    }

    public function trackings()
    {
        return $this->hasMany(ShipmentTracking::class, 'shipment_id')->orderBy('tracked_at', 'asc');
    }

    public function latestTracking()
    {
        return $this->hasOne(ShipmentTracking::class, 'shipment_id')->latestOfMany('tracked_at');
    }

    public function rate()
    {
        return $this->belongsTo(Rate::class);
    }

    public function statusAudits()
    {
        return $this->hasMany(ShipmentStatusAudit::class, 'shipment_id');
    }

    public function legs()
    {
        return $this->hasMany(ShipmentLeg::class, 'shipment_id')->orderBy('sequence');
    }

    public function exceptions()
    {
        return $this->hasMany(ShipmentException::class);
    }

    public function notifications()
    {
        return $this->hasMany(ShipmentNotification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'shipment_id');
    }

    public function healthStatus(): string
    {
        if ($this->status === 'delivered') {
            return 'complete';
        }

        if (in_array($this->status, ['cancelled', 'delivery_failed', 'returned_to_hub', 'held', 'damaged', 'lost', 'exception'], true)) {
            return 'exception';
        }

        if ($this->relationLoaded('exceptions') && $this->exceptions->where('status', 'open')->isNotEmpty()) {
            return 'exception';
        }

        if ($this->relationLoaded('payment') && $this->payment && $this->payment->payment_status !== 'paid') {
            return 'waiting_payment';
        }

        $legs = $this->relationLoaded('legs') ? $this->legs : collect();
        $activeLegs = $legs->whereIn('status', ['pending', 'departed']);

        if ($activeLegs->contains(fn ($leg) => $leg->planned_arrival_at && $leg->planned_arrival_at->isPast())) {
            return 'delayed';
        }

        if ($activeLegs->contains(fn ($leg) => $leg->planned_arrival_at && $leg->planned_arrival_at->diffInHours(now(), false) >= -12)) {
            return 'at_risk';
        }

        return 'on_time';
    }

    public function healthLabel(): string
    {
        return match ($this->healthStatus()) {
            'complete' => 'COMPLETE',
            'exception' => 'EXCEPTION',
            'waiting_payment' => 'WAITING_PAYMENT',
            'delayed' => 'DELAYED',
            'at_risk' => 'AT_RISK',
            default => 'ON_TIME',
        };
    }

    public function nextPendingLegFrom(?int $branchId)
    {
        if (! $branchId || ! $this->relationLoaded('legs')) {
            return null;
        }

        return $this->legs
            ->where('origin_branch_id', $branchId)
            ->where('status', 'pending')
            ->sortBy('sequence')
            ->first();
    }

    public function dispatchLockReason(?int $branchId): ?string
    {
        if (in_array($this->status, ['delivered', 'cancelled', 'lost', 'damaged'], true)) {
            return 'STATUS_'.$this->status;
        }

        if ($this->relationLoaded('payment') && $this->payment && $this->payment->payment_status !== 'paid') {
            return 'PAYMENT_'.$this->payment->payment_status;
        }

        if (! $this->nextPendingLegFrom($branchId)) {
            return 'NO_PENDING_LEG_FROM_THIS_HUB';
        }

        return null;
    }

    public function nextActionHint(?int $branchId, ?string $role): array
    {
        if ($this->relationLoaded('payment') && $this->payment && $this->payment->payment_status !== 'paid') {
            return ['label' => 'Verifikasi pembayaran', 'tone' => 'danger'];
        }

        if (in_array($this->status, ['delivered', 'cancelled'], true)) {
            return ['label' => 'Tidak ada aksi', 'tone' => 'neutral'];
        }

        if (in_array($this->status, ['delivery_failed', 'rescheduled', 'returned_to_hub', 'held', 'damaged', 'lost', 'exception'], true)) {
            return ['label' => 'Review kendala', 'tone' => 'danger'];
        }

        if (in_array($role, ['manager', 'cashier'], true) && $branchId && $this->relationLoaded('legs')) {
            $receivableLeg = $this->legs
                ->where('destination_branch_id', $branchId)
                ->where('status', 'departed')
                ->sortBy('sequence')
                ->first();

            if ($receivableLeg) {
                return ['label' => 'Terima di hub', 'tone' => 'primary'];
            }

            if ($this->nextPendingLegFrom($branchId)) {
                return ['label' => 'Berangkatkan dari hub', 'tone' => 'accent'];
            }

            if ((int) $this->destination_branch_id === (int) $branchId && $this->status === 'arrived_at_branch') {
                return ['label' => $this->courier_id ? 'Mulai delivery' : 'Assign delivery', 'tone' => 'primary'];
            }
        }

        if ($role === 'courier') {
            return ['label' => match ($this->status) {
                'pending' => 'Ambil paket',
                'picked_up' => 'Berangkat',
                'in_transit' => 'Tiba di hub',
                'arrived_at_branch' => 'Mulai delivery',
                'out_for_delivery' => 'Selesaikan delivery',
                default => 'Update status',
            }, 'tone' => 'accent'];
        }

        return ['label' => 'Monitor', 'tone' => 'neutral'];
    }
}
