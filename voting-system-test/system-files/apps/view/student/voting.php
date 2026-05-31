<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login-signup.php#login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../../../public/css/student.css?v=<?= time() ?>">
	<title>Position Voting</title>
</head>
	<body>
		<header>
			<div class = "container">
				<div class = "header-content">
					<div class="title">
						<h1>University </h1><h1 class="elec">Election</h1>
					</div>
					<div class = "right-nav">
						<a href="browse.php" style="color: white; font-weight: 500; text-decoration: none; display: flex; align-items: center; gap: 8px;">Back to Home</a>
					</div>
				</div>
			</div>
		</header>
		<section class = "vote-section">
			<div class = "container">
				<div class ="vote-container">
					<div class = "vote-title">
						<h1>Cast Your Vote</h1>
						<div class = "line"></div>
					</div>
					<div class = "choices-container">
						<div class = "cho-title">
							<h3 id="position-title">President</h3>
							<div class = "line"></div>
						</div>
						<div class="cand-cards" id="cand-cards"></div>
					</div>
					<div class = "progression">
						<button id = "previous">PREV</button>
							<div class="num-prog">
								<div class = "prog-current-prog">
									<div class = "num"><p>1</p></div>
									<p>Presidential Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>2</p></div>
									<p>Vice-Presidential Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>3</p></div>
									<p>Secretarial Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>4</p></div>
									<p>Treasurer Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>5</p></div>
									<p>Auditor Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>6</p></div>
									<p>Review Vote</p>
								</div>
						</div>
						<button id = "next">NEXT</button>
					</div>
				</div>
			</div>
		</section>
		<section class="review-sec">
			<div class="container">
				<div class="rev-container">
					<div class = "rev-title">
						<h1>Review Your Final Vote</h1>
						<p>Participate in shaping the university's tomorrow. Vote securely for your preferred candidate.</p>
					</div>
					<div class = "table-container">
						<div class = "table-title">
							<h3>Position</h3>
							<h3>Selected Candidate</h3>
							<h3>Action</h3>
						</div>
						<div class="rev-table">
							<div class="body">
								<ul id="review-list"></ul>
							</div>
							<div class="button">
								<button id="confirm-vote-btn">Confirm Vote</button>
							</div>
						</div>
					</div>
					<div class = "progression">
						<button id = "previous">PREV</button>
							<div class="num-prog">
								<div class = "prog">
									<div class = "num"><p>1</p></div>
									<p>Presidential Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>2</p></div>
									<p>Vice-Presidential Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>3</p></div>
									<p>Secretarial Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>4</p></div>
									<p>Treasurer Candidate</p>
								</div>
								<div class = "prog">
									<div class = "num"><p>5</p></div>
									<p>Auditor Candidate</p>
								</div>
								<div class = "prog-current-prog">
									<div class = "num"><p>6</p></div>
									<p>Review Vote</p>
								</div>
						</div>
						<button id = "next">NEXT</button>
					</div>
				</div>
			</div>
		</section>
		<section class = "prof-modal">
			<div class = "container">
				<div class = "modal-container">
					<div class = "top-container">
						<div class = "left">
							<img src = "../../../public/img/478589759275824754.png" alt = "profile-img">
							<div class = "prof-info">
								<h1>Candidate name</h1>
								<div class = "details">
									<h3>Position</h3>
									<p>Party-list</p>
								</div>
							</div>
						</div>
						<img src = "../../../public/img/icons/i-close.svg" alt = "X" class = "x-btn">
					</div>
					<div class = "bottom-container">
						<div class = "plat">
							<h3>Campaign Platform</h3>
							<p></p>
						</div>
						<div class = "achi-exp">
							<h3>Achievement & Experience</h3>
							<ul></ul>
						</div>
					</div>
				</div>
			</div>
		</section>
		<script src="../../../public/js/student-common.js?v=<?= time() ?>"></script>
		<script src="../../../public/js/student-voting.js?v=<?= time() ?>"></script>
	</body>
</html>
