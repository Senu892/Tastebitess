<?php
session_start();
session_destroy();
// Clear any remember me data
setcookie('rememberMe', '', time() - 3600);
// Redirect to login page
header('Location: login.php');
exit();
?>