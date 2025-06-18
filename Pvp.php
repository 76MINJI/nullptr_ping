<?php
// Pvp.php

// 1) 세션 및 DB 설정
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db-config.php';

// 2) 로그인 체크
if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}

// 3) 조회할 글의 pkey 결정 
$pkey = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$user_pkey = isset($_SESSION['user_pkey']) ? (int)$_SESSION['user_pkey'] : 0;

if ($user_pkey <= 0) {
    die("잘못된 로그인 정보입니다. 다시 로그인해주세요.");
}


// 4) 글 내용 + 태그 + 해답 조회
$sql = "
SELECT
    ep.base_pkey,
    ep.content AS post_content,
    ep.user_pkey,
    be.insert_date,
    s.content AS solution_content,
    st_place.sub_classification AS tag_place,
    st_person.sub_classification AS tag_person,
    st_time.sub_classification AS tag_time,
    st_mood.sub_classification AS tag_mood
FROM excuse_posts ep
JOIN base_entity be ON ep.base_pkey = be.pkey
LEFT JOIN solutions s ON ep.sol_pkey = s.pkey
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

// 5) 존재하지 않는 글 처리
if (!$post) {
    echo "<script>
        alert('존재하지 않는 글입니다.');
        history.back();
    </script>";
    exit;
}

$base_pkey = (int)$post['base_pkey'];

// 6) 투표 처리
if (isset($_GET['vote']) && in_array($_GET['vote'], ['0','1'])) {
    $_SESSION['last_vote_type'] = (int)$_GET['vote'];
    header("Location: Pvp.php?id={$pkey}");
    exit;
}

// 7) 태그 배열 생성
$tags = array_filter([
    $post['tag_place'],
    $post['tag_person'],
    $post['tag_time'],
    $post['tag_mood'],
]);
$postContent = $post['post_content'];
$solution = $post['solution_content'] ?? '';

// 8) 투표 결과 집계
$sqlVote = "
    SELECT judgement_type, SUM(vote_count) AS cnt
    FROM judgements
    WHERE excuse_pkey = ?
    GROUP BY judgement_type
";
$stmtVote = $conn->prepare($sqlVote);
$stmtVote->bind_param("i", $pkey);
$stmtVote->execute();
$resVote = $stmtVote->get_result();

$notGuilty = 0;
$guilty = 0;
while ($rv = $resVote->fetch_assoc()) {
    if ((int)$rv['judgement_type'] === 0) $notGuilty = (int)$rv['cnt'];
    else $guilty = (int)$rv['cnt'];
}
$stmtVote->close();

$totalVotes = $notGuilty + $guilty;
$pctNot = $totalVotes > 0 ? round($notGuilty / $totalVotes * 100) : 50;
$pctGuilty = 100 - $pctNot;

// 9) 댓글 등록 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $ctype = (int)($_SESSION['last_vote_type'] ?? -1);
    $ctext = trim($_POST['content'] ?? '');

    if ($ctype !== -1 && $ctext !== '') {
        // 0. 투표 중복 여부 확인
        $stmtDupCheck = $conn->prepare("
            SELECT COUNT(*) FROM judgements_replies 
            WHERE excuse_pkey = ? AND user_pkey = ?
        ");
        $stmtDupCheck->bind_param("ii", $pkey, $user_pkey); 
        $stmtDupCheck->execute();
        $stmtDupCheck->bind_result($replyCount);        
        $stmtDupCheck->fetch();
        $stmtDupCheck->close();

        if ($replyCount > 0) {
            echo "<script>
                alert('이미 이 글에 댓글을 작성하셨습니다.');
                location.href='Pvp.php?id={$pkey}';
                </script>";
            exit;
        }

        if ($existing_jid) {
            // 1. 이미 투표한 경우 → 기존 투표 갱신
            $stmtUpdate = $conn->prepare("
                UPDATE judgements 
                SET judgement_type = ?, vote_count = 1 
                WHERE pkey = ?
            ");
            $stmtUpdate->bind_param("ii", $ctype, $existing_jid);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $jid = $existing_jid; // 나중에 댓글에 넣기 위함
        } else {
            // 2. 처음 투표하는 경우 → INSERT
            $stmtBase = $conn->prepare("INSERT INTO base_entity (insert_date) VALUES (NOW())");
            $stmtBase->execute();
            $new_base_pkey = $conn->insert_id;
            $stmtBase->close();

            $stmtInsert = $conn->prepare("
                INSERT INTO judgements (base_pkey, excuse_pkey, user_pkey, vote_count, judgement_type)
                VALUES (?, ?, ?, 1, ?)
            ");
            $stmtInsert->bind_param("iiii", $new_base_pkey, $pkey, $user_pkey, $ctype);
            $stmtInsert->execute();
            $jid = $conn->insert_id;
            $stmtInsert->close();
        }

        // 3. base_entity 생성 (댓글용)
        $stmtBaseReply = $conn->prepare("INSERT INTO base_entity (insert_date) VALUES (NOW())");
        $stmtBaseReply->execute();
        $reply_base_pkey = $conn->insert_id;
        $stmtBaseReply->close();

        // 4. judgements_replies INSERT
        $ins = $conn->prepare("INSERT INTO judgements_replies
            (base_pkey, excuse_pkey, judgement_pkey, user_pkey, content, vote)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins->bind_param("iiiisi", $reply_base_pkey, $pkey, $jid, $user_pkey, $ctext, $ctype);
        $ins->execute();
        $ins->close();

        header("Location: Pvp.php?id={$pkey}");
        exit;
    }
}

// 10) 댓글 불러오기
$comments = [];
$sql = "
    SELECT jr.pkey, jr.user_pkey, u.name AS username, jr.content, jr.vote, be.insert_date
    FROM judgements_replies jr
    JOIN users u         ON jr.user_pkey = u.pkey
    JOIN base_entity be  ON jr.base_pkey = be.pkey
    WHERE jr.excuse_pkey = ?
    ORDER BY be.insert_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pkey);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $comments[] = $row;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>핑계 재판소</title>
    <style>
        body { margin:0; font-family: MainFont-medium; }
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

        .vote-container {
            position: relative;
            width: 90%;     /* 전체 고정폭 */
            height: 50px;
            margin: 20px auto 8px;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
        }

        .vote-segment {
            min-width: 5%;
            display: block;
            height: 100%;
            text-decoration: none;
        }

        .vote-left  { background: #5ab9ea; }   /* 파란색 */
        .vote-right { background: #ec6b6b; }   /* 분홍색 */
        .vote-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
            margin: auto;
            font-size: 1em;
            color: #333;
        }
        .vote-info-item {
            box-sizing: border-box;
            padding: 6px 0;
        }
        .vote-info-item.left {
            text-align: left;       /* 왼쪽 정렬 */
        }

        .vote-info-item.right {
            text-align: right;      /* 오른쪽 정렬 */
        }

        .inner {
            text-align: center;
        }

        /* === 댓글 기능 스타일 추가 === */
        .comment-section {
            max-width: 90%;
            margin: 20px auto 40px;
            border: 2px solid #5ab9ea;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }
        .comment-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .comment-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .avatar {
            width: 40px; height: 40px;
            background: #ccc; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: #fff; margin-right: 12px;
        }
        .comment-text {
            flex: 1; margin-right: 12px;
        }
        .comment-tag {
            padding: 2px 6px; border-radius: 4px;
            font-size: 0.8rem; margin-right: 12px;
            color: #fff;
        }
        .comment-tag.innocent { background: #5ab9ea; }
        .comment-tag.guilty   { background: #ec6b6b; }
        .comment-date {
            font-size: 0.75rem; color: #666;
        }
        .comment-form {
            display: flex;
            border-top: 2px solid #5ab9ea;
        }
        .comment-input {
            flex: 1; display: flex; align-items: center; padding: 10px;
        }
        .comment-input label {
            margin-right: 12px;
            font-size: 0.9rem;
        }
        .comment-input textarea {
            flex: 1; resize: none; height: 40px;
            padding: 6px; margin-left: 12px;
            font-size: 0.9rem;
        }
        .comment-form button {
            background: #5ab9ea; color: #fff;
            border: none; padding: 0 20px;
            font-size: 0.9rem; cursor: pointer;
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
        </section>

        <aside id="sidebar">
            <h3>핑계 해답</h3>
            <div class="solution-box">
                <p><?= nl2br(htmlspecialchars($solution ?: '해답이 존재하지 않습니다.')) ?></p>
            </div>
        </aside>
    </div>
    <div class="vote-container">
        <a href="?id=<?= $pkey ?>&vote=0"
            class="vote-segment vote-left"
            style="width:<?= $pctNot ?>%;"></a>
        <a href="?id=<?= $pkey ?>&vote=1"
            class="vote-segment vote-right"
            style="width:<?= $pctGuilty ?>%;"></a>
    </div>
    <div class="vote-info">
        <div class="vote-info-item left">
            무죄다 (말이 된다) &nbsp; <?= $pctNot ?>% &nbsp; (<?= $notGuilty ?>표)
        </div>
        <div class="vote-info-item right">
            유죄다 (억지다) &nbsp; <?= $pctGuilty ?>% &nbsp; (<?= $guilty ?>표)
        </div>
    </div>

<div class="comment-section">
    <form method="post" class="comment-form">
        <div class="comment-input">
            <?php if (isset($_SESSION['last_vote_type'])): ?>
                <p style="margin:0 12px; font-size:0.9rem;">
                    선택한 판단: <strong><?= $_SESSION['last_vote_type'] == 0 ? '무죄' : '유죄' ?></strong>
                </p>
            <?php else: ?>
                <p style="margin:0 12px; font-size:0.9rem; color: red;">
                    댓글을 남기려면 먼저 위에서 투표해 주세요.
                </p>
            <?php endif; ?>

            <textarea name="content" placeholder="나의 판단 남기기" required></textarea>
        </div>
        <button type="submit" name="submit_comment" <?= isset($_SESSION['last_vote_type']) ? '' : 'disabled' ?>>등록</button>
    </form>

    <!-- ✅ 댓글 목록 -->
<!-- ✅ 댓글 목록 -->
    <?php if (!empty($comments)): ?>
        <div class="comment-list">
            <?php foreach ($comments as $c): ?>
                <div class="comment-item">
                    <div class="comment-text" style="flex:1;">
                        <?= nl2br(htmlspecialchars($c['content'], ENT_QUOTES, 'UTF-8')) ?>
                        <div class="comment-date"><?= date('y/m/d H:i', strtotime($c['insert_date'])) ?></div>
                    </div>
                    <div class="comment-tag <?= $c['vote'] == 0 ? 'innocent' : 'guilty' ?>">
                        <?= $c['vote'] == 0 ? '무죄' : '유죄' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center; color:#888; margin: 20px 0;">아직 댓글이 없습니다.</p>
    <?php endif; ?>

</div>


</body>
</html>
