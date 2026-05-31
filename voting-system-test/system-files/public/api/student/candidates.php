<?php
/**
 * api/student/candidates.php
 * List candidates, standings, and profile details for students.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

// Read-only candidate data is publicly accessible for landing page and candidates listing
require_once __DIR__ . '/../../../apps/config/dbconnection.php';
use apps\config\dbconnection;

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Candidate ID is required.']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT ci.id, ci.position, ci.partylist, ci.platform, ci.profilePicture,
                   u.firstname, u.lastname, u.mi, u.suffix, sl.department, sl.program
            FROM candidateinfo ci
            INNER JOIN users u ON ci.userID = u.id
            LEFT JOIN studentlist sl ON u.loginID = sl.schoolID
            WHERE ci.id = ? AND LOWER(ci.status) = 'approved'
        ");
        $stmt->execute([$id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$candidate) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Candidate not found.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, achievement, description FROM achievements WHERE candidateID = ?");
        $stmt->execute([$id]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $candidate['fullname'] = trim("{$candidate['firstname']} {$candidate['lastname']}");
        echo json_encode(['success' => true, 'data' => ['candidate' => $candidate, 'achievements' => $achievements]]);
        exit;
    }

    $positionFilter = trim($_GET['position'] ?? '');
    $positionMap = [
        'president'       => 'President',
        'vice-president'  => 'Vice President',
        'secretary'       => 'Secretary',
        'treasurer'       => 'Treasurer',
        'auditor'         => 'Auditor',
    ];

    $query = "
        SELECT 
            ci.id,
            ci.position,
            ci.partylist,
            ci.platform,
            ci.profilePicture,
            u.firstname,
            u.lastname,
            COUNT(v.id) AS vote_count
        FROM candidateinfo ci
        INNER JOIN users u ON ci.userID = u.id
        LEFT JOIN votes v ON ci.id = v.candidateID
        WHERE LOWER(ci.status) = 'approved'
    ";
    $params = [];

    if ($positionFilter !== '' && isset($positionMap[$positionFilter])) {
        $query .= " AND ci.position = :position";
        $params[':position'] = $positionMap[$positionFilter];
    }

    $query .= " GROUP BY ci.id ORDER BY ci.position, vote_count DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($candidates as &$c) {
        $c['fullname'] = trim("{$c['firstname']} {$c['lastname']}");
        $stmt = $db->prepare("SELECT achievement, description FROM achievements WHERE candidateID = ? LIMIT 3");
        $stmt->execute([$c['id']]);
        $c['achievements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($c);

    if ($action === 'standings') {
        $byPosition = [];
        foreach ($candidates as $c) {
            $byPosition[$c['position']][] = $c;
        }
        echo json_encode(['success' => true, 'data' => ['by_position' => $byPosition, 'candidates' => $candidates]]);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $candidates]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
