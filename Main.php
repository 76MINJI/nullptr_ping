<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MAINPING</title>
    <style>
        body {
            margin: 0;
            background: #ffffff;
            font-family: 'MainFont-Medium', sans-serif;
        }

        .main-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 60px 80px;
        }

        .top10-box {
            background: #FFF7B0;
            padding: 20px 30px;
            width: 200px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.15);
        }

        .top10-box h3 {
            font-family: 'MainFont-Bold';
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .top10-box ol {
            list-style: none;
            padding: 0;
            margin: 0;
            line-height: 2.2;
            counter-reset: item;
        }

        .top10-box li::before {
            content: counter(item) ". ";
            font-weight: bold;
            display: inline-block;
            /* min-width: 2em;    */
            /* ✅ 숫자 길이 관계없이 . 정렬 */
            text-align: right;
            margin-right: 0.5em;
            font-feature-settings: "tnum"; /* tabular numbers */
        }

        .top10-box li {
            counter-increment: item;
        }

        .intro-section {
            flex: 1;
            padding-left: 80px;
        }

        .intro-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .intro-subtitle {
            font-size: 18px;
            color: #444;
            margin-bottom: 30px;
        }

        .intro-logo {
            margin-top: 20px;
        }

        .intro-logo img {
            width: 120px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="main-container">
    <!-- 왼쪽 TOP10 영역 -->
    <div class="top10-box">
        <h3>오늘의 핑계 TOP 10</h3>
        <ol>
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <li>핑계 리스트</li>
            <?php endfor; ?>
        </ol>
    </div>

    <!-- 오른쪽 소개 영역 -->
    <div class="intro-section">
        <div class="intro-title">
            그럴듯한 핑계를 매번 만든다고?<br>
            난 핑계핑이 만들어줘!
        </div>
        <div class="intro-subtitle">
            내 화면 속 핑계요정 핑계핑
        </div>
        <div class="intro-logo">
            <img src="img/LOGO_circle.png" alt="핑계핑 로고" />
        </div>
    </div>
</div>

</body>
</html>