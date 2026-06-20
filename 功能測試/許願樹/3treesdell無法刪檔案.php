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

// ==========================================
// 🚀 核心修正：融入您提供的範本邏輯，確實刪除實體檔案與資料
// ==========================================
$message_notice = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $ids_to_delete = [];
    
    // 收集前端傳過來的 makeawish ID
    if (isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
        $ids_to_delete = array_map('intval', $_POST['delete_ids']);
    } elseif (isset($_POST['single_delete_id'])) {
        $ids_to_delete[] = intval($_POST['single_delete_id']);
    }

    if (!empty($ids_to_delete)) {
        $deleted_count = 0;
        
        // 指定您的實體檔案存放目錄
        $file_base_dir = "D:\\web02\\icon\\";

        foreach ($ids_to_delete as $del_id) {
            
            // 🎯 雷同功能實作 1：先找出 files 中所有對應的主資料 row 紀錄
            $file_sql = "SELECT * FROM files WHERE file_id = ?";
            $f_stmt = $conn->prepare($file_sql);
            $f_stmt->bind_param("i", $del_id);
            $f_stmt->execute();
            $f_result = $f_stmt->get_result();
            
            while ($photo = $f_result->fetch_assoc()) {
                // 取得資料庫存的路徑或檔名
                $url = $photo['file_path']; 
                
                if (!empty($url)) {
                    // 使用 basename 確保不論資料庫存絕對還是相對路徑，都能抽取出純檔名
                    $pure_filename = basename($url);
                    $full_real_path = $file_base_dir . $pure_filename;
                    
                    // 🎯 雷同功能實作 2：unlink 刪除實體檔案
                    if (file_exists($full_real_path)) {
                        unlink($full_real_path); 
                    } else if (file_exists($url)) {
                        // 備用防錯：若直接丟資料庫欄位找得到就直接刪除
                        unlink($url);
                    }
                }
            }
            $f_stmt->close();

            // 🎯 雷同功能實作 3：delete 刪除資料庫中的檔案紀錄
            $del_files_sql = "DELETE FROM files WHERE file_id = ?";
            $df_stmt = $conn->prepare($del_files_sql);
            $df_stmt->bind_param("i", $del_id);
            $df_stmt->execute();
            $df_stmt->close();

            // 4. 最後刪除主資料表 makeawish 的數據
            $del_wish_sql = "DELETE FROM makeawish WHERE ID = ?";
            $dw_stmt = $conn->prepare($del_wish_sql);
            $dw_stmt->bind_param("i", $del_id);
            $dw_stmt->execute();
            
            if ($dw_stmt->affected_rows > 0) {
                $deleted_count++;
            }
            $dw_stmt->close();
        }
        
        $message_notice = "成功刪除 {$deleted_count} 筆祈願紀錄！";
    }
}

// ==========================================
// 2. 處理結合 members 與 makeawish 的搜尋邏輯
// ==========================================
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$results = [];
$search_status = '預覽最新資料';

$exact_generation = isset($_GET['exact_generation']) ? trim($_GET['exact_generation']) : ''; 
$exact_id = isset($_GET['exact_id']) ? trim($_GET['exact_id']) : '';
$exact_name = isset($_GET['exact_name']) ? trim($_GET['exact_name']) : '';
$exact_date = isset($_GET['exact_date']) ? trim($_GET['exact_date']) : '';

if ($keyword !== '') {
    $search_status = '第一類：綜合模糊查詢 ➔ ' . htmlspecialchars($keyword);
    $like_keyword = "%" . $keyword . "%";

    $sql = "SELECT 
                w.ID, w.name, w.number_of_houses, w.emperor_shizu, w.generation, w.family_members, w.message_of_blessing, w.login_time,
                m.new_member
            FROM makeawish w
            LEFT JOIN members m ON w.name = m.name
            WHERE m.new_member LIKE ? 
               OR w.name LIKE ? 
               OR w.ID LIKE ?
               OR w.message_of_blessing LIKE ?
               OR w.family_members LIKE ? 
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
    $search_status = '第二類：多條件精確查詢';

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

    $sql = "SELECT 
                w.ID, w.name, w.number_of_houses, w.emperor_shizu, w.generation, w.family_members, w.message_of_blessing, w.login_time,
                m.new_member
            FROM makeawish w
            LEFT JOIN members m ON w.name = m.name
            WHERE $where_sql
            ORDER BY w.ID DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
} else {
    // 預設預覽最新 20 筆 (已修正 ID 重複問題)
    $sql = "SELECT 
                w.ID, w.name, w.number_of_houses, w.emperor_shizu, w.generation, w.family_members, w.message_of_blessing, w.login_time,
                m.new_member
            FROM makeawish w
            LEFT JOIN members m ON w.name = m.name
            GROUP BY w.ID
            ORDER BY w.ID DESC LIMIT 20";
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
    <title>跨表結合查詢系統</title>
    <style>
        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background-color: #f4f7f6;
            color: #333;
            padding: 30px;
        }

        .search-container {
            max-width: 1100px;
            margin: 0 auto 15px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            color: #b45309;
            font-weight: bold;
            margin-bottom: 15px;
            border-left: 5px solid #d97706;
            padding-left: 10px;
            margin-top: 25px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .exact-single-row {
            display: flex;
            gap: 20px;
            width: 100%;
            align-items: center;
        }

        .exact-half-block {
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

        .search-input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            height: 42px;
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
            background: #d97706;
            padding: 0 15px;
        }

        .btn-exact:hover {
            background: #b45309;
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
        .btn-batch-delete:hover {
            background: #b91c1c;
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

        .badge-id {
            display: inline-block;
            background: #f0fdf4;
            color: #166534;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            border: 1px solid #bbf7d0;
        }

        .badge-info {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 4px;
        }

        .badge-house {
            display: inline-block;
            background: #fef3c7;
            color: #d97706;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 4px;
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
        .link-edit { color: #2563eb; }
        .link-edit:hover { text-decoration: underline; }
        .link-delete { color: #dc2626; cursor: pointer; }
        .link-delete:hover { text-decoration: underline; }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #666;
        }

        .blessing-content img {
            max-width: 120px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin: 5px;
        }

        @media (max-width: 900px) {
            .exact-single-row {
                flex-direction: column;
                gap: 10px;
            }
            .exact-half-block {
                flex: 0 0 100%;
                width: 100%;
            }
        }
    </style>
    
    <script>
        function toggleAll(master) {
            var checkboxes = document.getElementsByClassName('id-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = master.checked;
            }
        }

        function confirmSingleDelete(id) {
            var msg = "⚠️ 【警告】您確定要刪除此筆 許願ID #" + id + " 資料嗎？\n\n此動作將會「真實清空資料庫欄位」並「永久從伺服器硬碟 D:\\web02\\icon\\ 刪除上傳的實體圖片檔案」，刪除後將完全無法復原！";
            if (confirm(msg)) {
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
                alert("請先勾選想要刪除的資料項目。");
                return false;
            }

            var msg = "⚠️ ⚠️ 【極重要警告】您目前選取了 " + checkedCount + " 筆資料！\n\n按下確認後，將會「整批永久刪除這些關聯資料與 D:\\web02\\icon\\ 內實體圖片檔案」，這項操作是不可逆的，您確定要繼續執行嗎？";
            if (confirm(msg)) {
                document.getElementById('delete_form').submit();
            }
        }
    </script>
</head>

<body>

    <?php if ($message_notice !== ''): ?>
        <script>alert("<?php echo $message_notice; ?>");</script>
    <?php endif; ?>

    <div class="search-container">
        <form method="GET" action="">
            <div class="search-title">🌿 祈願卡智慧跨表模糊查詢 (支援許願ID/派下員/宗親會員編號或姓名/家庭成員/許願卡內文)</div>
            <div class="search-box">
                <input type="text" name="keyword" class="search-input" placeholder="請輸入編號、姓名、內文或家庭成員關鍵字..." value="<?php echo htmlspecialchars($keyword); ?>">
                <button type="submit" class="search-btn">搜尋 🔍</button>
            </div>
        </form>

        <div class="exact-section-title">🎯 類別二：多欄位條件精確查詢 (完全符合輸入資料)</div>
        <form method="GET" action="">
            <div class="exact-single-row">
                <div class="exact-half-block">
                    <div class="input-sub-item">
                        <span>1.世代</span>
                        <input type="text" name="exact_generation" class="search-input" placeholder="定居大甲世代" value="<?php echo htmlspecialchars($exact_generation); ?>">
                    </div>
                    <div class="input-sub-item">
                        <span>2.編號</span>
                        <input type="text" name="exact_id" class="search-input" placeholder="派下員/宗親會員編號" value="<?php echo htmlspecialchars($exact_id); ?>">
                    </div>
                </div>

                <div class="exact-half-block">
                    <div class="input-sub-item">
                        <span>3.姓名</span>
                        <input type="text" name="exact_name" class="search-input" placeholder="派下員/宗親會員姓名" value="<?php echo htmlspecialchars($exact_name); ?>">
                    </div>
                    <div class="input-sub-item">
                        <span>4.時間</span>
                        <input type="date" name="exact_date" class="search-input" value="<?php echo htmlspecialchars($exact_date); ?>">
                    </div>
                    <button type="submit" class="search-btn btn-exact">精確篩選</button>
                </div>
            </div>
        </form>
    </div>

    <div class="status-info-bar">
        <div>
            📌 目前狀態：<?php echo $search_status; ?> ( 找到 <?php echo count($results); ?> 筆資料 )
            <?php if ($keyword !== '' || $exact_id !== '' || $exact_name !== '' || $exact_generation !== '' || $exact_date !== ''): ?>
                <a href="?" style="margin-left: 15px; color: #dc2626; text-decoration: none;">[清除重設]</a>
            <?php endif; ?>
        </div>
        <div>
            <button type="button" class="btn-batch-delete" onclick="confirmBatchDelete()">🗑️ 刪除選取項目</button>
        </div>
    </div>

    <form id="delete_form" method="POST" action="">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="single_delete_id" id="single_delete_id" value="">

        <table class="result-table">
            <thead>
                <tr>
                    <th style="width: 140px;">
                        <input type="checkbox" onchange="toggleAll(this)"> 全選/操作
                    </th>
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
                                    <a href="#" class="link-edit" onclick="alert('編輯功能開發中'); return false;">[編輯]</a>
                                    <span class="link-delete" onclick="confirmSingleDelete(<?php echo $row['ID']; ?>)">[刪除]</span>
                                </div>
                            </td>

                            <td><strong>#<?php echo $row['ID']; ?></strong></td>

                            <td>
                                <div class="member-info-block">
                                    <strong class="member-name"><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <span class="badge-id">編號: <?php echo $row['new_member'] ? htmlspecialchars($row['new_member']) : '未綁定'; ?></span>
                                    <span class="badge-house">第 <?php echo $row['number_of_houses']; ?> 大房</span>
                                    <br>
                                    <span class="badge-info">第 <?php echo $row['emperor_shizu']; ?> 世祖 / 大甲 <?php echo $row['generation']; ?> 代</span>

                                    <?php if (!empty($row['family_members'])): ?>
                                        <span class="family-info">👨‍👩‍👧‍👦 <strong>家庭成員：</strong><?php echo htmlspecialchars($row['family_members']); ?></span>
                                    <?php endif; ?>

                                    <br>
                                    <span class="time-info">
                                        📅 <strong>發表時間：</strong><br>
                                        <?php echo $row['login_time'] ? htmlspecialchars($row['login_time']) : '無記錄'; ?>
                                    </span>
                                </div>
                            </td>

                            <td class="blessing-content">
                                <?php echo $row['message_of_blessing']; ?>
                            </td>
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

</body>

</html>
<?php
$conn->close();
?>