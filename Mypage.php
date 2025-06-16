<?php
session_start();
if (!isset($_SESSION['id'])) {
    echo "<script>
        alert('로그인이 필요합니다.');
        location.href='Login.php';
    </script>";
    exit;
}
include 'db-config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['id'];
    $newId = $_POST['id'] ?? '';
    $newPwd = $_POST['pwd'] ?? '';

    if ($newId && $newPwd) {
        $stmt = $conn->prepare("UPDATE users SET id = ?, pwd = ? WHERE id = ?");
        $stmt->bind_param("sss", $newId, $newPwd, $userId);

        if ($stmt->execute()) {
            $_SESSION['id'] = $newId;
            echo "<script>alert('정보가 성공적으로 수정되었습니다.'); location.href='Mypage.php';</script>";
        } else {
            echo "<script>alert('정보 수정에 실패했습니다.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('모든 항목을 입력해주세요.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <title>개인정보 수정</title>
    <style>
    html,
    body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        box-sizing: border-box;
        background-color: #f8f8f8;
        font-family: 'MainFont-Bold';
    }

    .container {
        width: 100%;
        max-width: 1200px;
        margin: 20px;
        padding: 10px;
    }

    h1 {
        font-size: 35px;
        margin-bottom: 8px;
        font-family: 'MainFont-Bold';
    }

    p {
        font-size: 16px;
        margin-bottom: 24px;
    }

    label {
        font-weight: bold;
        display: block;
        margin-bottom: 6px;
    }

    input[type="text"],
    input[type="password"] {
        width: 300px;
        padding: 10px;
        margin-bottom: 20px;
        border: none;
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
    }

    #id-field,
    #pwd-field {
        background-color: #fff7b5;
        font-family: 'MainFont-medium';
        margin-top: 15px;
        color: #989898;
    }

    input:-webkit-autofill {
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2), 0 0 0 1000px #fff7b5 inset !important;
        -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2), 0 0 0 1000px #fff7b5 inset !important;
        -webkit-text-fill-color: #989898 !important;
    }


    .edit-btn {
        background-color: #00C3FF;
        font-family: 'MainFont-Bold';
        padding: 10px 20px;
        color: white;
        border: none;
        box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        border-radius: 5px;
        cursor: pointer;
    }

    .form-row {
        display: flex;
        align-items: center;
        gap: 100px;
        margin-top: -8px;
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>개인정보 수정</h1>
        <p>나의 정보를 수정하자핑!</p>

        <form method="POST">
        <label for="id" style="font-family: 'MainFont-Bold';">ID</label>
            <input type="text" id="id-field" name="id" placeholder="아이디 입력"
                value="<?= htmlspecialchars($_SESSION['id']) ?>">

            <label for="pwd" style="font-family: 'MainFont-Bold';">PWD</label>
            <div class="form-row">
                <input type="password" id="pwd-field" name="pwd" placeholder="비밀번호 입력">
            </div>
            <button type="submit" class="edit-btn">수정</button>
        </form>
    </div>
</body>

</html>