/**
 * signup.js
 * 1. Login / Sign-up panel toggle
 * 2. Login form AJAX submit → redirect by role
 * 3. Student-ID auto-fetch (debounced) → fills Name, Program, Department
 * 4. Signup form AJAX submit
 */

(() => {
    'use strict';

    // ── Panel toggle ─────────────────────────────────────────────────────────
    const loginPanel  = document.querySelector('.login-container');
    const signupPanel = document.querySelector('.signup-container');

    document.querySelectorAll('[data-switch-panel]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            showPanel(link.dataset.switchPanel);
        });
    });

    function showPanel(which) {
        const toLogin = which === 'login';
        loginPanel?.classList.toggle('panel-hidden', !toLogin);
        signupPanel?.classList.toggle('panel-hidden',  toLogin);
        history.replaceState(null, '', toLogin ? '#login' : '#register');
    }

    showPanel(location.hash === '#register' ? 'signup' : 'login');

    // ── Helpers ──────────────────────────────────────────────────────────────
    function setStatus(el, text, type) {
        if (!el) {
            // Failsafe: If you forgot to add the ID to your HTML, alert the error instead of failing silently
            if (type === 'error') alert("Error: " + text);
            return;
        }
        el.textContent = text;
        el.className   = 'field-status ' + (type ?? '');
    }
    
    function clearStatus(el) { setStatus(el, '', ''); }

    // ── Login form ───────────────────────────────────────────────────────────
    const loginForm    = document.querySelector('.login-form');
    const loginBtn     = loginForm?.querySelector('button[type="submit"]');
    const loginMsg     = document.getElementById('login-message');

    loginForm?.addEventListener('submit', async e => {
        e.preventDefault();
        clearStatus(loginMsg);

        const credential = loginForm.querySelector('#login-credential')?.value.trim() ?? '';
        const password   = loginForm.querySelector('#login-password')?.value ?? '';

        if (!credential || !password) {
            setStatus(loginMsg, 'Please fill in all fields.', 'error');
            return;
        }

        loginBtn.disabled    = true;
        loginBtn.textContent = 'Signing in…';

        try {
            const res  = await fetch('../../public/login.php', {
                method: 'POST',
                body:   new FormData(loginForm),
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json' // Forces PHP to return JSON, preventing redirect loops
                },
            });
            
            // Check if PHP threw a fatal HTML error before trying to parse JSON
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const rawText = await res.text();
                console.error("Server Error (Not JSON):", rawText);
                throw new Error("Server did not return valid JSON. Check the console.");
            }

            const json = await res.json();

            if (json.success) {
                setStatus(loginMsg, 'Success! Redirecting…', 'success');
                window.location.href = json.redirect;
            } else {
                setStatus(loginMsg, json.message ?? 'Login failed.', 'error');
            }
        } catch (err) {
            console.error("Fetch Error:", err);
            setStatus(loginMsg, 'Network error or server crash. Check console.', 'error');
        } finally {
            loginBtn.disabled    = false;
            loginBtn.textContent = 'Sign In';
        }
    });

    // ── Signup — student ID auto-fetch ───────────────────────────────────────
    const studIdInput  = document.getElementById('stud-id');
    const nameInput    = document.getElementById('name');
    const programInput = document.getElementById('program');
    const deptInput    = document.getElementById('department');
    const signupForm   = document.querySelector('.signup-form');
    const signupBtn    = signupForm?.querySelector('button[type="submit"]');
    const lookupStatus = document.getElementById('lookup-status');
    const formMsg      = document.getElementById('form-message');

    function resetAutoFields() {
        [nameInput, programInput, deptInput].forEach(el => {
            if (!el) return;
            el.value    = '';
            el.disabled = true;
        });
    }

    let timer = null;

    studIdInput?.addEventListener('input', () => {
        clearTimeout(timer);
        const id = studIdInput.value.trim();

        if (id.length < 3) { resetAutoFields(); clearStatus(lookupStatus); return; }

        setStatus(lookupStatus, 'Looking up student…', 'loading');

        timer = setTimeout(async () => {
            try {
                const res  = await fetch('../../public/api/fetch_student.php?schoolId=' + encodeURIComponent(id), {
                    headers: { 'Accept': 'application/json' }
                });
                
                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) throw new Error("Not JSON");

                const json = await res.json();

                if (!json.success) {
                    resetAutoFields();
                    setStatus(lookupStatus, json.message, 'error');
                    return;
                }

                if (nameInput)    { nameInput.value    = json.data.name;       nameInput.disabled    = true; }
                if (programInput) { programInput.value = json.data.program;    programInput.disabled = true; }
                if (deptInput)    { deptInput.value    = json.data.department; deptInput.disabled    = true; }

                setStatus(lookupStatus, '✓ Student found!', 'success');

            } catch (err) {
                console.error("Lookup Error:", err);
                resetAutoFields();
                setStatus(lookupStatus, 'Network error. Please try again.', 'error');
            }
        }, 500);
    });

    // ── Signup form submit ────────────────────────────────────────────────────
    signupForm?.addEventListener('submit', async e => {
        e.preventDefault();
        clearStatus(formMsg);

        const password = document.getElementById('signup-password')?.value ?? '';
        const confirm  = document.getElementById('confirm-password')?.value  ?? '';

        if (password.length < 8) { setStatus(formMsg, 'Password must be at least 8 characters.', 'error'); return; }
        if (password !== confirm) { setStatus(formMsg, 'Passwords do not match.', 'error'); return; }

        signupBtn.disabled    = true;
        signupBtn.textContent = 'Registering…';

        try {
            const res  = await fetch('../../public/register.php', { 
                method: 'POST', 
                body: new FormData(signupForm),
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const rawText = await res.text();
                console.error("Server Error (Not JSON):", rawText);
                throw new Error("Server did not return valid JSON. Check the console.");
            }

            const json = await res.json();

            if (json.success) {
                setStatus(formMsg, json.message, 'success');
                signupForm.reset();
                resetAutoFields();
                clearStatus(lookupStatus);
                setTimeout(() => showPanel('login'), 2000);
            } else {
                setStatus(formMsg, json.message ?? 'Registration failed.', 'error');
            }
        } catch (err) {
            console.error("Registration Error:", err);
            setStatus(formMsg, 'Network error or server crash. Check console.', 'error');
        } finally {
            signupBtn.disabled    = false;
            signupBtn.textContent = 'Register';
        }
    });
})();