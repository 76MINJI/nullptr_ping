<?php
session_start();
require_once __DIR__ . '/db-config.php';

if (!isset($_SESSION['id'])) {
    echo "<script>alert('로그인이 필요합니다.'); location.href='Login.php';</script>";
    exit;
}

$user_pkey = $_SESSION['user_pkey'] ?? 0;
$post_pkey = intval($_POST['post_pkey'] ?? 0);
$content   = $_POST['content'] ?? '';
$place     = intval($_POST['place'] ?? 0);
$person    = intval($_POST['person'] ?? 0);
$time      = intval($_POST['time'] ?? 0);
$mood      = intval($_POST['mood'] ?? 0);
$status    = intval($_POST['status'] ?? 1);

$combo_key = ($place << 18) | ($person << 12) | ($time << 6) | $mood;

$sol_stmt = $conn->prepare("SELECT pkey FROM solutions WHERE combo_key = ?");
$sol_stmt->bind_param("i", $combo_key);
$sol_stmt->execute();
$sol_result = $sol_stmt->get_result();
$sol_row = $sol_result->fetch_assoc();
$sol_pkey = $sol_row['pkey'] ?? null;
$sol_stmt->close();

if (!$sol_pkey) {
    echo "<script>alert('해당 조합의 해답이 없습니다.'); history.back();</script>";
    exit;
}

$img_stmt = $conn->prepare("SELECT pkey FROM post_images WHERE sub_pkey = ? LIMIT 1");
$img_stmt->bind_param("i", $person);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$img_row = $img_result->fetch_assoc();
$image_pkey = $img_row['pkey'] ?? 1;  
$img_stmt->close();

$update_stmt = $conn->prepare("
    UPDATE excuse_posts
    SET sol_pkey = ?, content = ?, image_pkey = ?, status = ?
    WHERE pkey = ? AND user_pkey = ?
");
echo "<pre>"; print_r($pings_by_date); echo "</pre>";

$update_stmt->bind_param("isiiii", $sol_pkey, $content, $image_pkey, $status, $post_pkey, $user_pkey);

if ($update_stmt->execute()) {
    echo "<script>alert('수정되었습니다.'); location.href='Viewmydetailping.php?id={$post_pkey}';</script>";
} else {
    echo "<script>alert('수정 실패: {$update_stmt->error}'); history.back();</script>";
}

$update_stmt->close();
$conn->close();
?>
