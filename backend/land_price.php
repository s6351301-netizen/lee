<?php
// ==========================================
// 1. 資料庫連線設定 (PDO)
// ==========================================
$host = 'localhost';
$db   = 'lee';
$user = 'root';
$pass = '';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("資料庫連線失敗: " . $e->getMessage());
}

// ==========================================
// 2. 待查詢清單
// ==========================================
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

// ==========================================
// 3. 準備寫入 SQL (ON DUPLICATE KEY 避免重複錯誤)
// ==========================================
$sql = "INSERT INTO land_price 
        (record_date, section_name, land_number, posted_land_value, declared_land_value, land_area) 
        VALUES (:date, :section, :number, :posted, :declared, :area)
        ON DUPLICATE KEY UPDATE 
        posted_land_value = VALUES(posted_land_value), 
        declared_land_value = VALUES(declared_land_value), 
        land_area = VALUES(land_area)";

$stmt = $pdo->prepare($sql);

// ==========================================
// 4. 執行迴圈處理
// ==========================================
foreach ($search_list as $item) {
    echo "正在處理: {$item['section']} {$item['no']} ... ";

    // 這裡呼叫您的爬取邏輯 (需實作 cURL)
    $data = fetchLandData($item['section'], $item['no']);

    if ($data) {
        $stmt->execute([
            ':date'     => $data['date'],
            ':section'  => $item['section'],
            ':number'   => $item['no'],
            ':posted'   => $data['posted'],
            ':declared' => $data['declared'],
            ':area'     => $data['area']
        ]);
        echo "寫入成功。\n";
    } else {
        echo "查無資料。\n";
    }

    // 隨機暫停 2-5 秒，避免對政府伺服器造成壓力
    sleep(rand(2, 5));
}

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