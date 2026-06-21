<?php
// =========================================================================
// 後端 PHP 影像比對邏輯 (當收到 AJAX 拍照請求時觸發)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_image') {
    header('Content-Type: application/json');

    // 1. 取得前端傳過來的鏡頭擷取畫面
    $imgData = $_POST['image'];
    $imgData = str_replace('data:image/jpeg;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $userImgBinary = base64_decode($imgData);

    $userImage = imagecreatefromstring($userImgBinary);
    
    // 2. 讀取目標樹木圖片 (為了確保比對速度，後端直接讀取您的樹木網路圖，或您可改為本機路徑)
    $targetUrl = "https://s6351301-netizen.github.io/lee/icon/MountTai-%20Tree.png";
    $targetImage = @imagecreatefrompng($targetUrl);

    if (!$userImage || !$targetImage) {
        echo json_encode(['success' => false, 'message' => '影像讀取失敗，請重新拍攝。']);
        exit;
    }

    // 3. 【大小與位置核對演算法】將兩張圖縮放到相同尺寸（例如 64x64 網格）進行灰階與色彩特徵比對
    $thumbW = 64;
    $thumbH = 64;
    
    $userThumb = imagecreatetruecolor($thumbW, $thumbH);
    $targetThumb = imagecreatetruecolor($thumbW, $thumbH);
    
    imagecopyresampled($userThumb, $userImage, 0, 0, 0, 0, $thumbW, $thumbH, imagesx($userImage), imagesy($userImage));
    imagecopyresampled($targetThumb, $targetImage, 0, 0, 0, 0, $thumbW, $thumbH, imagesx($targetImage), imagesy($targetImage));

    // 4. 逐像素計算形狀與色彩位置重合度
    $matchedPixels = 0;
    $totalPixels = $thumbW * $thumbH;
    $colorThreshold = 55; // 容許的像素色彩誤差範圍值

    for ($x = 0; $x < $thumbW; $x++) {
        for ($y = 0; $y < $thumbH; $y++) {
            $rgbUser = imagecolorat($userThumb, $x, $y);
            $rU = ($rgbUser >> 16) & 0xFF;
            $gU = ($rgbUser >> 8) & 0xFF;
            $bU = $rgbUser & 0xFF;

            $rgbTarget = imagecolorat($targetThumb, $x, $y);
            $rT = ($rgbTarget >> 16) & 0xFF;
            $gT = ($rgbTarget >> 8) & 0xFF;
            $bT = $rgbTarget & 0xFF;

            // 如果目標像素是透明或白色（外圍背景），則不加入主要輪廓重合懲罰
            if ($rT > 240 && $gT > 240 && $bT > 240) {
                $totalPixels--; 
                continue;
            }

            // 計算兩者色彩與亮度的歐氏距離（位置與大小不對，像素色彩就會錯位）
            $distance = sqrt(pow($rU - $rT, 2) + pow($gU - $gT, 2) + pow($bU - $bT, 2));
            if ($distance < $colorThreshold) {
                $matchedPixels++;
            }
        }
    }

    // 5. 計算最終相似度
    $similarity = $totalPixels > 0 ? round(($matchedPixels / $totalPixels) * 100) : 0;
    
    // 考慮到戶外光線，給予一個基本環境光補償容差 (非必須，可移除)
    $similarity = min(100, $similarity + 15); 

    // 6. 釋放記憶體
    imagedestroy($userImage); imagedestroy($targetImage);
    imagedestroy($userThumb); imagedestroy($targetThumb);

    // 7. 回傳比對結果 (不寫入資料庫，純邏輯判定)
    if ($similarity >= 80) {
        echo json_encode(['success' => true, 'similarity' => $similarity]);
    } else {
        echo json_encode(['success' => false, 'similarity' => $similarity]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新北市泰山職訓打卡地圖-寶可夢樹木比對 (PHP版)</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        html, body { margin: 0; padding: 0; width: 100%; height: 100%; font-family: "Microsoft JhengHei", Arial, sans-serif; overflow: hidden; }
        #map { width: 100%; height: 100%; }
        .info-panel { padding: 12px 15px; font-size: 14px; background: rgba(255, 255, 255, 0.95); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); border-radius: 8px; border-left: 5px solid #1a73e8; min-width: 200px; }
        .info-panel h4 { margin: 0 0 5px 0; color: #333; font-size: 16px; }
        .info-panel p { margin: 3px 0; color: #666; }
        .highlight { color: #e81123; font-weight: bold; font-size: 18px; }
        #action-container { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; text-align: center; width: 90%; max-width: 400px; }
        .btn { background-color: #4CAF50; color: white; border: none; padding: 15px 30px; font-size: 18px; font-weight: bold; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3); width: 100%; margin-bottom: 10px; }
        .btn:disabled { background-color: #cccccc; cursor: not-allowed; }
        #camera-view { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; z-index: 2000; }
        #video { width: 100%; height: 100%; object-fit: cover; }
        #comparison-frame { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; display: flex; flex-direction: column; justify-content: space-between; box-sizing: border-box; }
        .frame-blind { background: rgba(0, 0, 0, 0.75); color: white; text-align: center; padding: 15px; font-size: 16px; }
        .center-row { display: flex; flex-grow: 1; justify-content: space-between; }
        .side-blind { background: rgba(0, 0, 0, 0.75); width: 10%; }
        .clear-window { border: 4px solid #ffeb3b; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5); flex-grow: 1; border-radius: 12px; margin: 20px 0; position: relative; overflow: hidden; }
        .clear-window::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: url('https://s6351301-netizen.github.io/lee/icon/MountTai-%20Tree.png') no-repeat center; background-size: contain; opacity: 0.45; }
        .ar-control-panel { position: absolute; bottom: 5%; left: 50%; transform: translateX(-50%); width: 85%; max-width: 340px; text-align: center; z-index: 2100; }
        .ar-tip-text { color: #00FF00; font-size: 16px; font-weight: bold; text-shadow: 0 0 8px #000; background: rgba(0,0,0,0.6); padding: 8px 12px; border-radius: 8px; margin-bottom: 12px; }
        .capture-btn { background-color: #ff3b30; color: white; border: 2px solid white; padding: 14px 25px; font-size: 18px; font-weight: bold; border-radius: 30px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.4); width: 100%; }
        .close-btn { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.8); border: none; padding: 8px 16px; border-radius: 5px; font-size: 14px; font-weight: bold; z-index: 2200; }
        #loading-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:3000; color:white; font-size:20px; text-align:center; padding-top:50%; }
    </style>
</head>
<body>

    <div id="map"></div>
    <div id="action-container">
        <button id="ar-btn" class="btn" disabled onclick="openCameraWithComparison()">距離太遠（GPS 鎖定中）</button>
    </div>

    <div id="camera-view">
        <video id="video" autoplay playsinline></video>
        <div id="comparison-frame">
            <div class="frame-blind">📷 樹木外型與位置核對模式 (PHP 實時分析版)</div>
            <div class="center-row">
                <div class="side-blind"></div>
                <div class="clear-window" id="scan-window"></div>
                <div class="side-blind"></div>
            </div>
            <div class="frame-blind" style="padding-bottom:120px;">⚠️ 現場樹木大小、位置須與黃框內輪廓吻合</div>
        </div>
        
        <div class="ar-control-panel">
            <div class="ar-tip-text">🧩 請對準現場樹木並使其填滿黃色方框</div>
            <button class="capture-btn" onclick="verifyAndScore()">[ 📸 拍攝：送出後端核對大小位置 ]</button>
        </div>
        <button class="close-btn" onclick="closeCamera()">關閉鏡頭</button>
    </div>

    <div id="loading-overlay">⏳ 伺服器正在核對影像大小與位置...</div>

    <canvas id="capture-canvas" style="display:none;"></canvas>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const targetSpot = { name: "泰山職訓中心", lat: 25.0435125, lng: 121.4192113, score: 0, hasScored: false };
        const map = L.map('map').setView([targetSpot.lat, targetSpot.lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        L.marker([targetSpot.lat, targetSpot.lng]).addTo(map).bindPopup(`<b>${targetSpot.name}</b><br>泰山樹木打卡點`).openPopup();
        L.circle([targetSpot.lat, targetSpot.lng], { color: 'red', fillColor: '#f03', fillOpacity: 0.1, radius: 80 }).addTo(map);

        let userMarker = null;
        const info = L.control({ position: 'topright' });
        info.onAdd = function (map) { this._div = L.DomUtil.create('div', 'info-panel'); this.update(null, "GPS 定位中..."); return this._div; };
        info.update = function (distance, scoreText) {
            let distStr = distance !== null ? `${distance.toFixed(1)} 公尺` : "未知";
            this._div.innerHTML = `<h4>訓練家打卡系統</h4><p>目前得分：<span class="highlight" style="color:green;">${targetSpot.score} 分</span></p><p>目標景點：<b>${targetSpot.name}</b></p><p>與目標距離：<span class="highlight">${distStr}</span></p><p>狀態：${scoreText}</p>`;
        };
        info.addTo(map);

        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180; const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
            return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)));
        }

        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(function(position) {
                const uLat = position.coords.latitude; const uLng = position.coords.longitude;
                if (!userMarker) userMarker = L.circleMarker([uLat, uLng], { radius: 8, color: '#1a73e8', fillColor: '#1a73e8', fillOpacity: 1 }).addTo(map);
                else userMarker.setLatLng([uLat, uLng]);

                const distance = getDistance(uLat, uLng, targetSpot.lat, targetSpot.lng);
                const arBtn = document.getElementById('ar-btn');

                if (distance <= 80) {
                    if (!targetSpot.hasScored) {
                        info.update(distance, "<span style='color:green; font-weight:bold;'>已進入樹木打卡區！請啟動鏡頭。</span>");
                        arBtn.disabled = false; arBtn.style.backgroundColor = "#4CAF50"; arBtn.innerText = "⚡ 開啟鏡頭核對";
                    } else {
                        info.update(distance, "此據點已完成打卡。");
                        arBtn.disabled = true; arBtn.style.backgroundColor = "#cccccc"; arBtn.innerText = "已完成打卡";
                    }
                } else {
                    info.update(distance, "未進入 80 米範圍 (請繼續靠近)");
                    arBtn.disabled = true; arBtn.style.backgroundColor = "#cccccc"; arBtn.innerText = "距離太遠（GPS 鎖定中）";
                }
            }, function(error) { info.update(null, "<span style='color:red;'>請開啟手機高精度 GPS 權限</span>"); }, { enableHighAccuracy: true, timeout: 5000 });
        }

        const video = document.getElementById('video');
        const cameraView = document.getElementById('camera-view');

        function openCameraWithComparison() {
            cameraView.style.display = 'block';
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" }, audio: false })
                .then(function(stream) { video.srcObject = stream; })
                .catch(function(err) { alert("無法啟動相機。請確認為 HTTPS 安全連線。"); closeCamera(); });
        }

        // 🎯 核心變更：拍照後將影像利用 AJAX 傳送給後端 PHP 作真實大小輪廓核對
        function verifyAndScore() {
            if (targetSpot.hasScored) return;

            const currentLatLng = userMarker ? userMarker.getLatLng() : {lat: 0, lng: 0};
            const currentDist = getDistance(currentLatLng.lat, currentLatLng.lng, targetSpot.lat, targetSpot.lng);

            if (currentDist > 80) {
                alert("❌ 打卡失敗！您已離開 GPS 認證範圍。");
                closeCamera();
                return;
            }

            // 擷取黃框範圍內的影像
            const canvas = document.getElementById('capture-canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // 轉成 Base64 格式
            const base64Image = canvas.toDataURL('image/jpeg', 0.8);

            // 顯示 Loading
            document.getElementById('loading-overlay').style.display = 'block';

            // 將影像送交給本頁的 PHP 後端進行比對
            const formData = new FormData();
            formData.append('action', 'verify_image');
            formData.append('image', base64Image);

            fetch('', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    
                    if (data.success) {
                        targetSpot.score += 100;
                        targetSpot.hasScored = true;
                        alert(`🌟 核對成功（相似度：${data.similarity}% ≧ 80%）！\n恭喜獲得寶可夢積分：+100 分！`);
                        closeCamera();
                        info.update(currentDist, "<span style='color:gold; font-weight:bold;'>🎉 樹木影像核對通過！順利得分。</span>");
                    } else {
                        alert(`❌ 核對失敗（相似度：${data.similarity}% < 80%）！\n\n【提示】：要作影像大小與位置核對，請移動位置將樹木貼齊黃色框線。`);
                    }
                })
                .catch(err => {
                    document.getElementById('loading-overlay').style.display = 'none';
                    alert("連線後端錯誤，請重新嘗試。");
                });
        }

        function closeCamera() {
            cameraView.style.display = 'none';
            if (video.srcObject) { video.srcObject.getTracks().forEach(track => track.stop()); }
        }
    </script>
</body>
</html>