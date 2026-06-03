<?php
/**
 * api/admin/edit_user.php
 * Fetch and edit detailed student/candidate information and achievements.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../../apps/config/dbconnection.php';
use apps\config\dbconnection;

$action = $_GET['action'] ?? 'get';

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'get') {
        $userId = $_GET['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            exit;
        }

        // Fetch basic details
        $stmt = $db->prepare("
            SELECT u.id, u.loginID, u.firstname, u.lastname, u.mi, u.suffix, u.email, u.roles, u.lastLogin,
                   sl.program, sl.department
            FROM users u
            LEFT JOIN studentlist sl ON u.loginID = sl.schoolID
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Fetch candidate details if candidate
        $candidateInfo = null;
        $achievements  = [];

        $stmt = $db->prepare("SELECT * FROM candidateinfo WHERE userID = ?");
        $stmt->execute([$userId]);
        $candidateInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($candidateInfo) {
            $stmt = $db->prepare("SELECT * FROM achievements WHERE candidateID = ?");
            $stmt->execute([$candidateInfo['id']]);
            $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'user'          => $user,
                'candidateinfo' => $candidateInfo,
                'achievements'  => $achievements
            ]
        ]);
        exit;

    } elseif ($action === 'save') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            exit;
        }

        $db->beginTransaction();

        // 1. Update basic user details
        $role = $data['role'] ?? 'student';
        $hasCandidacy = !empty($data['is_candidate'])
            || ($data['has_candidacy'] ?? '') === 'yes'
            || $role === 'candidate';
        $storedRole = ($role === 'admin') ? 'admin' : 'student';

        $stmt = $db->prepare("
            UPDATE users 
            SET firstname = :firstname, lastname = :lastname, mi = :mi, suffix = :suffix, email = :email, roles = :roles
            WHERE id = :id
        ");
        $stmt->execute([
            ':firstname' => $data['first_name'],
            ':lastname'  => $data['last_name'],
            ':mi'        => $data['m_i'] ?? '',
            ':suffix'    => $data['suffix'] ?? '',
            ':email'     => $data['email'],
            ':roles'     => $storedRole,
            ':id'        => $userId
        ]);

        // Get LoginID to update studentlist
        $stmt = $db->prepare("SELECT loginID FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $loginID = $stmt->fetchColumn();

        // 2. Update studentlist program & department
        if ($loginID) {
            $stmt = $db->prepare("
                UPDATE studentlist 
                SET program = :program, department = :department, firstname = :firstname, lastname = :lastname, mi = :mi, suffix = :suffix
                WHERE schoolID = :schoolID
            ");
            $stmt->execute([
                ':program'    => $data['program'],
                ':department' => $data['department'],
                ':firstname'  => $data['first_name'],
                ':lastname'   => $data['last_name'],
                ':mi'         => $data['m_i'] ?? '',
                ':suffix'     => $data['suffix'] ?? '',
                ':schoolID'   => $loginID
            ]);
        }

        // 3. Handle candidate profile extension (student role + candidateinfo)
        if ($hasCandidacy) {
            $stmt = $db->prepare("SELECT id FROM candidateinfo WHERE userID = ?");
            $stmt->execute([$userId]);
            $candId = $stmt->fetchColumn();

            if ($candId) {
                // Update
                $stmt = $db->prepare("
                    UPDATE candidateinfo 
                    SET partylist = :partylist, position = :position, status = :status, platform = :platform
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':partylist' => $data['partylist'] ?? '',
                    ':position'  => $data['position'] ?? 'President',
                    ':status'    => $data['cand_status'] ?? 'pending',
                    ':platform'  => $data['platform'] ?? '',
                    ':id'        => $candId
                ]);
            } else {
                // Insert new candidate info
                $stmt = $db->prepare("
                    INSERT INTO candidateinfo (userID, partylist, position, status, platform)
                    VALUES (:userID, :partylist, :position, :status, :platform)
                ");
                $stmt->execute([
                    ':userID'    => $userId,
                    ':partylist' => $data['partylist'] ?? '',
                    ':position'  => $data['position'] ?? 'President',
                    ':status'    => $data['cand_status'] ?? 'pending',
                    ':platform'  => $data['platform'] ?? ''
                ]);
            }
        } else {
            // If they are demoted to student, clean up their candidateinfo and achievements
            $stmt = $db->prepare("SELECT id FROM candidateinfo WHERE userID = ?");
            $stmt->execute([$userId]);
            $candId = $stmt->fetchColumn();
            
            if ($candId) {
                $stmt = $db->prepare("DELETE FROM achievements WHERE candidateID = ?");
                $stmt->execute([$candId]);

                $stmt = $db->prepare("DELETE FROM candidateinfo WHERE id = ?");
                $stmt->execute([$candId]);
            }
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'User details saved successfully!']);
        exit;

    } elseif ($action === 'add_achievement') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? null;
        $title = trim($data['title'] ?? '');
        $desc = trim($data['desc'] ?? '');

        if (!$userId || !$title) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        // Get candidate ID
        $stmt = $db->prepare("SELECT id FROM candidateinfo WHERE userID = ?");
        $stmt->execute([$userId]);
        $candId = $stmt->fetchColumn();

        if (!$candId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User is not a candidate.']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO achievements (achievement, description, candidateID) VALUES (?, ?, ?)");
        $stmt->execute([$title, $desc, $candId]);

        echo json_encode(['success' => true, 'message' => 'Achievement added successfully.']);
        exit;

    } elseif ($action === 'remove_achievement') {
        $data = json_decode(file_get_contents('php://input'), true);
        $achievementId = $data['achievement_id'] ?? null;

        if (!$achievementId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Achievement ID is required.']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM achievements WHERE id = ?");
        $stmt->execute([$achievementId]);

        echo json_encode(['success' => true, 'message' => 'Achievement removed successfully.']);
        exit;

    } elseif ($action === 'update_document_status') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? null;
        $docKey = $data['doc_key'] ?? null;
        $status = $data['status'] ?? null;

        if (!$userId || !$docKey || !$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, documents, status FROM candidateinfo WHERE userID = ?");
        $stmt->execute([$userId]);
        $cand = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cand) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Candidate profile not found.']);
            exit;
        }

        $candId = (int)$cand['id'];

        if ($docKey === 'overall') {
            $validStatuses = ['approved', 'rejected', 'pending'];
            $normalizedStatus = strtolower($status);
            if ($normalizedStatus === 'declined') {
                $normalizedStatus = 'rejected';
            }
            if (!in_array($normalizedStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid overall status.']);
                exit;
            }

            $stmt = $db->prepare("UPDATE candidateinfo SET status = ? WHERE id = ?");
            $stmt->execute([$normalizedStatus, $candId]);

            echo json_encode(['success' => true, 'message' => 'Candidate overall status updated to ' . $normalizedStatus]);
            exit;
        } else {
            $docs = json_decode($cand['documents'] ?? '', true);
            if (!is_array($docs)) {
                $docs = [];
            }

            if (!isset($docs[$docKey])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Document not found for this candidate.']);
                exit;
            }

            $docs[$docKey]['status'] = $status;

            $stmt = $db->prepare("UPDATE candidateinfo SET documents = ? WHERE id = ?");
            $stmt->execute([json_encode($docs), $candId]);

            echo json_encode(['success' => true, 'message' => 'Document status updated.', 'documents' => $docs]);
            exit;
        }
    }

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
