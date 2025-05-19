<?php
include "db-config.php";
session_start();

$content = $_POST['content'] ?? '';

if (!$content) {
    echo "내용이 비어 있습니다.";
    exit;
}

$conn->query("INSERT INTO base_entity () VALUES ()");
$base_pkey = $conn->insert_id;

$sql = "INSERT INTO excuse_posts (
    base_pkey, sub_pkey, user_pkey, emotion_pkey, image_pkey, judgement_pkey,
    content, rating, status, view_count
) VALUES (
    $base_pkey, 1, 1, 1, 1, 1,
    '" . $conn->real_escape_string($content) . "', 3, 1, 0
)";

if ($conn->query($sql)) {
    echo "<script>alert('글이 등록되었습니다.'); window.location.href='Viewmydetailping.php';</script>";
} else {
    echo "오류 발생: " . $conn->error;
}

$conn->close();
?>
