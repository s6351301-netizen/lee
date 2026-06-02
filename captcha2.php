<?php
session_start();

// 產生隨機四位數字或字母
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_code = '';
for ($i = 0; $i < 4; $i++) {
    $captcha_code .= $chars[rand(0, strlen($chars)-1)];
}
$_SESSION['captcha'] = $captcha_code;

// 建立圖片
$width = 120;
$height = 50;
$image = imagecreate($width, $height);

// 背景顏色
$bg_color = imagecolorallocate($image, 255, 255, 255);

// 干擾線顏色
$line_color = imagecolorallocate($image, 200, 200, 200);

// 畫干擾線
for ($i = 0; $i < 5; $i++) {
    imageline($image, 0, rand()%$height, $width, rand()%$height, $line_color);
}

// 加入雜點
for ($i = 0; $i < 100; $i++) {
    $dot_color = imagecolorallocate($image, rand(0,255), rand(0,255), rand(0,255));
    imagesetpixel($image, rand()%$width, rand()%$height, $dot_color);
}

// 文字顏色
$text_color = imagecolorallocate($image, 0, 0, 0);

// 使用 TrueType 字型繪製文字
$font = __DIR__ . '/arial.ttf'; // 請放一個 TTF 字型檔在同目錄，例如 arial.ttf
imagettftext($image, 20, rand(-10,10), 20, 35, $text_color, $font, $captcha_code);

// 輸出 PNG
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);
?>
