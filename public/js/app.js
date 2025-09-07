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
