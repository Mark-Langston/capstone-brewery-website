<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$stmt = $pdo->query("
    SELECT map_location_id, name, address, beers_sold, latitude, longitude
    FROM map_locations
    WHERE latitude IS NOT NULL
      AND longitude IS NOT NULL
    ORDER BY name ASC, map_location_id ASC
");
$locations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Channel Brewing - Find Our Beer</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #222;
        }
        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 15px 30px;
        }
        .page-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.10);
            margin-bottom: 20px;
        }
        h1, h2 { margin-top: 0; }
        .intro-text {
            color: #555;
            margin-bottom: 0;
        }
        .map-layout {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
            gap: 20px;
            align-items: start;
        }
        #map {
            width: 100%;
            height: 650px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .locations-panel {
            background: #fafafa;
            border: 1px solid #e6e6e6;
            border-radius: 10px;
            padding: 16px;
            max-height: 650px;
            overflow-y: auto;
        }
        .locations-panel h2 {
            font-size: 20px;
            margin-bottom: 14px;
        }
        .location-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .location-list li + li {
            margin-top: 10px;
        }
        .location-button {
            display: block;
            width: 100%;
            text-align: left;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.1s ease;
        }
        .location-button:hover,
        .location-button:focus {
            background: #f0f0f0;
            border-color: #bbb;
            outline: none;
            transform: translateY(-1px);
        }
        .location-name {
            font-weight: bold;
            display: block;
            margin-bottom: 4px;
        }
        .location-address,
        .location-beers {
            display: block;
            font-size: 13px;
            color: #555;
            line-height: 1.45;
        }
        .empty-state {
            color: #666;
            line-height: 1.5;
        }
        .leaflet-popup-content {
            line-height: 1.5;
            font-size: 14px;
        }
        .popup-title {
            font-weight: bold;
            margin-bottom: 6px;
        }
        .popup-line {
            margin-bottom: 4px;
        }
        .popup-line:last-child {
            margin-bottom: 0;
        }
        @media (max-width: 900px) {
            .map-layout { grid-template-columns: 1fr; }
            #map { height: 480px; }
            .locations-panel { max-height: none; }
        }
        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
                padding: 0 10px 20px;
            }
            .page-card {
                padding: 16px;
                border-radius: 8px;
            }
            h1 { font-size: 26px; }
            h2 { font-size: 20px; }
            #map {
                height: 360px;
                border-radius: 8px;
            }
            .location-button { padding: 14px; }
            .location-name { font-size: 15px; }
            .location-address,
            .location-beers { font-size: 14px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="page-card">
        <h1>Find Our Beer</h1>
        <p class="intro-text">Browse locations that carry Main Channel Brewing beers. Tap a location in the list or click a marker on the map for details.</p>
    </div>

    <div class="page-card">
        <div class="map-layout">
            <div id="map"></div>

            <aside class="locations-panel">
                <h2>Locations</h2>

                <?php if (empty($locations)): ?>
                    <p class="empty-state">No mapped locations are available yet.</p>
                <?php else: ?>
                    <ul class="location-list">
                        <?php foreach ($locations as $location): ?>
                            <li>
                                <button
                                    type="button"
                                    class="location-button"
                                    data-location-id="<?= (int) $location['map_location_id'] ?>"
                                >
                                    <span class="location-name">
                                        <?= htmlspecialchars((string) $location['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <span class="location-address">
                                        <?= htmlspecialchars((string) $location['address'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <?php if (!empty($location['beers_sold'])): ?>
                                        <span class="location-beers">
                                            Beers: <?= htmlspecialchars((string) $location['beers_sold'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    const locations = <?php echo json_encode($locations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    const map = L.map('map');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // 350x350 source image scaled down for map use.
    const beerIcon = L.icon({
        iconUrl: '/assets/images/map/beer_pin.png',
        iconSize: [42, 42],
        iconAnchor: [21, 42],
        popupAnchor: [0, -38]
    });

    const bounds = [];
    const markersById = {};

    locations.forEach(loc => {
        if (!loc.latitude || !loc.longitude) {
            return;
        }

        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        bounds.push([lat, lng]);

        const popupBeers = loc.beers_sold
            ? `<div class="popup-line"><strong>Beers:</strong> ${escapeHtml(loc.beers_sold)}</div>`
            : '';

        const popupHtml = `
            <div class="popup-title">${escapeHtml(loc.name)}</div>
            <div class="popup-line">${escapeHtml(loc.address)}</div>
            ${popupBeers}
        `;

        const marker = L.marker([lat, lng], { icon: beerIcon })
            .addTo(map)
            .bindPopup(popupHtml);

        markersById[String(loc.map_location_id)] = marker;
    });

    if (bounds.length === 1) {
        map.setView(bounds[0], 13);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [50, 50] });
    } else {
        map.setView([38.5816, -121.4944], 10);
    }

    document.querySelectorAll('.location-button').forEach(button => {
        button.addEventListener('click', () => {
            const locationId = button.getAttribute('data-location-id');
            const marker = markersById[locationId];

            if (!marker) {
                return;
            }

            map.setView(marker.getLatLng(), 15, { animate: true });
            marker.openPopup();
        });
    });

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>

</body>
</html>
