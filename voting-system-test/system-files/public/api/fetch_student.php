<?php
// public/api/fetch_student.php
// AJAX endpoint: GET ?loginId=XXXXX

require_once __DIR__ . '/../../apps/config/dbconnection.php';
require_once __DIR__ . '/../../apps/model/User.php';
require_once __DIR__ . '/../../apps/controller/UserController.php';

use apps\controller\UserController;

$controller = new UserController();
$controller->fetchStudent();