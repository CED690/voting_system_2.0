<?php
/**
 * seed_voters.php
 * Creates student (voter) accounts so users can browse candidates and cast votes.
 * Run seed_candidates.php first if you need candidacy profiles on studentlist entries.
 * Run: http://localhost/voting-system-test/seed_voters.php
 */
require_once __DIR__ . '/system-files/apps/config/dbconnection.php';

use apps\config\dbconnection;

header('Content-Type: text/html; charset=utf-8');

$defaultPassword = 'student12345';

$voters = [
    [
        'schoolID'   => '2024-0101',
        'firstname'  => 'Liza',
        'mi'         => 'A',
        'lastname'   => 'Mendoza',
        'suffix'     => null,
        'program'    => 'Bachelor of Science in Information Technology',
        'department' => 'College of Computer Science and Engineering',
        'email'      => 'liza.mendoza@university.edu',
    ],
    [
        'schoolID'   => '2024-0102',
        'firstname'  => 'Mark',
        'mi'         => 'D',
        'lastname'   => 'Villanueva',
        'suffix'     => null,
        'program'    => 'Bachelor of Science in Business Administration',
        'department' => 'College of Business Adminstration',
        'email'      => 'mark.villanueva@university.edu',
    ],
    [
        'schoolID'   => '2024-0103',
        'firstname'  => 'Grace',
        'mi'         => 'P',
        'lastname'   => 'Lim',
        'suffix'     => null,
        'program'    => 'Bachelor of Arts in Communication',
        'department' => 'College of Arts and Science',
        'email'      => 'grace.lim@university.edu',
    ],
];

function renderPage(string $title, string $body, string $type = 'success'): void {
    $styles = [
        'success' => 'background:#d4edda;color:#155724;border:1px solid #c3e6cb;',
        'info'    => 'background:#cce5ff;color:#004085;border:1px solid #b8daff;',
        'error'   => 'background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;',
    ];
    $style = $styles[$type] ?? $styles['info'];
    echo "<div style='font-family:sans-serif;padding:20px;border-radius:8px;{$style}max-width:800px;margin:50px auto;'>";
    echo "<h2 style='margin-top:0;'>{$title}</h2>{$body}</div>";
}

try {
    $db = (new dbconnection())->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    $insertStudent = $db->prepare("
        INSERT INTO studentlist (schoolID, firstname, mi, lastname, suffix, program, department)
        VALUES (:schoolID, :firstname, :mi, :lastname, :suffix, :program, :department)
    ");
    $findStudent = $db->prepare('SELECT id FROM studentlist WHERE schoolID = ? LIMIT 1');
    $findUser = $db->prepare('SELECT id, roles FROM users WHERE loginID = ? LIMIT 1');
    $insertUser = $db->prepare("
        INSERT INTO users (loginID, firstname, mi, lastname, suffix, email, password, roles)
        VALUES (:loginID, :firstname, :mi, :lastname, :suffix, :email, :password, 'student')
    ");
    $updateStudentRole = $db->prepare("UPDATE users SET roles = 'student' WHERE id = ? AND roles != 'admin'");

    $created = 0;
    $skipped = 0;
    $rows = '';

    foreach ($voters as $v) {
        $findStudent->execute([$v['schoolID']]);
        if (!$findStudent->fetchColumn()) {
            $insertStudent->execute([
                ':schoolID'   => $v['schoolID'],
                ':firstname'  => $v['firstname'],
                ':mi'         => $v['mi'],
                ':lastname'   => $v['lastname'],
                ':suffix'     => $v['suffix'],
                ':program'    => $v['program'],
                ':department' => $v['department'],
            ]);
        }

        $findUser->execute([$v['schoolID']]);
        $existing = $findUser->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['roles'] !== 'student' && $existing['roles'] !== 'admin') {
                $updateStudentRole->execute([$existing['id']]);
            }
            $skipped++;
            $rows .= '<tr><td colspan="4" style="padding:8px;border-bottom:1px solid #ddd;color:#666;">'
                . htmlspecialchars($v['schoolID']) . ' — account already exists</td></tr>';
            continue;
        }

        $insertUser->execute([
            ':loginID'   => $v['schoolID'],
            ':firstname' => $v['firstname'],
            ':mi'        => $v['mi'],
            ':lastname'  => $v['lastname'],
            ':suffix'    => $v['suffix'] ?? '',
            ':email'     => $v['email'],
            ':password'  => password_hash($defaultPassword, PASSWORD_BCRYPT),
        ]);
        $created++;

        $rows .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($v['schoolID']) . '</code></td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($v['firstname'] . ' ' . $v['lastname']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($v['email']) . '</code></td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($defaultPassword) . '</code></td>'
            . '</tr>';
    }

    $db->commit();

    $body = '<p>Student voter accounts are ready. Use these to log in, browse candidates, and cast votes.</p>'
        . "<p><strong>{$created}</strong> new voter account(s) created. <strong>{$skipped}</strong> skipped (already exist).</p>"
        . '<table style="border-collapse:collapse;width:100%;margin-top:15px;">'
        . '<tr>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Student ID</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Name</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Email</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Password</th>'
        . '</tr>'
        . $rows
        . '</table>'
        . '<p style="margin-top:20px;">Login at '
        . '<a href="system-files/apps/view/login-signup.php#login">login-signup.php</a>'
        . ' using Student ID or Email, then go to Browse → Cast Your Vote.</p>'
        . '<p style="font-size:0.9em;color:#6c757d;"><strong>Note:</strong> Candidates also log in as students and can vote. Their candidacy tools appear under My Candidacy after login.</p>';

    renderPage('Voter Accounts Ready', $body, $created > 0 ? 'success' : 'info');

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    renderPage('Database Error', '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>', 'error');
}
