<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

// ==========================================
// 後端處理 A：提供給「收件人」輸入框的 AJAX 模糊查詢 API (只針對收件人)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'search_receivers') {
    header('Content-Type: application/json; charset=utf-8');
    $search = isset($_GET['term']) ? trim($_GET['term']) : '';
    $list = [];

    if (!empty($search)) {
        $conn = new mysqli($servername, $username, $password, $dbname);
        $conn->set_charset("utf8mb4");
        $kw = "%" . $search . "%";
        
        // 模糊搜尋名冊 members 中的人名或編號，當作可以勾選的個人收件對象
        $stmt = $conn->prepare("SELECT name, new_member, emperor_shizu, generation, number_of_houses FROM members WHERE new_member LIKE ? OR name LIKE ? LIMIT 10");
        $stmt->bind_param("ss", $kw, $kw);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                'new_member' => $row['new_member'],
                'name' => $row['name'],
                'shizu' => $row['emperor_shizu'],
                'gen' => $row['generation'],
                'houses' => $row['number_of_houses']
            ];
        }
        $stmt->close();
        $conn->close();
    }
    echo json_encode($list);
    exit;
}

// ==========================================
// 後端處理 B：接收表單 POST 提交 (一對多群發儲存)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=utf-8');
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) { die("【資料庫連線失敗】: " . $conn->connect_error); }
    $conn->set_charset("utf8mb4");

    $uploaded_name = $_POST['uploaded_name'] ?? ''; // 自動取得的本人姓名
    $uploaded_id = $_POST['uploaded_id'] ?? '';     // 自動取得的本人編號
    $description = $_POST['description'] ?? '';     // 包含大括號文字的內文
    $public = isset($_POST['public']) ? (int)$_POST['public'] : 0;
    
    // 接收多個勾選的收件目標
    $targets = isset($_POST['targets']) ? $_POST['targets'] : []; 

    if (empty($targets)) {
        die("【發送失敗】請至少勾選一個收件對象、世代或職稱群組！");
    }

    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $orig_name = $_FILES['video_file']['name'];
        $file_size = $_FILES['video_file']['size'];
        $file_type = $_FILES['video_file']['type']; 
        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
        if (empty($ext)) { $ext = (strpos($file_type, 'webm') !== false) ? 'webm' : 'mp4'; }
        
        $new_file_name = 'vid_' . uniqid() . '.' . $ext;
        $file_path = $upload_dir . $new_file_name;
        
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $file_path)) {
            
            $file_url = 'http://' . $_SERVER['HTTP_HOST'] . '/' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' . $file_path;
            $file_url = str_replace('//uploads', '/uploads', $file_url);

            // 1. 寫入 files 主資料表
            $sql_file = "INSERT INTO files (file_name, file_path, file_url, file_type, file_size, uploaded_id, uploaded_name, description, reference_id, public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)";
            $stmt_file = $conn->prepare($sql_file);
            $stmt_file->bind_param("ssssisssi", $new_file_name, $file_path, $file_url, $file_type, $file_size, $uploaded_id, $uploaded_name, $description, $public);
            $stmt_file->execute();
            $new_file_id = $stmt_file->insert_id; 
            $stmt_file->close();

            // 2. 核心：遍歷勾選的對象，寫入 messages 郵件信箱紀錄表
            $sql_msg = "INSERT INTO messages (file_id, from_id, from_name, to_type, to_target) VALUES (?, ?, ?, ?, ?)";
            $stmt_msg = $conn->prepare($sql_msg);

            foreach ($targets as $raw_target) {
                $parts = explode(':', $raw_target, 2);
                if (count($parts) === 2) {
                    $to_type = $parts[0];   // user, generation, houses, role
                    $to_target = $parts[1]; // 具體的值 (如 6, admin, 5號)
                    $stmt_msg->bind_param("issss", $new_file_id, $uploaded_id, $uploaded_name, $to_type, $to_target);
                    $stmt_msg->execute();
                }
            }
            $stmt_msg->close();

            echo "✉ 影音訊息已成功群發至所有指定對象！";
        } else {
            echo "【檔案搬移失敗】無法儲存影音檔。";
        }
    } else {
        echo "【上傳失敗】後端未收到影音檔案。";
    }
    
    $conn->close();
    exit;
}

// ==========================================
// 前端處理：直接連動撈取當前登入使用者的完整名冊身分 (李秋香、5號、世祖、代、房)
// ==========================================
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// 預設訪客防呆
$my_name = "訪客"; 
$my_member_id = "0"; 
$my_shizu = "?"; 
$my_gen = "?"; 
$my_houses = "?";
$meta_init_text = "";

// 🎯 改為直接擷取登入資訊套用，完全不經由文字框手動查詢
if (isset($_SESSION['name'])) {
    $login_session_name = $_SESSION['name'];
    
    // 拿登入的 Session 姓名直接去對名冊 members 表，撈出完整的世祖代房資訊
    $stmt = $conn->prepare("SELECT name, new_member, emperor_shizu, generation, number_of_houses FROM members WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $login_session_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $my_name = $row['name'];
        $my_member_id = $row['new_member'];
        $my_shizu = $row['emperor_shizu'];
        $my_gen = $row['generation'];
        $my_houses = $row['number_of_houses'];
        
        // 🚀 修改：補足缺少之「第」5代、「第」1大房
        $meta_init_text = "寄件者：{$my_name}， 編號：{$my_member_id}， 第{$my_shizu}世祖 第{$my_gen}代 第{$my_houses}大房。\n-----------------------------------\n";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌿 新增影音信箱郵件</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 850px; margin: 20px auto; padding: 10px; background-color: #f5f8f6; }
        .wish-card { background: white; padding: 25px; border-radius: 12px; border: 2px solid #a3c2a3; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .card-title { font-size: 1.5em; color: #2e5c2e; font-weight: bold; text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #a3c2a3; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        .meta-header { background: #eef7f0; padding: 14px; border-radius: 8px; font-size: 1.05em; color: #1e4620; font-weight: bold; margin-bottom: 20px; border: 1px solid #c2e2c5; text-align: center; }
        .highlight { color: #d97706; margin: 0 2px; font-size: 1.1em; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 6px; font-size: 1em; }
        
        /* 🚀 修改：郵件內文 / 留言描述 整個靠右對齊 */
        textarea { text-align:left; }

        .checkbox-zone { background: #fafafa; border: 1px solid #ddd; border-radius: 6px; /*padding: 12px;*/ max-height: 300px; overflow-y: auto; display: flex; flex-wrap: wrap; gap: 8px; }
        .checkbox-item { display: flex; align-items: center; background: white; padding: 5px 10px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.9em; cursor: pointer; }
        .checkbox-item input { margin-right: 5px; width: auto; }
        
        .ajax-container { position: relative; }
        .ajax-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #bbb; max-height: 160px; overflow-y: auto; z-index: 99; border-radius: 0 0 6px 6px; display: none; box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
        .ajax-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 0.95em; }
        .ajax-item:hover { background: #e8f4e8; }
        
        .rec-active { background-color: #ff4d4d !important; color: white; }
        video { width: 100%; max-height: 250px; background: #000; margin-top: 10px; display: none; border-radius: 6px; }
        .btn { padding: 10px 15px; cursor: pointer; border-radius: 6px; border: none; font-size: 0.95em; margin-right: 5px; }
        #pc_interface, #mobile_interface { display: none; }
    </style>
</head>
<body>

    <div class="wish-card">
        <div class="card-title">✉ 撰寫新留言</div>
        
        <form id="commentForm">
            <div class="meta-header">
                寄件者姓名：<span class="highlight"><?php echo htmlspecialchars($my_name); ?></span>， 
                編號：<span class="highlight"><?php echo htmlspecialchars($my_member_id); ?></span>， 
                第<span class="highlight"><?php echo htmlspecialchars($my_shizu); ?></span>世祖 
                第<span class="highlight"><?php echo htmlspecialchars($my_gen); ?></span>代 
                第<span class="highlight"><?php echo htmlspecialchars($my_houses); ?></span>大房
            </div>

            <input type="hidden" name="uploaded_name" value="<?php echo htmlspecialchars($my_name); ?>">
            <input type="hidden" name="uploaded_id" value="<?php echo htmlspecialchars($my_member_id); ?>">

            <div class="form-group ajax-container">
                <h2 factor="searchReceiver">🔍 1.單獨指定收件人(打姓名或編號關鍵字進行模糊篩選)：</h2>
                <input type="text" id="searchReceiver" placeholder="🔍 輸入關鍵字快速過濾人選...">
                <div id="receiverDropdown" class="ajax-dropdown"></div>
            </div>
            <p style="font-size:0.85em; color:#555; margin-bottom:4px; font-weight:bold;">👤 選中個人名單：</p>
                <div class="checkbox-zone" id="personalTargetZone">
                    <span style="color:#999; font-size:1em; padding:5px;">尚未經由上方搜尋加入特定個人...</span>
                </div>

            <div class="form-group">
                <h2>🎯 2.在下方勾選要寄送群組對象：</h2>
                
                <p style="font-size:0.85em; color:#555; margin-bottom:4px; font-weight:bold;">👥 依系統群組與大甲世代群組選擇：</p>
                
                <div class="checkbox-zone" style="margin-bottom:12px; overflow-y:visible;">
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="role:admin">🛡️ 管理者群組(admin)</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="role:user">👥 派下員群組(user)</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="role:clan">🍂 宗親群組(clan)</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="generation:6">🌱 大甲第 6 代群組</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="generation:7">🌱 大甲第 7 代群組</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="generation:8">🌱 大甲第 8 代群組</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="generation:9">🌱 大甲第 9 代群組</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="generation:10">🌱 大甲第 10 代群組</label>
                    <label class="checkbox-item"><input type="checkbox" name="targets[]" value="generation:11">🌱 大甲第 11 代群組</label>
                </div>

                
            </div>

            <div class="form-group">
                <h2 factor="description">✍️ 3. 郵件內文 / 留言描述：</h2>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($meta_init_text); ?></textarea>
                <div style="margin-top: 5px;">
                    <button type="button" id="voiceTypeBtn" class="btn" style="background:#e2e8f0;">🎤 語音輸入</button>
                    <button type="button" id="deviceTestBtn" class="btn" style="background:#d1fae5; color:#065f46; font-weight:bold;">⚙ 電腦影音檢測和功能測式</button>
                </div>
            </div>
            
            <div class="form-group" style="border: 1px dashed #a3c2a3; padding:10px; border-radius:6px;">
                <h2>🎬 4. 影音卡附件 (限時 5 分鐘內)：</h2>
                <div id="pc_interface">
                    <button type="button" id="startPCOne" class="btn" style="background:#2e5c2e; color:white;">📹 開始電腦錄影</button>
                    <button type="button" id="stopPCOne" class="btn" style="background:#ddd;" disabled>⏹ 停止</button>
                    <span id="pcTimer" style="color:red; font-weight:bold;"></span>
                </div>
                <div id="mobile_interface">
                    <p style="color: #2e5c2e; font-size: 0.85em; margin-bottom: 6px;">行動模式：選取將開啟手機錄影相機</p>
                    <input type="file" id="videoMobileFile" accept="video/*">
                </div>
                <div id="videoError" style="color:red; font-weight:bold; font-size:0.85em; margin-top:5px; display:none;">❌ 影片長度超過 5 分鐘，請重新錄製！</div>
                <video id="videoPlayback" controls playsinline></video>
            </div>

            <div class="form-group">
                <h2 factor="isPublic">是否公開此信件：</h2>
                <select id="isPublic" name="public">
                    <option value="1">私有 (僅收件對象可見)</option>
                    <option value="0">公開 (所有人可見)</option>

                </select>
            </div>
            
            <button type="submit" style="background-color: #2e5c2e; color: white; font-size: 1.1em; width: 100%; padding: 14px; border: none; border-radius: 6px; font-weight:bold;">📤 送出新留言</button>
        </form>
    </div>

    <script>
    // 裝置環境自適應
    const isMobile = /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
    if (isMobile) {
        document.getElementById('mobile_interface').style.display = 'block';
    } else {
        document.getElementById('pc_interface').style.display = 'block';
    }

    const MAX_DURATION = 300; 
    let finalVideoBlobOrFile = null; 

    // ==========================================
    // 🎯 收件人模糊搜尋機制 (AJAX)
    // ==========================================
    const searchReceiver = document.getElementById('searchReceiver');
    const receiverDropdown = document.getElementById('receiverDropdown');
    const personalTargetZone = document.getElementById('personalTargetZone');
    let addedUserIds = new Set(); // 記錄已被加入過清單的成員編號，避免重複加入

    searchReceiver.addEventListener('input', async function() {
        const val = this.value.trim();
        if (val.length === 0) { receiverDropdown.style.display = 'none'; return; }

        const response = await fetch(`voicemail.php?action=search_receivers&term=${encodeURIComponent(val)}`);
        const data = await response.json();
        receiverDropdown.innerHTML = '';

        if (data.length > 0) {
            data.forEach(m => {
                const item = document.createElement('div');
                item.className = 'ajax-item';
                item.innerText = `👤 ${m.name} (${m.new_member}號) - 大甲${m.gen}代 / ${m.houses}房`;
                
                item.addEventListener('click', () => {
                    // 清除一開始的「尚未加入」預設文字
                    if (addedUserIds.size === 0) personalTargetZone.innerHTML = '';

                    if (!addedUserIds.has(m.new_member)) {
                        addedUserIds.add(m.new_member);
                        
                        // 動態長出一個帶有打勾狀態的新 Checkbox
                        const label = document.createElement('label');
                        label.className = 'checkbox-item';
                        label.innerHTML = `<input type="checkbox" name="targets[]" value="user:${m.new_member}" checked> 👤 ${m.name} (${m.new_member}號)`;
                        personalTargetZone.appendChild(label);
                    } else {
                        alert("此對象已存在於下方的選取名單清單中了。");
                    }

                    searchReceiver.value = '';
                    receiverDropdown.style.display = 'none';
                });
                receiverDropdown.appendChild(item);
            });
            receiverDropdown.style.display = 'block';
        } else {
            receiverDropdown.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!searchReceiver.contains(e.target) && !receiverDropdown.contains(e.target)) receiverDropdown.style.display = 'none';
    });

    // ==========================================
    // 錄影、語音轉文字、表單一條龍群發
    // ==========================================
    let pcRecorder; let pcChunks = []; let pcCountdown;
    document.getElementById('startPCOne').addEventListener('click', async () => {
        pcChunks = [];
        const videoPlayback = document.getElementById('videoPlayback');
        document.getElementById('videoError').style.display = 'none';
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            videoPlayback.srcObject = stream; videoPlayback.muted = true; videoPlayback.style.display = 'block'; videoPlayback.play();
            let options = { mimeType: 'video/webm;codecs=vp9,opus' };
            if (!MediaRecorder.isTypeSupported(options.mimeType)) options = { mimeType: 'video/mp4' };

            pcRecorder = new MediaRecorder(stream, options);
            pcRecorder.ondataavailable = e => { if (e.data.size > 0) pcChunks.push(e.data); };
            pcRecorder.onstop = () => {
                stream.getTracks().forEach(track => track.stop());
                videoPlayback.srcObject = null; videoPlayback.muted = false;
                finalVideoBlobOrFile = new Blob(pcChunks, { type: pcRecorder.mimeType });
                videoPlayback.src = URL.createObjectURL(finalVideoBlobOrFile);
            };
            pcRecorder.start();
            document.getElementById('startPCOne').disabled = true; document.getElementById('startPCOne').classList.add('rec-active');
            document.getElementById('stopPCOne').disabled = false;

            let timeLeft = MAX_DURATION;
            pcCountdown = setInterval(() => {
                timeLeft--;
                let mins = Math.floor(timeLeft / 60); let secs = timeLeft % 60;
                document.getElementById('pcTimer').innerText = `剩餘：${mins}:${secs < 10 ? '0' : ''}${secs}`;
                if (timeLeft <= 0) { clearInterval(pcCountdown); document.getElementById('stopPCOne').click(); }
            }, 1000);
        } catch (err) { alert("PC 鏡頭受限，走 80 Port 本機測試建議改用手機連入錄製。"); }
    });

    document.getElementById('stopPCOne').addEventListener('click', () => {
        if (pcRecorder && pcRecorder.state !== "inactive") {
            clearInterval(pcCountdown); pcRecorder.stop();
            document.getElementById('startPCOne').disabled = false; document.getElementById('startPCOne').classList.remove('rec-active');
            document.getElementById('stopPCOne').disabled = true; document.getElementById('pcTimer').innerText = "";
        }
    });

    document.getElementById('videoMobileFile').addEventListener('change', function(e) {
        const file = e.target.files[0]; const videoPlayback = document.getElementById('videoPlayback'); const errorDiv = document.getElementById('videoError');
        if (!file) return;
        videoPlayback.src = URL.createObjectURL(file); videoPlayback.style.display = 'block';
        finalVideoBlobOrFile = file; errorDiv.style.display = 'none';
        videoPlayback.onloadedmetadata = function() {
            if (!isNaN(videoPlayback.duration) && isFinite(videoPlayback.duration)) {
                if (videoPlayback.duration > MAX_DURATION) {
                    errorDiv.style.display = 'block'; finalVideoBlobOrFile = null; document.getElementById('videoMobileFile').value = '';
                    alert("長度不能超過 5 分鐘！");
                }
            }
        };
    });

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        const recognition = new SpeechRecognition(); recognition.lang = 'zh-TW';
        document.getElementById('voiceTypeBtn').addEventListener('click', () => recognition.start());
        recognition.onresult = (e) => document.getElementById('description').value += e.results[0][0].transcript;
    } else { document.getElementById('voiceTypeBtn').style.display = 'none'; }

    // 🚀 電腦影音檢測按鈕預留事件點擊處理
    document.getElementById('deviceTestBtn').addEventListener('click', () => {
        alert("正在啟動多媒體設備檢測調試環境，請確保瀏覽器鏡頭與麥克風權限已允許。");
    });

    // 表單非同步打包發送
    document.getElementById('commentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // 檢查是否有勾選對象
        const checkedTargets = document.querySelectorAll('input[name="targets[]"]:checked');
        if (checkedTargets.length === 0) {
            alert("請至少選擇或加選一個收件對象群組！"); return;
        }

        if (!finalVideoBlobOrFile) {
            alert("請附加您錄製影音檔案！"); return;
        }

        const formData = new FormData(document.getElementById('commentForm'));
        if (!isMobile) {
            const ext = pcRecorder.mimeType.includes('mp4') ? 'mp4' : 'webm';
            formData.append('video_file', finalVideoBlobOrFile, `pc_video.${ext}`);
        } else {
            formData.append('video_file', finalVideoBlobOrFile);
        }

        try {
            const response = await fetch('voicemail.php', { method: 'POST', body: formData });
            if (response.ok) {
                const result = await response.text();
                alert(result);
                if (result.includes('成功')) location.reload();
            } else {
                alert("伺服器回應錯誤，請確認後端資料庫容量限制。");
            }
        } catch (error) { alert("發送系統異常！"); }
    });
    </script>
</body>
</html>