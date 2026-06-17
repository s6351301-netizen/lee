<?php
// 引用您定義好的 API
require_once 'api_account-members.php';

// 取得資料
$data_list = getAllMemberData($conn);
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <title>會員資料清單</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 1px;
            height: 95vh;
            display: flex;
            flex-direction: column;
        }

        .table-container {            
            overflow: auto;   /* 產生捲軸 */         
            /*flex-grow: 1;
            border: 1px solid #ccc;
            margin-top: 10px;
            */
        }

        table {
            border-collapse: collapse;
            width: 100%;
            white-space: nowrap;
        }

        th {
            background: #143622;
            color: white;
            padding: 10px;
            font-size: 13px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-size: 13px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        button {
            cursor: pointer;
            /*padding: 5px 10px;*/
        }
    </style>
</head>

<body>   

    <div class="table-container"><P>宗親會員資料清單</P>
        <?php if (!empty($data_list)): ?>
            <table>
                <thead>
                    <tr>
                        <th>account_id<br>(帳號-序號)</th>
                        <th>member_id<br>(會員-序號)</th>
                        <th>account_email<br>(登入帳號)</th>
                        <th>account_new_member<br>(編號)</th>
                        <th>account_name<br>(姓名)</th>
                        <th>account_gender<br>(性別)</th>
                        <th>number_of_houses<br>(大房)</th>
                        <th>emperor_shizu<br>(世祖)</th>
                        <th>generation<br>(世代)</th>
                        <th>living_status<br>(存/亡/未知)</th>
                        <th>account_status<br>(狀態)</th>
                        <th>操作<br>(Action)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_list as $row): ?>
                        <tr>
                            <td><?php echo $row['account_id'] ?? ''; ?></td>
                            <td><?php echo $row['member_id'] ?? ''; ?></td>
                            <td><?php echo $row['account_email'] ?? ''; ?></td>
                            <td><?php echo $row['account_new_member'] ?? ''; ?></td>
                            <td><?php echo $row['account_name'] ?? $row['member_name'] ?? ''; ?></td>
                            <td><?php echo $row['account_gender'] ?? ''; ?></td>
                            <td><?php echo $row['number_of_houses'] ?? ''; ?></td>
                            <td><?php echo $row['emperor_shizu'] ?? ''; ?></td>
                            <td><?php echo $row['generation'] ?? ''; ?></td>
                            <td><?php echo $row['living_status'] ?? ''; ?></td>
                            <td><?php echo $row['account_status'] ?? ''; ?></td>
                            <td>
                                <button onclick="window.open('member_details.php?id=<?php echo (int)$row['account_id']; ?>', '_blank', 'width=800,height=600')">
                                    詳情
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>目前查無資料。</p>
        <?php endif; ?>
    </div>
</body>

</html>