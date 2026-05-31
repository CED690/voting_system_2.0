/**
 * candidates.js
 * Public candidates page: highlights, candidate list, filtering.
 */
(() => {
    'use strict';

    const API = '../../public/api/student/candidates.php';
    const { candidatePhoto, isDefaultProfilePhoto } = window.StudentCommon || {};

    const POSITION_SLUG_REVERSE = {
        'president': 'President',
        'vice-president': 'Vice President',
        'secretary': 'Secretary',
        'treasurer': 'Treasurer',
        'auditor': 'Auditor'
    };

    const highlightCards = document.getElementById('highlight-cards');
    const allCandCards   = document.getElementById('all-cand-cards');
    const candCountEl    = document.getElementById('cand-count');
    const positionFilter = document.getElementById('position-filter');

    let allCandidates = [];

    async function loadData() {
        try {
            const res = await fetch(`${API}?v=${Date.now()}`);
            const json = await res.json();
            if (!json.success) return;

            allCandidates = json.data || [];
            renderHighlights();
            renderAllCandidates();
        } catch (err) {
            console.error('Error loading candidates page:', err);
        }
    }

    function renderHighlights() {
        if (!highlightCards) return;

        // Sort by vote count descending
        const sorted = [...allCandidates].sort((a, b) => parseInt(b.vote_count, 10) - parseInt(a.vote_count, 10));
        const top = sorted.slice(0, 2);

        if (top.length === 0) {
            highlightCards.innerHTML = '<p style="color:#888;padding:1rem;">No approved candidates yet.</p>';
            return;
        }

        highlightCards.innerHTML = top.map(c => {
            const tags = (c.achievements || []).slice(0, 3).map(a =>
                `<li><p>${a.achievement}</p></li>`
            ).join('') || '<li><p>No achievements listed</p></li>';

            const photo = candidatePhoto(c.profilePicture);
            const defaultClass = isDefaultProfilePhoto(c.profilePicture) ? 'default-profile-img' : '';

            return `
                <div class="ch-card">
                    <div class="head-card">
                        <div class="prof">
                            <img src="${photo}" alt="img" class="${defaultClass}">
                            <div class="details"><h1>${c.fullname}</h1><h3>${c.position}</h3></div>
                        </div>
                        <div class="tags"><ul>${tags}</ul></div>
                    </div>
                    <div class="body-card">
                        <div class="details">
                            <h3>${c.partylist || 'Independent'}</h3>
                            <p>${(c.platform || 'No platform provided.').substring(0, 120)}${(c.platform || '').length > 120 ? '...' : ''}</p>
                        </div>
                        <a href="view-candidate-profile.html?id=${c.id}"><button>View Profile</button></a>
                    </div>
                </div>`;
        }).join('');
    }

    function renderAllCandidates() {
        if (!allCandCards) return;

        const filterVal = positionFilter?.value || '';
        const filtered = filterVal
            ? allCandidates.filter(c => c.position === POSITION_SLUG_REVERSE[filterVal])
            : allCandidates;

        if (candCountEl) candCountEl.textContent = filtered.length;

        if (filtered.length === 0) {
            allCandCards.innerHTML = '<p style="color:#888;padding:1rem;">No candidates match this filter.</p>';
            return;
        }

        allCandCards.innerHTML = filtered.map(c => {
            const photo = candidatePhoto(c.profilePicture);
            const defaultClass = isDefaultProfilePhoto(c.profilePicture) ? 'default-profile-img' : '';
            return `
                <div class="ac-card">
                    <div class="prof">
                        <img src="${photo}" alt="img" class="${defaultClass}">
                        <div class="details"><h1>${c.fullname}</h1><h3>${c.position}</h3></div>
                    </div>
                    <a href="view-candidate-profile.html?id=${c.id}"><button>View Profile</button></a>
                </div>`;
        }).join('');
    }

    positionFilter?.addEventListener('change', renderAllCandidates);

    loadData();
})();
