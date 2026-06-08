<?php
// ==========================================
// 1. 資料庫連線設定
// ==========================================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("連線失敗: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$message_notice = "";

// ==========================================
// 處理「編輯更新」邏輯 (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_wish') {
    $update_id = intval($_POST['update_id']);
    $u_generation = trim($_POST['u_generation']);
    $u_name = trim($_POST['u_name']);
    $u_family = trim($_POST['u_family']);
    $u_content = $_POST['u_content'];
    $u_time = trim($_POST['u_time']);

    // 更新 makeawish 主資料表 (同步更新世代與姓名)
    $update_sql = "UPDATE makeawish SET name = ?, generation = ?, family_members = ?, message_of_blessing = ?, login_time = ? WHERE ID = ?";
    $u_stmt = $conn->prepare($update_sql);
    $u_stmt->bind_param("sisssi", $u_name, $u_generation, $u_family, $u_content, $u_time, $update_id);

    if ($u_stmt->execute()) {
        $message_notice = "許願ID #{$update_id} 資料已成功更新！";
    } else {
        $message_notice = "更新失敗：" . $conn->error;
    }
    $u_stmt->close();
}

// ==========================================
// 處理「刪除」邏輯 (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $ids_to_delete = [];
    if (isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
        $ids_to_delete = array_map('intval', $_POST['delete_ids']);
    } elseif (isset($_POST['single_delete_id'])) {
        $ids_to_delete[] = intval($_POST['single_delete_id']);
    }

    if (!empty($ids_to_delete)) {
        $deleted_count = 0;
        $file_base_dir = "D:\\web02\\icon\\";

        foreach ($ids_to_delete as $del_id) {
            $file_sql = "SELECT f.file_path FROM files f WHERE f.file_id = ?";
            $f_stmt = $conn->prepare($file_sql);
            $f_stmt->bind_param("i", $del_id);
            $f_stmt->execute();
            $f_result = $f_stmt->get_result();

            while ($photo = $f_result->fetch_assoc()) {
                $url = $photo['file_path'];
                if (!empty($url)) {
                    $pure_filename = basename($url);
                    $full_real_path = $file_base_dir . $pure_filename;
                    if (file_exists($full_real_path)) {
                        unlink($full_real_path);
                    } else if (file_exists($url)) {
                        unlink($url);
                    }
                }
            }
            $f_stmt->close();

            $del_files_sql = "DELETE FROM files WHERE file_id = ?";
            $df_stmt = $conn->prepare($del_files_sql);
            $df_stmt->bind_param("i", $del_id);
            $df_stmt->execute();
            $df_stmt->close();

            $del_wish_sql = "DELETE FROM makeawish WHERE ID = ?";
            $dw_stmt = $conn->prepare($del_wish_sql);
            $dw_stmt->bind_param("i", $del_id);
            $dw_stmt->execute();

            if ($dw_stmt->affected_rows > 0) {
                $deleted_count++;
            }
            $dw_stmt->close();
        }
        $message_notice = "成功刪除 {$deleted_count} 筆祈願紀錄，並已確實清空 D:\\web02\\icon\\ 內實體檔案！";
    }
}

// ==========================================
// 🚀 AJAX 讀取單筆資料 API 
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'get_single_wish') {
    header('Content-Type: application/json; charset=utf-8');
    $get_id = intval($_GET['id']);

    $sql = "SELECT w.ID AS id, w.name, w.generation, w.family_members, w.message_of_blessing, w.login_time, 
                   m.new_member, m.number_of_houses, m.emperor_shizu 
            FROM makeawish w 
            LEFT JOIN members m ON w.name = m.name 
            WHERE w.ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $get_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo json_encode($row ? $row : ['error' => '找不到資料']);
    $stmt->close();
    exit;
}

// ==========================================
// 🚀 AJAX 根據姓名查找成員（支援同名同姓多筆回傳）
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'get_member_info_by_name') {
    header('Content-Type: application/json; charset=utf-8');
    $query_name = trim($_GET['name']);

    // 直接從 members 中撈出所有符合姓名的宗親資料
    $sql = "SELECT m.new_member, m.number_of_houses, m.emperor_shizu, 
                   IFNULL(w.generation, '1') AS generation 
            FROM members m 
            LEFT JOIN makeawish w ON m.name = w.name 
            WHERE m.name = ? 
            GROUP BY m.new_member 
            ORDER BY w.ID DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $query_name);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    // 回傳陣列給前端判定數量
    echo json_encode($rows);
    exit;
}

// ==========================================
// 3. 處理搜尋與篩選邏輯
// ==========================================
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$results = [];
$search_status = '預覽最新資料';

$exact_generation = isset($_GET['exact_generation']) ? trim($_GET['exact_generation']) : '';
$exact_id = isset($_GET['exact_id']) ? trim($_GET['exact_id']) : '';
$exact_name = isset($_GET['exact_name']) ? trim($_GET['exact_name']) : '';
$exact_date = isset($_GET['exact_date']) ? trim($_GET['exact_date']) : '';

if ($keyword !== '') {
    $search_status = '條件糊查詢 ➔ ' . htmlspecialchars($keyword);
    $like_keyword = "%" . $keyword . "%";

    $sql = "SELECT w.ID, w.name, w.generation, w.family_members, w.message_of_blessing, w.login_time, 
                   m.new_member, m.number_of_houses, m.emperor_shizu
            FROM makeawish w LEFT JOIN members m ON w.name = m.name
            WHERE m.new_member LIKE ? OR w.name LIKE ? OR w.ID LIKE ? OR w.message_of_blessing LIKE ? OR w.family_members LIKE ? 
            ORDER BY w.ID DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $like_keyword, $like_keyword, $like_keyword, $like_keyword, $like_keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
} else if ($exact_id !== '' || $exact_name !== '' || $exact_generation !== '' || $exact_date !== '') {
    $search_status = '條件精確查詢';
    $where_clauses = [];
    $params = [];
    $types = "";

    if ($exact_id !== '') {
        $where_clauses[] = "m.new_member = ?";
        $params[] = $exact_id;
        $types .= "s";
    }
    if ($exact_name !== '') {
        $where_clauses[] = "w.name = ?";
        $params[] = $exact_name;
        $types .= "s";
    }
    if ($exact_generation !== '') {
        $where_clauses[] = "w.generation = ?";
        $params[] = $exact_generation;
        $types .= "s";
    }
    if ($exact_date !== '') {
        $where_clauses[] = "w.login_time BETWEEN ? AND ?";
        $params[] = $exact_date . " 00:00:00";
        $params[] = $exact_date . " 23:59:59";
        $types .= "ss";
    }

    $where_sql = implode(" AND ", $where_clauses);
    $sql = "SELECT w.ID, w.name, w.generation, w.family_members, w.message_of_blessing, w.login_time, 
                   m.new_member, m.number_of_houses, m.emperor_shizu
            FROM makeawish w LEFT JOIN members m ON w.name = m.name WHERE $where_sql ORDER BY w.ID DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
} else {
    $sql = "SELECT w.ID, w.name, w.generation, w.family_members, w.message_of_blessing, w.login_time, 
                   m.new_member, m.number_of_houses, m.emperor_shizu
            FROM makeawish w LEFT JOIN members m ON w.name = m.name GROUP BY w.ID ORDER BY w.ID DESC LIMIT 20";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>智慧查詢與進階編輯系統</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jodit@3.24.5/build/jodit.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jodit@3.24.5/build/jodit.min.js"></script>

    <style>
        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background-color: #f4f7f6;
            color: #333;
            padding: 30px;
        }

        .search-container,
        .edit-container {
            max-width: 1100px;
            margin: 0 auto 15px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .edit-container {
            border-left: 5px solid #2563eb;
            display: none;
        }

        .search-title {
            font-size: 1.4rem;
            color: #143622;
            font-weight: bold;
            margin-bottom: 15px;
            border-left: 5px solid #407a52;
            padding-left: 10px;
        }

        .exact-section-title {
            font-size: 1.4rem;
            color: #143622;
            font-weight: bold;
            margin-bottom: 15px;
            border-left: 5px solid #407a52;
            padding-left: 10px;
            margin-top: 25px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 5px;
        }

        .edit-title {
            font-size: 1.4rem;
            color: #1e3a8a;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .exact-single-row,
        .edit-single-row {
            display: flex;
            gap: 20px;
            width: 100%;
            align-items: center;
            margin-bottom: 10px;
        }

        .exact-half-block,
        .edit-half-block {
            flex: 0 0 calc(50% - 10px);
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .input-sub-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .input-sub-item span {
            font-size: 0.9rem;
            font-weight: bold;
            color: #4b5563;
            white-space: nowrap;
        }

        .search-input0 {
            width: 49%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            height: 42px;
        }


        .search-input {
            width: 90%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            height: 42px;
        }

        .search-input.readonly-gray {
            background-color: #e2e8f0 !important;
            color: #475569 !important;
            cursor: not-allowed;
            border: 1px solid #cbd5e1;
        }

        .search-btn {
            background: #407a52;
            color: #fff;
            border: none;
            padding: 0 20px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            height: 42px;
            white-space: nowrap;
        }

        .search-btn:hover {
            background: #2d5a3a;
        }

        .btn-exact {
            background: #407a52;
            padding: 0 15px;
        }

        .btn-exact:hover {
            background: #2d5a3a;
        }

        .btn-update {
            background: #2563eb;
        }

        .btn-update:hover {
            background: #1d4ed8;
        }

        .status-info-bar {
            max-width: 1100px;
            margin: 0 auto 15px auto;
            background: #e2e8f0;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: bold;
            color: #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-batch-delete {
            background: #dc2626;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .result-table {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .result-table th {
            background-color: #143622;
            color: #fff;
            padding: 12px 15px;
            text-align: left;
        }

        .result-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            line-height: 1.6;
        }

        .result-table tr:hover {
            background-color: #f9fbf9;
        }

        .member-info-block {
            font-size: 14px;
            color: #333;
        }

        .member-name {
            font-size: 16px;
            color: #111827;
            display: inline-block;
            margin-bottom: 4px;
        }

        .badge-id,
        .badge-info,
        .badge-house {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }

        .badge-id {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-house {
            background: #fef3c7;
            color: #d97706;
        }

        .time-info {
            display: block;
            font-size: 14px;
            color: #666;
            margin-top: 8px;
            border-top: 1px dashed #ddd;
            padding-top: 6px;
        }

        .family-info {
            display: block;
            font-size: 14px;
            color: #4b5563;
            margin-top: 4px;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .action-links {
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .action-links a {
            text-decoration: none;
            margin-right: 5px;
            font-weight: bold;
        }

        .link-edit {
            color: #2563eb;
            cursor: pointer;
        }

        .link-delete {
            color: #dc2626;
            cursor: pointer;
        }

        /* 🚀 彈出視窗基礎樣式 (Jodit 與 同名選擇視窗通用) */
        .editor-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 32, 39, 0.6);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .editor-modal-overlay.active {
            display: flex;
        }

        .editor-modal-content {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            width: 85%;
            height: 80%;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        /* 同名選擇特定寬高調整 */
        #memberChoiceModal .editor-modal-content {
            width: 600px;
            height: auto;
            max-height: 80%;
        }

        .member-choice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .member-choice-table th {
            background: #1e3a8a;
            color: white;
            padding: 10px;
            text-align: left;
        }

        .member-choice-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .member-choice-table tr:hover {
            background: #f8fafc;
        }

        .editor-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }

        .editor-modal-close {
            background: transparent;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #64748b;
        }

        .editor-container-box {
            flex: 1;
            min-height: 0;
            margin-bottom: 15px;
        }

        .expand-link-tag {
            font-size: 0.8rem;
            color: #2563eb;
            cursor: pointer;
            text-decoration: underline;
            margin-left: 5px;
        }

        /* 鎖定按鈕樣式 */
        .btn-unlock {
            background: #ea580c;
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            margin-left: 5px;
            display: none;
        }

        @media (max-width: 900px) {

            .exact-single-row,
            .edit-single-row {
                flex-direction: column;
                gap: 10px;
            }

            .exact-half-block,
            .edit-half-block {
                flex: 0 0 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <?php if ($message_notice !== ''): ?>
        <script>
            alert("<?php echo $message_notice; ?>");
        </script>
    <?php endif; ?>

    <div class="edit-container" id="editContainer">
        <div class="edit-title">
            編輯許願卡資料方塊 (關鍵字ID: <span id="txt_view_id" style="color:#1e3a8a; font-weight:bold;">#</span>)
            <span id="lockStatusBadge" style="color: #16a34a; font-size: 0.9rem; margin-left: 10px; display: none;">🔒 3.姓名欄位已鎖定</span>
            <span id="unlockBtn" class="btn-unlock" onclick="unlockFields()">解鎖更換</span>
        </div>
        <form method="POST" action="" onsubmit="prepareSubmitForm()">
            <input type="hidden" name="action" value="update_wish">
            <input type="hidden" name="update_id" id="u_id">

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>1.世代</span>
                        <input type="text" name="u_generation" id="u_generation" class="search-input readonly-gray" readonly placeholder="由姓名自動帶出">
                    </div>
                    <div class="input-sub-item">
                        <span>2.編號</span>
                        <input type="text" id="u_member_id" class="search-input readonly-gray" readonly placeholder="由姓名自動帶出">
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>3.姓名</span>
                        <input type="text" name="u_name" id="u_name" class="search-input" required placeholder="請輸入姓名" oninput="fetchMemberInfoByName(this.value)">
                    </div>
                    <div class="input-sub-item">
                        <span>4.時間</span>
                        <input type="datetime-local" name="u_time" id="u_time" class="search-input" required onkeydown="return false;" style="cursor: pointer;">
                    </div>
                </div>
            </div>

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>5.大房/世祖</span>
                        <input type="text" id="u_house_shizu" class="search-input readonly-gray" readonly placeholder="由姓名自動帶出">
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>6.定居大甲</span>
                        <input type="text" id="u_dajia_generation" class="search-input readonly-gray" readonly placeholder="由姓名自動帶出" 代>
                    </div>
                </div>
            </div>

            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
                <div class="input-sub-item">
                    <span>家庭成員</span>
                    <input type="text" name="u_family" id="u_family" class="search-input" required placeholder="家庭成員名單">
                </div>
                <div class="input-sub-item">
                    <span>✨ 祈願內文</span>
                    <input type="text" name="u_content" id="u_content" class="search-input" onclick="openJoditModal()" readonly style="cursor:pointer; background:#fff8f0;" placeholder="點擊此處或右側 [展開進階編輯]...">
                    <span class="expand-link-tag" onclick="openJoditModal()">[展開進階編輯]</span>
                </div>
                <div style="text-align: right; margin-top: 5px;">
                    <button type="button" class="search-btn" style="background:#64748b; margin-right:10px;" onclick="document.getElementById('editContainer').style.display='none'">取消</button>
                    <button type="submit" class="search-btn btn-update">確認修改並送出 ➔</button>
                </div>
            </div>
        </form>
    </div>

    <div class="editor-modal-overlay" id="memberChoiceModal">
        <div class="editor-modal-content">
            <div class="editor-modal-header">
                <div style="font-size:1.2rem; font-weight:bold; color:#1e3a8a;">⚠️ 偵測到同名同姓宗親，請進行單選確認</div>
                <button class="editor-modal-close" onclick="closeChoiceModal()">&times;</button>
            </div>
            <div style="color: #64748b; font-size:0.9rem;">請勾選正確的成員資料，送出後將鎖定編輯資料欄位。</div>

            <table class="member-choice-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">選擇</th>
                        <th>會員編號</th>
                        <th>大房/世祖資訊</th>
                        <th>大甲代</th>
                    </tr>
                </thead>
                <tbody id="memberChoiceRows">
                </tbody>
            </table>

            <div style="text-align:right; margin-top: 10px;">
                <button type="button" class="search-btn" style="background:#94a3b8; margin-right:10px;" onclick="closeChoiceModal()">取消</button>
                <button type="button" class="search-btn" style="background:#1e3a8a;" onclick="submitMemberChoice()">確認選擇並鎖定 🔒</button>
            </div>
        </div>
    </div>

    <div class="search-container">
        <form method="GET" action="">
            <div class="search-title">🌿 祈願卡模糊查詢</div>
            <div class="search-box">
                <input type="text" name="keyword" class="search-input0" placeholder="請輸入關鍵字..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit" class="search-btn">搜尋 🔍</button>
            </div>
        </form>

        <div class="exact-section-title">🌿 祈願卡精確查詢</div>
        <form method="GET" action="">
            <div class="exact-single-row">
                <div class="exact-half-block">
                    <div class="input-sub-item">
                        <span>世代</span>
                        <input type="text" name="exact_generation" class="search-input" placeholder="定居大甲世代" value="<?php echo htmlspecialchars($exact_generation); ?>">
                    </div>
                    <div class="input-sub-item">
                        <span>編號</span>
                        <input type="text" name="exact_id" class="search-input" placeholder="宗親會員編號" value="<?php echo htmlspecialchars($exact_id); ?>">
                    </div>
                </div>
                <div class="exact-half-block">
                    <div class="input-sub-item">
                        <span>姓名</span>
                        <input type="text" name="exact_name" class="search-input" placeholder="宗親會員姓名" value="<?php echo htmlspecialchars($exact_name); ?>">
                    </div>
                    <div class="input-sub-item">
                        <span>時間</span>
                        <input type="date" name="exact_date" class="search-input" value="<?php echo htmlspecialchars($exact_date); ?>">
                    </div>
                    <button type="submit" class="search-btn btn-exact">精確篩選</button>
                </div>
            </div>
        </form>
    </div>

    <div class="status-info-bar">
        <div>📌 目前狀態：<?php echo $search_status; ?> ( 找到 <?php echo count($results); ?> 筆 )</div>
        <div><button type="button" class="btn-batch-delete" onclick="confirmBatchDelete()">🗑️ 刪除選取項目</button></div>
    </div>

    <form id="delete_form" method="POST" action="">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="single_delete_id" id="single_delete_id" value="">

        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 140px;"><input type="checkbox" onchange="toggleAll(this)"> 全選/操作</th>
                    <th style="width: 70px;">許願ID</th>
                    <th style="width: 250px;">🌿 成員基本資訊</th>
                    <th>✨ 祈願內文 (message_of_blessing)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td>
                                <div class="action-links">
                                    <input type="checkbox" name="delete_ids[]" class="id-checkbox" value="<?php echo $row['ID']; ?>">
                                    <span class="link-edit" onclick="loadWishData(<?php echo $row['ID']; ?>)">[編輯]</span>
                                    <span class="link-delete" onclick="confirmSingleDelete(<?php echo $row['ID']; ?>)">[刪除]</span>
                                </div>
                            </td>
                            <td><strong>#<?php echo $row['ID']; ?></strong></td>
                            <td class="member-info-cell">
                                <div class="member-info-block">
                                    <strong class="member-name"><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <span class="badge-id">編號: <?php echo $row['new_member'] ? htmlspecialchars($row['new_member']) : '未綁定'; ?></span>
                                    <span class="badge-house">第 <?php echo $row['number_of_houses']; ?> 大房</span><br>
                                    <span class="badge-info">第 <?php echo $row['emperor_shizu']; ?> 世祖 / 大甲 <?php echo $row['generation']; ?> 代</span>
                                    <?php if (!empty($row['family_members'])): ?>
                                        <span class="family-info">👨‍👩‍👧‍👦 <strong>家庭成員：</strong><?php echo htmlspecialchars($row['family_members']); ?></span>
                                    <?php endif; ?>
                                    <span class="time-info">📅 <strong>發表時間：</strong><br><?php echo $row['login_time']; ?></span>
                                </div>
                            </td>
                            <td class="blessing-content"><?php echo $row['message_of_blessing']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-data">❌ 沒有找到符合條件的祈願紀錄。</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <div class="editor-modal-overlay" id="editorModal">
        <div class="editor-modal-content">
            <div class="editor-modal-header">
                <div style="font-size:1.2rem; font-weight:bold; color:#1e3a8a;">🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)</div>
                <button class="editor-modal-close" onclick="closeJoditModal()">&times;</button>
            </div>
            <div class="editor-container-box">
                <textarea id="joditEditorTarget"></textarea>
            </div>
            <div style="text-align:right;">
                <button type="button" class="search-btn" style="background:#94a3b8; margin-right:10px;" onclick="closeJoditModal()">取消</button>
                <button type="button" class="search-btn btn-update" onclick="saveJoditContent()">確認回填編輯區</button>
            </div>
        </div>
    </div>

    <script>
        let currentChoiceData = []; // 暫存同名同姓撈出的資料陣列
        let isFieldsLocked = false; // 控制編輯區相連動欄位鎖定狀態

        const joditEditor = new Jodit('#joditEditorTarget', {
            height: '100%',
            language: 'zh_tw',
            uploader: {
                url: '?action=upload_icon',
                format: 'json'
            }
        });

        // 🚀 點擊編輯超連結：加載單筆資料並回填
        function loadWishData(id) {
            unlockFields(); // 初始化解鎖狀態
            fetch('?action=get_single_wish&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        document.getElementById('u_id').value = data.id;
                        document.getElementById('txt_view_id').textContent = "#" + data.id;

                        document.getElementById('u_generation').value = data.generation;
                        document.getElementById('u_member_id').value = data.new_member ? data.new_member : '未綁定';
                        document.getElementById('u_name').value = data.name;
                        document.getElementById('u_family').value = data.family_members;
                        document.getElementById('u_content').value = data.message_of_blessing;

                        let house = data.number_of_houses ? data.number_of_houses : '?';
                        let shizu = data.emperor_shizu ? data.emperor_shizu : '?';
                        document.getElementById('u_house_shizu').value = `第 ${house} 大房第 ${shizu} 世祖`;
                        document.getElementById('u_dajia_generation').value = `第 ${data.generation ? data.generation : '?'} 代`;

                        if (data.login_time) {
                            let formattedTime = data.login_time.replace(' ', 'T').substring(0, 16);
                            document.getElementById('u_time').value = formattedTime;
                        }

                        // 點擊已有項目進入編輯，預設啟動鎖定保護
                        lockFields();

                        document.getElementById('editContainer').style.display = 'block';
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
        }

        // 🚀 根據姓名即時查找成員（同名同姓彈窗核心邏輯）
        function fetchMemberInfoByName(name) {
            if (isFieldsLocked) return; // 若已鎖定則不重複觸發
            if (!name.trim()) {
                clearMemberFields();
                return;
            }

            fetch('?action=get_member_info_by_name&name=' + encodeURIComponent(name))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        clearMemberFields();
                        document.getElementById('u_member_id').value = '未綁定';
                        document.getElementById('u_generation').value = '1';
                        document.getElementById('u_dajia_generation').value = '大甲 1 代';
                        return;
                    }

                    if (data.length === 1) {
                        // 只有單筆資料，直接回填並鎖定
                        fillMemberFields(data[0]);
                        lockFields();
                    } else {
                        // 🚀 偵測到大於1筆（同名同姓），開啟彈出選擇視窗
                        currentChoiceData = data;
                        let rowsHtml = '';
                        data.forEach((item, index) => {
                            let house = item.number_of_houses ? item.number_of_houses : '?';
                            let shizu = item.emperor_shizu ? item.emperor_shizu : '?';
                            rowsHtml += `
                                <tr>
                                    <td><input type="radio" name="member_radio_choice" value="${index}" ${index === 0 ? 'checked' : ''}></td>
                                    <td><strong>${item.new_member}</strong></td>
                                    <td>第 ${house} 大房第 ${shizu} 世祖</td>
                                    <td>大甲 ${item.generation} 代</td>
                                </tr>
                            `;
                        });
                        document.getElementById('memberChoiceRows').innerHTML = rowsHtml;
                        document.getElementById('memberChoiceModal').classList.add('active');
                    }
                });
        }

        // 提交同名同姓單選結果
        function submitMemberChoice() {
            let selectedRadio = document.querySelector('input[name="member_radio_choice"]:checked');
            if (!selectedRadio) {
                alert("請選取一筆成員資料！");
                return;
            }
            let index = selectedRadio.value;
            fillMemberFields(currentChoiceData[index]);
            lockFields(); // 鎖定編輯區
            closeChoiceModal();
        }

        // 填入資料方法
        function fillMemberFields(item) {
            document.getElementById('u_generation').value = item.generation;
            document.getElementById('u_member_id').value = item.new_member ? item.new_member : '未綁定';

            let house = item.number_of_houses ? item.number_of_houses : '?';
            let shizu = item.emperor_shizu ? item.emperor_shizu : '?';
            document.getElementById('u_house_shizu').value = `第 ${house} 大房第 ${shizu} 世祖`;
            document.getElementById('u_dajia_generation').value = `大甲 ${item.generation ? item.generation : '?'} 代`;
        }

        // 清空欄位方法
        function clearMemberFields() {
            document.getElementById('u_generation').value = '';
            document.getElementById('u_member_id').value = '';
            document.getElementById('u_house_shizu').value = '';
            document.getElementById('u_dajia_generation').value = '';
        }

        // 🔒 鎖定欄位方法
        function lockFields() {
            isFieldsLocked = true;
            document.getElementById('u_name').readOnly = true;
            document.getElementById('u_name').style.backgroundColor = '#e2e8f0';
            document.getElementById('lockStatusBadge').style.display = 'inline';
            document.getElementById('unlockBtn').style.display = 'inline';
        }

        // 🔓 解鎖欄位方法
        function unlockFields() {
            isFieldsLocked = false;
            document.getElementById('u_name').readOnly = false;
            document.getElementById('u_name').style.backgroundColor = '#fff';
            document.getElementById('lockStatusBadge').style.display = 'none';
            document.getElementById('unlockBtn').style.display = 'none';
        }

        // 防止 readonly 欄位在送出表單時發生異常
        function prepareSubmitForm() {
            // 在主要送出前確保所有阻擋邏輯完備
            return true;
        }

        function closeChoiceModal() {
            document.getElementById('memberChoiceModal').classList.remove('active');
        }

        function openJoditModal() {
            joditEditor.value = document.getElementById('u_content').value;
            document.getElementById('editorModal').classList.add('active');
            setTimeout(() => {
                joditEditor.events.fire('resize');
            }, 100);
        }

        function closeJoditModal() {
            document.getElementById('editorModal').classList.remove('active');
        }

        function saveJoditContent() {
            document.getElementById('u_content').value = joditEditor.value;
            closeJoditModal();
        }

        function toggleAll(master) {
            var checkboxes = document.getElementsByClassName('id-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = master.checked;
            }
        }

        function confirmSingleDelete(id) {
            if (confirm("⚠️ 確定要永久刪除此筆 許願ID #" + id + " 資料與實體檔案嗎？")) {
                document.getElementById('single_delete_id').value = id;
                document.getElementById('delete_form').submit();
            }
        }

        function confirmBatchDelete() {
            var checkboxes = document.getElementsByClassName('id-checkbox');
            var checkedCount = 0;
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].checked) checkedCount++;
            }
            if (checkedCount === 0) {
                alert("請先勾選項目。");
                return;
            }
            if (confirm("⚠️ 確定要整批永久刪除這 " + checkedCount + " 筆資料與 D:\\web02\\icon\\ 內實體檔案嗎？")) {
                document.getElementById('delete_form').submit();
            }
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>