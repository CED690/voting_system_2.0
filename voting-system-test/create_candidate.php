<?php
/**
 * create_candidate.php
 * Creates a demo candidate account that logs in to the candidate dashboard.
 * Run: http://localhost/voting-system-test/create_candidate.php
 */
require_once __DIR__ . '/system-files/apps/config/dbconnection.php';

use apps\config\dbconnection;

header('Content-Type: text/html; charset=utf-8');

$loginID   = '2024-0201';
$email     = 'candidate@university.edu';
$password  = 'candidate12345';
$firstname = 'Demo';
$lastname  = 'Candidate';
$mi        = 'R';
$program   = 'Bachelor of Science in Information Technology';
$department = 'College of Computer Science and Engineering';
$position  = 'President';
$partylist = 'Progressive Alliance';
$platform  = 'Lead with transparency and inclusive student governance. I will strengthen student services, open budget discussions, and create regular town halls so every voice is heard.';

function renderBox(string $title, string $body, string $type = 'success'): void {
    $styles = [
        'success' => 'background:#d4edda;color:#155724;border:1px solid #c3e6cb;',
        'info'    => 'background:#fff3cd;color:#856404;border:1px solid #ffeeba;',
        'error'   => 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;',
    ];
    $style = $styles[$type] ?? $styles['info'];
    echo "<div style='font-family:sans-serif;padding:20px;border-radius:8px;{$style}max-width:640px;margin:50px auto;'>";
    echo "<h2 style='margin-top:0;'>{$title}</h2>{$body}</div>";
}

try {
    $db = (new dbconnection())->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare('SELECT u.id, u.roles, ci.id AS candidate_id FROM users u LEFT JOIN candidateinfo ci ON ci.userID = u.id WHERE u.loginID = ? OR u.email = ?');
    $stmt->execute([$loginID, $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['roles'] === 'candidate' && $existing['candidate_id']) {
        renderBox(
            'Candidate Account Already Exists',
            '<p>A candidate account is already set up with these credentials.</p>'
            . '<table style="border-collapse:collapse;width:100%;margin-top:15px;">'
            . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Student ID</td><td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($loginID) . '</code></td></tr>'
            . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Email</td><td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($email) . '</code></td></tr>'
            . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Password</td><td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($password) . '</code></td></tr>'
            . '</table>'
            . '<p style="margin-top:20px;">Log in at <a href="system-files/apps/view/login-signup.php#login">login-signup.php</a> — you will be redirected to the candidate dashboard.</p>',
            'info'
        );
        exit;
    }

    $db->beginTransaction();

    $findStudent = $db->prepare('SELECT id FROM studentlist WHERE schoolID = ?');
    $findStudent->execute([$loginID]);
    if (!$findStudent->fetchColumn()) {
        $db->prepare('
            INSERT INTO studentlist (schoolID, firstname, mi, lastname, suffix, program, department)
            VALUES (?, ?, ?, ?, NULL, ?, ?)
        ')->execute([$loginID, $firstname, $mi, $lastname, $program, $department]);
    }

    if ($existing) {
        $userId = (int) $existing['id'];
        $db->prepare("UPDATE users SET roles = 'candidate', password = ?, firstname = ?, lastname = ?, mi = ?, email = ? WHERE id = ?")
           ->execute([password_hash($password, PASSWORD_BCRYPT), $firstname, $lastname, $mi, $email, $userId]);
    } else {
        $db->prepare('
            INSERT INTO users (loginID, firstname, mi, lastname, suffix, email, password, roles)
            VALUES (?, ?, ?, ?, ?, ?, ?, \'candidate\')
        ')->execute([
            $loginID, $firstname, $mi, $lastname, '',
            $email, password_hash($password, PASSWORD_BCRYPT),
        ]);
        $userId = (int) $db->lastInsertId();
    }

    $findCand = $db->prepare('SELECT id FROM candidateinfo WHERE userID = ?');
    $findCand->execute([$userId]);
    if (!$findCand->fetchColumn()) {
        $db->prepare('
            INSERT INTO candidateinfo (userID, partylist, position, status, platform)
            VALUES (?, ?, ?, \'approved\', ?)
        ')->execute([$userId, $partylist, $position, $platform]);

        $candidateId = (int) $db->lastInsertId();
        $achievements = [
            ['Student Council Officer', 'Served as class representative for two consecutive years.'],
            ['Leadership Seminar Graduate', 'Completed university leadership and governance training.'],
        ];
        $insertAch = $db->prepare('INSERT INTO achievements (achievement, description, candidateID) VALUES (?, ?, ?)');
        foreach ($achievements as [$title, $desc]) {
            $insertAch->execute([$title, $desc, $candidateId]);
        }
    }

    $db->commit();

    renderBox(
        'Candidate Account Created',
        '<p>A demo candidate account has been created. Log in to access the candidate dashboard.</p>'
        . '<table style="border-collapse:collapse;width:100%;margin-top:15px;">'
        . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Student ID</td><td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($loginID) . '</code></td></tr>'
        . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Email</td><td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($email) . '</code></td></tr>'
        . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Password</td><td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($password) . '</code></td></tr>'
        . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Position</td><td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($position) . '</td></tr>'
        . '<tr><td style="padding:8px;border-bottom:1px solid #ddd;font-weight:bold;">Party List</td><td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($partylist) . '</td></tr>'
        . '</table>'
        . '<p style="margin-top:20px;"><a href="system-files/apps/view/login-signup.php#login">Go to Login</a></p>'
    );

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    renderBox('Database Error', '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>', 'error');
}
