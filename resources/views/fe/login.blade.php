@extends('fe.layouts.main')

@section('title', 'SprintLog | Secure Access')
@section('body_class', 'auth-page')

@section('content')

    <div class="auth-shell">
        <div class="auth-card">
            <x-fe.panel title="Login" subtitle="Masuk ke akun SprintLog" variant="accent" headerClass="text-center">

                @if ($errors->any())
                    <x-fe.alert tone="danger" title="Login gagal">
                        Email atau password belum cocok.
                    </x-fe.alert>
                @endif

                <form action="/login" method="POST">
                    @csrf
                    <x-fe.input type="email" label="Email" name="email" placeholder="nama@email.com" required />
                    <x-fe.input type="password" label="Password" name="password" placeholder="********" required />

                    <x-fe.button type="submit" variant="primary" style="width: 100%; margin-top: 1rem;">Login</x-fe.button>
                </form>

                <div style="text-align: center; margin-top: 2rem;">
                    <a href="/" style="color: var(--color-gray); text-decoration: none; font-size: 0.8rem;">Kembali ke beranda</a>
                </div>
            </x-fe.panel>
        </div>
    </div>

@endsection
