<?php
// 宣告回傳格式為 JSON，並支援 UTF-8 中文字
header('Content-Type: application/json; charset=utf-8');

// 安全檢查：只允許 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '無效的請求方法']);
    exit;
}

// 1. 接收前端 Fetch 送來的 JSON 原始資料
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

// 檢查參數是否存在
if (!isset($data['finalAngle'])) {
    echo json_encode(['success' => false, 'message' => '缺少驗證參數']);
    exit;
}

$angle = intval($data['finalAngle']);

// 2. 將角度標準化在 0 ~ 359 度之間
$angle = $angle % 360;
if ($angle < 0) {
    $angle += 360;
}

// 3. 設定容許的肉眼微調誤差（正負 5 度以內都算合格）
$tolerance = 5; 

$isCorrect = false;
// 如果角度接近 0 度 (正上方) 或接近 360 度 (繞一圈回到正上方)
if ($angle <= $tolerance || $angle >= (360 - $tolerance)) {
    $isCorrect = true;
}

// 4. 回傳最終審查結果給前端 JS
if ($isCorrect) {
    echo json_encode([
        'success' => true, 
        'message' => '驗證成功'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => '角度不正確',
        'debug_received_angle' => $angle // 回傳目前角度給前端，方便除錯
    ]);
}
exit;