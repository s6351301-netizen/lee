<?php
session_start();

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
// 2. 處理篩選條件的下拉選單選項目錄（不重複撈取）
// ==========================================
$districts = []; $land_numbers = []; $record_dates = []; $land_uses = [];

$res = $conn->query("SELECT DISTINCT district FROM ethnic_property_price_view WHERE district IS NOT NULL AND district != ''");
while($r = $res->fetch_assoc()) { $districts[] = $r['district']; }

$res = $conn->query("SELECT DISTINCT land_number FROM ethnic_property_price_view WHERE land_number IS NOT NULL AND land_number != '' ORDER BY land_number");
while($r = $res->fetch_assoc()) { $land_numbers[] = $r['land_number']; }

$res = $conn->query("SELECT DISTINCT record_date FROM ethnic_property_price_view WHERE record_date IS NOT NULL AND record_date != '' ORDER BY record_date DESC");
while($r = $res->fetch_assoc()) { $record_dates[] = $r['record_date']; }

$res = $conn->query("SELECT DISTINCT land_use FROM ethnic_property_price_view WHERE land_use IS NOT NULL AND land_use != ''");
while($r = $res->fetch_assoc()) { $land_uses[] = $r['land_use']; }


// ==========================================
// 3. 設定篩選預設值與分頁引數
// ==========================================
// 預設值為大甲區、0995-0000、當年度(2026-01)
$filter_status    = isset($_GET['status']) ? $_GET['status'] : '全部';
$filter_district  = isset($_GET['district']) ? $_GET['district'] : '大甲區';
$filter_land_no   = isset($_GET['land_number']) ? $_GET['land_number'] : '0995-0000';
$filter_date      = isset($_GET['record_date']) ? $_GET['record_date'] : '2026-01';
$filter_land_use  = isset($_GET['land_use']) ? $_GET['land_use'] : '全部';

$limit = isset($_GET['limit']) && (int)$_GET['limit'] === 100 ? 100 : 50;
$page  = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ==========================================
// 4. 動態組成 SQL 查詢語句與 Bind 參數
// ==========================================
$where_clauses = [];
$params = [];
$types = "";

if ($filter_status !== '全部') {
    $where_clauses[] = "`status` = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if ($filter_district !== '全部') {
    $where_clauses[] = "`district` = ?";
    $params[] = $filter_district;
    $types .= "s";
}
if ($filter_land_no !== '全部') {
    $where_clauses[] = "`land_number` = ?";
    $params[] = $filter_land_no;
    $types .= "s";
}
if ($filter_date !== '全部') {
    $where_clauses[] = "`record_date` = ?";
    $params[] = $filter_date;
    $types .= "s";
}
if ($filter_land_use !== '全部') {
    $where_clauses[] = "`land_use` = ?";
    $params[] = $filter_land_use;
    $types .= "s";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 4a. 計算總筆數
$count_sql = "SELECT COUNT(*) as total FROM `ethnic_property_price_view`" . $where_sql;
$stmt_c = $conn->prepare($count_sql);
if (!empty($types)) {
    $stmt_c->bind_param($types, ...$params);
}
$stmt_c->execute();
$total_rows = $stmt_c->get_result()->fetch_assoc()['total'];
$stmt_c->close();

$total_pages = ceil($total_rows / $limit);

// 4b. 獲取當頁清單資料 (已加入 owner_type 欄位)
$data_sql = "SELECT record_date, section_name, land_number, posted_land_value, declared_land_value, ethnic_area, land_use, owner_type 
             FROM `ethnic_property_price_view` " . $where_sql . " LIMIT ?, ?";
$stmt_d = $conn->prepare($data_sql);

$data_params = $params;
$data_params[] = $offset;
$data_params[] = $limit;
$data_types = $types . "ii";

$stmt_d->bind_param($data_types, ...$data_params);
$stmt_d->execute();
$data_result = $stmt_d->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>族產地價與稅務查詢系統</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f9f9f9; color: #333; }
        .filter-section { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; background: #eee; padding: 15px; border-radius: 5px; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-weight: bold; margin-bottom: 5px; }
        .filter-group select { padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; min-width: 130px; }
        .btn-search { background-color: #007bff; color: white; border: none; padding: 8px 15px; align-self: flex-end; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-search:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .pagination { margin-top: 20px; display: flex; gap: 8px; justify-content: center; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 4px; }
        .pagination .active { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination .disabled { color: #ccc; pointer-events: none; }
        .summary-info { background-color: #e1f5fe; padding: 10px; border-left: 5px solid #0288d1; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

<h2>📊 族產公告地價與稅務查詢 (property_tax.php)</h2>
<hr>

<!-- 篩選表單 -->
<form method="GET" action="property_tax.php" class="filter-section">
    <div class="filter-group">
        <label>A.持有或已賣</label>
        <select name="status">
            <option value="全部" <?php if($filter_status=='全部') echo 'selected'; ?>>全部</option>
            <option value="持有" <?php if($filter_status=='持有') echo 'selected'; ?>>持有</option>
            <option value="已賣" <?php if($filter_status=='已賣') echo 'selected'; ?>>已賣</option>
        </select>
    </div>

    <div class="filter-group">
        <label>B.地區</label>
        <select name="district">
            <option value="全部">全部</option>
            <?php foreach($districts as $d): ?>
                <option value="<?php echo htmlspecialchars($d); ?>" <?php if($filter_district==$d) echo 'selected'; ?>><?php echo htmlspecialchars($d); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-group">
        <label>C.地號</label>
        <select name="land_number">
            <option value="全部">全部</option>
            <?php foreach($land_numbers as $ln): ?>
                <option value="<?php echo htmlspecialchars($ln); ?>" <?php if($filter_land_no==$ln) echo 'selected'; ?>><?php echo htmlspecialchars($ln); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-group">
        <label>D.公告年月</label>
        <select name="record_date">
            <option value="全部">全部</option>
            <?php foreach($record_dates as $rd): ?>
                <option value="<?php echo htmlspecialchars($rd); ?>" <?php if($filter_date==$rd) echo 'selected'; ?>><?php echo htmlspecialchars($rd); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-group">
        <label>E.使用地類別</label>
        <select name="land_use">
            <option value="全部">全部</option>
            <?php foreach($land_uses as $lu): ?>
                <option value="<?php echo htmlspecialchars($lu); ?>" <?php if($filter_land_use==$lu) echo 'selected'; ?>><?php echo htmlspecialchars($lu); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-group">
        <label>每頁顯示筆數</label>
        <select name="limit">
            <option value="50" <?php if($limit==50) echo 'selected'; ?>>50 筆</option>
            <option value="100" <?php if($limit==100) echo 'selected'; ?>>100 筆</option>
        </select>
    </div>

    <button type="submit" class="btn-search">🔍 查詢資料</button>
</form>

<div class="summary-info">
    當前篩選條件下，共計找到 <?php echo $total_rows; ?> 筆重複/歷年紀錄。
</div>

<!-- 資料呈現表格 -->
<table>
    <thead>
        <tr>
            <th>公告年月</th>
            <th>段小段</th>
            <th>地號</th>
            <th>公告土地現值(元)</th>
            <th>公告地價(元)</th>
            <th>面積(㎡)</th>
            <th>持分</th>
            <th>使用地類別</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($data_result->num_rows > 0): ?>
            <?php while($row = $data_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['record_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['land_number']); ?></td>
                    <td>$<?php echo number_format($row['posted_land_value'], 2); ?></td>
                    <td>$<?php echo number_format($row['declared_land_value'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['ethnic_area']); ?></td>
                    <td>
                        <?php 
                        // 解析 owner_type 欄位中，"祭祀公業:" 後面的百分比數值
                        $share_display = '100.00%'; 
                        if (!empty($row['owner_type']) && preg_match('/祭祀公業:\s*([0-9.]+%)/u', $row['owner_type'], $matches)) {
                            $share_display = $matches[1];
                        }
                        echo htmlspecialchars($share_display); 
                        ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($row['land_use']); ?></strong></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center; color: red;">查無符合條件的數據。</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- 分頁導覽列 -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])); ?>" class="<?php if($page <= 1) echo 'disabled'; ?>">上一頁</a>
        
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php if($page == $i) echo 'active'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])); ?>" class="<?php if($page >= $total_pages) echo 'disabled'; ?>">下一頁</a>
    </div>
<?php endif; ?>

</body>
</html>