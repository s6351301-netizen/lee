<?php
// 啟動 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 權限檢查：若未登入則導向
if (!isset($_SESSION['new_member'])) {
    header("Location: login.php");
    exit;
}

// 引入您的會員 API (此 API 應包含 $conn 資料庫連線)
require_once 'api_account-members.php'; 
?>

<?php
// 引入上述權限與連線設定... (請放在最上方)

// 處理新增資料請求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_task') {
    $stmt = $conn->prepare("INSERT INTO dev_tracking (creator_member_id, project_name_zh, status, dev_start_at, dev_end_at, ai_url, dev_note) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", 
        $_SESSION['new_member'], 
        $_POST['project_name'], 
        $_POST['status'], 
        $_POST['start_at'], 
        $_POST['end_at'], 
        $_POST['ai_url'], 
        $_POST['dev_note']
    );
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 撈取資料
$result = $conn->query("SELECT * FROM dev_tracking ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>李武略家族 - 開發進度追蹤</title>
    <style>
    /* 全局設定：白色背景，深色文字 */
    body { 
        background-color: #ffffff; 
        color: #333333; 
        font-family: sans-serif; 
        padding: 20px; 
        line-height: 1.6;
    }
    
    .main-container { 
        max-width: 1000px; 
        margin: auto; 
    }

    h1 { color: #000000; border-bottom: 2px solid #333; padding-bottom: 10px; }

    /* 表格樣式：簡單邊框 */
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th { background: #f8f8f8; color: #000; padding: 12px; border: 1px solid #ddd; text-align: left; }
    td { padding: 12px; border: 1px solid #ddd; }

    /* 狀態顏色：使用文字顏色區隔 */
    .status-tag { font-weight: bold; }
    .status-todo { color: #666; }
    .status-in_progress { color: #d97706; }
    .status-testing { color: #2563eb; }
    .status-completed { color: #059669; }

    /* 表單與按鈕：簡單乾淨 */
    form { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; background: #fafafa; }
    input, select { padding: 8px; margin-right: 10px; border: 1px solid #ccc; }
    .btn-add { background: #333; color: #fff; padding: 8px 15px; border: none; cursor: pointer; }
    .btn-add:hover { background: #000; }
</style>
</head>
<body>
    <div class="main-container">
        <h1>開發進度追蹤</h1>
        <p>歡迎回來，<?php echo $_SESSION['name']; ?> 宗親。</p>

        <form method="POST">
            <input type="hidden" name="action" value="add_task">
            <input type="text" name="project_name" placeholder="專案名稱" required>
            <select name="status">
                <option value="todo">待辦 (Todo)</option>
                <option value="in_progress">進行中</option>
                <option value="testing">測試中</option>
            </select>
            <button type="submit" class="btn-add">+ 新增進度</button>
        </form>

        <table>
            <tr>
                <th>專案名稱</th>
                <th>狀態</th>
                <th>建立者</th>
                <th>開發筆記</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['project_name_zh']); ?></td>
                <td><span class="status-tag"><?php echo $row['status']; ?></span></td>
                <td><?php echo $row['creator_member_id']; ?></td>
                <td><?php echo nl2br($row['dev_note']); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>