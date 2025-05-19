<?php
session_start();  

include "db-config.php";

$id = $_POST['id'];
$pwd = $_POST['pwd'];

$sql = "SELECT * FROM users WHERE id='$id' AND pwd='$pwd'";
$result = mysqli_query($conn, $sql);

$result_login = 0;

while($row = mysqli_fetch_array($result)){
    $result_login = 1;
    $_SESSION['id'] = $id;
}

$link="";

if($result_login)
    $link="Main.php";
else
    $link="Login.php";

echo("<script> location.replace('$link'); </script>");

//echo $result_login;
mysqli_close($conn);
?>