<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>偵錯模式：YouBike 大甲區</title>
</head>
<body>
    <h1>偵錯模式 - 檢查點位</h1>
    <div id="debug-log" style="background:#eee; padding:10px; font-family:monospace;"></div>

    <script>
        const stations = [
            {"sna": "中山路一段568巷口", "lat": 24.34810, "lng": 120.61490},
            {"sna": "義和二義和三街口", "lat": 24.34612, "lng": 120.61458},
            {"sna": "水尾橋", "lat": 24.34725, "lng": 120.61455},
            {"sna": "大甲義和里石瀨庄福德祠", "lat": 24.34910, "lng": 120.61360},
            {"sna": "義和二街246巷", "lat": 24.34855, "lng": 120.61510},
            {"sna": "大甲里明宮", "lat": 24.34965, "lng": 120.61690}
        ];

        let log = document.getElementById('debug-log');
        log.innerHTML = "成功讀取陣列，共 " + stations.length + " 筆關鍵點位：<br>";
        
        stations.forEach(s => {
            log.innerHTML += "站點: " + s.sna + " | 經度: " + s.lng + "<br>";
        });
    </script>
</body>
</html>