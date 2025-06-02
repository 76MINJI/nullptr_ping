<!DOCTYPE html>
<html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title></head>
        <style>
            .login-container {
                text-align: center;
            }

            h3 {
                font-size: 36px;
                margin-bottom: 5px;
            }
        
            p {
                margin-bottom: 25px;
                font-size: 18px;
            }
        
            form {
                display: inline-block;
                text-align: left;
            }
        
            label {
                display: inline-block;
                width: 60px;
                font-size: 20px;
                margin-bottom: 12px;
                font-family: 'MainFont-Bold';
            }
        
            input[type="text"],
            input[type="password"] {
                width: 220px;
                padding: 10px;
                font-size: 18px;
                background-color: #FFF7B0;
                border: none;
                margin-bottom: 20px;
                box-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            }
        
            input[type="submit"] {
                width: 60%;
                background-color: #00C3FF;
                color: white;
                border: none;
                padding: 12px;
                font-size: 25px;
                cursor: pointer;
                box-shadow: 2px 2px 4px rgba(0,0,0,0.2);
                margin-top: 10px;
                font-family: 'MainFont-Bold';
            }
        
            .signup {
                display: block;
                text-align: center;
                margin-top: 12px;
                font-size: 14px;
		font-weight: bold;
                color: #00C3FF;
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <?php include 'navbar.php'; ?>

        <div class="login-container">
            <h3>로그인</h3>
            <p>핑계핑에 오신 걸 환영한다핑!</p>
            <form action="/Request_login.php" method="post">
                <div>
                    <label for="id">ID</label>
                    <input type="text" name="id" required>
                </div>
                <div>
                    <label for="pwd">PWD</label>
                    <input type="password" name="pwd" required>
                </div>
                <input type="submit" value="LOGIN">
                <div>
                    <a class="signup" href="signup.php">회원가입</a>
                </div>
            </form>
        </div>
    </body>
</html>