<?php
// views/signup.php  (or rename to signup.php inside your pages folder)
// No router needed — form actions point directly to public PHP files.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/style.css?v=<?= time() ?>">
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
                            <div class="password-field">
                                <input
                                    type="password"
                                    id="login-password"
                                    name="password"
                                    placeholder="Enter Password"
                                    autocomplete="current-password"
                                    required
                                >
                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-target="login-password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
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
                            <div class="password-field">
                                <input
                                    type="password"
                                    id="signup-password"
                                    name="password"
                                    placeholder="Password (min. 8 characters)"
                                    autocomplete="new-password"
                                    required
                                >
                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-target="signup-password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="password-field">
                                <input
                                    type="password"
                                    id="confirm-password"
                                    name="confirm_password"
                                    placeholder="Confirm Password"
                                    autocomplete="new-password"
                                    required
                                >
                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-target="confirm-password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
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

    <script src="../../public/js/login-signup.js?v=<?= time() ?>"></script>
</body>
</html>