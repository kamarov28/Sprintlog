@extends('fe.layouts.main')

@section('body_class', 'auth-page')

@section('content')
    <div class="auth-shell">
        <div class="auth-card">
            <x-fe.panel title="Daftar Akun" subtitle="Buat akun SprintLog baru" variant="primary">
                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <x-fe.input label="Nama Lengkap" name="name" required placeholder="Nama kamu" />
                    @error('name') <small class="text-accent" style="font-weight: bold;">{{ $message }}</small> @enderror

                    <x-fe.input type="email" label="Email" name="email" required placeholder="nama@email.com" />
                    @error('email') <small class="text-accent" style="font-weight: bold;">{{ $message }}</small> @enderror

                    <x-fe.input label="Nomor Telepon" name="phone" required placeholder="+62 8..." />
                    @error('phone') <small class="text-accent" style="font-weight: bold;">{{ $message }}</small> @enderror

                    <x-fe.input type="password" label="Password" name="password" required />
                    @error('password') <small class="text-accent" style="font-weight: bold;">{{ $message }}</small> @enderror

                    <x-fe.input type="password" label="Konfirmasi Password" name="password_confirmation" required />

                    <x-fe.button type="submit" variant="primary" style="width: 100%; font-weight: 900; margin-top: 2rem;">Daftar</x-fe.button>
                    
                    <p class="text-main mt-4" style="font-size: 0.85rem; text-align: center; font-weight: bold;">
                        Sudah punya akun? <a href="{{ route('login') }}" class="text-accent" style="text-decoration: underline;">Login</a>
                    </p>
                </form>
            </x-fe.panel>
        </div>
    </div>
@endsection
