<?php
// views/signup.php  (or rename to signup.php inside your pages folder)
// No router needed — form actions point directly to public PHP files.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/style.css">
    <title>Login / Sign Up — University Election</title>
    <style>
        /* ── Panel toggle ── */
        .login-signup-container .panel-hidden {
            display: none !important;
        }
        .login-container,
        .signup-container {
            animation: panelIn .2s ease;
        }
        @keyframes panelIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Status messages ── */
        .field-status {
            display: block;
            font-size: 0.78rem;
            margin-top: 4px;
            min-height: 1.1em;
            font-weight: 500;
        }
        .field-status.loading { color: #888; animation: pulse 1s infinite; }
        .field-status.success { color: #2bb36e; }
        .field-status.error   { color: #e04040; }

        #form-message { margin: 8px 0 4px; font-size: 0.82rem; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }

        input:disabled {
            background: #f0f0f0;
            color: #555;
            cursor: not-allowed;
            opacity: 0.85;
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <div class="header-content">
                <div class="title">
                    <h1>University </h1><h1 class="elec">Election</h1>
                </div>
                <div class="right-nav">
                    <a href="index.php">Home</a>
                    <a href="candidates.html">Candidates</a>
                </div>
            </div>
        </div>
    </header>

    <section class="login-signup-sec">
        <div class="container">
            <div class="login-signup-container">

                <!-- ══════════ LOGIN ══════════ -->
                <div class="login-container">
                    <h1>Login</h1>
                    <form class="login-form" action="../../public/login.php" method="POST">

                        <div class="form-group">
                            <label for="login-credential">Student ID / Email</label>
                            <input
                                type="text"
                                id="login-credential"
                                name="stud_id_email"
                                placeholder="Enter Student ID or Email"
                                autocomplete="username"
                            >
                        </div>

                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <input
                                type="password"
                                id="login-password"
                                name="password"
                                placeholder="Enter Password"
                                autocomplete="current-password"
                                required
                            >
                            <a href="forgot-password.html">Forgot Password?</a>
                        </div>

                        <span id="login-message" class="field-status" aria-live="polite"></span>

                        <div class="btm-prt">
                            <button id = "submit" type="submit">Sign In</button>
                            <div class="no-acc">
                                <p>Don't have an account?</p>
                                <a href="#register" data-switch-panel="signup">Register</a>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- ══════════ SIGN UP ══════════ -->
                <div class="signup-container">
                    <h1>Sign Up</h1>

                    <!-- action points straight to the public endpoint file -->
                    <form class="signup-form" action="../../public/register.php" method="POST" novalidate>

                        <div class="form-group">
                            <input
                                type="text"
                                id="stud-id"
                                name="stud_id"
                                placeholder="Student ID"
                                autocomplete="off"
                                required
                            >
                            <span id="lookup-status" class="field-status" aria-live="polite"></span>
                        </div>

                        <div class="form-group">
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Name (auto-filled)"
                                disabled
                                tabindex="-1"
                            >
                        </div>

                        <div class="form-group">
                            <input
                                type="email"
                                id="email-address"
                                name="email_address"
                                placeholder="Email Address"
                                autocomplete="email"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <input
                                type="password"
                                id="signup-password"
                                name="password"
                                placeholder="Password (min. 8 characters)"
                                autocomplete="new-password"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <input
                                type="password"
                                id="confirm-password"
                                name="confirm_password"
                                placeholder="Confirm Password"
                                autocomplete="new-password"
                                required
                            >
                            <a href="terms.html">Terms of Service</a>
                        </div>

                        <span id="form-message" class="field-status" aria-live="polite"></span>

                        <div class="btm-prt">
                            <button type="submit">Register</button>
                            <div class="no-acc">
                                <p>Already have an account?</p>
                                <a href="#login" data-switch-panel="login">Login</a>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </section>

    <script src="../../public/js/login-signup.js"></script>
</body>
</html>