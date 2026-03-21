<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$stmt = $pdo->query("SELECT * FROM map_locations");
$locations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Channel Brewing - Map</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f4f4f4;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 15px;
        }

        h1 {
            text-align: center;
        }

        #map {
            height: 600px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Find Our Beer 🍺</h1>
    <div id="map"></div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const map = L.map('map');

    // Base tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const locations = <?php echo json_encode($locations); ?>;

    const bounds = [];

    locations.forEach(loc => {
        if (loc.latitude && loc.longitude) {

            const lat = parseFloat(loc.latitude);
            const lng = parseFloat(loc.longitude);

            bounds.push([lat, lng]);

            L.marker([lat, lng])
                .addTo(map)
                .bindPopup(`
                    <strong>${loc.name}</strong><br>
                    ${loc.address}<br>
                    ${loc.beers_sold || ''}
                `);
        }
    });

    // Auto-center map
    if (bounds.length === 1) {
        map.setView(bounds[0], 13);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [50, 50] });
    } else {
        // fallback if no data
        map.setView([38.5816, -121.4944], 10);
    }
</script>

</body>
</html>
