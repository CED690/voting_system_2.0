<?php
/**
 * api/student/votes.php
 * Check vote status, submit ballot, export ballot summary.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require_once __DIR__ . '/../../../apps/config/dbconnection.php';
use apps\config\dbconnection;

$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';

$requiredPositions = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor'];

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'status') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM votes WHERE userID = ?");
        $stmt->execute([$userId]);
        $hasVoted = (int)$stmt->fetchColumn() > 0;

        $selections = [];
        if ($hasVoted) {
            $stmt = $db->prepare("
                SELECT ci.position, ci.id AS candidate_id,
                       CONCAT(u.firstname, ' ', u.lastname) AS candidate_name,
                       ci.partylist, ci.profilePicture
                FROM votes v
                INNER JOIN candidateinfo ci ON v.candidateID = ci.id
                INNER JOIN users u ON ci.userID = u.id
                WHERE v.userID = ?
            ");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $selections[$row['position']] = $row;
            }
        }

        echo json_encode([
            'success'   => true,
            'has_voted' => $hasVoted,
            'selections'=> $selections
        ]);
        exit;
    }

    if ($action === 'submit') {
        $data = json_decode(file_get_contents('php://input'), true);
        $votes = $data['votes'] ?? [];

        $stmt = $db->prepare("SELECT COUNT(*) FROM votes WHERE userID = ?");
        $stmt->execute([$userId]);
        if ((int)$stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You have already submitted your vote.']);
            exit;
        }

        if (!is_array($votes) || count($votes) !== count($requiredPositions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please select one candidate for each position before confirming.']);
            exit;
        }

        $submittedPositions = [];
        $db->beginTransaction();

        foreach ($votes as $vote) {
            $candidateId = (int)($vote['candidate_id'] ?? 0);
            $position    = trim($vote['position'] ?? '');

            if (!$candidateId || !in_array($position, $requiredPositions, true)) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid vote data.']);
                exit;
            }

            if (in_array($position, $submittedPositions, true)) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Duplicate position in ballot.']);
                exit;
            }

            $stmt = $db->prepare("
                SELECT id FROM candidateinfo
                WHERE id = ? AND position = ? AND LOWER(status) = 'approved'
            ");
            $stmt->execute([$candidateId, $position]);
            if (!$stmt->fetchColumn()) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Invalid candidate for {$position}."]);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO votes (userID, candidateID) VALUES (?, ?)");
            $stmt->execute([$userId, $candidateId]);
            $submittedPositions[] = $position;
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Your vote has been recorded successfully!']);
        exit;
    }

    if ($action === 'export_ballot') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=my_ballot_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Position', 'Voted Candidate', 'Party List']);

        $stmt = $db->prepare("
            SELECT ci.position,
                   CONCAT(u.firstname, ' ', u.lastname) AS candidate_name,
                   ci.partylist
            FROM votes v
            INNER JOIN candidateinfo ci ON v.candidateID = ci.id
            INNER JOIN users u ON ci.userID = u.id
            WHERE v.userID = ?
            ORDER BY FIELD(ci.position, 'President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor')
        ");
        $stmt->execute([$userId]);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            // Write a note if student hasn't voted yet
            fputcsv($output, ['No votes cast yet.', '', '']);
        } else {
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['position'],
                    $row['candidate_name'],
                    $row['partylist'] ?: 'Independent'
                ]);
            }
        }

        fclose($output);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
