<?php
// 請務必填寫您 Python 的完整執行檔路徑
$pythonExe = "C:\\Users\\user\\AppData\\Local\\Python\\pythoncore-3.14-64\\python.exe";
$scriptPath = __DIR__ . "\\land_price.py";

// 執行指令 (捕捉錯誤訊息)
$cmd = escapeshellcmd("$pythonExe $scriptPath 1040 0000 2>&1");
$result = shell_exec($cmd);

// 輸出結果
if (strpos($result, "Error") !== false) {
    echo "<h3>抓取失敗，錯誤訊息如下：</h3><pre>$result</pre>";
} else {
    echo "<h3>抓取成功！資料如下：</h3><pre>$result</pre>";
}
?>