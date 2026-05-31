/**
 * student-voting.js
 * Multi-step voting flow: select candidates, review, confirm.
 */
(() => {
    'use strict';

    const {
        API_BASE, DEFAULT_IMG, POSITION_ORDER,
        candidatePhoto, isDefaultProfilePhoto, fetchJson
    } = StudentCommon;

    const voteSection    = document.querySelector('.vote-section');
    const reviewSection  = document.querySelector('.review-sec');
    const candCardsEl    = document.getElementById('cand-cards');
    const positionTitle  = document.getElementById('position-title');
    const reviewList     = document.getElementById('review-list');
    const confirmBtn     = document.getElementById('confirm-vote-btn');
    const viewModal      = document.querySelector('.prof-modal');
    const closeModalBtn  = document.querySelector('.x-btn');

    const STEP_LABELS = [
        'Presidential Candidate',
        'Vice-Presidential Candidate',
        'Secretarial Candidate',
        'Treasurer Candidate',
        'Auditor Candidate',
        'Review Vote'
    ];

    let allCandidates = [];
    let candidatesByPosition = {};
    let currentStep = 0;
    let selections = {};
    let hasVoted = false;

    async function init() {
        try {
            const [candJson, statusJson] = await Promise.all([
                fetchJson('candidates.php'),
                fetchJson('votes.php?action=status')
            ]);

            if (candJson.success) {
                allCandidates = candJson.data || [];
                candidatesByPosition = {};
                allCandidates.forEach(c => {
                    if (!candidatesByPosition[c.position]) candidatesByPosition[c.position] = [];
                    candidatesByPosition[c.position].push(c);
                });
            }

            if (statusJson.success && statusJson.has_voted) {
                hasVoted = true;
                selections = {};
                Object.entries(statusJson.selections || {}).forEach(([pos, data]) => {
                    selections[pos] = {
                        id: data.candidate_id,
                        fullname: data.candidate_name,
                        partylist: data.partylist,
                        profilePicture: data.profilePicture,
                        position: pos
                    };
                });
                (statusJson.abstained || []).forEach(pos => {
                    selections[pos] = null;
                });
                showAlreadyVoted();
                return;
            }

            renderStep();
        } catch (err) {
            console.error('Error initializing voting:', err);
        }
    }

    function showAlreadyVoted() {
        if (voteSection) voteSection.style.display = 'none';
        if (reviewSection) {
            reviewSection.style.display = 'flex';
            renderReview();
            if (confirmBtn) {
                confirmBtn.textContent = 'Vote Already Submitted';
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = '0.6';
            }
        }
    }

    function getCurrentPosition() {
        return POSITION_ORDER[currentStep] || null;
    }

    function updateProgress() {
        document.querySelectorAll('.num-prog').forEach(prog => {
            prog.querySelectorAll('.prog, .prog-current-prog').forEach((el, i) => {
                el.className = i === currentStep ? 'prog-current-prog' : 'prog';
            });
        });
    }

    function renderStep() {
        updateProgress();

        if (currentStep >= POSITION_ORDER.length) {
            if (voteSection) voteSection.style.display = 'none';
            if (reviewSection) reviewSection.style.display = 'flex';
            renderReview();
            return;
        }

        if (voteSection) voteSection.style.display = 'flex';
        if (reviewSection) reviewSection.style.display = 'none';

        const position = getCurrentPosition();
        if (positionTitle) positionTitle.textContent = position;

        const candidates = candidatesByPosition[position] || [];

        if (!candCardsEl) return;

        const skipHint = document.getElementById('vote-skip-hint');
        if (skipHint) {
            skipHint.textContent = selections[position] === null
                ? `You chose not to vote for ${position}. Select a candidate or click NEXT to continue.`
                : `You may select a candidate, or click NEXT to skip voting for ${position}.`;
        }

        if (candidates.length === 0) {
            candCardsEl.innerHTML = `<p style="padding:2rem;color:#888;">No approved candidates for ${position}. Click NEXT if you do not wish to vote for this position.</p>`;
            return;
        }

        candCardsEl.innerHTML = candidates.map(c => {
            const isSelected = selections[position]?.id === c.id;
            return `
                <div class="c-card ${isSelected ? 'voted' : ''}" data-candidate-id="${c.id}">
                    <div class="img-container">
                        <img src="${candidatePhoto(c.profilePicture)}" alt="img" class="${isDefaultProfilePhoto(c.profilePicture) ? 'default-profile-img' : ''}">
                    </div>
                    <div class="c-body">
                        <h1>${c.fullname}</h1>
                        <div class="details">
                            <h3>${c.position}</h3>
                            <p>${c.partylist || 'Independent'}</p>
                        </div>
                    </div>
                    <div class="buttons">
                        <button class="view-profile" data-id="${c.id}">View Profile</button>
                        <button class="vt-btn">${isSelected ? 'Unvote' : 'Vote'}</button>
                    </div>
                </div>`;
        }).join('');

        bindCardEvents();
    }

    function bindCardEvents() {
        candCardsEl.querySelectorAll('.vt-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const card = this.closest('.c-card');
                const candidateId = parseInt(card.dataset.candidateId, 10);
                const position = getCurrentPosition();
                const candidate = (candidatesByPosition[position] || []).find(c => c.id === candidateId);
                if (!candidate) return;

                if (card.classList.contains('voted')) {
                    delete selections[position];
                    card.classList.remove('voted');
                    this.textContent = 'Vote';
                } else {
                    candCardsEl.querySelectorAll('.c-card').forEach(c => {
                        c.classList.remove('voted');
                        c.querySelector('.vt-btn').textContent = 'Vote';
                    });
                    selections[position] = candidate;
                    card.classList.add('voted');
                    this.textContent = 'Unvote';
                }
            });
        });

        candCardsEl.querySelectorAll('.view-profile').forEach(btn => {
            btn.addEventListener('click', () => openProfile(parseInt(btn.dataset.id, 10)));
        });
    }

    async function openProfile(candidateId) {
        try {
            const json = await fetchJson(`candidates.php?action=get&id=${candidateId}`);
            if (!json.success) return;

            const c = json.data.candidate;
            const achievements = json.data.achievements || [];

            viewModal.querySelector('.prof-info h1').textContent = `${c.firstname} ${c.lastname}`;
            viewModal.querySelector('.prof-info .details h3').textContent = c.position;
            viewModal.querySelector('.prof-info .details p').textContent = c.partylist || 'Independent';
            const modalImg = viewModal.querySelector('.top-container .left img');
            modalImg.src = candidatePhoto(c.profilePicture);
            modalImg.classList.toggle('default-profile-img', isDefaultProfilePhoto(c.profilePicture));
            viewModal.querySelector('.plat p').textContent = c.platform || 'No platform provided.';

            const achList = viewModal.querySelector('.achi-exp ul');
            achList.innerHTML = achievements.length
                ? achievements.map(a => `<li><h3>${a.achievement}</h3><p>${a.description || ''}</p></li>`).join('')
                : '<li><p style="color:#888;">No achievements listed.</p></li>';

            viewModal.style.display = 'flex';
        } catch (err) {
            console.error(err);
        }
    }

    function renderReview() {
        if (!reviewList) return;

        reviewList.innerHTML = POSITION_ORDER.map(pos => {
            const sel = selections[pos];
            const isAbstain = sel === null;
            const hasVote = sel && sel.id;
            const img = hasVote ? candidatePhoto(sel.profilePicture) : DEFAULT_IMG;
            const imgClass = hasVote && !isDefaultProfilePhoto(sel.profilePicture) ? '' : 'default-profile-img';
            let name = '— Not selected —';
            if (isAbstain) name = 'No vote';
            else if (hasVote) name = sel.fullname;
            return `
                <li class="${isAbstain ? 'review-abstain' : ''}">
                    <h3>${pos}</h3>
                    <div class="details">
                        <img src="${img}" alt="" class="${imgClass}">
                        <p>${name}</p>
                    </div>
                    <div class="img">
                        ${sel !== undefined ? `<button class="edit-vote-btn" data-step="${POSITION_ORDER.indexOf(pos)}" title="Change">✎</button>` : ''}
                    </div>
                </li>`;
        }).join('');

        reviewList.querySelectorAll('.edit-vote-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentStep = parseInt(btn.dataset.step, 10);
                renderStep();
            });
        });
    }

    function goNext() {
        if (currentStep >= POSITION_ORDER.length) {
            return;
        }

        const position = getCurrentPosition();
        const choice = selections[position];

        if (choice === undefined) {
            const skipMsg = `You did not select a candidate for ${position}.\n\nYou will not vote for this position.\n\nContinue?`;
            if (!confirm(skipMsg)) {
                return;
            }
            selections[position] = null;
        }

        currentStep++;
        renderStep();
    }

    function goPrev() {
        if (currentStep > 0) {
            currentStep--;
            renderStep();
        } else {
            window.location.href = 'browse.php';
        }
    }

    document.querySelectorAll('#next').forEach(btn => btn.addEventListener('click', goNext));
    document.querySelectorAll('#previous').forEach(btn => btn.addEventListener('click', goPrev));

    closeModalBtn?.addEventListener('click', () => {
        viewModal.style.display = 'none';
    });

    viewModal?.addEventListener('click', e => {
        if (e.target === viewModal) viewModal.style.display = 'none';
    });

    confirmBtn?.addEventListener('click', async () => {
        if (hasVoted) return;

        const undecided = POSITION_ORDER.filter(p => selections[p] === undefined);
        if (undecided.length) {
            alert('Please review every position before confirming. Use PREV to go back to any you have not finished.');
            currentStep = POSITION_ORDER.indexOf(undecided[0]);
            renderStep();
            return;
        }

        const abstained = POSITION_ORDER.filter(p => selections[p] === null);
        let confirmMsg = 'Confirm your final ballot? This action cannot be undone.';
        if (abstained.length) {
            confirmMsg += `\n\nYou are not voting for: ${abstained.join(', ')}.`;
        }
        if (!confirm(confirmMsg)) return;

        try {
            const votes = POSITION_ORDER.map(pos => ({
                position: pos,
                candidate_id: selections[pos]?.id ?? null
            }));

            const res = await fetch(`${API_BASE}/votes.php?action=submit`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ votes })
            });
            const json = await res.json();

            if (json.success) {
                hasVoted = true;
                alert(json.message || 'Vote submitted successfully!');
                window.location.href = 'browse.php';
            } else {
                alert(json.message || 'Failed to submit vote.');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while submitting your vote.');
        }
    });

    init();
})();
