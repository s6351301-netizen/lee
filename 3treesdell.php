<?php
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
// 2. 處理檔案上傳 API -> 【保留原始中文檔名、支援 UTF-8、防重複實體檔案、勾選後真刪除】
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'upload_icon') {
    header('Content-Type: application/json; charset=utf-8');
    
    // 檢查是否有檔案上傳
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        echo json_encode(['error' => '請選擇要上傳的檔案']);
        exit;
    }

    // 定義根目錄下的 icon 資料夾路徑
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/icon/';
    
    // 若資料夾不存在則自動建立
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $uploaded_urls = [];
    $uploaded_ids = [];
    $errors = [];
    $overwrite = isset($_POST['overwrite']) && $_POST['overwrite'] === 'true';

    // 使用迴圈處理多個檔案上傳
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
        
        // 為了達到使用者「同檔名就覆蓋/刪除舊檔」的需求，直接使用原檔名做處理
        $new_file_name = $safe_original_name;
        
        // 網頁顯示與網址用途的 URL（保持 UTF-8 編碼）
        $web_url = '/icon/' . $new_file_name;
        
        // 如果伺服器是 Windows，作業系統路徑需要將 UTF-8 轉成 BIG5 才能正常寫入實體檔案
        $disk_file_name = $new_file_name;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $disk_file_name = iconv("UTF-8", "BIG5//IGNORE", $new_file_name);
        }
        $target_file_path = $upload_dir . $disk_file_name;

        // 【核心修改】：檢查同名檔案是否存在
        if (file_exists($target_file_path)) {
            if ($overwrite) {
                // 真正刪除舊的實體檔案，不保留歷史檔案
                @unlink($target_file_path);
                
                // 連動將舊檔案的資料庫紀錄直接 DELETE，不保留歷史髒資料
                $stmt_del_old = $conn->prepare("DELETE FROM files WHERE file_name = ?");
                $stmt_del_old->bind_param("s", $safe_original_name);
                $stmt_del_old->execute();
                $stmt_del_old->close();
            } else {
                // 如果前端尚未確認覆蓋，回傳 need_confirm 訊號給前端跳出勾選確認視窗
                echo json_encode(['need_confirm' => true, 'filename' => $safe_original_name]);
                exit;
            }
        }

        // 移動新檔案至目標目錄
        if (move_uploaded_file($file_tmp, $target_file_path)) {
            
            // 寫入 files 表
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

    // 回傳 JSON
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
// 新增/修改功能：API - 刪除祈願卡並真正連動清除雙資料表、實體檔案
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete_wish') {
    header('Content-Type: application/json; charset=utf-8');
    $wish_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($wish_id > 0) {
        $conn->begin_transaction();
        try {
            // 1. 撈出該祈願卡的內文，解析出內部附帶的所有 /icon/ 檔案實體並真正刪除
            $stmt_select = $conn->prepare("SELECT message_of_blessing FROM makeawish WHERE ID = ?");
            $stmt_select->bind_param("i", $wish_id);
            $stmt_select->execute();
            $res_select = $stmt_select->get_result();
            
            if ($row = $res_select->fetch_assoc()) {
                $content = $row['message_of_blessing'];
                
                // 解析內容中所有的實體檔案路徑
                if (preg_match_all('/\/icon\/([^\s"\'\>]+)/', $content, $matches)) {
                    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/icon/';
                    foreach ($matches[1] as $filename_on_url) {
                        $decoded_filename = urldecode($filename_on_url);
                        
                        // 區分系統作業平台進行轉碼以利實體刪除
                        $disk_file_name = $decoded_filename;
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            $disk_file_name = iconv("UTF-8", "BIG5//IGNORE", $decoded_filename);
                        }
                        
                        $target_file_path = $upload_dir . $disk_file_name;
                        // 真正清除指定檔案路徑下的舊實體檔案
                        if (file_exists($target_file_path)) {
                            @unlink($target_file_path);
                        }
                    }
                }
            }
            $stmt_select->close();

            // 2. 連動刪除資料庫紀錄：files 與 makeawish 兩個資料表綁定一併刪除
            $stmt_file_del = $conn->prepare("DELETE FROM files WHERE reference_id = ?");
            $stmt_file_del->bind_param("i", $wish_id);
            $stmt_file_del->execute();
            $stmt_file_del->close();

            $stmt_wish_del = $conn->prepare("DELETE FROM makeawish WHERE ID = ?");
            $stmt_wish_del->bind_param("i", $wish_id);
            $stmt_wish_del->execute();
            $stmt_wish_del->close();

            $conn->commit();
            echo json_encode(['success' => true, 'msg' => '該祈願卡與其關聯之實體檔案、連動資料庫紀錄已完全成功清除！']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => '刪除失敗，錯誤訊息：' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => '無效的祈願卡識別碼']);
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
// 4. 處理表單送出：同時寫入/更新 makeawish 與 更新 files 資料表
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $insert_id = isset($_POST['next_id']) ? intval($_POST['next_id']) : 1;
    $is_edit = isset($_POST['is_edit_mode']) && $_POST['is_edit_mode'] === 'true';
    $name = isset($_POST['author']) ? $_POST['author'] : '';
    
    $emperor_shizu = isset($_POST['hidden_shizu']) ? intval($_POST['hidden_shizu']) : 0;
    $generation_val = isset($_POST['hidden_generation']) ? intval($_POST['hidden_generation']) : 0;
    $number_of_houses = isset($_POST['hidden_houses']) ? intval($_POST['hidden_houses']) : 0;
    
    $new_member_val = isset($_POST['hidden_new_member']) ? trim($_POST['hidden_new_member']) : '0';
    
    $family_members = isset($_POST['familyMember']) ? $_POST['familyMember'] : '';
    $message_of_blessing = isset($_POST['content']) ? $_POST['content'] : '';
    $login_time = isset($_POST['login_time']) ? $_POST['login_time'] : null; 

    $conn->begin_transaction();

    try {
        if ($is_edit) {
            // 【編輯模式】：更新現有祈願內容
            $stmt = $conn->prepare("UPDATE makeawish SET name=?, number_of_houses=?, emperor_shizu=?, generation=?, family_members=?, message_of_blessing=? WHERE ID=?");
            $stmt->bind_param("siiissi", $name, $number_of_houses, $emperor_shizu, $generation_val, $family_members, $message_of_blessing, $insert_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // 【新增模式】：寫入祈願表
            $stmt = $conn->prepare("INSERT INTO makeawish (ID, name, number_of_houses, emperor_shizu, generation, family_members, message_of_blessing, login_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiisss", $insert_id, $name, $number_of_houses, $emperor_shizu, $generation_val, $family_members, $message_of_blessing, $login_time);
            $stmt->execute();
            $stmt->close();
        }

        // B. 更新 files 表中的 reference_id 與上傳者資訊
        if (preg_match_all('/\/icon\/([^\s"\'\>]+)/', $message_of_blessing, $matches)) {
            foreach ($matches[1] as $filename_on_url) {
                $decoded_filename = urldecode($filename_on_url);
                $like_url = "%" . $decoded_filename;
                
                $stmt_update_file = $conn->prepare("UPDATE files SET reference_id = ?, uploaded_id = ?, uploaded_name = ? WHERE file_url LIKE ? AND reference_id = 0");
                $stmt_update_file->bind_param("isss", $insert_id, $new_member_val, $name, $like_url);
                $stmt_update_file->execute();
                $stmt_update_file->close();
            }
        }

        $conn->commit();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('處理失敗：" . addslashes($e->getMessage()) . "');</script>";
    }
}

// ==========================================
// 5. 撈取目前大樹祈願清單
// ==========================================
$max_id = 0;
$sql_max = "SELECT MAX(ID) AS max_id FROM makeawish";
$result_max = $conn->query($sql_max);
if ($result_max && $row_max = $result_max->fetch_assoc()) {
    $max_id = $row_max['max_id'] ? intval($row_max['max_id']) : 0; 
}
$next_id = $max_id + 1;

$wishes_array = [];

$sql_wishes = "SELECT 
                    w.ID,
                    w.name, 
                    w.emperor_shizu, 
                    w.generation, 
                    w.number_of_houses, 
                    w.family_members,
                    w.message_of_blessing
               FROM makeawish w
               ORDER BY w.ID DESC LIMIT 50";

$result_wishes = $conn->query($sql_wishes);
if ($result_wishes && $result_wishes->num_rows > 0) {
    while($w_row = $result_wishes->fetch_assoc()) {
        $wishes_array[] = [
            'id' => intval($w_row['ID']),
            'raw_name' => $w_row['name'],
            'author' => $w_row['name'] . " (" ."第" . $w_row['number_of_houses'] . "大房)",
            'shizu_num' => intval($w_row['emperor_shizu']),
            'gen_num' => intval($w_row['generation']),
            'house_num' => intval($w_row['number_of_houses']),
            'emperor_shizu' => "來台第" . $w_row['emperor_shizu'] . "世祖",
            'generation' => "定居大甲第" . $w_row['generation'] . "代",
            'family_members' => $w_row['family_members'],
            'content' => $w_row['message_of_blessing']
        ];
    }
} else {
    $wishes_array = [
        [ "id" => 1, "raw_name" => "國華", "author" => "1長房大堂哥 國華 (第1大房)", "shizu_num"=>24,"gen_num"=>5,"house_num"=>1,"emperor_shizu" => "來台第24世祖", "generation" => "定居大甲第5代", "family_members"=>"全家", "content" => "感念曾祖父當年用一雙長滿繭的手..." ],
        [ "id" => 2, "raw_name" => "佩芬", "author" => "2二房 佩芬 (第2大房)", "shizu_num"=>24,"gen_num"=>5,"house_num"=>2,"emperor_shizu" => "來台第24世祖", "generation" => "定居大甲第5代", "family_members"=>"全家", "content" => "小時候總聽阿公說『吃米擔水要思源』..." ]
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
        header { text-align: center; width: 100%; max-width: 900px; margin-bottom: 15px; animation: fadeInDown 1s ease; }
        header h1 { font-size: 2.3rem; color: #a3ccab; margin-bottom: 8px; letter-spacing: 3px; font-weight: 700; text-shadow: 0 0 12px rgba(163, 204, 171, 0.3); }
        header h2 { font-size: 1.4rem; margin-bottom: 6px; color: #ece6dc; }
        header h3 { font-size: 1.1rem; margin-bottom: 8px; color: #f39c12; font-weight: 500; }
        header p { font-size: 0.95rem; line-height: 1.5; color: #cbd5e1; }

        /* 🚀 新增：頂端模糊查詢工具列區塊樣式 */
        .search-bar-container {
            width: 100%; max-width: 600px; margin: 10px auto 20px auto; display: flex; flex-direction: column; position: relative; z-index: 20;
        }
        .search-input-box {
            width: 100%; padding: 12px 18px; border: 2px solid rgba(163, 204, 171, 0.6); border-radius: 30px;
            background: rgba(10, 31, 20, 0.85); color: #fff; font-size: 1rem; text-align: center; backdrop-filter: blur(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.4); font-family: inherit; transition: all 0.3s;
        }
        .search-input-box:focus { outline: none; border-color: #f39c12; box-shadow: 0 0 15px rgba(243, 156, 18, 0.4); }
        .top-search-results-wrapper {
            position: absolute; top: 110%; left: 0; width: 100%; background: rgba(10, 31, 20, 0.98);
            border: 1px solid #a3ccab; border-radius: 12px; max-height: 250px; overflow-y: auto; display: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.6); padding: 5px; z-index: 999;
        }

        .open-sidebar-btn {
            position: fixed; right: 20px; top: 20px; background: rgba(163, 204, 171, 0.25); color: #a3ccab; border: 1px solid rgba(163, 204, 171, 0.6);
            padding: 10px 18px; border-radius: 25px; cursor: pointer; font-family: inherit; font-weight: bold; backdrop-filter: blur(8px); transition: all 0.3s; z-index: 10; 
        }
        .open-sidebar-btn:hover { background: #407a52; color: #fff; transform: translateY(-2px); }

        .scroll-container { width: 100%; max-width: 1100px; height: 52vh; overflow: hidden; position: relative; mask-image: linear-gradient(to bottom, transparent 0%, black 8%, black 92%, transparent 100%); }
        .marquee-track { display: flex; flex-direction: column; gap: 25px; width: 100%; position: absolute; top: 0; left: 0; animation: scrollUp 35s infinite linear; }
        .marquee-track:hover { animation-play-state: paused; }
        .wish-row { display: flex; justify-content: center; align-items: flex-start; gap: 25px; width: 100%; }
        @keyframes scrollUp { 0% { transform: translateY(0); } 100% { transform: translateY(-50%); } }

        /* 卡片主體與表格樣式 */
        .wish-card {
            background: rgba(20, 54, 34, 0.65); border-radius: 6px 6px 12px 12px; padding: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); backdrop-filter: blur(8px); position: relative; 
            transition: transform 0.4s, background 0.4s, z-index 0.4s; width: 340px; min-width: 320px;
            overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
        }
        .wish-card:hover { transform: scale(1.03) !important; background: rgba(20, 54, 34, 0.85); z-index: 50; }
        .wish-card::before {
            content: ''; position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            width: 10px; height: 10px; background: #f39c12; border-radius: 50%; box-shadow: 0 0 8px #f39c12;
            z-index: 4;
        }

        /* 🚀 圓圈右手邊的浮動操作視窗(Tooltip) */
        .wish-card-actions {
            position: absolute;
            top: 2px;
            left: calc(50% + 12px);
            display: flex;
            gap: 6px;
            background: rgba(10, 31, 20, 0.9);
            border: 1px solid #f39c12;
            padding: 3px 8px;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            z-index: 10;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: translateX(-5px);
        }
        .wish-card:hover .wish-card-actions {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(0);
        }
        .wish-action-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
            color: #fff;
            transition: background 0.2s;
        }
        .wish-action-edit { color: #5cc2f2; }
        .wish-action-edit:hover { background: rgba(92, 194, 242, 0.2); }
        .wish-action-delete { color: #f25c5c; }
        .wish-action-delete:hover { background: rgba(242, 92, 92, 0.2); }

        /* 卡片頂部首圖區塊樣式 */
        .wish-card-hero-image-wrapper {
            width: calc(100% + 44px); margin-left: -22px; margin-top: -22px; margin-bottom: 15px;
            height: 160px; overflow: hidden; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative; cursor: pointer; background: #000;
        }
        .wish-card-hero-image {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;
        }
        .wish-card-hero-image-wrapper:hover .wish-card-hero-image {
            transform: scale(1.06);
        }

        .wish-content { font-size: 0.95rem; line-height: 1.6; color: #ece6dc; margin-bottom: 15px; min-height: 65px; flex-grow: 1; display: block; overflow: hidden; }
        .wish-content > img { display: none !important; }

        .wish-file-table {
            width: 100%; border-collapse: collapse; margin-top: 12px; margin-bottom: 8px;
            background: rgba(0, 0, 0, 0.2); border-radius: 6px; overflow: hidden; font-size: 0.85rem;
        }
        .wish-file-table tr { border-bottom: 1px solid rgba(255, 255, 255, 0.08); transition: background 0.2s; }
        .wish-file-table tr:last-child { border-bottom: none; }
        .wish-file-table tr:hover { background: rgba(255, 255, 255, 0.05); }
        .wish-file-table td { padding: 8px 10px; vertical-align: middle; color: #e9e4db; text-align: left; }
        .wish-file-icon { width: 24px; font-size: 1.1rem; text-align: center; }
        .wish-file-name { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .wish-file-name a { color: #a3ccab; text-decoration: none; font-weight: 500; }
        .wish-file-name a:hover { color: #f39c12; text-decoration: underline; }
        
        .wish-table-thumb {
            width: 42px; height: 32px; object-fit: cover; border-radius: 3px; 
            cursor: pointer; border: 1px solid rgba(255, 255, 255, 0.2); transition: transform 0.2s;
        }
        .wish-table-thumb:hover { transform: scale(1.1); border-color: #f39c12; }

        /* 大圖彈出視窗 */
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
            cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: background 0.2s, transform 0.2s;
        }
        .img-popup-download-btn { background: #407a52; font-size: 18px; }
        .img-popup-download-btn:hover { background: #2d5a3a; transform: scale(1.1); }
        .img-popup-close-btn { background: #e63946; font-size: 20px; }
        .img-popup-close-btn:hover { background: #d62828; transform: scale(1.1) rotate(90deg); }

        @keyframes zoomInQuick { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }

        .wish-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; border-top: 1px dashed rgba(255, 255, 255, 0.2); padding-top: 10px; }
        .wish-author { font-weight: bold; color: #f39c12; }
        .wish-generation { background-color: rgba(163, 204, 171, 0.2); color: #a3ccab; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }

        /* 右側輸入視窗 */
        .sidebar {
            position: fixed; top: 0; right: -100%; width: 32%; min-width: 350px; max-width: 440px; height: 100vh;
            background: rgba(10, 31, 20, 0.95); backdrop-filter: blur(20px); padding: 50px 30px; display: flex; flex-direction: column;
            box-shadow: -10px 0 35px rgba(0, 0, 0, 0.6); border-left: 1px solid rgba(255, 255, 255, 0.08); z-index: 100; transition: right 0.5s cubic-bezier(0.16, 1, 0.3, 1); overflow-y: auto;
        }
        .sidebar.active { right: 0; }
        .close-sidebar-btn { position: absolute; top: 15px; right: 20px; background: transparent; border: none; color: #94a3b8; font-size: 2rem; cursor: pointer; transition: transform 0.3s; }
        .close-sidebar-btn:hover { color: #f39c12; transform: rotate(90deg); }

        .counter-box { font-size: 1rem; background: rgba(163, 204, 171, 0.1); padding: 8px 15px; border-radius: 8px; text-align: center; color: #a3ccab; font-weight: bold; margin-bottom: 15px; border: 1px solid rgba(163, 204, 171, 0.2); }
        .counter-box span { font-size: 1.4rem; color: #f39c12; margin: 0 4px; }
        .form-title { font-size: 1.4rem; color: #a3ccab; margin-bottom: 20px; font-weight: bold; border-bottom: 2px solid #a3ccab; padding-bottom: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.95rem; margin-bottom: 8px; color: #cbd5e1; font-weight: bold; }
        .label-text-header { font-size: 0.9rem; color: #a3ccab; margin-bottom: 6px; display: block; line-height: 1.5; }
        
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; font-family: inherit; font-size: 1rem; background-color: rgba(5, 15, 10, 0.7); color: #e9e4db; transition: all 0.3s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #a3ccab; box-shadow: 0 0 8px rgba(163, 204, 171, 0.3); }
        textarea { resize: none; height: 150px; }
        select:disabled { background-color: rgba(30, 45, 35, 0.8); color: #a3ccab; cursor: not-allowed; border-color: rgba(163, 204, 171, 0.4); }

        .member-select-wrapper { background: rgba(0, 0, 0, 0.3); border: 1px dashed rgba(163, 204, 171, 0.4); border-radius: 8px; padding: 10px; margin-top: 10px; display: none; max-height: 160px; overflow-y: auto; }
        .member-item { display: flex; align-items: center; gap: 10px; padding: 6px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.88rem; cursor: pointer; }
        .member-item:last-child { border-bottom: none; }
        .member-item input[type="checkbox"] { width: auto; cursor: pointer; margin-right: 5px; }

        .submit-btn { width: 100%; background: linear-gradient(135deg, #407a52, #143622); color: #e9e4db; border: none; padding: 14px; font-size: 1.1rem; font-weight: bold; border-radius: 8px; cursor: pointer; transition: all 0.3s; margin-top: 5px; margin-bottom: 20px; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(163, 204, 171, 0.3); }
        .sidebar-footer { font-size: 0.85rem; color: #94a3b8; text-align: center; }
        .highlight-val { color: #f39c12; font-weight: bold; margin: 0 3px; }

        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-width: 100%; padding: 40px 20px; }
            .open-sidebar-btn { position: static; margin-bottom: 15px; display: block; }
            .scroll-container { height: 42vh; }
            .wish-row { flex-direction: column; align-items: center; }
        }
        .marquee-input { width: 300px; padding: 10px; font-size: 16px; overflow: hidden; white-space: nowrap; }

        /* Jodit 編輯器樣式彈出視窗 */
        .editor-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 32, 39, 0.7); backdrop-filter: blur(5px); z-index: 200; display: none; align-items: center; justify-content: center; }
        .editor-modal-overlay.active { display: flex; }
        .editor-modal-content { background: #eef7f4; border: 2px solid #a3d8f4; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; box-shadow: 0 12px 40px rgba(0,0,0,0.25); width: 90%; height: 90%; max-width: 95vw; max-height: 95vh; }
        .editor-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #bae6fd; padding-bottom: 10px; }
        .editor-modal-title { font-size: 1.2rem; color: #1e3a8a; font-weight: bold; }
        .editor-modal-close { background: transparent; border: none; color: #64748b; font-size: 1.8rem; cursor: pointer; }
        
        .editor-container-box { flex: 1; min-height: 0; margin-bottom: 15px; height: 100%; }
        .editor-modal-footer { display: flex; justify-content: flex-end; gap: 15px; }
        .modal-btn { padding: 10px 24px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; transition: all 0.2s; }
        .modal-btn-cancel { background: #94a3b8; color: #ffffff; }
        .modal-btn-submit { background: #0284c7; color: #ffffff; }

        .jodit-container { background: #f0fdf4 !important; color: #000000 !important; height: 100% !important; border: 1px solid #cbd5e1 !important; }
        .jodit-toolbar__box { background: #e0f2fe !important; border-bottom: 1px solid #bae6fd !important; }
        .jodit-toolbar-button__icon { fill: #1e293b !important; }
        .jodit-status-bar { background: #e0f2fe !important; color: #334155 !important; border-top: 1px solid #bae6fd !important; }
        .expand-link { font-size: 0.85rem; color: #f39c12; cursor: pointer; margin-left: 10px; text-decoration: underline; font-weight: normal; }
        .expand-link:hover { color: #ffd166; }

        .jodit-wysiwyg { color: #000000; }
        .jodit-wysiwyg a { text-decoration: underline !important; }
        .jodit-source__textarea, .jodit-src, .jodit-source, .jodit-source__textarea textarea { color: #000000 !important; background-color: #ffffff !important; background: #ffffff !important; text-shadow: none !important; }
        .jodit-container .ace_editor { background-color: #ffffff !important; }
        .jodit-container .ace_editor .ace_scroller { background-color: #ffffff !important; }
        .jodit-container .ace_editor * { text-shadow: none !important; background-color: transparent !important; }
        .jodit-container .ace_editor .ace_text-layer { color: #000000 !important; }

        .jodit-popup, .jodit-popup__content, .jodit-popup__container, .jodit-dialog, .jodit-dialog__box, .jodit-dialog__content, .jodit-dialog__header, .jodit-dialog__footer, .jodit-toolbar-list, .jodit-properties, .jodit-ui-form { background-color: #e0f2fe !important; color: #0f172a !important; border-color: #7dd3fc !important; }
        .jodit-popup__content *, .jodit-toolbar-list *, .jodit-toolbar-button, .jodit-toolbar-list .jodit-toolbar-button__text { color: #000000 !important; }
        .jodit-popup__content .jodit-colorpicker * { color: inherit !important; }
        .jodit-nav-button:hover, .jodit-toolbar-button:hover, .jodit-popup__content .jodit-toolbar-button:hover { background-color: #bae6fd !important; }
        .jodit-dialog input, .jodit-dialog select, .jodit-dialog textarea, .jodit-popup input, .jodit-popup select, .jodit-popup textarea, .jodit-ui-form input, .jodit-ui-form select, .jodit-ui-form textarea, .jproperties input, .jodit-properties select, .jodit-properties textarea { color: #000000 !important; background-color: #f0f9ff !important; border: 1px solid #7dd3fc !important; }
        .jodit-ui-form label, .jodit-ui-label, .jodit-dialog__content label, .jodit-dialog__content .jodit-ui-label { color: #1e3a8a !important; font-weight: bold !important; }
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
            
            <div class="search-bar-container">
                <input type="text" id="topSearchInput" class="search-input-box" placeholder="🔍 輸入會員ID (新會員號) 或 姓名 做模糊查詢..." autocomplete="off">
                <div class="top-search-results-wrapper" id="topSearchResults"></div>
            </div>

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
        <div class="form-title" id="sidebarFormTitle">🌿 撰寫祈願卡</div>        
        <form id="wishForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            
            <input type="hidden" id="next_id" name="next_id" value="<?php echo $next_id; ?>">
            <input type="hidden" id="is_edit_mode" name="is_edit_mode" value="false">
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
            </div>

            <button type="submit" class="submit-btn" id="submitFormBtn">掛上祈願樹(送出)➔</button>
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

        document.getElementById('openSidebarBtn').addEventListener('click', () => {
            resetFormToCreate();
            sidebar.classList.add('active');
        });
        document.getElementById('closeSidebarBtn').addEventListener('click', () => sidebar.classList.remove('active'));

        function resetFormToCreate() {
            document.getElementById('sidebarFormTitle').textContent = "🌿 撰寫祈願卡";
            document.getElementById('submitFormBtn').textContent = "掛上祈願樹(送出)➔";
            document.getElementById('is_edit_mode').value = "false";
            document.getElementById('next_id').value = "<?php echo $next_id; ?>";
            document.getElementById('wishForm').reset();
            clearLabelsAndHiddens();
        }

        // 解析文字中的連結與圖片，格式化為一行一行的 Table 輸出
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
                
                if (idx === 0) {
                    heroImageUrl = url;
                } else {
                    filesArray.push({ type: 'image', url: url, name: name });
                }
                img.remove(); 
            });

            fileLinks.forEach(link => {
                const url = link.getAttribute('href');
                if (url === heroImageUrl) {
                    link.remove();
                    return;
                }

                let name = link.textContent.replace('📎 下載附件:', '').trim();
                if(!name) {
                    name = url.substring(url.lastIndexOf('/') + 1);
                    try { name = decodeURIComponent(name); } catch(e) {}
                }
                
                const isImageFile = /\.(jpg|jpeg|png|gif|webp)$/i.test(url);
                if (isImageFile) {
                    if (!filesArray.some(f => f.url === url)) {
                        filesArray.push({ type: 'image', url: url, name: name });
                    }
                } else {
                    filesArray.push({ type: 'file', url: url, name: name });
                }
                link.remove(); 
            });

            let tableHtml = '';
            if (filesArray.length > 0) {
                tableHtml = '<table class="wish-file-table">';
                filesArray.forEach(file => {
                    let displayName = file.name;
                    if (displayName.includes('_')) {
                        const parts = displayName.split('_');
                        if (parts.length >= 4 && /^\d{8}$/.test(parts[0])) {
                            displayName = parts.slice(3).join('_');
                        }
                    }

                    if (file.type === 'image') {
                        tableHtml += `
                            <tr>
                                <td class="wish-file-icon">🖼️</td>
                                <td class="wish-file-name"><a href="${file.url}" class="card-img-trigger" target="_blank">${displayName}</a></td>
                                <td style="text-align: right; width: 60px;"><img src="${file.url}" class="wish-table-thumb" alt="縮圖"></td>
                            </tr>`;
                    } else {
                        tableHtml += `
                            <tr>
                                <td class="wish-file-icon">📎</td>
                                <td class="wish-file-name" colspan="2"><a href="${file.url}" target="_blank">下載附件: ${displayName}</a></td>
                            </tr>`;
                    }
                });
                tableHtml += '</table>';
            }

            return {
                cleanHtml: tempDiv.innerHTML,
                tableHtml: tableHtml,
                heroImageUrl: heroImageUrl
            };
        }

        // 🚀 建構含有編輯與刪除功能的卡片
        function createCardNode(wish) {
            const card = document.createElement('div');
            card.className = 'wish-card';
            const randomRotate = (Math.random() * 4 - 2).toFixed(1);
            card.style.transform = `rotate(${randomRotate}deg)`;
            
            const parsed = parseContentFilesToTable(wish.content);

            let heroImageHtml = '';
            if (parsed.heroImageUrl) {
                heroImageHtml = `
                    <div class="wish-card-hero-image-wrapper">
                        <img src="${parsed.heroImageUrl}" class="wish-card-hero-image card-img-trigger" alt="主視覺圖片">
                    </div>
                `;
            }

            // 注入浮動操作欄與編輯、連動刪除按鈕
            card.innerHTML = `
                <div class="wish-card-actions">
                    <button class="wish-action-btn wish-action-edit" onclick="triggerEditWish(${wish.id})">編輯 ✏️</button>
                    <button class="wish-action-btn wish-action-delete" onclick="triggerDeleteWish(${wish.id})">刪除 ❌</button>
                </div>
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

        // 前端發動編輯
        function triggerEditWish(id) {
            const target = wishesData.find(item => item.id === id);
            if (!target) return;
            
            document.getElementById('sidebarFormTitle').textContent = "✏️ 編輯祈願卡";
            document.getElementById('submitFormBtn').textContent = "更新祈願卡➔";
            document.getElementById('is_edit_mode').value = "true";
            document.getElementById('next_id').value = id;
            
            document.getElementById('author').value = target.raw_name;
            document.getElementById('familyMember').value = target.family_members;
            document.getElementById('content').value = target.content;
            
            applyMemberValues({
                name: target.raw_name,
                new_member: "",
                shizu: target.shizu_num,
                gen: target.gen_num,
                houses: target.house_num
            });

            sidebar.classList.add('active');
        }

        // 前端發動刪除 (雙表與實體附加檔案連動真刪除)
        function triggerDeleteWish(id) {
            if (confirm("您確定要刪除此張祈願卡嗎？\n確認後將會連同指定路徑上的附加上傳檔案一併實體刪除，且雙資料表連動資料均不予保留，無法還原！")) {
                fetch(`?action=delete_wish&id=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.msg);
                            location.reload();
                        } else {
                            alert("刪除失敗：" + data.error);
                        }
                    });
            }
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
                for (let i = 0; i < needAdds; i++) {
                    processedWishes.push(wishesData[wishesData.length - 1 - i] || wishesData[0]);
                }
            }

            const rowsData = [];
            for (let i = 0; i < processedWishes.length; i += itemsPerRow) {
                rowsData.push(processedWishes.slice(i, i + itemsPerRow));
            }

            rowsData.forEach(rowItems => {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'wish-row';
                rowItems.forEach(item => rowDiv.appendChild(createCardNode(item)));
                marqueeTrack.appendChild(rowDiv);
            });
            const totalRows = rowsData.length;
            const speedFactor = 8.5;
            marqueeTrack.style.animationDuration = `${totalRows * speedFactor}s`;            
        }

        // ==========================================
        // 🚀 新增功能：網頁上端用會員ID與姓名做模糊查詢功能控制
        // ==========================================
        const topSearchInput = document.getElementById('topSearchInput');
        const topSearchResults = document.getElementById('topSearchResults');

        topSearchInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length < 1) {
                topSearchResults.style.display = 'none';
                topSearchResults.innerHTML = '';
                return;
            }

            // 直接呼叫 get_houses API 接口進行模糊比對
            fetch(`?action=get_houses&new_member=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    topSearchResults.innerHTML = '';
                    if (data && data.length > 0) {
                        topSearchResults.style.display = 'block';
                        
                        data.forEach(member => {
                            const item = document.createElement('div');
                            item.className = 'member-item';
                            item.style.padding = '10px';
                            item.style.color = '#fff';
                            item.innerHTML = `🔍 <strong>${member.name}</strong> (編號: ${member.new_member}) &emsp; 來台第${member.emperor_shizu}世祖 / 定居大甲第${member.generation}代 / 第${member.number_of_houses}房`;
                            
                            // 點選模糊查詢結果後，直接幫使用者自動開啟撰寫視窗並帶入世代資料
                            item.addEventListener('click', () => {
                                resetFormToCreate();
                                applyMemberValues({
                                    name: member.name,
                                    new_member: member.new_member,
                                    shizu: member.emperor_shizu,
                                    gen: member.generation,
                                    houses: member.number_of_houses
                                });
                                topSearchResults.style.display = 'none';
                                topSearchInput.value = `${member.name} (${member.new_member})`;
                                sidebar.classList.add('active');
                            });
                            topSearchResults.appendChild(item);
                        });
                    } else {
                        topSearchResults.innerHTML = '<div style="padding:10px; color:#aaa; text-align:center;">未找到符合此條件之家族會員</div>';
                        topSearchResults.style.display = 'block';
                    }
                });
        });

        // 點擊網頁外部時自動隱藏上端搜尋結果區
        document.addEventListener('click', function(e) {
            if (e.target !== topSearchInput && e.target !== topSearchResults) {
                topSearchResults.style.display = 'none';
            }
        });

        // ==========================================
        // 原側邊欄 AJAX 查詢與動態欄位填充
        // ==========================================
        const authorInput = document.getElementById('author');
        const wrapper = document.getElementById('memberSelectWrapper');

        authorInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length < 1) {
                wrapper.style.display = 'none';
                wrapper.innerHTML = '';
                clearLabelsAndHiddens();
                return;
            }

            fetch(`?action=get_houses&new_member=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    wrapper.innerHTML = '';
                    if (data && data.length > 0) {
                        wrapper.style.display = 'block';
                        
                        data.forEach((member, index) => {
                            const item = document.createElement('div');
                            item.className = 'member-item';
                            
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'selected_member_cb';
                            checkbox.value = index;
                            
                            checkbox.dataset.name = member.name;
                            checkbox.dataset.new_member = member.new_member;
                            checkbox.dataset.shizu = member.emperor_shizu;
                            checkbox.dataset.gen = member.generation;
                            checkbox.dataset.houses = member.number_of_houses;

                            checkbox.addEventListener('change', function() {
                                if (this.checked) {
                                    document.querySelectorAll('input[name="selected_member_cb"]').forEach(cb => {
                                        if (cb !== this) cb.checked = false;
                                    });
                                    applyMemberValues(this.dataset);
                                } else {
                                    clearLabelsAndHiddens();
                                }
                            });

                            const textLabel = document.createElement('span');
                            textLabel.textContent = `${member.name} ${member.new_member} 號${','} 第 ${member.emperor_shizu}世祖/ 第${member.generation}代/${member.number_of_houses}房`;

                            item.addEventListener('click', function(e) {
                                if (e.target !== checkbox) {
                                    checkbox.checked = !checkbox.checked;
                                    checkbox.dispatchEvent(new Event('change'));
                                }
                            });

                            item.appendChild(checkbox);
                            item.appendChild(textLabel);
                            wrapper.appendChild(item);

                            if(data.length === 1) {
                                checkbox.checked = true;
                                applyMemberValues(checkbox.dataset);
                            }
                        });
                    } else {
                        wrapper.style.display = 'none';
                        clearLabelsAndHiddens();
                    }
                })
                .catch(err => console.error('AJAX 查詢出錯:', err));
        });

        function applyMemberValues(dataset) {
            document.getElementById('show_name').textContent = dataset.name;
            document.getElementById('show_member_id').textContent = dataset.new_member || "?";
            document.getElementById('show_shizu').textContent = dataset.shizu;
            document.getElementById('show_generation').textContent = dataset.gen;
            document.getElementById('show_houses').textContent = dataset.houses;
            document.getElementById('hidden_shizu').value = dataset.shizu;
            document.getElementById('hidden_generation').value = dataset.gen;
            document.getElementById('hidden_houses').value = dataset.houses;
            document.getElementById('hidden_new_member').value = dataset.new_member || "0";
            
            const genSelect = document.getElementById('generation');
            if(genSelect.querySelector(`option[value="${dataset.shizu}"]`)){
                genSelect.value = dataset.shizu;
                genSelect.disabled = true; 
            } else if(genSelect.querySelector(`option[value="${dataset.gen}"]`)){
                genSelect.value = dataset.gen;
                genSelect.disabled = true; 
            }
            authorInput.value = dataset.name;
        }

        function clearLabelsAndHiddens() {
            document.getElementById('show_name').textContent = '?';
            document.getElementById('show_member_id').textContent = '?';
            document.getElementById('show_shizu').textContent = '?';
            document.getElementById('show_generation').textContent = '?';
            document.getElementById('show_houses').textContent = '?';
            document.getElementById('hidden_shizu').value = 0;
            document.getElementById('hidden_generation').value = 0;
            document.getElementById('hidden_houses').value = 0;
            document.getElementById('hidden_new_member').value = 0;

            const genSelect = document.getElementById('generation');
            genSelect.disabled = false;
            genSelect.value = "";
        }

        document.getElementById('wishForm').addEventListener('submit', function(e) {
            if (document.getElementById('hidden_shizu').value === "0" && document.getElementById('show_shizu').textContent === "?") {
                alert("姓名與世代為必填！！不開放「祈願卡」給訪客無會員編號填寫！");
                e.preventDefault();
                return;
            }
            const now = new Date();
            const formattedTime = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')} :${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            document.getElementById('login_time').value = formattedTime;
            document.getElementById('generation').disabled = false; 
        });

        // ==========================================
        // Jodit 編輯器配置
        // ==========================================
        const fullFreeButtons = [
            'source', '|', 'bold', 'strikethrough', 'underline', 'italic', '|',
            'superscript', 'subscript', '|', 'ul', 'ol', '|',
            'outdent', 'indent', '|', 'font', 'fontsize', 'brush', 'paragraph', '|',
            'image', 'file', 'video', 'table', 'link', '|', 'align', 'undo', 'redo', '|',
            'hr', 'eraser', 'copyformat', '|', 'symbol', 'print', 'about'
        ];

        const joditEditor = new Jodit('#joditEditorTarget', {
            buttons: fullFreeButtons, buttonsMD: fullFreeButtons, buttonsSM: fullFreeButtons, buttonsXS: fullFreeButtons, 
            disablePlugins: [], height: '100%', language: 'zh_tw', style: { color: '#000000' },
            controls: {
                font: {
                    list: {
                        'Microsoft JhengHei, sans-serif': '微軟正黑體',
                        'PMingLiU, serif': '新細明體',
                        'DFKai-SB, serif': '標楷體',
                        'PingFang TC, sans-serif': '蘋方體 (Mac)',
                        'Arial, Helvetica, sans-serif': 'Arial (無襯線體)',
                        'Times New Roman, Times, serif': 'Times New Roman (襯線體)'
                    }
                }
            },
            uploader: {
                url: '?action=upload_icon', 
                format: 'json',
                path: 'files',
                multiple: false, 
                isSuccess: function (resp) { return resp.success === true || resp.need_confirm === true; },
                process: function (resp) { return resp; },
                defaultHandlerSuccess: function (resp) {
                    // 🚀【核心修改】：若伺服器已存在同檔名檔案，跳出勾選視窗供使用者確認是否實體覆蓋
                    if (resp.need_confirm) {
                        if (confirm(`伺服器上已存在同名檔案 [${resp.filename}]。\n是否確認刪除原來的舊檔並進行覆蓋？（警告：歷史舊檔將被完全移除）`)) {
                            // 使用者勾選同意覆蓋：重新附帶 overwrite=true 參數進行強制實體刪除與覆蓋
                            const fileInput = this.files[0]; 
                            const formData = new FormData();
                            formData.append('files[]', fileInput);
                            formData.append('overwrite', 'true');

                            fetch('?action=upload_icon', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(retryResp => {
                                if (retryResp.success) {
                                    insertUploadedFiles(this.jodit, retryResp.files);
                                } else {
                                    alert('檔案覆蓋失敗：' + retryResp.error);
                                }
                            });
                        }
                        return;
                    }

                    if (resp.success && resp.files && resp.files.length) {
                        insertUploadedFiles(this.jodit, resp.files);
                    }
                }
            }
        });

        function insertUploadedFiles(editorInstance, files) {
            files.forEach(fileUrl => {
                const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileUrl);
                let displayFileName = fileUrl.substring(fileUrl.lastIndexOf('/') + 1);
                try { displayFileName = decodeURIComponent(displayFileName); } catch(e) {}
                
                if (isImage) {
                    editorInstance.s.insertImage(fileUrl, null, 200); 
                } else {
                    editorInstance.s.insertHTML(`<a href="${fileUrl}" target="_blank" style="color: #0284c7; text-decoration: underline;">📎 下載附件: ${displayFileName}</a>&nbsp;`);
                }
            });
        }

        const editorModal = document.getElementById('editorModal');
        const mainContentTextarea = document.getElementById('content');

        document.getElementById('openEditorBtn').addEventListener('click', function() {
            joditEditor.value = mainContentTextarea.value;
            editorModal.classList.add('active');
            setTimeout(() => { joditEditor.events.fire('resize'); }, 50);
        });

        function closeModal() { editorModal.classList.remove('active'); }
        document.getElementById('closeEditorBtn').addEventListener('click', closeModal);
        document.getElementById('cancelModalBtn').addEventListener('click', closeModal);

        document.getElementById('submitModalBtn').addEventListener('click', function() {
            mainContentTextarea.value = joditEditor.value;
            closeModal();
        });

        // ==========================================
        // 點擊卡片縮圖或首圖大圖彈出控制
        // ==========================================
        const imgPopupOverlay = document.getElementById('imgPopupOverlay');
        const imgPopupTarget = document.getElementById('imgPopupTarget');
        const imgPopupCloseBtn = document.getElementById('imgPopupCloseBtn');
        const imgPopupDownloadBtn = document.getElementById('imgPopupDownloadBtn'); 

        marqueeTrack.addEventListener('click', function(e) {
            const isThumb = e.target.classList.contains('wish-table-thumb');
            const isHeroImg = e.target.classList.contains('wish-card-hero-image');
            const isImgLink = e.target.classList.contains('card-img-trigger');
            
            if (isThumb || isImgLink || isHeroImg) {
                e.preventDefault();
                e.stopPropagation(); 
                
                const imgSrc = (isThumb || isHeroImg) ? e.target.src : e.target.getAttribute('href');
                
                imgPopupTarget.src = imgSrc; 
                imgPopupDownloadBtn.href = imgSrc; 
                imgPopupOverlay.classList.add('active'); 
            }
        });

        imgPopupCloseBtn.addEventListener('click', closeImagePopup);
        imgPopupOverlay.addEventListener('click', function(e) { if (e.target === imgPopupOverlay) closeImagePopup(); });

        function closeImagePopup() {
            imgPopupOverlay.classList.remove('active');
            setTimeout(() => { imgPopupTarget.src = ''; imgPopupDownloadBtn.href = ''; }, 200); 
        }

        // ==========================================
        // 即時動態時鐘功能
        // ==========================================
        function updateClock() {
            const now = new Date();
            const clockEl = document.getElementById('clock');
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