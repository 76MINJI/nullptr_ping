<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db-config.php';

if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}

if (isset($_GET['id'])) {
    $pkey = (int)$_GET['id'];
} else {
    $pkey = 0;

    $sql = "SELECT excuse_pkey, SUM(count) AS total_count
            FROM judgement_icon
            GROUP BY excuse_pkey
            HAVING total_count >= 2
            ORDER BY excuse_pkey DESC
            LIMIT 1";
    $res = $conn->query($sql);
    if ($row = $res->fetch_assoc()) {
        $pkey = (int)$row['excuse_pkey'];
    }
}

$user_pkey = isset($_SESSION['user_pkey']) ? (int)$_SESSION['user_pkey'] : 0;

$stmt = $conn->prepare("SELECT SUM(count) FROM judgement_icon WHERE excuse_pkey = ?");
$stmt->bind_param("i", $pkey);
$stmt->execute();
$stmt->bind_result($trial_count);
$stmt->fetch();
$stmt->close();

if ((int)$trial_count < 2) {
    echo "<script>
        alert('이 글은 아직 재판에 회부된 횟수가 부족하여 재판을 진행할 수 없습니다.');
        history.back();
    </script>";
    exit;
}

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
-- 재판 회부 수 집계 -> 2 이상인 글만 필터링
WHERE ep.pkey = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    exit('서버 오류가 발생했습니다.');
}
$stmt->bind_param("i", $pkey);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    echo "<script>
        alert('존재하지 않는 글입니다.');
        history.back();
    </script>";
    exit;
}


$base_pkey = (int)$post['base_pkey'];

if (isset($_GET['vote']) && in_array($_GET['vote'], ['0','1'])) {
    $_SESSION['last_vote_type'] = (int)$_GET['vote'];
    header("Location: Pvp.php?id={$pkey}");
    exit;
}

$tags = array_filter([
    $post['tag_place'],
    $post['tag_person'],
    $post['tag_time'],
    $post['tag_mood'],
]);
$postContent = $post['post_content'];
$solution = $post['solution_content'] ?? '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $ctype = (int)($_SESSION['last_vote_type'] ?? -1);
    $ctext = trim($_POST['content'] ?? '');

    if ($ctype !== -1 && $ctext !== '') {
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
            $stmtUpdate = $conn->prepare("
                UPDATE judgements 
                SET judgement_type = ?, vote_count = 1 
                WHERE pkey = ?
            ");
            $stmtUpdate->bind_param("ii", $ctype, $existing_jid);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            $jid = $existing_jid; 
        } else {
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
        $stmtBaseReply = $conn->prepare("INSERT INTO base_entity (insert_date) VALUES (NOW())");
        $stmtBaseReply->execute();
        $reply_base_pkey = $conn->insert_id;
        $stmtBaseReply->close();
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
            width: 90%;  
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

        .vote-left  { background: #5ab9ea; }  
        .vote-right { background: #ec6b6b; }  
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
            text-align: left;     
        }

        .vote-info-item.right {
            text-align: right;    
        }

        .inner {
            text-align: center;
        }

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
        <div class="tags">
            <strong>상황:</strong>
            <?php foreach ($tags as $tag): ?>
            <span class="tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>

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
