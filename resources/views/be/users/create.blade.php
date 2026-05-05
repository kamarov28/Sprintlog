@extends('be.layouts.main')

@section('header_title', 'Tambah Personel')

@section('content')

    <div class="hud-panel" style="max-width: 600px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">Data Personel</h3>

        <form action="{{ route('be.users.store') }}" method="POST">
            @csrf

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama Lengkap</label><br>
                <input type="text" name="name" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Email / User ID</label><br>
                <input type="email" name="email" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
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

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.users.index') }}" class="btn-neon" style="color: var(--color-gray); border-color: var(--color-gray);">Batal</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px;">Simpan Personel</button>
            </div>

        </form>
    </div>

@endsection
