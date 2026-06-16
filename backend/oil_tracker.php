<?php
$conn = new mysqli("localhost", "root", "", "lee");
$conn->set_charset("utf8mb4");

// 1. 如果是前端傳送資料過來，由這裡處理存檔
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        $stmt = $conn->prepare("INSERT INTO cpc_oil (effect_date, oil_92, oil_95, oil_98, diesel) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE oil_92=VALUES(oil_92), oil_95=VALUES(oil_95), oil_98=VALUES(oil_98), diesel=VALUES(diesel)");
        $stmt->bind_param("sssss", $data['date'], $data['p92'], $data['p95'], $data['p98'], $data['pd']);
        $stmt->execute();
        echo "success";
        exit;
    }
}

// 2. 正常網頁顯示
$result = $conn->query("SELECT * FROM cpc_oil ORDER BY effect_date DESC LIMIT 30");
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .btn { padding: 10px; background: green; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <button class="btn" onclick="syncOil()">撈取今天油價並更新</button>
    <div id="msg"></div>
    <table border="1" style="margin-top:20px; width:100%; border-collapse:collapse;">
        <tr><th>日期</th><th>92</th><th>95</th><th>98</th><th>柴油</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr><td><?=$row['effect_date']?></td><td><?=$row['oil_92']?></td><td><?=$row['oil_95']?></td><td><?=$row['oil_98']?></td><td><?=$row['diesel']?></td></tr>
        <?php endwhile; ?>
    </table>

    <script>
    function syncOil() {
        document.getElementById('msg').innerText = "正在連線中油...";
        // 瀏覽器端直接連線，避開 PHP 防火牆
        axios.get('https://vipmember.tmtd.cpc.com.tw/OpenData/ListPriceWebService.asmx/getRetailPriceList')
        .then(res => {
            const xml = new DOMParser().parseFromString(res.data, "text/xml");
            const items = xml.getElementsByTagName("PriceList");
            const data = {
                date: items[0].getElementsByTagName("牌價生效日期")[0].textContent,
                p92: items[0].getElementsByTagName("零售價")[0].textContent,
                p95: items[0].getElementsByTagName("零售價")[1].textContent,
                p98: items[0].getElementsByTagName("零售價")[2].textContent,
                pd: items[0].getElementsByTagName("零售價")[3].textContent
            };
            // 傳回 PHP 存檔
            axios.post(window.location.href, data).then(() => {
                alert("更新成功！");
                location.reload();
            });
        }).catch(e => { alert("連線失敗：" + e.message); });
    }
    </script>
</body>
</html>