<?php
session_start();

// ==========================================
// 1. 資料庫連線設定與角色/會員號查詢
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

$role_title = "";  
$member_no = "";   
$user_role = "";   
$display_name = ""; 

if (isset($_SESSION['name'])) {
    $current_user = $_SESSION['name'];
    
    $stmt = $conn->prepare("SELECT role, new_member FROM account WHERE name = ?");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $member_no = !empty($row['new_member']) ? $row['new_member'] : "";
        $user_role = $row['role']; 
        
        switch ($row['role']) {
            case 'admin': $role_title = "（管理者）"; break;
            case 'user':  $role_title = "（派下員）"; break;
            case 'clan':  $role_title = "（宗親）"; break;
            default:      $role_title = ""; break;
        }
        $display_name = $current_user . $role_title;
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
    <title>語音與錄影留言系統</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 10px; }
        .form-group { margin-bottom: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; }
        .rec-active { background-color: #ff4d4d; color: white; }
        input:read-only { background-color: #f0f0f0; color: #555; cursor: not-allowed; }
        video, audio { width: 100%; max-height: 300px; background: #000; margin-top: 10px; }
        .timer { color: red; font-weight: bold; margin-left: 10px; }
    </style>
</head>
<body>

    <h2>發表語音/錄影留言</h2>
    
    <form id="commentForm">
        <div class="form-group">
            <label for="uploadedName">您的姓名：</label>
            <input type="text" id="uploadedName" name="uploaded_name" 
                   value="<?php echo htmlspecialchars($display_name); ?>" 
                   <?php echo !empty($display_name) ? 'readonly' : ''; ?> required>
        </div>

        <div class="form-group">
            <label for="description">留言內容 / 檔案描述：</label>
            <textarea id="description" name="description" rows="3" placeholder="請輸入留言..."></textarea>
            <div style="margin-top: 5px;">
                <button type="button" id="voiceTypeBtn">🎤 語音輸入 (講話變文字)</button>
            </div>
        </div>
        
        <div class="form-group">
            <label>🎬 影片檔案附件 (限時 5 分鐘)：</label>
            <div>
                <button type="button" id="startVideoRec">📹 開啟鏡頭並錄影</button>
                <button type="button" id="stopVideoRec" disabled>⏹ 停止錄影</button>
                <span id="videoTimer" class="timer"></span>
            </div>
            <video id="videoPlayback" controls playsinline></video>
        </div>

        <div class="form-group">
            <label for="isPublic">是否公開留言：</label>
            <select id="isPublic" name="public">
                <option value="1">公開</option>
                <option value="0">私有</option>
            </select>
        </div>
        
        <button type="submit" style="background-color: #4CAF50; color: white; font-size: 1.1em; width: 100%; padding: 12px;">送出留言與影音檔</button>
    </form>

    <script>
    // 語音轉文字變數
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    // 錄影相關變數
    let videoRecorder;
    let videoChunks = [];
    let videoBlob = null;
    let videoCountdown;
    const MAX_RECORD_TIME = 300; // 5分鐘 = 300秒

    // ==========================================
    // 1. 語音轉文字 (Speech-to-Text)
    // ==========================================
    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.lang = 'zh-TW';
        document.getElementById('voiceTypeBtn').addEventListener('click', () => recognition.start());
        recognition.onresult = (e) => document.getElementById('description').value += e.results[0][0].transcript;
    } else {
        document.getElementById('voiceTypeBtn').disabled = true;
    }

    // ==========================================
    // 2. 網頁直接錄影功能 (限時 5 分鐘)
    // ==========================================
    document.getElementById('startVideoRec').addEventListener('click', async () => {
        videoChunks = [];
        try {
            // 同時請求鏡頭與麥克風權限 (手機上會自動喚起前後鏡頭)
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { width: 640, height: 480, facingMode: "user" }, // user=前鏡頭, environment=後鏡頭
                audio: true 
            });
            
            // 將即時鏡頭畫面同步顯示在預覽視窗上
            const videoPlayback = document.getElementById('videoPlayback');
            videoPlayback.srcObject = stream;
            videoPlayback.muted = true; // 預覽時靜音避免回授音
            videoPlayback.play();

            // 設定錄影編碼格式 (優先使用 mp4 或 webm)
            let options = { mimeType: 'video/webm;codecs=vp9,opus' };
            if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                options = { mimeType: 'video/mp4' };
            }

            videoRecorder = new MediaRecorder(stream, options);
            
            videoRecorder.ondataavailable = e => {
                if (e.data.size > 0) videoChunks.push(e.data);
            };

            // 錄影停止時的處理
            videoRecorder.onstop = () => {
                // 停止所有硬體鏡頭串流
                stream.getTracks().forEach(track => track.stop());
                videoPlayback.srcObject = null;
                videoPlayback.muted = false;

                // 打包成影片檔
                videoBlob = new Blob(videoChunks, { type: videoRecorder.mimeType });
                
                // 將錄好的影片塞入播放器，讓使用者可以直接看影音結果
                videoPlayback.src = URL.createObjectURL(videoBlob);
            };

            // 開始錄影
            videoRecorder.start();
            document.getElementById('startVideoRec').disabled = true;
            document.getElementById('startVideoRec').classList.add('rec-active');
            document.getElementById('stopVideoRec').disabled = false;

            // --- 5分鐘計時器邏輯 ---
            let timeLeft = MAX_RECORD_TIME;
            document.getElementById('videoTimer').innerText = `剩餘時間：5:00`;
            
            videoCountdown = setInterval(() => {
                timeLeft--;
                let mins = Math.floor(timeLeft / 60);
                let secs = timeLeft % 60;
                document.getElementById('videoTimer').innerText = `剩餘時間：${mins}:${secs < 10 ? '0' : ''}${secs}`;
                
                // 時間到，自動觸發停止錄影
                if (timeLeft <= 0) {
                    clearInterval(videoCountdown);
                    document.getElementById('stopVideoRec').click();
                    alert('已達到 5 分鐘錄影上限，錄影自動結束。');
                }
            }, 1000);

        } catch (err) {
            console.error(err);
            alert("無法開啟鏡頭。請確認是否開啟 HTTPS 環境及麥克風鏡頭權限。");
        }
    });

    // 停止錄影按鈕
    document.getElementById('stopVideoRec').addEventListener('click', () => {
        if (videoRecorder && videoRecorder.state !== "inactive") {
            clearInterval(videoCountdown);
            videoRecorder.stop();
            document.getElementById('startVideoRec').disabled = false;
            document.getElementById('startVideoRec').classList.remove('rec-active');
            document.getElementById('stopVideoRec').disabled = true;
            document.getElementById('videoTimer').innerText = "錄影完成";
        }
    });

    // ==========================================
    // 3. 非同步上傳至 save_file.php
    // ==========================================
    document.getElementById('commentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(document.getElementById('commentForm'));
        formData.append('uploaded_id', '<?php echo htmlspecialchars($member_no); ?>'); 
        formData.append('reference_id', '100'); 

        // 如果有錄製影片，將影片二進位檔案打包塞入
        if (videoBlob) {
            // 根據錄製格式決定副檔名
            const ext = videoRecorder.mimeType.includes('mp4') ? 'mp4' : 'webm';
            formData.append('video_file', videoBlob, `video_comment.${ext}`);
        } else {
            alert("提示：未錄製影片，將僅傳送純文字。");
        }

        try {
            const response = await fetch('save_file.php', { method: 'POST', body: formData });
            if (response.ok) {
                const result = await response.text();
                alert("伺服器回應：" + result);
            } else {
                alert("上傳失敗，可能超過伺服器單一檔案上傳大小限制(post_max_size)。");
            }
        } catch (error) {
            alert("傳送出錯！");
        }
    });
    </script>
</body>
</html>