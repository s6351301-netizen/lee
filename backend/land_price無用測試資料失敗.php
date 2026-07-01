<?php
// 此為直接請求查詢結果的 API 接口
$url = "https://landquery.taichung.gov.tw/query/rwd/valueprice.jsp";

$postData = [
    'action'   => 'Query1',
    'SiteArea' => 'BE-11',
    'R48'      => '3652',
    'NUM1'     => '1035', // 您的地號
    'NUM2'     => '0000',
    'Type'     => '1',
    'btnQuery' => '查詢'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// 關鍵：將回應內容儲存並直接輸出，讓我看看伺服器到底回傳了什麼
$response = curl_exec($ch);
curl_close($ch);

// 將內容寫入檔案供我們檢查
file_put_contents('debug_raw.html', $response);

echo "已執行查詢，請打開目錄下的 debug_raw.html 查看內容。";
?>