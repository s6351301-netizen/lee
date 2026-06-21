<?php
// 1. 啟動 Session 驗證機制
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 資料庫連線設定 (請根據您的 MySQL 資料庫實際資訊修改)
$host    = 'localhost';
$db      = 'your_database_name'; // 請替換為您的資料庫名稱
$user    = 'root';               // 請替換為您的資料庫帳號
$pass    = '';                   // 請替換為您的資料庫密碼
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 開啟錯誤異常模式
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 設定預設關聯陣列模式
    PDO::ATTR_EMULATE_PREPARES   => false,                  // 關閉模擬預處理，提升安全性
];

try {
    // 建立 PDO 資料庫連線物件 $pdo
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 若連線失敗，停止執行並顯示錯誤訊息
    die("資料庫連線失敗: " . $e->getMessage());
}

// =========================================================================
// 3. 後台權限檢查與強制轉址
// =========================================================================

// 取得當前執行的檔案名稱 (例如：index.php 或 login.php)
$current_page = basename($_SERVER['PHP_SELF']);

// 如果當前頁面不是 login.php，才需要檢查是否登入
if ($current_page !== 'login.php') {
    
    // 檢查 Session 中是否存在管理員登入紀錄 (請確認您登入成功時寫入的 Key 是否為 'admin_user')
    if (!isset($_SESSION['admin_user'])) {
        
        // 輸出提示文字並利用 JavaScript 導向至登入頁面
        echo "<script>
            alert('後台網頁需登入，請按此輸入帳號密碼做登入。');
            window.location.href = 'login.php';
        </script>";
        
        // 確保轉址後，後續的後台原始碼與敏感資料不會被載入執行
        exit;
    }
}
?>