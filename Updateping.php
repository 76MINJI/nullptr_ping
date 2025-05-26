<?php
session_start();
require_once __DIR__ . '/db-config.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title>Update Ping</title>
<style>
  /* 1) 폰트 파일 별로 @font-face 선언 */
  @font-face {
    font-family: 'MainFont-Bold';
    src: url('fonts/GmarketSansTTFBold.ttf') format('truetype');
    font-weight: 700;
    font-style: normal;
  }
  @font-face {
    font-family: 'MainFont-Medium';
    src: url('fonts/GmarketSansTTFMedium.ttf') format('truetype');
    font-weight: 500;
    font-style: normal;
  }
  @font-face {
    font-family: 'MainFont-Light';
    src: url('fonts/GmarketSansTTFLight.ttf') format('truetype');
    font-weight: 300;
    font-style: normal;
  }

  /* 2) 사용 */
  body {
    margin: 0;
    background: #f5f5f5;
    /* 기본 텍스트는 Medium */
    font-family: 'MainFont-Medium', sans-serif;
  }
   nav {
    display: flex;
    align-items: center;
    background: #00C3FF;
    padding: 10px;
    font-family: 'MainFont-Bold', sans-serif;
  }
  .nav-logo {
    display: inline-block;
    vertical-align: middle;
    margin-left: 12px;
    margin-right: 12px;
  }
  .nav-logo img {
    height: 32px;
    width: auto;
  }
  nav a {
    color: #f5f5f5;
    text-decoration: none;
    font-weight: normal;
    margin-right: 15px;
  }
  /* 마지막 링크에만 자동 마진 */
  nav a:last-child {
    margin-left: auto;
    margin-right: 12px;
  }
</style>

<script>
</script>
</head>

<body>
<nav>
    <div class="nav-logo">
    <img src="img\LOGO_nullptr.png" alt="Logo" />
  </div>
    <a href=></a>
    <a href="Makeping.php">MAKE PING</a>
    <a href="Myping.php">MY PING</a>
    <a href="Otherping.php">OTHER PING</a>
    <a href="Pvp.php">PING vs PING</a>
    <a href="Mypage.php">MYPAGE</a>
</nav>
</body>
</html>