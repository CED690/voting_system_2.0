/**
 * candidate-dashboard.js
 */
(() => {
    'use strict';

    const API = '../../../public/api/candidate/profile.php';
    const { candidatePhoto, isDefaultProfilePhoto } = window.StudentCommon || {};

    const fields = {
        position: document.querySelector('.title .pos'),
        program:  document.querySelector('.info li:nth-child(4) p'),
        dept:     document.querySelector('.info li:nth-child(5) p'),
        posInfo:  document.querySelector('.info li:nth-child(6) p'),
        party:    document.querySelector('.info li:nth-child(7) p'),
        status:   document.querySelector('.info li:nth-child(8) h5'),
    };

    const profileImg = document.getElementById('candidate-profile-img');
    const profilePhotoInput = document.getElementById('profile-photo-input');
    const changeProfilePhotoBtn = document.getElementById('change-profile-photo');
    const removeProfilePhotoBtn = document.getElementById('remove-profile-photo');

    const modal = document.getElementById('edit-profile-modal');
    const modalPartylist = document.getElementById('modal-partylist');
    const modalPlatform = document.getElementById('modal-platform');
    const modalAchievementTitle = document.getElementById('modal-achievement-title');
    const modalAchievementDesc = document.getElementById('modal-achievement-desc');
    const modalAchievementsList = document.getElementById('modal-achievements-list');

    let profileData = null;
    let currentAchievements = [];

    function statusLabel(status) {
        if (!status) return 'Pending';
        return status.charAt(0).toUpperCase() + status.slice(1);
    }

    function updateProfileImage(storedPath) {
        if (!profileImg || !candidatePhoto) return;
        profileImg.src = candidatePhoto(storedPath);
        profileImg.classList.toggle('default-profile-img', isDefaultProfilePhoto(storedPath));
    }

    async function loadProfile() {
        try {
            const res = await fetch(API);
            const json = await res.json();
            if (!json.success) return;

            profileData = json.data.profile;
            currentAchievements = json.data.achievements || [];

            if (fields.position) fields.position.textContent = profileData.position || '—';
            if (fields.program)  fields.program.textContent  = profileData.program || '—';
            if (fields.dept)     fields.dept.textContent      = profileData.department || '—';
            if (fields.posInfo)  fields.posInfo.textContent   = profileData.position || '—';
            if (fields.party)    fields.party.textContent     = profileData.partylist || 'Independent';
            if (fields.status)   fields.status.textContent    = statusLabel(profileData.status);

            updateProfileImage(profileData.profilePicture);
        } catch (err) {
            console.error('Failed to load candidate profile:', err);
        }
    }

    async function uploadProfilePhoto(file) {
        const formData = new FormData();
        formData.append('photo', file);

        const res = await fetch(`${API}?action=upload_photo`, {
            method: 'POST',
            body: formData,
        });
        const json = await res.json();
        if (!json.success) {
            throw new Error(json.message || 'Failed to upload profile photo.');
        }
        return json;
    }

    async function removeProfilePhoto() {
        const res = await fetch(`${API}?action=remove_photo`, { method: 'POST' });
        const json = await res.json();
        if (!json.success) {
            throw new Error(json.message || 'Failed to remove profile photo.');
        }
        return json;
    }

    function openModal() {
        if (!profileData || !modal) return;

        modalPartylist.value = profileData.partylist || '';
        modalPlatform.value = profileData.platform || '';

        renderModalAchievements();
        modal.style.display = 'flex';
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    function renderModalAchievements() {
        if (!modalAchievementsList) return;
        modalAchievementsList.innerHTML = '';

        if (currentAchievements.length === 0) {
            modalAchievementsList.innerHTML = '<li style="color:#888;padding:0.5rem 1rem;">No achievements added.</li>';
            return;
        }

        currentAchievements.forEach(ach => {
            const li = document.createElement('li');
            li.innerHTML = `
                <h3>${ach.achievement}</h3>
                <p>${ach.description || ''}</p>
                <button type="button" data-id="${ach.id}" class="remove-ach-btn">Remove</button>
            `;
            modalAchievementsList.appendChild(li);
        });

        modalAchievementsList.querySelectorAll('.remove-ach-btn').forEach(btn => {
            btn.addEventListener('click', () => removeAchievement(parseInt(btn.dataset.id, 10)));
        });
    }

    async function addAchievement() {
        const title = modalAchievementTitle.value.trim();
        const desc = modalAchievementDesc.value.trim();

        if (!title) {
            alert('Please enter an achievement title.');
            return;
        }

        try {
            const res = await fetch(`${API}?action=add_achievement`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, desc })
            });
            const json = await res.json();
            if (json.success) {
                modalAchievementTitle.value = '';
                modalAchievementDesc.value = '';
                await loadProfile();
                renderModalAchievements();
            } else {
                alert(json.message || 'Failed to add achievement.');
            }
        } catch (err) {
            console.error(err);
            alert('Error adding achievement.');
        }
    }

    async function removeAchievement(achievementId) {
        if (!confirm('Are you sure you want to remove this achievement?')) return;

        try {
            const res = await fetch(`${API}?action=remove_achievement`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ achievement_id: achievementId })
            });
            const json = await res.json();
            if (json.success) {
                await loadProfile();
                renderModalAchievements();
            } else {
                alert(json.message || 'Failed to remove achievement.');
            }
        } catch (err) {
            console.error(err);
            alert('Error removing achievement.');
        }
    }

    async function saveProfileChanges() {
        const formData = new FormData();
        formData.append('partylist', modalPartylist.value.trim());
        formData.append('platform', modalPlatform.value.trim());

        try {
            const res = await fetch(`${API}?action=save`, {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.success) {
                alert('Profile updated successfully!');
                await loadProfile();
                closeModal();
            } else {
                alert(json.message || 'Failed to save profile changes.');
            }
        } catch (err) {
            console.error(err);
            alert('Error saving profile changes.');
        }
    }

    changeProfilePhotoBtn?.addEventListener('click', () => profilePhotoInput?.click());

    profilePhotoInput?.addEventListener('change', async () => {
        const file = profilePhotoInput.files?.[0];
        profilePhotoInput.value = '';
        if (!file) return;

        try {
            const json = await uploadProfilePhoto(file);
            profileData.profilePicture = json.profilePicture;
            updateProfileImage(json.profilePicture);
            alert('Profile photo updated.');
        } catch (err) {
            console.error(err);
            alert(err.message || 'Error uploading profile photo.');
        }
    });

    removeProfilePhotoBtn?.addEventListener('click', async () => {
        if (!profileData?.profilePicture) {
            return;
        }
        if (!confirm('Remove your photo and use the default placeholder?')) {
            return;
        }

        try {
            await removeProfilePhoto();
            profileData.profilePicture = null;
            updateProfileImage(null);
            alert('Profile photo reset to default.');
        } catch (err) {
            console.error(err);
            alert(err.message || 'Error removing profile photo.');
        }
    });

    document.getElementById('edit-btn')?.addEventListener('click', openModal);
    document.getElementById('edit-profile-action')?.addEventListener('click', openModal);
    document.getElementById('cancel-btn')?.addEventListener('click', closeModal);
    document.getElementById('modal-add-achievement-btn')?.addEventListener('click', addAchievement);
    document.getElementById('save-btn')?.addEventListener('click', saveProfileChanges);

    const leftCard = modal?.querySelector('.left');
    const rightCard = modal?.querySelector('.right');

    rightCard?.addEventListener('click', () => {
        if (!rightCard.classList.contains('active')) {
            leftCard?.classList.add('deactive');
            rightCard?.classList.add('active');
        }
    });

    leftCard?.addEventListener('click', () => {
        if (leftCard.classList.contains('deactive')) {
            leftCard?.classList.remove('deactive');
            rightCard?.classList.remove('active');
        }
    });

    loadProfile();
})();
