<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db-config.php';

// 로그인 검사
if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}

//  로그인된 사용자 기본 ID
$user_id = intval($_SESSION['user_pkey']);


// 사용자의 핑계 불러오기 쿼리
$sql = "SELECT
    ep.pkey AS excuse_pkey,
    be.insert_date,
    ep.rating,
    st_place.sub_classification  AS tag_place,
    st_person.sub_classification AS tag_person,
    st_time.sub_classification   AS tag_time,
    st_mood.sub_classification   AS tag_mood
FROM excuse_posts ep
JOIN base_entity be ON ep.base_pkey = be.pkey
JOIN solutions s ON ep.sol_pkey = s.pkey
LEFT JOIN sub_tags st_place  ON st_place.pkey  = (s.combo_key >> 18)
LEFT JOIN sub_tags st_person ON st_person.pkey = ((s.combo_key >> 12) & 63)
LEFT JOIN sub_tags st_time   ON st_time.pkey   = ((s.combo_key >> 6) & 63)
LEFT JOIN sub_tags st_mood   ON st_mood.pkey   = (s.combo_key & 63)
WHERE ep.user_pkey = $user_id
ORDER BY be.insert_date DESC
LIMIT 20";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("쿼리 실패: " . mysqli_error($conn));
}

// 날짜별
$pings_by_date = [];
while ($row = mysqli_fetch_assoc($result)) {
    $date = date('Y-m-d', strtotime($row['insert_date']));
    $pings_by_date[$date][] = $row;
}
?>

<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>나의 핑계 보기</title>
    <style>
    body {
        margin: 0;
        font-family: "MainFont-Medium", sans-serif;
        background-color: #D3F5FF;
    }

    .container {
        display: flex;
        justify-content: center;
        gap: 20px;
        padding: 40px;
    }

    .calendar {
        background-color: #D3F5FF;
        padding: 30px;
        border-radius: 10px;
        width: 620px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .calendar h2 {
        font-size: 28px;
        margin-bottom: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th:first-child {
        color: #ff8991;
    }

    th,
    td {
        width: 14.2%;
        border: 1px solid #ccc;
        text-align: left;
        vertical-align: top;
        font-size: 16px;
        background-color: #fefefe;
        position: relative;
        padding: 3px;
    }

    td {
        height: 80px;
    }

    .main-title {
        font-size: 40px;
        font-family: 'MainFont-Bold';
        margin-left: 60px;
        margin-top: 40px;
        margin-bottom: 1px;
        color: #1D1D1D;
    }


    .cell-date {
        position: absolute;
        top: 6px;
        left: 6px;
        font-size: 14px;
        font-weight: bold;
    }

    .cell-stars {
        position: absolute;
        bottom: 6px;
        left: 6px;
        font-size: 14px;
        font-weight: bold;
        color: #00bfff;
        line-height: 1.2;
    }

    .sidebar {
        width: 300px;
        background-color: #fff9ae;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 2px 3px 8px rgba(0, 0, 0, 0.08);
    }

    .sidebar h3 {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 20px;
    }

    a.ping-item {
        text-decoration: none;
        color: inherit;
        background-color: transparent;
        border-radius: 6px;
        padding: 6px 8px;
        margin-bottom: 8px;
        font-size: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    a.ping-item:hover {
        background-color: #f0f0f0;
    }

    .ping-item:last-child {
        font-weight: bold;
    }

    .ping-item .date {
        color: #888;
        font-weight: bold;
        margin-right: 8px;
        width: 65px;
    }

    .ping-item .desc {
        flex-grow: 1;
        font-weight: bold;
    }

    .ping-item .star {
        color: #00bfff;
        font-weight: bold;
        margin-left: 6px;
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="main-title">나의 핑계 보기</div>
    <div class="container">
        <div class="calendar">
            <h2><?= date('F Y') ?></h2>
            <table>
                <tr>
                    <th>일요일</th>
                    <th>월요일</th>
                    <th>화요일</th>
                    <th>수요일</th>
                    <th>목요일</th>
                    <th>금요일</th>
                    <th>토요일</th>
                </tr>
                <?php
                    $today = new DateTime('first day of this month');
                    $start_day = $today->format('w');
                    $days_in_month = $today->format('t');
                    $calendar = array_fill(0, $start_day, '');

                    for ($i = 1; $i <= $days_in_month; $i++) {
                        $calendar[] = $i;
                    }

                    $rows = array_chunk($calendar, 7);

                    foreach ($rows as $week) {
                        echo "<tr>";
                        foreach ($week as $day) {
                            $date_key = $day ? $today->format("Y-m") . '-' . str_pad($day, 2, '0', STR_PAD_LEFT) : '';
                            echo "<td>";
                            if ($day) {
                                echo "<div class='cell-date'>{$day}</div>";
                                if (isset($pings_by_date[$date_key])) {
                                    echo "<div class='cell-stars'>";
                                    foreach ($pings_by_date[$date_key] as $ping) {
                                        echo "★ " . intval($ping['rating']) . "<br>";
                                    }  
                                    echo "</div>";
                                }
                            }
                        echo "</td>";
                        }
                        echo "</tr>";
                    }
                ?>
            </table>
        </div>

        <div class="sidebar">
            <h3>나의 핑계 목록</h3>
            <?php foreach ($pings_by_date as $date => $ping_list): ?>
            <?php foreach ($ping_list as $ping): ?>
            <a class="ping-item" href="Viewmydetailping.php?id=<?= $ping['excuse_pkey'] ?>">
                <span class="date"><?= date('y/n/j', strtotime($date)) ?></span>
                <span
                    class="desc"><?= htmlspecialchars($ping['tag_place']) ?>-<?= htmlspecialchars($ping['tag_person']) ?>-<?= htmlspecialchars($ping['tag_time']) ?>-<?= htmlspecialchars($ping['tag_mood']) ?></span>
                <span class="star">★ <?= intval($ping['rating']) ?></span>
            </a>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>