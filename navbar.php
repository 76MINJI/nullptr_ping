<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db-config.php';
?>

<!-- 내비게이션 영역 -->
<nav>
  <div class="nav-logo">
    <img src="img/LOGO_nullptr.png" alt="Logo" />
  </div>
  <a href="Makeping.php">MAKE PING</a>
  <a href="Viewmyping.php">MY PING</a>
  <a href="Otherping.php">OTHER PING</a>
  <a href="Pvp.php">PING vs PING</a>
  <a href="Mypage.php">MYPAGE</a>
</nav>

<!-- 공통 스타일 -->
<style>
  @font-face {
    font-family: 'MainFont-Bold';
    src: url('fonts/GmarketSansTTFBold.ttf') format('truetype');
  }
  @font-face {
    font-family: 'MainFont-Medium';
    src: url('fonts/GmarketSansTTFMedium.ttf') format('truetype');
  }
  body {
    font-family: 'MainFont-Medium', sans-serif;
    margin: 0;
    background: #f5f5f5;
  }
  nav {
    display: flex;
    align-items: center;
    background: #00C3FF;
    padding: 10px;
    font-family: 'MainFont-Bold', sans-serif;
  }
  .nav-logo img {
    height: 32px;
    margin: 0 12px;
    padding-left: 10px;
    padding-right: 10px;
  }
  nav a {
    color: #fff;
    text-decoration: none;
    margin-right: 15px;
    padding-left: 100px;
    padding-right: 10px;
  }
  nav a:last-child {
    margin-left: auto;
    margin-right: 12px;
  }
</style>
