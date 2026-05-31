/**
 * admin-common.js
 * Shared helpers for admin pages.
 */
(() => {
    'use strict';

    const API_BASE = '../../../public/api/admin';

    window.AdminCommon = {
        API_BASE,

        logout() {
            window.location.href = '../../../public/logout.php';
        },

        formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        },

        departmentLabel(code) {
            const map = {
                CCST: 'College of Computer Science and Technology',
                COE: 'College of Engineering',
                CAS: 'College of Arts and Science',
                'n-a': 'N/A'
            };
            return map[code] || code || '—';
        },

        async loadAnalyticsCards() {
            try {
                const res = await fetch(`${API_BASE}/analytics.php`);
                const json = await res.json();
                if (!json.success) return;

                const data = json.data;
                const set = (sel, val) => {
                    const el = document.querySelector(sel);
                    if (el) el.textContent = val;
                };

                set('#total-stud h1', data.total_students);
                set('#total-cad h1', data.total_candidates);
                set('#vote-turnout h1', data.voter_turnout + '%');
                set('#vote-cast h1', data.votes_cast);
            } catch (err) {
                console.error('Error loading analytics:', err);
            }
        },

        async apiFetch(endpoint, options = {}) {
            const res = await fetch(`${API_BASE}/${endpoint}`, options);
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return res.json();
            }
            return res;
        }
    };

    document.getElementById('logout-btn')?.addEventListener('click', () => {
        AdminCommon.logout();
    });
})();
