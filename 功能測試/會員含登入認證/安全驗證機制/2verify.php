<?php
// 設定回傳格式為 JSON 
header('Content-Type: application/json; charset=utf-8');

// 限制只允許 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

// 1. 接收前端 Fetch 發送的原始 JSON 資料
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$captchaToken = $data['captchaToken'] ?? '';

// 檢查參數是否齊全
if (empty($captchaToken)) {
    echo json_encode(['success' => false, 'message' => '缺少驗證碼 Token']);
    exit;
}

// 2. 準備 Cloudflare 私鑰 (此為官方提供的必過測試金鑰)
$secretKey = "1x0000000000000000000000000000000AA";

// 3. 封裝要發給 Cloudflare 的比對資料
$postData = [
    'secret'   => $secretKey,
    'response' => $captchaToken,
    'remoteip' => $_SERVER['REMOTE_ADDR'] // 可選：提供用戶的 IP 增加判斷準確度
];

// 4. 使用 cURL 發送 POST 請求給 Cloudflare API
$ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 設定逾時時間

$responseRaw = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => '與驗證伺服器連線失敗: ' . $curlError]);
    exit;
}

// 5. 解析 Cloudflare 回傳的 JSON 結果
$result = json_decode($responseRaw, true);

if (isset($result['success']) && $result['success'] === true) {
    
    // 【驗證通過】在這裡安全地進行你的業務邏輯（例如檢查資料庫密碼）
    if ($username === "admin" && $password === "123456") {
        echo json_encode(['success' => true, 'message' => '登入成功！']);
    } else {
        echo json_encode(['success' => false, 'message' => '帳號或密碼錯誤']);
    }

} else {
    // 【驗證失敗】可能是黑客偽造的 Token，或是機器人行為
    $errorCodes = isset($result['error-codes']) ? implode(', ', $result['error-codes']) : '未知錯誤';
    echo json_encode([
        'success' => false, 
        'message' => '防機器人驗證未通過，拒絕請求。',
        'debug_info' => $errorCodes
    ]);
}
exit;