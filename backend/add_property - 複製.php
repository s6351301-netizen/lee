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

// 模擬您原有的 Session 驗證結構 (此處保留供系統擴充)
if (isset($_SESSION['name'])) {
    $current_user = $_SESSION['name'];
    $stmt = $conn->prepare("SELECT role, new_member FROM account WHERE name = ?");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}

// ==========================================
// 2. 處理 AJAX 請求 (API 路由)
// ==========================================

// A. 處理資料表單新增 (AJAX POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['action']) && $_GET['action'] == 'insert') {
    header('Content-Type: application/json');
    
    // 接收並過濾資料
    // [族產欄位]
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
    
    // [地價欄位]
    $record_date          = trim($_POST['record_date'] ?? '');
    $posted_land_value    = !empty($_POST['posted_land_value']) ? floatval($_POST['posted_land_value']) : null;
    $declared_land_value  = !empty($_POST['declared_land_value']) ? floatval($_POST['declared_land_value']) : null;
    $land_area            = !empty($_POST['land_area']) ? floatval($_POST['land_area']) : null;

    // 啟動交易(Transaction)，確保兩張表都成功才寫入
    $conn->begin_transaction();

    try {
        // 1. 新增到 ethnic_property
        $sql_ethnic = "INSERT INTO ethnic_property (status, city, district, section_code, section_name, land_number, register_date, area, zoning, land_use, owner_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_e = $conn->prepare($sql_ethnic);
        $stmt_e->bind_param("sssssssdsss", $status, $city, $district, $section_code, $section_name, $land_number, $register_date, $area, $zoning, $land_use, $owner_type);
        $stmt_e->execute();
        $stmt_e->close();

        // 2. 新增或更新到 land_price (包含重複鍵值的更新機制)
        if (!empty($record_date)) {
            $sql_price = "INSERT INTO land_price (record_date, section_name, land_number, posted_land_value, declared_land_value, land_area) 
                          VALUES (?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                          posted_land_value = VALUES(posted_land_value), 
                          declared_land_value = VALUES(declared_land_value), 
                          land_area = VALUES(land_area)";
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

// B. 獲取不重複的歷史紀錄 (AJAX GET)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_history') {
    header('Content-Type: application/json');
    
    $history_data = [];
    
    // 撈取族產表不重複資料
    $res_ethnic = $conn->query("SELECT DISTINCT status, city, district, section_code, section_name, land_number, register_date, area, zoning, land_use, owner_type FROM ethnic_property ORDER BY id DESC");
    while ($row = $res_ethnic->fetch_assoc()) {
        $history_data['ethnic'][] = $row;
    }
    
    // 撈取地價表不重複資料
    $res_price = $conn->query("SELECT DISTINCT record_date, section_name, land_number, posted_land_value, declared_land_value, land_area FROM land_price ORDER BY record_date DESC");
    while ($row = $res_price->fetch_assoc()) {
        $history_data['price'][] = $row;
    }
    
    echo json_encode($history_data);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>族產與歷年地價管理系統 (AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: "Microsoft JhengHei", sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: none; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .required-star { color: red; margin-left: 3px; }
    </style>
</head>
<body>

<div class="container py-5">
    <!-- 表單卡片 -->
    <div class="card mb-5">
        <div class="card-header bg-primary text-white p-3">
            <h4 class="mb-0">📋 族產資料與歷年地價整合新增表單 (AJAX)</h4>
        </div>
        <div class="card-body p-4">
            <!-- 提示訊息 -->
            <div id="alert-box" class="alert d-none alert-dismissible fade show" role="alert">
                <span id="alert-msg"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>

            <form id="property-form">
                <h5 class="text-secondary border-bottom pb-2 mb-3">📍 第一部分：族產基本資料 (ethnic_property)</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">狀態<span class="required-star">*</span></label>
                        <select class="form-select" name="status" required>
                            <option value="持有">持有</option>
                            <option value="已賣">已賣</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">縣市</label>
                        <input type="text" class="form-control" name="city" placeholder="臺中市">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">鄉鎮市區</label>
                        <input type="text" class="form-control" name="district" placeholder="大甲區">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">段小段代碼</label>
                        <input type="text" class="form-control" name="section_code" placeholder="3652">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">段小段名稱<span class="required-star">*</span></label>
                        <input type="text" class="form-control" name="section_name" placeholder="義水段" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">地號<span class="required-star">*</span></label>
                        <input type="text" class="form-control" name="land_number" placeholder="0855-0000" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">登記日期</label>
                        <input type="text" class="form-control" name="register_date" placeholder="840519">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">面積 (平方公尺)</label>
                        <input type="number" step="0.01" class="form-control" name="area" placeholder="12.73">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">使用分區</label>
                        <input type="text" class="form-control" name="zoning" placeholder="城鄉發展地區第一類">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">使用地類別</label>
                        <input type="text" class="form-control" name="land_use" placeholder="住宅區">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">土地權利人類別</label>
                    <textarea class="form-control" name="owner_type" rows="2" placeholder="祭祀公業:100.00%"></textarea>
                </div>

                <h5 class="text-secondary border-bottom pb-2 mb-3 mt-4">💰 第二部分：土地公告地價資料 (land_price)</h5>
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
                        <label class="form-label">地價面積 (元/㎡)</label>
                        <input type="number" step="0.01" class="form-control" name="land_area" placeholder="8278.29">
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="button" class="btn btn-secondary me-2" onclick="document.getElementById('property-form').reset()">重設</button>
                    <button type="submit" class="btn btn-success px-5">儲存送出 (AJAX)</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 歷史資料展示區 (不重複) -->
    <div class="row">
        <!-- 族產表歷史 -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">📜 族產歷史資料清單 (不重複)</h5>
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
                            <tbody id="ethnic-history-tbody">
                                <tr><td colspan="11" class="text-muted py-4">資料載入中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 地價表歷史 -->
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">📊 歷年公告地價清單 (land_price 不重複)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 align-middle text-center" style="font-size:0.9rem;">
                            <thead class="table-secondary sticky-top">
                                <tr>
                                    <th>公告年月</th><th>段小段</th><th>地號</th><th>公告土地現值(元)</th><th>公告地價(元)</th><th>面積(㎡)</th>
                                </tr>
                            </thead>
                            <tbody id="price-history-tbody">
                                <tr><td colspan="6" class="text-muted py-4">資料載入中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // 網頁載入時自動讀取歷史資料
    loadHistory();

    // 監聽表單送出事件
    document.getElementById('property-form').addEventListener('submit', function(e) {
        e.preventDefault(); // 阻擋表單預設重新導向頁面
        
        const formData = new FormData(this);
        
        // 發送 AJAX POST 請求
        fetch('manage_property.php?action=insert', {
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
                document.getElementById('property-form').reset(); // 清空表單
                loadHistory(); // 重新刷歷史資料
            } else {
                alertBox.classList.add('alert-danger');
                alertMsg.innerText = data.message;
            }
            window.scrollTo({ top: 0, behavior: 'smooth' }); // 平滑捲動至頂端看訊息
        })
        .catch(error => {
            console.error('Error:', error);
            alert('系統發生錯誤，無法完成 AJAX 傳輸。');
        });
    });
});

// 透過 AJAX 撈取歷史紀錄不重複列表
function loadHistory() {
    fetch('manage_property.php?action=get_history')
    .then(response => response.json())
    .then(data => {
        // 渲染 族產歷史
        const ethnicTbody = document.getElementById('ethnic-history-tbody');
        ethnicTbody.innerHTML = '';
        if(data.ethnic && data.ethnic.length > 0) {
            data.ethnic.forEach(item => {
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
                        <td class="text-start" style="white-space: pre-line;">${item.owner_type || '-'}</td>
                    </tr>
                `;
            });
        } else {
            ethnicTbody.innerHTML = '<tr><td colspan="11" class="text-muted py-3">暫無歷史紀錄</td></tr>';
        }

        // 渲染 地價歷史
        const priceTbody = document.getElementById('price-history-tbody');
        priceTbody.innerHTML = '';
        if(data.price && data.price.length > 0) {
            data.price.forEach(item => {
                priceTbody.innerHTML += `
                    <tr>
                        <td><strong>${item.record_date}</strong></td>
                        <td>${item.section_name}</td>
                        <td>${item.land_number}</td>
                        <td class="text-end text-success">${item.posted_land_value ? '$'+Number(item.posted_land_value).toLocaleString() : '-'}</td>
                        <td class="text-end text-primary">${item.declared_land_value ? '$'+Number(item.declared_land_value).toLocaleString() : '-'}</td>
                        <td>${item.land_area || '-'}</td>
                    </tr>
                `;
            });
        } else {
            priceTbody.innerHTML = '<tr><td colspan="6" class="text-muted py-3">暫無歷史地價紀錄</td></tr>';
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>