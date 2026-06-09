<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

// 🔐 安全檢查
if (!isset($_SESSION['name'])) {
    die("【權限不足】您尚未登入系統，請先登入後再前來查看信箱。");
}

$login_session_name = $_SESSION['name'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("資料庫連線失敗: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// 1. 擷取登入者本人名冊資訊 (編號/世代/大房)
$my_id = ""; $my_gen = ""; $my_houses = ""; $my_role = "無角色";
$stmt_mem = $conn->prepare("SELECT new_member, generation, number_of_houses FROM members WHERE name = ? LIMIT 1");
$stmt_mem->bind_param("s", $login_session_name);
$stmt_mem->execute();
$res_mem = $stmt_mem->get_result();
if ($row_m = $res_mem->fetch_assoc()) {
    $my_id = $row_m['new_member'];
    $my_gen = $row_m['generation'];
    $my_houses = $row_m['number_of_houses'];
}
$stmt_mem->close();

// 2. 查帳號角色並轉換為中文
$stmt_acc = $conn->prepare("SELECT role FROM account WHERE name = ? LIMIT 1");
$stmt_acc->bind_param("s", $login_session_name);
$stmt_acc->execute();
$res_acc = $stmt_acc->get_result();
if ($row_a = $res_acc->fetch_assoc()) {
    $role_map = ['admin' => '管理者', 'user' => '派下員', 'clan' => '宗親'];
    $raw_role = $row_a['role'];
    $my_role = isset($role_map[$raw_role]) ? $role_map[$raw_role] : $raw_role;
}
$stmt_acc->close();

// 3. 撈取寄給我的信件
// 注意：role 比對需用原始英文代號
$raw_role_val = array_search($my_role, $role_map) ?: $my_role; 
$sql_mail = "SELECT m.id as msg_id, m.is_read, m.created_at, m.from_name, f.description, f.file_url, f.file_type
             FROM messages m
             LEFT JOIN files f ON m.file_id = f.file_id
             WHERE 
                (m.to_type = 'user' AND m.to_target = ?) OR
                (m.to_type = 'generation' AND m.to_target = ?) OR
                (m.to_type = 'houses' AND m.to_target = ?) OR
                (m.to_type = 'role' AND m.to_target = ?)
             ORDER BY m.created_at DESC";

$stmt_mail = $conn->prepare($sql_mail);
$stmt_mail->bind_param("ssss", $my_id, $my_gen, $my_houses, $raw_role_val);
$stmt_mail->execute();
$result_mail = $stmt_mail->get_result();

$mail_list = [];
$msg_ids_to_update = [];
while ($row = $result_mail->fetch_assoc()) {
    $mail_list[] = $row;
    if ($row['is_read'] == 0) $msg_ids_to_update[] = $row['msg_id'];
}
$stmt_mail->close();

// 4. 自動標記已讀
if (!empty($msg_ids_to_update)) {
    $ids_string = implode(',', array_map('intval', $msg_ids_to_update));
    $conn->query("UPDATE messages SET is_read = 1 WHERE id IN ($ids_string)");
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✉ 個人郵件信箱</title>
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; max-width: 700px; margin: 20px auto; padding: 10px; background-color: #f4f6f4; }
        .mailbox-container { background: white; padding: 25px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .mailbox-title { font-size: 1.4em; color: #1e3a1e; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #2e5c2e; padding-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .back-home { font-size: 0.6em; background: #2e5c2e; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; }
        .mail-card { background: #fafafa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 15px; }
        .mail-header { display: flex; justify-content: space-between; font-size: 0.9em; color: #64748b; border-bottom: 1px dashed #e2e8f0; padding-bottom: 6px; margin-bottom: 10px; }
        .sender-name { font-weight: bold; color: #2e5c2e; font-size: 1.05em; }
        .mail-body { font-size: 1em; line-height: 1.6; white-space: pre-wrap; color: #1e293b; margin-bottom: 12px; }
        video { width: 100%; max-height: 250px; background: #000; border-radius: 6px; }
        .new-badge { background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75em; margin-left: 5px; }
        .no-mail { text-align: center; color: #94a3b8; padding: 40px 10px; }
        .meta-info { font-size: 0.85em; background: #edf2f7; padding: 8px 12px; border-radius: 6px; margin-bottom: 15px; color: #4a5568; }
    </style>
</head>
<body>
    <div class="mailbox-container">
        <div class="mailbox-title">
            <span>✉ 您的個人專屬信箱</span>
            <a href="home.html" class="back-home">➔ 回到首頁</a>
        </div>
        <div class="meta-info">
            👤 <b>當前收信人：</b><?php echo htmlspecialchars($login_session_name); ?> 
            (編號: <?php echo htmlspecialchars($my_id); ?> | 大甲第 <?php echo htmlspecialchars($my_gen); ?> 代 | 第<?php echo htmlspecialchars($my_houses); ?>大房 | 角色: <?php echo htmlspecialchars($my_role); ?>)
        </div>

        <?php if (empty($mail_list)): ?>
            <div class="no-mail">📭 目前您的信箱內沒有任何留給您的訊息。</div>
        <?php else: ?>
            <?php foreach ($mail_list as $mail): ?>
                <div class="mail-card">
                    <div class="mail-header">
                        <div>
                            來自：<span class="sender-name"><?php echo htmlspecialchars($mail['from_name']); ?></span>
                            <?php if ($mail['is_read'] == 0): ?><span class="new-badge">新留言</span><?php endif; ?>
                        </div>
                        <div><?php echo $mail['created_at']; ?></div>
                    </div>
                    <div class="mail-body"><?php echo htmlspecialchars($mail['description']); ?></div>
                    <?php if (!empty($mail['file_url'])): ?>
                        <div class="mail-media"><video src="<?php echo htmlspecialchars($mail['file_url']); ?>" controls playsinline></video></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>