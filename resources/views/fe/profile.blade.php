@extends('fe.layouts.main')

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preload" as="style" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    </noscript>
    <style>
        .loading-pulse {
            animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>
@endpush

@section('content')
<div class="space-y-8">
    <x-fe.page-header title="Profil" subtitle="Kelola identitas, alamat pickup, dan keamanan akun.">
        <x-fe.button href="{{ route('dashboard') }}" variant="outline" class="btn-sm font-bold">Dashboard</x-fe.button>
    </x-fe.page-header>

    @if(session('success'))
        <x-fe.alert tone="success" title="Berhasil">{{ session('success') }}</x-fe.alert>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
        <!-- Profile Info -->
        <x-fe.panel title="Data Profil" variant="primary" class="text-left">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="flex flex-col sm:flex-row items-center gap-6 mb-8 bg-slate-950/20 p-4 rounded-xl border border-slate-800/40">
                    <div id="photo-preview-container" class="shrink-0">
                        @if($user->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" id="photo-preview" class="w-24 h-24 rounded-full border-2 border-primary object-cover">
                        @else
                            <div id="photo-placeholder" class="w-24 h-24 rounded-full border-2 border-dashed border-slate-700 flex items-center justify-center text-slate-500 text-[10px] text-center p-3">Belum ada foto</div>
                            <img src="" id="photo-preview" class="w-24 h-24 rounded-full border-2 border-primary object-cover" style="display: none;">
                        @endif
                    </div>
                    <div class="text-center sm:text-left space-y-2">
                        <input type="file" name="photo" id="photo" class="hidden" onchange="previewImage(this)">
                        <x-fe.button type="button" onclick="document.getElementById('photo').click()" variant="outline" class="btn-xs py-2 px-3 font-semibold text-[10px]">Pilih Foto Baru</x-fe.button>
                        @error('photo') <br><span class="text-xs text-red-400 font-bold block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <x-fe.input label="Nama Lengkap" name="name" value="{{ $user->name }}" required />
                <x-fe.input label="Nomor Telepon" name="phone" id="phone_input" value="{{ $user->phone }}" required />

                <x-fe.input type="select" label="Provinsi" name="province_id" id="province_id" onchange="loadKota()" required>
                    <option value="" disabled {{ !$user->province_id ? 'selected' : '' }}>Pilih provinsi...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ $user->province_id == $prov->id ? 'selected' : '' }}>{{ strtoupper($prov->name) }}</option>
                    @endforeach
                </x-fe.input>

                <x-fe.input type="select" label="Kota/Kabupaten" name="city" id="city_id" required>
                    <option value="{{ $user->city }}" selected>{{ $user->city ?: 'Pilih provinsi dahulu...' }}</option>
                </x-fe.input>

                <div class="mt-4">
                    <x-fe.input type="textarea" label="Alamat Utama" name="address" id="address" rows="3" class="mb-2">{{ $user->address }}</x-fe.input>
                    
                    <!-- GPS Inline Status Alert -->
                    <div id="gps-status-alert" class="alert alert-error bg-error/10 border-error/20 text-red-400 rounded-xl p-3 text-xs font-semibold mb-2" style="display: none;">
                        <span id="gps-status-text"></span>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-2">
                        <x-fe.button type="button" onclick="toggleMap()" variant="outline" class="btn-xs py-1.5 px-3 font-semibold text-[10px]">Pilih di Map &rarr;</x-fe.button>
                        <x-fe.button type="button" onclick="senseLocation(event)" variant="primary" class="btn-xs py-1.5 px-3 font-semibold text-[10px]">Gunakan Lokasi Saat Ini</x-fe.button>
                    </div>
                </div>

                <!-- Coordinate Hidden Inputs -->
                <input type="hidden" name="latitude" id="lat" value="{{ $user->latitude }}">
                <input type="hidden" name="longitude" id="lng" value="{{ $user->longitude }}">

                <!-- Map Container -->
                <div id="map-container" class="glass-panel border-white/5 p-2 rounded-2xl shadow-xl mt-4" style="display: none;">
                    <div id="map" class="w-full rounded-xl overflow-hidden" style="height: 300px;"></div>
                    <p class="text-slate-400 text-[10px] mt-2 font-medium px-1">Klik map atau geser marker untuk menyesuaikan titik pickup.</p>
                </div>

                <x-fe.button type="submit" variant="primary" class="w-full mt-8 py-3.5 font-bold uppercase tracking-wider">Simpan Profil</x-fe.button>
            </form>
        </x-fe.panel>

        @push('scripts')
        <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
        let map, marker;
        const defaultLat = {{ $user->latitude ?: -6.2088 }};
        const defaultLng = {{ $user->longitude ?: 106.8456 }};

        function initMap() {
            if (map) return;
            
            map = L.map('map').setView([defaultLat, defaultLng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (defaultLat && defaultLng && {{ $user->latitude ? 'true' : 'false' }}) {
                setMarker(defaultLat, defaultLng);
            }

            map.on('click', function(e) {
                setMarker(e.latlng.lat, e.latlng.lng);
            });
        }

        function setMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function(e) {
                    const pos = marker.getLatLng();
                    updateInputs(pos.lat, pos.lng);
                });
            }
            updateInputs(lat, lng);
        }

        function updateInputs(lat, lng) {
            document.getElementById('lat').value = lat.toFixed(8);
            document.getElementById('lng').value = lng.toFixed(8);
            reverseGeocode(lat, lng);
        }

        function reverseGeocode(lat, lng) {
            const addressInput = document.getElementById('address');
            const oldVal = addressInput.value;
            addressInput.value = "Mencari alamat...";
            addressInput.classList.add('loading-pulse');
            addressInput.readOnly = true;

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => {
                    addressInput.classList.remove('loading-pulse');
                    addressInput.readOnly = false;
                    if (data.display_name) {
                        addressInput.value = data.display_name;
                    } else {
                        addressInput.value = oldVal;
                    }
                })
                .catch(() => {
                    addressInput.classList.remove('loading-pulse');
                    addressInput.readOnly = false;
                    addressInput.value = oldVal;
                });
        }

        function toggleMap() {
            const container = document.getElementById('map-container');
            if (container.style.display === 'none') {
                container.style.display = 'block';
                initMap();
                setTimeout(() => { map.invalidateSize(); }, 100);
            } else {
                container.style.display = 'none';
            }
        }

        function senseLocation(event) {
            const alertBox = document.getElementById('gps-status-alert');
            const alertText = document.getElementById('gps-status-text');
            
            if (alertBox) alertBox.style.display = 'none';

            if (!navigator.geolocation) {
                showGpsError("Browser Anda tidak mendukung deteksi lokasi otomatis.");
                return;
            }

            // Ensure map is visible
            const container = document.getElementById('map-container');
            if (container.style.display === 'none') toggleMap();

            const btn = event.currentTarget;
            const oldText = btn.textContent;
            btn.textContent = "Mencari...";
            btn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    setMarker(lat, lng);
                    map.setView([lat, lng], 16);
                    btn.textContent = oldText;
                    btn.disabled = false;
                },
                (err) => {
                    btn.textContent = oldText;
                    btn.disabled = false;
                    
                    let errorMsg = "Gagal membaca lokasi: " + err.message;
                    if (err.code === 1) {
                        errorMsg = "Izin lokasi ditolak. Aktifkan GPS pada browser Anda.";
                    } else if (err.code === 2) {
                        errorMsg = "Informasi lokasi tidak tersedia saat ini.";
                    } else if (err.code === 3) {
                        errorMsg = "Waktu pencarian lokasi habis. Silakan coba lagi.";
                    }
                    showGpsError(errorMsg);
                },
                { enableHighAccuracy: true, timeout: 7000, maximumAge: 0 }
            );
        }

        function showGpsError(message) {
            const alertBox = document.getElementById('gps-status-alert');
            const alertText = document.getElementById('gps-status-text');
            if (alertBox && alertText) {
                alertText.textContent = message;
                alertBox.style.display = 'flex';
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 5000);
            }
        }

        function loadKota() {
            const provId = document.getElementById('province_id').value;
            const kotaSelect = document.getElementById('city_id');
            const provText = document.getElementById('province_id').options[document.getElementById('province_id').selectedIndex].text;
            
            if (!provId) return;

            kotaSelect.innerHTML = '<option value="">Loading...</option>';
            kotaSelect.classList.add('loading-pulse');

            fetch(`/api/locations/kota?provinsi_id=${provId}`)
                .then(r => r.json())
                .then(kota => {
                    kotaSelect.classList.remove('loading-pulse');
                    kotaSelect.innerHTML = '<option value="" disabled selected>Pilih kota...</option>';
                    kota.forEach(k => {
                        const opt = document.createElement('option');
                        opt.value = k.name;
                        opt.textContent = k.name.toUpperCase();
                        kotaSelect.appendChild(opt);
                    });
                    
                    if (map) {
                        const query = `${kotaSelect.value || ''} ${provText} Indonesia`.trim();
                        recenterMap(query);
                    }
                })
                .catch(() => {
                    kotaSelect.classList.remove('loading-pulse');
                    kotaSelect.innerHTML = '<option value="">Gagal memuat kota.</option>';
                });
        }

        function recenterMap(query) {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(r => r.json())
                .then(data => {
                    if (data.length > 0) {
                        map.setView([data[0].lat, data[0].lon], 12);
                    }
                });
        }

        document.getElementById('city_id').addEventListener('change', function() {
            const cityText = this.value;
            const provText = document.getElementById('province_id').options[document.getElementById('province_id').selectedIndex].text;
            const query = `${cityText}, ${provText}, Indonesia`;
            recenterMap(query);
        });

        // Phone input auto formatter
        document.addEventListener('DOMContentLoaded', () => {
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
        });
        </script>
        @endpush

        <!-- Security Access -->
        <x-fe.panel title="Keamanan Akun" variant="accent" class="text-left">
            <form action="{{ route('profile.password') }}" method="POST" class="space-y-2">
                @csrf
                <x-fe.input type="password" label="Password Lama" name="current_password" required />
                <x-fe.input type="password" label="Password Baru" name="password" required />
                <x-fe.input type="password" label="Konfirmasi Password Baru" name="password_confirmation" required />

                <x-fe.button type="submit" variant="secondary" class="w-full mt-6 py-3.5 font-bold uppercase tracking-wider">Update Password</x-fe.button>
            </form>
        </x-fe.panel>
    </div>
</div>
@endsection
