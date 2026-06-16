<?php
// 1. 資料庫連線設定 (請根據你的環境修改)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// 2. 處理從前端送來的資料 (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data && isset($data['date'])) {
        $date = $data['date'];
        $oils = $data['prices'];

        // 使用 REPLACE 或 INSERT ... ON DUPLICATE KEY UPDATE 避免重複日期匯入
        $stmt = $conn->prepare("INSERT INTO cpc_oil (effect_date, oil_92, oil_95, oil_98, diesel) 
                                VALUES (?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE oil_92=VALUES(oil_92), oil_95=VALUES(oil_95), oil_98=VALUES(oil_98), diesel=VALUES(diesel)");
        
        $stmt->bind_param("sssss", $date, $oils['92無鉛汽油'], $oils['95無鉛汽油'], $oils['98無鉛汽油'], $oils['超級柴油']);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => '資料已寫入資料庫']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>即時油價管理</title>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 20px; text-align: center; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn { padding: 10px 20px; background: #0056b3; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .price-card { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<div class="container">
    <h2>油價管理系統</h2>
    <button class="btn" onclick="fetchAndSave()">撈取並儲存今日油價</button>
    <div id="status" style="margin: 15px; font-weight: bold;"></div>
    <div id="price-display"></div>
</div>

<script>
    async function fetchAndSave() {
        const status = document.getElementById('status');
        status.innerText = "正在連線中油 API...";
        
        try {
            const api = 'https://vipmember.tmtd.cpc.com.tw/OpenData/ListPriceWebService.asmx/getRetailPriceList';
            const res = await axios.get(api);
            const xml = new DOMParser().parseFromString(res.data, "text/xml");
            const items = xml.getElementsByTagName("PriceList");
            
            const data = {
                date: items[0].getElementsByTagName("牌價生效日期")[0].textContent,
                prices: {
                    "92無鉛汽油": items[0].getElementsByTagName("零售價")[0].textContent,
                    "95無鉛汽油": items[0].getElementsByTagName("零售價")[1].textContent,
                    "98無鉛汽油": items[0].getElementsByTagName("零售價")[2].textContent,
                    "超級柴油": items[0].getElementsByTagName("零售價")[3].textContent
                }
            };

            // 將抓到的資料 POST 給當前 PHP 檔案
            status.innerText = "資料已抓取，正在寫入資料庫...";
            await axios.post(window.location.href, data);
            
            status.innerText = "成功！資料已更新至資料庫。";
            alert("同步完成！");
        } catch (err) {
            status.innerText = "同步失敗: " + err.message;
        }
    }
</script>
</body>
</html>