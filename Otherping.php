<?php
include 'db-config.php';

// 정렬 기준
$order = $_GET['order'] ?? 'latest';
$order_sql = "";
switch ($order) {
  case 'latest':   
      $order_sql = "ORDER BY be.insert_date DESC"; 
      break;
  case 'review':   
      $order_sql = "ORDER BY s.review_count DESC"; 
      break;

  case 'useful':   
      $order_sql = "ORDER BY (CASE WHEN e.icon = 2 THEN e.icon_count ELSE 0 END) DESC"; 
      break;
  case 'like':     
      $order_sql = "ORDER BY (CASE WHEN e.icon = 1 THEN e.icon_count ELSE 0 END) DESC"; 
      break;
  case 'dislike':  
      $order_sql = "ORDER BY (CASE WHEN e.icon = 3 THEN e.icon_count ELSE 0 END) DESC"; 
      break;
  case 'smile':    
      $order_sql = "ORDER BY (CASE WHEN e.icon = 4 THEN e.icon_count ELSE 0 END) DESC"; 
      break;
  case 'mad':      
      $order_sql = "ORDER BY (CASE WHEN e.icon = 5 THEN e.icon_count ELSE 0 END) DESC"; 
      break;

  default:         
      $order_sql = "ORDER BY be.insert_date DESC"; 
}


$sql = "SELECT
  ep.pkey AS excuse_pkey,
  be.insert_date,
  pi.url AS url,
  st_place.sub_classification  AS tag_place,
  st_person.sub_classification AS tag_person,
  st_time.sub_classification   AS tag_time,
  st_mood.sub_classification   AS tag_mood,
  e.icon,
  e.icon_count
FROM excuse_posts ep
JOIN base_entity be ON ep.base_pkey = be.pkey
LEFT JOIN post_images pi ON ep.image_pkey = pi.pkey
LEFT JOIN solutions s ON ep.sol_pkey = s.pkey
LEFT JOIN sub_tags st_place  ON st_place.pkey  = (s.combo_key >> 18)
LEFT JOIN sub_tags st_person ON st_person.pkey = ((s.combo_key >> 12) & 63)
LEFT JOIN sub_tags st_time   ON st_time.pkey   = ((s.combo_key >> 6) & 63)
LEFT JOIN sub_tags st_mood   ON st_mood.pkey   = (s.combo_key & 63)
LEFT JOIN emotions e ON ep.emotion_pkey = e.pkey
{$order_sql}
LIMIT 20";



$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>OTHER PING</title>
  <style>
    
    body { font-family:GmarketSansTTFBold; background: #f7f7f7; margin: 0; }
    .header {
      background: #00aaff; padding: 15px 20px; color: white;
      display: flex; justify-content: space-between; align-items: center;
    }
    .header .nav a {
      color: white; margin: 0 10px; text-decoration: none; font-weight: bold;
    }
    .sort-wrapper {
  display: flex;
  justify-content: flex-end;
  padding: 20px 24px 0 0;
  position: relative;
  z-index: 999;
}
.header-row {
  display: flex;
  justify-content: space-between;
  font-family: 'MainFont-Bold', sans-serif;
  align-items: center;
  padding: 0 20px;
  margin-top: 30px;
}


.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-button {
  background: #fff8c6;     
  color: #666;
  padding: 10px 18px;
  font-size: 16px;
  font-weight: bold;
  border: none;   
  border-radius: 6px;
  cursor: pointer;
  box-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.dropdown-button .arrow {
  margin-left: 6px;
}

.dropdown-content {
  display: none;
  position: absolute;
  top: 100%;
  right: 0;
  background-color: #fff8c6;
  border-radius: 6px;
  box-shadow: 0px 6px 12px rgba(0,0,0,0.15);
  min-width: 160px;
  padding: 10px 0;
  font-family: 'MainFont-Medium', sans-serif;
}

.dropdown-content a {
  color: #444;
  padding: 10px 20px;
  display: block;
  text-decoration: none;
  font-size: 15px;
}

.dropdown-content a:hover {
  background-color: #f1e6a3;
}

.dropdown:hover .dropdown-content {
  display: block;
}



    .main-title {
      font-size: 22px; font-Bold: bold; padding: 20px;
    }

    .ping-list {
      display: flex; flex-wrap: wrap; gap: 20px; padding: 0 20px 40px 20px;
    }
    .ping-card-link {
  text-decoration: none;     /* 밑줄 제거 */
  color: inherit;            /* 부모의 색상 상속 */
  display: inline-block;     /* 블록처럼 감싸기 */
}
.ping-card-link * {
  color: inherit !important; /* 내부 요소도 링크 색 안 따르게 */
  text-decoration: none !important;
}

    .ping-card {
      width: 220px; background: white; padding: 12px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1); border-radius: 10px;
      display: flex; flex-direction: column; align-items: center;
      font-family: 'MainFont-Bold', sans-serif;
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
  display: grid;
  color: white !important;     
  grid-template-columns: repeat(2, auto); /* 2열 */
  gap: 6px;
}

.meta-tags div {
  background: #888;
  color: white;
  padding: 4px 10px;
  font-size: 13px;
  font-weight: bold;
  border-radius: 4px;
  box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
  text-align: center;
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
<?php include 'navbar.php'; ?>

<!-- <div class="sort-wrapper"> -->
<div class="header-row">
  <div class="main-title">다른 사용자들의 핑계 구경하기</div>
  <div class="dropdown">
    <button class="dropdown-button">정렬 <span class="arrow">▾</span></button>
    <div class="dropdown-content">
      <a href="?order=latest">최신순</a>
      <a href="?order=review">리뷰순</a>
      <a href="?order=reuse">재판 회부 순</a>
      <a href="?order=useful">유용해요 순</a>
      <a href="?order=like">웃겨요 순</a>
      <a href="?order=mad">화나요 순</a>
      <a href="?order=dislike">황당해요 순</a>
      <a href="?order=smile">멋져요 순</a>
    </div>
  </div>
</div>

<div class="ping-list">
<?php
while ($row = mysqli_fetch_assoc($result)) {
    $pkey = (int)$row['excuse_pkey'];  // pkey는 각 excuse_posts의 고유 식별자

    echo "<a href='Otherdetailping.php?pkey={$pkey}' class='ping-card-link'>";
    echo "<div class='ping-card'>";
    
    // 이미지 영역
    echo "<div class='image-wrapper'>";
    if ($row['url']) {
        echo "<img src='" . htmlspecialchars($row['url']) . "' />";
    }
    echo "</div>";


    // 메타태그 + 날짜
    echo "<div class='meta-emoji-row'>";

      echo "<div class='meta-left'>";
      echo "  <div class='meta-tags'>";
      echo "    <div>" . htmlspecialchars($row['tag_place'] ?? '') . "</div>";
      echo "    <div>" . htmlspecialchars($row['tag_time'] ?? '') . "</div>";
      echo "  </div>";
      echo "  <div class='meta-tags'>";
      echo "    <div>" . htmlspecialchars($row['tag_person'] ?? '') . "</div>";
      echo "    <div>" . htmlspecialchars($row['tag_mood'] ?? '') . "</div>";
      echo "  </div>";
      echo "  <div class='date'>" . date('Y.m.d', strtotime($row['insert_date'])) . "</div>";
      echo "</div>";
      
      


      // 오른쪽: 이모지 + 누른 수
$emojiMap = [
  1 => 'like',     // 웃겨요
  2 => 'useful',    // 유용해요
  3 => 'dislike',   // 별로예요
  4 => 'smile',     // 인정이에요
  5 => 'mad'      // 화나요
];

$emojiIndex = isset($row['icon']) ? (int)$row['icon'] : 1;
$emojiType = isset($emojiMap[$emojiIndex]) ? $emojiMap[$emojiIndex] : 'funny';
$emojiCount = isset($row['icon_count']) ? (int)$row['icon_count'] : 0;// 클릭 수

echo "<div class='emoji-wrapper'>
      <div class='emoji-circle'>
        <img src='./emotions/{$emojiType}.png' alt='{$emojiType}' style='width:24px; height:24px;' />
      </div>
      <div class='emoji-count'>{$emojiCount}</div>
    </div>";



    echo "</div>"; // meta-emoji-row

    echo "</div>"; // ping-card
}
?>

</div>

</body>
</html>
