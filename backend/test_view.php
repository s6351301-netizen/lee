<?php
require_once 'api_account-members.php';

// 後端處理邏輯
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    
    if ($_POST['action'] === 'delete') {
        $conn->query("DELETE FROM account WHERE id = $id");
        $conn->query("DELETE FROM members WHERE id = $id");
    } elseif ($_POST['action'] === 'update') {
        // 取得所有編輯欄位的值
        $email = $conn->real_escape_string($_POST['account_email']);
        $new_member = $conn->real_escape_string($_POST['account_new_member']);
        $name = $conn->real_escape_string($_POST['account_name']);
        $gender = $conn->real_escape_string($_POST['account_gender']);
        $houses = intval($_POST['number_of_houses']);
        $shizu = intval($_POST['emperor_shizu']);
        $gen = intval($_POST['generation']);
        $living = $conn->real_escape_string($_POST['living_status']);
        $status = $conn->real_escape_string($_POST['account_status']);
        
        // 呼叫預存程序
        $stmt = $conn->prepare("CALL sync_account_members(?, ?, ?, ?)");
        $stmt->bind_param("isss", $id, $name, $new_member, $status);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$data_list = getAllMemberData($conn);
?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Action<br>(功能)</th><th>account_id</th><th>member_id</th>
                <th>account_email</th><th>account_new_member</th>
                <th>account_name</th><th>account_gender</th>
                <th>number_of_houses</th><th>emperor_shizu<br>(世祖)</th>
                <th>generation<br>(世代)</th><th>living_status</th>
                <th>account_status</th><th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data_list as $row): ?>
            <tr>
                <form method="POST">
                    <td>
                        <input type="hidden" name="id" value="<?php echo $row['account_id']; ?>">
                        <button type="submit" name="action" value="update">儲存</button>
                        <button type="submit" name="action" value="delete" onclick="return confirm('確定刪除？')">刪除</button>
                    </td>
                    <td><?php echo $row['account_id']; ?></td>
                    <td><?php echo $row['member_id']; ?></td>
                    <td><input type="text" name="account_email" value="<?php echo $row['account_email']; ?>"></td>
                    <td><input type="text" name="account_new_member" value="<?php echo $row['account_new_member']; ?>"></td>
                    <td><input type="text" name="account_name" value="<?php echo $row['account_name']; ?>"></td>
                    <td><input type="text" name="account_gender" value="<?php echo $row['account_gender']; ?>"></td>
                    <td><input type="text" name="number_of_houses" value="<?php echo $row['number_of_houses']; ?>"></td>
                    
                    <td><input type="number" name="emperor_shizu" class="shizu" value="<?php echo $row['emperor_shizu']; ?>" onblur="syncGen(this, 'shizu')"></td>
                    <td><input type="number" name="generation" class="gen" value="<?php echo $row['generation']; ?>" onblur="syncGen(this, 'gen')"></td>
                    
                    <td><input type="text" name="living_status" value="<?php echo $row['living_status']; ?>"></td>
                    <td><input type="text" name="account_status" value="<?php echo $row['account_status']; ?>"></td>
                </form>
                <td><button onclick="window.open('test_member_details.php?id=<?php echo $row['account_id']; ?>', '_blank', 'width=800,height=600')">詳情</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function syncGen(el, type) {
    let row = el.closest('tr');
    let shizu = row.querySelector('.shizu');
    let gen = row.querySelector('.gen');
    
    if (type === 'shizu') {
        if (shizu.value !== "") {
            gen.value = parseInt(shizu.value) - 19;
            gen.readOnly = true; // 鎖定世代
        } else {
            gen.readOnly = false; // 若清除世祖，解除世代鎖定
        }
    } else if (type === 'gen') {
        if (gen.value !== "") {
            shizu.value = parseInt(gen.value) + 19;
            shizu.readOnly = true; // 鎖定世祖
        } else {
            shizu.readOnly = false; // 若清除世代，解除世祖鎖定
        }
    }
}
</script>