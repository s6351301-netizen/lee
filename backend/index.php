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

$role_title = "";  // 用來儲存轉換後的中文稱謂
$member_no = "";   // 用來儲存會員號
$unread_count = 0; // 🎯 新增：用來儲存未讀信件總數

// 檢查是否有登入 Session
if (isset($_SESSION['name'])) {
    $current_user = $_SESSION['name'];
    
    // 使用預備陳述式同時撈取 role 與 new_member 欄位
    $stmt = $conn->prepare("SELECT role, new_member FROM account WHERE name = ?");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // 1. 取得會員號
        $member_no = !empty($row['new_member']) ? $row['new_member'] : "";
        $user_role = $row['role']; // 記錄原始 role 值
        
        // 2. 根據 role 欄位值轉換為中文職稱
        switch ($row['role']) {
            case 'admin':
                $role_title = "（管理者）";
                break;
            case 'user':
                $role_title = "（派下員）";
                break;
            case 'clan':
                $role_title = "（宗親）";
                break;
            default:
                $role_title = ""; 
                break;
        }

        // ==========================================
        // 🎯 核心追加：同步至名冊撈取世代與大房，用以精準統計未讀信件總數
        // ==========================================
        $my_gen = ""; $my_houses = "";
        $stmt_mem = $conn->prepare("SELECT generation, number_of_houses FROM members WHERE name = ? LIMIT 1");
        $stmt_mem->bind_param("s", $current_user);
        $stmt_mem->execute();
        $res_mem = $stmt_mem->get_result();
        if ($row_m = $res_mem->fetch_assoc()) {
            $my_gen = $row_m['generation'];
            $my_houses = $row_m['number_of_houses'];
        }
        $stmt_mem->close();

        // 統計四種收件管道交集下，所有 is_read = 0 的未讀信件總數
        $sql_unread = "SELECT COUNT(*) as total FROM messages WHERE is_read = 0 AND (
            (to_type = 'user' AND to_target = ?) OR
            (to_type = 'generation' AND to_target = ?) OR
            (to_type = 'houses' AND to_target = ?) OR
            (to_type = 'role' AND to_target = ?)
        )";
        $stmt_unread = $conn->prepare($sql_unread);
        $stmt_unread->bind_param("ssss", $member_no, $my_gen, $my_houses, $user_role);
        $stmt_unread->execute();
        $res_unread = $stmt_unread->get_result();
        if ($row_u = $res_unread->fetch_assoc()) {
            $unread_count = (int)$row_u['total'];
        }
        $stmt_unread->close();
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
    <title>台中市李武略派下李氏宗親會 - 後台管理系統</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body,
        html {
            height: 100%;
            font-family: "Microsoft JhengHei", Arial, sans-serif;
            background-color: #f4f6f9;
            overflow: hidden;
        }

        .header {
            height: 60px;
            background: radial-gradient(circle, #2892a0 0%, #05285f 54%, #0f172a 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            padding: 0 20px;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .main-container {
            display: flex;
            height: calc(100% - 60px);
            width: 100%;
        }

        .clanMenuData {
            font-size: 14px;
        }

        .sidebar {
            width: 200px;
            min-width: 150px;
            max-width: 500px;
            border-right: 1px solid #dcdfe6;
            overflow-y: auto;
        }

        #drag-bar {
            width: 2px;
            background-color: #dcdfe6;
            cursor: col-resize;
            flex-shrink: 0;
            transition: background 0.1s;
        }

        #drag-bar:hover,
        body.is-dragging #drag-bar {
            background-color: #68696a;
        }

        body.is-dragging #contentFrame {
            pointer-events: none;
        }

        .content-area {
            flex-grow: 1;
            height: 100%;
            border: none;
            background-color: #fcfcfc;
        }

        .tree-view,
        .tree-view ul {
            list-style-type: none;
            padding-left: 0px;
            margin: 0;
        }

        .tree-view>ul {
            padding-left: 0 !important;
            margin-left: 0 !important;
        }

        .tree-node {
            position: relative;
            margin: 8px 0;
        }

        .node-content {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            border-radius: 0 4px 4px 0;
            color: #4a4a4a;
            font-size: 14px;
            transition: all 0.2s;
            width: 100%;
        }

        .node-content:hover {
            background-color: #fdf2f2;
            color: #1b1b34;
        }

        .tree-view>ul>.is-leaf>.node-content {
            padding-left: 0px;
        }

        .arrow {
            display: inline-block;
            height: 16px;
            margin-right: 4px;
            text-align: center;
            line-height: 16px;
            font-size: 10px;
            color: #0500ff;
            transition: transform 0.3s;
        }

        .expanded>.node-content .arrow {
            transform: rotate(90deg);
        }

        .is-leaf>.node-content .arrow {
            visibility: hidden;
            width: 16px;
            display: none;
        }

        .tree-view>ul>.is-leaf>.node-content .arrow {
            display: none;
        }

        .icon {
            margin-right: 6px;
            font-size: 14px;
        }

        .subtree {
            display: none;
        }

        .expanded>.subtree {
            display: block;
        }

        /* 🎯 訊息通知圖示專用全新樣式 */
        .mail-notification-link {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: #ffedd5;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 15px;
            font-family: inherit;
            margin-left: 8px;
            transition: background 0.2s;
            vertical-align: middle;
        }
        .mail-notification-link:hover {
            background: rgba(243, 156, 18, 0.3);
            color: #ffffff;
        }
        .mail-badge {
            background-color: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: bold;
            border-radius: 10px;
            padding: 1px 6px;
            margin-left: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
    </style>
</head>

<body>

    <div class="header">
        台中市
        <span style="font-family: 'DFKai-sb', '標楷體', serif;font-size: 36px;">
            李武略</span>
        派下李氏宗親會 - 後台管理系統
        &emsp;
        <img src="../icon/logo.svg" alt="Li Wulue's Li Clan Association李武略派下李氏宗親會" style="height: 50px; width: auto;">
        <a href="index.html" title="前台首頁"
            style="color: rgb(3, 162, 13); font-family: 'DFKai-sb', '標楷體', serif; font-size: 18px;text-decoration: none;">
            前台首頁
        </a>
       &emsp;
        <a href="index.html" title="後台首頁"
            style="color: rgb(4, 121, 167); font-family: 'DFKai-sb', '標楷體', serif; font-size: 18px;text-decoration: none;">
            後台首頁
        </a>
        &emsp;     
        <div style="text-align:right;">
            <?php if(isset($_SESSION['name'])): ?>
                <?php echo htmlspecialchars($_SESSION['name']) . htmlspecialchars($member_no) . $role_title; ?>
                
                <a href="view-voicemail.php" target="contentFrame" class="mail-notification-link" title="查看祈願信箱留言">
                    ✉ 訊息
                    <?php if ($unread_count > 0): ?>
                        <span class="mail-badge"><?php echo $unread_count; ?></span>
                    <?php else: ?>
                        <span style="color: #94a3b8; margin-left: 4px;">(0)</span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>&emsp;
        <a href="../backend/logout.php" style="color:GreenYellow; font-family:'DFKai-SB'; text-decoration:none;">登出</a>

    </div>

    <div class="main-container">
        <aside class="sidebar" id="sidebar">
            <nav class="tree-view">
                <ul id="menu-tree"></ul>
            </nav>
        </aside>

        <div id="drag-bar"></div>

        <iframe name="contentFrame" id="contentFrame" class="content-area" src="home.html"></iframe>
    </div>

    <script class="clanMenuData">
        const clanMenuData = [
            { id: 1, name: "<span style='font-family: DFKai-SB, BiauKai, sans-serif; font-weight: bold;color:#000080;font-size: 20px;'>🔵首頁歡迎資訊</span>", url: "home.html", parentId: 0 },
            { id: 2, name: "<span style='font-family: DFKai-SB, BiauKai, sans-serif; font-weight: bold;color: #000080;font-size: 18px;'>01 宗親會簡介</span>", url: "null", parentId: 0 },
            { id: 3, name: "<span style='font-size: 16px;'>1-1.緣起與歷史沿革</span>", url: "history.html", parentId: 2 },
            { id: 4, name: "<span style='font-size: 16px;'>1-2.組織章程與組織編制</span>", url: "charter.html", parentId: 2 },
            { id: 4, name: "<span style='font-size: 16px;'>0-0.測試API會員PHP</span>", url: "test_view.php", parentId: 2 },
            { id: 4, name: "<span style='font-size: 16px;'>0-0.開發進度維護PHP</span>", url: "dev_tracking.php", parentId: 2 },            
            { id: 5, name: "<span style='font-family: DFKai-SB, BiauKai, sans-serif; font-weight: bold;color: #000080;font-size: 18px;'>02 始祖與開台源流</span>", url: "null", parentId: 0 },
            { id: 6, name: "<span style='font-size:16px;'>2-1.隴西堂號由來</span>", url: "bohai.html", parentId: 5 },
            { id: 7, name: "<span style='font-size:16px;'>2-2.歷代昭穆字輩表</span>", url: "genealogy.html", parentId: 5 },
            { id: 7, name: "<span style='font-size:16px;'>2-3.歷代昭穆字輩表json</span>", url: "genealogy2.html", parentId: 5 },
            { id: 8, name: "<span style='font-family:DFKai-SB, BiauKai, sans-serif; font-weight: bold;color: #000080;font-size: 18px;'>03 會務與祭祀管理</span>", url: "null", parentId: 0 },
            { id: 9, name: "<span style='font-size:16px;'>3-1.春季祭祖大典紀錄</span>", url: "spring.html", parentId: 8 },
            { id: 10, name: "<span style='font-size:16px;'>3-2.派下員掃墓祭掃公告</span>", url: "grave.html", parentId: 8 },
            { id: 11, name: "<span style='font-family: DFKai-SB, BiauKai, sans-serif; font-weight: bold;color: #000080;font-size: 18px;'>04 派下員名冊管理</span>", url: "null", parentId: 0 },
            { id: 12, name: "<span style='font-size:16px;'>4-1.當屆會員代表名冊</span>", url: "member_list.html", parentId: 11 },
            { id: 13, name: "<span style='font-size:16px;'>4-2.優秀獎學金申請php</span>", url: "clan_system.php", parentId: 11 },
            { id: 14, name: "<span style='font-size:16px;'>4-3.優秀獎學金申請json</span>", url: "scholarship2.html", parentId: 11 },
            { id: 14, name: "<span style='font-size:16px;'>4-3.獎學金申請json大甲</span>", url: "scholarship3.html", parentId: 11 },
            { id: 15, name: "<span style='font-size:16px;'>4-4.填寫許願卡</span>", url: "../3trees.php", parentId: 11 },
            { id: 16, name: "<span style='font-size:16px;'>4-5.修改/刪除許願卡</span>", url: "3treesdell.php", parentId: 11 },
            { id: 17, name: "<span style='font-size:16px;'>4-6.會員資料session傳值</span>", url: "member.php", parentId: 11 },
            { id: 18, name: "<span style='font-size:16px;'>4-7.新增語音/影音留言</span>", url: "voicemail.php", parentId: 11 },
            { id: 19, name: "<span style='font-size:16px;'>4-8.查看語音/影音留言</span>", url: "view-voicemail.php", parentId: 11 },
            { id: 20, name: "<span style='font-size:16px;'>4-9.祭祖活動html統計</span>", url: "ancestor-worship.html", parentId: 11 },
            { id: 20, name: "<span style='font-size:16px;'>4-9.祭祖活動php統計</span>", url: "ancestor-worship.php", parentId: 11 },
            { id: 21, name: "<span style='font-size:16px;'>4-10.每日撈中油油價顯示3個月統計資料</span>", url: "oil_tracker.php", parentId: 11 }            

        ];

        function buildTree(list, parentId = 0) {
            const tree = [];
            for (const item of list) {
                if (item.parentId === parentId) {
                    const children = buildTree(list, item.id);
                    if (children.length > 0) item.children = children;
                    tree.push(item);
                }
            }
            return tree;
        }

        function renderTree(treeData, container) {
            treeData.forEach(node => {
                const li = document.createElement('li');
                li.className = 'tree-node';
                const hasChildren = node.children && node.children.length > 0;
                if (!hasChildren) li.classList.add('is-leaf');

                const arrowHtml = `<span class="arrow"></span>`;
                const iconHtml = hasChildren ? `<span class="icon">▼</span>` : `<span class="icon"></span>`;

                li.innerHTML = `
          <div class="node-content" data-url="${node.url || ''}">
            ${arrowHtml}
            ${iconHtml}
            <span class="label">${node.name}</span>
          </div>
        `;

                if (hasChildren) {
                    const subUl = document.createElement('ul');
                    subUl.className = 'subtree';
                    renderTree(node.children, subUl);
                    li.appendChild(subUl);

                    li.querySelector('.node-content').addEventListener('click', (e) => {
                        e.stopPropagation();
                        li.classList.toggle('expanded');
                    });
                } else {
                    li.querySelector('.node-content').addEventListener('click', (e) => {
                        e.stopPropagation();
                        document.getElementById('contentFrame').src = node.url;
                    });
                }
                container.appendChild(li);
            });
        }

        renderTree(buildTree(clanMenuData), document.getElementById('menu-tree'));

        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const dragBar = document.getElementById('drag-bar');
            let isResizing = false;

            dragBar.addEventListener('mousedown', (e) => {
                isResizing = true;
                document.body.classList.add('is-dragging');
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isResizing) return;
                let newWidth = e.clientX;
                if (newWidth >= 150 && newWidth <= 500) {
                    sidebar.style.width = newWidth + 'px';
                }
            });

            document.addEventListener('mouseup', () => {
                if (isResizing) {
                    isResizing = false;
                    document.body.classList.remove('is-dragging');
                }
            });
        });
    </script>
</body>

</html>