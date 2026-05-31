<?php
/**
 * api/admin/analytics.php
 * Fetch dashboard analytics and candidate overview.
 */
session_start();

// Enable CORS and JSON content type
header('Content-Type: application/json; charset=utf-8');

// Verification check
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../../apps/config/dbconnection.php';
use apps\config\dbconnection;

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Total Registered Students
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE roles = 'student'");
    $totalStudents = (int)$stmt->fetchColumn();

    // 2. Total Candidates
    $stmt = $db->query("SELECT COUNT(*) FROM candidateinfo");
    $totalCandidates = (int)$stmt->fetchColumn();

    // 3. Ballots submitted (includes voters who abstained on some or all positions)
    $votesCast = 0;
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM ballot_submissions");
        $votesCast = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        $stmt = $db->query("SELECT COUNT(DISTINCT userID) FROM votes");
        $votesCast = (int) $stmt->fetchColumn();
    }

    // 4. Voter Turnout (Voted / Total Students)
    $voterTurnout = 0;
    if ($totalStudents > 0) {
        $voterTurnout = round(($votesCast / $totalStudents) * 100, 1);
    }

    // 5. Candidate Overview (Grouped by Position and Candidate)
    // We join users, candidateinfo, and calculate votes
    $query = "
        SELECT 
            ci.id as candidate_id,
            u.firstname,
            u.lastname,
            ci.position,
            ci.partylist,
            ci.profilePicture,
            COUNT(v.id) as vote_count
        FROM candidateinfo ci
        INNER JOIN users u ON ci.userID = u.id
        LEFT JOIN votes v ON ci.id = v.candidateID
        GROUP BY ci.id
        ORDER BY ci.position, vote_count DESC
    ";
    $candidates = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($candidates as &$c) {
        $deptQuery = "
            SELECT 
                sl.department,
                COUNT(v.id) as vote_count
            FROM votes v
            INNER JOIN users u ON v.userID = u.id
            INNER JOIN studentlist sl ON u.loginID = sl.schoolID
            WHERE v.candidateID = ?
            GROUP BY sl.department
        ";
        $deptStmt = $db->prepare($deptQuery);
        $deptStmt->execute([$c['candidate_id']]);
        $c['departments'] = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($c);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_students'   => $totalStudents,
            'total_candidates' => $totalCandidates,
            'votes_cast'       => $votesCast,
            'voter_turnout'    => $voterTurnout,
            'candidates'       => $candidates
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
