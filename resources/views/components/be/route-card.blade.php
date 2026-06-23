@props(['estimate'])

@php
    $mapId = 'route_map_' . substr(md5(json_encode($estimate)), 0, 10);
    $points = collect($estimate['points'] ?? [])->values();
@endphp

@once
    @push('scripts')
        <script>
            window.sprintLogLoadLeaflet = window.sprintLogLoadLeaflet || (() => {
                let loader;

                return () => {
                    if (window.L) {
                        return Promise.resolve(window.L);
                    }

                    if (loader) {
                        return loader;
                    }

                    loader = new Promise((resolve, reject) => {
                        if (!document.getElementById('leaflet-css')) {
                            const css = document.createElement('link');
                            css.id = 'leaflet-css';
                            css.rel = 'stylesheet';
                            css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                            css.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                            css.crossOrigin = '';
                            document.head.appendChild(css);
                        }

                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                        script.crossOrigin = '';
                        script.onload = () => resolve(window.L);
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });

                    return loader;
                };
            })();
        </script>
    @endpush
@endonce

<div class="hud-panel mb-4" style="border-color: var(--color-primary);">
    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: start; margin-bottom: 1rem;">
        <div>
            <div class="font-ui text-gray" style="font-size: 0.76rem;">COURIER_ROUTE</div>
            <h3 class="font-bank text-primary" style="font-size: 1.15rem; margin: 0.35rem 0 0;">{{ $estimate['label'] ?? 'Route Estimate' }}</h3>
        </div>
        @if($estimate['available'] ?? false)
            <a href="{{ $estimate['google_url'] }}" target="_blank" rel="noopener" class="btn-neon" style="text-decoration: none; border-color: #4285F4; color: #4285F4;">GOOGLE MAPS</a>
        @endif
    </div>

    @if(!($estimate['available'] ?? false))
        <div class="inline-alert">
            <div class="font-ui text-gray" style="font-size: 0.82rem;">{{ $estimate['reason'] ?? 'Koordinat rute belum lengkap.' }}</div>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
            <div class="ops-card">
                <div class="data-label">DISTANCE_EST</div>
                <div class="font-bank text-main" style="font-size: 1.05rem;">{{ number_format($estimate['distance_km'], 1, ',', '.') }} KM</div>
            </div>
            <div class="ops-card">
                <div class="data-label">ETA_EST</div>
                <div class="font-bank text-accent" style="font-size: 1.05rem;">{{ strtoupper($estimate['duration_label']) }}</div>
            </div>
            <div class="ops-card">
                <div class="data-label">ROUTING</div>
                <div class="font-bank text-primary" style="font-size: 1.05rem;">{{ strtoupper($estimate['provider'] ?? 'fallback') }}</div>
            </div>
        </div>

        <button type="button" class="btn-neon" data-route-map-trigger="{{ $mapId }}" style="margin-bottom: 1rem;">SHOW MAP</button>
        <div id="{{ $mapId }}" style="display: none; height: 260px; border: 1px solid var(--color-panel-border); border-radius: var(--radius-sm); overflow: hidden; background: var(--color-bg);"></div>

        <div style="display: grid; gap: 0.65rem; margin-top: 1rem;">
            @if($estimate['uses_current_location_origin'] ?? false)
                <div style="display: grid; grid-template-columns: 34px minmax(0, 1fr); gap: 0.75rem; align-items: start;">
                    <div class="font-bank text-accent" style="border: 1px solid var(--color-accent); border-radius: 999px; width: 30px; height: 30px; display: grid; place-items: center;">GPS</div>
                    <div>
                        <div class="font-ui text-main" style="font-weight: bold;">Posisi kurir sekarang</div>
                        <div class="font-ui text-gray" style="font-size: 0.76rem; line-height: 1.5;">Dipakai oleh Google Maps sebagai titik awal saat tombol dibuka.</div>
                    </div>
                </div>
            @endif
            @foreach($points as $point)
                <div style="display: grid; grid-template-columns: 34px minmax(0, 1fr); gap: 0.75rem; align-items: start;">
                    <div class="font-bank text-primary" style="border: 1px solid var(--color-primary); border-radius: 999px; width: 30px; height: 30px; display: grid; place-items: center;">{{ $loop->iteration }}</div>
                    <div>
                        <div class="font-ui text-main" style="font-weight: bold;">{{ $point['label'] }}</div>
                        <div class="font-ui text-gray" style="font-size: 0.76rem; line-height: 1.5;">{{ $point['address'] ?: number_format($point['lat'], 5).', '.number_format($point['lng'], 5) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="font-ui text-gray" style="font-size: 0.72rem; line-height: 1.5; margin-top: 1rem;">
            {{ $estimate['note'] }}
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const trigger = document.querySelector('[data-route-map-trigger="{{ $mapId }}"]');
                    const mapEl = document.getElementById('{{ $mapId }}');

                    if (!trigger || !mapEl) {
                        return;
                    }

                    trigger.addEventListener('click', async () => {
                        if (window.__{{ $mapId }}Booted) {
                            mapEl.style.display = 'block';
                            return;
                        }

                        trigger.disabled = true;
                        trigger.textContent = 'Loading map';

                        try {
                            await window.sprintLogLoadLeaflet();
                        } catch (error) {
                            trigger.textContent = 'Map failed';
                            trigger.disabled = false;
                            return;
                        }

                        window.__{{ $mapId }}Booted = true;
                        mapEl.style.display = 'block';
                        const points = @json($points);
                        const latLngs = points.map(point => [Number(point.lat), Number(point.lng)]);
                        const map = L.map('{{ $mapId }}', {
                            scrollWheelZoom: false,
                        });

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap',
                        }).addTo(map);

                        L.polyline(latLngs, {
                            color: '#4a00e5',
                            weight: 4,
                            opacity: 0.88,
                        }).addTo(map);

                        points.forEach((point, index) => {
                            L.marker([Number(point.lat), Number(point.lng)])
                                .addTo(map)
                                .bindPopup(`${index + 1}. ${point.label}`);
                        });

                        map.fitBounds(latLngs, { padding: [24, 24] });
                        setTimeout(() => map.invalidateSize(), 60);
                        trigger.textContent = 'Map ready';
                    });
                });
            </script>
        @endpush
    @endif
</div>
