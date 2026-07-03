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
    
    $res_ethnic = $conn->query("SELECT DISTINCT status, district, section_name, land_number, owner_type FROM ethnic_property");
    if ($res_ethnic) {
        while ($row = $res_ethnic->fetch_assoc()) {
            $search_data['ethnic'][] = $row;
        }
    }
    
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
    <title>查詢族產歷年公告地價與現值</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 引入 SheetJS 用於純前端生成標準不警告的 .xlsx 檔案 -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: "Microsoft JhengHei", sans-serif; }
        .custom-container { width: 100%; max-width: 100%; margin: 0 auto; }
        .main-wrapper { border: none; box-shadow: none; background: transparent; }
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .text-xs { font-size: 0.85rem; font-weight: bold; color: #555; }
        
        /* 初始化隱藏純列印專用的頁碼容器 */
        .print-page-footer { display: none; }

        /* ==========================================
           列印專用樣式設定 (window.print())
           ========================================== */
        @media print {
            /* 1. 設定物理紙張邊界：上下留 2cm，左右為 0 (由 body padding 接管左右) */
            @page { 
                margin-top: 2cm !important;
                margin-bottom: 2cm !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            
            /* 2. body 設定左右留白 1.5cm，上下設為 0 避免與 @page 疊加 */
            body { 
                background-color: #fff !important; 
                margin: 0 !important;
                padding: 0 1.5cm 0 1.5cm !important; /* 順序：上(0) 右(1.5cm) 下(0) 左(1.5cm) */
                /* 初始化列印計數器 */
                counter-reset: page;
            }
            
            /* 隱藏網頁按鈕與非必要元件 */
            .no-print, #search-btn, .btn, .row.g-2, #pagination-nav, .pagination, a[href^="property_add.php"] { 
                display: none !important; 
            }
            
            /* 展開表格響應式外殼，確保跨頁不被截斷 */
            .table-responsive { 
                max-height: none !important; 
                overflow: visible !important; 
            }
            
            /* 3. 強制表格所有欄位與欄位標題上黑色邊框格線 */
            .table { 
                width: 100% !important; 
                border-collapse: collapse !important; 
                border: 2px solid #000000 !important; 
            }
            
            /* 確保若跨多頁，每頁頂部自動重印標題 */
            .table thead {
                display: table-header-group !important; 
            }

            /* 欄位標題四邊黑色格線 */
            .table thead th {
                background-color: #e9ecef !important;
                color: #000000 !important;
                border: 2px solid #000000 !important; 
                font-weight: bold !important;
                padding: 8px 4px !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            
            /* 資料欄位四邊黑色格線 */
            .table tbody td { 
                border: 1px solid #000000 !important; 
                padding: 6px 4px !important; 
                background-color: transparent !important;
            }

            /* 4. 每頁右下角自動計算、印出頁碼 */
            .print-page-footer {
                display: block !important;
                position: fixed !important;
                /* 安全放置在下邊距 2cm 的留白區域正中央，絕對不壓資料 */
                bottom: -1.3cm !important; 
                right: 3.5cm !important;
                font-size: 10pt !important;
                font-family: "Microsoft JhengHei", sans-serif !important;
                color: #000000 !important;
            }

            /* 讓瀏覽器每遇到一個 tr 就自動把頁碼計數器往上加 */
            .table tbody tr {
                counter-increment: page;
            }

            /* 利用偽元素動態印出當頁頁碼 */
            .print-page-footer::after {
                content: "第 " counter(page) " 頁";
            }
        }
    </style>
</head>
<body>

<div>    
    <div class="main-wrapper">
        <!-- 標題功能列 -->
        <div class="bg-transparent text-dark p-3 d-flex justify-content-between align-items-center rounded-top flex-wrap gap-2">
            <h4 class="mb-0 fw-bold">📊查詢歷年公告地價與現值</h4>
            <div class="d-flex gap-1 flex-wrap">
                <button type="button" class="btn btn-sm btn-success fw-bold text-white" onclick="exportToExcel()">📊 匯出 EXCEL</button>
                <button type="button" class="btn btn-sm btn-info fw-bold text-white" onclick="exportToWord()">📝 匯出 WORD</button>
                <button type="button" class="btn btn-sm btn-secondary fw-bold" onclick="window.print()">🖨️ 列印表單</button>
                <a href="property_add.php" class="btn btn-sm btn-outline-dark fw-bold no-print">➕ 切至新增族產地價</a>
            </div>
        </div>
  
        <div class="p-3 rounded-bottom">
            <div class="row g-2 mb-3 bg-light p-2 rounded align-items-end no-print">
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
                <table class="table table-bordered table-striped table-hover mb-0 align-middle text-center" style="font-size:0.9rem; border: 1px solid #dee2e6;">
                    <thead class="table-secondary sticky-top">
                        <tr>
                            <th>編號</th><th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th><th>持分</th><th>價值(元)</th>
                        </tr>
                    </thead>
                    <tbody id="price-history-tbody">
                        <tr><td colspan="9" class="text-muted py-3">數據載入中...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- 分頁功能 -->
            <div class="d-flex justify-content-between align-items-center mt-3 no-print">
                <div id="page-info" class="text-muted style-xs">顯示第 0 到 0 筆，共 0 筆</div>
                <nav id="pagination-nav">
                    <ul class="pagination pagination-sm mb-0" id="pagination-ul"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- 列印專用的頁碼獨立容器（固定在頁尾右側留白範圍內） -->
<div class="print-page-footer"></div>

<script>
let globalPriceData = [];
let globalEthnicData = [];
let currentPage = 1;
let filteredTotalData = [];

document.addEventListener("DOMContentLoaded", function() {
    loadSearchData();            
    fetchFilterOptions();        

    document.getElementById('search-btn').addEventListener('click', function() {
        currentPage = 1; 
        filterPriceTable();
    });
});

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

function loadSearchData() {
    fetch('property_search.php?action=get_search_data')
    .then(response => response.json())
    .then(data => {
        globalPriceData = data.price || [];
        globalEthnicData = data.ethnic || [];
        filterPriceTable(); 
    });
}

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

        if (filterStatus === '全部') {
            statusMatch = true; 
        } else if (matchEthnic) {
            statusMatch = (matchEthnic.status === filterStatus);
        } else {
            statusMatch = false; 
        }

        if (filterDistrict === '全部') {
            districtMatch = true;
        } else if (matchEthnic) {
            districtMatch = (matchEthnic.district && matchEthnic.district.includes(filterDistrict));
        } else {
            districtMatch = false;
        }

        if (filterLandNumber === '全部') {
            landMatch = true;
        } else {
            landMatch = (priceItem.land_number === filterLandNumber);
        }

        if (filterRecordDate === '全部') {
            dateMatch = true;
        } else {
            dateMatch = (priceItem.record_date === filterRecordDate);
        }

        return statusMatch && districtMatch && landMatch && dateMatch;
    });

    filteredTotalData.sort((a, b) => {
        const matchEthnicA = globalEthnicData.find(e => e.section_name === a.section_name && e.land_number === a.land_number);
        const matchEthnicB = globalEthnicData.find(e => e.section_name === b.section_name && e.land_number === b.land_number);
        
        const distA = matchEthnicA ? (matchEthnicA.district || '') : '';
        const distB = matchEthnicB ? (matchEthnicB.district || '') : '';
        
        if (distA !== distB) {
            return distA.localeCompare(distB, 'zh-Hant');
        }
        return a.land_number.localeCompare(b.land_number, undefined, {numeric: true, sensitivity: 'base'});
    });

    let totalValueSum = 0;
    filteredTotalData.forEach(item => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        
        let holdingValue = 1; 
        
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

    let roundedTotalValue = Math.round(totalValueSum);
    document.getElementById('search-summary-text').innerText = `查詢A.狀態"${filterStatus}"與B.地區"${filterDistrict}"與C.地號"${filterLandNumber}"條件,在D.公告年月"${filterRecordDate}",共 ${filteredTotalData.length.toLocaleString()} 筆,其總價值：${roundedTotalValue.toLocaleString()}(元)`;
    renderPriceTablePage();
}

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
        pageData.forEach((item, index) => {
            let rowNumber = startIndex + index + 1;

            const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
            let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
            
            let holdingText = "-";
            let holdingValue = 1; 
            
            let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
            if (match && match[1]) {
                holdingText = match[1] + "%";
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
            let calculatedValue = (postedLandValue * landArea * holdingValue).toFixed(2);

            priceTbody.innerHTML += `
                <tr>
                    <td>${rowNumber}</td>
                    <td><strong>${item.record_date}</strong></td>
                    <td>${item.section_name}</td>
                    <td>${item.land_number}</td>
                    <td class="text-end text-success">${item.posted_land_value ? '$'+Number(item.posted_land_value).toLocaleString() : '-'}</td>
                    <td class="text-end text-primary">${item.declared_land_value ? '$'+Number(item.declared_land_value).toLocaleString() : '-'}</td>
                    <td>${item.land_area || '-'}</td>
                    <td>${holdingText}</td>
                    <td class="text-end text-dark fw-bold">${holdingText !== '-' ? '$'+Number(calculatedValue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                </tr>`;
        });
        document.getElementById('page-info').innerText = `顯示第 ${startIndex + 1} 到 ${endIndex} 筆，共 ${totalRecords} 筆`;
    } else {
        priceTbody.innerHTML = '<tr><td colspan="9" class="text-muted py-3">無符合篩選條件之公告地價紀錄</td></tr>';
        document.getElementById('page-info').innerText = `顯示第 0 到 0 筆，共 0 筆`;
    }

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

function changeTablePage(page) {
    currentPage = page;
    renderPriceTablePage();
}

function buildExportHtmlTable() {
    let summaryText = document.getElementById('search-summary-text').innerText;
    let html = `<p style="font-size:14px; font-weight:bold;">${summaryText}</p>`;
    html += `<table border="1" style="border-collapse:collapse; text-align:center; font-family:Microsoft JhengHei;">
                <thead style="background-color:#e9ecef;">
                    <tr>
                        <th>編號</th><th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th><th>持分</th><th>價值(元)</th>
                    </tr>
                </thead>
                <tbody>`;
                
    if (filteredTotalData.length === 0) {
        html += `<tr><td colspan="9">無符合篩選條件之公告地價紀錄</td></tr>`;
    } else {
        filteredTotalData.forEach((item, index) => {
            const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
            let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
            let holdingText = "-";
            let holdingValue = 1;
            
            let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
            if (match && match[1]) {
                holdingText = match[1] + "%";
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
            let calculatedValue = (postedLandValue * landArea * holdingValue).toFixed(2);
            
            html += `<tr>
                        <td>${index + 1}</td>
                        <td>${item.record_date}</td>
                        <td>${item.section_name}</td>
                        <td>${item.land_number}</td>
                        <td style="text-align:right;">${item.posted_land_value ? Number(item.posted_land_value).toLocaleString() : '-'}</td>
                        <td style="text-align:right;">${item.declared_land_value ? Number(item.declared_land_value).toLocaleString() : '-'}</td>
                        <td>${item.land_area || '-'}</td>
                        <td>${holdingText}</td>
                        <td style="text-align:right; font-weight:bold;">${holdingText !== '-' ? Number(calculatedValue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                     </tr>`;
        });
    }
    html += `</tbody></table>`;
    return html;
}

function exportToExcel() {
    let summaryText = document.getElementById('search-summary-text').innerText;
    
    let dataRows = [
        [summaryText], 
        [],            
        ["編號", "公告年月", "段小段", "地號", "公告土地現值(元)", "公告地價(元)", "面積(㎡)", "持分", "價值(元)"]
    ];
    
    filteredTotalData.forEach((item, index) => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        let holdingText = "-";
        let holdingValue = 1;
        
        let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
        if (match && match[1]) {
            holdingText = match[1] + "%";
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
        let calculatedValue = holdingText !== '-' ? parseFloat((postedLandValue * landArea * holdingValue).toFixed(2)) : null;
        
        dataRows.push([
            index + 1,
            item.record_date,
            item.section_name,
            item.land_number,
            item.posted_land_value ? parseFloat(item.posted_land_value) : null,
            item.declared_land_value ? parseFloat(item.declared_land_value) : null,
            item.land_area ? parseFloat(item.land_area) : null,
            holdingText,
            calculatedValue
        ]);
    });
    
    let wb = XLSX.utils.book_new();
    let ws = XLSX.utils.aoa_to_sheet(dataRows);
    
    XLSX.utils.book_append_sheet(wb, ws, "查詢結果");
    XLSX.writeFile(wb, "歷年公告地價查詢結果_" + new Date().toISOString().slice(0,10) + ".xlsx");
}

function exportToWord() {
    const tableHtml = buildExportHtmlTable();
    const template = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="utf-8" /></head>
        <body>${tableHtml}</body>
        </html>`;
        
    const blob = new Blob([template], { type: "application/msword;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "歷年公告地價查詢結果_" + new Date().toISOString().slice(0,10) + ".doc";
    link.click();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>