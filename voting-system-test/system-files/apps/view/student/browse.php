<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login-signup.php#login');
    exit;
}

require_once __DIR__ . '/../../config/session_helpers.php';
require_once __DIR__ . '/../../config/dbconnection.php';

use apps\config\dbconnection;

$db = (new dbconnection())->connect();
syncCandidateSession($db, (int) $_SESSION['user_id']);

$firstname = htmlspecialchars($_SESSION['firstname']);
$isCandidate = !empty($_SESSION['is_candidate']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../public/css/student.css?v=<?= time() ?>">
    <title>Browse</title>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="title">
                    <h1>University </h1><h1 class="elec">Election</h1>
                </div>
                <div class="right-nav">
                    <button id="logout-btn">
                        <a class="lgn-txt" href="../../../public/logout.php">Logout</a>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <section class="hero-section-student">
        <div class="container">
            <div class="hero-stud-container">
                <div class="hero-stud-text">
                    <h1>Welcome, <?= $firstname ?>!</h1>
                    <p>Participate in shaping the university's tomorrow. Vote securely for your preferred candidate.</p>
                    <a href="voting.php" class="hero-vote-btn">Vote</a>
                </div>
                <button id="export-btn">Export Ballot</button>
            </div>
        </div>
    </section>

    <section class="current-standings-sec">
        <div class="container">
            <div class="cs-container">
                <div class="cs-text">
                    <h1>Current Standings</h1>
                    <p>Real-time standings. Data refreshes every hour.</p>
                </div>
                <div class="graph-container" id="standings-graphs"></div>
                <div class="cs-pagination">
                    <ul id="standings-pagination"></ul>
                </div>
            </div>
        </div>
    </section>

    <section class="candidate-highlight-sec">
        <div class="container">
            <div class="cand-high-container">
                <div class="cand-high-title">
                    <h1>Candidate Highlights</h1>
                    <div class="line"></div>
                </div>
                <div class="cand-high-cards" id="highlight-cards"></div>
            </div>
        </div>
    </section>

    <section class="all-candidates-sec">
        <div class="container">
            <div class="all-cand-container">
                <div class="all-cand-title">
                    <h1>All Candidates</h1>
                    <div class="line"></div>
                </div>
                <div class="all-cand-body">
                    <nav>
                        <div class="custom-select">
                            <select name="position" id="position-filter">
                                <option value="">All Positions</option>
                                <option value="president">President</option>
                                <option value="vice-president">Vice President</option>
                                <option value="secretary">Secretary</option>
                                <option value="treasurer">Treasurer</option>
                                <option value="auditor">Auditor</option>
                            </select>
                            <span class="custom-arrow"></span>
                        </div>
                        <h3>No. of Candidates: <span id="cand-count">0</span></h3>
                    </nav>
                    <div class="all-cand-cards" id="all-cand-cards"></div>
                </div>
            </div>
        </div>
    </section>
    <?php $switchPage = 'browse'; include __DIR__ . '/partials/student-switch-btn.php'; ?>
    <script src="../../../public/js/student-common.js?v=<?= time() ?>"></script>
    <script src="../../../public/js/student-browse.js?v=<?= time() ?>"></script>
</body>
</html>