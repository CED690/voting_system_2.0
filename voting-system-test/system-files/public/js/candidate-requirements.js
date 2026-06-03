/**
 * candidate-requirements.js
 */
(() => {
    'use strict';

    const API = '../../../public/api/candidate/profile.php';
    const requiredDocs = ['good-moral', 'photo', 'student-id'];
    const uploaded = {};

    // Load existing documents
    async function loadRequirements() {
        try {
            const res = await fetch(API);
            const json = await res.json();
            if (!json.success || !json.data.profile) return;

            const profile = json.data.profile;
            let docs = {};
            if (profile.documents) {
                try {
                    docs = typeof profile.documents === 'string' ? JSON.parse(profile.documents) : profile.documents;
                } catch(e) {
                    console.error("Error parsing documents JSON:", e);
                }
            }

            document.querySelectorAll('.form-group').forEach(group => {
                const docKey = group.dataset.doc;
                const btn = group.querySelector('.file-btn');
                const status = group.querySelector('.doc-status');

                if (docs && docs[docKey]) {
                    const doc = docs[docKey];
                    uploaded[docKey] = doc.filename; // mark as uploaded

                    if (status) {
                        let statusText = '';
                        let color = '#888';
                        if (doc.status === 'pending') {
                            statusText = `⏳ Pending review: ${doc.filename}`;
                            color = '#e0a800'; // Amber/Yellow
                        } else if (doc.status === 'approved') {
                            statusText = `✓ Approved: ${doc.filename}`;
                            color = '#2bb36e'; // Green
                        } else if (doc.status === 'rejected' || doc.status === 'declined') {
                            statusText = `✗ Declined: ${doc.filename}`;
                            color = '#d9534f'; // Red
                        } else {
                            statusText = `✓ Submitted: ${doc.filename}`;
                            color = '#2bb36e';
                        }
                        status.textContent = statusText;
                        status.style.color = color;
                    }
                    if (btn) btn.textContent = 'Change file';
                }
            });
        } catch (err) {
            console.error('Failed to load candidate requirements:', err);
        }
    }

    document.querySelectorAll('.form-group').forEach(group => {
        const docKey = group.dataset.doc;
        const btn = group.querySelector('.file-btn');
        const input = group.querySelector('.file-input');
        const status = group.querySelector('.doc-status');

        btn?.addEventListener('click', () => input?.click());

        input?.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file) return;
            uploaded[docKey] = file; // Save the actual File object
            if (status) {
                status.textContent = 'Selected: ' + file.name;
                status.style.color = '#1954B2';
            }
            btn.textContent = 'Change file';
        });
    });

    document.getElementById('submit-btn')?.addEventListener('click', async () => {
        const missing = requiredDocs.filter(key => !uploaded[key]);
        if (missing.length) {
            alert('Please select/upload all required documents before submitting.');
            return;
        }

        const formData = new FormData();
        let hasNewFiles = false;

        for (const key in uploaded) {
            if (uploaded[key] instanceof File) {
                formData.append(key, uploaded[key]);
                hasNewFiles = true;
            }
        }

        if (!hasNewFiles) {
            alert('No new documents were selected. Redirecting to Candidate Dashboard.');
            window.location.href = 'candidate-dashboard.php';
            return;
        }

        try {
            const btnEl = document.getElementById('submit-btn');
            btnEl.disabled = true;
            btnEl.textContent = 'Uploading...';
            const res = await fetch(`${API}?action=submit_requirements`, {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.success) {
                alert('Requirements submitted successfully! The election committee will review your documents.');
                window.location.href = 'candidate-dashboard.php';
            } else {
                alert(json.message || 'Failed to submit requirements.');
                btnEl.disabled = false;
                btnEl.textContent = 'Submit Requirements';
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred during submission.');
            const btnEl = document.getElementById('submit-btn');
            btnEl.disabled = false;
            btnEl.textContent = 'Submit Requirements';
        }
    });

    loadRequirements();
})();
