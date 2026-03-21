<?php
require_once __DIR__ . '/db.php';

$stmt = $pdo->query("SELECT * FROM map_locations");
$locations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Map</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        #map {
            height: 600px;
            width: 100%;
        }
    </style>
</head>
<body>

<h2>Map Test</h2>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const map = L.map('map').setView([38.5816, -121.4944], 10); // Sacramento default

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const locations = <?php echo json_encode($locations); ?>;

    locations.forEach(loc => {
        if (loc.latitude && loc.longitude) {
            L.marker([loc.latitude, loc.longitude])
                .addTo(map)
                .bindPopup(`
                    <strong>${loc.name}</strong><br>
                    ${loc.address}<br>
                    ${loc.beers_sold || ''}
                `);
        }
    });
</script>

</body>
</html>
