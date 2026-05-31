<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="../../public/css/style.css">
	<title>University Voting</title>
</head>
	<body>
		<header>
			<div class = "container">
				<div class = "header-content">
					<div class="title">
						<h1>University </h1><h1 class="elec">Election</h1>
					</div>
					<div class = "right-nav">
						<a href="index.php" class="active">Home</a>
						<a href="candidates.html">Candidates</a>
						<button id = "login-btn"><a class="lgn-txt" href="login-signup.php">Login</a></button>
					</div>
				</div>
			</div>
		</header>
		<section class = "hero-section">
			<div class = "container">
				<div class ="hero-container">
					<div class = "hs-text">
						<h1>Your Voice, Your Vote, Your Future</h1>
						<p>Participate in shaping the university's tomorrow. Vote securely for your preferred candidate.</p>
					</div>
					<button>Start Voting</button>
				</div>
			</div>
		</section>
		<section class = "current-standings-sec">
			<div class = "container">
				<div class = "cs-container">
					<div class = "cs-text">
						<h1>Current Standings</h1>
						<p>Real-time standings. Data refreshes every hour.</p>
					</div>
					<div class = "graph-container" id="standings-graphs">
						<div class = "graph"></div>
						<div class = "graph"></div>
					</div>
					<div class = "cs-pagination">
						<ul id="standings-pagination">
						</ul>
					</div>
				</div>
			</div>
		</section>
		<section class = "offer-sec">
			<div class = "container">
				<div class = "os-container">
					<h3>Why Choose Our Voting System?</h3>
					<div class = "card-container">
						<div class = "card">
							<div class="img-container">
								<img src="" alt = "img">
							</div>
							<div class = "card-content">
								<h3>Secure Voting</h3>
								<p>Each student can vote once using their university credentials. Your vote is encrypted and secure.</p>
							</div>
						</div>
						<div class = "card">
							<div class="img-container">
								<img src="" alt = "img">
							</div>
							<div class = "card-content">
								<h3>Candidate Profiles</h3>
								<p>View candidate platforms achievements, and campaign goals before making your decision</p>
							</div>
						</div>
						<div class = "card">
							<div class="img-container">
								<img src="" alt = "img">
							</div>
							<div class = "card-content">
								<h3>Real-time Results</h3>
								<p>Election officers can monitor vote statistics instantly with our live dashboard.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<section class="steps-sec">
			<div class="container">
				<div class="step-container">
					<h3>How to Vote</h3>
					<div class="sc-container">
						<div class="step-card">
							<div class="number">
								<h3>1</h3>
							</div>
							<div class = "step-content">
								<h3>Login</h3>
								<p>Sign in with your student credentials</p>
							</div>
						</div>
						<div class="step-card">
							<div class="number">
								<h3>2</h3>
							</div>
							<div class = "step-content">
								<h3>Choose</h3>
								<p>Review candidates and their platforms</p>
							</div>
						</div>
						<div class="step-card">
							<div class="number">
								<h3>3</h3>
							</div>
							<div class = "step-content">
								<h3>Vote</h3>
								<p>Cast your vote for your preferred candidate</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<script src="../../public/js/landing.js?v=<?= time() ?>"></script>
	</body>
	<footer>
		<div class = "container">
			<p>© 2026 University Election System. All rights reserved.</p>
		</div>
	</footer>
</html>
