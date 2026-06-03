<?php
/**
 * Floating bottom-left switch: Browse ↔ My Candidacy (candidates only).
 *
 * Expected vars:
 *   $switchPage  — 'browse' | 'vote' | 'candidacy'
 *   $isCandidate — bool
 */
$switchPage  = $switchPage ?? 'browse';
$isCandidate = !empty($isCandidate);

if ($switchPage === 'candidacy' || $switchPage === 'vote') {
    $switchHref  = $switchPage === 'candidacy'
        ? '../student/browse.php'
        : 'browse.php';
    $switchLabel = 'Browse';
} elseif ($isCandidate) {
    $switchHref  = '../candidate/candidate-dashboard.php';
    $switchLabel = 'My Candidacy';
} else {
    $switchHref  = '#';
    $switchLabel = 'Apply as Candidate';
}
?>

<?php if ($switchLabel === 'Apply as Candidate'): ?>
<button type="button" id="apply-candidate-btn" class="student-switch-btn"><?= htmlspecialchars($switchLabel) ?></button>

<!-- Candidacy Registration Modal -->
<div class="supervision-modal-bg" id="candidacy-reg-modal" style="display: none;">
    <div class="edit-container" style="max-width: 36rem; width: 90%; height: auto; padding: 2rem; background: #ffffff; border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); box-sizing: border-box; font-family: 'Roboto Flex', sans-serif; text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">
            <h2 style="font-size: 1.5rem; color: #0f172a; margin: 0; font-weight: 700; text-align: left; font-family: sans-serif;">Apply for Candidacy</h2>
            <button type="button" id="close-reg-modal" style="background: none; border: none; font-size: 2rem; cursor: pointer; color: #64748b; font-weight: bold; line-height: 1;">&times;</button>
        </div>
        
        <form id="candidacy-reg-form" style="display: flex; flex-direction: column; gap: 1.25rem; text-align: left;">
            <div style="display: flex; flex-direction: column; gap: 0.5rem; text-align: left;">
                <label for="reg-position" style="font-weight: 600; font-size: 0.95rem; color: #334155; text-align: left; font-family: sans-serif;">Position to Run For *</label>
                <select id="reg-position" required style="padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-weight: 500; color: #1e293b; background-color: #f8fafc; cursor: pointer; width: 100%; font-family: sans-serif;">
                    <option value="">Select a position...</option>
                    <option value="President">President</option>
                    <option value="Vice President">Vice President</option>
                    <option value="Secretary">Secretary</option>
                    <option value="Treasurer">Treasurer</option>
                    <option value="Auditor">Auditor</option>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; text-align: left;">
                <label for="reg-partylist" style="font-weight: 600; font-size: 0.95rem; color: #334155; text-align: left; font-family: sans-serif;">Party-List (Optional)</label>
                <input type="text" id="reg-partylist" placeholder="e.g. Progressive Alliance, Unity Party, or Independent" style="padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-weight: 500; color: #1e293b; background-color: #f8fafc; width: 100%; box-sizing: border-box; font-family: sans-serif;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; text-align: left;">
                <label for="reg-platform" style="font-weight: 600; font-size: 0.95rem; color: #334155; text-align: left; font-family: sans-serif;">Campaign Platform *</label>
                <textarea id="reg-platform" required placeholder="Describe your campaign objectives and platform..." rows="5" style="padding: 0.75rem; border-radius: 0.5rem; border: 1px solid #cbd5e1; font-weight: 500; color: #1e293b; background-color: #f8fafc; font-family: sans-serif; resize: vertical; width: 100%; box-sizing: border-box;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid #e2e8f0; padding-top: 1.5rem; margin-top: 0.5rem;">
                <button type="button" id="cancel-reg-btn" style="padding: 0.75rem 1.5rem; background-color: #f1f5f9; color: #475569; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-family: sans-serif;">Cancel</button>
                <button type="submit" style="padding: 0.75rem 1.5rem; background-color: #1968E5; color: #ffffff; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-family: sans-serif;">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<style>
.supervision-modal-bg {
    background-color: rgba(6, 21, 45, 0.6);
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    z-index: 99999;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const applyBtn = document.getElementById('apply-candidate-btn');
    const modal = document.getElementById('candidacy-reg-modal');
    const closeBtn = document.getElementById('close-reg-modal');
    const cancelBtn = document.getElementById('cancel-reg-btn');
    const form = document.getElementById('candidacy-reg-form');

    if (!applyBtn || !modal) return;

    const openModal = () => {
        modal.style.display = 'flex';
    };

    const closeModal = () => {
        modal.style.display = 'none';
        form.reset();
    };

    applyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        openModal();
    });

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const position = document.getElementById('reg-position').value;
        const partylist = document.getElementById('reg-partylist').value;
        const platform = document.getElementById('reg-platform').value;

        const submitBtn = form.querySelector('button[type="submit"]');
        const origText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        try {
            const apiPath = '../../../public/api/student/register_candidate.php';

            const res = await fetch(apiPath, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ position, partylist, platform })
            });

            const json = await res.json();
            if (json.success) {
                alert('Your candidacy application has been registered successfully! You will now be redirected to submit your requirements.');
                window.location.href = '../candidate/candidate-dashboard.php';
            } else {
                alert(json.message || 'Registration failed.');
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = origText;
        }
    });
});
</script>
<?php else: ?>
<a href="<?= htmlspecialchars($switchHref) ?>" class="student-switch-btn"><?= htmlspecialchars($switchLabel) ?></a>
<?php endif; ?>
