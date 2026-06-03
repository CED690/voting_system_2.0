<?php
/**
 * api/student/register_candidate.php
 * Endpoint for a student to register as a candidate.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../../apps/config/dbconnection.php';
require_once __DIR__ . '/../../../apps/config/session_helpers.php';
use apps\config\dbconnection;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $db = (new dbconnection())->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = (int) $_SESSION['user_id'];

    // Check if user already has a candidate profile
    $stmt = $db->prepare("SELECT id FROM candidateinfo WHERE userID = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You are already registered as a candidate.']);
        exit;
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $position = trim($data['position'] ?? '');
    $partylist = trim($data['partylist'] ?? '');
    $platform = trim($data['platform'] ?? '');

    $validPositions = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor'];
    // Normalize position case to match enum
    $normalizedPosition = '';
    foreach ($validPositions as $vp) {
        if (strcasecmp($vp, $position) === 0) {
            $normalizedPosition = $vp;
            break;
        }
    }

    if (!$normalizedPosition) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please select a valid position.']);
        exit;
    }

    if (empty($platform)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please enter your campaign platform.']);
        exit;
    }

    // Insert new candidate info
    $stmt = $db->prepare("
        INSERT INTO candidateinfo (userID, partylist, position, status, platform, documents)
        VALUES (?, ?, ?, 'pending', ?, ?)
    ");
    $stmt->execute([
        $userId,
        $partylist ?: 'Independent',
        $normalizedPosition,
        $platform,
        json_encode((object)[])
    ]);

    // Sync session
    syncCandidateSession($db, $userId);

    echo json_encode([
        'success' => true,
        'message' => 'Candidacy application submitted successfully!'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
