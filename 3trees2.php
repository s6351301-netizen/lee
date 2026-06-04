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
// 2. 提供給前端 AJAX 的 API 接口 (透過姓名撈取房數)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'get_houses') {
    $new_member = isset($_GET['new_member']) ? $_GET['new_member'] : '';
    $number_of_houses = 0;

    if (!empty($new_member)) {
        // 查詢 members 資料表
        $stmt = $conn->prepare("SELECT number_of_houses FROM members WHERE new_member = ? LIMIT 1");
        $stmt->bind_param("s", $new_member);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $number_of_houses = intval($row['number_of_houses']);
        }
        $stmt->close();
    }
    
    // 回傳 JSON
    header('Content-Type: application/json');
    echo json_encode(['number_of_houses' => $number_of_houses]);
    exit; // 結束執行，不載入下方的 HTML
}

// ==========================================
// 3. 處理表單送出：【全新插入一筆指定新 ID 與時間的資料】
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 接收來自前端隱藏欄位與表單的資料
    $insert_id = isset($_POST['next_id']) ? intval($_POST['next_id']) : 1;
    $name = isset($_POST['author']) ? $_POST['author'] : '';
    $number_of_houses = isset($_POST['hidden_houses']) ? intval($_POST['hidden_houses']) : 0;
    $raw_generation = isset($_POST['generation']) ? $_POST['generation'] : '';
    
    // 🛠️ 修正點：接收 familyMember 的值
    $family_members = isset($_POST['familyMember']) ? $_POST['familyMember'] : '';
    $message_of_blessing = isset($_POST['content']) ? $_POST['content'] : '';
    $login_time = isset($_POST['login_time']) ? $_POST['login_time'] : null; // 接收前端系統時間

    // 解析世代輩分下拉選單的值
    $emperor_shizu = 0;
    $generation_val = 0;

    // 抓取 "世祖" 前面的數字
    if (preg_match('/(\d+)世祖/', $raw_generation, $matches)) {
        $emperor_shizu = intval($matches[1]);
    }
    // 抓取 "代" 前面的數字
    if (preg_match('/(\d+)代/', $raw_generation, $matches)) {
        $generation_val = intval($matches[1]);
    }

    // 🛠️ 修正點：SQL 語法與 bind_param 補上 family_members 欄位
    $stmt = $conn->prepare("INSERT INTO makeawish (ID, name, number_of_houses, emperor_shizu, generation, family_members, message_of_blessing, login_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiisss", $insert_id, $name, $number_of_houses, $emperor_shizu, $generation_val, $family_members, $message_of_blessing, $login_time);
    
    if ($stmt->execute()) {
        // 新增成功後重新導向回原頁面，防止使用者重新整理網頁時重複發送 INSERT 表單
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<script>alert('寫入失敗：" . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// ==========================================
// 4. 撈取目前資料表 makeawish 欄位 ID 的最大一個數值
// ==========================================
$max_id = 0;
$sql_max = "SELECT MAX(ID) AS max_id FROM makeawish";
$result_max = $conn->query($sql_max);
if ($result_max && $row_max = $result_max->fetch_assoc()) {
    // 如果資料表完全是空的，則預設為 0
    $max_id = $row_max['max_id'] ? intval($row_max['max_id']) : 0; 
}

// 計算最大值再加 1 的值，作為下一次寫入 ID 欄位的值
$next_id = $max_id + 1;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家族大樹祈願樹 - 飲水思源 • 世代感恩</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@500;700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
    
    <style>
        /* ================= 全局與主體設定 ================= */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: "Noto Serif TC", "PingFang TC", serif;
            color: #e9e4db; 
            height: 100vh;
            display: flex;
            overflow: hidden; 
            position: relative;
            background-color: #0a1f14;
        }

        /* 專門負責「大樹背景」鏡頭移動與放大的獨立圖層 */
        .tree-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background-image: 
                linear-gradient(rgba(10, 31, 20, 0.85), rgba(10, 31, 20, 0.85)),                          
                url('https://cdntwrunning.biji.co/800_21cb7a3776e5fdbb6ffeb4e235067e88.jpg');
            background-size: 100% 100%, cover;
            background-position: center, center;
            background-repeat: no-repeat, no-repeat;
            
            will-change: transform;
            animation: treeOrbitLinkage 100s infinite linear;
        }

        /* 將少許星空微粒浮動在樹木上方、文字下方 */
        .tree-background::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.18) 0.5px, transparent 20px), 
                radial-gradient(rgba(255, 255, 255, 0.12) 0.6px, transparent 25px),   
                radial-gradient(#f39c12, rgba(243, 156, 18, 0.15) 1.5px, transparent 40px);   
            
            background-size: 280px 280px, 380px 380px, 580px 580px;
            background-position: 0 0, 0 0, 0 0;
            background-repeat: repeat, repeat, repeat;            
            animation: forestStream 50s infinite linear;
        }

        /* 微粒緩慢飄動動畫 */
        @keyframes forestStream {
            0% { background-position: 0 0, 0 0, 0 0; }
            100% { background-position: 500px 1000px, -350px 700px, 550px 550px; }
        }   

        /* 樹木跟隨太陽移動放大的核心動畫 */
        @keyframes treeOrbitLinkage {
            0% { transform: scale(1.05) translateX(-3%) translateY(0); }
            50% { transform: scale(1.2) translateX(0%) translateY(-2%); }
            100% { transform: scale(1.05) translateX(-3%) translateY(0); }
        }

        /* ================= 太陽天體拋物線運動動畫 ================= */
        .celestial-body {
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            z-index: 2; 
            pointer-events: none; 
            animation: 
                sunOrbitX 100s infinite linear,
                sunOrbitY 100s infinite cubic-bezier(0.3, 0.2, 0.7, 0.8),
                sunPulse 100s infinite linear;
        }

        @keyframes sunOrbitX { 0% { left: -10%; } 100% { left: 110%; } }
        @keyframes sunOrbitY { 0% { top: 60%; } 50% { top: 3%; } 100% { top: 60%; } }

        @keyframes sunPulse {
            0%, 100% {
                background: transparent;
                box-shadow: -15px 15px 0 0 rgba(238, 242, 246, 0.85); 
                filter: drop-shadow(0 0 10px rgba(255,255,255,0.4));
                transform: rotate(0deg);
            }
            45%, 55% {
                background: linear-gradient(135deg, #ffd166, #f39c12, #e63946); 
                box-shadow: 0 0 40px #f39c12, 0 0 70px #e63946;
                filter: drop-shadow(0 0 0 transparent);
                transform: rotate(180deg);
            }
        }

        /* ================= 全螢幕呈現區 (100%) ================= */
        .main-content {
            width: 100%;
            height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 3; 
        }

        header {
            text-align: center;
            width: 100%;
            max-width: 900px;
            margin-bottom: 25px;
            animation: fadeInDown 1s ease;
            position: relative;
        }
        header h1 {
            font-size: 2.3rem;
            color: #a3ccab; 
            margin-bottom: 8px;
            letter-spacing: 3px;
            font-weight: 700;
            text-shadow: 0 0 12px rgba(163, 204, 171, 0.3);
        }
        header h2 { font-size: 1.5rem; margin-bottom: 10px; color: #ece6dc; }
        header p { font-size: 1rem; line-height: 1.6; color: #cbd5e1; margin-bottom: 5px; }

        .open-sidebar-btn {
            position: fixed;
            right: 20px;
            top: 20px;
            background: rgba(163, 204, 171, 0.25);
            color: #a3ccab;
            border: 1px solid rgba(163, 204, 171, 0.6);
            padding: 10px 18px;
            border-radius: 25px;
            cursor: pointer;
            font-family: inherit;
            font-weight: bold;
            font-size: 0.95rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            z-index: 10; 
        }
        .open-sidebar-btn:hover {
            background: #407a52;
            color: #fff;
            border-color: #407a52;
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 6px 20px rgba(64, 122, 82, 0.4);
        }

        /* ================= 跑馬燈卡片流動動畫區 ================= */
        .scroll-container {
            width: 100%;
            max-width: 1100px;
            height: 60vh; 
            overflow: hidden;
            position: relative;
            mask-image: linear-gradient(to bottom, transparent 0%, black 8%, black 92%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 8%, black 92%, transparent 100%);
        }

        .marquee-track {
            display: flex;
            flex-direction: column;
            gap: 25px;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            animation: scrollUp 35s infinite linear;
        }
        .marquee-track:hover { animation-play-state: paused; }

        .wish-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px;
            width: 100%;
        }

        @keyframes scrollUp {
            0% { transform: translateY(0); }
            100% { transform: translateY(-50%); } 
        }

        .wish-card {
            background: rgba(20, 54, 34, 0.65); 
            border-top: 4px solid #a3ccab; 
            border-radius: 6px 6px 12px 12px;
            padding: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px); 
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            transform-origin: top center;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s;
        }
        .wish-card:hover {
            transform: scale(1.05) rotate(0deg) !important; 
            background: rgba(20, 54, 34, 0.85);
            box-shadow: 0 12px 25px rgba(163, 204, 171, 0.3);
        }
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
        .wish-generation { background-color: rgba(163, 204, 171, 0.2); color: #a3ccab; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; border: 1px solid rgba(163, 204, 171, 0.3); }

        /* ================= 右側浮動式輸入視窗 ================= */
        .sidebar {
            position: fixed;
            top: 0;
            right: -100%; 
            width: 32%;
            min-width: 350px;
            max-width: 440px;
            height: 100vh;
            max-height: 100vh; 
            background: rgba(10, 31, 20, 0.92); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 50px 30px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            box-shadow: -10px 0 35px rgba(0, 0, 0, 0.6);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 100;
            transition: right 0.5s cubic-bezier(0.16, 1, 0.3, 1); 
            overflow-y: auto;
            -webkit-overflow-scrolling: touch; 
        }
        .sidebar.active { right: 0; }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(163, 204, 171, 0.3); border-radius: 3px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(163, 204, 171, 0.6); }

        .close-sidebar-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 2rem;
            cursor: pointer;
            line-height: 1;
            transition: color 0.3s, transform 0.3s;
        }
        .close-sidebar-btn:hover { color: #f39c12; transform: rotate(90deg); }

        .counter-box {
            font-size: 1rem;
            background: rgba(163, 204, 171, 0.1);
            padding: 8px 15px;
            border-radius: 8px;
            text-align: center;
            color: #a3ccab;
            font-weight: bold;
            margin-bottom: 15px;
            border: 1px solid rgba(163, 204, 171, 0.2);
        }
        .counter-box span { font-size: 1.4rem; color: #f39c12; margin: 0 4px; text-shadow: 0 0 8px rgba(243, 156, 18, 0.4); }

        .form-title { font-size: 1.4rem; color: #a3ccab; margin-bottom: 20px; font-weight: bold; border-bottom: 2px solid #a3ccab; padding-bottom: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 0.95rem; margin-bottom: 8px; color: #cbd5e1; font-weight: bold; }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            background-color: rgba(5, 15, 10, 0.7);
            color: #e9e4db;
            transition: all 0.3s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #a3ccab; box-shadow: 0 0 8px rgba(163, 204, 171, 0.3); background-color: rgba(5, 15, 10, 0.9); }
        select option { background-color: #0a1f14; color: #e9e4db; }
        textarea { resize: none; height: 200px; }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #407a52, #143622);
            color: #e9e4db;
            border: none;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(20, 54, 34, 0.4);
            transition: all 0.3s ease;
            margin-top: 5px;
            margin-bottom: 20px; 
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(163, 204, 171, 0.3); }
        .sidebar-footer { font-size: 0.85rem; color: #94a3b8; text-align: center; }

        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            header h1 { font-size: 1.8rem; }
            header h2 { font-size: 1.2rem; }
            .sidebar { width: 100%; min-width: 100%; padding: 40px 20px; }
            .open-sidebar-btn { position: static; margin-bottom: 15px; display: block; }
            .scroll-container { height: 50vh; }
        }
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

            <input type="hidden" id="hidden_houses" name="hidden_houses" value="0">

            <input type="hidden" id="login_time" name="login_time" value="">

            <div class="form-group">
                <label for="author">您的姓名</label>本人為?世祖?代?大房
                <input type="text" id="author" name="author" placeholder="例如：李國華" required>
            </div>
            <div class="form-group">
                <label for="familyMember">家庭成員</label>
                <input type="text" id="familyMember" name="familyMember" placeholder="本人或全家或2男1女或本人" required>
            </div>

            <div class="form-group">
                <label for="generation">世代輩分</label>
                <select id="generation" name="generation" required>
                    <option value="" disabled selected>請選擇輩分</option>
                    <option value="來台第21世祖/大甲2代">來台第21世祖/大甲2代(祖字輩)</option>
                    <option value="來台第22世祖/大甲3代">來台第22世祖/大甲3代(武字輩)</option>
                    <option value="來台第23世祖/大甲4代">來台第23世祖/大甲4代(德字輩)</option>
                    <option value="來台第24世祖/大甲5代">來台第24世祖/大甲5代(業字輩)</option>
                    <option value="來台第25世祖/大甲6代">來台第25世祖/大甲6代(貽字輩)</option>
                    <option value="來台第26世祖/大甲7代">來台第26世祖/大甲7代(孫字輩)</option>
                    <option value="來台第27世祖/大甲8代">來台第27世祖/大甲8代(謀字輩)</option>
                    <option value="來台第28世祖/大甲9代">來台第28世祖/大甲9代(不使用字輩)</option>
                    <option value="來台第29世祖/大甲10代">來台第29世祖/大甲10代(不使用字輩)</option>
                    <option value="來台第30世祖/大甲11代">來台第30世祖/大甲11代(不使用字輩)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="content">寫給祖先/祈願的話</label>
                <textarea id="content" name="content" placeholder="請寫下您對先祖默默耕耘的感念，或是對李氏後代子孫的祝福，或是對家族或家庭互動的心得感想..." required></textarea>
            </div>

            <button type="submit" class="submit-btn">掛上祈願樹(送出)➔</button>
        </form>
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
        // 預設靜態跑馬燈展示資料
        let wishesData = [
            { author: "1長房大堂哥 國華", generation: "來台第六代", content: "感念曾祖父當年用一雙長滿繭的手，一鋤一鋤地在這片荒地挖出水田。如今我們雖然不再務農，但那份刻苦耐勞、腳踏實地的家風，我一定會繼續傳給下一代。" },
            { author: "2二房 佩芬", generation: "來台第六代", content: "小時候總聽阿公說『吃米擔水要思源』。長大後進了都市工作，每當遇到挫折，想到祖先們當年渡海與對抗天災的堅韌，就覺得自己充滿力量。" },
            { author: "3三房 志明", generation: "來台第七代", content: "每到秋收時節，看著金黃色的稻浪，就彷彿看見公媽們微笑的臉龐。願李氏家族就像這棵大樹一樣，根深蒂固，開花結果。" },
            { author: "4小妹 雅婷", generation: "來台第七代", content: "謝謝阿太、阿公、阿嬤一輩子守護這片土地，讓我們有家可回、有根可尋。每當回到三合院，心裡就無比踏實。" },
            { author: "5三房 志明", generation: "來台第七代", content: "每到秋收時節，看著金黃色的稻浪，就彷彿看見公媽們微笑的臉龐。願李氏家族就像這棵大樹一樣，根深蒂固，開花結果。" },
            { author: "6三房 志明", generation: "來台第七代", content: "每到秋收時節，看著金黃色的稻浪，就彷彿看見公媽們微笑的臉龐。願李氏家族就像這棵大樹一樣，根深蒂固，開花結果。" },
            { author: "7三房 志明", generation: "來台第七代", content: "每到秋收時節，看著金黃色的稻浪，就彷彿看見公媽們微笑的臉龐。願李氏家族就像這棵大樹一樣，根深蒂固，開花結果。" }, 
            { author: "8三房 志明", generation: "來台第七代", content: "每到秋收時節，看著金黃色的稻浪，就彷彿看見公媽們微笑的臉龐。願李氏家族就像這棵大樹一樣，根深蒂固，開花結果。" }
        ];

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
                    <span class="wish-generation">${wish.generation}</span>
                </div>
            `;
            return card;
        }

        function renderMarquee() {
            marqueeTrack.innerHTML = '';
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

            rowsData.forEach(rowItems => {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'wish-row';
                rowItems.forEach(item => rowDiv.appendChild(createCardNode(item)));
                marqueeTrack.appendChild(rowDiv);
            });

            const totalRows = rowsData.length;
            const speedFactor = isMobile ? 12.5 : 15.5;
            marqueeTrack.style.animationDuration = `${totalRows * speedFactor}s`;            
        }

        // ==========================================
        // 🛠️ 封裝獨立函數：向後端請求房數資料（回傳 Promise）
        // ==========================================
        function fetchHouseNumber(memberName) {
            if (!memberName) return Promise.resolve(0);
            return fetch(`?action=get_houses&new_member=${encodeURIComponent(memberName)}`)
                .then(response => response.json())
                .then(data => {
                    const houses = data.number_of_houses || 0;
                    document.getElementById('hidden_houses').value = houses;
                    console.log(`AJAX 房數載入成功: ${houses}`);
                    return houses;
                })
                .catch(error => {
                    console.error('AJAX 撈取房數發生錯誤:', error);
                    return 0;
                });
        }

        // 當輸入框失焦或改變時，先預先撈取
        document.getElementById('author').addEventListener('change', function() {
            fetchHouseNumber(this.value.trim());
        });

        // ==========================================
        // 🛠️ 修正監聽表單送出事件：使用 async/await 確保房數撈完再送出
        // ==========================================
        document.getElementById('wishForm').addEventListener('submit', async function(e) {
            // 1. 先暫停表單預設的同步送出行為
            e.preventDefault();

            // 2. 捕捉系統現在時間並格式化寫入隱藏欄位
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const formattedTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            document.getElementById('login_time').value = formattedTime;

            // 3. 關鍵修正：即時、強制重新去撈取一次最新的資料庫房數欄位，阻斷等待回應
            const memberName = document.getElementById('author').value.trim();
            await fetchHouseNumber(memberName);

            // 4. 房數已確實寫入隱藏欄位，手動觸代表單送出
            this.submit();
        });

        window.addEventListener('resize', renderMarquee);
        window.addEventListener('DOMContentLoaded', () => {
            renderMarquee();
        });
    </script>
</body>
</html>
<?php
// 關閉連接
$conn->close();
?>