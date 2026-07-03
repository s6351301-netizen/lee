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
// 2. 處理 AJAX 請求 (篩選選單與主數據)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_filter_suggestions') {
    header('Content-Type: application/json');
    $field = $_GET['field'] ?? '';
    $allowed_fields = ['district', 'land_number', 'record_date', 'land_use'];
    
    if (in_array($field, $allowed_fields)) {
        $suggestions = [];
        if ($field === 'record_date') {
            $sql = "SELECT DISTINCT `record_date` FROM land_price WHERE `record_date` IS NOT NULL AND `record_date` != '' ORDER BY `record_date` DESC";
        } elseif ($field === 'land_use') {
            $sql = "SELECT DISTINCT `land_use` FROM ethnic_property WHERE `land_use` IS NOT NULL AND `land_use` != '' ORDER BY `land_use` ASC";
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

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_search_data') {
    header('Content-Type: application/json');
    $search_data = [];
    
    $res_ethnic = $conn->query("SELECT DISTINCT status, district, section_name, land_number, owner_type, land_use FROM ethnic_property");
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
    <title>查詢族產歷年公告地價與現值 (複選框多選版)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
    <style>
        body { background-color: #f4f7f6; font-family: "Microsoft JhengHei", sans-serif; }
        .table-responsive { max-height: 500px; overflow-y: auto; }
        .text-xs { font-size: 0.85rem; font-weight: bold; color: #333; margin-bottom: 6px; display: block; border-bottom: 2px solid #ddd; padding-bottom: 3px; }
        .print-page-footer { display: none; }

        /* 複選框容器優化 */
        .checkbox-group-container { max-height: 120px; overflow-y: auto; background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 6px 10px; }
        .checkbox-inline-item { display: block; font-size: 0.85rem; margin-bottom: 2px; cursor: pointer; }
        .checkbox-inline-item input { margin-right: 6px; }

        /* 圖表全面改為單行排列 */
        .chart-box { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; width: 100%; }
        .tooltip-box { position: absolute; background: rgba(0, 0, 0, 0.85); color: #fff; padding: 8px 12px; border-radius: 4px; font-size: 13px; pointer-events: none; opacity: 0; z-index: 9999; line-height: 1.5; }
        .legend-dot { display: inline-block; width: 12px; height: 12px; margin-right: 5px; border-radius: 2px; vertical-align: middle; }
        .legend-item { display: inline-block; margin-right: 20px; font-size: 14px; margin-bottom: 5px; }
        .chart-scroll-container { width: 100%; overflow-x: auto; background: #fafafa; border-radius: 4px; padding: 10px 0; }
        
        .chart-line { fill: none; stroke-width: 3px; }
        .chart-dot { stroke-width: 2px; stroke: #fff; cursor: pointer; }

        @media print {
            @page { margin-top: 2cm !important; margin-bottom: 2cm !important; margin-left: 0 !important; margin-right: 0 !important; }
            body { background-color: #fff !important; margin: 0 !important; padding: 0 1.5cm 0 1.5cm !important; counter-reset: page; }
            .no-print, #search-btn, .btn, .row.g-2, #pagination-nav, .pagination, .chart-section { display: none !important; }
            .table-responsive { max-height: none !important; overflow: visible !important; }
            .table { width: 100% !important; border-collapse: collapse !important; border: 2px solid #000000 !important; }
            .table thead { display: table-header-group !important; }
            .table thead th { background-color: #e9ecef !important; color: #000000 !important; border: 2px solid #000000 !important; font-weight: bold !important; padding: 8px 4px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .table tbody td { border: 1px solid #000000 !important; padding: 6px 4px !important; background-color: transparent !important; }
            .print-page-footer { display: block !important; position: fixed !important; bottom: -1.3cm !important; right: 3.5cm !important; font-size: 10pt !important; font-family: "Microsoft JhengHei", sans-serif !important; color: #000000 !important; }
            .table tbody tr { counter-increment: page; }
            .print-page-footer::after { content: "第 " counter(page) " 頁"; }
        }
    </style>
</head>
<body>

<div class="container-fluid py-3">    
    <div class="main-wrapper">
        <div class="bg-transparent text-dark p-3 d-flex justify-content-between align-items-center rounded-top flex-wrap gap-2">
            <h4 class="mb-0 fw-bold">📊 查詢族產歷年公告地價與現值 (複選框多選版)</h4>
            <div class="d-flex gap-1 flex-wrap">
                <button type="button" class="btn btn-sm btn-primary fw-bold text-white" onclick="toggleChartSection()">📊 顯示/隱藏進階分析圖表</button>
                <button type="button" class="btn btn-sm btn-success fw-bold text-white" onclick="exportToExcel()">📊 匯出 EXCEL</button>
                <button type="button" class="btn btn-sm btn-info fw-bold text-white" onclick="exportToWord()">📝 匯出 WORD</button>
                <button type="button" class="btn btn-sm btn-secondary fw-bold" onclick="window.print()">🖨️ 列印表單</button>
            </div>
        </div>
  
        <!-- 複選框多選控制面板區 -->
        <div class="p-3 rounded-bottom no-print">
            <div class="row g-2 mb-3 bg-light p-3 rounded align-items-start">
                
                <!-- A. 持有狀態大類 -->
                <div class="col-md-2">
                    <span class="text-xs">A.持有或已賣</span>
                    <div class="checkbox-group-container" id="cb-container-status">
                        <label class="checkbox-inline-item"><input type="checkbox" value="全部" id="cb-status-all">全部</label>
                        <label class="checkbox-inline-item"><input type="checkbox" value="持有" class="cb-item-status" checked>持有</label>
                        <label class="checkbox-inline-item"><input type="checkbox" value="已賣" class="cb-item-status">已賣</label>
                    </div>
                </div>

                <!-- B. 地區大類 -->
                <div class="col-md-2">
                    <span class="text-xs">B.地區</span>
                    <div class="checkbox-group-container" id="cb-container-district">
                        <label class="checkbox-inline-item"><input type="checkbox" value="全部" id="cb-district-all">全部</label>
                        <div id="cb-items-district"></div>
                    </div>
                </div>

                <!-- C. 地號大類 (已由 col-md-3 縮減至 col-md-2) -->
                <div class="col-md-2">
                    <span class="text-xs">C.地號</span>
                    <div class="checkbox-group-container" id="cb-container-land-number">
                        <label class="checkbox-inline-item"><input type="checkbox" value="全部" id="cb-land-number-all">全部</label>
                        <div id="cb-items-land-number"></div>
                    </div>
                </div>

                <!-- D. 公告年月大類 -->
                <div class="col-md-2">
                    <span class="text-xs">D.公告年月</span>
                    <div class="checkbox-group-container" id="cb-container-record-date">
                        <label class="checkbox-inline-item"><input type="checkbox" value="全部" id="cb-record-date-all">全部</label>
                        <div id="cb-items-record-date"></div>
                    </div>
                </div>

                <!-- E. 使用地類別大類 -->
                <div class="col-md-2">
                    <span class="text-xs">E.使用地類別</span>
                    <div class="checkbox-group-container" id="cb-container-land-use">
                        <label class="checkbox-inline-item"><input type="checkbox" value="全部" id="cb-land-use-all" checked>全部</label>
                        <div id="cb-items-land-use"></div>
                    </div>
                </div>

                <!-- 每頁筆數與查詢送出 (已分配挪用空間，由 col-md-1 擴大至 col-md-2) -->
                <div class="col-md-2">
                    <span class="text-xs">每頁筆數</span>
                    <select id="filter-page-size" class="form-select form-select-sm mb-2">
                        <option value="50" selected>50 筆</option>
                        <option value="100">100 筆</option>
                    </select>
                    <button type="button" id="search-btn" class="btn btn-sm btn-primary w-100 fw-bold">🔍 查詢</button>
                </div>
            </div>
        </div>

        <div class="alert alert-info py-2 px-3 mb-3 fw-bold" id="search-summary-text" style="font-size: 0.95rem;">
            查詢中...
        </div>

        <!-- 資料表格 -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover mb-0 align-middle text-center" style="font-size:0.9rem; border: 1px solid #dee2e6;">
                <thead class="table-secondary sticky-top">
                    <tr>
                        <th>編號</th><th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th><th>持分</th><th>使用地類別</th><th>價值(元)</th>
                    </tr>
                </thead>
                <tbody id="price-history-tbody">
                    <tr><td colspan="10" class="text-muted py-3">數據載入中...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 no-print">
            <div id="page-info" class="text-muted style-xs">顯示第 0 到 0 筆，共 0 筆</div>
            <nav id="pagination-nav">
                <ul class="pagination pagination-sm mb-0" id="pagination-ul"></ul>
            </nav>
        </div>

        <!-- ==========================================
             D3 四大圖表區 (全數調整為單行大圖顯示)
             ========================================== -->
        <div id="chart-section" class="chart-section mt-4 d-none no-print">
            
            <!-- 圖表一 -->
            <div class="chart-box">
                <h5 class="fw-bold text-success mb-2">📊 圖表 (一) ：歷年公告土地現值對比圖 (元)</h5>
                <div id="posted-legend" class="mb-2 text-start small"></div>
                <div class="chart-scroll-container"><div id="posted-chart"></div></div>
            </div>

            <!-- 圖表二 -->
            <div class="chart-box">
                <h5 class="fw-bold text-primary mb-2">📊 圖表 (二) ：歷年公告地價對比圖 (元)</h5>
                <div id="declared-legend" class="mb-2 text-start small"></div>
                <div class="chart-scroll-container"><div id="declared-chart"></div></div>
            </div>

            <!-- 圖表三 -->
            <div class="chart-box">
                <h5 class="fw-bold text-dark mb-1">📈 圖表 (三) ：價差比率分析 (現值 / 地價倍數)</h5>
                <p class="text-muted small mb-2">※ 數值越高，代表該地號市價飆升速度遠大於持有地價稅基。</p>
                <div id="ratio-legend" class="mb-2 text-start small"></div>
                <div class="chart-scroll-container"><div id="ratio-chart"></div></div>
            </div>

            <!-- 圖表四 -->
            <div class="chart-box">
                <h5 class="fw-bold text-danger mb-1">🍰 圖表 (四) ：總量與個體差異分析 (資產大餅)</h5>
                <p class="text-muted small mb-2">※ 依據各選定地號之「最新公告價值(元)」計算權重占比。</p>
                <div class="text-center"><div id="pie-chart"></div></div>
                <div id="pie-legend" class="mt-3 text-start small" style="max-height:160px; overflow-y:auto; border-top: 1px dashed #ccc; padding-top: 10px;"></div>
            </div>

        </div>

    </div>
</div>

<div id="chart-tooltip" class="tooltip-box"></div>
<div class="print-page-footer"></div>

<script>
let globalPriceData = [];
let globalEthnicData = [];
let currentPage = 1;
let filteredTotalData = [];

document.addEventListener("DOMContentLoaded", function() {
    loadSearchData();            
    setupAllMasterCheckboxes();

    document.getElementById('search-btn').addEventListener('click', function() {
        currentPage = 1; 
        filterPriceTable();
    });
    document.getElementById('filter-page-size').addEventListener('change', function() {
        currentPage = 1;
        renderPriceTablePage();
    });
});

// 全選連動邏輯
function setupAllMasterCheckboxes() {
    const categories = ['status', 'district', 'land-number', 'record-date', 'land-use'];
    categories.forEach(cat => {
        const master = document.getElementById(`cb-${cat}-all`);
        if(!master) return;
        master.addEventListener('change', function() {
            const items = document.querySelectorAll(`.cb-item-${cat}`);
            items.forEach(cb => { cb.checked = master.checked; });
        });
    });
}

function fetchAndRenderCheckboxes() {
    const fields = [
        { name: 'district', defaultVal: '大甲區' },
        { name: 'land_number', defaultVal: '0995-0000' },
        { name: 'record_date', defaultVal: '2026-01' },
        { name: 'land_use', defaultVal: '全部' }
    ];

    fields.forEach(f => {
        fetch(`property_search_d3.php?action=get_filter_suggestions&field=${f.name}`)
        .then(res => res.json())
        .then(options => {
            const catId = f.name.replace('_', '-');
            const container = document.getElementById(`cb-items-${catId}`);
            if(!container) return;
            container.innerHTML = '';

            options.forEach(val => {
                if(val !== null && val !== undefined && val !== '') {
                    const label = document.createElement('label');
                    label.className = 'checkbox-inline-item';
                    
                    let isChecked = false;
                    if(f.defaultVal === '全部' && document.getElementById(`cb-${catId}-all`).checked) {
                        isChecked = true;
                    } else if (val.toString().trim() === f.defaultVal) {
                        isChecked = true;
                    }

                    label.innerHTML = `<input type="checkbox" value="${val}" class="cb-item-${catId}" ${isChecked ? 'checked' : ''}>${val}`;
                    container.appendChild(label);
                }
            });
        }).catch(err => console.error(`撈取複選選項[${f.name}]失敗:`, err));
    });
}

function loadSearchData() {
    fetch('property_search_d3.php?action=get_search_data')
    .then(response => response.json())
    .then(data => {
        globalPriceData = data.price || [];
        globalEthnicData = data.ethnic || [];
        
        fetchAndRenderCheckboxes();
        setTimeout(() => { filterPriceTable(); }, 400);
    }).catch(err => {
        console.error("載入主數據失敗:", err);
        document.getElementById('price-history-tbody').innerHTML = '<tr><td colspan="10" class="text-danger">資料載入失敗，請檢查資料庫連線。</td></tr>';
    });
}

function getSelectedCheckboxValues(catName) {
    const master = document.getElementById(`cb-${catName}-all`);
    if(master && master.checked) return ['全部'];
    
    const checkedItems = document.querySelectorAll(`.cb-item-${catName}:checked`);
    let vals = [];
    checkedItems.forEach(cb => vals.push(cb.value.toString().trim()));
    return vals;
}

function filterPriceTable() {
    const selectedStatuses = getSelectedCheckboxValues('status');
    const selectedDistricts = getSelectedCheckboxValues('district');
    const selectedLandNumbers = getSelectedCheckboxValues('land-number');
    const selectedRecordDates = getSelectedCheckboxValues('record-date');
    const selectedLandUses = getSelectedCheckboxValues('land-use');

    filteredTotalData = globalPriceData.filter(priceItem => {
        const sName = priceItem.section_name ? priceItem.section_name.trim() : "";
        const lNum = priceItem.land_number ? priceItem.land_number.trim() : "";

        const matchEthnic = globalEthnicData.find(e => 
            (e.section_name ? e.section_name.trim() : "") === sName && 
            (e.land_number ? e.land_number.trim() : "") === lNum
        );
        
        let statusMatch = false; let districtMatch = false; let landMatch = false; let dateMatch = false; let useMatch = false;

        if (selectedStatuses.includes('全部') || selectedStatuses.length === 0) statusMatch = true; 
        else if (matchEthnic) statusMatch = selectedStatuses.includes(matchEthnic.status);

        if (selectedDistricts.includes('全部') || selectedDistricts.length === 0) districtMatch = true;
        else if (matchEthnic && matchEthnic.district) {
            districtMatch = selectedDistricts.some(d => matchEthnic.district.includes(d));
        }

        if (selectedLandNumbers.includes('全部') || selectedLandNumbers.length === 0) landMatch = true;
        else landMatch = selectedLandNumbers.includes(lNum);

        if (selectedRecordDates.includes('全部') || selectedRecordDates.length === 0) dateMatch = true;
        else dateMatch = (priceItem.record_date && selectedRecordDates.includes(priceItem.record_date.toString().trim()));

        if (selectedLandUses.includes('全部') || selectedLandUses.length === 0) useMatch = true;
        else if (matchEthnic) useMatch = selectedLandUses.includes(matchEthnic.land_use);

        return statusMatch && districtMatch && landMatch && dateMatch && useMatch;
    });

    filteredTotalData.sort((a, b) => {
        const matchEthnicA = globalEthnicData.find(e => e.section_name === a.section_name && e.land_number === a.land_number);
        const matchEthnicB = globalEthnicData.find(e => e.section_name === b.section_name && e.land_number === b.land_number);
        const distA = matchEthnicA ? (matchEthnicA.district || '') : '';
        const distB = matchEthnicB ? (matchEthnicB.district || '') : '';
        if (distA !== distB) return distA.localeCompare(distB, 'zh-Hant');
        return (a.land_number || "").localeCompare((b.land_number || ""), undefined, {numeric: true, sensitivity: 'base'});
    });

    let totalValueSum = 0;
    filteredTotalData.forEach(item => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        let holdingValue = 1; 
        let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
        if (match && match[1]) {
            let numMatch = match[1].match(/(\d+)分之(\d+)/);
            if (numMatch) holdingValue = parseFloat(numMatch[1]) / parseFloat(numMatch[2]);
            else { let pureNum = parseFloat(match[1]); if (!isNaN(pureNum)) holdingValue = pureNum / 100; }
        }
        let postedLandValue = item.posted_land_value ? parseFloat(item.posted_land_value) : 0;
        let landArea = item.land_area ? parseFloat(item.land_area) : 0;
        totalValueSum += (postedLandValue * landArea * holdingValue);
    });

    let roundedTotalValue = Math.round(totalValueSum);
    document.getElementById('search-summary-text').innerText = `[多選查詢結果] 當前符合條件歷史紀錄共 ${filteredTotalData.length.toLocaleString()} 筆，估算持分總價值為：${roundedTotalValue.toLocaleString()} 元`;
    
    renderPriceTablePage();
    if (!document.getElementById('chart-section').classList.contains('d-none')) {
        renderAllD3Charts();
    }
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
            let landUseStr = matchEthnic ? matchEthnic.land_use : "-";
            
            let holdingText = "-"; let holdingValue = 1; 
            let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
            if (match && match[1]) {
                holdingText = match[1] + "%";
                let numMatch = match[1].match(/(\d+)分之(\d+)/);
                if (numMatch) holdingValue = parseFloat(numMatch[1]) / parseFloat(numMatch[2]);
                else { let pureNum = parseFloat(match[1]); if (!isNaN(pureNum)) holdingValue = pureNum / 100; }
            }
            let postedLandValue = item.posted_land_value ? parseFloat(item.posted_land_value) : 0;
            let landArea = item.land_area ? parseFloat(item.land_area) : 0;
            let calculatedValue = (postedLandValue * landArea * holdingValue).toFixed(2);

            priceTbody.innerHTML += `
                <tr>
                    <td>${rowNumber}</td>
                    <td><strong>${item.record_date || '-'}</strong></td>
                    <td>${item.section_name || '-'}</td>
                    <td>${item.land_number || '-'}</td>
                    <td class="text-end text-success">${item.posted_land_value ? '$'+Number(item.posted_land_value).toLocaleString() : '-'}</td>
                    <td class="text-end text-primary">${item.declared_land_value ? '$'+Number(item.declared_land_value).toLocaleString() : '-'}</td>
                    <td>${item.land_area || '-'}</td>
                    <td>${holdingText}</td>
                    <td><strong>${landUseStr}</strong></td>
                    <td class="text-end text-dark fw-bold">${holdingText !== '-' ? '$'+Number(calculatedValue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                </tr>`;
        });
        document.getElementById('page-info').innerText = `顯示第 ${startIndex + 1} 到 ${endIndex} 筆，共 ${totalRecords} 筆`;
    } else {
        priceTbody.innerHTML = '<tr><td colspan="10" class="text-muted py-3">無符合篩選條件之公告地價紀錄</td></tr>';
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

function toggleChartSection() {
    const chartSec = document.getElementById('chart-section');
    if (chartSec.classList.contains('d-none')) {
        chartSec.classList.remove('d-none');
        renderAllD3Charts();
        chartSec.scrollIntoView({ behavior: 'smooth' });
    } else {
        chartSec.classList.add('d-none');
    }
}

// ==========================================================
// D3 繪圖引擎核心邏輯 (一排一圖排版)
// ==========================================================
function renderAllD3Charts() {
    ["#posted-chart", "#posted-legend", "#declared-chart", "#declared-legend", "#ratio-chart", "#ratio-legend", "#pie-chart", "#pie-legend"].forEach(id => {
        document.querySelector(id).innerHTML = "";
    });

    if (!filteredTotalData || filteredTotalData.length === 0) return;

    const dataset = filteredTotalData.map(item => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        let landUseStr = matchEthnic ? (matchEthnic.land_use || "未分類") : "未分類";
        let holdingValue = 1; 
        let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
        if (match && match[1]) {
            let numMatch = match[1].match(/(\d+)分之(\d+)/);
            if (numMatch) holdingValue = parseFloat(numMatch[1]) / parseFloat(numMatch[2]);
            else { let pureNum = parseFloat(match[1]); if (!isNaN(pureNum)) holdingValue = pureNum / 100; }
        }

        let pVal = item.posted_land_value ? parseFloat(item.posted_land_value) : 0;
        let dVal = item.declared_land_value ? parseFloat(item.declared_land_value) : 0;
        let area = item.land_area ? parseFloat(item.land_area) : 0;
        
        return {
            record_date: item.record_date ? item.record_date.toString().trim() : "未知時間",
            land_number: item.land_number ? item.land_number.toString().trim() : "未知地號",
            land_use: landUseStr,
            posted_val: pVal,
            declared_val: dVal,
            ratio: dVal > 0 ? parseFloat((pVal / dVal).toFixed(2)) : 0,
            asset_value: Math.round(pVal * area * holdingValue)
        };
    });

    dataset.sort((a, b) => a.record_date.localeCompare(b.record_date, undefined, {numeric: true}));

    const allDates = Array.from(new Set(dataset.map(d => d.record_date)));
    const allLands = Array.from(new Set(dataset.map(d => d.land_number)));

    const margin = { top: 25, right: 40, bottom: 40, left: 75 };
    const width = Math.max(900, allDates.length * 110) - margin.left - margin.right;
    const height = 320 - margin.top - margin.bottom;

    const colorScale = d3.scaleOrdinal(d3.schemeCategory10).domain(allLands);
    const tooltip = d3.select("#chart-tooltip");

    generateGroupedBarChart("#posted-chart", "#posted-legend", "posted_val", "公告土地現值");
    generateGroupedBarChart("#declared-chart", "#declared-legend", "declared_val", "公告地價");

    function generateGroupedBarChart(containerId, legendId, valueField, titleName) {
        const svg = d3.select(containerId).append("svg")
            .attr("width", width + margin.left + margin.right).attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", `translate(${margin.left}, ${margin.top})`);

        const x0Scale = d3.scaleBand().domain(allDates).range([0, width]).paddingInner(0.25).paddingOuter(0.2);
        const x1Scale = d3.scaleBand().domain(allLands).range([0, x0Scale.bandwidth()]).paddingInner(0.05);
        const yScale = d3.scaleLinear().domain([0, d3.max(dataset, d => d[valueField]) * 1.1 || 1000]).range([height, 0]);

        svg.append("g").attr("transform", `translate(0, ${height})`).call(d3.axisBottom(x0Scale))
           .selectAll("text").style("font-size", "11px").style("font-weight", "bold");
        svg.append("g").call(d3.axisLeft(yScale).tickFormat(d3.format(",")));

        const timeGroups = svg.selectAll(".time-g").data(allDates).enter().append("g")
            .attr("transform", d => `translate(${x0Scale(d)}, 0)`);

        timeGroups.selectAll("rect").data(date => dataset.filter(d => d.record_date === date)).enter().append("rect")
            .attr("x", d => x1Scale(d.land_number)).attr("y", d => yScale(d[valueField]))
            .attr("width", x1Scale.bandwidth()).attr("height", d => height - yScale(d[valueField]))
            .attr("fill", d => colorScale(d.land_number)).attr("stroke", "#ffffff").attr("stroke-width", 1)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("fill", d3.rgb(colorScale(d.land_number)).darker(0.5));
                tooltip.style("opacity", 1).html(`<strong>地號：</strong>${d.land_number}<br><strong>使用地類別：</strong>${d.land_use}<br><strong>公告年月：</strong>${d.record_date}<br><strong>${titleName}：</strong>$${d[valueField].toLocaleString()} 元`);
            })
            .on("mousemove", event => tooltip.style("left", (event.pageX + 15) + "px").style("top", (event.pageY - 25) + "px"))
            .on("mouseout", function(event, d) { d3.select(this).attr("fill", colorScale(d.land_number)); tooltip.style("opacity", 0); });

        renderCommonLegend(legendId);
    }

    (function generateRatioLineChart() {
        const svg = d3.select("#ratio-chart").append("svg")
            .attr("width", width + margin.left + margin.right).attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", `translate(${margin.left}, ${margin.top})`);

        const xScale = d3.scalePoint().domain(allDates).range([30, width - 30]);
        const yScale = d3.scaleLinear().domain([0, d3.max(dataset, d => d.ratio) * 1.1 || 5]).range([height, 0]);

        svg.append("g").attr("transform", `translate(0, ${height})`).call(d3.axisBottom(xScale))
           .selectAll("text").style("font-size", "11px").style("font-weight", "bold");
        svg.append("g").call(d3.axisLeft(yScale).tickFormat(d => d + "倍"));

        allLands.forEach(landNum => {
            const landData = dataset.filter(d => d.land_number === landNum);
            const lineGenerator = d3.line().x(d => xScale(d.record_date)).y(d => yScale(d.ratio));

            svg.append("path").datum(landData).attr("class", "chart-line")
               .attr("d", lineGenerator).attr("stroke", colorScale(landNum));

            svg.selectAll(`.dot-${landNum}`).data(landData).enter().append("circle")
               .attr("class", "chart-dot")
               .attr("cx", d => xScale(d.record_date)).attr("cy", d => yScale(d.ratio)).attr("r", 5)
               .attr("fill", colorScale(landNum))
               .on("mouseover", function(event, d) {
                    d3.select(this).attr("r", 7);
                    tooltip.style("opacity", 1).html(`<strong>地號：</strong>${d.land_number}<br><strong>使用地類別：</strong>${d.land_use}<br><strong>公告年月：</strong>${d.record_date}<br><strong>土地現值：</strong>$${d.posted_val.toLocaleString()} 元<br><strong>公告地價：</strong>$${d.declared_val.toLocaleString()} 元<br><span class="text-warning"><strong>現值為地價的：</strong>${d.ratio} 倍</span>`);
               })
               .on("mousemove", event => tooltip.style("left", (event.pageX + 15) + "px").style("top", (event.pageY - 25) + "px"))
               .on("mouseout", function() { d3.select(this).attr("r", 5); tooltip.style("opacity", 0); });
        });

        renderCommonLegend("#ratio-legend");
    })();

    (function generateAssetPieChart() {
        const latestAssetMap = {};
        const landUseMap = {};
        
        dataset.forEach(d => {
            latestAssetMap[d.land_number] = d.asset_value; 
            landUseMap[d.land_number] = d.land_use;
        });

        const pieData = Object.keys(latestAssetMap).map(landNum => ({
            land_number: landNum,
            land_use: landUseMap[landNum],
            value: latestAssetMap[landNum]
        })).filter(d => d.value > 0);

        if (pieData.length === 0) {
            document.querySelector("#pie-chart").innerHTML = "<div class='text-muted py-5 small'>無有效資產價值數據可繪製大餅圖</div>";
            return;
        }

        const pieWidth = 360, pieHeight = 360, radius = Math.min(pieWidth, pieHeight) / 2;
        const svg = d3.select("#pie-chart").append("svg")
            .attr("width", pieWidth).attr("height", pieHeight)
            .append("g").attr("transform", `translate(${pieWidth / 2}, ${pieHeight / 2})`);

        const pie = d3.pie().value(d => d.value).sort(null);
        const arc = d3.arc().innerRadius(radius * 0.4).outerRadius(radius * 0.9);
        const arcHover = d3.arc().innerRadius(radius * 0.4).outerRadius(radius * 0.98);

        const totalPieSum = d3.sum(pieData, d => d.value);
        const arcs = svg.selectAll(".arc").data(pie(pieData)).enter().append("g").attr("class", "arc");

        arcs.append("path").attr("d", arc).attr("fill", d => colorScale(d.data.land_number))
            .attr("stroke", "#fff").attr("stroke-width", 2).style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).transition().duration(100).attr("d", arcHover);
                const pct = ((d.data.value / totalPieSum) * 100).toFixed(1);
                tooltip.style("opacity", 1).html(`<strong>地號：</strong>${d.data.land_number}<br><strong>使用地類別：</strong>${d.data.land_use}<br><strong>持分估值：</strong>$${d.data.value.toLocaleString()} 元<br><span class="text-info"><strong>資產占比：</strong>${pct}%</span>`);
            })
            .on("mousemove", event => tooltip.style("left", (event.pageX + 15) + "px").style("top", (event.pageY - 25) + "px"))
            .on("mouseout", function() { d3.select(this).transition().duration(100).attr("d", arc); tooltip.style("opacity", 0); });

        const pieLegend = d3.select("#pie-legend");
        pieData.sort((a,b) => b.value - a.value);
        pieData.forEach(d => {
            const barColor = colorScale(d.land_number);
            const pct = ((d.value / totalPieSum) * 100).toFixed(1);
            pieLegend.append("span").attr("class", "legend-item").style("color", "#333")
                     .html(`<span class="legend-dot" style="background:${barColor}"></span>地號 ${d.land_number} [ <strong>${d.land_use}</strong> ]: <b>$${d.value.toLocaleString()}</b> 元 (${pct}%)`);
        });
    })();

    function renderCommonLegend(legendContainerId) {
        allLands.forEach(landNum => {
            const barColor = colorScale(landNum);
            const matchData = dataset.find(d => d.land_number === landNum);
            const useStr = matchData ? ` [ <strong>${matchData.land_use}</strong> ]` : '';
            d3.select(legendContainerId).append("span").attr("class", "legend-item").style("color", barColor)
              .html(`<span class="legend-dot" style="background:${barColor}"></span>地號:${landNum}${useStr}`);
        });
    }
}

// 報表匯出模組
function exportToExcel() {
    let summaryText = document.getElementById('search-summary-text').innerText;
    let dataRows = [[summaryText], [], ["編號", "公告年月", "段小段", "地號", "公告土地現值(元)", "公告地價(元)", "面積(㎡)", "持分", "使用地類別", "價值(元)"]];
    filteredTotalData.forEach((item, index) => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        let landUseStr = matchEthnic ? (matchEthnic.land_use || "-") : "-";
        let holdingText = "-"; let holdingValue = 1;
        let match = ownerTypeStr.match(/祭祀公業:(.*?)%/);
        if (match && match[1]) {
            holdingText = match[1] + "%";
            let numMatch = match[1].match(/(\d+)分之(\d+)/);
            if (numMatch) holdingValue = parseFloat(numMatch[1]) / parseFloat(numMatch[2]);
            else { let pureNum = parseFloat(match[1]); if (!isNaN(pureNum)) holdingValue = pureNum / 100; }
        }
        let postedLandValue = item.posted_land_value ? parseFloat(item.posted_land_value) : 0;
        let landArea = item.land_area ? parseFloat(item.land_area) : 0;
        dataRows.push([index + 1, item.record_date, item.section_name, item.land_number, item.posted_land_value?parseFloat(item.posted_land_value):null, item.declared_land_value?parseFloat(item.declared_land_value):null, item.land_area?parseFloat(item.land_area):null, holdingText, landUseStr, holdingText !== '-' ? parseFloat((postedLandValue * landArea * holdingValue).toFixed(2)) : null]);
    });
    let wb = XLSX.utils.book_new(); let ws = XLSX.utils.aoa_to_sheet(dataRows);
    XLSX.utils.book_append_sheet(wb, ws, "查詢結果"); XLSX.writeFile(wb, "查詢結果_" + new Date().toISOString().slice(0,10) + ".xlsx");
}

function exportToWord() {
    let summaryText = document.getElementById('search-summary-text').innerText;
    let html = `<p style="font-size:14px; font-weight:bold;">${summaryText}</p><table border="1" style="border-collapse:collapse; text-align:center; font-family:Microsoft JhengHei;"><thead style="background-color:#e9ecef;"><tr><th>編號</th><th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th><th>持分</th><th>使用地類別</th><th>價值(元)</th></tr></thead><tbody>`;
    filteredTotalData.forEach((item, index) => {
        const matchEthnic = globalEthnicData.find(e => e.section_name === item.section_name && e.land_number === item.land_number);
        let ownerTypeStr = matchEthnic ? (matchEthnic.owner_type || "") : "";
        let landUseStr = matchEthnic ? (matchEthnic.land_use || "-") : "-";
        let holdingText = "-"; let holdingValue = 1;
        if (ownerTypeStr.includes("祭祀公業")) { let match = ownerTypeStr.match(/祭祀公業:(.*?)%/); if (match && match[1]) holdingText = match[1] + "%"; }
        let postedLandValue = item.posted_land_value ? parseFloat(item.posted_land_value) : 0; let landArea = item.land_area ? parseFloat(item.land_area) : 0;
        html += `<tr><td>${index + 1}</td><td>${item.record_date || ''}</td><td>${item.section_name || ''}</td><td>${item.land_number || ''}</td><td style="text-align:right;">${item.posted_land_value || '-'}</td><td style="text-align:right;">${item.declared_land_value || '-'}</td><td>${item.land_area || '-'}</td><td>${holdingText}</td><td><b>${landUseStr}</b></td><td style="text-align:right; font-weight:bold;">${Number((postedLandValue * landArea * holdingValue).toFixed(2)).toLocaleString()}</td></tr>`;
    });
    html += `</tbody></table>`;
    const template = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8" /></head><body>${html}</body></html>`;
    const blob = new Blob([template], { type: "application/msword;charset=utf-8;" });
    const link = document.createElement("a"); link.href = URL.createObjectURL(blob); link.download = "查詢結果_" + new Date().toISOString().slice(0,10) + ".doc"; link.click();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>