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
// 設定編碼為 utf8mb4 避免中文亂碼
$conn->set_charset("utf8mb4");

// ==========================================
// 2. 提供給前端 AJAX 的 API 接口 (支援姓名與編號的 LIKE 模糊查詢)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'get_houses') {
    $search_keyword = isset($_GET['new_member']) ? trim($_GET['new_member']) : '';
    
    $members_list = [];

    if (!empty($search_keyword)) {
        // 🛠️ 修正點：使用 LIKE 搭配前後 % 進行模糊查詢，不論輸入部分編號或部分姓名都能比對到
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
    
    // 回傳完整的 JSON 陣列
    header('Content-Type: application/json');
    echo json_encode($members_list);
    exit; // 結束執行，不載入下方的 HTML
}

// ==========================================
// 3. 處理表單送出：寫入 makeawish 資料表
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $insert_id = isset($_POST['next_id']) ? intval($_POST['next_id']) : 1;
    $name = isset($_POST['author']) ? $_POST['author'] : '';
    
    $emperor_shizu = isset($_POST['hidden_shizu']) ? intval($_POST['hidden_shizu']) : 0;
    $generation_val = isset($_POST['hidden_generation']) ? intval($_POST['hidden_generation']) : 0;
    $number_of_houses = isset($_POST['hidden_houses']) ? intval($_POST['hidden_houses']) : 0;
    
    $family_members = isset($_POST['familyMember']) ? $_POST['familyMember'] : '';
    $message_of_blessing = isset($_POST['content']) ? $_POST['content'] : '';
    $login_time = isset($_POST['login_time']) ? $_POST['login_time'] : null; 

    $stmt = $conn->prepare("INSERT INTO makeawish (ID, name, number_of_houses, emperor_shizu, generation, family_members, message_of_blessing, login_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiisss", $insert_id, $name, $number_of_houses, $emperor_shizu, $generation_val, $family_members, $message_of_blessing, $login_time);
    
    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('寫入失敗：" . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// ==========================================
// 4. 撈取目前資料表 makeawish 的最大 ID 與 跑馬燈清單
// ==========================================
$max_id = 0;
$sql_max = "SELECT MAX(ID) AS max_id FROM makeawish";
$result_max = $conn->query($sql_max);
if ($result_max && $row_max = $result_max->fetch_assoc()) {
    $max_id = $row_max['max_id'] ? intval($row_max['max_id']) : 0; 
}
$next_id = $max_id + 1;

// 動態從資料庫抓取目前的祈願內容，直接顯示
$wishes_array = [];
$sql_wishes = "SELECT name,emperor_shizu,generation, number_of_houses, message_of_blessing FROM makeawish ORDER BY ID DESC LIMIT 50";
$result_wishes = $conn->query($sql_wishes);
if ($result_wishes && $result_wishes->num_rows > 0) {
    while($w_row = $result_wishes->fetch_assoc()) {
        $wishes_array[] = [
            'author' => $w_row['name'] . " (" ."第" . $w_row['number_of_houses'] . "大房)",
            'emperor_shizu' => "來台第" . $w_row['emperor_shizu'] . "世祖",
            'generation' => "定居大甲第" . $w_row['generation'] . "代",
            'content' => $w_row['message_of_blessing']
        ];
    }
} else {
    // 預設預備資料
    $wishes_array = [
        [ "author" => "1長房大堂哥 國華", "generation" => "來台第六代", "content" => "感念曾祖父當年用一雙長滿繭的手..." ],
        [ "author" => "2二房 佩芬", "generation" => "來台第六代", "content" => "小時候總聽阿公說『吃米擔水要思源』..." ]
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
        header { text-align: center; width: 100%; max-width: 900px; margin-bottom: 25px; animation: fadeInDown 1s ease; }
        header h1 { font-size: 2.3rem; color: #a3ccab; margin-bottom: 8px; letter-spacing: 3px; font-weight: 700; text-shadow: 0 0 12px rgba(163, 204, 171, 0.3); }
        header h2 { font-size: 1.5rem; margin-bottom: 10px; color: #ece6dc; }
        header p { font-size: 1rem; line-height: 1.6; color: #cbd5e1; }

        .open-sidebar-btn {
            position: fixed; right: 20px; top: 20px; background: rgba(163, 204, 171, 0.25); color: #a3ccab; border: 1px solid rgba(163, 204, 171, 0.6);
            padding: 10px 18px; border-radius: 25px; cursor: pointer; font-family: inherit; font-weight: bold; backdrop-filter: blur(8px); transition: all 0.3s; z-index: 10; 
        }
        .open-sidebar-btn:hover { background: #407a52; color: #fff; transform: translateY(-2px); }

        .scroll-container { width: 100%; max-width: 1100px; height: 60vh; overflow: hidden; position: relative; mask-image: linear-gradient(to bottom, transparent 0%, black 8%, black 92%, transparent 100%); }
        .marquee-track { display: flex; flex-direction: column; gap: 25px; width: 100%; position: absolute; top: 0; left: 0; animation: scrollUp 35s infinite linear; }
        .marquee-track:hover { animation-play-state: paused; }
        .wish-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; width: 100%; }
        @keyframes scrollUp { 0% { transform: translateY(0); } 100% { transform: translateY(-50%); } }

        /* 卡片主體樣式 */
        .wish-card {
            background: rgba(20, 54, 34, 0.65); 
            border-top: 0px solid #a3ccab; 
            border-radius: 6px 6px 12px 12px; 
            padding: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); 
            backdrop-filter: blur(8px); 
            position: relative; 
            transition: transform 0.4s, 
            background 0.4s;
        }
        .wish-card:hover { transform: scale(1.05) !important; background: rgba(20, 54, 34, 0.85); }

        /* ✨ 成功套用：卡片頂端中央的發光小橘點 */
        .wish-card::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 10px;
            height: 10px;
            background: #f39c12; 
            border-radius: 50%;
            box-shadow: 0 0 8px #f39c12;
        }

        .wish-content { font-size: 0.95rem; line-height: 1.6; color: #ece6dc; margin-bottom: 15px; min-height: 65px; white-space: pre-line; }
        .wish-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; border-top: 1px dashed rgba(255, 255, 255, 0.2); padding-top: 10px; }
        .wish-author { font-weight: bold; color: #f39c12; }
        .wish-generation { 
            background-color: rgba(163, 204, 171, 0.2); 
            color: #a3ccab; 
            padding: 2px 8px; 
            border-radius: 20px; 
            font-size: 0.75rem; 
            font-weight: bold; }

        /* ================= 右側浮動式輸入視窗 ================= */
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

        .member-select-wrapper {
            background: rgba(0, 0, 0, 0.3); border: 1px dashed rgba(163, 204, 171, 0.4); border-radius: 8px; padding: 10px; margin-top: 10px; display: none; max-height: 160px; overflow-y: auto;
        }
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
            .scroll-container { height: 50vh; }
        }

        /* ======右邊許願卡填寫姓名欄位,跑馬燈輸入框的特殊動畫效果 =========== */
        .marquee-input {
            width: 300px; padding: 10px; font-size: 16px; overflow: hidden; white-space: nowrap;
        }

        .marquee-input::-webkit-input-placeholder { animation: marquee 12s linear infinite; }
        .marquee-input:-moz-placeholder { animation: marquee 12s linear infinite; }
        .marquee-input::-moz-placeholder { animation: marquee 12s linear infinite; }
        .marquee-input:-ms-input-placeholder { animation: marquee 12s linear infinite; }

        @keyframes marquee {
            0% { text-indent: 100%; }
            100% { text-indent: -130%; }
        }

        .marquee-input:focus::-webkit-input-placeholder { animation: none; text-indent: 0; }
        .marquee-input:focus:-moz-placeholder { animation: none; text-indent: 0; }
        .marquee-input:focus::-moz-placeholder { animation: none; text-indent: 0; }
        .marquee-input:focus:-ms-input-placeholder { animation: none; text-indent: 0; }

        /* ================= 🛠️ 新增：編輯器彈出視窗 (Modal) 樣式 ================= */
        .editor-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px);
            z-index: 200; display: none; align-items: center; justify-content: center;
        }
        .editor-modal-overlay.active { display: flex; }
        .editor-modal-content {
            background: #112d1b; border: 1px solid rgba(163, 204, 171, 0.4);
            border-radius: 12px; padding: 25px; display: flex; flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            /* 預設 PC 板寬度與高度 80% */
            width: 80%; height: 80%; max-width: 1200px; max-height: 800px;
        }
        .editor-modal-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;
        }
        .editor-modal-title { font-size: 1.2rem; color: #a3ccab; font-weight: bold; }
        .editor-modal-close { background: transparent; border: none; color: #94a3b8; font-size: 1.8rem; cursor: pointer; }
        .editor-modal-close:hover { color: #f39c12; }
        .editor-container-box { flex: 1; min-height: 0; margin-bottom: 15px; }
        .editor-modal-footer { display: flex; justify-content: flex-end; gap: 15px; }
        .modal-btn { padding: 10px 24px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; transition: all 0.2s; }
        .modal-btn-cancel { background: #475569; color: #cbd5e1; }
        .modal-btn-cancel:hover { background: #64748b; }
        .modal-btn-submit { background: #f39c12; color: #0a1f14; }
        .modal-btn-submit:hover { background: #f59e0b; transform: translateY(-1px); }

        /* 調整 Jodit 編輯器在黑底風格下的文字顏色 */
        .jodit-container { background: #ffffff !important; color: #333333 !important; height: 100% !important; }

        /* 手機板寬度與高度調整 */
        @media (max-width: 768px) {
            .editor-modal-content {
                width: 80%; height: 80%; padding: 15px;
            }
        }
        
        /* 擴展文字按鈕樣式 */
        .expand-link {
            font-size: 0.85rem; color: #f39c12; cursor: pointer; margin-left: 10px; text-decoration: underline; font-weight: normal;
        }
        .expand-link:hover { color: #ffd166; }
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
            <div class="sidebar-footer" >
               歲時感恩牆 • <span id="clock"></span> • 李武略家族後代子孫 敬立
            </div>
        </header>

        <div class="scroll-container">
            <div class="marquee-track" id="marqueeTrack"></div>
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <button class="close-sidebar-btn" id="closeSidebarBtn">&times;</button>

        <div class="counter-box">
            目前已有 <span id="wishCount"><?php echo $max_id; ?></span> 枝子孫祈願葉
        </div>

        <div class="form-title">🌿 撰寫祈願卡</div>        
        <form id="wishForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            
            <input type="hidden" id="next_id" name="next_id" value="<?php echo $next_id; ?>">
            
            <input type="hidden" id="hidden_shizu" name="hidden_shizu" value="0">
            <input type="hidden" id="hidden_generation" name="hidden_generation" value="0">
            <input type="hidden" id="hidden_houses" name="hidden_houses" value="0">
            <input type="hidden" id="login_time" name="login_time" value="">

            <div class="form-group">
                <span class="label-text-header">
                    <span for="author">您的姓名:</span><span id="show_name" class="highlight-val">?</span>
                    ,編號:<span id="show_member_id" class="highlight-val">?</span>
                    ,第<span id="show_shizu" class="highlight-val">?</span>世祖
                    <span id="show_generation" class="highlight-val">?</span>代
                    <span id="show_houses" class="highlight-val">?</span>大房
                </span>
                
                <input type="text" id="author" name="author" class="marquee-input" 
                placeholder="打關鍵字(姓名/編號),點符合項目,顯示世代.(不開放訪客使用.)" required autocomplete="off">
                <div class="member-select-wrapper" id="memberSelectWrapper"></div>
            </div>

            <div class="form-group">
                <label for="familyMember">家庭成員</label>
                <input type="text" id="familyMember" name="familyMember" placeholder="本人或全家或2男1女或本人" required>
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

            <button type="submit" class="submit-btn">掛上祈願樹(送出)➔</button>
        </form>
    </div>

    <div class="editor-modal-overlay" id="editorModal">
        <div class="editor-modal-content">
            <div class="editor-modal-header">
                <div class="editor-modal-title">🌿 祈願話語進階編輯</div>
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
        function updateClock() {
          const now = new Date();
          const year = now.getFullYear();
          const month = now.getMonth() + 1;
          const day = now.getDate();
          const time = now.toLocaleTimeString("zh-TW", { hour12: false });
          document.getElementById("clock").textContent = `${year}/${month}/${day} ${time}`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <script>
        let wishesData = <?php echo json_encode($wishes_array); ?>;

        const marqueeTrack = document.getElementById('marqueeTrack');
        const sidebar = document.getElementById('sidebar');

        document.getElementById('openSidebarBtn').addEventListener('click', () => sidebar.classList.add('active'));
        document.getElementById('closeSidebarBtn').addEventListener('click', () => sidebar.classList.remove('active'));

        function createCardNode(wish) {
            const card = document.createElement('div');
            card.className = 'wish-card';
            const randomRotate = (Math.random() * 4 - 2).toFixed(1);
            card.style.transform = `rotate(${randomRotate}deg)`;
            card.innerHTML = `
                <div class="wish-content">「${wish.content}」</div>
                <div class="wish-meta">
                    <span class="wish-author">${wish.author}</span>
                    <span class="wish-generation">${wish.emperor_shizu}&emsp;${wish.generation}</span>
                </div>
            `;
            return card;
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
            const speedFactor = isMobile ? 8.5 : 8.5;
            marqueeTrack.style.animationDuration = `${totalRows * speedFactor}s`;            
        }

        // ==========================================
        // AJAX 查詢與動態資料渲染 (使用 LIKE 模糊查詢)
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
            document.getElementById('show_member_id').textContent = dataset.new_member;
            document.getElementById('show_shizu').textContent = dataset.shizu;
            document.getElementById('show_generation').textContent = dataset.gen;
            document.getElementById('show_houses').textContent = dataset.houses;
            document.getElementById('hidden_shizu').value = dataset.shizu;
            document.getElementById('hidden_generation').value = dataset.gen;
            document.getElementById('hidden_houses').value = dataset.houses;
            
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
            const formattedTime = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
            document.getElementById('login_time').value = formattedTime;
        });

        // ==========================================
        // 🛠️ 新增：Jodit 編輯器初始化與彈出控制邏輯
        // ==========================================
        // 初始化 Jodit 編輯器
        const joditEditor = new Jodit('#joditEditorTarget', {
            buttons: ['source', '|', 'bold', 'strikethrough', 'underline', 'italic', '|', 'superscript', 'subscript', '|', 'ul', 'ol', '|', 'outdent', 'indent', '|', 'font', 'fontsize', 'brush', 'paragraph', '|', 'image', 'table', 'link', '|', 'align', 'undo', 'redo', '|', 'hr', 'eraser', 'fullsize'],
            height: '100%',
            language: 'zh_tw'
        });

        const editorModal = document.getElementById('editorModal');
        const mainContentTextarea = document.getElementById('content');

        // 打開彈出視窗
        document.getElementById('openEditorBtn').addEventListener('click', function() {
            // 將原本 textarea 的內容導入編輯器中 (支援原文字或基本 HTML)
            joditEditor.value = mainContentTextarea.value;
            editorModal.classList.add('active');
        });

        // 關閉與取消邏輯
        function closeModal() {
            editorModal.classList.remove('active');
        }
        document.getElementById('closeEditorBtn').addEventListener('click', closeModal);
        document.getElementById('cancelModalBtn').addEventListener('click', closeModal);

        // 按下編輯視窗的「確認送出」按鈕
        document.getElementById('submitModalBtn').addEventListener('click', function() {
            // 將編輯器的內容傳回原來的 "寫給祖先/祈願的話" 位置 (保持原本 HTML 或純文字)
            mainContentTextarea.value = joditEditor.value;
            closeModal();
        });

        window.addEventListener('resize', renderMarquee);
        window.addEventListener('DOMContentLoaded', renderMarquee);
    </script>
</body>
</html>
<?php
$conn->close();
?>