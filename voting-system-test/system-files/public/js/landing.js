/**
 * landing.js
 * Fetch and render standings on the public landing page.
 */
(() => {
    'use strict';

    const API = '../../public/api/student/candidates.php';
    const DEFAULT_IMG = '../../public/img/478589759275824754.png';
    const POSITION_ORDER = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor'];

    const graphContainer = document.getElementById('standings-graphs');
    const paginationEl   = document.getElementById('standings-pagination');

    let standingsByPosition = {};
    let currentPage = 0;
    const pagesCount = Math.ceil(POSITION_ORDER.length / 2) || 1;

    async function loadData() {
        try {
            const res = await fetch(`${API}?action=standings&v=${Date.now()}`);
            const json = await res.json();
            if (!json.success) return;

            standingsByPosition = json.data.by_position || {};
            renderStandings();
        } catch (err) {
            console.error('Error loading standings on landing page:', err);
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
        const positions = POSITION_ORDER.slice(start, start + 2);

        graphContainer.innerHTML = positions.map(renderStandingsGraph).join('');
        renderPagination();
    }

    function renderPagination() {
        if (!paginationEl) return;

        let html = `<li><button class="prev" ${currentPage === 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''}>PREV</button></li>`;
        for (let i = 0; i < pagesCount; i++) {
            html += `<li><button class="pg ${i === currentPage ? 'active' : ''}" data-page="${i}">${i + 1}</button></li>`;
        }
        html += `<li><button class="next" ${currentPage >= pagesCount - 1 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''}>NEXT</button></li>`;
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

    loadData();
})();
