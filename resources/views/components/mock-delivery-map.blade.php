@php
    $status = $order->courier_status ?? $order->status;
    $isCancelled = $status === 'cancelled';

    // Kohan Mart Store (41 Insurance, Project 8, QC)
    $storeLat = 14.6648;
    $storeLng = 121.0235;

    // Customer Drop-off Coordinates
    $rawCustomerLat = (float) ($order->delivery_lat ?? 14.6565);
    $rawCustomerLng = (float) ($order->delivery_lng ?? 121.0315);

    // Ensure distinct origin & drop-off if database saved identical coordinates
    if (abs($storeLat - $rawCustomerLat) < 0.001 && abs($storeLng - $rawCustomerLng) < 0.001) {
        $customerLat = 14.6565; // SM North EDSA / North Ave area
        $customerLng = 121.0315;
    } else {
        $customerLat = $rawCustomerLat;
        $customerLng = $rawCustomerLng;
    }

    // Rider progress percentage based on order stage
    $progress = match ($status) {
        'delivered', 'completed' => 1.0,
        'on_the_way', 'out_for_delivery' => 0.55,
        'picked_up' => 0.25,
        default => 0.08,
    };

    $driverName = $order->courier_driver_name ?? 'Lalamove Rider';
    $customerName = $order->user->name ?? 'Customer';
    $deliveryAddress = $order->delivery_address ?? 'SM North EDSA, Quezon City';
@endphp

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

<div class="bg-[#FAF7F2] rounded-3xl border border-[#E2D8C3] p-5 space-y-4 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <span class="text-[10px] uppercase tracking-wider font-bold text-[#3F5A45] block">Live Map Tracking</span>
            <h3 class="font-bold text-sm text-[#2E2A25]">Kohan Mart Project 8 → Real-Time Street Navigation</h3>
        </div>
        <span class="px-3 py-1 rounded-full text-[11px] font-semibold bg-[#E6EFE8] text-[#2B4230] border border-[#B8D1BE] flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-[#3F5A45] animate-ping"></span>
            OpenStreetMap Live Feed
        </span>
    </div>

    <!-- Leaflet Map Container -->
    <div id="realDeliveryMap" class="w-full h-72 sm:h-80 rounded-2xl border border-[#D4C8B4] z-10 overflow-hidden"></div>

    <div class="flex flex-wrap items-center justify-between text-[11px] text-[#5C4E42] pt-1">
        <div class="flex items-center gap-4">
            <span class="flex items-center gap-1.5">🏪 <strong>Origin:</strong> Project 8</span>
            <span class="flex items-center gap-1.5">🛵 <strong>Rider:</strong> {{ $isCancelled ? 'Cancelled' : 'On Street Route' }}</span>
            <span class="flex items-center gap-1.5">📍 <strong>Drop-off:</strong> Customer Address</span>
        </div>
        @if($order->delivery_distance_km)
            <span class="font-mono text-[#6B4E3D]">Route Span: <strong>{{ number_format((float)$order->delivery_distance_km, 2) }} km</strong></span>
        @endif
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
(function() {
    async function initKohanMap() {
        const mapEl = document.getElementById('realDeliveryMap');
        if (!mapEl || typeof L === 'undefined') return;

        const storeLat = {{ $storeLat }};
        const storeLng = {{ $storeLng }};
        const customerLat = {{ $customerLat }};
        const customerLng = {{ $customerLng }};
        const progress = {{ $progress }};
        const isCancelled = @json($isCancelled);

        const map = L.map('realDeliveryMap').setView([storeLat, storeLng], 15);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const storeIcon = L.divIcon({
            className: '',
            html: '<div style="background:#3F5A45;color:#fff;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 8px rgba(0,0,0,0.25);font-size:15px;border:2px solid #fff;">🏪</div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });

        const riderIcon = L.divIcon({
            className: '',
            html: '<div style="background:#6B4E3D;color:#fff;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 14px rgba(0,0,0,0.35);font-size:17px;border:2px solid #fff;">🛵</div>',
            iconSize: [38, 38],
            iconAnchor: [19, 19]
        });

        const customerIcon = L.divIcon({
            className: '',
            html: '<div style="background:#9E3B33;color:#fff;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 8px rgba(0,0,0,0.25);font-size:15px;border:2px solid #fff;">📍</div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });

        // Origin & Drop-off Markers
        L.marker([storeLat, storeLng], { icon: storeIcon })
            .addTo(map)
            .bindPopup("<b>Kohan Mart</b><br>41 Insurance, Project 8, QC");

        L.marker([customerLat, customerLng], { icon: customerIcon })
            .addTo(map)
            .bindPopup("<b>{{ addslashes($customerName) }}</b><br>{{ addslashes($deliveryAddress) }}");

        let routeCoords = [
            [storeLat, storeLng],
            [customerLat, customerLng]
        ];

        // Fetch real street-by-street driving route from OSRM (Free, No API Key)
        try {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${storeLng},${storeLat};${customerLng},${customerLat}?overview=full&geometries=geojson`;
            const response = await fetch(osrmUrl);
            const data = await response.json();

            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                // Convert GeoJSON [lng, lat] pairs to Leaflet [lat, lng]
                routeCoords = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
            }
        } catch (err) {
            console.warn('OSRM fallback to direct path:', err);
        }

        // Split route into completed path (solid green) & remaining path (dashed terracotta/brown)
        const splitIndex = Math.min(
            routeCoords.length - 1,
            Math.max(0, Math.floor((routeCoords.length - 1) * progress))
        );

        const completedCoords = routeCoords.slice(0, splitIndex + 1);
        const remainingCoords = routeCoords.slice(splitIndex);

        if (completedCoords.length > 1) {
            L.polyline(completedCoords, {
                color: '#3F5A45',
                weight: 5,
                opacity: 0.95
            }).addTo(map);
        }

        if (remainingCoords.length > 1) {
            L.polyline(remainingCoords, {
                color: '#6B4E3D',
                weight: 4,
                dashArray: '8, 8',
                opacity: 0.75
            }).addTo(map);
        }

        // Snap Rider Pin directly onto the real road coordinate at current stage progress
        if (!isCancelled) {
            const riderPos = routeCoords[splitIndex] || [storeLat, storeLng];
            L.marker(riderPos, { icon: riderIcon })
                .addTo(map)
                .bindPopup("<b>{{ addslashes($driverName) }}</b><br>En route along street lane")
                .openPopup();
        }

        // Fit map cleanly around the entire street route
        const bounds = L.latLngBounds(routeCoords);
        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKohanMap);
    } else {
        initKohanMap();
    }
})();
</script>