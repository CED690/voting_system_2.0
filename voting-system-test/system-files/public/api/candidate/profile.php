<?php
/**
 * api/candidate/profile.php
 * Fetch logged-in candidate profile and stats.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../apps/config/session_helpers.php';
require_once __DIR__ . '/../../../apps/config/dbconnection.php';
require_once __DIR__ . '/../../../apps/config/profile_picture.php';

use apps\config\dbconnection;

$db = (new dbconnection())->connect();

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

if (!userHasCandidateProfile($db, $userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? 'save';
    
    try {
        $db = (new dbconnection())->connect();
        
        if ($action === 'save') {
            $partylist = $_POST['partylist'] ?? '';
            $platform = $_POST['platform'] ?? '';

            $stmt = $db->prepare("UPDATE candidateinfo SET partylist = ?, platform = ? WHERE userID = ?");
            $stmt->execute([$partylist, $platform, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
            exit;
            
        } elseif ($action === 'add_achievement') {
            $data = json_decode(file_get_contents('php://input'), true);
            $title = trim($data['title'] ?? '');
            $desc = trim($data['desc'] ?? '');
            
            if (!$title) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Achievement title is required.']);
                exit;
            }
            
            $stmt = $db->prepare("SELECT id FROM candidateinfo WHERE userID = ?");
            $stmt->execute([$userId]);
            $candId = $stmt->fetchColumn();
            
            if (!$candId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Candidate profile not found.']);
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO achievements (achievement, description, candidateID) VALUES (?, ?, ?)");
            $stmt->execute([$title, $desc, $candId]);
            
            echo json_encode(['success' => true, 'message' => 'Achievement added successfully.']);
            exit;
            
        } elseif ($action === 'remove_achievement') {
            $data = json_decode(file_get_contents('php://input'), true);
            $achievementId = (int)($data['achievement_id'] ?? 0);
            
            if (!$achievementId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Achievement ID is required.']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT a.id FROM achievements a
                INNER JOIN candidateinfo ci ON a.candidateID = ci.id
                WHERE a.id = ? AND ci.userID = ?
            ");
            $stmt->execute([$achievementId, $userId]);
            if (!$stmt->fetchColumn()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM achievements WHERE id = ?");
            $stmt->execute([$achievementId]);
            
            echo json_encode(['success' => true, 'message' => 'Achievement removed successfully.']);
            exit;

        } elseif ($action === 'upload_photo') {
            $result = saveCandidateProfilePictureUpload($db, $userId, $_FILES['photo'] ?? []);
            if (!$result['success']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message']]);
                exit;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Profile photo updated.',
                'profilePicture' => $result['path'],
            ]);
            exit;

        } elseif ($action === 'remove_photo') {
            if (!removeCandidateProfilePicture($db, $userId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Candidate profile not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Profile photo removed.', 'profilePicture' => null]);
            exit;
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

try {
    $db = (new dbconnection())->connect();

    $stmt = $db->prepare("
        SELECT u.id, u.loginID, u.firstname, u.lastname, u.mi, u.suffix, u.email,
               sl.program, sl.department,
               ci.id AS candidate_id, ci.position, ci.partylist, ci.status, ci.platform, ci.profilePicture,
               (SELECT COUNT(*) FROM votes v WHERE v.candidateID = ci.id) AS vote_count
        FROM users u
        LEFT JOIN studentlist sl ON u.loginID = sl.schoolID
        LEFT JOIN candidateinfo ci ON ci.userID = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Profile not found.']);
        exit;
    }

    $achievements = [];
    if ($profile['candidate_id']) {
        $stmt = $db->prepare('SELECT id, achievement, description FROM achievements WHERE candidateID = ?');
        $stmt->execute([$profile['candidate_id']]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'profile'      => $profile,
            'achievements' => $achievements,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
