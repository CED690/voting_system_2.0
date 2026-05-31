<?php
// public/login.php
// POST endpoint — handles login form submission

session_start();

require_once __DIR__ . '/../apps/config/dbconnection.php';
require_once __DIR__ . '/../apps/model/User.php';
require_once __DIR__ . '/../apps/controller/UserController.php';

use apps\controller\UserController;

$controller = new UserController();
$controller->login();