@extends('be.layouts.main')

@section('header_title', 'Direktori Personel')

@section('content')

    <x-be.panel class="hud-header">
        @if(auth()->user()->role == 'admin')
            <div class="font-ui text-gray">Hub: {{ $branches->total() }} | tampil {{ $branches->firstItem() ?? 0 }}-{{ $branches->lastItem() ?? 0 }}</div>
            <a href="{{ route('be.users.create') }}" class="btn-neon" style="padding: 5px 20px;">Tambah Manager</a>
        @else
            <div class="font-ui text-gray">
                @if(auth()->user()->role === 'cashier')
                    Direktori kurir: {{ $users->total() }}
                @else
                    Personel aktif: {{ $users->total() }}
                @endif
            </div>
            @if(in_array(auth()->user()->role, ['admin', 'manager']))
                <a href="{{ route('be.users.create') }}" class="btn-neon" style="padding: 5px 20px;">Tambah Personel</a>
            @endif
        @endif
    </x-be.panel>

    @if(auth()->user()->role == 'admin')
        <x-be.panel>
            <form method="GET" action="{{ route('be.users.index') }}">
                <x-be.form-grid min="180px">
                    <x-be.field label="Cari Hub" for="personnel-search">
                        <input id="personnel-search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="hub atau kota...">
                    </x-be.field>
                <button type="submit" class="btn-neon" style="min-width: 128px;">Filter</button>
                <a href="{{ route('be.users.index') }}" class="btn-neon" style="min-width: 128px; border-color: var(--color-gray); color: var(--color-gray); text-align: center;">Reset</a>
                </x-be.form-grid>
            </form>
        </x-be.panel>

        <x-be.panel class="personnel-panel">
            <x-be.table min-width="900px">
                <table class="personnel-table font-ui text-main">
                    <thead>
                        <tr>
                            <th>HUB</th>
                            <th>Manager Saat Ini</th>
                            <th class="centered">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $branch)
                        <tr class="personnel-row">
                            <td>
                                <div class="name">{{ $branch->name }}</div>
                                <div class="meta">{{ $branch->city }}</div>
                            </td>
                            <td>
                                @if($branch->manager)
                                    <div style="font-weight: bold;">{{ strtoupper($branch->manager->name) }}</div>
                                    <div class="meta">{{ $branch->manager->email }}</div>
                                @else
                                    <div class="meta">Belum ditugaskan</div>
                                @endif
                            </td>
                            <td class="centered">
                                <form method="POST" action="{{ route('be.branches.assign-manager', $branch) }}" class="assign-form">
                                    @csrf
                                    <select name="manager_id" class="small-select">
                                        <option value="">-- Lepas manager --</option>
                                        @foreach($managers as $m)
                                            <option value="{{ $m->id }}" {{ ($branch->manager && $branch->manager->id == $m->id) ? 'selected' : '' }}>{{ $m->name }} - {{ $m->email }} {{ $m->branch ? ' ('. $m->branch->name .')' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <div class="assign-actions">
                                        <button type="submit" class="btn-neon btn-assign">Assign</button>
                                        <a href="{{ route('be.users.create') }}?branch_id={{ $branch->id }}" class="btn-neon">Tambah Baru</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$branches" label="Halaman {{ $branches->currentPage() }} / {{ $branches->lastPage() }} | {{ $branches->perPage() }} per halaman" />
        </x-be.panel>
    @else
        <x-be.panel>
            <x-be.table min-width="760px">
                <table class="personnel-table font-ui text-main">
                    <thead>
                        <tr>
                            <th>{{ auth()->user()->role === 'cashier' ? 'Kurir / Kontak' : 'Nama / Email' }}</th>
                            <th>ROLE</th>
                            <th>Hub</th>
                            <th class="centered">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="name">{{ strtoupper($user->name) }}</div>
                                <div class="meta">{{ $user->email }}</div>
                            </td>
                            <td>
                                @php
                                    $rColors = ['admin' => 'role-admin', 'manager' => 'role-manager', 'cashier' => 'role-cashier', 'courier' => 'role-courier'];
                                    $rClass = $rColors[$user->role] ?? '';
                                @endphp
                                <span class="role-badge {{ $rClass }}">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td>
                                @if($user->branch)
                                    <div class="meta">{{ $user->branch->name }} ({{ $user->branch->city }})</div>
                                @else
                                    <div class="meta">Belum ditugaskan</div>
                                @endif
                            </td>
                            <td class="centered">
                                @if(in_array(auth()->user()->role, ['admin', 'manager']))
                                    <a href="{{ route('be.users.edit', $user) }}" class="btn-neon">Edit</a>
                                @else
                                    <span class="text-gray" style="font-size: 0.7rem;">Kontak saja</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="table-empty">Belum ada personel</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$users" />
        </x-be.panel>
    @endif

@endsection
