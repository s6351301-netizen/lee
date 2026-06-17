<?php
require_once 'api_account-members.php';

$id = $_GET['id'] ?? 0;
// 簡單的查詢：在 API 裡可以封裝一個 getMemberById($conn, $id) 函數
$sql = "SELECT * FROM account_members_view WHERE account_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<body>
    <h2>會員完整詳細資料</h2>
    <table border="1" style="width: 100%;">
        <?php 
        if ($row) {
            foreach ($row as $key => $value) {
                // 關鍵步驟：過濾掉 password 欄位
                if ($key === 'password') continue; 
                
                echo "<tr>
                        <th style='text-align:left; background:#eee;'>$key</th>
                        <td>" . htmlspecialchars($value ?? '') . "</td>
                      </tr>";
            }
        } else {
            echo "找不到此會員資料。";
        }
        ?>
    </table>
    <button onclick="window.close()">關閉視窗</button>
</body>
</html>