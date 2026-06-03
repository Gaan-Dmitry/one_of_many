const body = document.body;
const epoch = Number(body.dataset.epoch || 0) * 1000;
const speed = Number(body.dataset.speed || 1);

function formatWorldClock() {
    const elapsedSec = (Date.now() - epoch) / 1000;
    const worldSec = elapsedSec * speed;
    const secOfDay = Math.floor(((worldSec % 86400) + 86400) % 86400);
    const h = Math.floor(secOfDay / 3600);
    const m = Math.floor((secOfDay % 3600) / 60);
    const s = Math.floor(secOfDay % 60);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function tick() {
    const time = formatWorldClock();
    document.querySelectorAll('#world-clock, #hero-clock').forEach((node) => {
        node.textContent = time;
    });
    requestAnimationFrame(tick);
}

tick();

const codexSearch = document.getElementById('codex-search');
const codexCards = [...document.querySelectorAll('.codex-card')];
let activeSection = '';

function filterCodex() {
    const query = (codexSearch?.value || '').trim().toLowerCase();
    codexCards.forEach((card) => {
        const matchesQuery = card.dataset.search?.includes(query) ?? true;
        const matchesSection = !activeSection || card.dataset.path?.startsWith(activeSection);
        card.style.display = matchesQuery && matchesSection ? '' : 'none';
    });
}

codexSearch?.addEventListener('input', filterCodex);
document.querySelectorAll('.pill[data-section]').forEach((button) => {
    button.addEventListener('click', () => {
        activeSection = button.dataset.section || '';
        document.querySelectorAll('.pill[data-section]').forEach((pill) => pill.classList.remove('is-active'));
        button.classList.add('is-active');
        filterCodex();
    });
});

document.querySelectorAll('input[name="template"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        const category = radio.dataset.category;
        const select = document.getElementById('category-select');
        if (radio.checked && category && select) {
            select.value = category;
        }
        // Highlight selected card
        document.querySelectorAll('.template-card').forEach(card => {
            card.classList.remove('selected');
        });
        radio.closest('.template-card').classList.add('selected');
    });
});

document.querySelector('input[name="template"]:checked')?.dispatchEvent(new Event('change'));
