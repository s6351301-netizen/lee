<?php
session_start();
// 清除所有 Session
session_unset();
session_destroy();

// 導向到根目錄的 login.php
header("Location: /login.php");
exit();
?>
