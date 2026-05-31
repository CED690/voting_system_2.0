/**
 * admin-user-management.js
 * User list, search, filter, archive, disable, and tab switching.
 */
(() => {
    'use strict';

    const { API_BASE, formatDate, departmentLabel, loadAnalyticsCards } = AdminCommon;

    let allUsers = [];
    let currentView = 'users'; // 'users' | 'archive'

    const roleFilter   = document.getElementById('role-filter');
    const searchInput  = document.getElementById('search-user');
    const searchForm   = document.getElementById('search-form');
    const tableBody    = document.getElementById('user-table-body');
    const selectAllCb  = document.getElementById('select-all-cb');
    const usersOptBtn  = document.getElementById('users-opt-btn');
    const archiveOptBtn = document.getElementById('archive-opt-btn');
    const bulkArchiveBtn = document.getElementById('archive-btn');
    const bulkDisableBtn = document.getElementById('disabled-btn');

    async function loadUsers() {
        const role = roleFilter?.value || 'all';
        const search = searchInput?.value.trim() || '';

        try {
            const params = new URLSearchParams({ action: 'list', role, search });
            const res = await fetch(`${API_BASE}/users.php?${params}`);
            const json = await res.json();

            if (json.success) {
                allUsers = (json.data || []).filter(u => u.roles !== 'admin');
                renderTable();
            }
        } catch (err) {
            console.error('Error loading users:', err);
        }
    }

    function getFilteredUsers() {
        if (currentView === 'archive') {
            return allUsers.filter(u =>
                Number(u.is_candidate) === 1 &&
                ['rejected', 'disabled', 'pending'].includes((u.candidate_status || '').toLowerCase())
            );
        }
        return allUsers.filter(u =>
            Number(u.is_candidate) !== 1 ||
            !['rejected', 'disabled'].includes((u.candidate_status || '').toLowerCase())
        );
    }

    function statusLabel(user) {
        if (Number(user.is_candidate) === 1) {
            const s = (user.candidate_status || 'pending').toLowerCase();
            return s.charAt(0).toUpperCase() + s.slice(1);
        }
        return 'Active';
    }

    function statusClass(user) {
        const label = statusLabel(user).toLowerCase();
        if (label === 'approved' || label === 'active') return 'approved';
        if (label === 'disabled' || label === 'rejected') return 'disabled';
        return 'pending';
    }

    function accountTypeLabel(user) {
        if (Number(user.is_candidate) === 1) {
            return 'Student · Candidacy';
        }
        return user.roles.charAt(0).toUpperCase() + user.roles.slice(1);
    }

    function renderTable() {
        if (!tableBody) return;

        const users = getFilteredUsers();
        tableBody.innerHTML = '';

        if (users.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:2rem;color:#888;">No users found.</td></tr>`;
            return;
        }

        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.dataset.userId = user.id;
            tr.innerHTML = `
                <td class="cb"><input type="checkbox" class="row-cb" value="${user.id}"></td>
                <td><p>${user.loginID || '—'}</p></td>
                <td><p>${user.firstname} ${user.lastname}</p></td>
                <td><p>${departmentLabel(user.department)}</p></td>
                <td><p>${user.email || '—'}</p></td>
                <td><p>${accountTypeLabel(user)}</p></td>
                <td><p>${formatDate(user.createdAt)}</p></td>
                <td><p class="${statusClass(user)}">${statusLabel(user)}</p></td>
                <td><div class="actions-btns">
                    <img class="action-archive" src="../../../public/img/icons/i-archive2.png" alt="Archive" title="Archive" style="cursor:pointer">
                    <a href="edit-user.html?id=${user.id}"><img src="../../../public/img/icons/i-edit.png" alt="Edit" title="Edit"></a>
                    <img class="action-disable" src="../../../public/img/icons/i-disabled2.png" alt="Disable" title="Disable" style="cursor:pointer">
                </div></td>
            `;
            tableBody.appendChild(tr);
        });

        bindRowActions();
    }

    function getSelectedIds() {
        return [...document.querySelectorAll('.row-cb:checked')].map(cb => parseInt(cb.value, 10));
    }

    async function archiveUsers(ids) {
        if (!ids.length) {
            alert('Please select at least one user.');
            return;
        }
        if (!confirm(`Archive ${ids.length} user(s)? This will permanently remove them from the system.`)) return;

        try {
            const res = await fetch(`${API_BASE}/users.php?action=archive`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
            const json = await res.json();
            if (json.success) {
                await loadUsers();
                await loadAnalyticsCards();
            } else {
                alert(json.message || 'Failed to archive users.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while archiving users.');
        }
    }

    async function disableUser(id) {
        try {
            const res = await fetch(`${API_BASE}/users.php?action=toggle_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: 'disabled' })
            });
            const json = await res.json();
            if (json.success) {
                await loadUsers();
            } else {
                alert(json.message || 'Could not disable user.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    function bindRowActions() {
        document.querySelectorAll('.action-archive').forEach(btn => {
            btn.addEventListener('click', e => {
                const row = e.target.closest('tr');
                const id = parseInt(row.dataset.userId, 10);
                archiveUsers([id]);
            });
        });

        document.querySelectorAll('.action-disable').forEach(btn => {
            btn.addEventListener('click', e => {
                const row = e.target.closest('tr');
                const id = parseInt(row.dataset.userId, 10);
                disableUser(id);
            });
        });
    }

    function switchTab(view) {
        currentView = view;
        usersOptBtn?.classList.toggle('active', view === 'users');
        usersOptBtn?.classList.toggle('inactive', view !== 'users');
        archiveOptBtn?.classList.toggle('active', view === 'archive');
        archiveOptBtn?.classList.toggle('inactive', view !== 'archive');
        renderTable();
    }

    selectAllCb?.addEventListener('change', () => {
        document.querySelectorAll('.row-cb').forEach(cb => {
            cb.checked = selectAllCb.checked;
        });
    });

    roleFilter?.addEventListener('change', loadUsers);

    searchForm?.addEventListener('submit', e => {
        e.preventDefault();
        loadUsers();
    });

    bulkArchiveBtn?.addEventListener('click', () => archiveUsers(getSelectedIds()));
    bulkDisableBtn?.addEventListener('click', () => {
        const ids = getSelectedIds();
        if (!ids.length) {
            alert('Please select at least one user.');
            return;
        }
        ids.forEach(id => disableUser(id));
    });

    usersOptBtn?.addEventListener('click', () => switchTab('users'));
    archiveOptBtn?.addEventListener('click', () => switchTab('archive'));

    loadAnalyticsCards();
    loadUsers();
})();
