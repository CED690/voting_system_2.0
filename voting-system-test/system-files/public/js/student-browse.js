/**
 * student-browse.js
 * Browse page: standings, highlights, candidate list, filters, pagination, export.
 */
(() => {
    'use strict';

    const {
        API_BASE, DEFAULT_IMG, POSITION_ORDER, POSITION_SLUG, POSITION_SLUG_REVERSE,
        candidatePhoto, isDefaultProfilePhoto, fetchJson, triggerDownload
    } = StudentCommon;

    const graphContainer   = document.getElementById('standings-graphs');
    const paginationEl     = document.getElementById('standings-pagination');
    const highlightCards   = document.getElementById('highlight-cards');
    const allCandCards     = document.getElementById('all-cand-cards');
    const candCountEl      = document.getElementById('cand-count');
    const positionFilter   = document.getElementById('position-filter');
    const exportBtn        = document.getElementById('export-btn');

    let standingsByPosition = {};
    let allCandidates = [];
    let currentPage = 0;

    const positionKeys = POSITION_ORDER;
    const pagesCount = Math.ceil(positionKeys.length / 2) || 1;

    async function loadData() {
        try {
            const json = await fetchJson(`candidates.php?action=standings&v=${Date.now()}`);
            if (!json.success) return;

            allCandidates = json.data.candidates || [];
            standingsByPosition = json.data.by_position || {};
            renderStandings();
            renderHighlights();
            renderAllCandidates();
        } catch (err) {
            console.error('Error loading browse data:', err);
        }
    }

    function renderStandingsGraph(position) {
        const candidates = standingsByPosition[position] || [];
        const maxVotes = Math.max(...candidates.map(c => parseInt(c.vote_count, 10)), 1);

        let barsHtml = '';
        if (candidates.length === 0) {
            barsHtml = '<p style="color:#888;padding:1.5rem;font-family:var(--ff);text-align:center;">No candidates for this position.</p>';
        } else {
            candidates.forEach(c => {
                const pct = Math.round((parseInt(c.vote_count, 10) / maxVotes) * 100);
                const partylist = c.partylist ? c.partylist : 'Independent';
                barsHtml += `
                    <div style="margin-bottom:16px; transition: transform 0.2s ease;" onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='translateX(0)'">
                        <div style="display:flex;justify-content:space-between;font-size:0.85em;margin-bottom:6px;font-family:var(--ff);color:#475569;">
                            <span style="font-weight:600;color:#0f172a;">${c.fullname} <span style="font-size:0.85em;font-weight:500;color:#64748b;margin-left:4px;">(${partylist})</span></span>
                            <strong style="color:#1e3a8a;">${c.vote_count} votes</strong>
                        </div>
                        <div style="background:#e2e8f0;border-radius:6px;height:10px;overflow:hidden;box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);position:relative;">
                            <div style="background:linear-gradient(90deg,#3b82f6,#1d4ed8);width:${pct}%;height:100%;border-radius:6px;box-shadow:0 1px 3px rgba(59,130,246,0.3);transition:width 0.8s cubic-bezier(0.4, 0, 0.2, 1);"></div>
                        </div>
                    </div>`;
            });
        }

        return `
            <div class="graph" style="padding:2rem;box-sizing:border-box;background-color:#ffffff;border-radius:1rem;box-shadow:var(--shadow-m);transition:all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                <h3 style="margin-bottom:1.5rem;font-size:1.25rem;font-weight:700;color:#06152D;font-family:var(--ff);border-bottom:2px solid #f1f5f9;padding-bottom:8px;">${position}</h3>
                ${barsHtml}
            </div>`;
    }

    function renderStandings() {
        if (!graphContainer) return;

        const start = currentPage * 2;
        const positions = positionKeys.slice(start, start + 2);

        graphContainer.innerHTML = positions.map(renderStandingsGraph).join('');
        renderPagination();
    }

    function renderPagination() {
        if (!paginationEl) return;

        let html = `<li><button class="prev" ${currentPage === 0 ? 'disabled style="opacity:0.5"' : ''}>PREV</button></li>`;
        for (let i = 0; i < pagesCount; i++) {
            html += `<li><button class="pg ${i === currentPage ? 'active' : ''}" data-page="${i}">${i + 1}</button></li>`;
        }
        html += `<li><button class="next" ${currentPage >= pagesCount - 1 ? 'disabled style="opacity:0.5"' : ''}>NEXT</button></li>`;
        paginationEl.innerHTML = html;

        paginationEl.querySelector('.prev')?.addEventListener('click', () => {
            if (currentPage > 0) { currentPage--; renderStandings(); }
        });
        paginationEl.querySelector('.next')?.addEventListener('click', () => {
            if (currentPage < pagesCount - 1) { currentPage++; renderStandings(); }
        });
        paginationEl.querySelectorAll('.pg').forEach(btn => {
            btn.addEventListener('click', () => {
                currentPage = parseInt(btn.dataset.page, 10);
                renderStandings();
            });
        });
    }

    function renderHighlights() {
        if (!highlightCards) return;

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

            return `
                <div class="ch-card">
                    <div class="head-card">
                        <div class="prof">
                            <img src="${candidatePhoto(c.profilePicture)}" alt="img" class="${isDefaultProfilePhoto(c.profilePicture) ? 'default-profile-img' : ''}">
                            <div class="details"><h1>${c.fullname}</h1><h3>${c.position}</h3></div>
                        </div>
                        <div class="tags"><ul>${tags}</ul></div>
                    </div>
                    <div class="body-card">
                        <div class="details">
                            <h3>${c.partylist || 'Independent'}</h3>
                            <p>${(c.platform || 'No platform provided.').substring(0, 120)}${(c.platform || '').length > 120 ? '...' : ''}</p>
                        </div>
                        <a href="../view-candidate-profile.html?id=${c.id}"><button>View Profile</button></a>
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

        allCandCards.innerHTML = filtered.map(c => `
            <div class="ac-card">
                <div class="prof">
                    <img src="${candidatePhoto(c.profilePicture)}" alt="img" class="${isDefaultProfilePhoto(c.profilePicture) ? 'default-profile-img' : ''}">
                    <div class="details"><h1>${c.fullname}</h1><h3>${c.position}</h3></div>
                </div>
                <a href="../view-candidate-profile.html?id=${c.id}"><button>View Profile</button></a>
            </div>
        `).join('');
    }

    positionFilter?.addEventListener('change', renderAllCandidates);

    exportBtn?.addEventListener('click', () => {
        triggerDownload(`${API_BASE}/votes.php?action=export_ballot`);
    });

    loadData();
})();
