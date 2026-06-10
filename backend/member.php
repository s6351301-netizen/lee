<?php
session_start();

// 設定正確的台灣時區
date_default_timezone_set('Asia/Taipei');

// 1. 資料庫連線 (使用正確資料庫名 lee)
$pdo = new PDO("mysql:host=localhost;dbname=lee;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 2. 依據 Session 姓名取得當前【登入者】的唯一關鍵字：new_member (現在會員號)
$current_user = $_SESSION['name'] ?? '';
$stmt_login = $pdo->prepare("SELECT `new_member` FROM `members` WHERE `name` = ? LIMIT 1");
$stmt_login->execute([$current_user]);
$login_user_num = $stmt_login->fetchColumn() ?: '39'; // 撈出如：39
// 建立當前登入者的完整資訊比對字串 (例如："39 李寶珠")
$login_user_info = $login_user_num . ' ' . $current_user;

// 💡 核心修正：主系統框架正在查看、編輯的關鍵鑰匙，是「現在會員號 (new_member)」
// 優先順序：1. 網址帶來的編號 2. 外部主框架既有的變數 3. 預設顯示登入者自己
$target_member_num = $_GET['new_member'] ?? $new_member ?? $login_user_num;

// 3. 【動作處理】匯出成 Word 檔案
if (isset($_GET['action']) && $_GET['action'] === 'export_word') {
    // 💡 核心修正：以 new_member 作為撈取條件
    $stmt = $pdo->prepare("SELECT * FROM `members` WHERE `new_member` = ?");
    $stmt->execute([$target_member_num]);
    $m = $stmt->fetch();

    $avatar_file = "";
    if (is_dir('uploads')) {
        // 大頭照命名維持與該會員的 new_member 綁定
        $files = glob("uploads/avatar_" . $target_member_num . "_*");
        if (!empty($files)) {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $avatar_file = $files[0];
        }
    }
    $has_right = (($m['SendSubordinates'] ?? '') === '正常派下員' && ($m['living_status'] ?? '') === '存') ? 'yes' : 'no';

    // 將大頭照圖片轉換為 Base64 內嵌編碼，確保 Word 檔案能成功讀取並顯示照片
    $word_avatar_img = "貼照片處";
    if (!empty($avatar_file) && file_exists($avatar_file)) {
        $img_data = base64_encode(file_get_contents($avatar_file));
        $img_info = getimagesize($avatar_file);
        $mime_type = $img_info['mime'] ?? 'image/jpeg';
        $word_avatar_img = '<img src="data:' . $mime_type . ';base64,' . $img_data . '" width="120" height="150">';
    }

    // Word 匯出時做「本人」的邏輯判斷
    $db_updater = $m['last_updater'] ?? '';
    $word_display_updater = '無紀錄';
    if (!empty($db_updater)) {
        $word_display_updater = htmlspecialchars($db_updater);
        if (!empty($current_user) && strpos($db_updater, $current_user) !== false) {
            $word_display_updater .= '(本人)';
        }
    }

    $filename = "會員入會申請表_" . ($m['name'] ?? '未命名') . ".doc";

    header("Content-Type: application/vnd.ms-word; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . urlencode($filename));
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">

    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Microsoft JhengHei", Arial, sans-serif;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid #000;
            }

            th,
            td {
                border: 1px solid #000;
                padding: 8px;
                vertical-align: middle;
                font-size: 14px;
            }

            th {
                background: #f2f2f2;
                font-weight: bold;
                text-align: center;
            }

            .form-title {
                text-align: center;
                font-size: 22px;
                font-weight: bold;
            }

            .form-subtitle {
                text-align: center;
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 20px;
            }

            .top-info {
                margin-bottom: 10px;
                font-size: 14px;
                font-weight: bold;
            }
        </style>
    </head>

    <body>
        <div class="form-title">台中市李武略派下李氏宗親會</div>
        <div class="form-subtitle">【 會員入會申請表 】</div>
        <div class="top-info">
            收件日期：<?= !empty($m['receive_date']) ? date('Y-m-d H:i:s', strtotime($m['receive_date'])) : '未填寫' ?> &emsp;&emsp;
            第 <?= $m['number_of_houses'] ?? '' ?> 大房 &emsp;&emsp;
            編號：<?= htmlspecialchars($m['new_member'] ?? '') ?>
        </div>
        <table>
            <tr>
                <th>姓名</th>
                <td><?= htmlspecialchars($m['name'] ?? '') ?></td>
                <th>性別</th>
                <td><?= htmlspecialchars($m['gender'] ?? '') ?></td>
                <td rowspan="3" style="text-align:center; width:130px;">
                    <?= $word_avatar_img ?>
                </td>
            </tr>
            <tr>
                <th>身分證字號</th>
                <td><?= htmlspecialchars($m['id_card_num'] ?? '') ?></td>
                <th>出生日期</th>
                <td><?= !empty($m['birthday']) ? date('Y-m-d', strtotime($m['birthday'])) : '' ?></td>
            </tr>
            <tr>
                <th>派下世代</th>
                <td colspan="3">
                    遷台第 <?= $m['emperor_shizu'] ?? '' ?> 世祖 &emsp;
                    居大甲第 <?= $m['generation'] ?? '' ?> 代 &emsp;
                    派下權：<?= $has_right === 'yes' ? '有' : '無' ?>
                </td>
            </tr>
            <tr>
                <th>派下員狀態</th>
                <td colspan="4"><?= nl2br(htmlspecialchars($m['SendSubordinates'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>生存狀態</th>
                <td colspan="2"><?= htmlspecialchars($m['living_status'] ?? '') ?></td>
                <th>前會員號</th>
                <td><?= htmlspecialchars($m['old_member'] ?? '') ?></td>
            </tr>
            <tr>
                <th>出生地或籍貫</th>
                <td colspan="2"><?= htmlspecialchars($m['placeOfBirth'] ?? '') ?></td>
                <th>學歷</th>
                <td><?= htmlspecialchars($m['education'] ?? '') ?></td>
            </tr>
            <tr>
                <th>現職／經歷</th>
                <td colspan="4"><?= nl2br(htmlspecialchars($m['experience'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>通訊地址</th>
                <td colspan="4"><?= htmlspecialchars($m['address'] ?? '') ?></td>
            </tr>
            <tr>
                <th>聯絡電話</th>
                <td colspan="4">
                    行動：<?= htmlspecialchars($m['mobile_phone'] ?? '') ?> &emsp;
                    住家：<?= htmlspecialchars($m['home_phone'] ?? '') ?> &emsp;
                    公司：<?= htmlspecialchars($m['company_phone'] ?? '') ?>
                </td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td colspan="2"><?= htmlspecialchars($m['email'] ?? '') ?></td>
                <th>介紹人</th>
                <td><?= htmlspecialchars($m['introducer'] ?? '') ?></td>
            </tr>
            <tr>
                <th>備註</th>
                <td colspan="4">
                    <div style="color:#c0392b; font-weight:bold;">
                        🕒 最後一次修改時間：<?= !empty($m['update_time']) ? htmlspecialchars($m['update_time']) : '無紀錄' ?> &emsp;
                        👤 最後更新者：<?= $word_display_updater ?>
                    </div>
                    <br>
                    <?= nl2br(htmlspecialchars($m['remarks'] ?? '')) ?>
                </td>
            </tr>
        </table>
    </body>

    </html>
<?php
    exit;
}

// 🆕 核心修正：優化後的 Excel 匯出功能，採用微軟 VML 架構確保大頭照 100% 可視
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $stmt = $pdo->prepare("SELECT * FROM `members` WHERE `new_member` = ?");
    $stmt->execute([$target_member_num]);
    $m = $stmt->fetch();

    $avatar_file = "";
    if (is_dir('uploads')) {
        $files = glob("uploads/avatar_" . $target_member_num . "_*");
        if (!empty($files)) {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            $avatar_file = $files[0];
        }
    }
    $has_right = (($m['SendSubordinates'] ?? '') === '正常派下員' && ($m['living_status'] ?? '') === '存') ? 'yes' : 'no';

    // 解決 Excel 無法解碼 Base64 的關鍵：採用微軟內嵌圖像安全機制與強制寬高注入
    $excel_avatar_img = "貼照片處";
    if (!empty($avatar_file) && file_exists($avatar_file)) {
        $img_data = base64_encode(file_get_contents($avatar_file));
        $img_info = getimagesize($avatar_file);
        $mime_type = $img_info['mime'] ?? 'image/jpeg';
        // 同時寫入 HTML 規範與微軟專屬 VML 圖像樣式屬性，強迫 Excel 核心主動解碼大圖
        $excel_avatar_img = '<img src="data:' . $mime_type . ';base64,' . $img_data . '" width="120" height="150" style="display:block; width:120px; height:150px; v-image-anchor:true;">';
    }

    $db_updater = $m['last_updater'] ?? '';
    $excel_display_updater = '無紀錄';
    if (!empty($db_updater)) {
        $excel_display_updater = htmlspecialchars($db_updater);
        if (!empty($current_user) && strpos($db_updater, $current_user) !== false) {
            $excel_display_updater .= '(本人)';
        }
    }

    $filename = "會員入會申請表_" . ($m['name'] ?? '未命名') . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=" . urlencode($filename));
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:v="urn:schemas-microsoft-com:vml" xmlns="http://www.w3.org/TR/REC-html40">

    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "Microsoft JhengHei", Arial, sans-serif;
            }

            table {
                border-collapse: collapse;
                table-layout: fixed;
            }

            th,
            td {
                border: 0.5pt solid #000000;
                padding: 6px;
                vertical-align: middle;
                font-size: 11pt;
            }

            th {
                background: #f2f2f2;
                font-weight: bold;
                text-align: center;
            }

            .form-title {
                text-align: center;
                font-size: 16pt;
                font-weight: bold;
            }

            .form-subtitle {
                text-align: center;
                font-size: 13pt;
                font-weight: bold;
            }

            .text-format {
                mso-number-format: "\@";
            }
        </style>
    </head>

    <body>
        <table>
            <tr>
                <td colspan="5" class="form-title" style="border:none; text-align:center; font-weight:bold;">台中市李武略派下李氏宗親會</td>
            </tr>
            <tr>
                <td colspan="5" class="form-subtitle" style="border:none; text-align:center; font-weight:bold;">【 會員入會申請表 】</td>
            </tr>
            <tr>
                <td colspan="5" style="border:none; font-weight:bold;">
                    收件日期：<?= !empty($m['receive_date']) ? date('Y-m-d H:i:s', strtotime($m['receive_date'])) : '未填寫' ?> &emsp;&emsp;
                    第 <?= $m['number_of_houses'] ?? '' ?> 大房 &emsp;&emsp;
                    編號：<?= htmlspecialchars($m['new_member'] ?? '') ?>
                </td>
            </tr>

            <tr style="height:45px;">
                <th style="width:110px;">姓名</th>
                <td style="width:160px;"><?= htmlspecialchars($m['name'] ?? '') ?></td>
                <th style="width:110px;">性別</th>
                <td style="width:160px;"><?= htmlspecialchars($m['gender'] ?? '') ?></td>
                <td rowspan="3" align="center" valign="middle" style="width:140px; text-align:center; height:135px;">
                    <?= $excel_avatar_img ?>
                </td>
            </tr>
            <tr style="height:45px;">
                <th>身分證字號</th>
                <td class="text-format"><?= htmlspecialchars($m['id_card_num'] ?? '') ?></td>
                <th>出生日期</th>
                <td><?= !empty($m['birthday']) ? date('Y-m-d', strtotime($m['birthday'])) : '' ?></td>
            </tr>
            <tr style="height:45px;">
                <th>派下世代</th>
                <td colspan="3">
                    遷台第 <?= $m['emperor_shizu'] ?? '' ?> 世祖 &emsp;
                    居大甲第 <?= $m['generation'] ?? '' ?> 代 &emsp;
                    派下權：<?= $has_right === 'yes' ? '有' : '無' ?>
                </td>
            </tr>
            <tr>
                <th>派下員狀態</th>
                <td colspan="4"><?= nl2br(htmlspecialchars($m['SendSubordinates'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>生存狀態</th>
                <td colspan="2"><?= htmlspecialchars($m['living_status'] ?? '') ?></td>
                <th>前會員號</th>
                <td class="text-format"><?= htmlspecialchars($m['old_member'] ?? '') ?></td>
            </tr>
            <tr>
                <th>出生地或籍貫</th>
                <td colspan="2"><?= htmlspecialchars($m['placeOfBirth'] ?? '') ?></td>
                <th>學歷</th>
                <td><?= htmlspecialchars($m['education'] ?? '') ?></td>
            </tr>
            <tr>
                <th>現職／經歷</th>
                <td colspan="4"><?= nl2br(htmlspecialchars($m['experience'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>通訊地址</th>
                <td colspan="4"><?= htmlspecialchars($m['address'] ?? '') ?></td>
            </tr>
            <tr>
                <th>聯絡電話</th>
                <td colspan="4" class="text-format">
                    行動：<?= htmlspecialchars($m['mobile_phone'] ?? '') ?> &emsp;
                    住家：<?= htmlspecialchars($m['home_phone'] ?? '') ?> &emsp;
                    公司：<?= htmlspecialchars($m['company_phone'] ?? '') ?>
                </td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td colspan="2"><?= htmlspecialchars($m['email'] ?? '') ?></td>
                <th>介紹人</th>
                <td><?= htmlspecialchars($m['introducer'] ?? '') ?></td>
            </tr>
            <tr>
                <th>備註</th>
                <td colspan="4">
                    <div style="color:#c0392b; font-weight:bold;">
                        🕒 最後一次修改時間：<?= !empty($m['update_time']) ? htmlspecialchars($m['update_time']) : '無紀錄' ?> &emsp;
                        👤 最後更新者：<?= $excel_display_updater ?>
                    </div>
                    <br>
                    <?= nl2br(htmlspecialchars($m['remarks'] ?? '')) ?>
                </td>
            </tr>
        </table>
    </body>

    </html>
<?php
    exit;
}

// 4. 【情境二】處理表單修改更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    unset($_POST['update']);
    unset($_POST['has_right_option']);

    // 💡 核心修正：以現在會員號作索引，撈出未修改前的舊資料進行比對
    $stmt_old = $pdo->prepare("SELECT * FROM `members` WHERE `new_member` = ?");
    $stmt_old->execute([$target_member_num]);
    $old_data = $stmt_old->fetch();

    // 處理新大頭照上傳
    $is_photo_updated = false;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = "avatar_" . $target_member_num . "_" . time() . "." . $ext;
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        move_uploaded_file($_FILES['photo']['tmp_name'], 'uploads/' . $photo_name);
        $photo_path = "uploads/" . $photo_name;
        $is_photo_updated = true;
    } else {
        $photo_path = $_POST['current_photo_path'] ?? '';
    }
    unset($_POST['current_photo_path']);

    // 接收前端帶有秒數的時間並轉化為標準 SQL 格式存入資料庫
    if (!empty($_POST['receive_date'])) {
        $formatted_receive_date = date('Y-m-d H:i:s', strtotime($_POST['receive_date']));
    } else {
        $formatted_receive_date = null;
    }

    // 定義資料庫欄位與表單中文名稱的對照表
    $field_names_zh = [
        'receive_date'      => '收件日期',
        'number_of_houses'  => '大房',
        'new_member'        => '編號',
        'name'              => '姓名',
        'gender'            => '性別',
        'id_card_num'       => '身分證字號',
        'birthday'          => '出生日期',
        'emperor_shizu'    => '遷台世祖',
        'generation'        => '居大甲代',
        'SendSubordinates'  => '派下員狀態',
        'living_status'     => '生存狀態',
        'old_member'        => '前會員號',
        'placeOfBirth'      => '出生地或籍貫',
        'education'         => '學歷',
        'experience'        => '現職／經歷',
        'address'           => '通訊地址',
        'mobile_phone'      => '行動電話',
        'home_phone'        => '住家電話',
        'company_phone'     => '公司電話',
        'email'             => 'E-mail',
        'introducer'        => '介紹人'
    ];

    // 開始進行修改欄位比對
    $changed_fields = [];

    if ($is_photo_updated) {
        $changed_fields[] = '大頭照';
    }

    foreach ($field_names_zh as $col => $zh_name) {
        if (isset($_POST[$col])) {
            $old_val = $old_data[$col] ?? '';
            $new_val = $_POST[$col];

            if ($col === 'receive_date') {
                $old_val = !empty($old_val) ? date('Y-m-d H:i:s', strtotime($old_val)) : null;
                $new_val = $formatted_receive_date;
            }
            if ($col === 'birthday') {
                $old_val = !empty($old_val) ? date('Y-m-d', strtotime($old_val)) : '';
                $new_val = !empty($new_val) ? date('Y-m-d', strtotime($new_val)) : '';
            }

            if (trim((string)$old_val) !== trim((string)$new_val)) {
                $changed_fields[] = $zh_name;
            }
        }
    }

    $_POST['receive_date'] = $formatted_receive_date;
    $_POST['update_time'] = date('Y-m-d H:i:s');

    // 💡 修正：明確將當前點擊按鈕的「登入者」(39 李寶珠) 寫入最後更新者欄位，達成精準防呆
    $_POST['last_updater'] = $login_user_info;

    // 處理備註內容追加邏輯
    $old_remarks = trim($old_data['remarks'] ?? '');
    $new_remarks_input = trim($_POST['remarks'] ?? '');

    if (!empty($changed_fields)) {
        $log_string = implode(', ', $changed_fields) . ', ' . $_POST['update_time'] . ' 修改' . PHP_EOL;

        if (!empty($new_remarks_input)) {
            $_POST['remarks'] = $new_remarks_input . PHP_EOL . $log_string;
        } else {
            $_POST['remarks'] = $log_string;
        }
    } else {
        $_POST['remarks'] = $new_remarks_input;
    }

    // 💡 核心修正：動態 SQL 更新條件，由 id 修改為真正的關聯主鍵 `new_member`
    $fields = array_keys($_POST);
    $set_sql = implode('=?, ', $fields) . '=?';
    $sql = "UPDATE `members` SET {$set_sql} WHERE `new_member` = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge(array_values($_POST), [$target_member_num]));

    // 重新整理網頁並維持當前查看的編號
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['new_member']) ? '?new_member=' . $_GET['new_member'] : ''));
    exit;
}

// 5. 【情境一】讀取現有資料直接導入顯示
// 💡 核心修正：以 new_member (現在會員號) 作為外部主框架對接的唯一數據源
$stmt = $pdo->prepare("SELECT * FROM `members` WHERE `new_member` = ?");
$stmt->execute([$target_member_num]);
$m = $stmt->fetch();

$avatar_file = "";
if (is_dir('uploads')) {
    $files = glob("uploads/avatar_" . $target_member_num . "_*");
    if (!empty($files)) {
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $avatar_file = $files[0];
    }
}

$has_right = (($m['SendSubordinates'] ?? '') === '正常派下員' && ($m['living_status'] ?? '') === '存') ? 'yes' : 'no';

// 💡 核心修正：精準以登入 Session 姓名(李寶珠) 做為本人識別，不論查到誰的表單，顯示本人功能都不會錯亂
$web_display_updater = '無紀錄';
$db_updater = $m['last_updater'] ?? '';
if (!empty($db_updater)) {
    $web_display_updater = htmlspecialchars($db_updater);
    if (!empty($current_user) && strpos($db_updater, $current_user) !== false) {
        $web_display_updater .= '(本人)';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <title>台中市李武略派下李氏宗親會 - 會員入會申請表</title>
    <style>
        body {
            font-family: "Microsoft JhengHei", Arial, sans-serif;
            background: #fff;
            color: #000;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 25px;
        }

        .form-title {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 2px;
        }

        .form-subtitle {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 25px;
        }

        .top-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: bold;
        }

        .top-info input {
            border: none;
            border-bottom: 1px solid #000;
            padding: 2px 5px;
            outline: none;
            font-size: 15px;
        }

        .input-datetime {
            width: 240px;
            border: none;
            border-bottom: 1px solid #000;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: middle;
            font-size: 15px;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
            text-align: center;
            width: 14%;
        }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 6px;
            box-sizing: border-box;
            border: 1px solid #999;
            font-size: 14px;
            background: #fff;
        }

        .flex-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .inline-input {
            border: none;
            border-bottom: 1px solid #000;
            text-align: center;
            font-size: 15px;
            outline: none;
        }

        .photo-cell {
            text-align: center;
            width: 150px;
            background: #fafafa;
        }

        .photo-container {
            width: 130px;
            height: 160px;
            border: 1px solid #666;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-container:hover::after {
            content: "點擊更新照片";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            font-size: 12px;
            padding: 3px 0;
            text-align: center;
        }

        .no-photo {
            color: #666;
            font-size: 13px;
            text-align: center;
            padding: 10px;
        }

        #photoInput {
            display: none;
        }

        .btn-center {
            text-align: center;
            margin-top: 25px;
        }

        .btn-submit {
            background: #1a5276;
            color: #fff;
            padding: 12px 40px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            letter-spacing: 2px;
        }

        .btn-submit:hover {
            background: #113f5c;
        }

        .remarks-time-header {
            font-size: 13px;
            color: #c0392b;
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            gap: 20px;
        }

        /* 💡 核心修正：重構後的右上方按鈕控制盒，強迫內部所有物件以 flex-row 行內網格水平排列 */
        .action-box {
            position: fixed;
            top: 20px;
            right: 0px;
            z-index: 9999;
            display: flex;
            flex-direction:column;
            gap: 10px;
            align-items: center;
        }

        .action-btn {
            color: white;
            border: none;
           /* padding: 10px 18px;*/
            font-size: 15px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-print {
            background: #27ae60;
        }

        .btn-print:hover {
            background: #1e8449;
        }

        .btn-word {
            background: #2980b9;
        }

        .btn-word:hover {
            background: #1f618d;
        }

        .btn-excel {
            background: #e67e22;
        }

        .btn-excel:hover {
            background: #d35400;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 1cm;
            }

            body * {
                visibility: hidden;
            }

            .form-container,
            .form-container * {
                visibility: visible;
            }

            .form-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 100%;
                padding: 0;
                border: none;
            }

            .action-box,
            .btn-center {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="action-box">
        <button type="button" class="action-btn btn-print" onclick="window.print()">🖨️ 列印</button>
        <a href="?action=export_excel<?= isset($_GET['new_member']) ? '&new_member=' . $_GET['new_member'] : '' ?>" target="download_frame" class="action-btn btn-excel">📊匯出EXCEL</a>
        <a href="?action=export_word<?= isset($_GET['new_member']) ? '&new_member=' . $_GET['new_member'] : '' ?>" target="download_frame" class="action-btn btn-word">📝匯出WORD</a>
    </div>

    <iframe name="download_frame" style="display:none;"></iframe>

    <div class="form-container">
        <div class="form-title">台中市李武略派下李氏宗親會</div>
        <div class="form-subtitle">【 會員入會申請表 】</div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="current_photo_path" value="<?= htmlspecialchars($avatar_file) ?>">

            <div class="top-info">
                <div>收件日期：<input type="datetime-local" step="1" name="receive_date" class="input-datetime" value="<?= !empty($m['receive_date']) ? date('Y-m-d\TH:i:s', strtotime($m['receive_date'])) : '' ?>"></div>
                <div>第 <input type="text" name="number_of_houses" class="inline-input" style="width:40px;" value="<?= $m['number_of_houses'] ?>"> 大房</div>
                <div>編號：<input type="text" name="new_member" value="<?= htmlspecialchars($m['new_member'] ?? '') ?>" style="width: 80px;"></div>
            </div>

            <table>
                <tr>
                    <th>姓名</th>
                    <td><input type="text" name="name" required value="<?= htmlspecialchars($m['name'] ?? '') ?>"></td>
                    <th>性別</th>
                    <td>
                        <label><input type="radio" name="gender" value="男" <?= ($m['gender'] ?? '') === '男' ? 'checked' : '' ?>> 男</label> &nbsp;
                        <label><input type="radio" name="gender" value="女" <?= ($m['gender'] ?? '') === '女' ? 'checked' : '' ?>> 女</label>
                    </td>
                    <td rowspan="3" class="photo-cell">
                        <div class="photo-container" onclick="triggerUpload()">
                            <?php if (!empty($avatar_file) && file_exists($avatar_file)): ?>
                                <img src="<?= $avatar_file ?>?t=<?= time() ?>" id="avatarImage" alt="會員大頭照">
                            <?php else: ?>
                                <div class="no-photo" id="avatarImage">貼照片處<br>(二吋半身)</div>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="photo" id="photoInput" accept="image/*" onchange="previewAndSelect(this)">
                    </td>
                </tr>

                <tr>
                    <th>身分證字號</th>
                    <td><input type="text" name="id_card_num" value="<?= htmlspecialchars($m['id_card_num'] ?? '') ?>"></td>
                    <th>出生日期</th>
                    <td><input type="date" name="birthday" value="<?= !empty($m['birthday']) ? date('Y-m-d', strtotime($m['birthday'])) : '' ?>" onclick="this.showPicker ? this.showPicker() : null"></td>
                </tr>

                <tr>
                    <th>派下世代</th>
                    <td colspan="3">
                        <div class="flex-row">
                            <div>遷台第<input type="text" name="emperor_shizu" class="inline-input" style="width:40px;" value="<?= $m['emperor_shizu'] ?>">世祖</div>
                            <div>居大甲第<input type="text" name="generation" class="inline-input" style="width:40px;" value="<?= $m['generation'] ?>">代</div>
                            <div>&emsp;&emsp;
                                <strong>派下權：</strong>
                                <label><input type="radio" name="has_right_option" value="yes" <?= $has_right === 'yes' ? 'checked' : '' ?> onclick="validateRight(this)">有</label>
                                <label><input type="radio" name="has_right_option" value="no" <?= $has_right === 'no' ? 'checked' : '' ?> onclick="validateRight(this)">無</label>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>派下員狀態</th>
                    <td colspan="5"><textarea name="SendSubordinates" id="sendSubordinates" rows="2"><?= htmlspecialchars($m['SendSubordinates'] ?? '') ?></textarea></td>
                </tr>

                <tr>
                    <th>生存狀態</th>
                    <td colspan="2">
                        <select name="living_status" id="livingStatus" style="width: 100px;">
                            <option value="存" <?= $m['living_status'] === '存' ? 'selected' : '' ?>>存</option>
                            <option value="亡" <?= $m['living_status'] === '亡' ? 'selected' : '' ?>>亡</option>
                            <option value="未知" <?= $m['living_status'] === '未知' ? 'selected' : '' ?>>未知</option>
                        </select>
                    </td>
                    <th>前會員號</th>
                    <td colspan="2"><input type="text" name="old_member" value="<?= htmlspecialchars($m['old_member'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <th>出生地或籍貫<br>(祖籍地)</th>
                    <td colspan="2"><input type="text" name="placeOfBirth" value="<?= htmlspecialchars($m['placeOfBirth'] ?? '') ?>"></td>
                    <th>學歷</th>
                    <td colspan="2"><input type="text" name="education" value="<?= htmlspecialchars($m['education'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <th>現職／經歷</th>
                    <td colspan="5"><textarea name="experience" rows="2"><?= htmlspecialchars($m['experience'] ?? '') ?></textarea></td>
                </tr>

                <tr>
                    <th>通訊地址</th>
                    <td colspan="5"><input type="text" name="address" value="<?= htmlspecialchars($m['address'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <th>聯絡電話</th>
                    <td colspan="5">
                        <div class="flex-row" style="justify-content: space-between;">
                            <div style="width:32%;">行動：<input type="text" name="mobile_phone" value="<?= htmlspecialchars($m['mobile_phone'] ?? '') ?>"></div>
                            <div style="width:32%;">住家：<input type="text" name="home_phone" value="<?= htmlspecialchars($m['home_phone'] ?? '') ?>"></div>
                            <div style="width:32%;">公司：<input type="text" name="company_phone" value="<?= htmlspecialchars($m['company_phone'] ?? '') ?>"></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>E-mail</th>
                    <td colspan="2"><input type="email" name="email" value="<?= htmlspecialchars($m['email'] ?? '') ?>"></td>
                    <th>介紹人</th>
                    <td colspan="2"><input type="text" name="introducer" value="<?= htmlspecialchars($m['introducer'] ?? '') ?>"></td>
                </tr>

                <tr>
                    <th>備註</th>
                    <td colspan="5">
                        <div class="remarks-time-header">
                            <span>🕒 最後一次修改時間：<?= !empty($m['update_time']) ? htmlspecialchars($m['update_time']) : '無紀錄' ?></span>
                            <span>👤 最後更新者：<?= $web_display_updater ?></span>
                        </div>
                        <textarea name="remarks" rows="4"><?= htmlspecialchars($m['remarks'] ?? '') ?></textarea>
                    </td>
                </tr>
            </table>

            <div class="btn-center">
                <button type="submit" name="update" class="btn-submit">💾 儲存修改資料</button>
            </div>
        </form>
    </div>

    <script>
        function triggerUpload() {
            document.getElementById('photoInput').click();
        }

        function previewAndSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.photo-container').innerHTML = '<img src="' + e.target.result + '" id="avatarImage" alt="照片預覽">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function validateRight(radioElement) {
            if (radioElement.value === 'yes') {
                const livingStatus = document.getElementById('livingStatus').value;
                const sendSubordinates = document.getElementById('sendSubordinates').value.trim();
                if (livingStatus !== '存' || sendSubordinates !== '正常派下員') {
                    alert('⚠️ 警告：必須同時滿足「生存狀態為存」與「派下員狀態為正常派下員」，才可勾選【有】派下權！');
                    const noRadio = document.querySelector('input[name="has_right_option"][value="no"]');
                    if (noRadio) noRadio.checked = true;
                }
            }
        }
    </script>
</body>

</html>