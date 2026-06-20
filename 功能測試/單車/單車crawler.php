<?php
// 使用官方的 API 位址 (這是真實的數據源)
$url = 'https://apis.youbike.com.tw/api/v1/station/rus/taichung';

// 設定模擬瀏覽器請求
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
    ]
];
$context = stream_context_create($opts);

// 抓取並解析 JSON
$json_data = file_get_contents($url, false, $context);
$data = json_decode($json_data, true);

// 過濾出大甲區的資料
if (isset($data['data'])) {
    $dajia = array_filter($data['data'], function($item) {
        return $item['sarea'] == '大甲區';
    });
    // 存入 stations.json
    file_put_contents('stations.json', json_encode(array_values($dajia), JSON_UNESCAPED_UNICODE));
    echo "爬蟲成功！已抓取 " . count($dajia) . " 筆資料。";
} else {
    echo "抓取失敗，API 可能已更新或拒絕連線。";
}
?>