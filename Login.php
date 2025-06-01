<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    body {
        font-family: 'GmarketSansMedium', sans-serif;
        background: #f5f5f5;
        margin: 0;
        padding: 0;
    }
    .login-box {
    display: flex;
    margin-top: 0.5px;
    flex-direction: column;  
    align-items: center;
}
    .login-container {
        max-width: 500px;
        margin: 120px auto;
        text-align: center;
        background: white;
        padding: 40px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }
    .login-wrapper {
    margin-top: 10px;
    display: flex;
    justify-content: center;
}


    h3 {
        font-family: 'MainFont-Bold';
        font-size: 40px;
        margin-bottom: 10px;
    }

    p {
        font-family: 'MainFont-Medium';
        font-size: 16px;
        margin-bottom: 20px;
    }

    .form-group {
        margin: 20px 0;
        text-align: left;
    }

    label {
        font-family: 'GmarketSansBold';
        font-size: 17px;
        display: block;
        margin-bottom: 5px;
    }

    input[type="text"],
    input[type="password"] {
        width: 50%;
        padding: 12px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background-color: #FFF9C4;
        box-shadow: inset 3px 4px 2px rgba(0, 0, 0, 0.2);
    }



    .login-btn {
        background-color: #00C3FF;
        color: white;
        font-family: 'GmarketSansBold';
        font-size: 20px;
        padding: 12px 40px;
        border: none;
        border-radius: 6px;
        margin-top: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        cursor: pointer;
    }

    .signup-link {
        display: block;
        margin-top: 16px;
        font-size: 14px;
        font-weight: bold;
        color: #00C3FF;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="login-container">
        <h3>로그인</h3>
        <p>핑계핑에 오신 걸 환영한다핑!</p>

    <form action="/Request_login.php" method="post" style="width: 100%; max-width: 500px; margin: 0 auto;">
    <div class="login-wrapper">
    <div class="login-box">
    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <label for="id" style="width: 60px; font-weight: bold;">ID</label>
        <input type="text" id="id" name="id">
    </div>

    <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <label for="pwd" style="width: 60px; font-weight: bold;">PWD</label>
        <input type="password" id="pwd" name="pwd">
    </div>
    </div>
    </div>

    <input type="submit" value="LOGIN" class="login-btn">
</form>

        <a class="signup-link" href="Join.php">회원가입</a>
    </div>
</body>
</html>