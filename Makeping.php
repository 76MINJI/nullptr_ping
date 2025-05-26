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
            padding: 40px;
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
    </style>
</head>
<body>
    <form action="SubmitPost.php" method="POST">
        <div class="form-row">
            <label for="content">설명</label>
            <textarea name="content" id="content" placeholder="핑계가 필요한 상황을 자세히 서술해 주세요.
ex) 회식에 너무 가기 싫어요.. 이럴 땐 어떻게 하나요? 도와줘요 핑계핑" required></textarea>
        </div>

        <div class="button-row">
            <div class="privacy-buttons">
                <button type="button" class="privacy-button active" onclick="selectPrivacy(this)">공개</button>
                <button type="button" class="privacy-button" onclick="selectPrivacy(this)">비공개</button>
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
    </script>
</body>
</html>
