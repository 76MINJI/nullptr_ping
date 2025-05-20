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
  SELECT ep.base_pkey, ep.sub_pkey, ep.user_pkey, ep.content, be.insert_date
    FROM excuse_posts AS ep
    JOIN base_entity  AS be ON ep.base_pkey = be.pkey
   WHERE ep.pkey = ?
");
$stmt->bind_param("i", $post_pkey);
$stmt->execute();
$stmt->bind_result($base_pkey,$sub_pkey,$owner_pkey,$description,$created_at);
if (!$stmt->fetch()) {
    $base_pkey   = $sub_pkey = $owner_pkey = 0;
    $description = "[샘플] 아직 DB에 글이 없습니다.";
    $created_at  = date('Y-m-d H:i:s');
}
$stmt->close();

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
  body{margin:0;font-family:Arial,sans-serif;background:#f5f5f5}
  nav{background:#4cbfee;padding:10px}
  nav a{color:#fff;margin-right:15px;text-decoration:none;font-weight:bold}

  #container{display:flex;padding:20px}
  #detail{flex:1;background:#e0f7ff;padding:20px;border-radius:4px}
  #sidebar{width:300px;background:#fff7c2;padding:20px;margin-left:20px;border-radius:4px}

  /* 리뷰 바 & 리스트 */
  #reviews-wrapper {
    margin:0 20px 20px;
    border:2px solid #4cbfee;
    border-radius:6px;
    background:#fff;
    overflow:hidden;
  }
  #review-bar {
    display:flex; align-items:center;
    padding:12px 20px;
    border-bottom:2px solid #4cbfee;
    background:#fafafa;
  }
  .star-rating input{display:none}
  .star-rating label{
    font-size:1.5em;color:#ccc;cursor:pointer;
  }
  .star-rating input:checked ~ label,
  .star-rating label:hover,
  .star-rating label:hover ~ label {
    color:#f39c12;
  }
  #review-bar .prompt {
    flex:1;
    margin:0 12px;
    color:#333;
  }
  #review-bar textarea {
    flex:1;
    height:80px;
    padding:8px;
    margin-right:12px;
  }
  #review-bar button {
    padding:6px 14px;
    background:#4cbfee;color:#fff;
    border:none;border-radius:3px;cursor:pointer;
  }
  #review-list {
    max-height:300px;overflow-y:auto;
  }
  .review-item {
    display:flex;align-items:flex-start;
    padding:12px 20px;border-bottom:1px solid #eee;
  }
  .review-item .rating{
    color:#f39c12;margin-right:12px;font-size:1.1em;
    min-width:30px;text-align:center;
  }
  .review-item .content-wrapper{flex:1}
  .review-item .username{font-weight:bold;margin-right:6px}
  .review-item .date{color:#666;font-size:0.85em}
  .review-item .text{margin:6px 0;line-height:1.4}
</style>
<script>
  function openReviewBar(){
    document.getElementById('btnAddReview').disabled = true;
    document.getElementById('review-bar').style.display = 'flex';
  }
  window.addEventListener('DOMContentLoaded',()=>{
    <?php if($show_form):?>
      openReviewBar();
    <?php endif;?>
  });
</script>
</head>
<body>

<nav>
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
      <span class="tag">장소</span>
      <span class="tag">시간</span>
      <span class="tag">사람</span>
      <span class="tag">무드</span>
    </div>
    <p><strong>설명:</strong><br><?= nl2br(htmlspecialchars($description))?></p>
    <div class="actions">
      <button onclick="alert('비공개 준비 중')">비공개</button>
      <a href="?id=<?= $post_pkey ?>&action=delete"
        onclick="return confirm('정말 삭제하시겠습니까?');">
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
    <div id="add-review-btn">
      <button id="btnAddReview"
              onclick="openReviewBar()"
              <?= $show_form?'disabled':''?>>
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
      <textarea name="comment" placeholder="나의 핑계 사용 리뷰 남기기"><?=htmlspecialchars($_POST['comment']??'')?></textarea>
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
