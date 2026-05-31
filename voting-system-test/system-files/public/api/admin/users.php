<?php
/**
 * api/admin/users.php
 * Manage users (list, archive/delete, disable).
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

$action = $_GET['action'] ?? 'list';

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'list') {
        $roleFilter = $_GET['role'] ?? 'all'; // all, student, candidate
        $search = trim($_GET['search'] ?? '');

        $query = "
            SELECT 
                u.id,
                u.loginID,
                u.firstname,
                u.lastname,
                u.mi,
                u.suffix,
                u.email,
                u.roles,
                u.createdAt,
                sl.department,
                sl.program,
                ci.status as candidate_status,
                CASE WHEN ci.id IS NOT NULL THEN 1 ELSE 0 END as is_candidate
            FROM users u
            LEFT JOIN studentlist sl ON u.loginID = sl.schoolID
            LEFT JOIN candidateinfo ci ON u.id = ci.userID
            WHERE 1=1
        ";
        
        $params = [];

        if ($roleFilter === 'student') {
            $query .= " AND u.roles = 'student' AND ci.id IS NULL";
        } elseif ($roleFilter === 'with_candidacy' || $roleFilter === 'candidate') {
            $query .= " AND ci.id IS NOT NULL";
        }

        if ($search !== '') {
            $query .= " AND (u.loginID LIKE :search OR u.firstname LIKE :search2 OR u.lastname LIKE :search3 OR u.email LIKE :search4)";
            $params[':search']  = "%$search%";
            $params[':search2'] = "%$search%";
            $params[':search3'] = "%$search%";
            $params[':search4'] = "%$search%";
        }

        $query .= " ORDER BY u.id DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $users]);
        exit;

    } elseif ($action === 'archive' || $action === 'delete') {
        // We delete users. In XAMPP tests, direct deletion is standard since there is no archive table.
        $data = json_decode(file_get_contents('php://input'), true);
        $userIds = $data['ids'] ?? [];

        if (empty($userIds) || !is_array($userIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No user IDs provided.']);
            exit;
        }

        // Placeholders for IN query
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        
        // Delete candidate info, achievements, votes, and users to prevent constraint errors
        $db->beginTransaction();
        
        // 1. Delete achievements associated with candidates
        $stmt = $db->prepare("DELETE FROM achievements WHERE candidateID IN (SELECT id FROM candidateinfo WHERE userID IN ($placeholders))");
        $stmt->execute($userIds);

        // 2. Delete candidate info
        $stmt = $db->prepare("DELETE FROM candidateinfo WHERE userID IN ($placeholders)");
        $stmt->execute($userIds);

        // 3. Delete votes by/for these users
        $stmt = $db->prepare("DELETE FROM votes WHERE userID IN ($placeholders) OR candidateID IN (SELECT id FROM candidateinfo WHERE userID IN ($placeholders))");
        // We pass it twice because of two parameters in OR
        $stmt->execute(array_merge($userIds, $userIds));

        // 4. Delete users
        $stmt = $db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
        $stmt->execute($userIds);

        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Users successfully deleted.']);
        exit;

    } elseif ($action === 'toggle_status') {
        // Since students do not have status, we toggle candidates status between 'approved' and 'pending'
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? null;
        $status = $data['status'] ?? 'pending'; // approved, pending, rejected, disabled

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            exit;
        }

        // Check if candidateinfo row exists
        $stmt = $db->prepare("SELECT id FROM candidateinfo WHERE userID = ?");
        $stmt->execute([$userId]);
        $candId = $stmt->fetchColumn();

        if ($candId) {
            $stmt = $db->prepare("UPDATE candidateinfo SET status = ? WHERE userID = ?");
            $stmt->execute([$status, $userId]);
            echo json_encode(['success' => true, 'message' => 'Candidate status updated to ' . $status]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Only candidates have an approval status.']);
        }
        exit;
    }

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
