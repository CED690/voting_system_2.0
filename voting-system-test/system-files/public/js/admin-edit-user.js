/**
 * admin-edit-user.js
 * Load, edit, save, archive candidate/student profiles.
 */
(() => {
    'use strict';

    const { API_BASE, formatDate, departmentLabel } = AdminCommon;

    const params = new URLSearchParams(window.location.search);
    const userId = params.get('id');

    const candidateSection = document.querySelector('.edit-profile-candidate');
    const studentSection   = document.querySelector('.edit-profile-student');

    let currentUser = null;
    let achievements = [];

    const positionMap = {
        'President': 'president',
        'Vice President': 'vice-president',
        'Secretary': 'secretary',
        'Treasurer': 'treasurer',
        'Auditor': 'auditor'
    };

    const positionReverse = Object.fromEntries(
        Object.entries(positionMap).map(([k, v]) => [v, k])
    );

    function getActiveSection() {
        return candidateSection?.style.display !== 'none' ? candidateSection : studentSection;
    }

    function showSection(role) {
        const isCandidate = role === 'candidate';
        if (candidateSection) candidateSection.style.display = isCandidate ? 'flex' : 'none';
        if (studentSection)   studentSection.style.display   = isCandidate ? 'none' : 'flex';

        const breadcrumb = isCandidate ? '> Edit User > Candidate' : '> Edit User > Student';
        document.querySelectorAll('.edit-container .title p').forEach(p => {
            if (p.textContent.includes('Edit User')) p.textContent = breadcrumb;
        });
    }

    function setVal(section, selector, value) {
        const el = section?.querySelector(selector);
        if (el) el.value = value ?? '';
    }

    function setText(section, selector, value) {
        const el = section?.querySelector(selector);
        if (el) el.textContent = value ?? '—';
    }

    function populateForm(data) {
        const user = data.user;
        const cand = data.candidateinfo;
        achievements = data.achievements || [];
        currentUser = user;

        showSection(user.roles);

        [candidateSection, studentSection].forEach(section => {
            if (!section) return;
            setText(section, '.last-log p', formatDate(user.lastLogin));
            setVal(section, '#last-name', user.lastname);
            setVal(section, '#first-name', user.firstname);
            setVal(section, '#m-i', user.mi);
            setVal(section, '#suffix', user.suffix);
            setVal(section, '#email', user.email);
            setVal(section, '#stud-id', user.loginID);
            setVal(section, '#program-dd', user.program || 'n-a');
            setVal(section, '#departmant', user.department || 'n-a');
            setVal(section, '#role', user.roles);

            if (cand?.profilePicture) {
                const img = section.querySelector('.img-container img');
                if (img) img.src = cand.profilePicture;
            }
        });

        if (user.roles === 'candidate' && cand) {
            setVal(candidateSection, '#cand-status', (cand.status || 'pending').toLowerCase());
            const posKey = positionMap[cand.position] || 'n-a';
            setVal(candidateSection, '#position', posKey);
            setVal(candidateSection, '#party-list', cand.partylist || 'n-a');
            setVal(candidateSection, '#cam-platform', cand.platform || '');
            renderAchievements();
        } else if (studentSection) {
            setVal(studentSection, '#status', 'active');
        }
    }

    function renderAchievements() {
        const list = candidateSection?.querySelector('.current-achi-exp ul');
        if (!list) return;

        list.innerHTML = '';
        if (!achievements.length) {
            list.innerHTML = '<li style="color:#888;padding:1rem;">No achievements added yet.</li>';
            return;
        }

        achievements.forEach(a => {
            const li = document.createElement('li');
            li.innerHTML = `
                <h3>${a.achievement}</h3>
                <p>${a.description || ''}</p>
                <button class="remove-achi-btn" data-id="${a.id}">Remove</button>
            `;
            list.appendChild(li);
        });

        list.querySelectorAll('.remove-achi-btn').forEach(btn => {
            btn.addEventListener('click', () => removeAchievement(parseInt(btn.dataset.id, 10)));
        });
    }

    async function loadUser() {
        if (!userId) {
            alert('No user ID provided. Redirecting to User Management.');
            window.location.href = 'user-management.html';
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/edit_user.php?action=get&id=${userId}`);
            const json = await res.json();
            if (json.success) {
                populateForm(json.data);
            } else {
                alert(json.message || 'User not found.');
                window.location.href = 'user-management.html';
            }
        } catch (err) {
            console.error(err);
            alert('Failed to load user data.');
        }
    }

    function collectFormData() {
        const section = getActiveSection();
        const role = section?.querySelector('#role')?.value || 'student';

        const data = {
            id: parseInt(userId, 10),
            first_name: section?.querySelector('#first-name')?.value.trim(),
            last_name:  section?.querySelector('#last-name')?.value.trim(),
            m_i:        section?.querySelector('#m-i')?.value.trim(),
            suffix:     section?.querySelector('#suffix')?.value.trim(),
            email:      section?.querySelector('#email')?.value.trim(),
            program:    section?.querySelector('#program-dd')?.value,
            department: section?.querySelector('#departmant')?.value,
            role
        };

        if (role === 'candidate') {
            const posVal = candidateSection?.querySelector('#position')?.value;
            data.cand_status = candidateSection?.querySelector('#cand-status')?.value;
            data.position  = posVal && posVal !== 'n-a' ? (positionReverse[posVal] || 'President') : 'President';
            data.partylist = candidateSection?.querySelector('#party-list')?.value;
            data.platform  = candidateSection?.querySelector('#cam-platform')?.value.trim();
            if (data.partylist === 'n-a') data.partylist = '';
        }

        return data;
    }

    async function saveChanges() {
        const data = collectFormData();
        if (!data.first_name || !data.last_name || !data.email) {
            alert('Please fill in all required fields.');
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/edit_user.php?action=save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            if (json.success) {
                alert('Changes saved successfully!');
                await loadUser();
            } else {
                alert(json.message || 'Failed to save changes.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while saving.');
        }
    }

    async function archiveUser() {
        if (!confirm('Archive this user? They will be permanently removed from the system.')) return;

        try {
            const res = await fetch(`${API_BASE}/users.php?action=archive`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: [parseInt(userId, 10)] })
            });
            const json = await res.json();
            if (json.success) {
                alert('User archived successfully.');
                window.location.href = 'user-management.html';
            } else {
                alert(json.message || 'Failed to archive user.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    async function addAchievement() {
        const title = candidateSection?.querySelector('#achi-exp')?.value.trim();
        const desc  = candidateSection?.querySelector('#achi-exp-desc')?.value.trim();

        if (!title) {
            alert('Please enter an achievement title.');
            return;
        }

        try {
            const res = await fetch(`${API_BASE}/edit_user.php?action=add_achievement`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(userId, 10), title, desc })
            });
            const json = await res.json();
            if (json.success) {
                candidateSection.querySelector('#achi-exp').value = '';
                candidateSection.querySelector('#achi-exp-desc').value = '';
                await loadUser();
            } else {
                alert(json.message || 'Failed to add achievement.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    async function removeAchievement(achievementId) {
        if (!confirm('Remove this achievement?')) return;

        try {
            const res = await fetch(`${API_BASE}/edit_user.php?action=remove_achievement`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ achievement_id: achievementId })
            });
            const json = await res.json();
            if (json.success) {
                await loadUser();
            } else {
                alert(json.message || 'Failed to remove achievement.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    function setupPhotoButtons(section) {
        const changeBtn = section?.querySelector('#change-photo');
        const removeBtn = section?.querySelector('#remove-pho');
        const img       = section?.querySelector('.img-container img');

        let fileInput = section?.querySelector('.photo-file-input');
        if (!fileInput && section) {
            fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.className = 'photo-file-input';
            fileInput.style.display = 'none';
            section.appendChild(fileInput);
        }

        changeBtn?.addEventListener('click', () => fileInput?.click());

        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (file && img) {
                img.src = URL.createObjectURL(file);
            }
        });

        removeBtn?.addEventListener('click', () => {
            if (img) img.src = '../../../public/img/478589759275824754.png';
            if (fileInput) fileInput.value = '';
        });
    }

    function setupViewDocuments() {
        candidateSection?.querySelector('#view-btn')?.addEventListener('click', e => {
            e.preventDefault();
            alert('Required documents:\n\n• Good Moral Character Certificate\n• Recent 2x2 Photo\n• Valid Student ID\n• Parent/Guardian Consent (if under 18)\n\nDocuments are submitted by the candidate via the Candidacy Requirements page.');
        });
    }

    function bindButtons() {
        document.querySelectorAll('#save-btn').forEach(btn => {
            btn.addEventListener('click', e => { e.preventDefault(); saveChanges(); });
        });

        document.querySelectorAll('#archive-btn').forEach(btn => {
            btn.addEventListener('click', e => { e.preventDefault(); archiveUser(); });
        });

        document.querySelectorAll('.btn').forEach(btn => {
            if (btn.textContent.trim() === 'Cancel') {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    window.location.href = 'user-management.html';
                });
            }
        });

        candidateSection?.querySelector('#add-btn')?.addEventListener('click', e => {
            e.preventDefault();
            addAchievement();
        });

        [candidateSection, studentSection].forEach(section => {
            setupPhotoButtons(section);
            section?.querySelector('#role')?.addEventListener('change', e => {
                showSection(e.target.value);
            });
        });

        setupViewDocuments();
    }

    bindButtons();
    loadUser();
})();
