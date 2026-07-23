@extends('fe.layouts.main')

@section('title', 'SprintLog | Secure Access')

@section('content')
    <div class="flex items-center justify-center min-h-[calc(100vh-250px)] py-12 px-4">
        <div class="w-full max-w-md">
            <x-fe.panel title="Login Akun" subtitle="Masuk ke dashboard SprintLog Anda" variant="primary" headerClass="text-center">
                @if ($errors->any())
                    <x-fe.alert tone="danger" title="Login gagal" class="mb-4">
                        Email atau password belum cocok.
                    </x-fe.alert>
                @endif

                <form action="/login" method="POST" class="space-y-4">
                    @csrf
                    <x-fe.input type="email" label="Email" name="email" placeholder="nama@email.com" required />
                    <x-fe.input type="password" label="Password" name="password" placeholder="********" required />

                    <x-fe.button type="submit" variant="primary" class="w-full mt-6 py-3 font-bold uppercase tracking-wider text-xs flex items-center justify-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk Sekarang
                    </x-fe.button>
                </form>

                <div class="text-center mt-6 font-alt space-y-2">
                    <p class="text-xs text-slate-400">
                        Belum punya akun? <a href="{{ route('register') }}" class="text-primary hover:underline font-bold">Daftar Akun Baru</a>
                    </p>
                    <div>
                        <a href="/" class="text-slate-500 hover:text-slate-300 transition-colors text-xs font-medium inline-flex items-center gap-1"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a>
                    </div>
                </div>
            </x-fe.panel>
        </div>
    </div>
@endsection
