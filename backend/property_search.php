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
// 2. 處理 AJAX 請求 (專供查詢頁面使用)
// ==========================================

// 獲取篩選器所需的不重複地段與地號選項
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_filter_suggestions') {
    header('Content-Type: application/json');
    $field = $_GET['field'] ?? '';
    $allowed_fields = ['district', 'land_number', 'record_date'];
    
    if (in_array($field, $allowed_fields)) {
        $suggestions = [];
        if ($field === 'record_date') {
            $sql = "SELECT DISTINCT `record_date` FROM land_price WHERE `record_date` IS NOT NULL AND `record_date` != '' ORDER BY `record_date` DESC";
        } else {
            $sql = "SELECT DISTINCT `$field` FROM ethnic_property WHERE `$field` IS NOT NULL AND `$field` != '' ORDER BY `$field` ASC";
        }
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

// 獲取查詢所需的完整地價與族產不重複對照檔
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_search_data') {
    header('Content-Type: application/json');
    $search_data = [];
    
    // 修正點：補上完整的 SELECT 欄位與 FROM 資料表名稱
    $res_ethnic = $conn->query("SELECT DISTINCT status, district, section_name, land_number, owner_type FROM ethnic_property");
    if ($res_ethnic) {
        while ($row = $res_ethnic->fetch_assoc()) {
            $search_data['ethnic'][] = $row;
        }
    }
    
    // 獲取歷年公告地價清單
    $res_price = $conn->query("SELECT DISTINCT record_date, section_name, land_number, posted_land_value, declared_land_value, land_area FROM land_price ORDER BY record_date DESC");
    if ($res_price) {
        while ($row = $res_price->fetch_assoc()) {
            $search_data['price'][] = $row;
        }
    }
    
    echo json_encode($search_data);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>歷年公告地價查詢清單</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: "Microsoft JhengHei", sans-serif; }
        .custom-container { max-width: 95%; margin: 0 auto; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .text-xs { font-size: 0.85rem; font-weight: bold; color: #555; }
    </style>
</head>
<body>

<div class="container-fluid custom-container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 歷年公告地價查詢系統</h2>
        <a href="property_add.php" class="btn btn-outline-success">➕ 切換至族產地價登錄頁面</a>
    </div>

    <!-- 📊 歷年公告地價清單篩選與展示區 -->
    <div class="card">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">📊 歷年公告地價清單 (land_price 不重複)</h5>
            <button class="btn btn-sm btn-outline-light" onclick="loadSearchData()">🔄 刷新數據</button>
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
                    <label class="form-label text-xs">D.公告年月</label>
                    <select id="filter-record-date" class="form-select form-select-sm">
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
                <div class="col-md-2">
                    <button type="button" id="search-btn" class="btn btn-sm btn-primary w-100">🔍 查詢資料</button>
                </div>
            </div>

            <!-- 查詢結果表單上面的動態統計文字 -->
            <div class="alert alert-info py-2 px-3 mb-3 fw-bold" id="search-summary-text" style="font-size: 0.95rem;">
                查詢A.狀態"?"與B.地區"?"與C.地號"?"條件，在"D.公告年月"其總價值(元)：0 
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle text-center" style="font-size:0.9rem;">
                    <thead class="table-secondary sticky-top">
                        <tr>
                            <th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th><th>持分</th><th>價值(元)</th>
                        </tr>
                    </thead>
                    <tbody id="price-history-tbody">
                        <tr><td colspan="8" class="text-muted py-3">數據載入中...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- 分頁功能 -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="page-info" class="text-muted style-xs">顯示第 0 到 0 筆，共 0 筆</div>
                <nav id="pagination-nav">
                    <ul class="pagination pagination-sm mb-0" id="pagination-ul"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<script>
let globalPriceData = [];
let globalEthnicData = [];
let currentPage = 1;
let filteredTotalData = [];

document.addEventListener("DOMContentLoaded", function() {
    loadSearchData();            // 載入核心對照數據
    fetchFilterOptions();        // 獲取下拉選單選項

    // 監聽「查詢」按鈕
    document.getElementById('search-btn').addEventListener('click', function() {
        currentPage = 1; 
        filterPriceTable();
    });
});

// 撈取篩選器的不重複選項
function fetchFilterOptions() {
    ['district', 'land_number', 'record_date'].forEach(field => {
        fetch(`property_search.php?action=get_filter_suggestions&field=${field}`)
        .then(res => res.json())
        .then(options => {
            const selectId = field === 'district' ? 'filter-district' : (field === 'land_number' ? 'filter-land-number' : 'filter-record-date');
            updateFilterOptions(selectId, options);
        });
    });
}

// 更新下拉選單
function updateFilterOptions(selectId, options) {
    const select = document.getElementById(selectId);
    if(!select) return;
    select.innerHTML = '<option value="全部">全部</option>';
    options.forEach(val => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.innerText = val;
        select.appendChild(opt);
    });
}

// 撈取核心對照數據
function loadSearchData() {
    fetch('property_search.php?action=get_search_data')
    .then(response => response.json())
    .then(data => {
        globalPriceData = data.price || [];
        globalEthnicData = data.ethnic || [];
        filterPriceTable(); // 預設執行首波篩選 (持有)
    });
}

// 連動篩選處理
function filterPriceTable() {
    const filterStatus = document.getElementById('filter-status').value;
    const filterDistrict = document.getElementById('filter-district').value;
    const filterLandNumber = document.getElementById('filter-land-number').value;
    const filterRecordDate = document.getElementById('filter-record-date').value;

    filteredTotalData = globalPriceData.filter(priceItem => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === priceItem.section_name && e.land_number === priceItem.land_number);
        
        let statusMatch = false;
        let districtMatch = false;
        let landMatch = false;
        let dateMatch = false;

        // A. 持有或已賣：選擇「全部」時，兩個資料都要完整呈現出來
        if (filterStatus === '全部') {
            statusMatch = true; 
        } else if (matchEthnic) {
            statusMatch = (matchEthnic.status === filterStatus);
        } else {
            statusMatch = false; 
        }

        // B. 地區：選擇「全部」時，兩個資料都要呈現出來
        if (filterDistrict === '全部') {
            districtMatch = true;
        } else if (matchEthnic) {
            districtMatch = (matchEthnic.district && matchEthnic.district.includes(filterDistrict));
        } else {
            districtMatch = false;
        }

        // C. 地號
        if (filterLandNumber === '全部') {
            landMatch = true;
        } else {
            landMatch = (priceItem.land_number === filterLandNumber);
        }

        // D. 公告年月
        if (filterRecordDate === '全部') {
            dateMatch = true;
        } else {
            dateMatch = (priceItem.record_date === filterRecordDate);
        }

        return statusMatch && districtMatch && landMatch && dateMatch;
    });

    // 排序邏輯：按「地區」分組排序，同地區內再依「地號」升冪(由小到大)排列
    filteredTotalData.sort((a, b) => {
        const matchEthnicA = globalEthnicData.find(e => e.section_name === a.section_name && e.land_number === a.land_number);
        const matchEthnicB = globalEthnicData.find(e => e.section_name === b.section_name && e.land_number === b.land_number);
        
        const distA = matchEthnicA ? (matchEthnicA.district || '') : '';
        const distB = matchEthnicB ? (matchEthnicB.district || '') : '';
        
        // 先比對地區
        if (distA !== distB) {
            return distA.localeCompare(distB, 'zh-Hant');
        }
        // 同地區內，再依地號升冪排列
        return a.land_number.localeCompare(b.land_number, undefined, {numeric: true, sensitivity: 'base'});
    });

    // 計算查詢結果的「總價值(元)」，並更新上方文字
    let totalValueSum = 0;
    filteredTotalData.forEach(item => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        
        let holdingValue = 1; // 預設持分為1
        
        let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
        if (match && match[1]) {
            let numMatch = match[1].match(/(\d+)分之(\d+)/);
            if (numMatch) {
                holdingValue = parseFloat(numMatch[1]) / parseFloat(numMatch[2]);
            } else {
                let pureNum = parseFloat(match[1]);
                if (!isNaN(pureNum)) {
                    holdingValue = pureNum / 100;
                }
            }
        }
        
        let postedLandValue = item.posted_land_value ? parseFloat(item.posted_land_value) : 0;
        let landArea = item.land_area ? parseFloat(item.land_area) : 0;
        let rowValue = postedLandValue * landArea * holdingValue;
        totalValueSum += rowValue;
    });

    // 四捨五入算整數
    let roundedTotalValue = Math.round(totalValueSum);
    document.getElementById('search-summary-text').innerText = `查詢A.狀態"${filterStatus}"與B.地區"${filterDistrict}"與C.地號"${filterLandNumber}"條件，在D.公告年月"${filterRecordDate}"其總價值(元)：${roundedTotalValue.toLocaleString()}`;

    renderPriceTablePage();
}

// 渲染表格與分頁
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
            // 找出對應的 ethnic_property 以獲取 owner_type
            const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
            let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
            
            let holdingText = "-";
            let holdingValue = 1; 
            
            // 正則抓取 "祭祀公業:" 後面文字直到 "%" 結束
            let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
            if (match && match[1]) {
                holdingText = match[1] + "%";
                // 嘗試解析分數 (如：1分之1) 或 百分比數字
                let numMatch = match[1].match(/(\d+)分之(\d+)/);
                if (numMatch) {
                    holdingValue = parseFloat(numMatch[1]) / parseFloat(numMatch[2]);
                } else {
                    let pureNum = parseFloat(match[1]);
                    if (!isNaN(pureNum)) {
                        holdingValue = pureNum / 100;
                    }
                }
            }
            
            // 計算價值(元) = 公告土地現值 * 面積 * 持分，取小數到第二位
            let postedLandValue = item.posted_land_value ? parseFloat(item.posted_land_value) : 0;
            let landArea = item.land_area ? parseFloat(item.land_area) : 0;
            let calculatedValue = (postedLandValue * landArea * holdingValue).toFixed(2);

            priceTbody.innerHTML += `
                <tr>
                    <td><strong>${item.record_date}</strong></td>
                    <td>${item.section_name}</td>
                    <td>${item.land_number}</td>
                    <td class="text-end text-success">${item.posted_land_value ? '$'+Number(item.posted_land_value).toLocaleString() : '-'}</td>
                    <td class="text-end text-primary">${item.declared_land_value ? '$'+Number(item.declared_land_value).toLocaleString() : '-'}</td>
                    <td>${item.land_area || '-'}</td>
                    <td>${holdingText}</td>
                    <td class="text-end text-danger fw-bold">${holdingText !== '-' ? '$'+Number(calculatedValue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                </tr>`;
        });
        document.getElementById('page-info').innerText = `顯示第 ${startIndex + 1} 到 ${endIndex} 筆，共 ${totalRecords} 筆`;
    } else {
        priceTbody.innerHTML = '<tr><td colspan="8" class="text-muted py-3">無符合篩選條件之公告地價紀錄</td></tr>';
        document.getElementById('page-info').innerText = `顯示第 0 到 0 筆，共 0 筆`;
    }

    // 分頁導覽
    const paginationUl = document.getElementById('pagination-ul');
    paginationUl.innerHTML = '';

    if (totalPages > 1) {
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="changeTablePage(${currentPage - 1})">上一頁</a>`;
        paginationUl.appendChild(prevLi);

        for (let i = 1; i <= totalPages; i++) {
            const pageLi = document.createElement('li');
            pageLi.className = `page-item ${currentPage === i ? 'active' : ''}`;
            pageLi.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="changeTablePage(${i})">${i}</a>`;
            paginationUl.appendChild(pageLi);
        }

        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="javascript:void(0);" onclick="changeTablePage(${currentPage + 1})">下一頁</a>`;
        paginationUl.appendChild(nextLi);
    }
}

// 變更分頁頁碼
function changeTablePage(page) {
    currentPage = page;
    renderPriceTablePage();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>