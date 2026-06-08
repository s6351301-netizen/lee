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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_member') {
    $edit_account_id = intval($_POST['update_account_id']);

    // 接收來自表單可供編輯的欄位
    $u_email = trim($_POST['u_email']);
    $u_password = $_POST['u_password'];
    $u_role = trim($_POST['u_role']);
    $u_status = trim($_POST['u_status']);
    $u_join_date = !empty($_POST['u_join_date']) ? $_POST['u_join_date'] : null;
    $u_discontinued_date = !empty($_POST['u_discontinued_date']) ? $_POST['u_discontinued_date'] : null;
    $u_account_remarks = $_POST['u_account_remarks'];

    $u_generation = intval($_POST['u_generation']);
    $u_emperor_shizu = intval($_POST['u_emperor_shizu']);
    $u_number_of_houses = intval($_POST['u_number_of_houses']);
    $u_birthday = !empty($_POST['u_birthday']) ? $_POST['u_birthday'] : null;
    $u_placeOfBirth = trim($_POST['u_placeOfBirth']);
    $u_address = trim($_POST['u_address']);
    $u_SendSubordinates = trim($_POST['u_SendSubordinates']);
    $u_living_status = trim($_POST['u_living_status']);
    $u_member_remarks = $_POST['u_member_remarks'];

    // 啟動交易(Transaction) 以便同時安全修改兩張表
    $conn->begin_transaction();
    try {
        // 1. 更新 account 表
        $sql_act = "UPDATE account SET email=?, password=?, role=?, status=?, join_date=?, discontinued_date=?, remarks=? WHERE id=?";
        $stmt_act = $conn->prepare($sql_act);
        $stmt_act->bind_param("sssssssi", $u_email, $u_password, $u_role, $u_status, $u_join_date, $u_discontinued_date, $u_account_remarks, $edit_account_id);
        $stmt_act->execute();
        $stmt_act->close();

        // 2. 獲取對應的 new_member 與 name
        $sql_find = "SELECT new_member, name FROM account WHERE id = ?";
        $stmt_find = $conn->prepare($sql_find);
        $stmt_find->bind_param("i", $edit_account_id);
        $stmt_find->execute();
        $info = $stmt_find->get_result()->fetch_assoc();
        $stmt_find->close();

        if ($info) {
            $sql_mem = "UPDATE members SET generation=?, emperor_shizu=?, number_of_houses=?, birthday=?, placeOfBirth=?, address=?, SendSubordinates=?, living_status=?, remarks=? 
                        WHERE new_member = ? AND name = ?";
            $stmt_mem = $conn->prepare($sql_mem);
            $stmt_mem->bind_param("iiissssssss", $u_generation, $u_emperor_shizu, $u_number_of_houses, $u_birthday, $u_placeOfBirth, $u_address, $u_SendSubordinates, $u_living_status, $u_member_remarks, $info['new_member'], $info['name']);
            $stmt_mem->execute();
            $stmt_mem->close();
        }

        $conn->commit();
        $message_notice = "帳號 ID #{$edit_account_id} 的會員完整資料已成功同步更新！";
    } catch (Exception $e) {
        $conn->rollback();
        $message_notice = "更新失敗：" . $e->getMessage();
    }
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
        foreach ($ids_to_delete as $del_id) {
            $sql_find = "SELECT new_member, name FROM account WHERE id = ?";
            $stmt_find = $conn->prepare($sql_find);
            $stmt_find->bind_param("i", $del_id);
            $stmt_find->execute();
            $info = $stmt_find->get_result()->fetch_assoc();
            $stmt_find->close();

            if ($info) {
                $del_mem = "DELETE FROM members WHERE new_member = ? AND name = ?";
                $dm_stmt = $conn->prepare($del_mem);
                $dm_stmt->bind_param("ss", $info['new_member'], $info['name']);
                $dm_stmt->execute();
                $dm_stmt->close();
            }

            $del_act = "DELETE FROM account WHERE id = ?";
            $da_stmt = $conn->prepare($del_act);
            $da_stmt->bind_param("i", $del_id);
            $da_stmt->execute();
            if ($da_stmt->affected_rows > 0) {
                $deleted_count++;
            }
            $da_stmt->close();
        }
        $message_notice = "成功刪除 {$deleted_count} 筆會員相關紀錄！";
    }
}

// ==========================================
// 🚀 AJAX 讀取單筆會員詳細資料 API 
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'get_single_member') {
    header('Content-Type: application/json; charset=utf-8');
    $get_id = intval($_GET['id']);

    $sql = "SELECT 
                a.id AS account_id, a.new_member AS account_new_member, a.old_member AS account_old_member, a.name AS account_name, a.gender AS account_gender,
                a.email, a.password, a.role, a.join_date, a.status, a.discontinued_date, a.remarks AS account_remarks,
                m.id AS member_id, m.old_member AS member_old_member, m.new_member AS member_new_member, m.generation, m.emperor_shizu, m.number_of_houses,
                m.name AS member_name, m.gender AS member_gender, m.birthday, m.placeOfBirth, m.address, m.SendSubordinates, m.living_status, m.remarks AS member_remarks
            FROM account a
            INNER JOIN members m ON a.new_member = m.new_member AND a.name = m.name
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $get_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    echo json_encode($row ? $row : ['error' => '找不到資料']);
    $stmt->close();
    exit;
}

// ==========================================
// 3. 處理搜尋與篩選邏輯
// ==========================================
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$results = [];
$search_status = '預覽最新資料';

// 接收精確查詢的所有欄位 (包含新增欄位)
$exact_id = isset($_GET['exact_id']) ? trim($_GET['exact_id']) : '';
$exact_name = isset($_GET['exact_name']) ? trim($_GET['exact_name']) : '';
$exact_gender = isset($_GET['exact_gender']) ? trim($_GET['exact_gender']) : '';
$exact_email = isset($_GET['exact_email']) ? trim($_GET['exact_email']) : '';
$exact_role = isset($_GET['exact_role']) ? trim($_GET['exact_role']) : '';
$exact_generation = isset($_GET['exact_generation']) ? trim($_GET['exact_generation']) : '';
$exact_date = isset($_GET['exact_date']) ? trim($_GET['exact_date']) : '';

$base_select = "SELECT 
                    a.id AS account_id, a.new_member AS account_new_member, a.old_member AS account_old_member, a.name AS account_name, a.gender AS account_gender,
                    a.email, a.password, a.role, a.join_date, a.status, a.discontinued_date, a.remarks AS account_remarks,
                    m.id AS member_id, m.old_member AS member_old_member, m.new_member AS member_new_member, m.generation, m.emperor_shizu, m.number_of_houses,
                    m.name AS member_name, m.gender AS member_gender, m.birthday, m.placeOfBirth, m.address, m.SendSubordinates, m.living_status, m.remarks AS member_remarks
                FROM account a
                INNER JOIN members m ON a.new_member = m.new_member AND a.name = m.name ";

if ($keyword !== '') {
    $search_status = '條件模糊查詢 ➔ ' . htmlspecialchars($keyword);
    $like_keyword = "%" . $keyword . "%";

    $sql = $base_select . " WHERE a.new_member LIKE ? OR a.name LIKE ? OR a.email LIKE ? OR m.address LIKE ? OR a.id LIKE ? ORDER BY a.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $like_keyword, $like_keyword, $like_keyword, $like_keyword, $like_keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
} else if ($exact_id !== '' || $exact_name !== '' || $exact_gender !== '' || $exact_email !== '' || $exact_role !== '' || $exact_generation !== '' || $exact_date !== '') {
    $search_status = '條件精確查詢';
    $where_clauses = [];
    $params = [];
    $types = "";

    if ($exact_id !== '') {
        $where_clauses[] = "a.new_member = ?";
        $params[] = $exact_id;
        $types .= "s";
    }
    if ($exact_name !== '') {
        $where_clauses[] = "a.name = ?";
        $params[] = $exact_name;
        $types .= "s";
    }
    if ($exact_gender !== '') {
        $where_clauses[] = "a.gender = ?";
        $params[] = $exact_gender;
        $types .= "s";
    }
    if ($exact_email !== '') {
        $where_clauses[] = "a.email = ?";
        $params[] = $exact_email;
        $types .= "s";
    }
    if ($exact_role !== '') {
        $where_clauses[] = "a.role = ?";
        $params[] = $exact_role;
        $types .= "s";
    }
    if ($exact_generation !== '') {
        $where_clauses[] = "m.generation = ?";
        $params[] = intval($exact_generation);
        $types .= "i";
    }
    if ($exact_date !== '') {
        $where_clauses[] = "a.join_date = ?";
        $params[] = $exact_date;
        $types .= "s";
    }

    $where_sql = implode(" AND ", $where_clauses);
    $sql = $base_select . " WHERE $where_sql ORDER BY a.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
} else {
    $search_status = '預覽最新';
    $sql = $base_select . " ORDER BY a.id ASC LIMIT 20";
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
    <title>會員資料管理與智慧查詢系統</title>

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
            max-width: 1200px;
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

        .search-title,
        .exact-section-title {
            font-size: 1.4rem;
            color: #143622;
            font-weight: bold;
            margin-bottom: 15px;
            border-left: 5px solid #407a52;
            padding-left: 10px;
        }

        .exact-section-title {
            margin-top: 25px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 15px;
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
            margin-bottom: 12px;
            flex-wrap: wrap;
            /* 讓精確搜尋欄位多時自動折行 */
        }

        .exact-half-block,
        .edit-half-block {
            flex: 1 1 calc(50% - 10px);
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* 精確搜尋專用欄位區塊 */
        .exact-flex-item {
            flex: 1 1 calc(25% - 10px);
            /* 4等分排版基礎 */
            min-width: 200px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .input-sub-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .input-sub-item span,
        .exact-flex-item span {
            font-size: 0.9rem;
            font-weight: bold;
            color: #4b5563;
            white-space: nowrap;
            min-width: 90px;
            text-align: right;
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
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            height: 38px;
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
            height: 38px;
            padding: 0 20px;
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
            max-width: 1200px;
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

        .table-responsive-box {
            max-width: 1200px;
            margin: 0 auto;
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            min-width: 1600px;
        }

        .result-table th {
            background-color: #143622;
            color: #fff;
            padding: 12px 10px;
            text-align: left;
            font-size: 0.9rem;
        }

        .result-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            line-height: 1.5;
            font-size: 0.85rem;
        }

        .result-table tr:hover {
            background-color: #f9fbf9;
        }

        .badge-act {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: bold;
        }

        .badge-mem {
            background: #fecaca;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: bold;
        }

        .action-links a {
            text-decoration: none;
            margin-right: 5px;
            font-weight: bold;
        }

        .link-edit {
            color: #2563eb;
            cursor: pointer;
            font-weight: bold;
        }

        .link-delete {
            color: #dc2626;
            cursor: pointer;
            font-weight: bold;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #dc2626;
            font-weight: bold;
        }

        /* Jodit 彈出視窗 */
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
            ⚙️ 編輯會員完整資料區 (帳號自增 ID: <span id="txt_view_id" style="color:#1e3a8a; font-weight:bold;">#</span>)
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_member">
            <input type="hidden" name="update_account_id" id="u_account_id">

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>帳號-新會員號</span>
                        <input type="text" id="u_account_new_member" class="search-input readonly-gray" readonly>
                    </div>
                    <div class="input-sub-item">
                        <span>帳號-舊會員號</span>
                        <input type="text" id="u_account_old_member" class="search-input readonly-gray" readonly>
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>帳號-姓名</span>
                        <input type="text" id="u_account_name" class="search-input readonly-gray" readonly>
                    </div>
                    <div class="input-sub-item">
                        <span>帳號-性別</span>
                        <input type="text" id="u_account_gender" class="search-input readonly-gray" readonly>
                    </div>
                </div>
            </div>

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>電子郵件</span>
                        <input type="email" name="u_email" id="u_email" class="search-input" required>
                    </div>
                    <div class="input-sub-item">
                        <span>密碼</span>
                        <input type="text" name="u_password" id="u_password" class="search-input" required>
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>系統角色</span>
                        <input type="text" name="u_role" id="u_role" class="search-input">
                    </div>
                    <div class="input-sub-item">
                        <span>帳號狀態</span>
                        <input type="text" name="u_status" id="u_status" class="search-input">
                    </div>
                </div>
            </div>

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>加入日期</span>
                        <input type="date" name="u_join_date" id="u_join_date" class="search-input">
                    </div>
                    <div class="input-sub-item">
                        <span>停權日期</span>
                        <input type="date" name="u_discontinued_date" id="u_discontinued_date" class="search-input">
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>帳號備註說明</span>
                        <input type="text" name="u_account_remarks" id="u_account_remarks" class="search-input">
                    </div>
                </div>
            </div>

            <hr style="border:0; border-top:1px dashed #cbd5e1; margin:15px 0;">

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>會員自增 ID</span>
                        <input type="text" id="u_member_id" class="search-input readonly-gray" readonly>
                    </div>
                    <div class="input-sub-item">
                        <span>會員-新會員號</span>
                        <input type="text" id="u_member_new_member" class="search-input readonly-gray" readonly>
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>會員-舊會員號</span>
                        <input type="text" id="u_member_old_member" class="search-input readonly-gray" readonly>
                    </div>
                    <div class="input-sub-item">
                        <span>會員-姓名/性別</span>
                        <input type="text" id="u_member_name_gender" class="search-input readonly-gray" readonly>
                    </div>
                </div>
            </div>

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>世代代數</span>
                        <input type="number" name="u_generation" id="u_generation" class="search-input">
                    </div>
                    <div class="input-sub-item">
                        <span>第幾世祖</span>
                        <input type="number" name="u_emperor_shizu" id="u_emperor_shizu" class="search-input">
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>第幾大房</span>
                        <input type="number" name="u_number_of_houses" id="u_number_of_houses" class="search-input">
                    </div>
                    <div class="input-sub-item">
                        <span>出生日期</span>
                        <input type="date" name="u_birthday" id="u_birthday" class="search-input">
                    </div>
                </div>
            </div>

            <div class="edit-single-row">
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>出生地點</span>
                        <input type="text" name="u_placeOfBirth" id="u_placeOfBirth" class="search-input">
                    </div>
                    <div class="input-sub-item">
                        <span>現居地址</span>
                        <input type="text" name="u_address" id="u_address" class="search-input">
                    </div>
                </div>
                <div class="edit-half-block">
                    <div class="input-sub-item">
                        <span>派送下屬區分</span>
                        <input type="text" name="u_SendSubordinates" id="u_SendSubordinates" class="search-input">
                    </div>
                    <div class="input-sub-item">
                        <span>生存狀態</span>
                        <input type="text" name="u_living_status" id="u_living_status" class="search-input">
                    </div>
                </div>
            </div>

            <div style="margin-top: 10px;">
                <div class="input-sub-item">
                    <span>✨ 會員特殊備註</span>
                    <input type="text" name="u_member_remarks" id="u_member_remarks" class="search-input" onclick="openJoditModal()" readonly style="cursor:pointer; background:#fff8f0;" placeholder="點擊展開富文本進階編輯...">
                    <span class="expand-link-tag" onclick="openJoditModal()">[展開進階編輯]</span>
                </div>
                <div style="text-align: right; margin-top: 15px;">
                    <button type="button" class="search-btn" style="background:#64748b; margin-right:10px;" onclick="document.getElementById('editContainer').style.display='none'">取消</button>
                    <button type="submit" class="search-btn btn-update">確認修改會員資料 ➔</button>
                </div>
            </div>
        </form>
    </div>

    <div class="search-container">
        <form method="GET" action="">
            <div class="search-title">🌿 會員資料模糊查詢</div>
            <div class="search-box">
                <input type="text" name="keyword" class="search-input0" placeholder="可輸入新會員號、姓名、Email、地址、ID..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit" class="search-btn">搜尋 會員 🔍</button>
            </div>
        </form>

        <div class="exact-section-title">🌿 會員資料精確查詢</div>
        <form method="GET" action="">
            <div class="exact-single-row">
                <div class="exact-flex-item">
                    <span>新會員號</span>
                    <input type="text" name="exact_id" class="search-input" placeholder="精確會員號" value="<?php echo htmlspecialchars($exact_id); ?>">
                </div>
                <div class="exact-flex-item">
                    <span>姓名</span>
                    <input type="text" name="exact_name" class="search-input" placeholder="精確姓名" value="<?php echo htmlspecialchars($exact_name); ?>">
                </div>
                <div class="exact-flex-item">
                    <span>性別</span>
                    <input type="text" name="exact_gender" class="search-input" placeholder="男 / 女" value="<?php echo htmlspecialchars($exact_gender); ?>">
                </div>

            </div>

            <div class="exact-single-row">
                <div class="exact-flex-item">
                    <span>角色</span>
                    <input type="text" name="exact_role" class="search-input" placeholder="系統角色" value="<?php echo htmlspecialchars($exact_role); ?>">
                </div>
                <div class="exact-flex-item">
                    <span>世代代數</span>
                    <input type="number" name="exact_generation" class="search-input" placeholder="精確世代數字" value="<?php echo htmlspecialchars($exact_generation); ?>">
                </div>
                <div class="exact-flex-item">
                    <span>加入日期</span>
                    <input type="date" name="exact_date" class="search-input" value="<?php echo htmlspecialchars($exact_date); ?>">
                </div>
                <div class="exact-flex-item">
                    <span>Email</span>
                    <input type="text" name="exact_email" class="search-input" placeholder="精確電子郵件" value="<?php echo htmlspecialchars($exact_email); ?>">
                </div>
                <div class="exact-flex-item" style="justify-content: flex-end;">
                    <button type="submit" class="search-btn btn-exact" style="width: 100%;">精確篩選 ➔</button>
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

        <div class="table-responsive-box">
            <table class="result-table">
                <thead>
                    <tr>
                        <th style="width: 120px; min-width:120px;"><input type="checkbox" onchange="toggleAll(this)"> 全選/操作</th>
                        <th>帳號主鍵 ID</th>
                        <th>帳號關聯資(Account)</th>
                        <th>會員關聯資(Members)</th>
                        <th>通訊安全與系統細節</th>
                        <th>宗親祖籍世系</th>
                        <th>生存居住現況</th>
                        <th>備註摘要欄</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td>
                                    <div class="action-links">
                                        <input type="checkbox" name="delete_ids[]" class="id-checkbox" value="<?php echo $row['account_id']; ?>">
                                        <span class="link-edit" onclick="loadMemberData(<?php echo $row['account_id']; ?>)">[編輯]</span>
                                        <span class="link-delete" onclick="confirmSingleDelete(<?php echo $row['account_id']; ?>)">[刪除]</span>
                                    </div>
                                </td>
                                <td><strong style="color:#1e3a8a;">#<?php echo $row['account_id']; ?></strong></td>
                                <td>
                                    <span class="badge-act">新會員號:</span> <?php echo htmlspecialchars($row['account_new_member']); ?><br>
                                    <span class="badge-act">舊會員號:</span> <?php echo htmlspecialchars($row['account_old_member']); ?><br>
                                    <span class="badge-act">姓名:</span> <strong><?php echo htmlspecialchars($row['account_name']); ?></strong><br>
                                    <span class="badge-act">性別:</span> <?php echo htmlspecialchars($row['account_gender']); ?>
                                </td>
                                <td>
                                    <span class="badge-mem">流水 ID:</span> <?php echo $row['member_id']; ?><br>
                                    <span class="badge-mem">新會員號:</span> <?php echo htmlspecialchars($row['member_new_member']); ?><br>
                                    <span class="badge-mem">舊會員號:</span> <?php echo htmlspecialchars($row['member_old_member']); ?><br>
                                    <span class="badge-mem">姓名/性別:</span> <?php echo htmlspecialchars($row['member_name']); ?> / <?php echo htmlspecialchars($row['member_gender']); ?>
                                </td>
                                <td>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?><br>
                                    <strong>密碼:</strong> <?php echo htmlspecialchars($row['password']); ?><br>
                                    <strong>角色:</strong> <?php echo htmlspecialchars($row['role']); ?><br>
                                    <strong>狀態:</strong> <?php echo htmlspecialchars($row['status']); ?>
                                </td>
                                <td>
                                    <strong>世代代數:</strong> 第 <?php echo $row['generation']; ?> 代<br>
                                    <strong>世祖資訊:</strong> 第 <?php echo $row['emperor_shizu']; ?> 世祖<br>
                                    <strong>大房歸屬:</strong> 第 <?php echo $row['number_of_houses']; ?> 大房<br>
                                    <strong>生日:</strong> <?php echo $row['birthday']; ?>
                                </td>
                                <td>
                                    <strong>出生地:</strong> <?php echo htmlspecialchars($row['placeOfBirth']); ?><br>
                                    <strong>現地址:</strong> <?php echo htmlspecialchars($row['address']); ?><br>
                                    <strong>派送下屬:</strong> <?php echo htmlspecialchars($row['SendSubordinates']); ?><br>
                                    <strong>生存狀態:</strong> <?php echo htmlspecialchars($row['living_status']); ?>
                                </td>
                                <td>
                                    <div style="font-size:11px; margin-bottom:5px; background:#f1f5f9; padding:4px;">
                                        <strong>帳號備註:</strong> <?php echo htmlspecialchars($row['account_remarks']); ?>
                                    </div>
                                    <div style="font-size:11px; background:#fef3c7; padding:4px;">
                                        <strong>會員備註:</strong> <?php echo $row['member_remarks']; ?>
                                    </div>
                                    <div style="font-size:11px; color:#666; margin-top:4px;">
                                        📅 加入: <?php echo $row['join_date']; ?><br>
                                        ❌ 停權: <?php echo $row['discontinued_date']; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">❌ 沒有找到任何符合資料庫連動條件的會員紀錄。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <div class="editor-modal-overlay" id="editorModal">
        <div class="editor-modal-content">
            <div class="editor-modal-header">
                <div style="font-size:1.2rem; font-weight:bold; color:#1e3a8a;">🌿 會員備註細節進階富文本編輯器</div>
                <button class="editor-modal-close" onclick="closeJoditModal()">&times;</button>
            </div>
            <div class="editor-container-box">
                <textarea id="joditEditorTarget"></textarea>
            </div>
            <div style="text-align:right;">
                <button type="button" class="search-btn" style="background:#94a3b8; margin-right:10px;" onclick="closeJoditModal()">取消</button>
                <button type="button" class="search-btn btn-update" onclick="saveJoditContent()">確認回填至備註區</button>
            </div>
        </div>
    </div>

    <script>
        const joditEditor = new Jodit('#joditEditorTarget', {
            height: '100%',
            language: 'zh_tw'
        });

        // AJAX 讀取並填入單筆會員全欄位
        function loadMemberData(id) {
            fetch('?action=get_single_member&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        document.getElementById('u_account_id').value = data.account_id;
                        document.getElementById('txt_view_id').textContent = "#" + data.account_id;

                        document.getElementById('u_account_new_member').value = data.account_new_member;
                        document.getElementById('u_account_old_member').value = data.account_old_member;
                        document.getElementById('u_account_name').value = data.account_name;
                        document.getElementById('u_account_gender').value = data.account_gender;
                        document.getElementById('u_email').value = data.email;
                        document.getElementById('u_password').value = data.password;
                        document.getElementById('u_role').value = data.role;
                        document.getElementById('u_status').value = data.status;
                        document.getElementById('u_join_date').value = data.join_date;
                        document.getElementById('u_discontinued_date').value = data.discontinued_date;
                        document.getElementById('u_account_remarks').value = data.account_remarks;

                        document.getElementById('u_member_id').value = data.member_id;
                        document.getElementById('u_member_new_member').value = data.member_new_member;
                        document.getElementById('u_member_old_member').value = data.member_old_member;
                        document.getElementById('u_member_name_gender').value = data.member_name + " / " + data.member_gender;
                        document.getElementById('u_generation').value = data.generation;
                        document.getElementById('u_emperor_shizu').value = data.emperor_shizu;
                        document.getElementById('u_number_of_houses').value = data.number_of_houses;
                        document.getElementById('u_birthday').value = data.birthday;
                        document.getElementById('u_placeOfBirth').value = data.placeOfBirth;
                        document.getElementById('u_address').value = data.address;
                        document.getElementById('u_SendSubordinates').value = data.SendSubordinates;
                        document.getElementById('u_living_status').value = data.living_status;
                        document.getElementById('u_member_remarks').value = data.member_remarks;

                        document.getElementById('editContainer').style.display = 'block';
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
        }

        function openJoditModal() {
            joditEditor.value = document.getElementById('u_member_remarks').value;
            document.getElementById('editorModal').classList.add('active');
            setTimeout(() => {
                joditEditor.events.fire('resize');
            }, 100);
        }

        function closeJoditModal() {
            document.getElementById('editorModal').classList.remove('active');
        }

        function saveJoditContent() {
            document.getElementById('u_member_remarks').value = joditEditor.value;
            closeJoditModal();
        }

        function toggleAll(master) {
            var checkboxes = document.getElementsByClassName('id-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = master.checked;
            }
        }

        function confirmSingleDelete(id) {
            if (confirm("⚠️ 確定要從資料庫同步永久刪除此筆 帳號 ID #" + id + " 及其對應的宗親會員紀錄嗎？")) {
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
                alert("請先選取欲批次刪除的會員項目。");
                return;
            }
            if (confirm("⚠️ 警告：確定要整批同步永久刪除這 " + checkedCount + " 筆帳號與會員連動資料嗎？")) {
                document.getElementById('delete_form').submit();
            }
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>