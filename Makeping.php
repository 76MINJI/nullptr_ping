<?php
//session_start();
//
//// 로그인 안 했을 경우 로그인 페이지로 이동
//if (!isset($_SESSION['id'])) {
//    header("Location: Login.php");
//    exit();
//}

session_start();
if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}
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
        font-family: 'GmarketSansBold';
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
        font-family: 'GmarketSansMedium';
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
        font-family: 'GmarketSansBold';
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
        color: white;
    }

    .button-submit {
        background-color: #00C3FF;
        color: white;
        border: none;
        font-family: 'GmarketSansBold';
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
            <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)">장소 ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('place', 1, this)">집</div>
                <div onclick="selectOption('place', 2, this)">회사</div>
                <div onclick="selectOption('place', 3, this)">병원</div>
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)">시간 ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('time', 7, this)">아침</div>
                <div onclick="selectOption('time', 8, this)">저녁</div>
                <div onclick="selectOption('time', 9, this)">주말</div>
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)">사람 ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('person', 4, this)">상사</div>
                <div onclick="selectOption('person', 5, this)">연인</div>
                <div onclick="selectOption('person', 6, this)">친구</div>
            </div>
        </div>

        <div class="dropdown">
            <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)">무드 ⌄</button>
            <div class="dropdown-options">
                <div onclick="selectOption('mood', 10, this)">친근하게</div>
                <div onclick="selectOption('mood', 11, this)">정중하게</div>
                <div onclick="selectOption('mood', 12, this)">장난스럽게</div>
                <div onclick="selectOption('mood', 13, this)">급박하게</div>
            </div>
        </div>
    </div>

    <form action="SubmitPost.php" method="POST">
        <input type="hidden" name="place" id="place" required />
        <input type="hidden" name="time" id="time" required />
        <input type="hidden" name="person" id="person" required />
        <input type="hidden" name="mood" id="mood" required />
        <input type="hidden" name="status" id="status" value="1" />

        <div style="padding: 40px;">
            <!-- <form action="SubmitPost.php" method="POST"> -->
                <div class="form-row">
                    <label for="content">설명</label>
                    <textarea name="content" id="content" placeholder="핑계가 필요한 상황을 자세히 서술해 주세요.
ex) 회식에 너무 가기 싫어요.. 이럴 땐 어떻게 하나요? 도와줘요 핑계핑" required></textarea>
                </div>
        </div>

        <div class="button-row">
            <div class="privacy-buttons">
                <button type="button" class="privacy-button active" onclick="selectPrivacy(this,1)">공개</button>
                <button type="button" class="privacy-button" onclick="selectPrivacy(this,0)">비공개</button>
            </div>
            <input type="submit" class="button-submit" value="글 등록">
        </div>
    </form>

    <script>
    function selectPrivacy(clicked) {
        document.querySelectorAll('.privacy-button').forEach(btn => {
            btn.classList.remove('active');
        });
        clicked.classList.add('active');
    }

    function selectTag(field, value, btn) {
        document.getElementById(field).value = value;
        document.querySelectorAll('.tag-column').forEach(col => {
            if (col.querySelector('.tag-title').innerText === fieldMap[field]) {
                col.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('selected'));
            }
        });
        btn.classList.add('selected');
    }
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