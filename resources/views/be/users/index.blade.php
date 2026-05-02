@extends('be.layouts.main')

@section('header_title', 'PERSONNEL DIRECTORY')

@section('content')

    <x-be.panel class="hud-header">
        @if(auth()->user()->role == 'admin')
            <div class="font-ui text-gray">BRANCHES: {{ $branches->total() }} // SHOWING {{ $branches->firstItem() ?? 0 }}-{{ $branches->lastItem() ?? 0 }}</div>
            <a href="{{ route('be.users.create') }}" class="btn-neon" style="padding: 5px 20px;">RECRUIT NEW MANAGER</a>
        @else
            <div class="font-ui text-gray">
                @if(auth()->user()->role === 'cashier')
                    COURIER DIRECTORY: {{ $users->total() }}
                @else
                    ACTIVE PERSONNEL: {{ $users->total() }}
                @endif
            </div>
            @if(in_array(auth()->user()->role, ['admin', 'manager']))
                <a href="{{ route('be.users.create') }}" class="btn-neon" style="padding: 5px 20px;">RECRUIT NEW PERSONNEL</a>
            @endif
        @endif
    </x-be.panel>

    @if(auth()->user()->role == 'admin')
        <x-be.panel>
            <form method="GET" action="{{ route('be.users.index') }}">
                <x-be.form-grid min="180px">
                    <x-be.field label="SEARCH_HUB" for="personnel-search">
                        <input id="personnel-search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="hub atau kota...">
                    </x-be.field>
                <button type="submit" class="btn-neon" style="min-width: 128px;">FILTER</button>
                <a href="{{ route('be.users.index') }}" class="btn-neon" style="min-width: 128px; border-color: var(--color-gray); color: var(--color-gray); text-align: center;">RESET</a>
                </x-be.form-grid>
            </form>
        </x-be.panel>

        <x-be.panel class="personnel-panel">
            <x-be.table min-width="900px">
                <table class="personnel-table font-ui text-main">
                    <thead>
                        <tr>
                            <th>HUB</th>
                            <th>CURRENT MANAGER</th>
                            <th class="centered">ACTIONS</th>
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
                                    <div class="meta">UNASSIGNED</div>
                                @endif
                            </td>
                            <td class="centered">
                                <form method="POST" action="{{ route('be.branches.assign-manager', $branch) }}" class="assign-form">
                                    @csrf
                                    <select name="manager_id" class="small-select">
                                        <option value="">-- UNASSIGN --</option>
                                        @foreach($managers as $m)
                                            <option value="{{ $m->id }}" {{ ($branch->manager && $branch->manager->id == $m->id) ? 'selected' : '' }}>{{ $m->name }} - {{ $m->email }} {{ $m->branch ? ' ('. $m->branch->name .')' : '' }}</option>
                                        @endforeach
                                    </select>
                                    <div class="assign-actions">
                                        <button type="submit" class="btn-neon btn-assign">ASSIGN</button>
                                        <a href="{{ route('be.users.create') }}?branch_id={{ $branch->id }}" class="btn-neon">RECRUIT NEW</a>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$branches" label="PAGE {{ $branches->currentPage() }} / {{ $branches->lastPage() }} // {{ $branches->perPage() }} PER PAGE" />
        </x-be.panel>
    @else
        <x-be.panel>
            <x-be.table min-width="760px">
                <table class="personnel-table font-ui text-main">
                    <thead>
                        <tr>
                            <th>{{ auth()->user()->role === 'cashier' ? 'COURIER / CONTACT' : 'NAME / EMAIL' }}</th>
                            <th>ROLE</th>
                            <th>ASSIGNED HUB</th>
                            <th class="centered">ACTIONS</th>
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
                                    <div class="meta">UNASSIGNED</div>
                                @endif
                            </td>
                            <td class="centered">
                                @if(in_array(auth()->user()->role, ['admin', 'manager']))
                                    <a href="{{ route('be.users.edit', $user) }}" class="btn-neon">MODIFY</a>
                                @else
                                    <span class="text-gray" style="font-size: 0.7rem;">CONTACT ONLY</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="table-empty">NO PERSONNEL REGISTERED</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-be.table>

            <x-be.pagination :paginator="$users" />
        </x-be.panel>
    @endif

@endsection
