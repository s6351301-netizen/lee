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
    die(json_encode(["status" => "error", "message" => "連線失敗: " . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// ==========================================
// 2. 處理 AJAX 請求 (API 路由)
// ==========================================

// 新增功能：AJAX 獲取所有"持有"土地資料
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_hold_lands') {
    header('Content-Type: application/json');
    $hold_lands = [];
    $res = $conn->query("SELECT id, city, district, section_code, section_name, land_number, area FROM ethnic_property WHERE status = '持有' ORDER BY district ASC, land_number ASC");
    while ($row = $res->fetch_assoc()) {
        $hold_lands[] = $row;
    }
    echo json_encode($hold_lands);
    exit;
}

// A. 供表單各欄位透過 AJAX 獲取不重複歷史紀錄
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_field_suggestions') {
    header('Content-Type: application/json');
    $field = $_GET['field'] ?? '';
    
    $allowed_fields = ['status', 'city', 'district', 'section_code', 'section_name', 'land_number', 'register_date', 'zoning', 'land_use', 'owner_type'];
    
    if (in_array($field, $allowed_fields)) {
        $suggestions = [];
        $sql = "SELECT DISTINCT `$field` FROM ethnic_property WHERE `$field` IS NOT NULL AND `$field` != '' ORDER BY `$field` ASC";
        $res = $conn->query($sql);
        while ($row = $res->fetch_row()) {
            $suggestions[] = $row[0];
        }
        echo json_encode($suggestions);
    } else {
        echo json_encode([]);
    }
    exit;
}

// B. 處理資料表單新增 (AJAX POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'insert') {
    header('Content-Type: application/json');
    
    $status        = trim($_POST['status'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $district      = trim($_POST['district'] ?? '');
    $section_code  = trim($_POST['section_code'] ?? '');
    $section_name  = trim($_POST['section_name'] ?? '');
    $land_number   = trim($_POST['land_number'] ?? '');
    $register_date = trim($_POST['register_date'] ?? '');
    $area          = !empty($_POST['area']) ? floatval($_POST['area']) : null;
    $zoning        = trim($_POST['zoning'] ?? '');
    $land_use      = trim($_POST['land_use'] ?? '');
    $owner_type    = trim($_POST['owner_type'] ?? '');
    
    $record_date          = trim($_POST['record_date'] ?? '');
    $posted_land_value    = !empty($_POST['posted_land_value']) ? floatval($_POST['posted_land_value']) : null;
    $declared_land_value  = !empty($_POST['declared_land_value']) ? floatval($_POST['declared_land_value']) : null;
    $land_area            = !empty($_POST['land_area']) ? floatval($_POST['land_area']) : null;

    $conn->begin_transaction();
    try {
        // 1. 新增到 ethnic_property
        $sql_ethnic = "INSERT INTO ethnic_property (status, city, district, section_code, section_name, land_number, register_date, area, zoning, land_use, owner_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_e = $conn->prepare($sql_ethnic);
        $stmt_e->bind_param("sssssssdsss", $status, $city, $district, $section_code, $section_name, $land_number, $register_date, $area, $zoning, $land_use, $owner_type);
        $stmt_e->execute();
        $stmt_e->close();

        // 2. 新增或更新到 land_price
        if (!empty($record_date)) {
            $sql_price = "INSERT INTO land_price (record_date, section_name, land_number, posted_land_value, declared_land_value, land_area) 
                          VALUES (?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          posted_land_value = VALUES(posted_land_value), declared_land_value = VALUES(declared_land_value), land_area = VALUES(land_area)";
            $stmt_p = $conn->prepare($sql_price);
            $stmt_p->bind_param("sssddd", $record_date, $section_name, $land_number, $posted_land_value, $declared_land_value, $land_area);
            $stmt_p->execute();
            $stmt_p->close();
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "🎉 所有欄位資料與歷年地價皆成功寫入！"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "❌ 寫入失敗: " . $e->getMessage()]);
    }
    exit;
}

// 新增功能：整批儲存年度地價資料
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'batch_insert_price') {
    header('Content-Type: application/json');
    $record_date = trim($_POST['batch_record_date'] ?? '');
    $prices = $_POST['price'] ?? [];
    
    if (empty($record_date)) {
        echo json_encode(["status" => "error", "message" => "❌ 請填寫公告年月。"]);
        exit;
    }
    
    $conn->begin_transaction();
    try {
        $sql_price = "INSERT INTO land_price (record_date, section_name, land_number, posted_land_value, declared_land_value, land_area) 
                      VALUES (?, ?, ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE 
                      posted_land_value = VALUES(posted_land_value), declared_land_value = VALUES(declared_land_value), land_area = VALUES(land_area)";
        $stmt_p = $conn->prepare($sql_price);
        
        foreach ($prices as $p) {
            $sec_name = trim($p['section_name'] ?? '');
            $l_num    = trim($p['land_number'] ?? '');
            $p_val    = !empty($p['posted_land_value']) ? floatval($p['posted_land_value']) : null;
            $d_val    = !empty($p['declared_land_value']) ? floatval($p['declared_land_value']) : null;
            $l_area   = !empty($p['land_area']) ? floatval($p['land_area']) : null;
            
            if (!empty($sec_name) && !empty($l_num)) {
                $stmt_p->bind_param("sssddd", $record_date, $sec_name, $l_num, $p_val, $d_val, $l_area);
                $stmt_p->execute();
            }
        }
        $stmt_p->close();
        $conn->commit();
        echo json_encode(["status" => "success", "message" => "🎉 批量年度地價資料更新成功！"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "❌ 批量寫入失敗: " . $e->getMessage()]);
    }
    exit;
}

// C. 獲取底層表格完整不重複歷史紀錄 (AJAX GET)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_history') {
    header('Content-Type: application/json');
    $history_data = [];
    
    // 2. 依照地區(district), 地號(land_number)由小排到大修改
    $res_ethnic = $conn->query("SELECT DISTINCT status, city, district, section_code, section_name, land_number, register_date, area, zoning, land_use, owner_type FROM ethnic_property ORDER BY district ASC, land_number ASC");
    while ($row = $res_ethnic->fetch_assoc()) {
        $history_data['ethnic'][] = $row;
    }
    
    $res_price = $conn->query("SELECT DISTINCT record_date, section_name, land_number, posted_land_value, declared_land_value, land_area FROM land_price ORDER BY record_date DESC");
    while ($row = $res_price->fetch_assoc()) {
        $history_data['price'][] = $row;
    }
    
    echo json_encode($history_data);
    exit;
}

// 計算當前程式公告年月預設值
$current_year = intval(date('Y'));
$current_md = date('m-d');
if ($current_md <= '01-02') {
    $default_record_date = $current_year . "-01";
} else {
    $default_record_date = ($current_year + 1) . "-01";
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>族產與歷年地價管理系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: "Microsoft JhengHei", sans-serif; }
        /* 調整總體表單佔 95% */
        .custom-container { max-width: 95%; margin: 0 auto; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .required-star { color: red; margin-left: 3px; }
    </style>
</head>
<body>

<div class="container-fluid custom-container py-5">

    <!-- 壹、填寫年度地價資料 新增項目 (取消所有表單的內距 p-0) -->
    <div class="card mb-5">
        <div class="card-header bg-dark text-white p-3">
            <h4 class="mb-0">📋 壹、填寫年度地價資料</h4>
        </div>
        <div class="card-body p-0">
            <form id="batch-price-form">
                <div class="row align-items-center mb-4 ps-3 pt-3 pe-3">
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">公告年月*</label>
                        <input type="text" class="form-control" name="batch_record_date" value="<?php echo $default_record_date; ?>" required>
                    </div>
                </div>
                <div class="table-responsive mb-0">
                    <table class="table table-bordered align-middle text-center mb-0" style="font-size:0.9rem;">
                        <thead class="table-dark">
                            <tr>
                                <th>縣市</th><th>地區</th><th>段小段代碼</th><th>段小段名稱</th><th>地號</th><th>面積(㎡)</th><th>公告土地現值(元/㎡)</th><th>公告地價(元/㎡)</th><th>地價面積(㎡)</th>
                            </tr>
                        </thead>
                        <tbody id="hold-lands-tbody">
                            <tr><td colspan="9" class="text-muted py-3">正在載入持有土地資料...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-end pe-3 pb-3 pt-3">
                    <button type="submit" class="btn btn-primary px-5">全部新增地價資料</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header bg-primary text-white p-3">
            <h4 class="mb-0">📋貳、族產資料與地價新增表單</h4>
        </div>
        <div class="card-body p-4">
            
            <div id="alert-box" class="alert d-none alert-dismissible fade show" role="alert">
                <span id="alert-msg"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <form id="property-form">
                <!-- 第一部分：族產基本資料 -->
                <h5 class="text-secondary border-bottom pb-2 mb-3">第一部分：族產基本資料 (點擊或輸入時，將動態透過 AJAX 撈取歷史選項)</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">狀態<span class="required-star">*</span></label>
                        <input type="text" class="form-control ajax-suggest" name="status" list="suggest-status" placeholder="持有或已賣" required>
                        <datalist id="suggest-status"></datalist>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">縣市</label>
                        <input type="text" class="form-control ajax-suggest" name="city" list="suggest-city" placeholder="例如：臺中市">
                        <datalist id="suggest-city"></datalist>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">鄉鎮市區</label>
                        <input type="text" class="form-control ajax-suggest" name="district" list="suggest-district" placeholder="例如：大甲區">
                        <datalist id="suggest-district"></datalist>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">段小段代碼</label>
                        <input type="text" class="form-control ajax-suggest" name="section_code" list="suggest-section_code" placeholder="例如：3652">
                        <datalist id="suggest-section_code"></datalist>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">段小段名稱<span class="required-star">*</span></label>
                        <input type="text" class="form-control ajax-suggest" name="section_name" list="suggest-section_name" placeholder="例如：義水段" required>
                        <datalist id="suggest-section_name"></datalist>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">地號<span class="required-star">*</span></label>
                        <input type="text" class="form-control ajax-suggest" name="land_number" list="suggest-land_number" placeholder="例如：0855-0000" required>
                        <datalist id="suggest-land_number"></datalist>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">登記日期</label>
                        <input type="text" class="form-control ajax-suggest" name="register_date" list="suggest-register_date" placeholder="例如：840519">
                        <datalist id="suggest-register_date"></datalist>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">面積 (平方公尺)</label>
                        <input type="number" step="0.01" class="form-control" name="area" placeholder="例如：12.73">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">使用分區</label>
                        <input type="text" class="form-control ajax-suggest" name="zoning" list="suggest-zoning" placeholder="例如：城鄉發展地區第一類">
                        <datalist id="suggest-zoning"></datalist>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">使用地類別</label>
                        <input type="text" class="form-control ajax-suggest" name="land_use" list="suggest-land_use" placeholder="例如：住宅區">
                        <datalist id="suggest-land_use"></datalist>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">土地權利人類別</label>
                    <input type="text" class="form-control ajax-suggest" name="owner_type" list="suggest-owner_type" placeholder="例如：祭祀公業:100.00%">
                    <datalist id="suggest-owner_type"></datalist>
                </div>

                <!-- 第二部分：土地公告地價資料 -->
                <h5 class="text-secondary border-bottom pb-2 mb-3 mt-4">第二部分：土地公告地價資料 (land_price)</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">公告年月<span class="required-star">*</span></label>
                        <input type="text" class="form-control" name="record_date" placeholder="格式如: 2026-01" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">公告土地現值 (元/㎡)</label>
                        <input type="number" step="0.01" class="form-control" name="posted_land_value" placeholder="24398">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">公告地價 (元/㎡)</label>
                        <input type="number" step="0.01" class="form-control" name="declared_land_value" placeholder="3049">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">地價面積 (㎡)</label>
                        <input type="number" step="0.01" class="form-control" name="land_area" placeholder="8278.29">
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="button" class="btn btn-secondary me-2" onclick="document.getElementById('property-form').reset()">重設</button>
                    <button type="submit" class="btn btn-success px-5">儲存送出</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 歷史資料展示區 -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📜族產歷史資料清單 (不重複)</h5>
                    <button class="btn btn-sm btn-outline-light" onclick="loadHistory()">🔄 重新整理</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle text-center" style="font-size:0.9rem;">
                            <thead class="table-secondary sticky-top">
                                <tr>
                                    <th>狀態</th><th>縣市</th><th>鄉鎮</th><th>代碼</th><th>段小段</th><th>地號</th><th>登記日</th><th>面積</th><th>分區</th><th>用途</th><th>權利人</th>
                                </tr>
                            </thead>
                            <tbody id="ethnic-history-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. 📊 歷年公告地價清單 修改為下拉式選單篩選模式 -->
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">📊 歷年公告地價清單 (land_price 不重複)</h5>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-3 bg-light p-2 rounded align-items-end">
                        <div class="col-md-2">
                            <label class="form-label text-xs">A.持有或已賣</label>
                            <select id="filter-status" class="form-select form-select-sm">
                                <option value="全部">全部</option>
                                <option value="持有" selected>持有</option>
                                <option value="已賣">已賣</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xs">B.地區</label>
                            <select id="filter-district" class="form-select form-select-sm">
                                <option value="全部">全部</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xs">C.地號</label>
                            <select id="filter-land-number" class="form-select form-select-sm">
                                <option value="全部">全部</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-xs">每頁顯示筆數</label>
                            <select id="filter-page-size" class="form-select form-select-sm">
                                <option value="50" selected>50 筆</option>
                                <option value="100">100 筆</option>
                            </select>
                        </div>
                        <!-- 按鈕對應前端事件執行查詢過濾 -->
                        <div class="col-md-4">
                            <button type="button" id="search-btn" class="btn btn-sm btn-primary w-100">🔍 查詢資料</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle text-center" style="font-size:0.9rem;">
                            <thead class="table-secondary sticky-top">
                                <tr>
                                    <th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th>
                                </tr>
                            </thead>
                            <tbody id="price-history-tbody"></tbody>
                        </table>
                    </div>
                    <!-- 分頁功能按鈕容器 -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="page-info" class="text-muted style-xs">顯示第 0 到 0 筆，共 0 筆</div>
                        <nav id="pagination-nav">
                            <ul class="pagination pagination-sm mb-0" id="pagination-ul"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 全域儲存撈取的原始地價歷史與族產關聯，用來做前端下拉選單篩選
let globalPriceData = [];
let globalEthnicData = [];

// 分頁控制變數
let currentPage = 1;
let filteredTotalData = [];

document.addEventListener("DOMContentLoaded", function() {
    loadHoldLands();            // 載入壹、持有土地的表格資料
    loadHistory();              // 載入表格歷史
    fetchAllFieldSuggestions(); // 網頁初始化時，撈取所有欄位的歷史紀錄做成提示選項

    // 監聽壹、填寫年度地價資料表單提交 (AJAX POST)
    document.getElementById('batch-price-form').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        fetch('property_add.php?action=batch_insert_price', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if(data.status === 'success') {
                loadHistory();
            }
        });
    });

    // 監聽表單提交 (AJAX POST)指向 property_add.php
    document.getElementById('property-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('property_add.php?action=insert', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const alertBox = document.getElementById('alert-box');
            const alertMsg = document.getElementById('alert-msg');
            alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
            
            if(data.status === 'success') {
                alertBox.classList.add('alert-success');
                alertMsg.innerText = data.message;
                document.getElementById('property-form').reset();
                loadHistory();              // 刷新歷史表格
                loadHoldLands();            // 刷新持有土地表格
                fetchAllFieldSuggestions(); // 新增成功後再度刷新欄位的 AJAX 聯想選項
            } else {
                alertBox.classList.add('alert-danger');
                alertMsg.innerText = data.message;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // 監聽「查詢」按鈕點擊篩選事件
    document.getElementById('search-btn').addEventListener('click', function() {
        currentPage = 1; // 每次按查詢重新回到第一頁
        filterPriceTable();
    });
});

// 壹、使用 AJAX 載入所有"持有"土地並生成對應欄位全部可寫入、修改之 input 標籤
function loadHoldLands() {
    fetch('property_add.php?action=get_hold_lands')
    .then(res => res.json())
    .then(lands => {
        const tbody = document.getElementById('hold-lands-tbody');
        tbody.innerHTML = '';
        if(lands.length > 0) {
            lands.forEach((item, index) => {
                tbody.innerHTML += `
                    <tr>
                        <td><input type="text" class="form-control form-control-sm text-center" name="price[${index}][city]" value="${item.city || ''}"></td>
                        <td><input type="text" class="form-control form-control-sm text-center" name="price[${index}][district]" value="${item.district || ''}"></td>
                        <td><input type="text" class="form-control form-control-sm text-center" name="price[${index}][section_code]" value="${item.section_code || ''}"></td>
                        <td><input type="text" class="form-control form-control-sm text-center" name="price[${index}][section_name]" value="${item.section_name || ''}"></td>
                        <td><input type="text" class="form-control form-control-sm text-center" name="price[${index}][land_number]" value="${item.land_number || ''}"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-center" name="price[${index}][area]" value="${item.area || ''}"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-center" name="price[${index}][posted_land_value]" placeholder="土地現值"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-center" name="price[${index}][declared_land_value]" placeholder="公告地價"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm text-center" name="price[${index}][land_area]" value="${item.area || ''}"></td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="9" class="text-muted py-3">目前無 any 持有土地資料</td></tr>';
        }
    });
}

// 向後端 property_add.php 查詢所有欄位的不重複歷史紀錄，並動態組裝成 Datalist 選項
function fetchAllFieldSuggestions() {
    const inputs = document.querySelectorAll('.ajax-suggest');
    inputs.forEach(input => {
        const fieldName = input.getAttribute('name');
        const datalist = document.getElementById(`suggest-${fieldName}`);
        
        if (datalist) {
            fetch(`property_add.php?action=get_field_suggestions&field=${fieldName}`)
            .then(res => res.json())
            .then(options => {
                datalist.innerHTML = ''; 
                options.forEach(val => {
                    const option = document.createElement('option');
                    option.value = val;
                    datalist.appendChild(option);
                });
                
                // 同步更新篩選器內的地段/地號清單 (已移除硬編碼預設值)
                if(fieldName === 'district') updateFilterOptions('filter-district', options);
                if(fieldName === 'land_number') updateFilterOptions('filter-land-number', options);
            })
            .catch(err => console.error(`無法透過 AJAX 獲取欄位【${fieldName}】的歷史資料:`, err));
        }
    });
}

// 修正後的選單更新函式：移除強制塞入歷史無效預設值的 unshift 邏輯
function updateFilterOptions(selectId, options) {
    const select = document.getElementById(selectId);
    if(!select) return;
    const currentVal = select.value || '全部';
    select.innerHTML = '';
    
    // 多加入「全部」選項於最上方
    const allOpt = document.createElement('option');
    allOpt.value = '全部';
    allOpt.innerText = '全部';
    if(currentVal === '全部') allOpt.selected = true;
    select.appendChild(allOpt);
    
    options.forEach(val => {
        if(val === '全部') return;
        const opt = document.createElement('option');
        opt.value = val;
        opt.innerText = val;
        if(val === currentVal && currentVal !== '全部') opt.selected = true;
        select.appendChild(opt);
    });
}

// 撈取下方表格完整歷史不重複清單
function loadHistory() {
    fetch('property_add.php?action=get_history')
    .then(response => response.json())
    .then(data => {
        globalPriceData = data.price || [];
        globalEthnicData = data.ethnic || [];

        // 族產歷史 (已透過後端SQL限制由district, land_number由小排到大)
        const ethnicTbody = document.getElementById('ethnic-history-tbody');
        ethnicTbody.innerHTML = '';
        if(globalEthnicData.length > 0) {
            globalEthnicData.forEach(item => {
                ethnicTbody.innerHTML += `
                    <tr>
                        <td><span class="badge ${item.status === '持有' ? 'bg-success' : 'bg-danger'}">${item.status}</span></td>
                        <td>${item.city || '-'}</td>
                        <td>${item.district || '-'}</td>
                        <td>${item.section_code || '-'}</td>
                        <td>${item.section_name || '-'}</td>
                        <td>${item.land_number || '-'}</td>
                        <td>${item.register_date || '-'}</td>
                        <td>${item.area || '-'}</td>
                        <td>${item.zoning || '-'}</td>
                        <td>${item.land_use || '-'}</td>
                        <td class="text-start">${item.owner_type || '-'}</td>
                    </tr>`;
            });
        } else {
            ethnicTbody.innerHTML = '<tr><td colspan="11" class="text-muted py-3">暫無資料</td></tr>';
        }

        // 初始化時，直接先執行一次預設條件之篩選
        filterPriceTable();
    });
}

// 查詢按鈕對應之連動篩選處理
function filterPriceTable() {
    const filterStatus = document.getElementById('filter-status').value;
    const filterDistrict = document.getElementById('filter-district').value;
    const filterLandNumber = document.getElementById('filter-land-number').value;

    filteredTotalData = globalPriceData.filter(priceItem => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === priceItem.section_name && e.land_number === priceItem.land_number);
        
        let statusMatch = false;
        let districtMatch = false;
        let landMatch = false;

        // 1. 篩選 A. 持有或已賣 或 全部
        if (filterStatus === '全部') {
            statusMatch = true; 
        } else if (matchEthnic) {
            statusMatch = (matchEthnic.status === filterStatus);
        } else {
            statusMatch = (filterStatus === '持有'); 
        }

        // 2. 篩選 B. 地區
        if (filterDistrict === '全部') {
            districtMatch = true;
        } else if (matchEthnic) {
            districtMatch = (matchEthnic.district && matchEthnic.district.includes(filterDistrict));
        } else {
            districtMatch = false;
        }

        // 3. 篩選 C. 地號
        if (filterLandNumber === '全部') {
            landMatch = true;
        } else {
            landMatch = (priceItem.land_number === filterLandNumber);
        }

        return statusMatch && districtMatch && landMatch;
    });

    // 功能：若狀態為「全部」，則資料撈出後先依地區再依地號由小到大做升密排列
    if (filterStatus === '全部') {
        filteredTotalData.sort((a, b) => {
            const matchEthnicA = globalEthnicData.find(e => e.section_name === a.section_name && e.land_number === a.land_number);
            const matchEthnicB = globalEthnicData.find(e => e.section_name === b.section_name && e.land_number === b.land_number);
            
            const distA = matchEthnicA ? (matchEthnicA.district || '') : '';
            const distB = matchEthnicB ? (matchEthnicB.district || '') : '';
            
            if (distA !== distB) {
                return distA.localeCompare(distB, 'zh-Hant');
            }
            return a.land_number.localeCompare(b.land_number);
        });
    }

    renderPriceTablePage();
}

// 渲染分頁資料到表格中
function renderPriceTablePage() {
    const pageSize = parseInt(document.getElementById('filter-page-size').value, 10);
    const priceTbody = document.getElementById('price-history-tbody');
    priceTbody.innerHTML = '';

    const totalRecords = filteredTotalData.length;
    const totalPages = Math.ceil(totalRecords / pageSize) || 1;

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = Math.min(startIndex + pageSize, totalRecords);

    const pageData = filteredTotalData.slice(startIndex, startIndex + pageSize);

    if(pageData.length > 0) {
        pageData.forEach(item => {
            priceTbody.innerHTML += `
                <tr>
                    <td><strong>${item.record_date}</strong></td>
                    <td>${item.section_name}</td>
                    <td>${item.land_number}</td>
                    <td class="text-end text-success">${item.posted_land_value ? '$'+Number(item.posted_land_value).toLocaleString() : '-'}</td>
                    <td class="text-end text-primary">${item.declared_land_value ? '$'+Number(item.declared_land_value).toLocaleString() : '-'}</td>
                    <td>${item.land_area || '-'}</td>
                </tr>`;
        });
        document.getElementById('page-info').innerText = `顯示第 ${startIndex + 1} 到 ${endIndex} 筆，共 ${totalRecords} 筆`;
    } else {
        priceTbody.innerHTML = '<tr><td colspan="6" class="text-muted py-3">無符合篩選條件之公告地價紀錄</td></tr>';
        document.getElementById('page-info').innerText = `顯示第 0 到 0 筆，共 0 筆`;
    }

    // 建立分頁按鈕群組 (上一頁 / 下一頁 功能)
    const paginationUl = document.getElementById('pagination-ul');
    paginationUl.innerHTML = '';

    if (totalPages > 1) {
        // 上一頁
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="changeTablePage(${currentPage - 1})">上一頁</a>`;
        paginationUl.appendChild(prevLi);

        // 頁碼數
        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${currentPage === i ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="changeTablePage(${i})">${i}</a>`;
            paginationUl.appendChild(pageLi);
        }

        // 下一頁
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="changeTablePage(${currentPage + 1})">下一頁</a>`;
        paginationUl.appendChild(nextLi);
    }
}

// 觸發切換分頁頁碼之函式
function changeTablePage(page) {
    currentPage = page;
    renderPriceTablePage();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>