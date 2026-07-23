@extends('fe.layouts.main')

@section('title', 'SprintLog | Register Account')

@section('content')
    <div class="flex items-center justify-center min-h-[calc(100vh-250px)] py-12 px-4">
        <div class="w-full max-w-md">
            <x-fe.panel title="Daftar Akun Baru" subtitle="Buat akun SprintLog Anda sekarang" variant="primary">
                <form action="{{ route('register') }}" method="POST" class="space-y-3">
                    @csrf
                    
                    <x-fe.input label="Nama Lengkap" name="name" required placeholder="Nama lengkap Anda" value="{{ old('name') }}" />
                    @error('name') <span class="text-xs text-red-400 font-bold block -mt-2">{{ $message }}</span> @enderror

                    <x-fe.input type="email" label="Email" name="email" required placeholder="nama@email.com" value="{{ old('email') }}" />
                    @error('email') <span class="text-xs text-red-400 font-bold block -mt-2">{{ $message }}</span> @enderror

                    <x-fe.input label="Nomor Telepon" name="phone" id="phone_input" required placeholder="+62 8..." value="{{ old('phone') }}" />
                    @error('phone') <span class="text-xs text-red-400 font-bold block -mt-2">{{ $message }}</span> @enderror

                    <x-fe.input type="password" label="Password" name="password" id="password_input" required />
                    @error('password') <span class="text-xs text-red-400 font-bold block -mt-2">{{ $message }}</span> @enderror

                    <!-- Password Strength Meter -->
                    <div class="mb-4">
                        <div class="flex gap-1 h-1.5 w-full bg-slate-900 rounded-full overflow-hidden mt-1.5">
                            <div id="strength-bar-1" class="h-full w-1/3 bg-slate-800 transition-colors duration-300"></div>
                            <div id="strength-bar-2" class="h-full w-1/3 bg-slate-800 transition-colors duration-300"></div>
                            <div id="strength-bar-3" class="h-full w-1/3 bg-slate-800 transition-colors duration-300"></div>
                        </div>
                        <span id="strength-text" class="text-[9px] text-slate-500 font-semibold mt-1 block">Masukkan password minimal 8 karakter</span>
                    </div>

                    <x-fe.input type="password" label="Konfirmasi Password" name="password_confirmation" required />

                    <x-fe.button type="submit" variant="primary" class="w-full mt-6 py-3 font-bold uppercase tracking-wider text-xs flex items-center justify-center gap-2">
                        <i class="bi bi-person-plus-fill"></i> Buat Akun Sekarang
                    </x-fe.button>
                    
                    <p class="text-slate-400 mt-6 text-center text-xs font-medium">
                        Sudah punya akun? <a href="{{ route('login') }}" class="text-primary hover:underline font-bold">Login</a>
                    </p>
                </form>
            </x-fe.panel>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Phone number auto format
    const phoneInput = document.getElementById('phone_input');
    if (phoneInput) {
        phoneInput.addEventListener('input', (e) => {
            let val = e.target.value;
            if (val.startsWith('08')) {
                val = '+62 8' + val.substring(2);
            }
            e.target.value = val.replace(/[^\d\s\+\-]/g, '');
        });
    }

    // 2. Password strength meter
    const passwordInput = document.getElementById('password_input');
    if (passwordInput) {
        passwordInput.addEventListener('input', (e) => {
            const password = e.target.value;
            const bar1 = document.getElementById('strength-bar-1');
            const bar2 = document.getElementById('strength-bar-2');
            const bar3 = document.getElementById('strength-bar-3');
            const text = document.getElementById('strength-text');

            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password) || /[^A-Za-z0-9]/.test(password)) strength++;

            // Reset classes
            bar1.className = 'h-full w-1/3 transition-colors duration-300 ' + (password.length > 0 ? (strength === 1 ? 'bg-red-500' : (strength === 2 ? 'bg-yellow-500' : 'bg-green-500')) : 'bg-slate-800');
            bar2.className = 'h-full w-1/3 transition-colors duration-300 ' + (password.length > 0 && strength >= 2 ? (strength === 2 ? 'bg-yellow-500' : 'bg-green-500') : 'bg-slate-800');
            bar3.className = 'h-full w-1/3 transition-colors duration-300 ' + (password.length > 0 && strength === 3 ? 'bg-green-500' : 'bg-slate-800');

            if (password.length === 0) {
                text.textContent = 'Masukkan password minimal 8 karakter';
                text.className = 'text-[9px] text-slate-500 font-semibold mt-1 block';
            } else if (strength === 1) {
                text.textContent = 'Kekuatan: Lemah (Gunakan huruf besar/kecil & angka)';
                text.className = 'text-[9px] text-red-500 font-semibold mt-1 block';
            } else if (strength === 2) {
                text.textContent = 'Kekuatan: Sedang (Tambahkan angka atau karakter khusus)';
                text.className = 'text-[9px] text-yellow-500 font-semibold mt-1 block';
            } else {
                text.textContent = 'Kekuatan: Sangat Kuat!';
                text.className = 'text-[9px] text-green-500 font-semibold mt-1 block';
            }
        });
    }
});
</script>
@endpush
