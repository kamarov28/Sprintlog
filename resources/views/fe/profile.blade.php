@extends('fe.layouts.main')

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
<div style="margin-top: 5rem;">
    <x-fe.page-header title="Profil" subtitle="Kelola identitas, alamat pickup, dan keamanan akun.">
        <x-fe.button href="{{ route('dashboard') }}" variant="secondary" style="font-size: 0.8rem;">Dashboard</x-fe.button>
    </x-fe.page-header>

    @if(session('success'))
        <x-fe.alert tone="success" title="Berhasil">{{ session('success') }}</x-fe.alert>
    @endif

    <div class="grid-2 section-animate" style="gap: 4rem; align-items: flex-start;">
        <!-- Profile Info -->
        <x-fe.panel title="Data Profil" variant="primary">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
            <div class="form-cluster" style="display: flex; gap: 2rem; align-items: center; margin-bottom: 3rem;">
                    <div id="photo-preview-container">
                        @if($user->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" id="photo-preview" style="width: 100px; height: 100px; border: 2px solid var(--color-primary); object-fit: cover;">
                        @else
                            <div id="photo-placeholder" style="width: 100px; height: 100px; border: 2px dashed var(--color-gray); display: flex; align-items: center; justify-content: center; color: var(--color-gray); font-size: 0.72rem; text-align: center; padding: 5px;">Belum ada foto</div>
                            <img src="" id="photo-preview" style="width: 100px; height: 100px; border: 2px solid var(--color-primary); object-fit: cover; display: none;">
                        @endif
                    </div>
                    <div>
                        <input type="file" name="photo" id="photo" style="display: none;" onchange="previewImage(this)">
                        <x-fe.button type="button" onclick="document.getElementById('photo').click()" variant="secondary" style="font-size: 0.7rem;">Pilih Foto</x-fe.button>
                        @error('photo') <br><small class="text-accent" style="font-weight: bold;">{{ $message }}</small> @enderror
                    </div>
                </div>

                <x-fe.input label="Nama Lengkap" name="name" value="{{ $user->name }}" required />
                <x-fe.input label="Nomor Telepon" name="phone" value="{{ $user->phone }}" required />

                <x-fe.input type="select" label="Provinsi" name="province_id" id="province_id" onchange="loadKota()" required>
                    <option value="" disabled {{ !$user->province_id ? 'selected' : '' }}>Pilih provinsi...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ $user->province_id == $prov->id ? 'selected' : '' }}>{{ strtoupper($prov->name) }}</option>
                    @endforeach
                </x-fe.input>

                <x-fe.input type="select" label="Kota/Kabupaten" name="city" id="city_id" required>
                    <option value="{{ $user->city }}" selected>{{ $user->city ?: 'Pilih provinsi dahulu...' }}</option>
                </x-fe.input>

                <div style="margin-top: 1.5rem;">
                    <div style="display: flex; gap: 1rem; align-items: flex-end;">
                        <x-fe.input type="textarea" label="Alamat Utama" name="address" id="address" rows="2" class="mb-0" style="flex-grow: 1;">{{ $user->address }}</x-fe.input>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <x-fe.button type="button" onclick="toggleMap()" variant="secondary" style="font-size: 0.7rem; white-space: nowrap;">Pilih di Map</x-fe.button>
                            <x-fe.button type="button" onclick="senseLocation()" variant="primary" style="font-size: 0.7rem; white-space: nowrap;">Pakai Lokasi</x-fe.button>
                        </div>
                    </div>
                </div>

                <!-- Coordinate Hidden Inputs -->
                <input type="hidden" name="latitude" id="lat" value="{{ $user->latitude }}">
                <input type="hidden" name="longitude" id="lng" value="{{ $user->longitude }}">

                <!-- Map Container -->
                <div id="map-container" style="display: none; margin: 2rem 0; border: 2px solid var(--color-text-main); padding: 5px; background: var(--color-bg);">
                    <div id="map" style="height: 300px; background: var(--color-bg);"></div>
                    <p class="text-gray" style="font-size: 0.7rem; margin-top: 0.5rem; font-weight: bold;">Klik map atau geser marker untuk menyesuaikan titik.</p>
                </div>

                <x-fe.button type="submit" variant="primary" style="width: 100%; font-weight: 900; margin-top: 2rem;">Simpan Profil</x-fe.button>
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

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                .then(r => r.json())
                .then(data => {
                    if (data.display_name) {
                        addressInput.value = data.display_name;
                    } else {
                        addressInput.value = oldVal;
                    }
                })
                .catch(() => {
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

        function senseLocation() {
            if (!navigator.geolocation) {
                alert("Browser belum mendukung geolocation.");
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
                    alert("Gagal membaca lokasi: " + err.message);
                    btn.textContent = oldText;
                    btn.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        }

        function loadKota() {
            const provId = document.getElementById('province_id').value;
            const kotaSelect = document.getElementById('city_id');
            const provText = document.getElementById('province_id').options[document.getElementById('province_id').selectedIndex].text;
            
            if (!provId) return;

            kotaSelect.innerHTML = '<option value="">Loading...</option>';

            fetch(`/api/locations/kota?provinsi_id=${provId}`)
                .then(r => r.json())
                .then(kota => {
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
        </script>
        @endpush

        <!-- Security Access -->
        <x-fe.panel title="Keamanan Akun" variant="accent">
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                <x-fe.input type="password" label="Password Lama" name="current_password" required />
                <x-fe.input type="password" label="Password Baru" name="password" required />
                <x-fe.input type="password" label="Konfirmasi Password Baru" name="password_confirmation" required />

                <x-fe.button type="submit" variant="secondary" style="width: 100%; margin-top: 1rem;">Update Password</x-fe.button>
            </form>
        </x-fe.panel>
    </div>
</div>
@endsection
