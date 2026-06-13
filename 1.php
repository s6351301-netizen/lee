<?php

/**
 * 透過中華郵政 API 查詢地址的郵遞區號
 *
 * @param string $address 完整的台灣地址 (例如: 台北市中正區重慶南路一段122號)
 * @return array|null 成功回傳包含郵遞區號與標準地址的陣列，失敗回傳 null
 */
function getZipCode($address) {
    // 中華郵政 3+3碼郵遞區號查詢 API 網址
    $url = "https://ezpost.post.gov.tw/ChII/Address/GetZipCode";

    // 建立 cURL 請求
    $ch = curl_init();

    // 準備 Post 變數 (API 需要 address 參數)
    $postData = http_build_query([
        'address' => $address
    ]);

    // 設定 cURL 選項
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 設定逾時時間
    
    // 如果本地環境沒有設定 SSL 憑證，可取消下行註解（正式環境建議開啟）
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // 執行請求並取得回應
    $response = curl_exec($ch);

    // 檢查是否有 cURL 錯誤
    if (curl_errno($ch)) {
        // Log 錯誤訊息或自行處理
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    // 解析 JSON 回應
    $result = json_decode($response, true);

    // 檢查 API 回傳狀態與資料結構
    // 中華郵政 API 成功時通常會回傳包含 'zipCode' 的 JSON
    if ($result && isset($result['zipCode'])) {
        return [
            'zip_code' => $result['zipCode'],                 // 6碼郵遞區號
            'zip_3'    => substr($result['zipCode'], 0, 3),   // 前3碼 (行政區)
            'zip_3_2'  => substr($result['zipCode'], 3, 3),   // 後3碼 (投遞段)
            'norm_addr'=> $result['normAddr'] ?? ''           // 官方標準化後的地址
        ];
    }

    return null;
}

// === 測試使用範例 ===

$testAddress = "台北市中正區重慶南路一段122號"; // 總統府地址
$zipData = getZipCode($testAddress);

echo "<h3>郵遞區號查詢測試</h3>";
echo "原本輸入的地址: " . htmlspecialchars($testAddress) . "<br><br>";

if ($zipData) {
    echo "<b>完整郵遞區號 (3+3):</b> " . $zipData['zip_code'] . "<br>";
    echo "<b>主要郵遞區號 (前3碼):</b> " . $zipData['zip_3'] . "<br>";
    echo "<b>標準地址:</b> " . htmlspecialchars($zipData['norm_addr']) . "<br>";
} else {
    echo "無法查詢該地址的郵遞區號，請檢查地址是否正確或網路是否連通。";
}