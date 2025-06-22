<?php
session_start();
require_once __DIR__ . '/db-config.php'; 

if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}
$post_pkey = $_GET['id'] ?? 0;
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
    $name = null;
    $stmt = $conn->prepare("SELECT sub_classification FROM sub_tags WHERE pkey = ?");
    $stmt->bind_param("i", $pkey);
    $stmt->execute();
    $stmt->bind_result($name);
    if ($stmt->fetch()) return $name;
    return null;
    }
    // combo_key 분해
list($place_pkey, $person_pkey, $time_pkey, $mood_pkey) = decodeComboKey($combo_key);

// 각 태그 이름 가져오기
$place_name  = getSubTagName($conn, $place_pkey) ?? '장소';
$person_name = getSubTagName($conn, $person_pkey) ?? '사람';
$time_name   = getSubTagName($conn, $time_pkey) ?? '시간';
$mood_name   = getSubTagName($conn, $mood_pkey) ?? '무드';
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>Makeping</title>
    <style>
    @font-face {
        font-family: 'GmarketSansBold';
        src: url('GmarketSansTTFBold.ttf') format('truetype');
    }

    @font-face {
        font-family: 'GmarketSansMedium';
        src: url('GmarketSansTTFMedium.ttf') format('truetype');
    }

    body {
        font-family: 'GmarketSansMedium', sans-serif;
    }

    .form-row {
        display: flex;
        align-items: flex-start;
        margin-bottom: 30px;
    }

    .form-row label {
        font-family: 'Mainfont-Medium';
        font-size: 18px;
        width: 80px;
        margin-top: 8px;
    }

    textarea {
        background-color: #D3F5FF;
        width: 100%;
        height: 180px;
        font-size: 17px;
        border: none;
        resize: none;
        padding: 15px;
        line-height: 1.6;
        font-family: 'Mainfont-Medium';
    }

    .button-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-top: 20px;
        gap: 20px;
    }

    .privacy-buttons {
        display: flex;
    }

    .privacy-buttons button {
        background-color: #FEFEFE;
        color: #00C3FF;
        border: 2px solid #00C3FF;
        font-family: 'Mainfont-Bold';
        font-size: 18px;
        padding: 10px 25px;
        cursor: pointer;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
        border-radius: 0;
        transition: background-color 0.3s, color 0.3s;
    }

    .privacy-buttons button:first-child {
        border-right: none;
    }

    .privacy-button.active {
        background-color: #00C3FF;
        font-family: 'Mainfont-Bold';
        color: white;
    }

    .button-submit {
        background-color: #00C3FF;
        color: white;
        border: none;
        font-family: 'Mainfont-Bold';
        padding: 10px 25px;
        font-size: 18px;
        cursor: pointer;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
    }

    .tag-row {
        display: flex;
        gap: 100px;
        margin-top: 40px;
        flex-wrap: wrap;
        margin-left: 40px;
        margin-right: auto;
        font-size: 18px;
        font-family: 'Mainfont-Medium';
    }

    .dropdown {
        position: relative;
        color: #989898;
    }

    .dropdown-toggle {
        background-color: #FFF7B0;
        border: none;
        color: #989898;
        padding: 12px 18px;
        font-weight: bold;
        font-size: 15px;
        cursor: pointer;
        border-radius: 6px;
        box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
    }

    .dropdown-options {
        display: none;
        position: absolute;
        top: 110%;
        left: 0;
        color: #989898;
        background: #FFF7B0;
        min-width: 140px;
        box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.15);
        z-index: 10;
        border-radius: 6px;
    }

    .dropdown-options div {
        padding: 10px;
        cursor: pointer;
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    

    <div class="tag-row">
        <label class="situation-label">상황</label>
        <div class="dropdown">
        <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)"><?= $place_name ?> ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('place', 1, this)">집</div>
                <div onclick="selectOption('place', 2, this)">회사</div>
                <div onclick="selectOption('place', 3, this)">병원</div>
            </div>
        </div>

        <div class="dropdown">
        <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)"><?= $time_name ?> ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('time', 7, this)">아침</div>
                <div onclick="selectOption('time', 8, this)">저녁</div>
                <div onclick="selectOption('time', 9, this)">주말</div>
            </div>
        </div>

        <div class="dropdown">
        <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)"><?= $person_name ?> ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('person', 4, this)">상사</div>
                <div onclick="selectOption('person', 5, this)">연인</div>
                <div onclick="selectOption('person', 6, this)">친구</div>
            </div>
        </div>

        <div class="dropdown">
        <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)"><?= $mood_name ?> ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('mood', 10, this)">친근하게</div>
                <div onclick="selectOption('mood', 11, this)">정중하게</div>
                <div onclick="selectOption('mood', 12, this)">장난스럽게</div>
                <div onclick="selectOption('mood', 13, this)">급박하게</div>
            </div>
        </div>
    </div>

    <form action="Updateprocess.php" method="POST">
    <input type="hidden" name="post_pkey" value="<?= htmlspecialchars($post_pkey) ?>">
        <input type="hidden" name="place" id="place" value="<?= $place_pkey ?>" />
        <input type="hidden" name="time" id="time" value="<?= $time_pkey ?>" />
        <input type="hidden" name="person" id="person" value="<?= $person_pkey ?>" />
        <input type="hidden" name="mood" id="mood" value="<?= $mood_pkey ?>" />
        <input type="hidden" name="status" id="status" value="1" />
        <div style="padding: 40px;">
            <div class="form-row">
                <label for="content">설명</label><br>
                <textarea name="content" id="content" rows="10"
                    cols="80"><?= htmlspecialchars($description) ?></textarea>
            </div>
        </div>

    <div class="button-row">
        <div class="privacy-buttons">
            <button type="button" class="privacy-button active" onclick="selectPrivacy(this,1)">공개</button>
            <button type="button" class="privacy-button" onclick="selectPrivacy(this,0)">비공개</button>
        </div>
        
        <input type="submit" class="button-submit" value="글 수정">
    </div>
    </form>

    <script>
    const fieldMap = {
        place: '장소',
        person: '사람',
        time: '시간',
        mood: '무드'
    };

    function toggleDropdown(button) {
        const options = button.nextElementSibling;
        const isVisible = options.style.display === "block";
        document.querySelectorAll(".dropdown-options").forEach(el => el.style.display = "none");
        options.style.display = isVisible ? "none" : "block";
    }

    function selectOption(field, value, div) {
        document.getElementById(field).value = value;
        const button = div.closest(".dropdown").querySelector(".dropdown-toggle");
        button.innerText = div.innerText + " ⌄";
        div.parentElement.style.display = "none";
    }

    function selectPrivacy(clicked, value) {
        document.querySelectorAll('.privacy-button').forEach(btn => btn.classList.remove('active'));
        clicked.classList.add('active');
        document.getElementById('status').value = value;
    }
    </script>
</body>

</html>