/**
 * legal.js — Animations partagées pour les pages légales
 * Animation d'apparition progressive des sections au scroll (IntersectionObserver)
 * + Fonction de réouverture du bandeau cookies depuis la page confidentialité
 */

(function () {
    'use strict';

    // Anime chaque .legal-section à l'entrée dans le viewport
    function initSectionAnimations() {
        const sections = document.querySelectorAll('.legal-section');
        if (!sections.length) return;

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.08 });

        sections.forEach(function (section) {
            observer.observe(section);
        });
    }

    // Réouvre le bandeau cookies (utilisé sur politique-confidentialite.html)
    window.reopenCookieBanner = function () {
        localStorage.removeItem('ejce_cookie_consent');
        localStorage.removeItem('ejce_cookie_consent_date');
        location.reload();
    };

    document.addEventListener('DOMContentLoaded', initSectionAnimations);
})();
