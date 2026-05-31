<?php
/**
 * Floating bottom-left switch: Browse ↔ My Candidacy (candidates only).
 *
 * Expected vars:
 *   $switchPage  — 'browse' | 'vote' | 'candidacy'
 *   $isCandidate — bool
 */
$switchPage  = $switchPage ?? 'browse';
$isCandidate = !empty($isCandidate);

if ($switchPage === 'candidacy' || $switchPage === 'vote') {
    $switchHref  = $switchPage === 'candidacy'
        ? '../student/browse.php'
        : 'browse.php';
    $switchLabel = 'Browse';
} elseif ($isCandidate) {
    $switchHref  = '../candidate/candidate-dashboard.php';
    $switchLabel = 'My Candidacy';
} else {
    return;
}
?>
<a href="<?= htmlspecialchars($switchHref) ?>" class="student-switch-btn"><?= htmlspecialchars($switchLabel) ?></a>
