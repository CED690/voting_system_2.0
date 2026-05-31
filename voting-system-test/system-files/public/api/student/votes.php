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

function ensureBallotSubmissionsTable(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS ballot_submissions (
            userID INT NOT NULL PRIMARY KEY,
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ballot_submissions_user
                FOREIGN KEY (userID) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $db->exec("
        INSERT IGNORE INTO ballot_submissions (userID)
        SELECT DISTINCT v.userID
        FROM votes v
        LEFT JOIN ballot_submissions b ON b.userID = v.userID
        WHERE b.userID IS NULL
    ");
}

function userHasSubmittedBallot(PDO $db, int $userId): bool
{
    ensureBallotSubmissionsTable($db);
    $stmt = $db->prepare('SELECT 1 FROM ballot_submissions WHERE userID = ? LIMIT 1');
    $stmt->execute([$userId]);
    return (bool) $stmt->fetchColumn();
}

try {
    $dbClass = new dbconnection();
    $db = $dbClass->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'status') {
        $hasVoted = userHasSubmittedBallot($db, $userId);

        $selections = [];
        $abstained = [];
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
            $abstained = array_values(array_diff($requiredPositions, array_keys($selections)));
        }

        echo json_encode([
            'success'    => true,
            'has_voted'  => $hasVoted,
            'selections' => $selections,
            'abstained'  => $abstained,
        ]);
        exit;
    }

    if ($action === 'submit') {
        $data = json_decode(file_get_contents('php://input'), true);
        $votes = $data['votes'] ?? [];

        if (userHasSubmittedBallot($db, $userId)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You have already submitted your vote.']);
            exit;
        }

        if (!is_array($votes) || count($votes) !== count($requiredPositions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please complete every position on your ballot before confirming.']);
            exit;
        }

        $submittedPositions = [];
        $db->beginTransaction();
        ensureBallotSubmissionsTable($db);

        foreach ($votes as $vote) {
            $position = trim($vote['position'] ?? '');
            $candidateId = $vote['candidate_id'] ?? null;

            if (!in_array($position, $requiredPositions, true)) {
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

            $submittedPositions[] = $position;

            if ($candidateId === null || $candidateId === '' || (int) $candidateId === 0) {
                continue;
            }

            $candidateId = (int) $candidateId;

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

            $stmt = $db->prepare('INSERT INTO votes (userID, candidateID) VALUES (?, ?)');
            $stmt->execute([$userId, $candidateId]);
        }

        if (count($submittedPositions) !== count($requiredPositions)) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please complete every position on your ballot before confirming.']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO ballot_submissions (userID) VALUES (?)');
        $stmt->execute([$userId]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Your vote has been recorded successfully!']);
        exit;
    }

    if ($action === 'export_ballot') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=my_ballot_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Position', 'Voted Candidate', 'Party List']);

        if (!userHasSubmittedBallot($db, $userId)) {
            fputcsv($output, ['No ballot submitted yet.', '', '']);
            fclose($output);
            exit;
        }

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
        $votedPositions = array_column($rows, 'position');

        foreach ($requiredPositions as $position) {
            if (in_array($position, $votedPositions, true)) {
                $row = null;
                foreach ($rows as $r) {
                    if ($r['position'] === $position) {
                        $row = $r;
                        break;
                    }
                }
                if ($row) {
                    fputcsv($output, [
                        $row['position'],
                        $row['candidate_name'],
                        $row['partylist'] ?: 'Independent',
                    ]);
                }
            } else {
                fputcsv($output, [$position, 'No vote', '']);
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
