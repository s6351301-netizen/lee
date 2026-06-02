<!-- 
這是會員登入頁面，包含了帳號、密碼和驗證碼的輸入欄位。
使用者輸入後會進行驗證，成功則導向後台首頁，失敗則顯示錯誤訊息。
同時提供忘記帳號的功能，點擊後會提示使用者聯絡管理員。
另外，頁面上還有一個連結可以前往會員註冊頁面。
頁面頂部還包含了網站的標題和一個 logo 圖片，以及前台首頁和後台首頁的連結。
注意：這段程式碼中包含了 phpinfo() 函數，這會顯示 PHP 的配置信息，建議在正式環境中移除或註解掉這行程式碼，以免洩露敏感資訊。
另外，請確保在使用這段程式碼前已經建立了相應的資料庫和帳號資料表，並且已經設定好驗證碼的生成和存儲機制。
建議在正式環境中加強安全措施，例如使用 HTTPS、限制登入嘗試次數、使用更強的密碼哈希算法等，以保護使用者的帳號安全。
最后，請確保在使用這段程式碼前已經安裝並啟用了相應的 PHP 擴展，例如 mysqli 和 session，以確保程式碼能夠正常運行。
建議在正式環境中對錯誤訊息進行適當的處理，例如記錄錯誤日誌而不是直接顯示給使用者，以避免洩露敏感資訊。
總之，這段程式碼提供了一個基本的會員登入功能，但在實際使用中需要根據具體需求進行適當的修改和加強安全措施，以確保使用者的帳號安全和系統的穩定運行。
測試圖型功能是否開啟  前後都需要PHP開頭與結尾   phpinfo();  ?> 
 --> 
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
        // 查詢帳號是否存在
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
    $error = "請發E-MAIL聯絡管理員找回帳號";
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
        <!-- 點擊圖片可刷新驗證碼 -->
        <img src="captcha.php" alt="CAPTCHA" onclick="this.src='captcha.php?'+Math.random();" style="cursor:pointer;"><br>
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
