<?php
// 取得電腦系統日期 (西元)
$today = date("Y-m-d");

// 呼叫 Node.js lunar-javascript 套件
$lunar_output = shell_exec("node -e \"const { Solar } = require('lunar-javascript'); 
let [y,m,d]='$today'.split('-').map(Number); 
let solar=Solar.fromYmd(y,m,d); 
console.log(solar.getLunar().toFullString());\"");

// 顯示結果
echo "西元日期: " . $today . "<br>";
echo "農曆日期: " . htmlspecialchars($lunar_output);
?>
