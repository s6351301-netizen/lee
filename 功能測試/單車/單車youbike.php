<?php
// 讀取已經被爬蟲更新好的資料
$json = file_get_contents('stations.json');
$stations = json_decode($json, true);
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>#map { height: 100vh; }</style>
</head>
<body>
    <div id="map"></div>
    <script>
        const map = L.map('map').setView([24.3465, 120.6145], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        
        const stations = <?php echo json_encode($stations); ?>;
        stations.forEach(s => {
            // 注意：這裡需對應您的經緯度座標庫 (建議您建立一份包含 lat/lng 的對照表)
            L.marker([24.3465, 120.6145]).addTo(map).bindPopup(s.sna + "<br>車: " + s.sbi);
        });
    </script>
</body>
</html>