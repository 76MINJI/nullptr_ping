<?php
// Pvp.php

// ─── 1) 세션 및 DB 설정 ───────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db-config.php';

// ─── 2) 로그인 체크 ───────────────────────────────────
if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}

// ─── 3) 조회할 글의 pkey 결정 ────────────────────────────
// 테스트용: URL 파라미터가 있으면 그 값을, 없으면 기본 1번 글
$pkey = isset($_GET['id']) ? (int)$_GET['id'] : 1;

/*
// 3-1) URL 파라미터 id 가 있으면 우선 그 글을 시도
if (isset($_GET['id'])) {
    $pkey = (int)$_GET['id'];
} else {
    // 3-2) 없으면 judgement_icon에서 count >= 2인 글 중 하나를 랜덤으로
    $iconSql = "
      SELECT DISTINCT excuse_pkey
      FROM judgement_icon
      WHERE count >= 2
      ORDER BY RAND()
      LIMIT 1
    ";
    $iconRes = $conn->query($iconSql);
    if ($iconRes && $iconRes->num_rows > 0) {
        $row   = $iconRes->fetch_assoc();
        $pkey  = (int)$row['excuse_pkey'];
    } else {
        // 해당 조건의 글이 전혀 없을 때
        echo "<script>
            alert('아직 회부가 2회 이상인 글이 없습니다.');
            history.back();
        </script>";
        exit;
    }
}
*/

// ─── 4) 글 내용 + 태그 + 해답 조회용 SQL ────────────────
$sql = "
SELECT
    ep.base_pkey,
    ep.content              AS post_content,
    ep.user_pkey,
    be.insert_date,
    s.content               AS solution_content,
    st_place.sub_classification  AS tag_place,
    st_person.sub_classification AS tag_person,
    st_time.sub_classification   AS tag_time,
    st_mood.sub_classification   AS tag_mood
FROM excuse_posts ep
JOIN base_entity be    ON ep.base_pkey = be.pkey
LEFT JOIN solutions s  ON ep.sol_pkey  = s.pkey
LEFT JOIN sub_tags st_place  ON st_place.pkey  = (s.combo_key >> 18)
LEFT JOIN sub_tags st_person ON st_person.pkey = ((s.combo_key >> 12) & 63)
LEFT JOIN sub_tags st_time   ON st_time.pkey   = ((s.combo_key >> 6) & 63)
LEFT JOIN sub_tags st_mood   ON st_mood.pkey   = (s.combo_key & 63)
WHERE ep.pkey = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    exit('서버 오류가 발생했습니다.');
}
$stmt->bind_param("i", $pkey);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ─── 5) 혹시 해당 pkey 글이 없으면 안내 후 종료 ────────────
if (!$post) {
    echo "<script>
        alert('존재하지 않는 글입니다.');
        history.back();
    </script>";
    exit;
}

// ─── 6) 태그 배열 생성 (null 제거) ───────────────────────
$tags = array_filter([
    $post['tag_place'],
    $post['tag_person'],
    $post['tag_time'],
    $post['tag_mood'],
]);

// ─── 7) 본문·해답 변수 준비 ─────────────────────────────
$postContent = $post['post_content'];
$solution    = $post['solution_content'] ?? '';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>핑계 재판소</title>
  <style>
    body { margin:0; font-family: sans-serif; }
    #container { display: flex; padding: 20px; }
    section#detail {
      flex: 1;
      background: #e0f7ff;
      padding: 20px;
      border-radius: 4px;
    }
    aside#sidebar {
      width: 300px;
      background: #fff7c2;
      padding: 20px;
      margin-left: 20px;
      border-radius: 4px;
    }
    .main-title {
      font-size: 40px;
      font-weight: bold;
      margin: 40px 0 10px 60px;
      color: #1D1D1D;
    }
    .tags .tag {
      display: inline-block;
      background: #fff7c2;
      color: #333;
      padding: 4px 8px;
      margin-right: 6px;
      border-radius: 4px;
      font-size: 0.9rem;
    }
    .description p {
      line-height: 1.6;
      white-space: pre-line;
    }
    .solution-box {
      background: #fff;
      padding: 14px;
      border-radius: 4px;
      box-shadow: 0 0 4px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="main-title">핑계 재판소</div>
  <div id="container">
    <section id="detail">
      <!-- 상황 태그 -->
      <div class="tags">
        <strong>상황:</strong>
        <?php foreach ($tags as $tag): ?>
          <span class="tag"><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      </div>

      <!-- 설명 -->
      <div class="description">
        <strong>설명:</strong>
        <p><?= nl2br(htmlspecialchars($postContent)) ?></p>
      </div>

      <!-- TODO: 이모션 아이콘, 회부 버튼, 리뷰 등 추가 -->
    </section>

    <aside id="sidebar">
      <h3>핑계 해답</h3>
      <div class="solution-box">
        <p><?= nl2br(htmlspecialchars($solution ?: '해답이 존재하지 않습니다.')) ?></p>
      </div>
      <!-- TODO: 리뷰 폼 및 리스트 -->
    </aside>
  </div>
</body>
</html>
