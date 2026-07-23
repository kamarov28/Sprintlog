@props(['id', 'latName', 'lngName', 'addressName', 'defaultLat' => '-6.2088', 'defaultLng' => '106.8456', 'labelText' => 'Location Address', 'infoText' => '', 'buttonText' => 'Set on Map'])

@php
    $addressValue = trim((string) $slot);
@endphp

@once
    @push('scripts')
        <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush
@endonce

<div class="space-y-3">
    <div class="flex flex-col sm:flex-row items-end gap-3 w-full">
        <div class="flex-grow w-full">
            <x-fe.input type="textarea" label="{{ $labelText }}" name="{{ $addressName }}" id="{{ $id }}_address_display" required class="mb-0" {{ $attributes }}>{{ $addressValue }}</x-fe.input>
        </div>
        <x-fe.button type="button" onclick="toggleMap_{{ $id }}()" variant="outline" class="btn-sm font-semibold shrink-0 mb-4 h-12">{{ $buttonText }}</x-fe.button>
    </div>
    
    @if($infoText)
        <p class="text-[10px] text-slate-400 font-light text-left">{{ $infoText }}</p>
    @endif
    
    <div id="{{ $id }}_lock_status" class="text-[10px] text-slate-400 font-semibold border-l-2 border-primary/40 pl-3 leading-normal text-left">
        <span class="text-primary font-bold uppercase tracking-wider block text-[8px] mb-0.5">Ready</span>
        <strong id="{{ $id }}_lock_label" class="font-normal block">{{ $addressValue ? \Illuminate\Support\Str::limit($addressValue, 58) : 'Map point follows selected city until you lock exact address.' }}</strong>
    </div>
    
    <input type="hidden" name="{{ $latName }}" id="{{ $id }}_lat" value="{{ $defaultLat }}">
    <input type="hidden" name="{{ $lngName }}" id="{{ $id }}_lng" value="{{ $defaultLng }}">

    <div id="{{ $id }}-map-container" class="glass-panel border-white/5 p-2 rounded-2xl shadow-xl mt-3" style="display: none;">
        <div id="{{ $id }}_map" class="w-full rounded-xl overflow-hidden" style="height: 250px;"></div>
    </div>
</div>

@push('scripts')
<script>
let {{ $id }}Map, {{ $id }}Marker;
const {{ $id }}DefaultLat = {{ $defaultLat ?: -6.2088 }};
const {{ $id }}DefaultLng = {{ $defaultLng ?: 106.8456 }};

function initMap_{{ $id }}() {
    if ({{ $id }}Map) return;
    {{ $id }}Map = L.map('{{ $id }}_map').setView([{{ $id }}DefaultLat, {{ $id }}DefaultLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo({{ $id }}Map);

    {{ $id }}Map.on('click', (e) => setMarker_{{ $id }}(e.latlng.lat, e.latlng.lng));
    if ({{ $id }}DefaultLat && {{ $id }}DefaultLng) setMarker_{{ $id }}({{ $id }}DefaultLat, {{ $id }}DefaultLng);
}

function setMarker_{{ $id }}(lat, lng) {
    if ({{ $id }}Marker) {{ $id }}Marker.setLatLng([lat, lng]);
    else {
        {{ $id }}Marker = L.marker([lat, lng], {draggable: true}).addTo({{ $id }}Map);
        {{ $id }}Marker.on('dragend', () => {
            const p = {{ $id }}Marker.getLatLng();
            updateInputs_{{ $id }}(p.lat, p.lng);
        });
    }
    updateInputs_{{ $id }}(lat, lng);
}

function focusMap_{{ $id }}(lat, lng, zoom = 13, updateAddress = false) {
    initMap_{{ $id }}();
    {{ $id }}Map.setView([lat, lng], zoom);

    if ({{ $id }}Marker) {
        {{ $id }}Marker.setLatLng([lat, lng]);
    } else {
        {{ $id }}Marker = L.marker([lat, lng], {draggable: true}).addTo({{ $id }}Map);
        {{ $id }}Marker.on('dragend', () => {
            const p = {{ $id }}Marker.getLatLng();
            updateInputs_{{ $id }}(p.lat, p.lng);
        });
    }

    document.getElementById('{{ $id }}_lat').value = Number(lat).toFixed(8);
    document.getElementById('{{ $id }}_lng').value = Number(lng).toFixed(8);
    announceMapLock_{{ $id }}('AREA_FOCUSED', document.getElementById('{{ $id }}_address_display').value, lat, lng);

    if (updateAddress) {
        updateInputs_{{ $id }}(lat, lng);
    }
}

function updateInputs_{{ $id }}(lat, lng) {
    document.getElementById('{{ $id }}_lat').value = lat.toFixed(8);
    document.getElementById('{{ $id }}_lng').value = lng.toFixed(8);
    announceMapLock_{{ $id }}('POINT_LOCKED', `${lat.toFixed(5)}, ${lng.toFixed(5)}`, lat, lng);
    
    // Reverse Geocode
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(data => {
            if (data.display_name) {
                document.getElementById('{{ $id }}_address_display').value = data.display_name;
                announceMapLock_{{ $id }}('POINT_LOCKED', data.display_name, lat, lng);
            }
        });
}

function announceMapLock_{{ $id }}(status, label, lat = null, lng = null) {
    const statusBox = document.getElementById('{{ $id }}_lock_status');
    const labelBox = document.getElementById('{{ $id }}_lock_label');
    if (!statusBox || !labelBox) return;

    statusBox.querySelector('span').textContent = status.replaceAll('_', ' ').toLowerCase();
    labelBox.textContent = label || 'Exact point selected.';
    document.dispatchEvent(new CustomEvent('map-point-updated', {
        detail: {
            id: '{{ $id }}',
            status,
            label: label || '',
            lat,
            lng,
        }
    }));
}

function toggleMap_{{ $id }}() {
    const c = document.getElementById('{{ $id }}-map-container');
    if (c.style.display === 'none') {
        c.style.display = 'block';
        initMap_{{ $id }}();
        setTimeout(() => {{ $id }}Map.invalidateSize(), 100);
    } else c.style.display = 'none';
}
</script>
@endpush
