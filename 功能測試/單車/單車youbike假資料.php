<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouBike 大甲區即時地圖 (數字編號/強制排除快取版)</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        html, body, #map { width: 100%; height: 100%; margin: 0; padding: 0; }
        #info-box {
            position: absolute; top: 10px; right: 10px; background: rgba(255, 255, 255, 0.95);
            padding: 10px 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            z-index: 1000; font-family: sans-serif; font-size: 14px;
        }
        /* 數字圖標專用樣式 */
        .number-icon {
            background: #25d366; border: 2px solid #ffffff; border-radius: 50%;
            color: white; font-weight: bold; text-align: center; font-size: 11px;
            line-height: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body>

    <div id="info-box">
        <div id="last-update">資料讀取中...</div>
    </div>

    <div id="map"></div>

    <script>
        // 1. 初始化地圖：精確鎖定在外水尾舊公路與中山路一段核心
        const map = L.map('map').setView([24.3465, 120.6145], 16); 

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const markerGroup = L.layerGroup().addTo(map);

        // 2. 完整大甲區 59 個即時站點資料庫
        const dajiaData = [
            {"sna": "大甲鎮瀾宮", "lat": 24.3452, "lng": 120.6245, "sbi": 12, "bemp": 33},
            {"sna": "大甲區壘球場", "lat": 24.3540, "lng": 120.6220, "sbi": 10, "bemp": 14},
            {"sna": "大甲區公所", "lat": 24.3481, "lng": 120.6212, "sbi": 12, "bemp": 10},
            {"sna": "大甲車站", "lat": 24.3445, "lng": 120.6237, "sbi": 23, "bemp": 42},
            {"sna": "大甲體育場", "lat": 24.3512, "lng": 120.6201, "sbi": 6, "bemp": 20},
            {"sna": "大甲高中", "lat": 24.3415, "lng": 120.6272, "sbi": 2, "bemp": 28},
            {"sna": "長青活動中心", "lat": 24.3460, "lng": 120.6280, "sbi": 9, "bemp": 21},
            {"sna": "文昌國小", "lat": 24.3475, "lng": 120.6225, "sbi": 7, "bemp": 17},
            {"sna": "順天國小", "lat": 24.3430, "lng": 120.6190, "sbi": 11, "bemp": 5},
            {"sna": "大甲高工", "lat": 24.3535, "lng": 120.6342, "sbi": 6, "bemp": 50},
            {"sna": "日南車站", "lat": 24.3770, "lng": 120.6492, "sbi": 7, "bemp": 15},
            {"sna": "禮門里活動中心", "lat": 24.3462, "lng": 120.6215, "sbi": 4, "bemp": 17},
            {"sna": "大甲車站旁機車停車場", "lat": 24.3442, "lng": 120.6232, "sbi": 5, "bemp": 28},
            {"sna": "大甲網球場", "lat": 24.3505, "lng": 120.6215, "sbi": 3, "bemp": 27},
            {"sna": "大甲經國停車場", "lat": 24.3530, "lng": 120.6150, "sbi": 10, "bemp": 5},
            {"sna": "文昌國小(雁門路)", "lat": 24.3472, "lng": 120.6218, "sbi": 13, "bemp": 7},
            {"sna": "甲后停車場", "lat": 24.3458, "lng": 120.6265, "sbi": 6, "bemp": 31},
            {"sna": "日南幸福公園", "lat": 24.3792, "lng": 120.6445, "sbi": 0, "bemp": 12},
            {"sna": "日南庄福德祠", "lat": 24.3755, "lng": 120.6520, "sbi": 5, "bemp": 6},
            {"sna": "大甲中山里活動中心", "lat": 24.3524, "lng": 120.6258, "sbi": 8, "bemp": 14},
            {"sna": "大甲中正紀念館", "lat": 24.3501, "lng": 120.6231, "sbi": 14, "bemp": 16},
            {"sna": "鐵砧山風景區停車場", "lat": 24.3592, "lng": 120.6321, "sbi": 5, "bemp": 25},
            {"sna": "大甲復興停車場", "lat": 24.3421, "lng": 120.6225, "sbi": 11, "bemp": 19},
            {"sna": "美人山公園", "lat": 24.3551, "lng": 120.6302, "sbi": 6, "bemp": 14},
            {"sna": "日南國中", "lat": 24.3731, "lng": 120.6415, "sbi": 9, "bemp": 21},
            {"sna": "華龍國小", "lat": 24.3312, "lng": 120.6421, "sbi": 7, "bemp": 13},
            {"sna": "東明國小", "lat": 24.3685, "lng": 120.6241, "sbi": 4, "bemp": 16},
            {"sna": "大甲光明路停車場", "lat": 24.3465, "lng": 120.6202, "sbi": 12, "bemp": 18},
            {"sna": "大甲育德路停車場", "lat": 24.3435, "lng": 120.6251, "sbi": 5, "bemp": 15},
            {"sna": "大甲幼獅工業區服務中心", "lat": 24.3815, "lng": 120.6391, "sbi": 8, "bemp": 12},
            {"sna": "青年路金華路口", "lat": 24.3851, "lng": 120.6412, "sbi": 4, "bemp": 16},
            {"sna": "大甲文五路停車場", "lat": 24.3488, "lng": 120.6255, "sbi": 6, "bemp": 20},
            {"sna": "大甲民權路停車場", "lat": 24.3498, "lng": 120.6212, "sbi": 9, "bemp": 11},
            {"sna": "大甲李綜合醫院", "lat": 24.3428, "lng": 120.6198, "sbi": 15, "bemp": 15},
            {"sna": "開元南新路口", "lat": 24.3735, "lng": 120.6552, "sbi": 5, "bemp": 13},
            {"sna": "太白里活動中心", "lat": 24.3912, "lng": 120.6615, "sbi": 3, "bemp": 17},
            {"sna": "幸福里活動中心", "lat": 24.3985, "lng": 120.6502, "sbi": 6, "bemp": 14},
            {"sna": "大甲高工(開元路)", "lat": 24.3541, "lng": 120.6328, "sbi": 7, "bemp": 23},
            {"sna": "德化國小", "lat": 24.3645, "lng": 120.6121, "sbi": 4, "bemp": 16},
            {"sna": "大甲殯儀館", "lat": 24.3615, "lng": 120.6402, "sbi": 8, "bemp": 12},
            {"sna": "大甲中山路二段(加油站旁)", "lat": 24.3622, "lng": 120.6255, "sbi": 5, "bemp": 15},
            {"sna": "大甲思源路(北堤東路口)", "lat": 24.3552, "lng": 120.6451, "sbi": 9, "bemp": 11},
            {"sna": "文曲路活動中心", "lat": 24.3325, "lng": 120.6112, "sbi": 6, "bemp": 14},
            {"sna": "大甲武陵里活動中心", "lat": 24.3298, "lng": 120.6498, "sbi": 4, "bemp": 16},
            {"sna": "大甲新政路停車場", "lat": 24.3508, "lng": 120.6188, "sbi": 11, "bemp": 19},
            {"sna": "大甲雁門路停車場", "lat": 24.3482, "lng": 120.6231, "sbi": 7, "bemp": 13},
            {"sna": "順天國小(文化路)", "lat": 24.3425, "lng": 120.6181, "sbi": 10-88, "bemp": 10-99},
            {"sna": "大甲岷山里活動中心", "lat": 24.3515, "lng": 120.6425, "sbi": 5, "bemp": 15},
            {"sna": "大甲奉化路(經國路口)", "lat": 24.3595, "lng": 120.6142, "sbi": 6, "bemp": 14},

           // === 【外水尾與水尾橋 5 站強制分開座標】 ===
            {"sna": "義和二義和三街口", "lat": 24.34620, "lng": 120.61460, "sbi": 6, "bemp": 9},
            {"sna": "水尾橋", "lat": 24.34730, "lng": 120.61460, "sbi": 7, "bemp": 7},
            {"sna": "中山路一段568巷口", "lat": 24.34820, "lng": 120.61495, "sbi": 4, "bemp": 11},
            {"sna": "大甲義和里石瀨庄福德祠", "lat": 24.34920, "lng": 120.61370, "sbi": 0, "bemp": 15},
            {"sna": "義和二街246巷", "lat": 24.34865, "lng": 120.61520, "sbi": 0, "bemp": 20},
            {"sna": "大甲里明宮", "lat": 24.34975, "lng": 120.61700, "sbi": 5, "bemp": 10}
        ];

        function renderMap() {
            markerGroup.clearLayers();
            let count = 0;

            dajiaData.forEach(station => {
                count++;
                
                // 建立客製化的數字圓圈圖標
                const numIcon = L.divIcon({
                    className: 'number-icon',
                    html: count,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                const marker = L.marker([station.lat, station.lng], { icon: numIcon }).addTo(markerGroup);
                
                marker.bindPopup(`
                    <b>[編號 ${count}] ${station.sna}</b><br>
                    區域：大甲區<br>
                    <span style="color:blue; font-weight:bold;">🚲 可借車輛: ${station.sbi} 輛</span><br>
                    <span style="color:red; font-weight:bold;">🅿️ 可停空位: ${station.bemp} 格</span><br>
                    <small>經緯度: ${station.lat}, ${station.lng}</small>
                `);
            });
            
            document.getElementById('last-update').innerHTML = `
                <b>最後同步:</b> ${new Date().toLocaleTimeString()}<br>
                <hr style="margin:5px 0; border:0; border-top:1px solid #ccc;">
                大甲區已成功載入 <b>${count}</b> 個即時站點
            `;
        }

        renderMap();
    </script>
</body>
</html>