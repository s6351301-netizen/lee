<?php
session_start();

// ==========================================
// 1. 資料庫連線設定 (本機 lee)
// ==========================================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

// 建立 MySQLi 連線
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連線
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
// 🚀 關鍵設定：確保與資料庫來回的資料全部使用 utf8mb4，完美支援繁體中文與 Emoji
$conn->set_charset("utf8mb4");

// ==========================================
// 追加功能：後端處理 AJAX 模糊搜尋寄送對象 API (當接收到 search_target 時)
// ==========================================
if (isset($_GET['search_target'])) {
    header('Content-Type: application/json; charset=utf-8');
    $keyword = "%" . $_GET['search_target'] . "%";
    $results = [];

    // (A) 搜尋實體會員/帳號 (姓名或會員號)
    $stmt1 = $conn->prepare("SELECT name, new_member, role FROM account WHERE name LIKE ? OR new_member LIKE ? LIMIT 5");
    $stmt1->bind_param("ss", $keyword, $keyword);
    $stmt1->execute();
    $res1 = $stmt1->get_result();
    while ($row = $res1->fetch_assoc()) {
        $results[] = [
            'type' => 'user',
            'value' => $row['name'],
            'label' => "👤 帳號: " . $row['name'] . " (" . $row['new_member'] . ")"
        ];
    }
    $stmt1->close();

    // (B) 內建固定的群組/角色/世代選項（依據關鍵字作前端模糊過濾）
    $group_options = [
        ['type' => 'group', 'value' => '來台世祖', 'label' => '👥 群組: 來台世祖'],
        ['type' => 'group', 'value' => '大甲代祖', 'label' => '👥 群組: 大甲代祖'],
        ['type' => 'group', 'value' => '第一大房', 'label' => '👥 群組: 第一大房'],
        ['type' => 'role',  'value' => 'admin',  'label' => '🛡️ 角色群組: 管理者 (admin)'],
        ['type' => 'role',  'value' => 'user',   'label' => '👥 角色群組: 派下員 (user)'],
        ['type' => 'role',  'value' => 'clan',   'label' => '🍂 角色群組: 宗親 (clan)'],
    ];

    foreach ($group_options as $option) {
        if (mb_strpos($option['value'], $_GET['search_target']) !== false || mb_strpos($option['label'], $_GET['search_target']) !== false) {
            $results[] = $option;
        }
    }

    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// ==========================================
// 2. 處理檔案上傳 API -> 【保留原始中文檔名、支援 UTF-8、防重複】
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'upload_icon') {
    header('Content-Type: application/json; charset=utf-8');
    
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        echo json_encode(['error' => '請選擇要上傳的檔案']);
        exit;
    }

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/icon/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $uploaded_urls = [];
    $uploaded_ids = [];
    $errors = [];

    foreach ($_FILES['files']['name'] as $index => $original_name) {
        $file_tmp   = $_FILES['files']['tmp_name'][$index];
        $file_size  = $_FILES['files']['size'][$index];
        $file_error = $_FILES['files']['error'][$index];
        $file_type  = $_FILES['files']['type'][$index];

        if ($file_error !== UPLOAD_ERR_OK) {
            $errors[] = "檔案 [{$original_name}] 上傳發生錯誤，代碼：{$file_error}";
            continue;
        }

        $safe_original_name = basename($original_name); 
        $new_file_name = date('Ymd_His') . '_' . uniqid() . '_' . $safe_original_name;
        $web_url = '/icon/' . $new_file_name;
        
        $disk_file_name = $new_file_name;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $disk_file_name = iconv("UTF-8", "BIG5//IGNORE", $new_file_name);
        }
        $target_file_path = $upload_dir . $disk_file_name;

        if (move_uploaded_file($file_tmp, $target_file_path)) {
            $stmt_file = $conn->prepare("INSERT INTO files (file_name, file_path, file_url, file_type, file_size, status, reference_id) VALUES (?, ?, ?, ?, ?, 'active', 0)");
            $saved_path = $upload_dir . $new_file_name; 
            $stmt_file->bind_param("ssssi", $safe_original_name, $saved_path, $web_url, $file_type, $file_size);
            $stmt_file->execute();
            $new_file_id = $stmt_file->insert_id;
            $stmt_file->close();

            $uploaded_urls[] = $web_url;
            $uploaded_ids[] = $new_file_id;
        } else {
            $errors[] = "檔案 [{$safe_original_name}] 移動失敗，請檢查資料夾寫入權限";
        }
    }

    if (count($uploaded_urls) > 0) {
        echo json_encode([
            'success' => true,
            'files'   => $uploaded_urls, 
            'file_ids'=> $uploaded_ids,
            'msg'     => count($errors) > 0 ? implode(', ', $errors) : '上傳成功'
        ]);
    } else {
        echo json_encode(['error' => implode(', ', $errors)]);
    }
    exit;
}

// ==========================================
// 3. 提供給前端 AJAX 的 API 接口 (支援姓名與編號的 LIKE 模糊查詢)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'get_houses') {
    $search_keyword = isset($_GET['new_member']) ? trim($_GET['new_member']) : '';
    $members_list = [];

    if (!empty($search_keyword)) {
        $like_keyword = "%" . $search_keyword . "%";
        $stmt = $conn->prepare("SELECT name, new_member, emperor_shizu, generation, number_of_houses FROM members WHERE new_member LIKE ? OR name LIKE ?");
        $stmt->bind_param("ss", $like_keyword, $like_keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $members_list[] = [
                'name' => $row['name'],
                'new_member' => $row['new_member'],
                'emperor_shizu' => intval($row['emperor_shizu']),
                'generation' => intval($row['generation']),
                'number_of_houses' => intval($row['number_of_houses'])
            ];
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($members_list);
    exit; 
}

// ==========================================
// 4. 處理表單送出：同時寫入 makeawish 與 處理錄影檔案並更新/寫入 files 資料表
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 如果是前端 AJAX 的非同步表單送出影音
    if (isset($_FILES['video_file'])) {
        header('Content-Type: text/plain; charset=utf-8');
        
        $insert_id = isset($_POST['next_id']) ? intval($_POST['next_id']) : 1;
        $name = isset($_POST['author']) ? $_POST['author'] : '';
        $target_receiver = $_POST['target_receiver'] ?? ''; // 🔍 新增：接收對象或群組
        
        $emperor_shizu = isset($_POST['hidden_shizu']) ? intval($_POST['hidden_shizu']) : 0;
        $generation_val = isset($_POST['hidden_generation']) ? intval($_POST['hidden_generation']) : 0;
        $number_of_houses = isset($_POST['hidden_houses']) ? intval($_POST['hidden_houses']) : 0;
        $new_member_val = isset($_POST['hidden_new_member']) ? trim($_POST['hidden_new_member']) : '0';
        
        $family_members = isset($_POST['familyMember']) ? $_POST['familyMember'] : '';
        $message_of_blessing = isset($_POST['content']) ? $_POST['content'] : '';
        $login_time = isset($_POST['login_time']) ? $_POST['login_time'] : null; 
        $public = isset($_POST['public']) ? (int)$_POST['public'] : 1;

        if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            die("【PHP 檔案接收失敗】錯誤代碼: " . $_FILES['video_file']['error']);
        }
        
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $orig_name = $_FILES['video_file']['name'];
        $file_size = $_FILES['video_file']['size'];
        $file_type = $_FILES['video_file']['type']; 
        
        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
        if (empty($ext)) { $ext = (strpos($file_type, 'webm') !== false) ? 'webm' : 'mp4'; }
        
        $new_file_name = 'vid_' . uniqid() . '.' . $ext;
        $file_path = $upload_dir . $new_file_name;

        $conn->begin_transaction();
        try {
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $file_path)) {
                $file_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' . $file_path;
                $file_url = str_replace('//uploads', '/uploads', $file_url);

                // A. 寫入祈願表
                $stmt = $conn->prepare("INSERT INTO makeawish (ID, name, number_of_houses, emperor_shizu, generation, family_members, message_of_blessing, login_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiisss", $insert_id, $name, $number_of_houses, $emperor_shizu, $generation_val, $family_members, $message_of_blessing, $login_time);
                $stmt->execute();
                $stmt->close();

                // B. 寫入實體錄影資訊到 files 表中 (含對象欄位 target_receiver，若無此欄位會自動寫入，建議依照上一步手動加開)
                // 如果您的 files 表沒有 target_receiver 欄位，請在 MySQL 執行： ALTER TABLE files ADD COLUMN target_receiver VARCHAR(255) AFTER uploaded_name;
                $sql_vid = "INSERT INTO files (file_name, file_path, file_url, file_type, file_size, uploaded_id, uploaded_name, target_receiver, description, reference_id, public, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                $stmt_vid = $conn->prepare($sql_vid);
                if (!$stmt_vid) { die("【SQL 語法預備失敗】請檢查 files 結構: " . $conn->error); }
                
                $stmt_vid->bind_param("ssssisssssi", $new_file_name, $file_path, $file_url, $file_type, $file_size, $new_member_val, $name, $target_receiver, $message_of_blessing, $insert_id, $public);
                $stmt_vid->execute();
                $stmt_vid->close();

                $conn->commit();
                echo "🎉 祈願影音卡傳送成功！已成功掛上大樹。";
            } else {
                echo "【檔案搬移失敗】無法儲存影片。";
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "寫入失敗：" . $e->getMessage();
        }
        exit;
    }
}

// ==========================================
// 5. 撈取目前大樹祈願清單與最高 ID
// ==========================================
$max_id = 0;
$sql_max = "SELECT MAX(ID) AS max_id FROM makeawish";
$result_max = $conn->query($sql_max);
if ($result_max && $row_max = $result_max->fetch_assoc()) {
    $max_id = $row_max['max_id'] ? intval($row_max['max_id']) : 0; 
}
$next_id = $max_id + 1;

$wishes_array = [];
$sql_wishes = "SELECT w.name, w.emperor_shizu, w.generation, w.number_of_houses, w.message_of_blessing,
               (SELECT file_url FROM files WHERE reference_id = w.ID AND file_type LIKE 'video%' LIMIT 1) as attached_video
               FROM makeawish w ORDER BY w.ID DESC LIMIT 50";

$result_wishes = $conn->query($sql_wishes);
if ($result_wishes && $result_wishes->num_rows > 0) {
    while($w_row = $result_wishes->fetch_assoc()) {
        // 如果有附加錄影檔，在內容後方動態加上一個播放器 HTML
        $content_body = $w_row['message_of_blessing'];
        if (!empty($w_row['attached_video'])) {
            $content_body .= '<br><video src="'.$w_row['attached_video'].'" controls playsinline style="width:100%; margin-top:8px; border-radius:4px; max-height:150px; background:#000;"></video>';
        }

        $wishes_array[] = [
            'author' => $w_row['name'] . " (" ."第" . $w_row['number_of_houses'] . "大房)",
            'emperor_shizu' => "來台第" . $w_row['emperor_shizu'] . "世祖",
            'generation' => "定居大甲第" . $w_row['generation'] . "代",
            'content' => $content_body
        ];
    }
} else {
    $wishes_array = [
        [ "author" => "1長房大堂哥 國華 (第1大房)", "emperor_shizu" => "來台第24世祖", "generation" => "定居大甲第5代", "content" => "感念曾祖父當年用一雙長滿繭的手..." ],
        [ "author" => "2二房 佩芬 (第2大房)", "emperor_shizu" => "來台第24世祖", "generation" => "定居大甲第5代", "content" => "小時候總聽阿公說『吃米擔水要思源』..." ]
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家族大樹祈願樹 - 飲水思源 • 世代感恩</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@500;700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.5/build/jodit.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jodit@3.24.5/build/jodit.min.js"></script>
    
    <style>
        /* ================= 全局與主體設定 ================= */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Noto Serif TC", "PingFang TC", serif; color: #e9e4db; height: 100vh;
            display: flex; overflow: hidden; position: relative; background-color: #0a1f14;
        }
        .tree-background {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;
            background-image: linear-gradient(rgba(10, 31, 20, 0.85), rgba(10, 31, 20, 0.85)), url('https://cdntwrunning.biji.co/800_21cb7a3776e5fdbb6ffeb4e235067e88.jpg');
            background-size: 100% 100%, cover; background-position: center; background-repeat: no-repeat;
            will-change: transform; animation: treeOrbitLinkage 100s infinite linear;
        }
        .tree-background::after {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;
            background-image: radial-gradient(rgba(255, 255, 255, 0.18) 0.5px, transparent 20px), radial-gradient(rgba(255, 255, 255, 0.12) 0.6px, transparent 25px), radial-gradient(#f39c12, rgba(243, 156, 18, 0.15) 1.5px, transparent 40px);   
            background-size: 280px 280px, 380px 380px, 580px 580px; animation: forestStream 50s infinite linear;
        }
        @keyframes forestStream { 0% { background-position: 0 0; } 100% { background-position: 500px 1000px; } }   
        @keyframes treeOrbitLinkage { 0% { transform: scale(1.05) translateX(-3%); } 50% { transform: scale(1.2) translateY(-2%); } 100% { transform: scale(1.05) translateX(-3%); } }

        .celestial-body {
            position: absolute; width: 70px; height: 70px; border-radius: 50%; z-index: 2; pointer-events: none; 
            animation: sunOrbitX 100s infinite linear, sunOrbitY 100s infinite cubic-bezier(0.3, 0.2, 0.7, 0.8), sunPulse 100s infinite linear;
        }
        @keyframes sunOrbitX { 0% { left: -10%; } 100% { left: 110%; } }
        @keyframes sunOrbitY { 0% { top: 60%; } 50% { top: 3%; } 100% { top: 60%; } }
        @keyframes sunPulse {
            0%, 100% { background: transparent; box-shadow: -15px 15px 0 0 rgba(238, 242, 246, 0.85); filter: drop-shadow(0 0 10px rgba(255,255,255,0.4)); }
            45%, 55% { background: linear-gradient(135deg, #ffd166, #f39c12, #e63946); box-shadow: 0 0 40px #f39c12; }
        }

        .main-content { width: 100%; height: 100vh; padding: 20px; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 3; }
        header { text-align: center; width: 100%; max-width: 900px; margin-bottom: 25px; animation: fadeInDown 1s ease; }
        header h1 { font-size: 2.3rem; color: #a3ccab; margin-bottom: 8px; letter-spacing: 3px; font-weight: 700; text-shadow: 0 0 12px rgba(163, 204, 171, 0.3); }
        header h2 { font-size: 1.5rem; margin-bottom: 10px; color: #ece6dc; }
        header p { font-size: 1rem; line-height: 1.6; color: #cbd5e1; }

        .open-sidebar-btn {
            position: fixed; right: 20px; top: 20px; background: rgba(163, 204, 171, 0.25); color: #a3ccab; border: 1px solid rgba(163, 204, 171, 0.6);
            padding: 10px 18px; border-radius: 25px; cursor: pointer; font-family: inherit; font-weight: bold; backdrop-filter: blur(8px); transition: all 0.3s; z-index: 10; 
        }
        .open-sidebar-btn:hover { background: #407a52; color: #fff; transform: translateY(-2px); }

        .scroll-container { 
            width: 100%; 
            max-width: 1100px; 
            height: 65vh; 
            overflow: hidden; 
            position: relative; 
            margin-top: 20px;
            mask-image: linear-gradient(to bottom, transparent 0%, black 12%, black 85%, transparent 100%); 
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 12%, black 85%, transparent 100%);
        }
        
        .marquee-track { 
            display: flex; 
            flex-direction: column; 
            gap: 25px; 
            width: 100%; 
            position: absolute; 
            top: 0; 
            left: 0; 
            animation: scrollUp 35s infinite linear; 
        }
        .marquee-track:hover { animation-play-state: paused; }
        .wish-row { display: flex; justify-content: center; align-items: flex-start; gap: 25px; width: 100%; }
        
        @keyframes scrollUp { 0% { transform: translateY(150px); } 100% { transform: translateY(calc(-100% + 150px)); } }

        .wish-card {
            background: rgba(20, 54, 34, 0.65); border-radius: 6px 6px 12px 12px; padding: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); backdrop-filter: blur(8px); position: relative; 
            transition: transform 0.4s, background 0.4s, z-index 0.4s; width: 340px; min-width: 320px;
            overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
        }
        .wish-card:hover { transform: scale(1.03) !important; background: rgba(20, 54, 34, 0.85); z-index: 50; }
        .wish-card::before {
            content: ''; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            width: 10px; height: 10px; background: #f39c12; border-radius: 50%; box-shadow: 0 0 8px #f39c12; z-index: 4;
        }

        .wish-card-hero-image-wrapper {
            width: calc(100% + 44px); margin-left: -22px; margin-top: -22px; margin-bottom: 15px;
            height: 160px; overflow: hidden; border-bottom: 1px solid rgba(255, 255, 255, 0.1); position: relative; cursor: pointer; background: #000;
        }
        .wish-card-hero-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .wish-card-hero-image-wrapper:hover .wish-card-hero-image { transform: scale(1.06); }

        .wish-content { font-size: 0.95rem; line-height: 1.6; color: #ece6dc; margin-bottom: 15px; min-height: 65px; flex-grow: 1; display: block; overflow: hidden; }
        .wish-content video { width: 100%; border-radius: 4px; background:#000; margin-top:5px; }

        .wish-file-table {
            width: 100%; border-collapse: collapse; margin-top: 12px; margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.2); border-radius: 6px; overflow: hidden; font-size: 0.85rem;
        }
        .wish-file-table tr { border-bottom: 1px solid rgba(255, 255, 255, 0.08); transition: background 0.2s; }
        .wish-file-table tr:hover { background: rgba(255, 255, 255, 0.05); }
        .wish-file-table td { padding: 8px 10px; vertical-align: middle; color: #e9e4db; text-align: left; }
        .wish-file-name { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .wish-file-name a { color: #a3ccab; text-decoration: none; font-weight: 500; }
        
        .wish-table-thumb { width: 42px; height: 32px; object-fit: cover; border-radius: 3px; cursor: pointer; border: 1px solid rgba(255, 255, 255, 0.2); transition: transform 0.2s; }

        .img-click-popup-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 15, 10, 0.85); backdrop-filter: blur(8px);
            z-index: 99999; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.25s ease;
        }
        .img-click-popup-overlay.active { display: flex; opacity: 1; }
        .img-popup-container {
            position: relative; max-width: 90%; max-height: 85%; background: rgba(10, 31, 20, 0.95); padding: 12px;
            border: 2px solid #a3ccab; border-radius: 8px; box-shadow: 0 25px 60px rgba(0,0,0,0.6); animation: zoomInQuick 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .img-popup-container img { max-width: 100%; max-height: 75vh; display: block; object-fit: contain; border-radius: 4px; background: #000; }
        .img-popup-action-group { position: absolute; top: -18px; right: -18px; display: flex; gap: 8px; z-index: 100001; }
        .img-popup-btn {
            width: 36px; height: 36px; color: #ffffff; border: 2px solid #ffffff; border-radius: 50%; font-size: 16px; font-weight: bold; 
            cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .wish-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; border-top: 1px dashed rgba(255, 255, 255, 0.2); padding-top: 10px; }
        .wish-author { font-weight: bold; color: #f39c12; }
        .wish-generation { background-color: rgba(163, 204, 171, 0.2); color: #a3ccab; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }

        /* 右側輸入視窗 */
        .sidebar {
            position: fixed; top: 0; right: -100%; width: 35%; min-width: 360px; max-width: 460px; height: 100vh;
            background: rgba(10, 31, 20, 0.97); backdrop-filter: blur(20px); padding: 40px 25px; display: flex; flex-direction: column;
            box-shadow: -10px 0 35px rgba(0, 0, 0, 0.6); border-left: 1px solid rgba(255, 255, 255, 0.08); z-index: 100; transition: right 0.5s cubic-bezier(0.16, 1, 0.3, 1); overflow-y: auto;
        }
        .sidebar.active { right: 0; }
        .close-sidebar-btn { position: absolute; top: 15px; right: 20px; background: transparent; border: none; color: #94a3b8; font-size: 2rem; cursor: pointer; }

        .counter-box { font-size: 1rem; background: rgba(163, 204, 171, 0.1); padding: 8px 15px; border-radius: 8px; text-align: center; color: #a3ccab; font-weight: bold; margin-bottom: 15px; border: 1px solid rgba(163, 204, 171, 0.2); }
        .counter-box span { font-size: 1.4rem; color: #f39c12; margin: 0 4px; }
        .form-title { font-size: 1.4rem; color: #a3ccab; margin-bottom: 20px; font-weight: bold; border-bottom: 2px solid #a3ccab; padding-bottom: 10px; }
        .form-group { margin-bottom: 18px; position: relative; }
        label { display: block; font-size: 0.95rem; margin-bottom: 8px; color: #cbd5e1; font-weight: bold; }
        .label-text-header { font-size: 0.9rem; color: #a3ccab; margin-bottom: 6px; display: block; line-height: 1.5; }
        
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; font-family: inherit; font-size: 1rem; background-color: rgba(5, 15, 10, 0.7); color: #e9e4db; transition: all 0.3s; }
        textarea { resize: none; height: 100px; }
        select:disabled { background-color: rgba(30, 45, 35, 0.8); color: #a3ccab; cursor: not-allowed; }

        .member-select-wrapper { background: rgba(0, 0, 0, 0.3); border: 1px dashed rgba(163, 204, 171, 0.4); border-radius: 8px; padding: 10px; margin-top: 5px; display: none; max-height: 140px; overflow-y: auto; }
        .member-item { display: flex; align-items: center; gap: 10px; padding: 6px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.88rem; cursor: pointer; }
        .member-item input[type="checkbox"] { width: auto; }

        /* 🔍 追加：AJAX 模糊搜尋下拉選單樣式 */
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #102619; border: 1px solid #407a52; z-index: 999; max-height: 160px; overflow-y: auto; border-radius: 0 0 8px 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.3); display: none; }
        .autocomplete-item { padding: 10px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem; color: #e9e4db; }
        .autocomplete-item:hover { background-color: #2e5c2e; color: #ffffff; }

        /* 🎬 錄影區塊專用樣式 */
        .rec-active { background-color: #ff4d4d !important; color: white !important; }
        .sidebar video { width: 100%; max-height: 180px; background: #000; margin-top: 8px; border-radius: 6px; display: none; }
        .rec-btn { padding: 8px 14px; background: #334155; border: 1px solid #475569; color: #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 0.9em; margin-right: 5px; }

        .submit-btn { width: 100%; background: linear-gradient(135deg, #407a52, #143622); color: #e9e4db; border: none; padding: 14px; font-size: 1.1rem; font-weight: bold; border-radius: 8px; cursor: pointer; margin-top: 10px; margin-bottom: 20px; }
        .sidebar-footer { font-size: 0.85rem; color: #94a3b8; text-align: center; }
        .highlight-val { color: #f39c12; font-weight: bold; margin: 0 3px; }

        .marquee-input::placeholder { white-space: nowrap; display: inline-block; overflow: hidden; width: 100%; animation: placeholderMarquee 12s linear infinite; }
        .marquee-input:focus::placeholder { animation-play-state: paused; color: transparent; }
        @keyframes placeholderMarquee { 0% { text-indent: 100%; } 100% { text-indent: -150%; } }

        /* Jodit 彈出編輯器 */
        .editor-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 32, 39, 0.7); backdrop-filter: blur(5px); z-index: 200; display: none; align-items: center; justify-content: center; }
        .editor-modal-overlay.active { display: flex; }
        .editor-modal-content { background: #eef7f4; border: 2px solid #a3d8f4; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; width: 90%; height: 90%; max-width: 95vw; max-height: 95vh; }
        .editor-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #bae6fd; padding-bottom: 10px; }
        .editor-modal-title { font-size: 1.2rem; color: #1e3a8a; font-weight: bold; }
        .editor-modal-close { background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; }
        .editor-container-box { flex: 1; min-height: 0; margin-bottom: 15px; height: 100%; }
        .editor-modal-footer { display: flex; justify-content: flex-end; gap: 15px; }
        .modal-btn { padding: 10px 24px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; }
        .modal-btn-cancel { background: #94a3b8; color: #ffffff; }
        .modal-btn-submit { background: #0284c7; color: #ffffff; }

        .jodit-container { background: #f0fdf4 !important; color: #000000 !important; height: 100% !important; }
        .jodit-wysiwyg { color: #000000; }
        .expand-link { font-size: 0.85rem; color: #f39c12; cursor: pointer; margin-left: 10px; text-decoration: underline; font-weight: normal; }
        #pc_interface, #mobile_interface { display: none; margin-top: 5px; }
    </style>
</head>
<body>

    <div class="tree-background"></div>
    <div class="celestial-body"></div>

    <div class="main-content">              
        <header>  
            <button class="open-sidebar-btn" id="openSidebarBtn">🌿 填寫祈願卡</button>
            <h1>李武略家族</h1>
            <h2>村莊內三棵大樹 代表三大房</h2>
            <h3>守護家族的象徵、祈願的對象：<br>「一柱鋤頭落地開，千重稻浪代代傳。」</h3>
            <p>有祖先深埋泥土的根，才有今日繁星閃耀的子孫。</p>
            <div class="sidebar-footer" >
               歲時感恩牆 • <span id="clock"></span> • 李武略家族後代子孫 敬立
            </div>
        </header>

        <div class="scroll-container">
            <div class="marquee-track" id="marqueeTrack"></div>
        </div>
    </div>

    <div class="img-click-popup-overlay" id="imgPopupOverlay">
        <div class="img-popup-container">
            <div class="img-popup-action-group">
                <a href="" id="imgPopupDownloadBtn" class="img-popup-btn img-popup-download-btn" title="下載此圖片" download="家族祈願圖片">⬇</a>
                <button class="img-popup-btn img-popup-close-btn" id="imgPopupCloseBtn" title="關閉視窗">&times;</button>
            </div>
            <img src="" id="imgPopupTarget" alt="完整大圖顯示">
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <button class="close-sidebar-btn" id="closeSidebarBtn">&times;</button>
        <div class="counter-box">目前已有 <span id="wishCount"><?php echo $max_id; ?></span> 枝子孫祈願葉</div>
        <div class="form-title">🌿 撰寫祈願卡</div>        
        <form id="wishForm">
            
            <input type="hidden" id="next_id" name="next_id" value="<?php echo $next_id; ?>">
            <input type="hidden" id="hidden_shizu" name="hidden_shizu" value="0">
            <input type="hidden" id="hidden_generation" name="hidden_generation" value="0">
            <input type="hidden" id="hidden_houses" name="hidden_houses" value="0">
            <input type="hidden" id="hidden_new_member" name="hidden_new_member" value="0">
            <input type="hidden" id="login_time" name="login_time" value="">

            <div class="form-group">
                <span class="label-text-header">
                    <span for="author">您的姓名:</span><span id="show_name" class="highlight-val">?</span>
                    ,編號:<span id="show_member_id" class="highlight-val">?</span>
                    ,第<span id="show_shizu" class="highlight-val">?</span>世祖
                    <span id="show_generation" class="highlight-val">?</span>代
                    <span id="show_houses" class="highlight-val">?</span>大房
                </span>
                <input type="text" id="author" name="author" class="marquee-input" placeholder="打關鍵字(姓名/編號),點符合項目,顯示世代.(不開放訪客使用.)" required autocomplete="off">
                <div class="member-select-wrapper" id="memberSelectWrapper"></div>
            </div>

            <div class="form-group">
                <label for="targetInput">🎯 留言內容寄給誰？(輸入人名、世代或角色群組關鍵字)：</label>
                <input type="text" id="targetInput" placeholder="🔍 輸入關鍵字...如 admin、來台、或特定姓名" autocomplete="off">
                <input type="hidden" id="targetReceiver" name="target_receiver" required>
                <div id="searchDropdown" class="autocomplete-dropdown"></div>
            </div>

            <div class="form-group">
                <label for="familyMember">家庭成員</label>
                <input type="text" id="familyMember" name="familyMember" placeholder="本人或全家(需寫出完整姓名或家庭稱謂)" required>
            </div>

            <div class="form-group">
                <label for="generation">世代輩分</label>
                <select id="generation" name="generation" required>
                    <option value="" disabled selected>請選擇輩分</option>
                    <option value="21">來台第21世祖/大甲2代(祖字輩)</option>
                    <option value="22">來台第22世祖/大甲3代(武字輩)</option>
                    <option value="23">來台第23世祖/大甲4代(德字輩)</option>
                    <option value="24">來台第24世祖/大甲5代(業字輩)</option>
                    <option value="25">來台第25世祖/大甲6代(貽字輩)</option>
                    <option value="26">來台第26世祖/大甲7代(孫字輩)</option>
                    <option value="27">來台第27世祖/大甲8代(謀字輩)</option>
                    <option value="28">來台第28世祖/大甲9代(不使用字輩)</option>
                    <option value="29">來台第29世祖/大甲10代(不使用字輩)</option>
                    <option value="30">來台第30世祖/大甲11代(不使用字輩)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="content">寫給祖先/祈願的話 <span class="expand-link" id="openEditorBtn">[展開進階編輯]</span></label>
                <textarea id="content" name="content" placeholder="請寫下您對先祖默默耕耘的感念..." required></textarea>
                <div style="margin-top: 6px;">
                    <button type="button" id="voiceTypeBtn" style="padding:6px 12px; background:#1e3a29; border:1px solid #407a52; color:#a3ccab; border-radius:4px; cursor:pointer;">🎤 語音輸入 (講話變文字)</button>
                </div>
            </div>

            <div class="form-group" style="border: 1px dashed rgba(163,204,171,0.3); padding:10px; border-radius:8px;">
                <label>🎬 影音卡附件 (限時 5 分鐘內)：</label>
                
                <div id="pc_interface">
                    <button type="button" id="startPCOne" class="rec-btn">📹 開啟鏡頭並錄影</button>
                    <button type="button" id="stopPCOne" class="rec-btn" disabled>⏹ 停止</button>
                    <span id="pcTimer" style="color:#ff4d4d; font-weight:bold;"></span>
                </div>

                <div id="mobile_interface">
                    <p style="color: #a3ccab; font-size: 0.85em; margin-bottom: 6px;">行動裝置模式：將喚起手機錄影相機</p>
                    <input type="file" id="videoMobileFile" accept="video/*">
                </div>
                <div id="videoError" style="color:#ff4d4d; font-weight:bold; font-size:0.85em; margin-top:5px; display:none;">❌ 影片長度超過 5 分鐘，請重錄！</div>
                
                <video id="videoPlayback" controls playsinline></video>
            </div>

            <div class="form-group">
                <label for="isPublic">是否公開此祈願卡：</label>
                <select id="isPublic" name="public">
                    <option value="1">公開 (所有人可見)</option>
                    <option value="0">私有 (僅系統管理員與指定對象可見)</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">掛上祈願樹(送出)➔</button>
        </form>
    </div>

    <div class="editor-modal-overlay" id="editorModal">
        <div class="editor-modal-content">
            <div class="editor-modal-header">
                <div class="editor-modal-title">🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)</div>
                <button class="editor-modal-close" id="closeEditorBtn">&times;</button>
            </div>
            <div class="editor-container-box">
                <textarea id="joditEditorTarget"></textarea>
            </div>
            <div class="editor-modal-footer">
                <button class="modal-btn modal-btn-cancel" id="cancelModalBtn">取消</button>
                <button class="modal-btn modal-btn-submit" id="submitModalBtn">確認送出</button>
            </div>
        </div>
    </div>

    <script>
        let wishesData = <?php echo json_encode($wishes_array); ?>;
        const marqueeTrack = document.getElementById('marqueeTrack');
        const sidebar = document.getElementById('sidebar');

        document.getElementById('openSidebarBtn').addEventListener('click', () => sidebar.classList.add('active'));
        document.getElementById('closeSidebarBtn').addEventListener('click', () => sidebar.classList.remove('active'));

        // 自動判定裝置
        const isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
        if (isMobile) {
            document.getElementById('mobile_interface').style.display = 'block';
        } else {
            document.getElementById('pc_interface').style.display = 'block';
        }

        const MAX_DURATION = 300; 
        let finalVideoBlobOrFile = null;

        // ==========================================
        // 🔍 實作：AJAX 模糊搜尋寄送對象
        // ==========================================
        const targetInput = document.getElementById('targetInput');
        const targetReceiver = document.getElementById('targetReceiver');
        const searchDropdown = document.getElementById('searchDropdown');

        targetInput.addEventListener('input', async function() {
            const value = this.value.trim();
            if (value.length === 0) { searchDropdown.style.display = 'none'; return; }

            try {
                const response = await fetch(`voicemail.php?search_target=${encodeURIComponent(value)}`);
                const data = await response.json();
                searchDropdown.innerHTML = '';
                
                if (data.length === 0) {
                    const noResult = document.createElement('div');
                    noResult.className = 'autocomplete-item';
                    noResult.style.color = '#888';
                    noResult.innerText = '未找到匹配，送出將直接以此文字為對象';
                    searchDropdown.appendChild(noResult);
                    targetReceiver.value = value;
                } else {
                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerText = item.label;
                        div.addEventListener('click', () => {
                            targetInput.value = item.label;
                            targetReceiver.value = item.value;
                            searchDropdown.style.display = 'none';
                        });
                        searchDropdown.appendChild(div);
                    });
                }
                searchDropdown.style.display = 'block';
            } catch (err) { console.error("搜尋錯誤", err); }
        });

        document.addEventListener('click', (e) => {
            if (!targetInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.style.display = 'none';
                if(!targetReceiver.value) targetReceiver.value = targetInput.value.trim();
            }
        });

        // ==========================================
        // 🎬 實作：PC 版錄影與手機版防呆機制
        // ==========================================
        let pcRecorder; let pcChunks = []; let pcCountdown;

        document.getElementById('startPCOne').addEventListener('click', async () => {
            pcChunks = [];
            const videoPlayback = document.getElementById('videoPlayback');
            document.getElementById('videoError').style.display = 'none';
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                videoPlayback.srcObject = stream; videoPlayback.muted = true; videoPlayback.style.display = 'block'; videoPlayback.play();
                
                let options = { mimeType: 'video/webm;codecs=vp9,opus' };
                if (!MediaRecorder.isTypeSupported(options.mimeType)) options = { mimeType: 'video/mp4' };

                pcRecorder = new MediaRecorder(stream, options);
                pcRecorder.ondataavailable = e => { if (e.data.size > 0) pcChunks.push(e.data); };
                pcRecorder.onstop = () => {
                    stream.getTracks().forEach(track => track.stop());
                    videoPlayback.srcObject = null; videoPlayback.muted = false;
                    finalVideoBlobOrFile = new Blob(pcChunks, { type: pcRecorder.mimeType });
                    videoPlayback.src = URL.createObjectURL(finalVideoBlobOrFile);
                };
                pcRecorder.start();
                document.getElementById('startPCOne').disabled = true; document.getElementById('startPCOne').classList.add('rec-active');
                document.getElementById('stopPCOne').disabled = false;

                let timeLeft = MAX_DURATION;
                pcCountdown = setInterval(() => {
                    timeLeft--;
                    let mins = Math.floor(timeLeft / 60); let secs = timeLeft % 60;
                    document.getElementById('pcTimer').innerText = `剩餘：${mins}:${secs < 10 ? '0' : ''}${secs}`;
                    if (timeLeft <= 0) { clearInterval(pcCountdown); document.getElementById('stopPCOne').click(); }
                }, 1000);
            } catch (err) { alert("PC 鏡頭開啟受限，建議使用手機開啟本網頁錄製。"); }
        });

        document.getElementById('stopPCOne').addEventListener('click', () => {
            if (pcRecorder && pcRecorder.state !== "inactive") {
                clearInterval(pcCountdown); pcRecorder.stop();
                document.getElementById('startPCOne').disabled = false; document.getElementById('startPCOne').classList.remove('rec-active');
                document.getElementById('stopPCOne').disabled = true; document.getElementById('pcTimer').innerText = "錄製完成";
            }
        });

        // 手機版換軌放行防呆
        document.getElementById('videoMobileFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const videoPlayback = document.getElementById('videoPlayback');
            const errorDiv = document.getElementById('videoError');
            if (!file) return;

            videoPlayback.src = URL.createObjectURL(file);
            videoPlayback.style.display = 'block';
            finalVideoBlobOrFile = file;
            errorDiv.style.display = 'none';

            videoPlayback.onloadedmetadata = function() {
                if (!isNaN(videoPlayback.duration) && isFinite(videoPlayback.duration)) {
                    if (videoPlayback.duration > MAX_DURATION) {
                        errorDiv.style.display = 'block';
                        finalVideoBlobOrFile = null;
                        document.getElementById('videoMobileFile').value = '';
                        alert("影片容量或長度已超過 5 分鐘上限！");
                    }
                }
            };
        });

        // ==========================================
        // 🎤 實作：語音轉文字 (Web Speech API)
        // ==========================================
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (SpeechRecognition) {
            const recognition = new SpeechRecognition(); recognition.lang = 'zh-TW';
            document.getElementById('voiceTypeBtn').addEventListener('click', () => recognition.start());
            recognition.onresult = (e) => document.getElementById('content').value += e.results[0][0].transcript;
        } else {
            document.getElementById('voiceTypeBtn').style.display = 'none';
        }

        // ==========================================
        // 原生跑馬燈與解析卡片邏輯 (保持不變)
        // ==========================================
        function parseContentFilesToTable(contentHtml) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = contentHtml;

            const fileLinks = tempDiv.querySelectorAll('a[href*="/icon/"]');
            const fileImgs = tempDiv.querySelectorAll('img[src*="/icon/"]');
            
            let filesArray = [];
            let heroImageUrl = null; 

            fileImgs.forEach((img, idx) => {
                const url = img.getAttribute('src');
                let name = url.substring(url.lastIndexOf('/') + 1);
                try { name = decodeURIComponent(name); } catch(e) {}
                if (idx === 0) { heroImageUrl = url; } else { filesArray.push({ type: 'image', url: url, name: name }); }
                img.remove(); 
            });

            fileLinks.forEach(link => {
                const url = link.getAttribute('href');
                if (url === heroImageUrl) { link.remove(); return; }
                let name = link.textContent.replace('📎 下載附件:', '').trim();
                if(!name) { name = url.substring(url.lastIndexOf('/') + 1); try { name = decodeURIComponent(name); } catch(e) {} }
                const isImageFile = /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
                if (isImageFile) {
                    if (!filesArray.some(f => f.url === url)) { filesArray.push({ type: 'image', url: url, name: name }); }
                } else { filesArray.push({ type: 'file', url: url, name: name }); }
                link.remove(); 
            });

            let tableHtml = '';
            if (filesArray.length > 0) {
                tableHtml = '<table class="wish-file-table">';
                filesArray.forEach(file => {
                    let displayName = file.name;
                    if (displayName.includes('_')) {
                        const parts = displayName.split('_');
                        if (parts.length >= 4 && /^\d{8}$/.test(parts[0])) { displayName = parts.slice(3).join('_'); }
                    }
                    if (file.type === 'image') {
                        tableHtml += `<tr><td class="wish-file-icon">🖼️</td><td class="wish-file-name"><a href="${file.url}" class="card-img-trigger" target="_blank">${displayName}</a></td><td style="text-align: right; width: 60px;"><img src="${file.url}" class="wish-table-thumb" alt="縮圖"></td></tr>`;
                    } else {
                        tableHtml += `<tr><td class="wish-file-icon">📎</td><td class="wish-file-name" colspan="2"><a href="${file.url}" target="_blank">下載附件: ${displayName}</a></td></tr>`;
                    }
                });
                tableHtml += '</table>';
            }
            return { cleanHtml: tempDiv.innerHTML, tableHtml: tableHtml, heroImageUrl: heroImageUrl };
        }

        function createCardNode(wish) {
            const card = document.createElement('div');
            card.className = 'wish-card';
            const randomRotate = (Math.random() * 4 - 2).toFixed(1);
            card.style.transform = `rotate(${randomRotate}deg)`;
            
            const parsed = parseContentFilesToTable(wish.content);
            let heroImageHtml = '';
            if (parsed.heroImageUrl) {
                heroImageHtml = `<div class="wish-card-hero-image-wrapper"><img src="${parsed.heroImageUrl}" class="wish-card-hero-image card-img-trigger" alt="主視覺圖片"></div>`;
            }

            card.innerHTML = `
                ${heroImageHtml}
                <div class="wish-content">
                    <div>${parsed.cleanHtml}</div>
                    ${parsed.tableHtml}
                </div>
                <div class="wish-meta">
                    <span class="wish-author">${wish.author}</span>
                    <span class="wish-generation">${wish.emperor_shizu}&emsp;${wish.generation}</span>
                </div>
            `;
            return card;
        }

        function renderMarquee() {
            marqueeTrack.innerHTML = '';
            if(wishesData.length === 0) return;
            let processedWishes = [...wishesData];
            const isMobile = window.innerWidth <= 768;
            const itemsPerRow = isMobile ? 1 : 3;

            const remainder = processedWishes.length % itemsPerRow;
            if (remainder !== 0) {
                const needAdds = itemsPerRow - remainder;
                for (let i = 0; i < needAdds; i++) { processedWishes.push(wishesData[wishesData.length - 1 - i] || wishesData[0]); }
            }
            const rowsData = [];
            for (let i = 0; i < processedWishes.length; i += itemsPerRow) { rowsData.push(processedWishes.slice(i, i + itemsPerRow)); }

            rowsData.forEach(rowItems => {
                const rowDiv = document.createElement('div'); rowDiv.className = 'wish-row';
                rowItems.forEach(item => rowDiv.appendChild(createCardNode(item)));
                marqueeTrack.appendChild(rowDiv);
            });
            marqueeTrack.style.animationDuration = `${(rowsData.length * 8.5) + 2}s`;            
        }

        // ==========================================
        // AJAX 查詢名冊名單機制
        // ==========================================
        const authorInput = document.getElementById('author');
        const wrapper = document.getElementById('memberSelectWrapper');

        authorInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length < 1) { wrapper.style.display = 'none'; wrapper.innerHTML = ''; clearLabelsAndHiddens(); return; }

            fetch(`voicemail.php?action=get_houses&new_member=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    wrapper.innerHTML = '';
                    if (data && data.length > 0) {
                        wrapper.style.display = 'block';
                        data.forEach((member, index) => {
                            const item = document.createElement('div'); item.className = 'member-item';
                            const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.name = 'selected_member_cb'; checkbox.value = index;
                            
                            checkbox.dataset.name = member.name;
                            checkbox.dataset.new_member = member.new_member;
                            checkbox.dataset.shizu = member.emperor_shizu;
                            checkbox.dataset.gen = member.generation;
                            checkbox.dataset.houses = member.number_of_houses;

                            checkbox.addEventListener('change', function() {
                                if (this.checked) {
                                    document.querySelectorAll('input[name="selected_member_cb"]').forEach(cb => { if (cb !== this) cb.checked = false; });
                                    applyMemberValues(this.dataset);
                                } else { clearLabelsAndHiddens(); }
                            });

                            const textLabel = document.createElement('span');
                            textLabel.textContent = `${member.name} ${member.new_member} 號, 第 ${member.emperor_shizu}世祖/ 第${member.generation}代/${member.number_of_houses}房`;

                            item.addEventListener('click', function(e) { if (e.target !== checkbox) { checkbox.checked = !checkbox.checked; checkbox.dispatchEvent(new Event('change')); } });
                            item.appendChild(checkbox); item.appendChild(textLabel); wrapper.appendChild(item);

                            if(data.length === 1) { checkbox.checked = true; applyMemberValues(checkbox.dataset); }
                        });
                    } else { wrapper.style.display = 'none'; clearLabelsAndHiddens(); }
                });
        });

        function applyMemberValues(dataset) {
            document.getElementById('show_name').textContent = dataset.name;
            document.getElementById('show_member_id').textContent = dataset.new_member;
            document.getElementById('show_shizu').textContent = dataset.shizu;
            document.getElementById('show_generation').textContent = dataset.gen;
            document.getElementById('show_houses').textContent = dataset.houses;
            document.getElementById('hidden_shizu').value = dataset.shizu;
            document.getElementById('hidden_generation').value = dataset.gen;
            document.getElementById('hidden_houses').value = dataset.houses;
            document.getElementById('hidden_new_member').value = dataset.new_member;
            
            const genSelect = document.getElementById('generation');
            if(genSelect.querySelector(`option[value="${dataset.shizu}"]`)){ genSelect.value = dataset.shizu; genSelect.disabled = true; } 
            else if(genSelect.querySelector(`option[value="${dataset.gen}"]`)){ genSelect.value = dataset.gen; genSelect.disabled = true; }
            authorInput.value = dataset.name;
        }

        function clearLabelsAndHiddens() {
            document.getElementById('show_name').textContent = '?'; document.getElementById('show_member_id').textContent = '?';
            document.getElementById('show_shizu').textContent = '?'; document.getElementById('show_generation').textContent = '?'; document.getElementById('show_houses').textContent = '?';
            document.getElementById('hidden_shizu').value = 0; document.getElementById('hidden_generation').value = 0; document.getElementById('hidden_houses').value = 0; document.getElementById('hidden_new_member').value = 0;
            const genSelect = document.getElementById('generation'); genSelect.disabled = false; genSelect.value = "";
        }

        // ==========================================
        // 🚀 關鍵重構：全面改用 AJAX 進行一條龍送出上傳
        // ==========================================
        document.getElementById('wishForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (document.getElementById('hidden_shizu').value === "0" && document.getElementById('show_shizu').textContent === "?") {
                alert("姓名與世代為必填！！不開放訪客無會員編號填寫！");
                return;
            }
            if (!targetReceiver.value) {
                targetReceiver.value = targetInput.value.trim();
            }
            if (!targetReceiver.value) { alert("請指定祈願卡留言要寄給誰！"); return; }
            if (!finalVideoBlobOrFile) { alert("請附加 5 分鐘內的影音卡檔案再行送出！"); return; }

            const now = new Date();
            const formattedTime = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            document.getElementById('login_time').value = formattedTime;

            const formData = new FormData(this);
            if (!isMobile) {
                const ext = pcRecorder.mimeType.includes('mp4') ? 'mp4' : 'webm';
                formData.append('video_file', finalVideoBlobOrFile, `pc_video.${ext}`);
            } else {
                formData.append('video_file', finalVideoBlobOrFile);
            }

            try {
                const response = await fetch('voicemail.php', { method: 'POST', body: formData });
                if (response.ok) {
                    const result = await response.text();
                    alert(result);
                    if (result.includes('成功')) { location.reload(); }
                } else { alert("上傳失敗，請確認資料庫容量或路徑權限。"); }
            } catch (err) { alert("連線發送出錯！"); }
        });

        // ==========================================
        // Jodit 與 彈出視窗配置 (保持不變)
        // ==========================================
        const fullFreeButtons = ['source', '|', 'bold', 'strikethrough', 'underline', 'italic', '|', 'font', 'fontsize', 'brush', 'paragraph', '|', 'image', 'file', 'video', 'table', 'link', '|', 'align', 'undo', 'redo'];
        const joditEditor = new Jodit('#joditEditorTarget', {
            buttons: fullFreeButtons, height: '100%', language: 'zh_tw',
            uploader: {
                url: 'voicemail.php?action=upload_icon', format: 'json', path: 'files', multiple: true,
                isSuccess: resp => resp.success === true, process: resp => ({ files: resp.files || [], error: resp.error, msg: resp.msg }),
                defaultHandlerSuccess: function (data) {
                    if (data.files && data.files.length) {
                        data.files.forEach(fileUrl => {
                            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileUrl);
                            let displayFileName = fileUrl.substring(fileUrl.lastIndexOf('/') + 1);
                            try { displayFileName = decodeURIComponent(displayFileName); } catch(e) {}
                            if (isImage) { this.s.insertImage(fileUrl, null, 200); } 
                            else { this.s.insertHTML(`<a href="${fileUrl}" target="_blank" style="color: #0284c7;">📎 下載附件: ${displayFileName}</a>&nbsp;`); }
                        });
                    }
                }
            }
        });

        const editorModal = document.getElementById('editorModal');
        const mainContentTextarea = document.getElementById('content');
        document.getElementById('openEditorBtn').addEventListener('click', () => { joditEditor.value = mainContentTextarea.value; editorModal.classList.add('active'); });
        document.getElementById('closeEditorBtn').addEventListener('click', () => editorModal.classList.remove('active'));
        document.getElementById('cancelModalBtn').addEventListener('click', () => editorModal.classList.remove('active'));
        document.getElementById('submitModalBtn').addEventListener('click', () => { mainContentTextarea.value = joditEditor.value; editorModal.classList.remove('active'); });

        // 縮圖與時鐘控制
        const imgPopupOverlay = document.getElementById('imgPopupOverlay');
        const imgPopupTarget = document.getElementById('imgPopupTarget');
        const imgPopupDownloadBtn = document.getElementById('imgPopupDownloadBtn');
        marqueeTrack.addEventListener('click', (e) => {
            const isThumb = e.target.classList.contains('wish-table-thumb');
            const isHeroImg = e.target.classList.contains('wish-card-hero-image');
            const isImgLink = e.target.classList.contains('card-img-trigger');
            if (isThumb || isImgLink || isHeroImg) {
                e.preventDefault(); e.stopPropagation();
                const imgSrc = (isThumb || isHeroImg) ? e.target.src : e.target.getAttribute('href');
                imgPopupTarget.src = imgSrc; imgPopupDownloadBtn.href = imgSrc; imgPopupOverlay.classList.add('active');
            }
        });
        document.getElementById('imgPopupCloseBtn').addEventListener('click', () => imgPopupOverlay.classList.remove('active'));

        function updateClock() {
            const now = new Date(); const clockEl = document.getElementById('clock');
            if(clockEl) clockEl.textContent = `${now.getFullYear()}/${now.getMonth() + 1}/${now.getDate()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
        }
        setInterval(updateClock, 1000);
        window.addEventListener('resize', renderMarquee);
        window.addEventListener('DOMContentLoaded', () => { renderMarquee(); updateClock(); });
    </script>
</body>
</html>
<?php
$conn->close();
?>