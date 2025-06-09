<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db-config.php';

$user_pkey = $_SESSION['user_pkey'] ?? 0;

if (!isset($_SESSION['id'])) {
  echo "<script>
      alert('로그인이 필요합니다.');
      location.href='Login.php';
  </script>";
  exit;
}

// 정렬 기준
$order = $_GET['order'] ?? 'latest';
$order_sql = "";
switch ($order) {
  case 'latest':   
      $order_sql = "ORDER BY be.insert_date DESC"; 
      break;
  case 'review':
        $order_sql = "ORDER BY (
            SELECT COUNT(*)
            FROM reviews r
            WHERE r.post_pkey = ep.pkey
        ) DESC";
        break;
    
  case "judgement":
    $order_sql = "ORDER BY (
      SELECT SUM(ji.count)
      FROM judgement_icon ji
      WHERE ji.excuse_pkey = ep.pkey
      ) DESC";
      break;
  case 'useful':
    $order_sql = "ORDER BY (
        SELECT COUNT(*)
        FROM useful_icon ui
        WHERE ui.excuse_pkey = ep.pkey
    ) DESC";
    break;

case 'funny':
    $order_sql = "ORDER BY (
        SELECT COUNT(*)
        FROM funny_icon li
        WHERE li.excuse_pkey = ep.pkey
    ) DESC";
    break;

case 'bad':
    $order_sql = "ORDER BY (
        SELECT COUNT(*)
        FROM bad_icon di
        WHERE di.excuse_pkey = ep.pkey
    ) DESC";
    break;

case 'agree':
    $order_sql = "ORDER BY (
        SELECT COUNT(*)
        FROM agree_icon si
        WHERE si.excuse_pkey = ep.pkey
    ) DESC";
    break;

case 'angry':
    $order_sql = "ORDER BY (
        SELECT COUNT(*)
        FROM angry_icon mi
        WHERE mi.excuse_pkey = ep.pkey
    ) DESC";
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
st_mood.sub_classification   AS tag_mood
FROM excuse_posts ep
JOIN base_entity be ON ep.base_pkey = be.pkey
LEFT JOIN post_images pi ON ep.image_pkey = pi.pkey
LEFT JOIN solutions s ON ep.sol_pkey = s.pkey
LEFT JOIN sub_tags st_place  ON st_place.pkey  = (s.combo_key >> 18)
LEFT JOIN sub_tags st_person ON st_person.pkey = ((s.combo_key >> 12) & 63)
LEFT JOIN sub_tags st_time   ON st_time.pkey   = ((s.combo_key >> 6) & 63)
LEFT JOIN sub_tags st_mood   ON st_mood.pkey   = (s.combo_key & 63)
WHERE ep.status = 1 AND ep.user_pkey != ?
{$order_sql}
LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_pkey);
$stmt->execute();
$result = $stmt->get_result();

// $result = mysqli_query($conn, $sql);
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
  background: #FFF7B0;     
  color: #989898;
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
  color: #989898;
}

.dropdown-content {
  display: none;
  position: absolute;
  top: 100%;
  right: 0;
  background-color: #FFF7B0;
  border-radius: 6px;
  box-shadow: 0px 6px 12px rgba(0,0,0,0.15);
  min-width: 160px;
  padding: 10px 0;
  font-family: 'MainFont-Medium', sans-serif;
}

.dropdown-content a {
  color: #989898;
  padding: 10px 20px;
  display: block;
  text-decoration: none;
  font-size: 15px;
}

.dropdown-content a:hover {
  background-color: #FF8991;
}

.dropdown:hover .dropdown-content {
  display: block;
}


    .main-title {
      font-size: 35px; font-Bold: bold; padding: 20px; color: #1D1D1D;
    }

    .ping-list {
      display: flex; flex-wrap: wrap; gap: 20px; padding: 0 20px 40px 20px;
    }
    .ping-card-link {
  text-decoration: none;     
  color: inherit;            
  display: inline-block;     
}
.ping-card-link * {
  color: inherit !important; 
  text-decoration: none !important;
}

    .ping-card {
      width: 220px; background: #FEFEFE; padding: 12px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1); border-radius: 10px;
      display: flex; flex-direction: column; align-items: center;
      font-family: 'MainFont-Bold', sans-serif;
    }
    .image-wrapper {
      width: 100%;
      height: 160px;
      background-color: #FEFEFE;
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
  color: #FEFEFE !important;     
  grid-template-columns: repeat(2, auto); 
  gap: 6px;
}

.meta-tags div {
  background: #989898;
  color: #FEFEFE;
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


  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="header-row">
  <div class="main-title">다른 사용자들의 핑계 구경하기</div>
  <div class="dropdown">
    <button class="dropdown-button">정렬 <span class="arrow">▾</span></button>
    <div class="dropdown-content">
      <a href="?order=latest">최신순</a>
      <a href="?order=review">리뷰순</a>
      <a href="?order=judgement">재판 회부 순</a>
      <a href="?order=useful">유용해요 순</a>
      <a href="?order=funny">웃겨요 순</a>
      <a href="?order=bad">별로예요 순</a>
      <a href="?order=agree">인정이에요 순</a>
      <a href="?order=angry">화나요 순</a>
    </div>
  </div>
</div>

<div class="ping-list">
<?php
while ($row = mysqli_fetch_assoc($result)) {
    $pkey = (int)$row['excuse_pkey']; 

    echo "<a href='Otherdetailping.php?id={$pkey}' class='ping-card-link'>";
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
    echo "</div>"; // ping-card row 
    echo "</div>"; // ping-card
}
?>

</div>

</body>
</html>