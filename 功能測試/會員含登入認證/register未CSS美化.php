<?php
session_start();

// 資料庫連線
$mysqli = new mysqli("localhost", "root", "", "lee");
if ($mysqli->connect_error) {
    die("資料庫連線失敗: " . $mysqli->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_member = trim($_POST['new_member']);
    $old_member = trim($_POST['old_member']);
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = trim($_POST['role']);
    $status = isset($_POST['status']) && $_POST['status'] !== "" ? trim($_POST['status']) : "1";
    $discontinued_date = trim($_POST['discontinued_date']);
    $remarks = trim($_POST['remarks']);

    // 驗證必填欄位
    if ($password !== $confirm_password) {
        $error = "兩次密碼不一致";
    } elseif ($name === "" || $gender === "" || $email === "" || $role === "") {
        $error = "必填欄位不可空白";
    } else {
        // 如果 new_member 沒填，強制以 ID 自動代入
        if ($new_member === "") {
            $result = $mysqli->query("SELECT MAX(id) AS maxid FROM account");
            $row = $result->fetch_assoc();
            $new_member = $row['maxid'] + 1;
        }

        // 檢查帳號是否已存在
        $stmt = $mysqli->prepare("SELECT id FROM account WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "帳號已存在";
        } else {
            // 使用 password_hash 加密密碼
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // 系統自動帶入今天日期時間
            $join_date = date("Y-m-d H:i:s");

            // 新增帳號
            $stmt = $mysqli->prepare("INSERT INTO account 
                (new_member, old_member, name, gender, email, password, role, join_date, status, discontinued_date, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", 
                $new_member, $old_member, $name, $gender, $email, $hash, $role, $join_date, $status, $discontinued_date, $remarks);

            if ($stmt->execute()) {
                //$success = "註冊成功，請前往登入頁面";
                $success = '註冊成功，請前往 <a href="login.php">會員登入</a>頁面';
            } else {
                $error = "註冊失敗: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>會員註冊</title>
    <style>
        table { border-collapse: collapse; width: 600px; }
        td { padding: 8px; }
        .label { width: 200px; text-align: right; font-weight: bold; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h2>會員註冊</h2>

    <?php if ($error != ""): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success != ""): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <table>
            <tr><td class="label">現在會員號 (必填):</td><td><input type="text" name="new_member"></td></tr>
            <tr><td class="label">前會員號 (選填):</td><td><input type="text" name="old_member"></td></tr>
            <tr><td class="label">姓名 (必填):</td><td><input type="text" name="name" required></td></tr>
            <tr><td class="label">性別 (必填):</td>
                <td><select name="gender" required>
                    <option value="">請選擇</option>
                    <option value="男">男</option>
                    <option value="女">女</option>
                </select></td></tr>
            <tr><td class="label">電子信箱 (必填):</td><td><input type="email" name="email" required></td></tr>
            <tr><td class="label">密碼 (必填):</td><td><input type="password" name="password" required></td></tr>
            <tr><td class="label">再次確認密碼 (必填):</td><td><input type="password" name="confirm_password" required></td></tr>
            <tr><td class="label">權限 (必填):</td>
                <td><select name="role" required>
                    <option value="">請選擇</option>
                    <option value="admin">管理者</option>
                    <option value="user">派下員</option>
                    <option value="clan">宗親</option>
                </select></td></tr>
            <tr><td class="label">帳號狀態 (選填):</td>
                <td><select name="status">
                    <option value="1">使用中</option>
                    <option value="0">停用</option>
                </select></td></tr>
            <tr><td class="label">停用日期 (選填):</td><td><input type="date" name="discontinued_date"></td></tr>
            <tr><td class="label">備註 (選填):</td><td><input type="text" name="remarks"></td></tr>
            <tr><td></td><td><input type="submit" value="註冊"></td></tr>
        </table>
    </form>

    <a href="login.php">會員登入</a>
</body>
</html>
