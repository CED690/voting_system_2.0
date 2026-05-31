/**
 * admin-tools.js
 * Audit log display and CSV export buttons.
 */
(() => {
    'use strict';

    const { API_BASE } = AdminCommon;

    const auditTableBody = document.getElementById('audit-table-body');
    const exportAuditBtn = document.getElementById('export-audit-btn');
    const exportResultBtn = document.getElementById('export-result-btn');

    async function loadAuditLog() {
        if (!auditTableBody) return;

        try {
            const res = await fetch(`${API_BASE}/tools.php?action=list_audit`);
            const json = await res.json();

            auditTableBody.innerHTML = '';

            if (!json.success || !json.data?.length) {
                auditTableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;padding:1.5rem;color:#888;">No audit entries found.</td></tr>`;
                return;
            }

            json.data.forEach(entry => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${entry.timestamp}</td>
                    <td>${entry.user}</td>
                    <td>${entry.action}</td>
                `;
                auditTableBody.appendChild(tr);
            });
        } catch (err) {
            console.error('Error loading audit log:', err);
            auditTableBody.innerHTML = `<tr><td colspan="3" style="text-align:center;padding:1.5rem;color:#888;">Failed to load audit log.</td></tr>`;
        }
    }

    function triggerDownload(url) {
        const a = document.createElement('a');
        a.href = url;
        a.download = '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    exportAuditBtn?.addEventListener('click', () => {
        triggerDownload(`${API_BASE}/tools.php?action=export_audit`);
    });

    exportResultBtn?.addEventListener('click', () => {
        triggerDownload(`${API_BASE}/tools.php?action=export_result`);
    });

    loadAuditLog();
})();
