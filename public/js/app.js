import './bootstrap.js';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Révèle les éléments au scroll (ajoute .is-visible à .reveal)
(function(){
    const els = document.querySelectorAll('[data-animate="reveal"], .reveal');
    if (!('IntersectionObserver' in window) || els.length === 0){
        els.forEach(el => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('is-visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: .12 });
    els.forEach(el => io.observe(el));
})();

// Défilement doux pour les ancres (#)
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute('href').slice(1);
    const target = document.getElementById(id);
    if (target){
        e.preventDefault();
        target.scrollIntoView({ behavior:'smooth', block:'start' });
    }
});
// JS applicatif global (tu peux y mettre tes petits scripts spécifiques)
// Exemple : fermer automatiquement les alertes côté client si besoin :
document.querySelectorAll('.alert .btn-close')?.forEach(btn=>{
    btn.addEventListener('click', e => e.target.closest('.alert')?.remove());
});

// --- Moderation AJAX (progressive enhancement) ---
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form.js-comment-toggle');
    if (!form) return;

    // Interception pour AJAX, tout en gardant le fallback si fetch échoue
    e.preventDefault();

    const wrapper = form.closest('.js-comment');
    const btn     = form.querySelector('.js-comment-btn');
    const badge   = wrapper.querySelector('.js-comment-status');

    // état "chargement"
    const oldText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });

        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Erreur');

        // MAJ bouton (classe + libellé)
        btn.classList.toggle('btn-success', data.newStatus !== 'approved');
        btn.classList.toggle('btn-outline-secondary', data.newStatus === 'approved');
        btn.textContent = data.labels.btn;

        // MAJ badge (classe + libellé)
        badge.classList.remove('text-bg-success', 'text-bg-warning', 'text-dark');
        if (data.newStatus === 'approved') {
            badge.classList.add('text-bg-success');
        } else {
            badge.classList.add('text-bg-warning', 'text-dark');
        }
        badge.textContent = data.labels.status;

        // Optionnel : micro toast client
        flashClient('success', data.newStatus === 'approved'
            ? 'Commentaire approuvé.'
            : 'Approbation annulée.');
    } catch (err) {
        console.error(err);
        flashClient('danger', 'Impossible de mettre à jour le statut.');
        // Fallback : tu peux désactiver le preventDefault pour laisser poster classique
    } finally {
        btn.disabled = false;
        if (btn.textContent === '...') btn.textContent = oldText;
    }
});

// Mini flash client (appends a bootstrap-like alert qui auto-disparaît)
function flashClient(type, message) {
    const div = document.createElement('div');
    div.className = `alert alert-${type}`;
    div.style.position = 'fixed';
    div.style.right = '16px';
    div.style.bottom = '16px';
    div.style.zIndex = '1080';
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('spoiler')) {
        e.target.classList.toggle('revealed');
    }
});
