/**
 * student-common.js
 * Shared helpers for student pages.
 */
(() => {
    'use strict';

    const API_BASE = window.STUDENT_API_BASE || '../../../public/api/student';
    const DEFAULT_IMG = window.STUDENT_DEFAULT_IMG || '../../../public/img/478589759275824754.png';

    const POSITION_ORDER = [
        'President',
        'Vice President',
        'Secretary',
        'Treasurer',
        'Auditor'
    ];

    const POSITION_SLUG = {
        'President': 'president',
        'Vice President': 'vice-president',
        'Secretary': 'secretary',
        'Treasurer': 'treasurer',
        'Auditor': 'auditor'
    };

    const POSITION_SLUG_REVERSE = Object.fromEntries(
        Object.entries(POSITION_SLUG).map(([k, v]) => [v, k])
    );

    window.StudentCommon = {
        API_BASE,
        DEFAULT_IMG,
        POSITION_ORDER,
        POSITION_SLUG,
        POSITION_SLUG_REVERSE,

        candidatePhoto(storedPath) {
            if (storedPath == null || String(storedPath).trim() === '') {
                return DEFAULT_IMG;
            }
            const path = String(storedPath).trim();
            if (/^https?:\/\//i.test(path)) {
                return path;
            }
            const publicBase = DEFAULT_IMG.replace(/img\/[^/]+$/, '');
            return publicBase + path.replace(/^\/+/, '');
        },

        isDefaultProfilePhoto(storedPath) {
            return storedPath == null || String(storedPath).trim() === '';
        },

        async fetchJson(endpoint, options = {}) {
            const res = await fetch(`${API_BASE}/${endpoint}`, options);
            return res.json();
        },

        triggerDownload(url) {
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    };
})();
