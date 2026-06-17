<?php
// api_account-members.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lee";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

/**
 * 取得檢視表所有資料
 */
function getAllMemberData($conn) {
    $sql = "SELECT * FROM account_members_view";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
?>