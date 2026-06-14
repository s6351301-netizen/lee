<?php
session_start();

// 設定正確的台灣時區
date_default_timezone_set('Asia/Taipei');

// 資料庫連線 (使用 mysqli)
$mysqli = new mysqli("localhost", "root", "", "lee");
if ($mysqli->connect_error) {
    die("資料庫連線失敗: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 忘記密碼表單
    if (isset($_POST['forgot'])) {
        $error = "請發 E-MAIL 聯絡管理員找回帳號";
    }
    // 登入表單
    elseif (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $captcha = trim($_POST['captcha']);

        // 驗證碼安全檢查
        if (empty($_SESSION['captcha']) || strtolower($captcha) != strtolower($_SESSION['captcha'])) {
            $error = "驗證碼錯誤";
        } else {
            // 💡 修正點：SQL 順便撈出 `new_member` 欄位，確保後續能完全對應所有欄位資料
            $stmt = $mysqli->prepare("SELECT `new_member`, `name`, `password`, `status` FROM `account` WHERE `email` = ?");
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
                    // 💡 關鍵修正：將登入成功的所有關鍵 ID 與對應值全部導入 Session 中
                    $_SESSION['new_member'] = $row['new_member']; // 會員編號/登入ID
                    $_SESSION['name']       = $row['name'];       // 會員姓名
                    $_SESSION['email']      = $email;             // 登入Email
                    $_SESSION['status']     = $row['status'];     // 帳號狀態

                    // 登入成功，清除驗證碼防重複使用
                    unset($_SESSION['captcha']);

                    // 導向後台主頁 (此處會完美帶入剛才撈取的對應 ID 值)
                    header("Location: backend/index.php");
                    exit();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員登入</title>
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; margin: 40px; background-color: #f9f9f9; }
        .login-box { max-width: 400px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #1a5276; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; padding: 10px; background-color: #1a5276; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        input[type="submit"]:hover { background-color: #113f5c; }
        .btn-forgot { background: none; border: none; color: #c0392b; text-decoration: underline; cursor: pointer; padding: 0; font-size: 14px; margin-top: 15px; }
        .captcha-area { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .captcha-img { cursor: pointer; height: 40px; border: 1px solid #ccc; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>會員登入</h2>

    <form method="post" action="">
        <label>帳號 (Email):</label>
        <input type="text" name="email" required autocomplete="username">

        <label>密碼:</label>
        <input type="password" name="password" required autocomplete="current-password">

        <label>驗證碼:</label>
        <div class="captcha-area">
            <input type="text" name="captcha" placeholder="請輸入圖中驗證碼" required style="margin:0; width:60%;">
            <img src="captcha.php?<?php echo rand(); ?>" alt="CAPTCHA" class="captcha-img"
                 onclick="this.src='captcha.php?'+Math.random();" title="點擊圖片可刷新驗證碼">
        </div>

        <input type="submit" name="login" value="登入">
    </form>

    <div style="display: flex; justify-content: space-between; align-items: center;">
        <form method="post" action="">
            <button type="submit" name="forgot" class="btn-forgot">忘記密碼？</button>
        </form>
        
        <a href="register.php" style="font-size: 14px; margin-top: 15px; color: #2980b9;">會員註冊</a>
    </div>

    <?php if ($error != ""): ?>
        <p style="color:red; text-align:center; font-weight:bold; margin-top:15px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</div>

</body>
</html>