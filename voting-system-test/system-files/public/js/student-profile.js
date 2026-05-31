/**
 * student-profile.js
 * Load candidate profile page by ?id= query param.
 */
(() => {
    'use strict';

    const { fetchJson, candidatePhoto } = StudentCommon;

    const params = new URLSearchParams(window.location.search);
    const candidateId = params.get('id');

    async function loadProfile() {
        if (!candidateId) {
            document.querySelector('.prof-details h3').textContent = 'Candidate not found';
            return;
        }

        try {
            const json = await fetchJson(`candidates.php?action=get&id=${candidateId}`);
            if (!json.success) {
                document.querySelector('.prof-details h3').textContent = 'Candidate not found';
                return;
            }

            const c = json.data.candidate;
            const achievements = json.data.achievements || [];

            document.querySelector('.prof-img img').src = candidatePhoto(c.profilePicture);
            document.querySelector('.position-tag').textContent = c.position;
            document.querySelector('.prof-details h3').textContent = `${c.firstname} ${c.lastname}`;
            document.querySelector('.prof-details > p').textContent = c.partylist || 'Independent';
            document.querySelector('.camp-plat p').textContent = c.platform || 'No platform provided.';

            const achList = document.querySelector('.achi-exp ul');
            achList.innerHTML = achievements.length
                ? achievements.map(a => `
                    <li>
                        <h3>${a.achievement}</h3>
                        <p>${a.description || ''}</p>
                    </li>`).join('')
                : '<li><p style="color:#888;">No achievements listed.</p></li>';

            // Dynamically handle back button if referred from public pages
            const backBtn = document.querySelector('a.back');
            if (backBtn && document.referrer && (document.referrer.includes('candidates.html') || document.referrer.includes('index.php'))) {
                backBtn.addEventListener('click', e => {
                    e.preventDefault();
                    window.history.back();
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    loadProfile();
})();
