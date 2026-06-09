<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>語音留言系統 - voicemail.php</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], textarea, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; cursor: pointer; margin-right: 5px; }
        .rec-active { background-color: #ff4d4d; color: white; }
    </style>
</head>
<body>

    <h2>發表語音留言 (voicemail.php)</h2>
    
    <form id="commentForm">
        <div class="form-group">
            <label for="uploadedName">您的姓名：</label>
            <input type="text" id="uploadedName" name="uploaded_name" placeholder="請輸入姓名" required>
        </div>

        <div class="form-group">
            <label for="description">留言內容 / 檔案描述：</label>
            <textarea id="description" name="description" rows="4" placeholder="請輸入留言，或使用下方的語音輸入功能..."></textarea>
            <div style="margin-top: 5px;">
                <button type="button" id="voiceTypeBtn">🎤 語音輸入 (講話變文字)</button>
            </div>
        </div>
        
        <div class="form-group">
            <label>語音檔案附件：</label>
            <div>
                <button type="button" id="startRec">🔴 開始錄音</button>
                <button type="button" id="stopRec" disabled>⏹ 停止錄音</button>
                <span id="recStatus" style="color: blue; margin-left: 10px;"></span>
            </div>
            <div style="margin-top: 10px;">
                <label style="font-size: 0.9em; color: #555;">錄音試聽：</label>
                <audio id="audioPlayback" controls></audio>
            </div>
        </div>

        <div class="form-group">
            <label for="isPublic">是否公開留言：</label>
            <select id="isPublic" name="public">
                <option value="1">公開 (所有人可見)</option>
                <option value="0">私有 (僅管理員可見)</option>
            </select>
        </div>
        
        <hr>
        <button type="submit" style="background-color: #4CAF50; color: white; font-size: 1.1em; width: 100%;">送出留言與語音檔</button>
    </form>

    <script>
    let mediaRecorder;
    let audioChunks = [];
    let audioBlob = null;

    // =========================================================================
    // 【A 部分：語音轉文字 (Web Speech API)】
    // 作用：把使用者的聲音「即時辨識成中文字」，直接填入 textarea 輸入框中。
    // =========================================================================
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.lang = 'zh-TW'; // 設定語系為台灣中文
        recognition.interimResults = false;
        
        document.getElementById('voiceTypeBtn').addEventListener('click', () => {
            recognition.start(); // 啟動瀏覽器語音辨識
            document.getElementById('recStatus').innerText = "聆聽辨識中，請說話...";
        });

        // 辨識成功後回傳文字結果
        recognition.onresult = (event) => {
            const resultText = event.results[0][0].transcript;
            // 將辨識出來的文字，塞進「檔案描述 (description)」這個欄位中
            document.getElementById('description').value += resultText;
        };
        
        recognition.onend = () => {
            document.getElementById('recStatus').innerText = "語音辨識結束";
            setTimeout(() => { document.getElementById('recStatus').innerText = ""; }, 2000);
        };
    } else {
        const btn = document.getElementById('voiceTypeBtn');
        btn.disabled = true;
        btn.innerText = "❌ 瀏覽器不支援語音轉文字";
    }


    // =========================================================================
    // 【B 部分：語音錄音 (MediaRecorder API)】
    // 作用：錄製實體語音檔案，並在網頁內建立音訊 Blob，供播放器試聽與當作附件上傳。
    // =========================================================================
    document.getElementById('startRec').addEventListener('click', async () => {
        audioChunks = [];
        try {
            // 請求麥克風權限
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            // 初始化錄音工具
            mediaRecorder = new MediaRecorder(stream);
            
            // 錄音中...持續把聲音碎片（Chunks）推入陣列儲存
            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) audioChunks.push(event.data);
            };

            // 當錄音停止時
            mediaRecorder.onstop = () => {
                // 將所有聲音碎片打包成一個真正的語音實體檔案 (Blob)
                audioBlob = new Blob(audioChunks, { type: 'audio/mp3' }); 
                
                // 產生一個瀏覽器內部的臨時 URL，讓 <audio> 標籤可以直接試聽
                const audioUrl = URL.createObjectURL(audioBlob);
                document.getElementById('audioPlayback').src = audioUrl; 
            };

            mediaRecorder.start(); // 開始錄音
            document.getElementById('startRec').disabled = true;
            document.getElementById('startRec').classList.add('rec-active');
            document.getElementById('stopRec').disabled = false;
            document.getElementById('recStatus').innerText = "🔴 麥克風錄音中...";
        } catch (err) {
            alert("無法存取麥克風，請檢查權限或確認是否在 HTTPS 環境下。");
        }
    });

    document.getElementById('stopRec').addEventListener('click', () => {
        if (mediaRecorder && mediaRecorder.state !== "inactive") {
            mediaRecorder.stop(); // 停止錄音
            mediaRecorder.stream.getTracks().forEach(track => track.stop()); // 關閉麥克風硬體
            
            document.getElementById('startRec').disabled = false;
            document.getElementById('startRec').classList.remove('rec-active');
            document.getElementById('stopRec').disabled = true;
            document.getElementById('recStatus').innerText = "⏹ 錄音已完成";
        }
    });


    // =========================================================================
    // 【C 部分：資料打包與非同步上傳】
    // 作用：將前面兩部分的結果（文字與錄音檔）與表單資料結合，一併送往後端 PHP。
    // =========================================================================
    document.getElementById('commentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(document.getElementById('commentForm'));
        formData.append('uploaded_id', 'USER_12345'); 
        formData.append('reference_id', '100');       

        // 如果【B 部分】有成功產生錄音檔 (audioBlob)
        if (audioBlob) {
            // 將這個語音檔案塞入 FormData 中，傳給後端對應檔案欄位
            formData.append('audio_file', audioBlob, 'voice_comment.mp3');
        } else {
            alert("提示：您尚未錄製語音附件，將僅送出文字資料。");
        }

        try {
            const response = await fetch('save_file.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.text();
                alert("伺服器回應：" + result);
            } else {
                alert("系統連線錯誤，請稍後再試。");
            }
        } catch (error) {
            console.error("Error:", error);
            alert("傳送失敗！");
        }
    });
    </script>
</body>
</html>