@extends('be.layouts.main')

@section('header_title', 'Tambah Hub Baru')

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')

    <div class="hud-panel" style="max-width: 600px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">Data Hub</h3>

        <form action="{{ route('be.branches.store') }}" method="POST">
            @csrf

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Nama Hub</label><br>
                <input type="text" name="name" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Provinsi</label><br>
                <select name="province_id" id="province_id" required onchange="loadKota('province_id', 'city_id')" style="width: 100%; padding: 0.5rem; background: transparent; border: none; border-bottom: 2px solid var(--color-gray); font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    <option value="" disabled selected>Pilih Provinsi...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Kota / Lokasi</label><br>
                <select name="city" id="city_id" required style="width: 100%; padding: 0.5rem; background: transparent; border: none; border-bottom: 2px solid var(--color-gray); font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    <option value="">Pilih Provinsi dahulu...</option>
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Telepon Hub</label><br>
                <input type="text" name="phone" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 3rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">Alamat Lengkap</label><br>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <textarea name="address" id="address" required rows="2" style="flex-grow: 1; border: none; border-bottom: 2px solid var(--color-primary); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none; resize: none;"></textarea>
                    <button type="button" onclick="toggleMap()" class="btn-neon" style="font-size: 0.7rem; white-space: nowrap;">Pilih di Map</button>
                </div>
            </div>

            <!-- Coordinate Hidden Inputs -->
            <input type="hidden" name="latitude" id="lat">
            <input type="hidden" name="longitude" id="lng">

            <!-- Map Container -->
            <div id="map-container" style="display: none; margin-bottom: 3rem;">
                <div id="map" style="height: 300px; border: 1px solid var(--color-panel-border); background: var(--color-bg);"></div>
                <p class="font-ui text-gray" style="font-size: 0.7rem; margin-top: 0.5rem;">Klik map untuk set koordinat, lalu geser marker kalau perlu.</p>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.branches.index') }}" class="btn-neon" style="color: var(--color-gray); border-color: var(--color-gray);">Batal</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px;">Simpan Hub</button>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
let map, marker;
const defaultLat = -6.2088; // Jakarta
const defaultLng = 106.8456;

function initMap() {
    if (map) return;
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });

    // Try GeoLocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            map.setView([latitude, longitude], 15);
            setMarker(latitude, longitude);
        });
    }
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
    addressInput.value = "Mencari alamat dari titik map...";

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

function loadKota(provSelectId, kotaSelectId, selectedName) {
    const provId = document.getElementById(provSelectId).value;
    const kotaSelect = document.getElementById(kotaSelectId);
    const provText = document.getElementById(provSelectId).options[document.getElementById(provSelectId).selectedIndex].text;
    
    if (!provId) {
        kotaSelect.innerHTML = '<option value="">Pilih Provinsi dahulu...</option>';
        return;
    }

    kotaSelect.innerHTML = '<option value="">Loading...</option>';

    fetch(`/api/locations/kota?provinsi_id=${provId}`)
        .then(r => r.json())
        .then(kota => {
            kotaSelect.innerHTML = '<option value="" disabled selected>Pilih Kota/Kabupaten...</option>';
            kota.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.name; // Use name as value for branch table schema
                opt.textContent = k.name;
                if (selectedName && k.name === selectedName) opt.selected = true;
                kotaSelect.appendChild(opt);
            });
            
            // Re-centering map based on province/city name if map is active
            if (map) {
                const query = `${kotaSelect.value || ''} ${provText} Indonesia`.trim();
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length > 0) {
                            map.setView([data[0].lat, data[0].lon], 12);
                        }
                    });
            }
        })
        .catch(() => {
            kotaSelect.innerHTML = '<option value="">Gagal memuat kota.</option>';
        });
}

// Ensure map updates when city changes
document.getElementById('city_id').addEventListener('change', function() {
    if (map) {
        const cityText = this.value;
        const provText = document.getElementById('province_id').options[document.getElementById('province_id').selectedIndex].text;
        const query = `${cityText}, ${provText}, Indonesia`;
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
            .then(r => r.json())
            .then(data => {
                if (data.length > 0) {
                    map.setView([data[0].lat, data[0].lon], 13);
                    setMarker(data[0].lat, data[0].lon);
                }
            });
    }
});
</script>
@endpush
