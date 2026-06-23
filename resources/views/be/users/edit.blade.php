@extends('be.layouts.main')

@section('header_title', 'Edit Personel: ' . $user->name)

@section('content')

    <div class="hud-panel" style="max-width: 600px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">Update Data Personel</h3>

        <form action="{{ route('be.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama Lengkap</label><br>
                <input type="text" name="name" value="{{ $user->name }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Email / User ID</label><br>
                <input type="email" name="email" value="{{ $user->email }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Nomor Telepon</label><br>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Password baru (kosongkan jika tidak diubah)</label><br>
                <input type="password" name="password" style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">Role</label><br>
                    <select name="role" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                        @if(auth()->user()->role == 'admin')
                            <option value="manager" {{ $user->role == 'manager' ? 'selected' : '' }}>MANAGER</option>
                        @else
                            <option value="cashier" {{ $user->role == 'cashier' ? 'selected' : '' }}>CASHIER</option>
                            <option value="courier" {{ $user->role == 'courier' ? 'selected' : '' }}>COURIER</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">Hub</label><br>
                    @if(auth()->user()->role == 'manager')
                        <input type="hidden" name="branch_id" value="{{ $user->branch_id }}">
                        <input type="text" disabled value="{{ $branches->first()->name ?? '-' }}" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui); background: transparent; color: var(--color-gray); border: 1px solid var(--color-panel-border);">
                    @else
                        <select name="branch_id" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                            <option value="">Tanpa hub</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $user->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            @if(auth()->user()->role == 'manager')
                <div class="form-cluster form-cluster--accent" style="margin-bottom: 3rem;">
                    <div class="data-label">Kendaraan Kurir</div>
                    <div class="font-ui text-gray" style="font-size: 0.78rem; margin-bottom: 1rem;">Wajib diisi kalau role adalah courier. Jika role diubah ke cashier, kendaraan akan dilepas.</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Plat Nomor</label>
                            <input type="text" name="vehicle_plate_number" value="{{ old('vehicle_plate_number', $user->vehicle?->plate_number) }}" style="width: 100%; padding: 0.55rem;">
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Jenis</label>
                            <select name="vehicle_type" style="width: 100%; padding: 0.55rem;">
                                <option value="motor" @selected(old('vehicle_type', $user->vehicle?->type) === 'motor')>MOTOR</option>
                                <option value="mobil" @selected(old('vehicle_type', $user->vehicle?->type) === 'mobil')>MOBIL</option>
                                <option value="truck" @selected(old('vehicle_type', $user->vehicle?->type ?? 'truck') === 'truck')>TRUCK</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Kapasitas KG</label>
                            <input type="number" name="vehicle_capacity_kg" value="{{ old('vehicle_capacity_kg', $user->vehicle?->capacity_kg ?? 1200) }}" min="0.1" step="0.1" style="width: 100%; padding: 0.55rem;">
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Kapasitas Paket</label>
                            <input type="number" name="vehicle_capacity_packages" value="{{ old('vehicle_capacity_packages', $user->vehicle?->capacity_packages ?? 180) }}" min="1" step="1" style="width: 100%; padding: 0.55rem;">
                        </div>
                    </div>
                </div>
            @endif

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.users.index') }}" class="btn-neon" style="color: var(--color-gray); border-color: var(--color-gray);">Batal</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px;">Update Personel</button>
            </div>

        </form>

        <form action="{{ route('be.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Pecat {{ addslashes($user->name) }} dari SprintLog?');" style="margin-top: 1rem; display: flex; justify-content: flex-end;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-neon" style="border-color: red; color: red; background: transparent;">Pecat Personel</button>
        </form>
    </div>

@endsection
