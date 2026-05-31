<?php
session_start();

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header('Location: ../login-signup.php#login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidacy Requirements</title>
	<link rel="stylesheet" href="../../../public/css/candidate.css?v=<?= time() ?>">
</head>
    <body>
        <header>
			<div class = "container">
				<div class = "header-content">
					<div class="title">
						<h1>University </h1><h1 class="elec">Election</h1>
					</div>
					<div class = "right-nav" style="display: flex; gap: 12px; align-items: center;">
						<a href="candidate-dashboard.php" class="lgn-txt" style="color: white; font-weight: 500; text-decoration: none;">Dashboard</a>
						<button id = "logout-btn"><a class="lgn-txt" href="../../../public/logout.php">Logout</a></button>
					</div>
				</div>
			</div>
		</header>
        <section class="requirement-sec">
            <div class="container">
                <div class="req-container">
                    <div class="title">
                        <h3>Candidacy Requirements</h3>
                        <p>Please submit all required documents to complete your candidacy application</p>
                    </div>
                    <div class="note">
                        <h3>Important Notes!!!</h3>
                        <ul>
                            <li>All requirements must be submitted before your candidacy can be approved</li>
                            <li>Original documents must be presented to the election committee for verification</li>
                            <li>Submitting false information may result in disqualification</li>
                            <li>For questions, contact the student council or election committee</li>
                        </ul>
                    </div>
                    <div class="document">
                        <h3>Required Documents</h3>
                        <form id="requirements-form">
                            <div class = "form-group" data-doc="good-moral">
                                <div class="main">
                                    <img src="../../../public/img/icons/i-document.png" alt="">
                                    <div class="text">
                                        <div class="title">
                                            <h3>Good Moral Character Certificate</h3>
                                            <p class="doc-status">Required</p>
                                        </div>
                                        <div class="desc">
                                            <p>Official certificate from the registrar's office proving good standing</p>
                                        </div>
                                    </div>
                                </div>
								<button type="button" class="btn file-btn">Add file</button>
								<input type="file" class="file-input" hidden accept=".pdf,.jpg,.jpeg,.png">
							</div>
                            <div class = "form-group" data-doc="photo">
                                <div class="main">
                                    <img src="../../../public/img/icons/i-profile.png" alt="">
                                    <div class="text">
                                        <div class="title">
                                            <h3>Recent 2x2 Photo</h3>
                                            <p class="doc-status">Required</p>
                                        </div>
                                        <div class="desc">
                                            <p>Recent formal photo with white background</p>
                                        </div>
                                    </div>
                                </div>
								<button type="button" class="btn file-btn">Add file</button>
								<input type="file" class="file-input" hidden accept=".jpg,.jpeg,.png">
							</div>
                            <div class = "form-group" data-doc="student-id">
                                <div class="main">
                                    <img src="../../../public/img/icons/i-ID.png" alt="">
                                    <div class="text">
                                        <div class="title">
                                            <h3>Valid Student ID</h3>
                                            <p class="doc-status">Required</p>
                                        </div>
                                        <div class="desc">
                                            <p>Copy of current valid school ID</p>
                                        </div>
                                    </div>
                                </div>
								<button type="button" class="btn file-btn">Add file</button>
								<input type="file" class="file-input" hidden accept=".pdf,.jpg,.jpeg,.png">
							</div>
                            <div class = "form-group" data-doc="consent">
                                <div class="main">
                                    <img src="../../../public/img/icons/i-profile.png" alt="">
                                    <div class="text">
                                        <div class="title">
                                            <h3>Parent/Guardian Consent</h3>
                                            <p class="doc-status">Required (if under 18)</p>
                                        </div>
                                        <div class="desc">
                                            <p>Signed consent form (for candidates under 18 years old)</p>
                                        </div>
                                    </div>
                                </div>
								<button type="button" class="btn file-btn">Add file</button>
								<input type="file" class="file-input" hidden accept=".pdf,.jpg,.jpeg,.png">
							</div>
                            <div class = "form-group" data-doc="optional">
                                <div class="main">
                                    <img src="../../../public/img/icons/i-profile.png" alt="">
                                    <div class="text">
                                        <div class="title">
                                            <h3>Additional Documents (Optional)</h3>
                                            <p class="doc-status">Optional</p>
                                        </div>
                                        <div class="desc">
                                            <p>Any supporting documents like certifications, achievements, etc.</p>
                                        </div>
                                    </div>
                                </div>
								<button type="button" class="btn file-btn">Add file</button>
								<input type="file" class="file-input" hidden accept=".pdf,.jpg,.jpeg,.png">
							</div>
                        </form>
                        <button type="button" id="submit-btn">Submit Requirements</button>
                    </div>
                </div>
            </div>
        </section>
        <script src="../../../public/js/candidate-requirements.js?v=<?= time() ?>"></script>
    </body>
</html>
