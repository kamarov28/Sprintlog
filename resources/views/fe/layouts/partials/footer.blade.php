<footer id="site-footer" class="cyber-footer">
    <div class="cyber-container">
        <div class="footer-grid">
            <!-- Brand & Integrity Section -->
            <div class="footer-section">
                <div class="logo-text mb-4" style="font-size: 1.8rem;">SPRINT<span class="accent">LOG</span></div>
                <p class="text-gray mb-6" style="font-size: 0.8rem; line-height: 1.6;">
                    &copy; {{ date('Y') }} SprintLog.<br>
                    Pickup, ongkir, dan tracking paket.<br>
                    Dibuat ringan untuk pengiriman harian.
                </p>
                
                <div class="badge-row">
                    <div class="tech-badge" title="Keamanan transaksi">Secure SSL</div>
                    <div class="tech-badge" title="Standar operasional">ISO 9001</div>
                </div>
            </div>

            <!-- Operational Modes Section -->
            <div class="footer-section">
                <h4 class="text-primary mb-4">Layanan</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('order.create') }}">Regular</a></li>
                    <li><a href="{{ route('order.create') }}">Best Priority</a></li>
                    <li><a href="{{ route('order.create') }}">Kargo 10kg+</a></li>
                </ul>
            </div>

        <!-- Data section -->
            <div class="footer-section">
                <h4 class="text-accent mb-4">Akun</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('dashboard') }}">Paket Saya</a></li>
                    <li><a href="{{ route('profile') }}">Profil</a></li>
                    <li><a href="/">Beranda</a></li>
                </ul>
            </div>

            <!-- Comms Channel Section -->
            <div class="footer-section">
                <h4 class="text-main mb-4">Kontak</h4>
                <p class="text-gray mb-2" style="font-size: 0.8rem;">+62 21-SPRINT-01</p>
                <p class="text-gray" style="font-size: 0.8rem; line-height: 1.6;">
                    Kantor pusat:<br>
                    Menara 2 BTN, Lt. 12<br>
                    Jakarta Pusat, 10110
                </p>
            </div>
        </div>
    </div>
</footer>
