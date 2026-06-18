<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['new_member'])) { header("Location: login.php"); exit; }
require_once 'api_account-members.php';

// AJAX 請求處理
if (isset($_GET['action']) && $_GET['action'] == 'get_history') {
    $field = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['field']);
    $stmt = $conn->prepare("SELECT DISTINCT $field FROM dev_tracking WHERE $field IS NOT NULL AND $field != '' LIMIT 20");
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while($row = $res->fetch_row()) $data[] = $row[0];
    echo json_encode($data); exit;
}

// 處理新增、修改、刪除
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_task') {
        $stmt = $conn->prepare("INSERT INTO dev_tracking (creator_member_id, ai_url, `references`, dev_note, dev_note1, dev_note2, dev_note3, dev_note4, dev_note5, dev_note6, dev_note7, dev_note8, dev_note9, dev_note10, project_name_zh, project_name_en, status, dev_start_at, dev_end_at, course_name_zh, skill_category, technology_name, teacher_name, skill_practiced) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssssssssssssssss", $_SESSION['new_member'], $_POST['ai_url'], $_POST['references'], $_POST['dev_note'], $_POST['dev_note1'], $_POST['dev_note2'], $_POST['dev_note3'], $_POST['dev_note4'], $_POST['dev_note5'], $_POST['dev_note6'], $_POST['dev_note7'], $_POST['dev_note8'], $_POST['dev_note9'], $_POST['dev_note10'], $_POST['project_name_zh'], $_POST['project_name_en'], $_POST['status'], $_POST['dev_start_at'], $_POST['dev_end_at'], $_POST['course_name_zh'], $_POST['skill_category'], $_POST['technology_name'], $_POST['teacher_name'], $_POST['skill_practiced']);
        $stmt->execute();
    } elseif ($_POST['action'] == 'edit_task' && !empty($_POST['id'])) {
        $stmt = $conn->prepare("UPDATE dev_tracking SET ai_url=?, `references`=?, dev_note=?, dev_note1=?, dev_note2=?, dev_note3=?, dev_note4=?, dev_note5=?, dev_note6=?, dev_note7=?, dev_note8=?, dev_note9=?, dev_note10=?, project_name_zh=?, project_name_en=?, status=?, dev_start_at=?, dev_end_at=?, course_name_zh=?, skill_category=?, technology_name=?, teacher_name=?, skill_practiced=? WHERE id=? AND creator_member_id=?");
        $stmt->bind_param("sssssssssssssssssssssssii", $_POST['ai_url'], $_POST['references'], $_POST['dev_note'], $_POST['dev_note1'], $_POST['dev_note2'], $_POST['dev_note3'], $_POST['dev_note4'], $_POST['dev_note5'], $_POST['dev_note6'], $_POST['dev_note7'], $_POST['dev_note8'], $_POST['dev_note9'], $_POST['dev_note10'], $_POST['project_name_zh'], $_POST['project_name_en'], $_POST['status'], $_POST['dev_start_at'], $_POST['dev_end_at'], $_POST['course_name_zh'], $_POST['skill_category'], $_POST['technology_name'], $_POST['teacher_name'], $_POST['skill_practiced'], $_POST['id'], $_SESSION['new_member']);
        $stmt->execute();
    } elseif ($_POST['action'] == 'delete_task' && !empty($_POST['id'])) {
        $stmt = $conn->prepare("DELETE FROM dev_tracking WHERE id=? AND creator_member_id=?");
        $stmt->bind_param("is", $_POST['id'], $_SESSION['new_member']);
        $stmt->execute();
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

$result = $conn->query("SELECT * FROM dev_tracking ORDER BY id DESC");
$edit_data = [];
$res_edit = $conn->query("SELECT * FROM dev_tracking");
while($row_edit = $res_edit->fetch_assoc()) { $edit_data[$row_edit['id']] = $row_edit; }
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>開發進度追蹤</title>
    <style>
        .container { max-width: 1100px; margin: auto; padding-bottom: 80px; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .full-width { grid-column: span 3; }
        label { display: block; font-weight: bold; margin-top: 10px; font-size: 0.9em; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; box-sizing: border-box; }
        textarea { height: 60px; white-space: pre-wrap; }
        details { border: 1px solid #ccc; padding: 10px; margin-top: 10px; background: #f9f9f9; }
        summary { font-weight: bold; cursor: pointer; padding: 5px; }
        .fixed-submit-btn { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 15px 50px; background: #000; color: #fff; border: none; cursor: pointer; font-size: 18px; border-radius: 50px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 1000; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        .btn-action { padding: 5px 10px; cursor: pointer; border-radius: 4px; border: 1px solid #999; }
        .btn-edit { background-color: #e6f7ff; }
        .btn-delete { background-color: #fff1f0; }
    </style>
</head>
<body>
    <div class="container">
        <p style="font-size: 20px; text-align: center; font-weight: bold; color: darkblue;">開發進度追蹤</p>
        <form method="POST" id="trackingForm">
            <input type="hidden" name="action" id="formAction" value="add_task">
            <input type="hidden" name="id" id="formId" value="">
            <div class="form-grid">
                <div><label>名稱(中)</label><input type="text" name="project_name_zh" id="project_name_zh" class="ajax-field"></div>
                <div><label>檔名(中/英)</label><input type="text" name="project_name_en" id="project_name_en" class="ajax-field"></div>
                <div><label>狀態</label>
                    <select name="status" id="status">
                        <option value="待辦事項">待辦事項</option><option value="進行中">進行中</option>
                        <option value="測試中">測試中</option><option value="已完成">已完成</option>
                    </select>
                </div>
                <div><label>開始時間</label><input type="datetime-local" name="dev_start_at" id="dev_start_at"></div>
                <div><label>開發-迄</label><input type="datetime-local" name="dev_end_at" id="dev_end_at"></div>
                <div><label>指導老師</label><input type="text" name="teacher_name" id="teacher_name" class="ajax-field"></div>
                <div><label>課程名稱</label><input type="text" name="course_name_zh" id="course_name_zh" class="ajax-field"></div>
                <div><label>技能領域</label><input type="text" name="skill_category" id="skill_category" class="ajax-field"></div>
                <div><label>技術名稱</label><input type="text" name="technology_name" id="technology_name" class="ajax-field"></div>
                <div class="full-width"><label>練習技能點</label><textarea name="skill_practiced" id="skill_practiced" class="ajax-field"></textarea></div>
                <div class="full-width"><label>參考文獻</label><textarea name="references" id="references" class="ajax-field"></textarea></div>
                <div class="full-width"><label>開發筆記</label><textarea name="dev_note" id="dev_note" class="ajax-field"></textarea></div>
                <div class="full-width"><label>AI 開發網址</label><textarea name="ai_url" id="ai_url" class="ajax-field"></textarea></div>
                <div class="full-width"><details><summary>詳細筆記(1-10)</summary>
                    <?php for ($i=1;$i<=10;$i++): ?><label>筆記 <?php echo $i; ?></label><textarea name="dev_note<?php echo $i; ?>" id="dev_note<?php echo $i; ?>" class="ajax-field"></textarea><?php endfor; ?>
                </details></div>
            </div>
        </form>
        <button type="submit" form="trackingForm" class="fixed-submit-btn">儲存紀錄</button>

        <table>
            <tr>
                <th>修改與刪除</th>
                <th>名稱(中)2</th>
                <th>檔名(中/英)2</th>
                <th>課程名稱</th>
                <th>AI開發網址</th>
                <th>練習技能點</th>
                <th>參考文獻</th>
                <th>開發筆記</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>
                    <button type="button" class="btn-action btn-edit" onclick="editRow(<?php echo $row['id']; ?>)">修改</button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('確定要刪除？');">
                        <input type="hidden" name="action" value="delete_task"><input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn-action btn-delete">刪除</button>
                    </form>
                </td>
                <td><?php echo htmlspecialchars($row['project_name_zh']); ?></td>
                <td><?php echo htmlspecialchars($row['project_name_en']); ?></td>
                <td><?php echo htmlspecialchars($row['course_name_zh']); ?></td>
                <td><button onclick="showContentInWindow('AI 開發網址', `<?php echo addslashes(str_replace('`', '\`', $row['ai_url'])); ?>`)">檢視</button></td>
                <td><button onclick="showContentInWindow('練習技能點', `<?php echo addslashes(str_replace('`', '\`', $row['skill_practiced'])); ?>`)">檢視</button></td>
                <td><button onclick="showContentInWindow('參考文獻', `<?php echo addslashes(str_replace('`', '\`', $row['references'])); ?>`)">檢視</button></td>
                <td><button onclick="showContentInWindow('開發筆記', `<?php echo addslashes(str_replace('`', '\`', $row['dev_note'])); ?>`)">檢視</button></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <datalist id="ajax-list"></datalist>
    <script>
        // 設定開始時間為當前時間
        window.onload = function() {
            const now = new Date();
            // 轉為 YYYY-MM-DDThh:mm 格式
            const formatted = now.getFullYear() + '-' + 
                              String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(now.getDate()).padStart(2, '0') + 'T' + 
                              String(now.getHours()).padStart(2, '0') + ':' + 
                              String(now.getMinutes()).padStart(2, '0');
            document.getElementById('dev_start_at').value = formatted;
        };

        const editData = <?php echo json_encode($edit_data); ?>;
        function editRow(id) {
            const data = editData[id];
            if (!data) return;
            document.getElementById('formAction').value = 'edit_task';
            document.getElementById('formId').value = id;
            for (let key in data) {
                let el = document.getElementById(key);
                if (el) el.value = data[key];
            }
            if(data.dev_start_at) document.getElementById('dev_start_at').value = data.dev_start_at.replace(' ', 'T').substring(0, 16);
            if(data.dev_end_at) document.getElementById('dev_end_at').value = data.dev_end_at.replace(' ', 'T').substring(0, 16);
            document.querySelector('.fixed-submit-btn').innerText = "更新紀錄";
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function showContentInWindow(title, content) {
            let formattedContent = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>').replace(/\n/g, '<br>');
            let win = window.open("", "_blank", "width=600,height=400");
            win.document.write(`<html><body style="padding:20px; font-family:sans-serif;"><h3>${title}</h3><div>${formattedContent}</div><hr><button onclick="window.close()">關閉</button></body></html>`);
            win.document.close();
        }
        document.querySelectorAll('.ajax-field').forEach(el => {
            el.setAttribute('list', 'ajax-list');
            el.addEventListener('focus', function() {
                fetch('?action=get_history&field=' + this.name)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('ajax-list').innerHTML = data.map(item => `<option value="${item.replace(/"/g, '&quot;')}">`).join('');
                });
            });
        });
    </script>
</body>
</html>