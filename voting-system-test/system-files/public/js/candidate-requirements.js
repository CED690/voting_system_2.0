/**
 * candidate-requirements.js
 */
(() => {
    'use strict';

    const requiredDocs = ['good-moral', 'photo', 'student-id'];
    const uploaded = {};

    document.querySelectorAll('.form-group').forEach(group => {
        const docKey = group.dataset.doc;
        const btn = group.querySelector('.file-btn');
        const input = group.querySelector('.file-input');
        const status = group.querySelector('.doc-status');

        btn?.addEventListener('click', () => input?.click());

        input?.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;
            uploaded[docKey] = file.name;
            if (status) {
                status.textContent = '✓ ' + file.name;
                status.style.color = '#2bb36e';
            }
            btn.textContent = 'Change file';
        });
    });

    document.getElementById('submit-btn')?.addEventListener('click', () => {
        const missing = requiredDocs.filter(key => !uploaded[key]);
        if (missing.length) {
            alert('Please upload all required documents before submitting.');
            return;
        }
        alert('Requirements submitted successfully! The election committee will review your documents.');
        window.location.href = 'candidate-dashboard.php';
    });
})();
