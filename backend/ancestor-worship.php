<?php
// ==========================================
// 1. 強制登入與轉址檢查 (直接嵌入本頁最上方)
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    if (!isset($_SESSION['name'])) {
        echo "<script>
            alert('後台網頁需登入，請重新做登入。');
            window.location.href = 'login.php';
        </script>";
        exit;
    }
}

// ==========================================
// 2. 資料庫連線設定與角色/會員號查詢
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

$user_real_name = "";
$gender_title = " 先生/小姐";
$db_address = "";
$db_zip = "";
$zip_part1 = array("", "", "");
$zip_part2 = array("", "", "");

if (isset($_SESSION['name'])) {
    $current_user = $_SESSION['name'];

    $stmt = $conn->prepare("SELECT role, new_member FROM account WHERE name = ?");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $member_no = !empty($row['new_member']) ? $row['new_member'] : "";

        switch ($row['role']) {
            case 'admin': $role_title = "（管理者）"; break;
            case 'user': $role_title = "（派下員）"; break;
            case 'clan': $role_title = "（宗親）"; break;
            default: $role_title = ""; break;
        }
    }
    $stmt->close();

    // ==========================================
    // 從 account_members_view 核對資料 (包含 zip_code)
    // ==========================================
    $stmt_view = $conn->prepare("SELECT account_name, account_gender, address, zip_code FROM account_members_view WHERE account_name = ? LIMIT 1");
    $stmt_view->bind_param("s", $current_user);
    $stmt_view->execute();
    $res_view = $stmt_view->get_result();
    if ($row_v = $res_view->fetch_assoc()) {
        $user_real_name = $row_v['account_name'];
        $db_address = $row_v['address'];
        $raw_zip = trim($row_v['zip_code']);

        if ($row_v['account_gender'] == '女' || $row_v['account_gender'] == '女性' || strtolower($row_v['account_gender']) == 'female') {
            $gender_title = "小姐";
        } else {
            $gender_title = "先生";
        }

        if (!empty($raw_zip)) {
            $pure_nums = preg_replace('/[^0-9]/', '', $raw_zip);
            if (strlen($pure_nums) >= 6) {
                $db_zip = substr($pure_nums, 0, 3) . '-' . substr($pure_nums, 3, 3);
            } else {
                $db_zip = $raw_zip;
            }

            if (strpos($db_zip, '-') !== false) {
                $zip_arr = explode('-', $db_zip);
                $p1 = isset($zip_arr[0]) ? str_split($zip_arr[0]) : array();
                $p2 = isset($zip_arr[1]) ? str_split($zip_arr[1]) : array();
            } else {
                $p1 = str_split(substr($pure_nums, 0, 3));
                $p2 = str_split(substr($pure_nums, 3));
            }
            
            for ($i = 0; $i < 3; $i++) {
                $zip_part1[$i] = isset($p1[$i]) ? $p1[$i] : "";
                $zip_part2[$i] = isset($p2[$i]) ? $p2[$i] : "";
            }
        }
    } else {
        $user_real_name = $current_user;
    }
    $stmt_view->close();
}

// ==========================================
// 当点击送出表单时，写入 ancestor_worship 资料表
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_submit'])) {
    $post_name = isset($_POST['worship_name']) ? $_POST['worship_name'] : '';
    $post_address = isset($_POST['worship_address']) ? $_POST['worship_address'] : '';
    $post_zip = isset($_POST['worship_zip']) ? $_POST['worship_zip'] : '';

    if (!empty($post_zip) && strpos($post_zip, '-') === false) {
        $pure_nums = preg_replace('/[^0-9]/', '', $post_zip);
        if (strlen($pure_nums) >= 6) {
            $post_zip = substr($pure_nums, 0, 3) . '-' . substr($pure_nums, 3, 3);
        }
    }

    if (!empty($post_name)) {
        $stmt_ins = $conn->prepare("INSERT INTO ancestor_worship (name, address, zip_code) VALUES (?, ?, ?)");
        $stmt_ins->bind_param("sss", $post_name, $post_address, $post_zip);
        if ($stmt_ins->execute()) {
            echo "<script>alert('報名表單資料已成功寫入資料庫！'); window.location.href=window.location.href;</script>";
        } else {
            echo "<script>alert('寫入失敗：" . $stmt_ins->error . "');</script>";
        }
        $stmt_ins->close();
    }
    $conn->close();
    exit;
}

$conn->close();

function convertAddressNumbers($addressString)
{
    return preg_replace('/([0-9]+)/', '<span class="combine-num">${1}</span>', htmlspecialchars($addressString));
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>祭祖活動</title>
    <style>
        body {
            font-family: "DFKai-SB", "標楷體", Arial, "Helvetica Neue", Helvetica, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin: 0;
            padding-left: 1%;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 5px;
            text-align: center;
            font-family: "Microsoft JhengHei", "微軟正黑體", sans-serif;
            font-size: 18px;
            margin: 0;
        }

        input[type="text"] {
            border: none !important;
            background: transparent !important;
            outline: none !important;
            box-shadow: none !important;
            font-family: inherit;
            font-size: inherit;
            color: inherit;
            padding: 0;
            margin: 0;
        }

        .postcard-twin-container {
            display: inline-flex;
            flex-direction: row;
            gap: 10px;
            justify-content: center;
            align-items: flex-start;
            flex-wrap: nowrap;
            white-space: normal;
        }

        .postcard-face {
            background-color: #fffdf7;
            width: 288px;
            height: 480px;
            border: 2px solid #a1a1a1;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
            position: relative;
            padding: 15px;
            box-sizing: border-box;
        }

        .vertical-text-mode {
            writing-mode: vertical-rl;
            -webkit-writing-mode: vertical-rl;
        }

        .combine-num {
            text-combine-upright: all;
            -webkit-text-combine-upright: all;
            font-family: Arial, sans-serif;
            display: inline-block;
        }

        /* 郵遞區號 */
        .zip-code-zone {
            display: flex;
            align-items: center;
            gap: 2px;
            z-index: 5;
        }

        .zip-container {
            display: flex;
            gap: 1.5px;
        }

        .zip-box {
            width: 13px;
            height: 18px;
            border: 1px solid #ff0000;
            text-align: center;
            line-height: 18px;
            font-size: 14px;
            font-weight: bold;
            color: #ff0000;
            font-family: Arial, sans-serif;
        }

        .zip-hyphen {
            color: #ff0000;
            font-weight: bold;
            font-size: 11px;
        }

        .recipient-zip {
            position: absolute;
            right: 15px;
            top: 15px;
        }

        .sender-zip {
            position: absolute;
            left: 15px;
            bottom: 15px;
        }

        .stamp-placeholder {
            width: 60px;
            height: 70px;
            border: 1.2px dashed #ff0000;
            background-image: url("https://www.post.gov.tw/post/FileCenter/post_ww2/stamp_pic/stamp_bpic/D768_01.jpg");
            background-repeat: no-repeat;
            background-size: 60px 70px;
        }

        /* 【改回對齊地址的台字】將 top 改回 92px，使其頂端高度完全相同 */
        .floating-block {
            width: 65px;
            height: 300px;
            background-color: #ffffff;
            border: 2px solid #ff0000;
            outline: 1px solid #ff0000;
            outline-offset: -4px;
            border-radius: 8px;
            position: absolute;
            top: 92px;
            /* 完美對齊地址的頂端「台」字 */
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.12);
            z-index: 10;
        }

        .floating-block h1 {
            color: black;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 4px;
            margin: 0;
            display: block;
            text-align: center;
            line-height: 1.2;
        }

        .floating-block h1 input[type="text"] {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            width: auto;
            max-height: 180px;
            display: inline-block;
            margin-top: -65px; /* 收件人姓名 數字越大(例如 -20px) 字就會越往上挪 */
        }

        /* 收件人地址 (頂端為 62px) */
        .recipient-address {
            position: absolute;
            right: 45px;
            top: 62px;
            height: 384px;
            font-size: 18px;
            color: #000000;
            line-height: 18px;
            letter-spacing: 0.5px;
        }

        .recipient-address-content {
            font-size: 18px;
            display: inline-block;
        }

        /* 寄件者地址 */
        .sender-address {
            position: absolute;
            left: 25px;
            top: 100px;
            height: 360px;
            font-size: 14px;
            color: #000000;
            line-height: 25px;
        }

        /* =======================================================================
           【左側：明信片背面 - 內文面樣式】
           ======================================================================= */
        .back-content-spec {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            box-sizing: border-box;
            padding: 0;
        }

        .back-content-spec .form-title {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 18px;
            color: #2c3e50;
            margin-bottom: 50px;
            text-align: center;
        }

        .back-content-spec .main-text {
            padding: 17px;
            font-size: 18px;
            line-height: 2;
            color: #2c3e50;
        }

        .notice-bottom {
            font-size: 14px;
            color: #c0392b;
            font-weight: bold;
            line-height: 1.4;
            margin-top: auto;
            padding-right: 4px;
        }

        /* 動態效果：圍繞在 62px 固定高度上下微幅飄浮 */
        .floating-block {
            animation: floatMove 3s ease-in-out infinite;
        }

        @keyframes floatMove {
            0%, 100% { transform: translateX(-50%); }
            50% { transform: translateX(-50%) translateY(-5px); }
        }

        .notice-bottom {
            animation: textShake 2s ease-in-out infinite;
        }

        @keyframes textShake {
            0%, 90%, 100% { transform: translateX(0); }
            92% { transform: translateX(-4px); }
            94% { transform: translateX(4px); }
            96% { transform: translateX(-2px); }
            98% { transform: translateX(2px); }
        }

        @media screen {
            .back-content-spec .main-text {
                max-width: 0px;
                white-space: nowrap;
                overflow: hidden;
                animation: verticalWrite 4s cubic-bezier(0.4, 0, 0.2, 1) 0.8s forwards;
            }
        }

        @keyframes verticalWrite {
            0% { max-width: 0px; }
            100% { max-width: 288px; }
        }

        /* 郵遞區號動畫 */
        @media screen {
            .zip-code-zone .zip-box {
                opacity: 0;
                animation: zipDropBounce 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            }

            .recipient-zip .zip-container:first-child .zip-box:nth-child(1) { animation-delay: 0.1s; }
            .recipient-zip .zip-container:first-child .zip-box:nth-child(2) { animation-delay: 0.3s; }
            .recipient-zip .zip-container:first-child .zip-box:nth-child(3) { animation-delay: 0.5s; }
            .recipient-zip .zip-container:last-child .zip-box:nth-child(1) { animation-delay: 0.7s; }
            .recipient-zip .zip-container:last-child .zip-box:nth-child(2) { animation-delay: 0.9s; }
            .recipient-zip .zip-container:last-child .zip-box:nth-child(3) { animation-delay: 1.3s; }

            .sender-zip .zip-container:first-child .zip-box:nth-child(1) { animation-delay: 1.5s; }
            .sender-zip .zip-container:first-child .zip-box:nth-child(2) { animation-delay: 1.7s; }
            .sender-zip .zip-container:first-child .zip-box:nth-child(3) { animation-delay: 1.9s; }
            .sender-zip .zip-container:last-child .zip-box:nth-child(1) { animation-delay: 2.1s; }
            .sender-zip .zip-container:last-child .zip-box:nth-child(2) { animation-delay: 1.3s; }
            .sender-zip .zip-container:last-child .zip-box:nth-child(3) { animation-delay: 2.5s; }

            .zip-hyphen {
                opacity: 0;
                animation: fadeInHyphen 0.4s ease forwards 0.7s;
            }
        }

        @keyframes zipDropBounce {
            0% { opacity: 0; transform: translateY(-40px) scale(0.7); }
            60% { opacity: 1; transform: translateY(5px) scale(1.05); }
            80% { transform: translateY(-2px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes fadeInHyphen { to { opacity: 1; } }

        /* 列印保護 */
        @media print {
            .floating-block {
                animation: none !important;
                transform: translateX(-50%) !important;
                box-shadow: none !important;
            }

            .back-content-spec .main-text {
                animation: none !important;
                max-width: none !important;
                overflow: visible !important;
                white-space: normal !important;
            }

            .zip-code-zone .zip-box,
            .zip-hyphen {
                animation: none !important;
                opacity: 1 !important;
                transform: none !important;
            }
        }

        .transparent-table {
            border-collapse: collapse;
            border: none;
            background-color: transparent;
        }

        .transparent-table td {
            border: none;
            padding: 0;
            background-color: transparent;
        }

        .submit-container {
            position: absolute;
            bottom: 15px;
            right: 10px;
            z-index: 20;
            width: 100%;
            text-align: right;
            box-sizing: border-box;
        }

        .submit-btn {
            border: 1px solid #b5b5b5;
            font-size: 18px;
            font-family: "Microsoft JhengHei", sans-serif;
            font-weight: bold;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
            width: 15%;
        }

        .submit-btn:hover {
            background-color: #d4d4d4;
        }

        .instruction-table {
            border-collapse: collapse;
            border: none;
            background-color: transparent;
            margin-left: 20px;
        }

        .instruction-table td {
            border: none;
            background-color: transparent;
            padding: 15px;
            vertical-align: top;
        }

        .instruction-box {
            width: 350px;
            font-family: "Microsoft JhengHei", "微軟正黑體", sans-serif;
            color: #333333;
            font-size: 14px;
            line-height: 1.6;
        }

        .instruction-box h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 5px;
            font-size: 16px;
        }

        .instruction-item {
            margin-bottom: 15px;
            text-align: justify;
        }

        .instruction-item strong {
            color: #c0392b;
        }
    </style>
</head>

<body>
    <form method="POST" action="">
        <input type="hidden" name="action_submit" value="1">
        <input type="hidden" name="worship_zip" value="<?php echo htmlspecialchars($db_zip); ?>">

        <div class="postcard-twin-container">
            <table class="transparent-table">
                <tr>
                    <td>
                        <div class="postcard-face" style="margin-top: 5px; margin-right: 5px;">
                            <div class="back-content-spec vertical-text-mode">
                                <div class="form-title">報名單</div>
                                <div class="main-text">
                                    一、本人願意參加本公業民國<span class="combine-num">115</span>年<span class="combine-num">1</span>月
                                    <span class="combine-num">1</span>日(農曆<span class="combine-num">114</span><br>
                                    &emsp;&emsp;年<span class="combine-num">11</span>月<span
                                        class="combine-num">13</span>日)星期四，會員大會(祭祖並聚餐)。<br>
                                    二、參加人員有本人外還有太太和小孩，<br>
                                    &emsp;&emsp;葷食?個素食?個，共計<span class="combine-num">10</span>名。<br>
                                    三、若本人無法出席，將授權由_______代理出席。<br>
                                </div>
                                <div class="notice-bottom">
                                    *請盡速回函以便礁定人數，訂購餐盒，謝謝！<br>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="postcard-face" style="margin-top: 5px; margin-left: 5px;">
                            <div class="recipient-zip zip-code-zone">
                                <div class="zip-container">
                                    <div class="zip-box"><?php echo htmlspecialchars($zip_part1[0]); ?></div>
                                    <div class="zip-box"><?php echo htmlspecialchars($zip_part1[1]); ?></div>
                                    <div class="zip-box"><?php echo htmlspecialchars($zip_part1[2]); ?></div>
                                </div>
                                <div class="zip-hyphen">-</div>
                                <div class="zip-container">
                                    <div class="zip-box"><?php echo htmlspecialchars($zip_part2[0]); ?></div>
                                    <div class="zip-box"><?php echo htmlspecialchars($zip_part2[1]); ?></div>
                                    <div class="zip-box"><?php echo htmlspecialchars($zip_part2[2]); ?></div>
                                </div>
                            </div>

                            <div class="stamp-placeholder">
                                <span class="stamp-placeholder"></span>
                            </div>

                            <div class="sender-zip zip-code-zone">
                                <div class="zip-container">
                                    <div class="zip-box">4</div>
                                    <div class="zip-box">3</div>
                                    <div class="zip-box">7</div>
                                </div>
                                <div class="zip-hyphen">-</div>
                                <div class="zip-container">
                                    <div class="zip-box">0</div>
                                    <div class="zip-box">1</div>
                                    <div class="zip-box">2</div>
                                </div>
                            </div>

                            <div class="floating-block vertical-text-mode">
                                <h1>
                                    <input type="text" name="worship_name" value="<?php echo htmlspecialchars($user_real_name); ?>" readonly><?php echo htmlspecialchars($gender_title); ?>收
                                </h1>
                            </div>

                            <div class="recipient-address vertical-text-mode">
                                <div class="recipient-address-content"><?php echo convertAddressNumbers($db_address); ?></div>
                                <input type="hidden" name="worship_address" value="<?php echo htmlspecialchars($db_address); ?>">
                            </div>

                            <div class="sender-address vertical-text-mode">
                                寄件人：台中市李武略派下李氏宗親會 敬啟<br>
                                &emsp;&emsp;&emsp;&emsp;臺中市大甲區義和里中山路一段<span class="combine-num">484</span>巷<span
                                    class="combine-num">45</span>號
                            </div>

                            <div class="submit-container">
                                <button type="submit" class="submit-btn">送出</button>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="instruction-table" style="margin-top: 5px;">
                <tr>
                    <td>
                        <div class="instruction-box">
                            <h2>祭祖活動出席統計<br>(3x5 英吋迷你尺寸明信片)</h2>
                            <h3 style="margin-top: 15px;">系統操作說明</h3>
                            <div class="instruction-item" style="margin-top: 10px;">
                                <strong>A. 願意出席活動：</strong><br>
                                1.確認資料是否有錯誤。<br>
                                有誤，至"會員基本資料"修改，再回來填寫資料。<br>
                                2.攜伴參加者，填「攜伴」名字或家庭稱謂。<br>
                                3.填入總人數做，註明葷食與素食數量。<br>
                                4.確認無錯，按"送出"按紐即可。<br>
                            </div>
                            <div class="instruction-item">
                                <strong>B. 無法出席：</strong><br>
                                確認資料正確，有誤至"會員基本資料"修改。<br>
                                填寫代理出席者(限有會員編號的人)再"送出"。
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

        </div>
    </form>

    <script>
        const stamp = document.querySelector('.stamp-placeholder');
        const images = [
            "https://upload.wikimedia.org/wikipedia/commons/b/b6/Nicaragua1_1913.jpg",
            "https://www.post.gov.tw/post/FileCenter/post_ww2/stamp_pic/stamp_bpic/D561_02.561.1B.jpg",
            "https://www.post.gov.tw/post/FileCenter/post_ww2/stamp_pic/stamp_bpic/A148_01.1B.jpg",
            "https://www.post.gov.tw/post/FileCenter/post_ww2/stamp_pic/stamp_bpic/A146III_01.9-B.jpg",
            "https://www.post.gov.tw/post/FileCenter/post_ww2/stamp_pic/stamp_bpic/A148II_01.jpg",
            "https://www.post.gov.tw/post/FileCenter/post_ww2/stamp_pic/stamp_bpic/30809909.JPG",
            "https://www.cepp.gov.tw/Uploads/WebLevelD/gz1hu1xp.toj.jpg"
        ];
        let index = 0;
        setInterval(() => {
            stamp.style.backgroundImage = `url(${images[index]})`;
            index = (index + 1) % images.length;
        }, 1000);
    </script>
</body>

</html>