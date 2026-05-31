<?php
/**
 * seed_candidates.php
 * Creates candidate accounts from every row in studentlist.
 * Run once: http://localhost/voting-system-test/seed_candidates.php
 */
require_once __DIR__ . '/system-files/apps/config/dbconnection.php';

use apps\config\dbconnection;

header('Content-Type: text/html; charset=utf-8');

$defaultPassword = 'student12345';

$positionPlan = [
    '2024-0001' => ['position' => 'President',       'partylist' => 'Progressive Alliance'],
    '2024-0002' => ['position' => 'Vice President',  'partylist' => 'Unity Party'],
    '2024-0003' => ['position' => 'Secretary',       'partylist' => 'Student Reform Coalition'],
    '2024-0004' => ['position' => 'Treasurer',       'partylist' => 'Progressive Alliance'],
    '2024-0005' => ['position' => 'Auditor',         'partylist' => 'Independent'],
    '2024-0006' => ['position' => 'President',       'partylist' => 'Unity Party'],
];

$platforms = [
    'President'       => 'Lead with transparency and inclusive student governance. I will strengthen student services, open budget discussions, and create regular town halls so every voice is heard.',
    'Vice President'  => 'Support the president in delivering meaningful campus reforms while coordinating student organizations and ensuring accountability across all student council initiatives.',
    'Secretary'       => 'Keep accurate records, improve communication between the student body and council, and streamline document access for all university organizations.',
    'Treasurer'       => 'Ensure responsible fund management with clear reporting, fair allocation of resources, and student-led oversight of all council expenditures.',
    'Auditor'         => 'Promote fiscal accountability through regular audits, transparent reporting, and strict compliance with university financial policies.',
];

$achievementSets = [
    [
        ['title' => 'Student Council Officer', 'desc' => 'Served as class representative for two consecutive academic years.'],
        ['title' => 'Leadership Seminar Graduate', 'desc' => 'Completed university leadership and governance training program.'],
    ],
    [
        ['title' => 'Dean\'s Lister', 'desc' => 'Maintained academic excellence while actively participating in campus activities.'],
        ['title' => 'Organization President', 'desc' => 'Led a university-wide student organization with 200+ members.'],
    ],
    [
        ['title' => 'Community Outreach Volunteer', 'desc' => 'Organized barangay literacy programs and university outreach drives.'],
        ['title' => 'Event Coordinator', 'desc' => 'Managed logistics for major university events and student assemblies.'],
    ],
];

function slugEmail(string $firstname, string $lastname, string $schoolId): string {
    $base = strtolower(preg_replace('/[^a-z0-9]+/', '.', trim($firstname . '.' . $lastname), -1, $count));
    $base = trim($base, '.');
    if ($base === '') {
        $base = 'student';
    }
    return $base . '.' . str_replace('-', '', $schoolId) . '@university.edu';
}

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

    $students = $db->query('SELECT * FROM studentlist ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        renderPage('No Students Found', '<p>The <strong>studentlist</strong> table is empty. Add students first before seeding candidates.</p>', 'error');
        exit;
    }

    $db->beginTransaction();

    $createdUsers = 0;
    $updatedUsers = 0;
    $createdCandidates = 0;
    $skipped = [];
    $rows = [];

    $findUser = $db->prepare('SELECT id, email, roles FROM users WHERE loginID = ? LIMIT 1');
    $insertUser = $db->prepare("
        INSERT INTO users (loginID, firstname, mi, lastname, suffix, email, password, roles)
        VALUES (:loginID, :firstname, :mi, :lastname, :suffix, :email, :password, 'student')
    ");
    $updateRole = $db->prepare("UPDATE users SET roles = 'student' WHERE id = ?");
    $findCandidate = $db->prepare('SELECT id FROM candidateinfo WHERE userID = ? LIMIT 1');
    $insertCandidate = $db->prepare("
        INSERT INTO candidateinfo (userID, partylist, position, status, platform)
        VALUES (:userID, :partylist, :position, 'approved', :platform)
    ");
    $insertAchievement = $db->prepare('
        INSERT INTO achievements (achievement, description, candidateID)
        VALUES (:achievement, :description, :candidateID)
    ');

    foreach ($students as $index => $student) {
        $schoolId = $student['schoolID'];
        $plan = $positionPlan[$schoolId] ?? [
            'position'  => 'President',
            'partylist' => 'Independent',
        ];
        $position = $plan['position'];
        $partylist = $plan['partylist'];
        $platform = $platforms[$position] ?? 'Committed to serving the student body with integrity and dedication.';

        $findUser->execute([$schoolId]);
        $user = $findUser->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userId = (int) $user['id'];
            if ($user['roles'] === 'admin') {
                $skipped[] = "{$schoolId} — skipped (admin account)";
                continue;
            }
            // Ensure existing accounts use student role (candidacy is stored in candidateinfo)
            if ($user['roles'] !== 'student' && $user['roles'] !== 'admin') {
                $updateRole->execute([$userId]);
                $updatedUsers++;
            }
        } else {
            $email = slugEmail($student['firstname'], $student['lastname'], $schoolId);

            $emailCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $emailCheck->execute([$email]);
            if ((int) $emailCheck->fetchColumn() > 0) {
                $email = strtolower(str_replace('-', '', $schoolId)) . '@university.edu';
            }

            $insertUser->execute([
                ':loginID'   => $schoolId,
                ':firstname' => $student['firstname'],
                ':mi'        => $student['mi'] ?? '',
                ':lastname'  => $student['lastname'],
                ':suffix'    => $student['suffix'] ?? '',
                ':email'     => $email,
                ':password'  => password_hash($defaultPassword, PASSWORD_BCRYPT),
            ]);
            $userId = (int) $db->lastInsertId();
            $createdUsers++;
        }

        $findCandidate->execute([$userId]);
        $existingCandId = $findCandidate->fetchColumn();

        if ($existingCandId) {
            $skipped[] = "{$schoolId} — candidate profile already exists";
            continue;
        }

        $insertCandidate->execute([
            ':userID'    => $userId,
            ':partylist' => $partylist,
            ':position'  => $position,
            ':platform'  => $platform,
        ]);
        $candidateId = (int) $db->lastInsertId();
        $createdCandidates++;

        $achievements = $achievementSets[$index % count($achievementSets)];
        foreach ($achievements as $ach) {
            $insertAchievement->execute([
                ':achievement'  => $ach['title'],
                ':description'  => $ach['desc'],
                ':candidateID'  => $candidateId,
            ]);
        }

        $fullName = trim("{$student['firstname']} {$student['lastname']}");
        $rows[] = [
            'schoolId'  => $schoolId,
            'name'      => $fullName,
            'position'  => $position,
            'partylist' => $partylist,
            'department'=> $student['department'],
        ];
    }

    $db->commit();

    $tableRows = '';
    foreach ($rows as $row) {
        $tableRows .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;"><code>' . htmlspecialchars($row['schoolId']) . '</code></td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($row['name']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($row['position']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($row['partylist']) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #ddd;">' . htmlspecialchars($row['department']) . '</td>'
            . '</tr>';
    }

    $body = '<p>Candidate profiles were generated from the <strong>studentlist</strong> table.</p>'
        . '<ul>'
        . "<li><strong>{$createdUsers}</strong> new user account(s) created</li>"
        . "<li><strong>{$updatedUsers}</strong> existing user(s) updated to student role</li>"
        . "<li><strong>{$createdCandidates}</strong> candidate profile(s) created</li>"
        . '</ul>';

    if ($createdUsers > 0) {
        $body .= '<p><strong>Default password for new accounts:</strong> <code>' . htmlspecialchars($defaultPassword) . '</code></p>';
    }

    if (!empty($skipped)) {
        $body .= '<p><strong>Skipped:</strong></p><ul>';
        foreach ($skipped as $item) {
            $body .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $body .= '</ul>';
    }

    $body .= '<table style="border-collapse:collapse;width:100%;margin-top:15px;">'
        . '<tr>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Student ID</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Name</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Position</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Party List</th>'
        . '<th style="text-align:left;padding:8px;border-bottom:2px solid #ddd;">Department</th>'
        . '</tr>'
        . $tableRows
        . '</table>'
        . '<p style="margin-top:20px;font-size:0.9em;color:#6c757d;">All seeded candidates are set to <strong>approved</strong> status so they appear in student voting immediately.</p>';

    renderPage('Candidates Seeded Successfully', $body, 'success');

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    renderPage(
        'Database Error',
        '<p>Could not seed candidates.</p><pre style="background:#fff;padding:10px;border-radius:4px;overflow-x:auto;">'
        . htmlspecialchars($e->getMessage()) . '</pre>',
        'error'
    );
}
