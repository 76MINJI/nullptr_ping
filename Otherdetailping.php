<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db-config.php';

$user_pkey = $_SESSION['user_pkey'] ?? null;

if ($user_pkey === null) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}


// ① URL 파라미터에서 pkey 가져오기
$pkey = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pkey <= 0) {
    echo "잘못된 접근입니다.";
    exit;
}

// ② 글 내용 불러오기
$sql = "SELECT
            ep.base_pkey,
            ep.content AS post_content,
            ep.user_pkey,
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
$stmt->close();

if (!$post) {
    echo "존재하지 않는 글입니다.";
    exit;
}

$current_user_pkey = $_SESSION['user_pkey'] ?? 0;  // 현재 로그인 사용자
$post_pkey = $pkey;
$base_pkey = $post['base_pkey'];
$is_owner = ($post['user_pkey'] ?? -1) == $current_user_pkey;

// ㅡ 이모티콘
// 글의 pkey는 URL로부터
// $post_pkey = isset($_GET['pkey']) ? (int)$_GET['pkey'] : 0;
//$post_pkey = (int)($_GET['id'] ?? 0);

// ── 클릭 처리 ──
$clicked_icon = isset($_GET['icon']) ? (int)$_GET['icon'] : 0;

if ($clicked_icon >= 1 && $clicked_icon <= 5 && !$is_owner) {
    // 테이블 매핑
    switch ($clicked_icon) {
        case 1: $icon_table = 'useful_icon'; break;
        case 2: $icon_table = 'funny_icon'; break;
        case 3: $icon_table = 'bad_icon'; break;
        case 4: $icon_table = 'agree_icon'; break;
        case 5: $icon_table = 'angry_icon'; break;
        default:
            header("Location: Otherdetailping.php?id={$pkey}");
            exit;
    }

    // excuse_pkey 할당
    $excuse_pkey = $pkey;

    // 중복 클릭 방지 (옵션)
    $checkSql  = "SELECT pkey FROM {$icon_table} WHERE base_pkey = ? AND user_pkey = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $base_pkey, $user_pkey);
    $checkStmt->execute();
    $exists = $checkStmt->fetch();
    $checkStmt->close();

    if (!$exists) {
        $ins = $conn->prepare("INSERT INTO {$icon_table} (excuse_pkey, user_pkey, base_pkey, value) VALUES (?, ?, ?, 1)");
        $ins->bind_param("iii", $excuse_pkey, $user_pkey, $base_pkey);
        $ins->execute();
        $ins->close();
    }

    header("Location: Otherdetailping.php?id={$pkey}");
    exit;
}



// ── 이모션 개수 불러오기 ──
$emotion_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$placeholders = implode(',', array_fill(0, count($emotion_counts), '?'));
$sql2 = "
    SELECT 1 AS icon, COUNT(*) AS icon_count FROM useful_icon WHERE base_pkey = ?
    UNION ALL
    SELECT 2 AS icon, COUNT(*) AS icon_count FROM funny_icon WHERE base_pkey = ?
    UNION ALL
    SELECT 3 AS icon, COUNT(*) AS icon_count FROM bad_icon WHERE base_pkey = ?
    UNION ALL
    SELECT 4 AS icon, COUNT(*) AS icon_count FROM agree_icon WHERE base_pkey = ?
    UNION ALL
    SELECT 5 AS icon, COUNT(*) AS icon_count FROM angry_icon WHERE base_pkey = ?
";
$params = [$base_pkey, $base_pkey, $base_pkey, $base_pkey, $base_pkey];
$types = str_repeat('i', count($params));


$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param($types, ...$params);
$stmt2->execute();
$stmt2->bind_result($iconType, $iconCount);
while ($stmt2->fetch()) {
  $emotion_counts[$iconType] = $iconCount;
}
$stmt2->close();


// 해당 글의 base_pkey 가져오기
//$sql = "SELECT base_pkey FROM excuse_posts WHERE pkey = ?";
//$stmt = $conn->prepare($sql);
$stmt = $conn->prepare("SELECT base_pkey, user_pkey FROM excuse_posts WHERE pkey = ?");
$stmt->bind_param("i", $post_pkey);
$stmt->execute();
//$stmt->bind_result($base_pkey);
$stmt->bind_result($base_pkey, $owner_user_pkey);
$stmt->fetch();
$stmt->close();

// 본인 글 여부
$is_owner = ($owner_user_pkey === $current_user_pkey);
$user_pkey = $_SESSION['user_pkey'] ?? 0;
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
    header("Location: Otherdetailping.php?id={$post_pkey}");
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
  // ── 재판회부 아이콘 ──
  $excuse_pkey = $_GET['id'] ?? null;
  $clicked_judgement = isset($_GET['judgement']) ? true : false;
  
  if ($clicked_judgement && !$is_owner) {
      $checkSql = "SELECT pkey FROM judgement_icon WHERE base_pkey = ? AND excuse_pkey = ? AND user_pkey = ? LIMIT 1";
      $checkStmt = $conn->prepare($checkSql);
      $checkStmt->bind_param("iii", $base_pkey, $excuse_pkey, $current_user_pkey);
      $checkStmt->execute();
      $exists = $checkStmt->fetch();
      $checkStmt->close();
  
      if (!$exists) {
          $ins = $conn->prepare("INSERT INTO judgement_icon (base_pkey, excuse_pkey, user_pkey, count) VALUES (?, ?, ?, 1)");
          $ins->bind_param("iii", $base_pkey, $excuse_pkey, $current_user_pkey);
          $ins->execute();
          $ins->close();

          header("Location: Otherdetailping.php?id={$excuse_pkey}");
          exit;
      } else {
          echo "<script>
                  alert('재판 회부는 1회만 가능합니다.');
                  location.href='Otherdetailping.php?id={$excuse_pkey}';
                </script>";
          exit;
      }
  }
  
  
  


// ── 회부 수 조회 ──
$stmt = $conn->prepare("SELECT SUM(count) FROM judgement_icon WHERE base_pkey = ? AND excuse_pkey = ?");
$stmt->bind_param("ii", $base_pkey, $excuse_pkey);
$stmt->execute();
$stmt->bind_result($trial_count);
$stmt->fetch();
$stmt->close();



// ── 내가 이미 회부했는지 여부 확인 (버튼 비활성화용) ──
$stmt = $conn->prepare("SELECT COUNT(*) FROM judgements WHERE base_pkey = ? AND user_pkey = ? AND judgement_type = 1");
$stmt->bind_param("ii", $base_pkey, $current_user_pkey);
$stmt->execute();
$stmt->bind_result($already_clicked);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>View Other Detail Ping</title>
  <style>
    #container{display:flex;padding:20px}
    #detail{flex:1;background:#e0f7ff;padding:20px;border-radius:4px}
    #sidebar{width:300px;background:#fff7c2;padding:20px;margin-left:20px;border-radius:4px}

    .emotions-container {
        display: flex;
        align-items: center;
        gap: 20px;            
        padding: 12px;
        background: #e0f7ff;  
        border-radius: 6px;
        margin-top: 20px;     
    }
    .emotion-item {
        text-align: center;
        font-family: 'MainFont-Medium', sans-serif;
    }
    .emotion-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #fff;
        padding: 6px;
        object-fit: contain;
        box-shadow: 0 0 4px rgba(0,0,0,0.1);
        cursor: pointer;
    }
    .emotion-label {
        margin-top: 4px;
        font-size: 0.85rem;
        color: #333;
    }
    .emotion-count {
        margin-top: 2px;
        font-size: 0.8rem;
        color: #666;
    }

    /* ── 재판 회부 버튼  ── */
    .trial-btn-wrapper {
        /* position: absolute; */
        margin-left: auto;
        bottom: 20px;  
        right: 20px;   
        /* display: flex;
        align-items: center;
        gap: 8px; */
    }
    .trial-btn {
        padding: 8px 16px;
        background-color: #fff7c2;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: bold;
        cursor: pointer;     /* 클릭 불가
        /*opacity: 0.6;        /* 비활성화 느낌 */
    }

    .trial-btn .label {
      color: #666;
      font-weight: bold;
    }
    .trial-btn .count {
      font-size: 1rem;
      color: #4cbfee;
      font-weight: bold;
    }
    .trial-btn:hover:not([disabled]) {
    background-color: #fff7c2;
    }

    .trial-btn[disabled] {
    opacity: 0.5;
    /* cursor: not-allowed; */
    cursor: default;
    }


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
        flex:1; margin:0 12px; color:#333;
    }
    #review-bar textarea {
        flex:1;
        height:80px;
        padding:8px;
        margin-right:12px;
        flex:1; height:80px; padding:8px; margin-right:12px;
        font-family: 'MainFont-Medium'
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

    .solution-box {
      background: white;
      padding: 14px;
      /* border-radius: 6px; */
      box-shadow: 0 0 4px rgba(0,0,0,0.1);
      margin-bottom: 12px;
      min-height: 100px;
    }

    .solution-text {
      font-size: 15px;
      line-height: 1.6;
      color: #333;
      white-space: pre-line;
    }

    #add-review-btn {
      text-align: center;
    }

    #add-review-btn button {
      padding: 8px 16px;
      background: #4cbfee;
      color: #fff;
      border: none;
      font-family: 'MainFont-Medium';
      font-weight : bold;
      cursor: pointer;
    }
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
      <div class="emotions-container">
            <!-- 1) “유용해요” (icon=1) -->
            <div class="emotion-item">
              <?php if (!$is_owner): ?>
                <a href="?id=<?= $post_pkey ?>&icon=1" title="유용해요 누르기">
                  <img src="emotions/useful.png" alt="유용해요" class="emotion-icon">
                </a>
              <?php else: ?>
              <img src="emotions/useful.png" alt="유용해요" class="emotion-icon"> <!-- 본인은 클릭 못함 -->
            <?php endif; ?>
            <div class="emotion-label">유용해요</div>
            <div class="emotion-count"><?= $emotion_counts[1] ?>개</div>
            </div>

            <!-- 2) “웃겨요” (icon=2) -->
            <div class="emotion-item">
              <?php if (!$is_owner): ?>
                <a href="?id=<?= $post_pkey ?>&icon=2" title="웃겨요 누르기">
                  <img src="emotions/smile.png" alt="웃겨요" class="emotion-icon">
                </a>
              <?php else: ?>
              <img src="emotions/smile.png" alt="웃겨요" class="emotion-icon">
              <?php endif; ?>
                <div class="emotion-label">웃겨요</div>
                <div class="emotion-count"><?= $emotion_counts[2] ?>개</div>
            </div>

            <!-- 3) “별로예요” (icon=3) -->
            <div class="emotion-item">
              <?php if (!$is_owner): ?>
                <a href="?id=<?= $post_pkey ?>&icon=3" title="별로예요 누르기">
                  <img src="emotions/dislike.png" alt="별로예요" class="emotion-icon">
                </a>
              <?php else: ?>
              <img src="emotions/dislike.png" alt="별로예요" class="emotion-icon">
              <?php endif; ?>
                <div class="emotion-label">별로예요</div>
                <div class="emotion-count"><?= $emotion_counts[3] ?>개</div>
            </div>

            <!-- 4) “인정해요” (icon=4) -->
            <div class="emotion-item">
              <?php if (!$is_owner): ?>
                <a href="?id=<?= $post_pkey ?>&icon=4" title="인정해요 누르기">
                  <img src="emotions/useful.png" alt="인정해요" class="emotion-icon">
                </a>
              <?php else: ?>
              <img src="emotions/useful.png" alt="인정해요" class="emotion-icon">
              <?php endif; ?>
                <div class="emotion-label">인정해요</div>
                <div class="emotion-count"><?= $emotion_counts[4] ?>개</div>
            </div>

            <!-- 5) “화나요” (icon=5) -->
            <div class="emotion-item">
              <?php if (!$is_owner): ?>
                <a href="?id=<?= $post_pkey ?>&icon=5" title="화나요 누르기">
                  <img src="emotions/mad.png" alt="화나요" class="emotion-icon">
                </a>
              <?php else: ?>
                <img src="emotions/mad.png" alt="화나요" class="emotion-icon">
              <?php endif; ?>
                <div class="emotion-label">화나요</div>
                <div class="emotion-count"><?= $emotion_counts[5] ?>개</div>
            </div>
        <!-- 재판 회부 버튼 -->
        <div class="trial-btn-wrapper">
          <?php if (!$is_owner): ?>
            <?php if (!$already_clicked): ?>
              <a href="?id=<?= $pkey ?>&judgement=1">
                <button class="trial-btn">
                  <span class="label">재판 회부 </span>
                  <span class="count"><?= $trial_count ?></button></span>
              </a>
            <?php else: ?>
              <!-- 이미 회부했으면 클릭 시 JS 경고창만 띄움 -->
              <button class="trial-btn" onclick="alert('재판 회부는 1회만 가능합니다.')" return false;>
                <span class="label">재판 회부 </span>
                <span class="count"><?= $trial_count ?></button></span>
              </button>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      </section>

    <aside id="sidebar">
        <h3>핑계핑의 해답</h3>
        <div class="solution-box">
              <p class="solution-text"><?= htmlspecialchars($post['solution_content'] ?? '해답이 존재하지 않습니다.') ?>
              </p>
            </div>
        <div id="add-review-btn" style="margin-top:12px;">
          <button id="btnAddReview" onclick="openReviewBar()">
            리뷰 추가
          </button>
        </div>
        <!-- 리뷰 리스트(항상 노출) -->
        <!-- <div id="review-list">
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
        </div> -->
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
                <!-- <span class="username"><?=htmlspecialchars($r['username'])?></span> -->
                <span class="date"><?=$r['insert_date']?></span>
              </div>
              <div class="text"><?=nl2br(htmlspecialchars($r['content']))?></div>
            </div>
          </div>
        <!— <?php endforeach;?> —>
      </div>
    </section>
<script>
function openReviewBar() {
    document.getElementById('btnAddReview').disabled = true;
    document.getElementById('review-bar').style.display = 'flex';
}
</script>
</body>
</html>