<?php
// 1. 資料庫連線設定 (直接整合，確保連線變數存在)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("資料庫連線失敗: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 2. 撈取 account_members_view 資料
$sql = "SELECT * FROM account_members_view";
$result = $conn->query($sql);
$data_list = [];
if ($result) {
    $data_list = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "SQL 錯誤: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>會員基本資料</title>
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #143622; color: white; }
    </style>
</head>
<body>
    <h1>宗親會員基本資料 (檢視表)</h1>
    <?php if (count($data_list) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>帳號ID</th><th>現在會員號</th><th>姓名</th><th>世代</th><th>房號</th>
                    <th>世祖</th><th>手機</th><th>地址</th><th>生存狀態</th><th>備註</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_list as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['account_id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['account_new_member'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['account_name'] ?? $row['member_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['generation'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['number_of_houses'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['emperor_shizu'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile_phone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['address'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['living_status'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['member_remarks'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>目前資料庫中沒有資料，或檢視表名稱錯誤。</p>
        <p>請檢查您的 phpMyAdmin 中，檢視表是否真的叫 `account_members_view`</p>
    <?php endif; ?>
</body>
</html>