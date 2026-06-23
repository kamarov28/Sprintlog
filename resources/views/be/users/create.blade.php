@extends('be.layouts.main')

@section('header_title', 'Tambah Personel')

@section('content')

    <div class="hud-panel" style="max-width: 600px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">Data Personel</h3>

        <form action="{{ route('be.users.store') }}" method="POST">
            @csrf

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama Lengkap</label><br>
                <input type="text" name="name" value="{{ old('name') }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Email / User ID</label><br>
                <input type="email" name="email" value="{{ old('email') }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Nomor Telepon</label><br>
                <input type="text" name="phone" value="{{ old('phone') }}" style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Password</label><br>
                <input type="password" name="password" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">Role</label><br>
                    <select name="role" required style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                        @if(auth()->user()->role == 'admin')
                            <option value="manager">MANAGER</option>
                        @else
                            <option value="cashier">CASHIER</option>
                            <option value="courier">COURIER</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">Hub</label><br>
                    @if(auth()->user()->role == 'manager')
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                        <input type="text" disabled value="{{ $branches->first()->name ?? '-' }}" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui); background: transparent; color: var(--color-gray); border: 1px solid var(--color-panel-border);">
                    @else
                        <select name="branch_id" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                            <option value="">Tanpa hub</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (isset($selectedBranchId) && $selectedBranchId == $branch->id) ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            @if(auth()->user()->role == 'manager')
                <div class="form-cluster form-cluster--accent" style="margin-bottom: 3rem;">
                    <div class="data-label">Kendaraan Kurir</div>
                    <div class="font-ui text-gray" style="font-size: 0.78rem; margin-bottom: 1rem;">Wajib diisi kalau role yang dipilih adalah courier.</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Plat Nomor</label>
                            <input type="text" name="vehicle_plate_number" value="{{ old('vehicle_plate_number') }}" placeholder="B 1234 SPL" style="width: 100%; padding: 0.55rem;">
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Jenis</label>
                            <select name="vehicle_type" style="width: 100%; padding: 0.55rem;">
                                <option value="motor" @selected(old('vehicle_type') === 'motor')>MOTOR</option>
                                <option value="mobil" @selected(old('vehicle_type') === 'mobil')>MOBIL</option>
                                <option value="truck" @selected(old('vehicle_type', 'truck') === 'truck')>TRUCK</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Kapasitas KG</label>
                            <input type="number" name="vehicle_capacity_kg" value="{{ old('vehicle_capacity_kg', 1200) }}" min="0.1" step="0.1" style="width: 100%; padding: 0.55rem;">
                        </div>
                        <div>
                            <label class="font-ui text-gray" style="font-size: 0.8rem;">Kapasitas Paket</label>
                            <input type="number" name="vehicle_capacity_packages" value="{{ old('vehicle_capacity_packages', 180) }}" min="1" step="1" style="width: 100%; padding: 0.55rem;">
                        </div>
                    </div>
                </div>
            @endif

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.users.index') }}" class="btn-neon" style="color: var(--color-gray); border-color: var(--color-gray);">Batal</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px;">Simpan Personel</button>
            </div>

        </form>
    </div>

@endsection
