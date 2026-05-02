@extends('be.layouts.main')

@section('header_title', 'MODIFY HUB: ' . strtoupper($branch->name))

@push('head_assets')
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')

    <div class="hud-panel" style="max-width: 600px; margin: 0 auto;">
        <h3 class="font-bank text-accent mb-4">HUB CONFIGURATION UPDATE</h3>

        <form action="{{ route('be.branches.update', $branch) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">HUB_NAME</label><br>
                <input type="text" name="name" value="{{ $branch->name }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">PROVINCE</label><br>
                <select name="province_id" id="province_id" required onchange="loadKota('province_id', 'city_id')" style="width: 100%; padding: 0.5rem; background: transparent; border: none; border-bottom: 2px solid var(--color-gray); font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    <option value="" disabled>Pilih Provinsi...</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov->id }}" {{ $currentProvId == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">CITY / LOCATION</label><br>
                <select name="city" id="city_id" required style="width: 100%; padding: 0.5rem; background: transparent; border: none; border-bottom: 2px solid var(--color-gray); font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; outline: none;">
                    <option value="{{ $branch->city }}" selected>{{ $branch->city }}</option>
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">CONTACT_PHONE</label><br>
                <input type="text" name="phone" value="{{ $branch->phone }}" required style="width: 100%; border: none; border-bottom: 2px solid var(--color-gray); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none;">
            </div>

            <div style="margin-bottom: 3rem;">
                <label class="font-ui text-gray" style="font-size: 0.8rem;">FULL ADDRESS PROTOCOL</label><br>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <textarea name="address" id="address" required rows="2" style="flex-grow: 1; border: none; border-bottom: 2px solid var(--color-primary); background: transparent; font-family: var(--font-ui); color: var(--color-text-main); font-size: 1.1rem; padding: 0.5rem 0; outline: none; resize: none;">{{ $branch->address }}</textarea>
                    <button type="button" onclick="toggleMap()" class="btn-neon" style="font-size: 0.7rem; white-space: nowrap;">PICK ON MAP</button>
                </div>
            </div>

            <!-- Coordinate Hidden Inputs -->
            <input type="hidden" name="latitude" id="lat" value="{{ $branch->latitude }}">
            <input type="hidden" name="longitude" id="lng" value="{{ $branch->longitude }}">

            <!-- Map Container -->
            <div id="map-container" style="display: none; margin-bottom: 3rem;">
                <div id="map" style="height: 300px; border: 1px solid var(--color-panel-border); background: var(--color-bg);"></div>
                <p class="font-ui text-gray" style="font-size: 0.7rem; margin-top: 0.5rem;">> DRAG MARKER TO REFINE | CLICK MAP TO REPOSITION</p>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('be.branches.index') }}" class="btn-neon" style="color: var(--color-gray); border-color: var(--color-gray);">ABORT</a>
                <button type="submit" class="btn-neon" style="padding: 10px 40px;">UPDATE HUB</button>
            </div>

        </form>
    </div>

@endsection

@push('scripts')
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
let map, marker;
const initialLat = {{ $branch->latitude ?? -6.2088 }};
const initialLng = {{ $branch->longitude ?? 106.8456 }};

function initMap() {
    if (map) return;
    
    map = L.map('map').setView([initialLat, initialLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // If we already have coordinates, place marker
    @if($branch->latitude && $branch->longitude)
        setMarker(initialLat, initialLng);
    @endif

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
    addressInput.value = "🔍 AUTO-LOCATING ADDRESS PROTOCOL...";

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
    
    if (!provId) {
        kotaSelect.innerHTML = '<option value="">Pilih Provinsi dahulu...</option>';
        return;
    }

    kotaSelect.innerHTML = '<option value="">Loading...</option>';

    fetch(`/api/locations/kota?provinsi_id=${provId}`)
        .then(r => r.json())
        .then(kota => {
            kotaSelect.innerHTML = '<option value="" disabled>Pilih Kota/Kabupaten...</option>';
            kota.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.name;
                opt.textContent = k.name;
                if (selectedName && k.name === selectedName) opt.selected = true;
                kotaSelect.appendChild(opt);
            });
        })
        .catch(() => {
            kotaSelect.innerHTML = '<option value="">Gagal memuat kota.</option>';
        });
}

// Initial load for edit mode
document.addEventListener('DOMContentLoaded', function () {
    const provId = document.getElementById('province_id').value;
    if (provId) {
        loadKota('province_id', 'city_id', '{{ $branch->city }}');
    }
});

// Update map on city change
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
