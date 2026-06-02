<?php
session_start();

// 建立一個簡單的隨機數字
$code = rand(1000, 9999);
$_SESSION['captcha'] = $code;

// 建立圖片
$width = 100;
$height = 40;
$image = imagecreate($width, $height);

// 背景顏色
$bg = imagecolorallocate($image, 255, 255, 255);

// 文字顏色
$text_color = imagecolorallocate($image, 139, 0, 0);

// 畫文字
imagestring($image, 5, 25, 10, $code, $text_color);

// 干擾線顏色 (黑色)
$line_color = imagecolorallocate($image, 0, 0, 0);

// 畫干擾線
for ($i = 0; $i < 5; $i++) {
    imageline($image, 0, rand()%$height, $width, rand()%$height, $line_color);
}

/*畫干擾線與文字同顏色
for ($i = 0; $i < 5; $i++) {
    imageline($image, 0, rand()%$height, $width, rand()%$height, $text_color);
}
   */ 

// 加入雜點
for ($i = 0; $i < 50; $i++) {
    imagesetpixel($image, rand()%$width, rand()%$height, $text_color);
}


// 輸出 PNG
header("Content-type: image/png");
imagepng($image);
imagedestroy($image);



?>
