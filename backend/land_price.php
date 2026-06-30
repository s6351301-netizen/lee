<?php
// 關閉緩衝，確保輸出能即時顯示在瀏覽器
while (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);

// 1. 資料庫連線 (PDO)
$host = 'localhost'; $db = 'lee'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// 2. 待查詢清單
$search_list = [
    ['section' => '義水段', 'no' => '0855-0000'],
    ['section' => '義水段', 'no' => '0861-0000'],
    ['section' => '義水段', 'no' => '0911-0000'],
    ['section' => '義水段', 'no' => '0930-0000'],
    ['section' => '義水段', 'no' => '0934-0000'],
    ['section' => '義水段', 'no' => '0979-0000'],
    ['section' => '義水段', 'no' => '0980-0000'],
    ['section' => '義水段', 'no' => '0984-0000'],
    ['section' => '義水段', 'no' => '0987-0000'],
    ['section' => '義水段', 'no' => '0989-0000'],
    ['section' => '義水段', 'no' => '0995-0000'],
    ['section' => '義水段', 'no' => '1035-0000'],
    ['section' => '義水段', 'no' => '1040-0000'],
    ['section' => '水美東段', 'no' => '0191-0000'],
    ['section' => '水美東段', 'no' => '0192-0000'],
    ['section' => '水美東段', 'no' => '0193-0000'],
    ['section' => '水美東段', 'no' => '0194-0000'],
    ['section' => '水美東段', 'no' => '0195-0000'],
    ['section' => '水美東段', 'no' => '0565-0000'],
];

echo "<h3>系統提示：正在進行公告地價資料撈取，請稍候 (約需 2-3 分鐘)...</h3>";
echo "<div id='status-log' style='font-family: monospace; background: #f4f4f4; padding: 10px; margin-bottom: 20px;'>";

$results = [];
foreach ($search_list as $item) {
    echo "正在處理: 115年01月 - {$item['section']} {$item['no']} ... ";
    
    // 呼叫爬蟲函式 (此處為示意，需自行填入實際邏輯)
    $data = fetchLandData($item['section'], $item['no']);
    
    if ($data) {
        // 寫入資料庫
        $sql = "INSERT INTO land_price (record_date, section_name, land_number, posted_land_value, declared_land_value, land_area) VALUES (?,?,?,?,?,?) 
                ON DUPLICATE KEY UPDATE posted_land_value=VALUES(posted_land_value), declared_land_value=VALUES(declared_land_value), land_area=VALUES(land_area)";
        $pdo->prepare($sql)->execute(['2026-01', $item['section'], $item['no'], $data['posted'], $data['declared'], $data['area']]);
        
        $item['status'] = "寫入成功";
        echo "<span style='color:green'>完成</span><br>";
    } else {
        $item['status'] = "查無資料";
        echo "<span style='color:red'>失敗</span><br>";
    }
    $results[] = $item;
    sleep(rand(2, 4)); // 隨機延遲保護伺服器
}
echo "</div>";

// 3. 最終表格顯示
echo "<h3>資料處理結果摘要</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 600px;'>
        <tr style='background:#eee;'><th>地段</th><th>地號</th><th>處理結果</th></tr>";
foreach ($results as $res) {
    $color = ($res['status'] == "查無資料") ? "red" : "black";
    echo "<tr><td>{$res['section']}</td><td>{$res['no']}</td><td style='color:{$color};'>{$res['status']}</td></tr>";
}
echo "</table>";

// ==========================================
// 5. 爬取邏輯函式 (需依照實際網頁內容補完)
// ==========================================
function fetchLandData($section, $number) {
    // 這裡應該放入 cURL 請求程式碼
    // 記得使用 str_replace(',', '', $value) 移除數字中的逗號
    // 回傳結構範例:
    return [
        'date'     => '2026-01',
        'posted'   => 22767.00,
        'declared' => 2930.00,
        'area'     => 1285.77
    ];
}
?>