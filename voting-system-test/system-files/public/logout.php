<?php
session_start();
session_destroy();
header('Location: ../apps/view/login-signup.php#login');
exit;