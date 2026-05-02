@extends('be.layouts.main')

@section('header_title', 'MODIFY PERSONNEL: ' . strtoupper($user->name))

@section('content')

    <div class="hud-panel" style="max-width: 600px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">PERSONNEL CONFIGURATION UPDATE</h3>

        <form action="{{ route('be.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">FULL NAME</label><br>
                <input type="text" name="name" value="{{ $user->name }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">EMAIL PROTOCOL / USER ID</label><br>
                <input type="email" name="email" value="{{ $user->email }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">ACCESS_KEY (REAVE BLANK TO UNCHANGED)</label><br>
                <input type="password" name="password" style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                <div>
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">ASSIGNED_ROLE</label><br>
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
                    <label class="font-ui text-gray" style="font-size: 0.8rem;">ASSIGNED_HUB</label><br>
                    @if(auth()->user()->role == 'manager')
                        <input type="hidden" name="branch_id" value="{{ $user->branch_id }}">
                        <input type="text" disabled value="{{ $branches->first()->name ?? '-' }}" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui); background: transparent; color: var(--color-gray); border: 1px solid var(--color-panel-border);">
                    @else
                        <select name="branch_id" style="width: 100%; padding: 0.5rem; font-family: var(--font-ui);">
                            <option value="">NO HUB ASSIGNMENT</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $user->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->city }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.users.index') }}" class="btn-neon" style="color: var(--color-gray); border-color: var(--color-gray);">ABORT</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px;">UPDATE PERSONNEL</button>
            </div>

        </form>
    </div>

@endsection
