<?php
session_start();
require_once __DIR__ . '/db-config.php';

// 테스트용 유저
$user_pkey  = 1;

// 파라미터
$post_pkey  = intval($_GET['id'] ?? 0);
$action     = $_GET['action'] ?? '';

// ── 삭제 로직(action=delete) ──
if ($action === 'delete') {
    $del = $conn->prepare("DELETE FROM reviews WHERE post_pkey = ?");
    $del->bind_param("i", $post_pkey); $del->execute(); $del->close();
    $del = $conn->prepare("DELETE FROM excuse_posts WHERE pkey = ?");
    $del->bind_param("i", $post_pkey); $del->execute(); $del->close();
    header("Location: Viewmyping.php"); exit;
}

// ── 게시물 로드 ──
$stmt = $conn->prepare("
  SELECT ep.base_pkey, ep.sol_pkey, ep.user_pkey, ep.content, be.insert_date,
    s.combo_key, s.sub_pkey
    FROM excuse_posts AS ep
    JOIN base_entity  AS be ON ep.base_pkey = be.pkey
  LEFT JOIN solutions AS s ON ep.sol_pkey = s.pkey
  WHERE ep.pkey = ?
");
$stmt->bind_param("i", $post_pkey);
$stmt->execute();
$stmt->bind_result($base_pkey,$sol_pkey,$owner_pkey,$description,$created_at, $combo_key, $sub_pkey);
if (!$stmt->fetch()) {
  $base_pkey   = $sol_pkey = $owner_pkey = $combo_key = $sub_pkey = 0;
  $description = "[샘플] 아직 DB에 글이 없습니다.";
  $created_at  = date('Y-m-d H:i:s');
}
$stmt->close();
function decodeComboKey($combo_key) {
  $place_pkey  = ($combo_key >> 18) & 0x3F;
  $person_pkey = ($combo_key >> 12) & 0x3F;
  $time_pkey   = ($combo_key >> 6)  & 0x3F;
  $mood_pkey   = $combo_key & 0x3F;
  
  return [$place_pkey, $person_pkey, $time_pkey, $mood_pkey];
}

function getSubTagName($conn, $pkey) {
  $stmt = $conn->prepare("SELECT sub_classification FROM sub_tags WHERE pkey = ?");
  $stmt->bind_param("i", $pkey);
  $stmt->execute();
  $stmt->bind_result($name);
  if ($stmt->fetch()) return $name;
  return null;
}

list($place_pkey, $person_pkey, $time_pkey, $mood_pkey) = decodeComboKey($combo_key);

$post['tag_place']  = getSubTagName($conn, $place_pkey);
$post['tag_person'] = getSubTagName($conn, $person_pkey);
$post['tag_time']   = getSubTagName($conn, $time_pkey);
$post['tag_mood']   = getSubTagName($conn, $mood_pkey);

// 
$solution_text = '';
if ($sol_pkey) {
    $stmt = $conn->prepare("SELECT content FROM solutions WHERE pkey = ?");
    $stmt->bind_param("i", $sol_pkey);
    $stmt->execute();
    $stmt->bind_result($solution_text);
    $stmt->fetch();
    $stmt->close();
}


// ── 리뷰 등록 처리 ──
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_review'])) {
    $rating  = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating>=1 && $rating<=5 && $comment!=='') {
        $ins = $conn->prepare("
          INSERT INTO reviews
            (base_pkey, user_pkey, post_pkey, content, rating, status, view_count)
          VALUES (?, ?, ?, ?, ?, 1, 0)
        ");
        $ins->bind_param("iiisi",
          $base_pkey, $user_pkey, $post_pkey, $comment, $rating
        );
        $ins->execute(); $ins->close();
        header("Location: Viewmydetailping.php?id={$post_pkey}");
        exit;
    }
    $error = "별점(1~5)과 리뷰 내용을 모두 입력해주세요.";
}

// ── 리뷰 목록 로드 ──
$reviews = [];
$stmt = $conn->prepare("
  SELECT r.user_pkey, u.name AS username, r.content, r.rating, be.insert_date
    FROM reviews AS r
    JOIN users       AS u  ON r.user_pkey = u.pkey
    JOIN base_entity AS be ON r.base_pkey  = be.pkey
    WHERE r.post_pkey = ? AND r.status=1
    ORDER BY be.insert_date DESC
");
$stmt->bind_param("i", $post_pkey);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) $reviews[] = $row;
$stmt->close();

// 더미 리뷰
if (count($reviews)===0) {
    $reviews[] = [
      'user_pkey'=>0,
      'username'=>'테스트유저',
      'content'=>'[샘플 리뷰] 화면 렌더링 확인용',
      'rating'=>4,
      'insert_date'=>date('Y-m-d H:i:s'),
    ];
}

$show_form = ($action==='add_review');
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>View My Detail Ping</title>
    <style>
    @font-face {
        font-family: 'MainFont-Bold';
        src: url('fonts/GmarketSansTTFBold.ttf') format('truetype');
        font-weight: 700;
        font-style: normal;
    }

    @font-face {
        font-family: 'MainFont-Medium';
        src: url('fonts/GmarketSansTTFMedium.ttf') format('truetype');
        font-weight: 500;
        font-style: normal;
    }

    @font-face {
        font-family: 'MainFont-Light';
        src: url('fonts/GmarketSansTTFLight.ttf') format('truetype');
        font-weight: 300;
        font-style: normal;
    }

    /* 2) 사용 */
    body {
        margin: 0;
        background: #f5f5f5;
        /* 기본 텍스트는 Medium */
        font-family: 'MainFont-Medium', sans-serif;
    }

    nav {
        display: flex;
        align-items: center;
        background: #00C3FF;
        padding: 10px;
        font-family: 'MainFont-Bold', sans-serif;
    }

    .nav-logo {
        display: inline-block;
        vertical-align: middle;
        margin-left: 12px;
        margin-right: 12px;
    }

    .nav-logo img {
        height: 32px;
        width: auto;
    }

    nav a {
        color: #f5f5f5;
        text-decoration: none;
        font-weight: normal;
        margin-right: 15px;
    }

    /* 마지막 링크에만 자동 마진 */
    nav a:last-child {
        margin-left: auto;
        margin-right: 12px;
    }

    #container {
        display: flex;
        padding: 20px
    }

    #detail {
        flex: 1;
        background: #e0f7ff;
        padding: 20px;
        border-radius: 4px
    }

    #sidebar {
        width: 300px;
        background: #fff7c2;
        padding: 20px;
        margin-left: 20px;
        border-radius: 4px
    }

    /* 리뷰 바 & 리스트 */
    #reviews-wrapper {
        margin: 0 20px 20px;
        border: 2px solid #4cbfee;
        border-radius: 6px;
        background: #fff;
        overflow: hidden;
    }

    #review-bar {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        border-bottom: 2px solid #4cbfee;
        background: #fafafa;
    }

    .star-rating input {
        display: none
    }

    .star-rating label {
        font-size: 1.5em;
        color: #ccc;
        cursor: pointer;
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #f39c12;
    }

    #review-bar .prompt {
        flex: 1;
        margin: 0 12px;
        color: #333;
    }

    #review-bar textarea {
        flex: 1;
        height: 80px;
        padding: 8px;
        margin-right: 12px;
    }

    #review-bar button {
        padding: 6px 14px;
        background: #4cbfee;
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }

    #review-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .review-item {
        display: flex;
        align-items: flex-start;
        padding: 12px 20px;
        border-bottom: 1px solid #eee;
    }

    .review-item .rating {
        color: #f39c12;
        margin-right: 12px;
        font-size: 1.1em;
        min-width: 30px;
        text-align: center;
    }

    .review-item .content-wrapper {
        flex: 1
    }

    .review-item .username {
        font-weight: bold;
        margin-right: 6px
    }

    .review-item .date {
        color: #666;
        font-size: 0.85em
    }

    .review-item .text {
        margin: 6px 0;
        line-height: 1.4
    }
    </style>
    <script>
    function openReviewBar() {
        document.getElementById('btnAddReview').disabled = true;
        document.getElementById('review-bar').style.display = 'flex';
    }
    window.addEventListener('DOMContentLoaded', () => {
        <?php if($show_form):?>
        openReviewBar();
        <?php endif;?>
    });
    </script>
</head>

<body>
    <nav>
        <div class="nav-logo">
            <img src="img\LOGO_nullptr.png" alt="Logo" />
        </div>
        <a href="Makeping.php">MAKE PING</a>
        <a href="Myping.php">MY PING</a>
        <a href="Otherping.php">OTHER PING</a>
        <a href="Pvp.php">PING vs PING</a>
        <a href="Mypage.php">MYPAGE</a>
    </nav>

    <div id="container">
        <section id="detail">
            <!-- 상세 -->
            <div class="status">
                <p><strong>상황:</strong>
                    <span class="tag"><?= htmlspecialchars($post['tag_place'] ?? '') ?></span>
                    <span class="tag"><?= htmlspecialchars($post['tag_person'] ?? '') ?></span>
                    <span class="tag"><?= htmlspecialchars($post['tag_time'] ?? '') ?></span>
                    <span class="tag"><?= htmlspecialchars($post['tag_mood'] ?? '') ?></span>
            </div>
            <p><strong>설명:</strong><br><?= nl2br(htmlspecialchars($description))?></p>
            <div class="actions">
                <button onclick="alert('비공개 준비 중')">비공개</button>
                <a href="?id=<?= $post_pkey ?>&action=delete" onclick="return confirm('정말 삭제하시겠습니까?');">
                    <button>글 삭제</button>
                </a>
                <a href="Updateping.php?id=<?= $post_pkey ?>"><button>글 수정</button></a>
            </div>
            <div class="vote-dots">
                <span>●</span><span>●</span><span>●</span>
                <span>●</span><span>●</span> <strong>100</strong>
            </div>
        </section>

        <aside id="sidebar">
            <h3>핑계핑의 해답</h3>
            <!-- 리뷰 추가 버튼만 -->
            <p style="font-size:1.1em; line-height:1.5em;">
                <?= nl2br(htmlspecialchars($solution_text ?: '[해답 없음]')) ?>
            </p>
            <div id="add-review-btn">
                <button id="btnAddReview" onclick="openReviewBar()" <?= $show_form?'disabled':''?>>
                    리뷰 추가
                </button>
            </div>
            <!-- 리뷰 리스트(항상 노출) -->
            <div id="review-list">
                <?php foreach($reviews as $r):?>
                <div class="review-item">
                    <div class="rating">★ <?=$r['rating']?></div>
                    <div class="content-wrapper">
                        <div>
                            <span class="username"><?=htmlspecialchars($r['username'])?></span>
                            <span class="date"><?=$r['insert_date']?></span>
                        </div>
                        <div class="text"><?=nl2br(htmlspecialchars($r['content']))?></div>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </aside>
    </div>

    <!-- 리뷰 작성 폼 & 리스트 전체 밑에 배치 -->
    <section id="reviews-wrapper">
        <div id="review-bar" style="display:none;">
            <form method="post" action="?id=<?= $post_pkey ?>&action=add_review" style="display:flex;flex:1;">
                <div class="star-rating">
                    <?php for($i=5;$i>=1;$i--):?>
                    <input type="radio" id="r<?=$i?>" name="rating" value="<?=$i?>"
                        <?= (isset($_POST['rating'])&&$_POST['rating']==$i)?'checked':''?>>
                    <label for="r<?=$i?>">★</label>
                    <?php endfor;?>
                </div>
                <textarea name="comment"
                    placeholder="나의 핑계 사용 리뷰 남기기"><?=htmlspecialchars($_POST['comment']??'')?></textarea>
                <button type="submit" name="submit_review">등록</button>
            </form>
        </div>
        <div id="review-list">
            <?php foreach($reviews as $r):?>
            <div class="review-item">
                <div class="rating">★ <?=$r['rating']?></div>
                <div class="content-wrapper">
                    <div>
                        <span class="username"><?=htmlspecialchars($r['username'])?></span>
                        <span class="date"><?=$r['insert_date']?></span>
                    </div>
                    <div class="text"><?=nl2br(htmlspecialchars($r['content']))?></div>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </section>

</body>

</html>