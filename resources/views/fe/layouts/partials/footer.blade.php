<footer id="site-footer" class="relative z-10 w-full mt-20 px-4 pb-8">
    <div class="max-w-7xl mx-auto glass-panel rounded-3xl border-white/5 backdrop-blur-md p-8 md:p-14">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
            <!-- Brand Section -->
            <div class="flex flex-col gap-4">
                <div class="font-unbounded font-black text-2xl tracking-tight text-slate-100">
                    SPRINT<span class="text-primary">LOG</span>
                </div>
                <p class="text-slate-400 text-xs md:text-sm leading-relaxed font-light">
                    &copy; {{ date('Y') }} SprintLog.<br>
                    Platform logistik lokal modern dengan otomatisasi pickup instan & live tracking GPS.
                </p>
                <div class="flex gap-2.5 mt-2 font-alt">
                    <div class="badge badge-outline border-primary/30 text-primary text-[10px] font-bold px-3 py-2.5 flex items-center gap-1.5"><i class="bi bi-shield-check"></i> SECURE SSL</div>
                    <div class="badge badge-outline border-slate-700 text-slate-300 text-[10px] font-bold px-3 py-2.5 flex items-center gap-1.5"><i class="bi bi-award"></i> ISO 9001</div>
                </div>
            </div>

            <!-- Services Section -->
            <div class="font-alt">
                <h3 class="font-bold text-slate-200 mb-4 tracking-wider uppercase text-xs">Layanan</h3>
                <ul class="flex flex-col gap-2.5 text-slate-400 text-xs font-semibold">
                    <li><a href="{{ route('order.create', ['service_type' => 'REGULAR']) }}" class="hover:text-primary transition-colors flex items-center gap-2"><i class="bi bi-box-seam text-slate-500"></i> Regular (2-4 Hari)</a></li>
                    <li><a href="{{ route('order.create', ['service_type' => 'BEST']) }}" class="hover:text-primary transition-colors flex items-center gap-2"><i class="bi bi-lightning-charge text-slate-500"></i> BEST Priority (1 Hari)</a></li>
                    <li><a href="{{ route('order.create', ['service_type' => 'KARGO']) }}" class="hover:text-primary transition-colors flex items-center gap-2"><i class="bi bi-truck text-slate-500"></i> Kargo (10kg+)</a></li>
                </ul>
            </div>

            <!-- Accounts Section -->
            <div class="font-alt">
                <h3 class="font-bold text-slate-200 mb-4 tracking-wider uppercase text-xs">Akun & Navigasi</h3>
                <ul class="flex flex-col gap-2.5 text-slate-400 text-xs font-semibold">
                    <li><a href="{{ route('track.show') }}" class="hover:text-primary transition-colors flex items-center gap-2"><i class="bi bi-search text-slate-500"></i> Cek Resi</a></li>
                    <li><a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors flex items-center gap-2"><i class="bi bi-grid-fill text-slate-500"></i> Dashboard Paket</a></li>
                    <li><a href="{{ route('profile') }}" class="hover:text-primary transition-colors flex items-center gap-2"><i class="bi bi-person-circle text-slate-500"></i> Profil Saya</a></li>
                </ul>
            </div>

            <!-- Comms/Contact Section -->
            <div class="flex flex-col gap-2 font-alt">
                <h3 class="font-bold text-slate-200 mb-4 tracking-wider uppercase text-xs">Kontak Hub</h3>
                <p class="text-primary font-bold text-base flex items-center gap-2 font-unbounded text-sm"><i class="bi bi-telephone-fill text-sm"></i> +62 21-SPRINT-01</p>
                <p class="text-slate-400 text-xs leading-relaxed font-light mt-1 flex items-start gap-2">
                    <i class="bi bi-geo-alt-fill text-primary shrink-0 mt-0.5"></i>
                    <span>Menara 2 BTN, Lt. 12<br>Jakarta Pusat, 10110</span>
                </p>
            </div>
        </div>
    </div>
</footer>
