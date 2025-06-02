<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db-config.php';

// ① URL 파라미터에서 pkey 가져오기
$pkey = isset($_GET['pkey']) ? (int)$_GET['pkey'] : 0;
if ($pkey <= 0) {
    echo "잘못된 접근입니다.";
    exit;
}

// ② 글 내용 불러오기
$sql = "SELECT 
            ep.content AS post_content, 
            be.insert_date,
            s.content AS solution_content,  -- ← 해답 본문 가져오기
            st_place.sub_classification  AS tag_place,
            st_person.sub_classification AS tag_person,
            st_time.sub_classification   AS tag_time,
            st_mood.sub_classification   AS tag_mood
        FROM excuse_posts ep
        JOIN base_entity be ON ep.base_pkey = be.pkey
        LEFT JOIN solutions s ON ep.sol_pkey = s.pkey
        LEFT JOIN sub_tags st_place  ON st_place.pkey  = (s.combo_key >> 18)
        LEFT JOIN sub_tags st_person ON st_person.pkey = ((s.combo_key >> 12) & 63)
        LEFT JOIN sub_tags st_time   ON st_time.pkey   = ((s.combo_key >> 6) & 63)
        LEFT JOIN sub_tags st_mood   ON st_mood.pkey   = (s.combo_key & 63)
        WHERE ep.pkey = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pkey);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

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

if (!$post) {
    echo "존재하지 않는 글입니다.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>핑계 상세 보기</title>
  <style>
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
</head>
<body>
<?php include 'navbar.php'; ?>
<!-- <p><strong>등록일:</strong> <?= date('Y.m.d', strtotime($post['insert_date'])) ?></p> -->
<!-- <a href="Otherping.php">← 목록으로 돌아가기</a> -->
<div id="container">
    <section id="detail">
        <div class="status">
            <p><strong>상황:</strong>
            <span class="tag"><?= htmlspecialchars($post['tag_place'] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($post['tag_person'] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($post['tag_time'] ?? '') ?></span>
            <span class="tag"><?= htmlspecialchars($post['tag_mood'] ?? '') ?></span>
        </div>
    <p><strong>설명:</strong><br><?= nl2br(htmlspecialchars($post['post_content']))?></p>
    <div class="vote-dots">
      <span>●</span><span>●</span><span>●</span>
      <span>●</span><span>●</span> <strong>100</strong>
    </div>
    </section>

    <aside id="sidebar">
        <h3>핑계핑의 해답</h3>
        <p style="white-space: pre-line; font-size: 15px; line-height: 1.6;">
            <?= htmlspecialchars($post['solution_content'] ?? '해답이 존재하지 않습니다.') ?>
        </p>
        <div id="add-review-btn">
          <button id="btnAddReview"
                  onclick="openReviewBar()"
                  >
            <!--리뷰 추가 -->
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
        <!— <?php foreach($reviews as $r):?> —>
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
        <!— <?php endforeach;?> —>
      </div>
    </section>
</body>
</html>
