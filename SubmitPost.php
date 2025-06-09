<?php
include "db-config.php";
session_start();

$content    = $_POST['content'] ?? '';
$place      = intval($_POST['place'] ?? 0);
$time       = intval($_POST['time'] ?? 0);
$person     = intval($_POST['person'] ?? 0);
$mood       = intval($_POST['mood'] ?? 0);
$status     = intval($_POST['status'] ?? 1);  // 1 공개 0 비공개
$user_pkey  = $_SESSION['user_pkey'] ?? 0;

if (!$content || !$place || !$time || !$person || !$mood) {
    echo "<script>alert('모든 항목을 선택해주세요.'); history.back();</script>";
    exit;
}

$combo_key = ($place << 18) | ($person << 12) | ($time << 6) | $mood;

$conn->query("INSERT INTO base_entity () VALUES ()");
$base_pkey = $conn->insert_id;

$sol_stmt = $conn->prepare("
    SELECT pkey FROM solutions 
    WHERE combo_key = ? LIMIT 1
");
$sol_stmt->bind_param("i", $combo_key);
$sol_stmt->execute();
$sol_result = $sol_stmt->get_result();
$sol_row = $sol_result->fetch_assoc();
$sol_pkey = $sol_row['pkey'] ?? null;
$sol_stmt->close();

if (!$sol_pkey) {
    echo "<script>alert('해답이 없습니다.'); history.back();</script>";
    exit;
}

$img_stmt = $conn->prepare("
    SELECT pkey FROM post_images 
    WHERE sub_pkey = ? LIMIT 1
");
$img_stmt->bind_param("i", $person);
$img_stmt->execute();
$img_result = $img_stmt->get_result();
$img_row = $img_result->fetch_assoc();
$image_pkey = $img_row['pkey'] ?? null;
$img_stmt->close();


$sql = "INSERT INTO excuse_posts (
    base_pkey, sol_pkey, user_pkey, image_pkey,
    content, rating, status
) VALUES (
    ?, ?, ?, ?, ?, 3, ?
)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiisi", $base_pkey, $sol_pkey, $user_pkey, $image_pkey, $content, $status);

if ($stmt->execute()) {
    echo "<script>alert('글이 등록되었습니다.'); window.location.href='Viewmydetailping.php?id={$conn->insert_id}';</script>";
} else {
    echo "오류 발생: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
