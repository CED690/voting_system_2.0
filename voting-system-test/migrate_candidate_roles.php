<?php
/**
 * migrate_candidate_roles.php
 * One-time helper: converts legacy users.roles = 'candidate' to 'student'.
 * Candidacy data stays in candidateinfo.
 * Run: http://localhost/voting-system-test/migrate_candidate_roles.php
 */
require_once __DIR__ . '/system-files/apps/config/dbconnection.php';

use apps\config\dbconnection;

header('Content-Type: text/html; charset=utf-8');

try {
    $db = (new dbconnection())->connect();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE roles = 'candidate'");
    $count = (int) $stmt->fetchColumn();

    if ($count === 0) {
        echo "<div style='font-family:sans-serif;padding:20px;max-width:640px;margin:50px auto;background:#cce5ff;color:#004085;border:1px solid #b8daff;border-radius:8px;'>";
        echo '<h2>No Migration Needed</h2><p>All accounts already use the student login model. Candidates are identified by their <strong>candidateinfo</strong> profile.</p></div>';
        exit;
    }

    $db->exec("UPDATE users SET roles = 'student' WHERE roles = 'candidate'");

    echo "<div style='font-family:sans-serif;padding:20px;max-width:640px;margin:50px auto;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;'>";
    echo "<h2>Migration Complete</h2>";
    echo "<p>Updated <strong>{$count}</strong> account(s) from <code>candidate</code> role to <code>student</code>.</p>";
    echo '<p>Candidates can now log in as students, cast votes, and open <strong>My Candidacy</strong> from the browse page.</p>';
    echo '<p><a href="system-files/apps/view/login-signup.php#login">Go to Login</a></p></div>';
} catch (PDOException $e) {
    echo "<div style='font-family:sans-serif;padding:20px;max-width:640px;margin:50px auto;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:8px;'>";
    echo '<h2>Database Error</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>';
}
