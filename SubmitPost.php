<?php
include "db-config.php";
session_start();

$content = $_POST['content'] ?? '';
$place   = intval($_POST['place'] ?? 0);
$time    = intval($_POST['time'] ?? 0);
$person  = intval($_POST['person'] ?? 0);
$mood    = intval($_POST['mood'] ?? 0);
$status  = intval($_POST['status'] ?? 1);  // 공개/비공개
$user_pkey = $_SESSION['user_pkey'] ?? 0;  // 로그인 되어 있다면 해당 값 사용


// 유효성 검사
if (!$content || !$place || !$time || !$person || !$mood) {
    echo "<script>alert('모든 항목을 선택해주세요.'); history.back();</script>";
    exit;
}

// combo_key 계산 (각각 6비트 = 총 24비트)
$combo_key = ($place << 18) | ($person << 12) | ($time << 6) | $mood;

if (!$content) {
    echo "내용이 비어 있습니다.";
    exit;
}

$conn->query("INSERT INTO base_entity () VALUES ()");
$base_pkey = $conn->insert_id;

// 해답 검색
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

$sql = "INSERT INTO excuse_posts (
    base_pkey, sol_pkey, user_pkey, emotion_pkey, image_pkey, judgement_pkey,
    content, rating, status, view_count
) VALUES (
    -- $base_pkey, 1, 1, 1, 1, 1,
    -- '" . $conn->real_escape_string($content) . "', 3, 1, 0
    ?, ?, ?, 1, 1, 1, ?, 3, ?, 0
)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiisi", $base_pkey, $sol_pkey, $user_pkey, $content, $status);

// if ($conn->query($sql)) {
if ($stmt->execute()) {
    echo "<script>alert('글이 등록되었습니다.'); window.location.href='Viewmydetailping.php?id={$conn->insert_id}';</script>";
} else {
    // echo "오류 발생: " . $conn->error;
    echo "오류 발생: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
