<?php

namespace apps\controller;

use apps\model\User;

class UserController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function fetchStudent(): void {
        $schoolId = trim($_GET['schoolId'] ?? '');

        if ($schoolId === '') {
            $this->sendJson(400, false, 'Student ID is required.');
        }

        $student = $this->userModel->findStudentBySchoolId($schoolId);

        if (!$student) {
            $this->sendJson(404, false, 'Student ID not found. Please check and try again.');
        }

        if ($this->userModel->isAlreadyRegistered($schoolId)) {
            $this->sendJson(409, false, 'This Student ID is already registered. Please log in instead.');
        }

        $name  = $student['firstname'];
        $name .= !empty($student['mi'])     ? ' ' . $student['mi'] . '.' : '';
        $name .= ' ' . $student['lastname'];
        $name .= !empty($student['suffix']) ? ' ' . $student['suffix']   : '';

        $this->sendJson(200, true, '', [
            'name'       => $name,
            'program'    => $student['program'],
            'department' => $student['department'],
        ]);
    }

    public function register(): void {
        $schoolId = trim($_POST['stud_id']        ?? '');
        $email    = trim($_POST['email_address']  ?? '');
        $password =      $_POST['password']       ?? '';
        $confirm  =      $_POST['confirm_password'] ?? '';

        $errors = [];
        if ($schoolId === '')                              $errors[] = 'Student ID is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 8)                         $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)                        $errors[] = 'Passwords do not match.';

        if (!empty($errors)) {
            $this->sendJson(422, false, implode(' ', $errors));
        }

        $student = $this->userModel->findStudentBySchoolId($schoolId);

        if (!$student) {
            $this->sendJson(404, false, 'Student ID not found in the system.');
        }
        if ($this->userModel->isAlreadyRegistered($schoolId)) {
            $this->sendJson(409, false, 'This Student ID is already registered. Please log in.');
        }
        if ($this->userModel->emailExists($email)) {
            $this->sendJson(409, false, 'That email address is already in use.');
        }

        $ok = $this->userModel->register([
            'loginId'   => $student['schoolID'],
            'firstname' => $student['firstname'],
            'mi'        => $student['mi'],
            'lastname'  => $student['lastname'],
            'suffix'    => $student['suffix'],
            'email'     => $email,
            'password'  => $password,
        ]);

        if ($ok) {
            $this->sendJson(200, true, 'Registration successful! You can now log in.');
        } else {
            $this->sendJson(500, false, 'Registration failed due to a database error. Please try again.');
        }
    }

    public function login(): void {
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
               || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        $credential = trim($_POST['stud_id_email'] ?? '');
        $password   =      $_POST['password']      ?? '';

        if ($credential === '' || $password === '') {
            $msg = 'Please enter your Student ID / Email and password.';
            if ($isAjax) $this->sendJson(400, false, $msg);
            $this->redirectWithError($msg);
        }

        $user = $this->userModel->findByCredential($credential);

        if (!$user || !password_verify($password, $user['password'])) {
            $msg = 'Invalid Student ID / Email or password.';
            if ($isAjax) $this->sendJson(401, false, $msg);
            $this->redirectWithError($msg);
        }

        // ── Start session ────────────────────────────────────────────────────
        if (session_status() === PHP_SESSION_NONE) session_start();

        session_regenerate_id(true); 

        if ($user['roles'] === 'candidate') {
            $this->userModel->ensureStudentRole((int) $user['id']);
            $user['roles'] = 'student';
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['loginID']    = $user['loginID'];
        $_SESSION['firstname']  = $user['firstname'];
        $_SESSION['lastname']   = $user['lastname'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['roles'];
        $_SESSION['is_candidate'] = $this->userModel->hasCandidateProfile((int) $user['id']);

        $this->userModel->updateLastLogin($user['id']);

        // ── Redirect by role ─────────────────────────────────────────────────
        if ($isAjax) {
            // JS executes relative to: /apps/view/login-signup.php
            $redirectMap = [
                'student' => 'student/browse.php',
                'admin'   => 'admin/dashboard.html',
            ];
            $destination = $redirectMap[$user['roles']] ?? 'student/browse.php';
            $this->sendJson(200, true, 'Success', [], $destination);
            
        } else {
            // PHP executes relative to: /public/login.php
            $redirectMap = [
                'student' => '../apps/view/student/browse.php',
                'admin'   => '../apps/view/admin/dashboard.html',
            ];
            $destination = $redirectMap[$user['roles']] ?? '../apps/view/student/browse.php';
            header('Location: ' . $destination);
            exit;
        }
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function sendJson(int $httpCode, bool $success, string $message, array $data = [], string $redirect = null): never {
        if (ob_get_length()) {
            ob_clean();
        }
        
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = ['success' => $success];
        if ($message)  $response['message']  = $message;
        if ($data)     $response['data']     = $data;
        if ($redirect) $response['redirect'] = $redirect;

        echo json_encode($response);
        exit;
    }

    private function redirectWithError(string $message): never {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['login_error'] = $message;

        //test comment
        //test comment2
        //test comment3
        
        // Fix for PHP fallback error routing (relative to public/login.php)
        header('Location: ../apps/view/login-signup.php#login');
        exit;
    }
}