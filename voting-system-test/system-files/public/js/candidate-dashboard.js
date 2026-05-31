/**
 * candidate-dashboard.js
 */
(() => {
    'use strict';

    const API = '../../../public/api/candidate/profile.php';
    const DEFAULT_IMG = '../../../public/img/478589759275824754.png';

    const fields = {
        position: document.querySelector('.title .pos'),
        program:  document.querySelector('.info li:nth-child(4) p'),
        dept:     document.querySelector('.info li:nth-child(5) p'),
        posInfo:  document.querySelector('.info li:nth-child(6) p'),
        party:    document.querySelector('.info li:nth-child(7) p'),
        status:   document.querySelector('.info li:nth-child(8) h5'),
        profileImg: document.querySelector('.profile-pic .img-container img'),
    };

    const modal = document.getElementById('edit-profile-modal');
    const modalProfileImg = document.getElementById('modal-profile-img');
    const modalFileInput = document.getElementById('modal-file-input');
    const modalPartylist = document.getElementById('modal-partylist');
    const modalPlatform = document.getElementById('modal-platform');
    const modalAchievementTitle = document.getElementById('modal-achievement-title');
    const modalAchievementDesc = document.getElementById('modal-achievement-desc');
    const modalAchievementsList = document.getElementById('modal-achievements-list');

    let profileData = null;
    let currentAchievements = [];
    let isPhotoRemoved = false;

    function statusLabel(status) {
        if (!status) return 'Pending';
        return status.charAt(0).toUpperCase() + status.slice(1);
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
            if (fields.profileImg) {
                fields.profileImg.src = profileData.profilePicture || DEFAULT_IMG;
            }
        } catch (err) {
            console.error('Failed to load candidate profile:', err);
        }
    }

    function openModal() {
        if (!profileData || !modal) return;

        // Reset values
        modalProfileImg.src = profileData.profilePicture || DEFAULT_IMG;
        modalPartylist.value = profileData.partylist || '';
        modalPlatform.value = profileData.platform || '';
        modalFileInput.value = '';
        isPhotoRemoved = false;

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
        formData.append('remove_photo', isPhotoRemoved);

        const file = modalFileInput.files?.[0];
        if (file) {
            formData.append('profile_photo', file);
        }

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

    // Modal Control Bindings
    document.getElementById('edit-btn')?.addEventListener('click', openModal);
    document.getElementById('edit-profile-action')?.addEventListener('click', openModal);
    document.getElementById('cancel-btn')?.addEventListener('click', closeModal);
    document.getElementById('modal-add-achievement-btn')?.addEventListener('click', addAchievement);
    document.getElementById('save-btn')?.addEventListener('click', saveProfileChanges);

    // Photo Button Bindings
    document.getElementById('change-photo-btn')?.addEventListener('click', () => modalFileInput?.click());
    
    modalFileInput?.addEventListener('change', () => {
        const file = modalFileInput.files?.[0];
        if (file && modalProfileImg) {
            modalProfileImg.src = URL.createObjectURL(file);
            isPhotoRemoved = false;
        }
    });

    document.getElementById('remove-pho')?.addEventListener('click', () => {
        if (modalProfileImg) modalProfileImg.src = DEFAULT_IMG;
        modalFileInput.value = '';
        isPhotoRemoved = true;
    });

    // Card tab switching for Edit Profile Modal
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
