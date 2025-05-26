<?php
include 'db-config.php';

// 정렬 기준
$order = $_GET['order'] ?? 'latest';
$order_sql = "";
switch ($order) {
    case 'latest':   $order_sql = "ORDER BY be.insert_date DESC"; break;
    case 'review':   $order_sql = "ORDER BY s.review_count DESC"; break;
    case 'reuse':    $order_sql = "ORDER BY s.reuse_count DESC"; break;
    case 'useful':   $order_sql = "ORDER BY s.useful DESC"; break;
    case 'funny':    $order_sql = "ORDER BY s.funny DESC"; break;
    case 'angry':    $order_sql = "ORDER BY s.angry DESC"; break;
    case 'shocking': $order_sql = "ORDER BY s.shocking DESC"; break;
    case 'cool':     $order_sql = "ORDER BY s.cool DESC"; break;
    default:         $order_sql = "ORDER BY be.insert_date DESC";
}

$sql = "SELECT
ep.pkey AS excuse_pkey,
be.insert_date,
pi.url AS url,

-- sub_pkey 비트마스킹 해석
st_place.sub_classification AS tag_place,
st_person.sub_classification AS tag_person,
st_time.sub_classification AS tag_time,
st_mood.sub_classification AS tag_mood,

-- 가장 많이 클릭된 이모지
e.icon AS emoji_type,         -- 이모지 번호: 1~5
e.icon_count AS emoji_count   -- 클릭 수
FROM excuse_posts ep
JOIN base_entity be ON ep.base_pkey = be.pkey
LEFT JOIN post_images pi ON ep.image_pkey = pi.pkey
LEFT JOIN sub_tags st_place ON st_place.pkey = (ep.sub_pkey >> 18)
LEFT JOIN sub_tags st_person ON st_person.pkey = ((ep.sub_pkey >> 12) & 63)
LEFT JOIN sub_tags st_time ON st_time.pkey = ((ep.sub_pkey >> 6) & 63)
LEFT JOIN sub_tags st_mood ON st_mood.pkey = (ep.sub_pkey & 63)

-- 가장 많이 눌린 이모지 1개만 가져오기
LEFT JOIN emotions e ON e.base_pkey = ep.base_pkey
                   AND e.icon_count = (
                       SELECT MAX(e2.icon_count)
                       FROM emotions e2
                       WHERE e2.base_pkey = ep.base_pkey
                   )
$order_sql
LIMIT 20
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>OTHER PING</title>
  <style>
    body { font-family: sans-serif; background: #f7f7f7; margin: 0; }
    .header {
      background: #00aaff; padding: 15px 20px; color: white;
      display: flex; justify-content: space-between; align-items: center;
    }
    .header .nav a {
      color: white; margin: 0 10px; text-decoration: none; font-weight: bold;
    }
    .dropdown {
      position: relative;
    }
    .dropdown button {
      background: #ff7b7b; color: white; padding: 8px 12px; border: none;
      cursor: pointer; font-weight: bold; border-radius: 4px;
    }
    .dropdown-content {
      display: none; position: absolute; right: 0; background-color: #f9f9f9;
      min-width: 160px; z-index: 1; box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
    }
    .dropdown-content a {
      color: black; padding: 12px 16px; text-decoration: none; display: block;
    }
    .dropdown:hover .dropdown-content { display: block; }

    .main-title {
      font-size: 22px; font-weight: bold; padding: 20px;
    }

    .ping-list {
      display: flex; flex-wrap: wrap; gap: 20px; padding: 0 20px 40px 20px;
    }

    .ping-card {
      width: 220px; background: white; padding: 12px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1); border-radius: 10px;
      display: flex; flex-direction: column; align-items: center;
    }

    .image-wrapper {
      width: 100%;
      height: 160px;
      background-color: #d3f5ff;
      border-radius: 6px;
      overflow: hidden;
    }

    .image-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 6px;
    }

    .meta-emoji-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      width: 100%;
      margin-top: 10px;
    }

    .meta-left {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .meta-tags {
      display: flex; flex-wrap: wrap; gap: 4px;
    }

    .meta-tags div {
      background: #888;
      color: white;
      padding: 2px 6px;
      font-size: 11px;
      border-radius: 3px;
    }

    .date {
      font-size: 14px;
      font-weight: bold;
    }

    .emoji-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
    }

    .emoji-circle {
      width: 40px;
      height: 40px;
      background: #fff799;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 1px 1px 3px rgba(0,0,0,0.1);
      cursor: pointer;
    }

    .emoji-circle img {
      width: 22px;
      height: 22px;
    }

    .emoji-count {
      font-size: 13px;
      color: gray;
      font-weight: bold;
    }
  </style>
</head>
<body>



<div class="header">
  <div class="nav">
    <a href="#">MAKE PING</a>
    <a href="#">MY PING</a>
    <a href="#">OTHER PING</a>
    <a href="#">PING vs PING</a>
    <a href="#">MYPAGE</a>
  </div>
  <div class="dropdown">
    <button>정렬 ▼</button>
    <div class="dropdown-content">
      <a href="?order=latest">최신순</a>
      <a href="?order=review">리뷰순</a>
      <a href="?order=reuse">재판 회부 순</a>
      <a href="?order=useful">유용해요 순</a>
      <a href="?order=funny">웃겨요 순</a>
      <a href="?order=angry">화나요 순</a>
      <a href="?order=shocking">황당해요 순</a>
      <a href="?order=cool">멋져요 순</a>
    </div>
  </div>
</div>

<div class="main-title">다른 사용자들의 핑계 구경하기</div>

<div class="ping-list">
<?php
while ($row = mysqli_fetch_assoc($result)) {
    echo "<div class='ping-card'>";
    
    // 이미지 영역
    echo "<div class='image-wrapper'>";
    if ($row['url']) {
        echo "<img src='" . htmlspecialchars($row['url']) . "' />";
    }
    echo "</div>";

    // 메타태그 + 날짜 / 이모지 + 카운트 2열
    echo "<div class='meta-emoji-row'>";

      // 왼쪽: 태그 + 날짜

      echo "<div class='meta-left'>
        <div class='meta-tags'>";

        // 동적으로 sub 태그 출력
        for ($i = 1; $i <= 4; $i++) {
            $tag = htmlspecialchars($row["sub_tag$i"] ?? '');
            if (!empty($tag)) {
                echo "<div>$tag</div>";
            }
        }

        echo   "</div>
                <div class='date'>" . date('Y.m.d', strtotime($row['insert_date'])) . "</div>
              </div>";


      // 오른쪽: 이모지 + 누른 수
      $emojiType = htmlspecialchars($row['emoji_type'] ?? 'dislike'); // 기본값 funny
$emojiCount = (int)($row['emoji_count'] ?? 0); // DB에서 누른 수
echo "<div class='emoji-wrapper'>
        <div class='emoji-circle'>
          <img src='./emotions/$emojiType.png' alt='$emojiType' />
        </div>
        <div class='emoji-count'>$emojiCount</div>
      </div>";


    echo "</div>"; // meta-emoji-row

    echo "</div>"; // ping-card
}
?>

</div>

</body>
</html>