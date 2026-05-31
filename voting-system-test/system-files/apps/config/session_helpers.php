<?php

function userHasCandidateProfile(PDO $db, int $userId): bool
{
    $stmt = $db->prepare('SELECT id FROM candidateinfo WHERE userID = ? LIMIT 1');
    $stmt->execute([$userId]);
    return (bool) $stmt->fetchColumn();
}

function syncCandidateSession(PDO $db, int $userId): void
{
    $_SESSION['is_candidate'] = userHasCandidateProfile($db, $userId);
}

function requireStudentSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        header('Location: ../login-signup.php#login');
        exit;
    }
}

function requireStudentCandidateSession(PDO $db): void
{
    requireStudentSession();

    if (empty($_SESSION['is_candidate'])) {
        syncCandidateSession($db, (int) $_SESSION['user_id']);
    }

    if (empty($_SESSION['is_candidate'])) {
        header('Location: ../student/browse.php');
        exit;
    }
}
