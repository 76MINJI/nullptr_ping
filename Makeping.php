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
        body { font-family: sans-serif; padding: 20px; }
        textarea { width: 100%; height: 150px; margin-bottom: 20px; font-size: 16px; }
        input[type="submit"] {
            background-color:#00C3FF; color: white;
            padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;
        }
    </style>
</head>
<body>
    <form action="SubmitPost.php" method="POST">
        <label for="content"><strong>설명</strong></label><br>
        <textarea name="content" id="content" placeholder="핑계가 필요한 상황을 자세히 서술해 주세요.
ex) 회식에 너무 가기 싫어요.. 이럴 땐 어떻게 하나요? 도와줘요 핑계핑" required></textarea><br>

        <input type="submit" value="글 등록">
    </form>

</body>
</html>
