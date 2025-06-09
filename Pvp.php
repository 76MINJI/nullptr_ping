<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db-config.php';
$sql = "SELECT 
    --         ep.pkey AS post_pkey, 
    --         ep.base_pkey, 
    --         COUNT(j.pkey) AS total_votes
    -- FROM excuse_posts ep
    -- JOIN base_entity be ON ep.base_pkey = be.pkey
    -- JOIN judgements j ON ep.base_pkey = j.base_pkey
    -- WHERE j.judgement_type = 1
    --   AND DATE(j.insert_date) = CURDATE()
    -- GROUP BY ep.pkey, ep.base_pkey
    -- ORDER BY total_votes DESC
    -- LIMIT 1
        ep.pkey AS post_pkey
    FROM excuse_posts ep
    JOIN (
        SELECT base_pkey, COUNT(*) AS total_votes
        FROM judgements
        WHERE judgement_type = 1
        GROUP BY base_pkey
        ORDER BY total_votes DESC
        LIMIT 1
    ) j ON ep.base_pkey = j.base_pkey
";
$result = $conn->query($sql);
$top_post = $result->fetch_assoc();

// if ($top_post) {
//     header("Location: PVP.php" . $top_post['post_pkey']);
//     exit;
// } else {
//     echo "오늘은 아직 회부된 글이 없습니다.";
// }
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>PVP</title>
  <style>
    #container{display:flex;padding:20px}
    #detail{flex:1;background:#e0f7ff;padding:20px;border-radius:4px}
    #sidebar{width:300px;background:#fff7c2;padding:20px;margin-left:20px;border-radius:4px}

    .main-title {
        font-size: 40px;
        font-family: 'MainFont-Bold';
        margin-left: 60px;
        margin-top: 40px;
        margin-bottom: 1px;
        color: #1D1D1D;
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="main-title">핑계 재판소</div>
<!-- <p><strong>등록일:</strong> <?= date('Y.m.d', strtotime($post['insert_date'])) ?></p> -->
<!-- <a href="Otherping.php">← 목록으로 돌아가기</a> -->
<div id="container">
    <section id="detail">
        <div class="status">
            <p><strong>상황 :</strong>
            <!-- <span class="tag"><?= htmlspecialchars($post['tag_place'] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($post['tag_person'] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($post['tag_time'] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($post['tag_mood'] ?? '') ?></span> -->
        </div>
    <p><strong>설명 :</strong>
    <!-- <br><?= nl2br(htmlspecialchars($post['post_content']))?> -->
    </p>
</body>
</html>