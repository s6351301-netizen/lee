<?php
session_start();

// 資料庫連線
$mysqli = new mysqli("localhost", "root", "", "lee");
if ($mysqli->connect_error) {
    die("資料庫連線失敗: " . $mysqli->connect_error);
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $captcha = trim($_POST['captcha']);

    // 驗證碼檢查
    if ($captcha != $_SESSION['captcha']) {
        $error = "驗證碼錯誤";
    } else {
        // 查詢帳號
        $stmt = $mysqli->prepare("SELECT password, status FROM account WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $error = "帳號錯誤";
        } else {
            $row = $result->fetch_assoc();
            if ($row['status'] != 1) {
                $error = "帳號未啟用";
            } elseif (!password_verify($password, $row['password'])) {
                $error = "密碼錯誤";
            } else {
                // 登入成功
                header("Location: backend/index.html");
                exit();
            }
        }
        $stmt->close();
    }
}

// 忘記帳號功能
if (isset($_POST['forgot'])) {
    $error = "請聯絡管理員找回帳號";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>會員登入</title>
</head>
<body>
    <h2>會員登入</h2>
    <form method="post" action="">
        <label>帳號(Email):</label><br>
        <input type="text" name="email" required><br><br>

        <label>密碼:</label><br>
        <input type="password" name="password" required><br><br>

        <label>驗證碼:</label><br>
        <img src="captcha.php?<?php echo rand(); ?>" alt="CAPTCHA" 
             onclick="this.src='captcha.php?'+Math.random();" style="cursor:pointer;"><br>
        <small>點擊圖片可刷新驗證碼</small><br>
        <input type="text" name="captcha" required><br><br>

        <input type="submit" value="登入">
        <input type="submit" name="forgot" value="忘記帳號"><br><br>

        <a href="register.php">會員註冊</a>
    </form>

    <?php if ($error != ""): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>
