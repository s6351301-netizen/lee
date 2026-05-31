<?php
/**
 * 渤海堂高氏宗親會 - 派下員與資產管理系統 (單頁進階版)
 * 特色：物件導向、動態樹狀結構、權限防護、檔案型資料庫、CSV 匯出、CSRF 防禦、行為審計日誌。
 */

// 1. 全域初始化與安全防護
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Taipei');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 產生 CSRF Token 防禦安全漏洞
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. 模擬系統資料庫 (實務上可串接檔案或資料庫，此處使用 Session 保持單頁操作狀態)
if (!isset($_SESSION['initialized'])) {
    // 預設帳號密碼 (管理員 & 唯讀會員)
    $_SESSION['users'] = [
        'admin' => ['password' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin', 'name' => '高大明 (理事長)'],
        'member' => ['password' => password_hash('member123', PASSWORD_DEFAULT), 'role' => 'viewer', 'name' => '高小華 (宗親族人)']
    ];

    // 宗族樹狀階梯結構資料庫 (使用 Adjacency List Model)
    $_SESSION['clan_tree'] = [
        ['id' => 1, 'parent_id' => 0, 'name' => '高氏渡台始祖 (安溪公)', 'type' => 'root', 'detail' => '清乾隆年間渡海來台，開基始祖。'],
        ['id' => 2, 'parent_id' => 1, 'name' => '大房 (高文章派下)', 'type' => 'branch', 'detail' => '主要北部大直、內湖一帶發展。'],
        ['id' => 3, 'parent_id' => 1, 'name' => '二房 (高武德派下)', 'type' => 'branch', 'detail' => '中南部雲林、嘉義分支。'],
        ['id' => 4, 'parent_id' => 2, 'name' => '高詩傳 (長子)', 'type' => 'member', 'detail' => '現任公業管理代名人。'],
        ['id' => 5, 'parent_id' => 2, 'name' => '高詩禮 (次子)', 'type' => 'member', 'detail' => '移居海外，保持聯繫。'],
        ['id' => 6, 'parent_id' => 3, 'name' => '高禮本 (長子)', 'type' => 'member', 'detail' => '負責南部祖厝祭祀事宜。']
    ];

    // 審計行為日誌
    $_SESSION['audit_logs'] = [
        ['timestamp' => date('Y-m-d H:i:s'), 'user' => 'SYSTEM', 'action' => '宗親會系統初始化成功']
    ];

    $_SESSION['initialized'] = true;
}

// 3. 核心業務邏輯處理類別 (物件導向 OOP)
class ClanManager {
    private $treeData;
    
    public function __construct() {
        $this->treeData = &$_SESSION['clan_tree'];
    }

    // 遞迴演算法：將平面陣列建立為巢狀樹狀結構
    public function getTree($parentId = 0) {
        $branch = [];
        foreach ($this->treeData as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->getTree($element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    // 新增節點項目
    public function addNode($parentId, $name, $type, $detail) {
        if ($_SESSION['user']['role'] !== 'admin') return false;
        
        $newId = count($this->treeData) > 0 ? max(array_column($this->treeData, 'id')) + 1 : 1;
        $this->treeData[] = [
            'id' => $newId,
            'parent_id' => (int)$parentId,
            'name' => htmlspecialchars($name),
            'type' => htmlspecialchars($type),
            'detail' => htmlspecialchars($detail)
        ];
        $this->logAction("新增宗族節點: {$name}");
        return true;
    }

    // 紀錄行為日誌
    public function logAction($action) {
        $_SESSION['audit_logs'][] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $_SESSION['user']['name'] ?? 'GUEST',
            'action' => htmlspecialchars($action)
        ];
    }

    // 導出 CSV 功能
    public function exportToCSV() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clan_member_export.csv');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // 寫入 UTF-8 BOM 防止 Excel 亂碼
        fputcsv($output, ['節點ID', '父節點ID', '名稱/派下員', '類型', '備註細節']);
        foreach ($this->treeData as $row) {
            fputcsv($output, [$row['id'], $row['parent_id'], $row['name'], $row['type'], $row['detail']]);
        }
        fclose($output);
        $this->logAction("導出了全會宗族 CSV 報表");
        exit;
    }
}

$manager = new ClanManager();

// 4. 控制器 (Controller) 路由請求處理
$error = '';
$success = '';

// 處理登入
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($_SESSION['users'][$username]) && password_verify($password, $_SESSION['users'][$username]['password'])) {
        $_SESSION['user'] = [
            'username' => $username,
            'role' => $_SESSION['users'][$username]['role'],
            'name' => $_SESSION['users'][$username]['name']
        ];
        $manager->logAction("使用者登入成功");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "帳號或密碼錯誤！(提示：管理員 admin/admin123，一般會員 member/member123)";
    }
}

// 處理登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $manager->logAction("使用者登出系統");
    unset($_SESSION['user']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 權限攔截與安全檢查
if (isset($_SESSION['user'])) {
    // 處理資料新增 (限 Admin)
    if (isset($_POST['action']) && $_POST['action'] === 'add_node') {
        // CSRF 安全驗證
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            die('CSRF 安全憑證失效，拒絕請求。');
        }
        
        $result = $manager->addNode($_POST['parent_id'], $_POST['name'], $_POST['type'], $_POST['detail']);
        if ($result) {
            $success = "宗族節點/派下員新增成功！";
        } else {
            $error = "權限不足，無法操作！";
        }
    }

    // 處理 CSV 導出
    if (isset($_GET['action']) && $_GET['action'] === 'export') {
        $manager->exportToCSV();
    }
}

// 5. 視圖渲染函式：動態生成無邊界、完全靠左且預設展開的樹狀選單
function renderHtmlTree($treeData) {
    $html = '<ul>';
    foreach ($treeData as $node) {
        $hasChildren = isset($node['children']);
        $liClass = $hasChildren ? 'tree-node expanded' : 'tree-node is-leaf';
        $icon = $node['type'] === 'root' ? '🏛️' : ($node['type'] === 'branch' ? '🪵' : '📜');
        
        $html .= "<li class='{$liClass}'>";
        $html .= "<div class='node-content' onclick='showDetail(\"".addslashes($node['name'])."\", \"".addslashes($node['type'])."\", \"".addslashes($node['detail'])."\")'>";
        $html .= $hasChildren ? "<span class='arrow'>▼</span>" : "<span class='arrow' style='display:none;'></span>";
        $html .= "<span class='icon'>{$icon}</span><span class='label'>{$node['name']}</span>";
        $html .= "</div>";
        
        if ($hasChildren) {
            $html .= "<div class='subtree'>" . renderHtmlTree($node['children']) . "</div>";
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>高氏宗親會內部資產與派下員管理系統</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { height: 100%; font-family: "Microsoft JhengHei", sans-serif; background-color: #f4f6f9; color: #333; overflow: hidden; }
        
        /* 登入介面 */
        .login-wrapper { width: 100vw; height: 100vh; display: flex; justify-content: center; align-items: center; background: linear-gradient(135deg, #8b0000, #3a0000); }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); width: 380px; }
        .login-box h2 { text-align: center; color: #8b0000; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { width: 100%; padding: 10px; border: none; border-radius: 4px; background: #8b0000; color: white; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #b30000; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; }
        .alert-danger { background: #fde2e2; color: #f56c6c; border: 1px solid #fcd3d3; }
        .alert-success { background: #e1f3d8; color: #67c23a; border: 1px solid #d1edc4; }

        /* 後台主框架 */
        .header { height: 60px; background-color: #8b0000; color: white; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .main-container { display: flex; height: calc(100% - 60px); width: 100%; }
        
        /* 左側選單完全靠左到底 */
        .sidebar { width: 25%; background-color: #ffffff; border-right: 1px solid #dcdfe6; padding: 15px 0; overflow-y: auto; }
        .tree-view, .tree-view ul { list-style-type: none; padding-left: 15px; margin: 0; }
        .tree-view > ul { padding-left: 0 !important; margin-left: 0 !important; }
        .tree-node { position: relative; margin: 4px 0; }
        .node-content { display: inline-flex; align-items: center; padding: 8px; padding-left: 5px; cursor: pointer; color: #4a4a4a; font-size: 14px; transition: all 0.2s; width: 100%; }
        .node-content:hover { background-color: #fdf2f2; color: #8b0000; }
        .tree-view > ul > .tree-node > .node-content { padding-left: 5px !important; }
        .arrow { display: inline-block; width: 16px; margin-right: 4px; text-align: center; font-size: 10px; color: #909399; }
        .icon { margin-right: 6px; }
        .subtree { display: block; }

        /* 右側多功能工作區 */
        .workspace { width: 75%; height: 100%; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        .panel { background: white; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .panel h3 { color: #8b0000; border-bottom: 2px solid #8b0000; padding-bottom: 8px; margin-bottom: 15px; }
        
        /* 表格與日誌 */
        .log-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        .log-table th, .log-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .log-table th { background: #f9f2f2; color: #8b0000; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 12px; color: white; font-weight: bold; }
        .badge-admin { background: #e6a23c; }
        .badge-viewer { background: #909399; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['user'])): ?>
    <div class="login-wrapper">
        <div class="login-box">
            <h2>渤海堂高氏宗親會</h2>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>使用者帳號/管理者admin，一般會員member</label>
                    <input type="text" name="username" class="form-control" required placeholder="請輸入 admin 或 member">
                </div>
                <div class="form-group">
                    <label>密碼/管理員admin123/，一般會員member123</label>
                    <input type="password" name="password" class="form-control" required placeholder="請輸入密碼">
                </div>
                <button type="submit" class="btn">安全登入</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="header">
        <div><strong>渤海堂高氏宗親會 - 核心會務資料庫平台</strong></div>
        <div>
            歡迎，<?= $_SESSION['user']['name'] ?> 
            <span class="badge <?= $_SESSION['user']['role'] === 'admin' ? 'badge-admin' : 'badge-viewer' ?>">
                <?= $_SESSION['user']['role'] === 'admin' ? '系統理事長(最高權限)' : '一般宗親(唯讀)' ?>
            </span>
            | <a href="?action=logout" style="color: #ffcdcd; text-decoration: none; margin-left: 10px;">安全登出</a>
        </div>
    </div>

    <div class="main-container">
        <aside class="sidebar">
            <div style="padding: 0 15px 10px 15px; border-bottom: 1px solid #eee; margin-bottom: 10px;">
                <a href="?action=export" class="btn" style="display:block; text-align:center; text-decoration:none; font-size:13px; padding:6px;">📥 導出全會宗族階梯 CSV</a>
            </div>
            <nav class="tree-view">
                <?= renderHtmlTree($manager->getTree()) ?>
            </nav>
        </aside>

        <main class="workspace">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <div class="panel">
                <h3>🔍 資訊檢視面板 (點選左側選單動態更新)</h3>
                <div id="detail-area">
                    <p style="color:#909399;">請點擊左側組織樹狀選單項目，此處將即時調閱該派下員或分支的歷史詳細文獻...</p>
                </div>
            </div>

            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <div class="panel">
                    <h3>🛠️ 宗族譜系修補與增設 (管理員授權模式)</h3>
                    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <input type="hidden" name="action" value="add_node">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <div class="form-group">
                            <label>隸屬父輩/大房</label>
                            <select name="parent_id" class="form-control">
                                <?php foreach ($_SESSION['clan_tree'] as $node): ?>
                                    <option value="<?= $node['id'] ?>"><?= $node['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>名稱 (派下員/新大房)</label>
                            <input type="text" name="name" class="form-control" required placeholder="例如：高禮新">
                        </div>
                        <div class="form-group">
                            <label>類別</label>
                            <select name="type" class="form-control">
                                <option value="branch">支派房系 (Branch)</option>
                                <option value="member">個人成員 (Member)</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: span 3; margin-bottom: 0;">
                            <label>生平記述 / 派下資產分配備註</label>
                            <textarea name="detail" class="form-control" rows="2" required placeholder="請詳細記述生平記號或公業持分狀況..."></textarea>
                            <button type="submit" class="btn" style="margin-top: 10px; width: auto; padding: 8px 20px;">確認寫入宗族譜系資料庫</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="panel" style="flex: 1;">
                <h3>📜 系統安全性與行為審計日誌 (Audit Log)</h3>
                <div style="max-height: 200px; overflow-y: auto;">
                    <table class="log-table">
                        <thead>
                            <tr><th>時間戳記</th><th>操作人員</th><th>執行事件說明</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($_SESSION['audit_logs']) as $log): ?>
                                <tr>
                                    <td style="color:#909399; width: 160px;"><?= $log['timestamp'] ?></td>
                                    <td style="font-weight:bold; width: 150px;"><?= $log['user'] ?></td>
                                    <td><?= $log['action'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showDetail(name, type, detail) {
            const area = document.getElementById('detail-area');
            let typeText = type === 'root' ? '始祖開基公' : (type === 'branch' ? '支派房系' : '派下員成員');
            area.innerHTML = `
                <table class="log-table" style="font-size:15px;">
                    <tr><th style="width:120px;">資料標題</th><td><strong>${name}</strong></td></tr>
                    <tr><th>譜系層級</th><td><span class="badge" style="background:#8b0000;">${typeText}</span></td></tr>
                    <tr><th>歷史記述 / 資產持分明細</th><td>${detail}</td></tr>
                </table>
            `;
        }
    </script>
<?php endif; ?>

</body>
</html>