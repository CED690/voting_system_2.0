/**
 * admin-edit-user.js
 * Load, edit, save, archive student profiles and candidacy extensions.
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
    let currentCandidateInfo = null;

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

    function sectionPrefix(section) {
        return section === candidateSection ? 'cand' : 'stud';
    }

    function field(section, name) {
        return section?.querySelector(`#${sectionPrefix(section)}-${name}`);
    }

    function getActiveSection() {
        return candidateSection?.style.display !== 'none' ? candidateSection : studentSection;
    }

    function showSection(hasCandidate) {
        const isCandidate = !!hasCandidate;
        if (candidateSection) candidateSection.style.display = isCandidate ? 'flex' : 'none';
        if (studentSection)   studentSection.style.display   = isCandidate ? 'none' : 'flex';

        const breadcrumb = isCandidate ? '> Edit User > Candidacy Profile' : '> Edit User > Student';
        document.querySelectorAll('.edit-container .title p').forEach(p => {
            if (p.textContent.includes('Edit User')) p.textContent = breadcrumb;
        });
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
        currentCandidateInfo = cand;

        showSection(!!cand);

        [candidateSection, studentSection].forEach(section => {
            if (!section) return;
            setText(section, '.last-log p', formatDate(user.lastLogin));
            field(section, 'last-name').value = user.lastname ?? '';
            field(section, 'first-name').value = user.firstname ?? '';
            field(section, 'm-i').value = user.mi ?? '';
            field(section, 'suffix').value = user.suffix ?? '';
            field(section, 'email').value = user.email ?? '';
            field(section, 'stud-id').value = user.loginID ?? '';
            field(section, 'program-dd').value = user.program || 'n-a';
            field(section, 'departmant').value = user.department || 'n-a';

            const candidacySelect = field(section, 'candidacy-profile');
            if (candidacySelect) {
                candidacySelect.value = cand ? 'yes' : 'no';
            }

            const img = section.querySelector('.img-container img');
            if (img) {
                const pic = section === candidateSection && cand?.profilePicture
                    ? String(cand.profilePicture).trim()
                    : '';
                const defaultPath = '../../../public/img/478589759275824754.png';
                if (pic) {
                    img.src = '../../../public/' + pic.replace(/^\/+/, '');
                    img.classList.remove('default-profile-img');
                } else {
                    img.src = defaultPath;
                    img.classList.add('default-profile-img');
                }
            }
        });

        if (cand) {
            candidateSection.querySelector('#cand-status').value = (cand.status || 'pending').toLowerCase();
            const posKey = positionMap[cand.position] || 'n-a';
            candidateSection.querySelector('#cand-position').value = posKey;
            candidateSection.querySelector('#cand-party-list').value = cand.partylist || 'n-a';
            candidateSection.querySelector('#cand-cam-platform').value = cand.platform || '';
            renderAchievements();
        } else if (studentSection) {
            field(studentSection, 'status').value = 'active';
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
        const hasCandidacy = field(section, 'candidacy-profile')?.value === 'yes';

        const data = {
            id: parseInt(userId, 10),
            first_name: field(section, 'first-name')?.value.trim(),
            last_name:  field(section, 'last-name')?.value.trim(),
            m_i:        field(section, 'm-i')?.value.trim(),
            suffix:     field(section, 'suffix')?.value.trim(),
            email:      field(section, 'email')?.value.trim(),
            program:    field(section, 'program-dd')?.value,
            department: field(section, 'departmant')?.value,
            role: 'student',
            is_candidate: hasCandidacy
        };

        if (hasCandidacy) {
            const posVal = candidateSection?.querySelector('#cand-position')?.value;
            data.cand_status = candidateSection?.querySelector('#cand-status')?.value;
            data.position  = posVal && posVal !== 'n-a' ? (positionReverse[posVal] || 'President') : 'President';
            data.partylist = candidateSection?.querySelector('#cand-party-list')?.value;
            data.platform  = candidateSection?.querySelector('#cand-cam-platform')?.value.trim();
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
        const title = candidateSection?.querySelector('#cand-achi-exp')?.value.trim();
        const desc  = candidateSection?.querySelector('#cand-achi-exp-desc')?.value.trim();

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
                candidateSection.querySelector('#cand-achi-exp').value = '';
                candidateSection.querySelector('#cand-achi-exp-desc').value = '';
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
        const prefix = sectionPrefix(section);
        const changeBtn = section?.querySelector(`#${prefix}-change-photo`);
        const removeBtn = section?.querySelector(`#${prefix}-remove-pho`);
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

    const documentNames = {
        'good-moral': 'Good Moral Character Certificate',
        'photo': 'Recent 2x2 Photo',
        'student-id': 'Valid Student ID',
        'consent': 'Parent/Guardian Consent',
        'optional': 'Additional Documents'
    };

    const supervisionModal = document.getElementById('supervision-modal');

    function openSupervisionModal() {
        if (!supervisionModal) return;
        renderSupervisionDocs();
        if (currentCandidateInfo) {
            const overallSelect = document.getElementById('overall-candidacy-status');
            if (overallSelect) {
                overallSelect.value = (currentCandidateInfo.status || 'pending').toLowerCase();
            }
        }
        supervisionModal.style.display = 'flex';
    }

    function closeSupervisionModal() {
        if (supervisionModal) {
            supervisionModal.style.display = 'none';
        }
    }

    function renderSupervisionDocs() {
        const list = document.getElementById('supervision-docs-list');
        if (!list || !currentCandidateInfo) return;

        list.innerHTML = '';
        let docs = {};
        if (currentCandidateInfo.documents) {
            try {
                docs = typeof currentCandidateInfo.documents === 'string' ? JSON.parse(currentCandidateInfo.documents) : currentCandidateInfo.documents;
            } catch(e) {
                console.error("Error parsing candidate documents:", e);
            }
        }

        const keys = ['good-moral', 'photo', 'student-id', 'consent', 'optional'];
        let hasAnyDocs = false;

        keys.forEach(key => {
            if (docs && docs[key]) {
                hasAnyDocs = true;
                const doc = docs[key];
                const card = document.createElement('div');
                card.className = 'supervision-card';
                card.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="font-weight: 700; color: #1e293b; font-size: 1rem;">${documentNames[key] || key}</span>
                        <span style="color: #64748b; font-size: 0.875rem; word-break: break-all;">File: ${doc.filename}</span>
                        <div style="margin-top: 0.5rem;">
                            <span class="badge badge-${doc.status || 'pending'}">${doc.status || 'pending'}</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <a href="../../../public/${doc.filepath}" target="_blank" class="supervision-btn supervision-btn-view">View</a>
                        <button class="supervision-btn supervision-btn-approve" data-key="${key}">Approve</button>
                        <button class="supervision-btn supervision-btn-decline" data-key="${key}">Decline</button>
                    </div>
                `;
                list.appendChild(card);
            }
        });

        if (!hasAnyDocs) {
            list.innerHTML = '<div style="text-align: center; padding: 2rem; color: #64748b;">No documents uploaded by this candidate.</div>';
        }

        list.querySelectorAll('.supervision-btn-approve').forEach(btn => {
            btn.addEventListener('click', () => updateDocumentStatus(btn.dataset.key, 'approved'));
        });
        list.querySelectorAll('.supervision-btn-decline').forEach(btn => {
            btn.addEventListener('click', () => updateDocumentStatus(btn.dataset.key, 'declined'));
        });
    }

    async function updateDocumentStatus(docKey, status) {
        try {
            const res = await fetch(`${API_BASE}/edit_user.php?action=update_document_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(userId, 10), doc_key: docKey, status })
            });
            const json = await res.json();
            if (json.success) {
                if (currentCandidateInfo) {
                    let docs = {};
                    if (currentCandidateInfo.documents) {
                        docs = typeof currentCandidateInfo.documents === 'string' ? JSON.parse(currentCandidateInfo.documents) : currentCandidateInfo.documents;
                    }
                    if (docs[docKey]) {
                        docs[docKey].status = status;
                    }
                    currentCandidateInfo.documents = docs;
                }
                renderSupervisionDocs();
            } else {
                alert(json.message || 'Failed to update document status.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    async function saveOverallStatus() {
        const overallSelect = document.getElementById('overall-candidacy-status');
        const status = overallSelect ? overallSelect.value : 'pending';

        try {
            const res = await fetch(`${API_BASE}/edit_user.php?action=update_document_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(userId, 10), doc_key: 'overall', status })
            });
            const json = await res.json();
            if (json.success) {
                alert('Candidacy status saved!');
                if (currentCandidateInfo) {
                    currentCandidateInfo.status = status;
                }
                const mainStatusDropdown = candidateSection.querySelector('#cand-status');
                if (mainStatusDropdown) {
                    mainStatusDropdown.value = status.toLowerCase();
                }
                closeSupervisionModal();
            } else {
                alert(json.message || 'Failed to save candidacy status.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred.');
        }
    }

    function setupViewDocuments() {
        candidateSection?.querySelector('#cand-view-btn')?.addEventListener('click', e => {
            e.preventDefault();
            openSupervisionModal();
        });

        document.getElementById('close-supervision-modal')?.addEventListener('click', closeSupervisionModal);
        document.getElementById('save-overall-status-btn')?.addEventListener('click', saveOverallStatus);
        
        supervisionModal?.addEventListener('click', e => {
            if (e.target === supervisionModal) {
                closeSupervisionModal();
            }
        });
    }

    function bindCandidacyToggle(section) {
        field(section, 'candidacy-profile')?.addEventListener('change', e => {
            showSection(e.target.value === 'yes');
        });
    }

    function bindButtons() {
        [
            '#cand-save-btn', '#cand-save-btn-side', '#stud-save-btn'
        ].forEach(selector => {
            document.querySelector(selector)?.addEventListener('click', e => {
                e.preventDefault();
                saveChanges();
            });
        });

        [
            '#cand-archive-btn', '#cand-archive-btn-side', '#stud-archive-btn'
        ].forEach(selector => {
            document.querySelector(selector)?.addEventListener('click', e => {
                e.preventDefault();
                archiveUser();
            });
        });

        document.querySelectorAll('.btn').forEach(btn => {
            if (btn.textContent.trim() === 'Cancel') {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    window.location.href = 'user-management.html';
                });
            }
        });

        candidateSection?.querySelector('#cand-add-btn')?.addEventListener('click', e => {
            e.preventDefault();
            addAchievement();
        });

        [candidateSection, studentSection].forEach(section => {
            setupPhotoButtons(section);
            bindCandidacyToggle(section);
        });

        setupViewDocuments();
    }

    bindButtons();
    loadUser();
})();
