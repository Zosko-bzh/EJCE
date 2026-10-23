/**
 * cookies.js — Bandeau de consentement aux cookies EJCE
 * Conforme RGPD / ePrivacy Directive
 * Ce script s'auto-injecte sur toutes les pages qui l'incluent.
 */

(function () {
    'use strict';

    const CONSENT_KEY = 'ejce_cookie_consent';
    const CONSENT_DATE_KEY = 'ejce_cookie_consent_date';
    const CONSENT_EXPIRY_MONTHS = 12;

    // Vérifie si le consentement est encore valide (< 12 mois)
    function isConsentValid() {
        const consent = localStorage.getItem(CONSENT_KEY);
        const date = localStorage.getItem(CONSENT_DATE_KEY);
        if (!consent || !date) return false;
        const consentDate = new Date(date);
        const expiryDate = new Date(consentDate);
        expiryDate.setMonth(expiryDate.getMonth() + CONSENT_EXPIRY_MONTHS);
        return new Date() < expiryDate;
    }

    // Enregistre le consentement
    function saveConsent(accepted) {
        localStorage.setItem(CONSENT_KEY, accepted ? 'accepted' : 'refused');
        localStorage.setItem(CONSENT_DATE_KEY, new Date().toISOString());
    }

    // Supprime le bandeau avec animation
    function removeBanner() {
        const banner = document.getElementById('ejce-cookie-banner');
        if (banner) {
            banner.style.transform = 'translateY(120%)';
            banner.style.opacity = '0';
            setTimeout(() => banner.remove(), 400);
        }
    }

    // Crée et injecte le bandeau
    function createBanner() {
        // Styles injectés dynamiquement
        const style = document.createElement('style');
        style.textContent = `
            #ejce-cookie-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 99999;
                background: #1a1a1a;
                color: #f0f0f0;
                padding: 0;
                transform: translateY(100%);
                opacity: 0;
                transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.4s ease;
                box-shadow: 0 -4px 30px rgba(0,0,0,0.35);
                font-family: 'Poppins', 'Segoe UI', sans-serif;
            }

            #ejce-cookie-banner.show {
                transform: translateY(0);
                opacity: 1;
            }

            .cookie-inner {
                max-width: 1200px;
                margin: 0 auto;
                padding: 22px 28px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                flex-wrap: wrap;
            }

            .cookie-icon-wrap {
                display: flex;
                align-items: center;
                gap: 14px;
                flex: 1;
                min-width: 260px;
            }

            .cookie-icon {
                font-size: 2rem;
                flex-shrink: 0;
            }

            .cookie-text-block h4 {
                font-size: 1rem;
                font-weight: 600;
                color: #ffffff;
                margin: 0 0 4px;
            }

            .cookie-text-block p {
                font-size: 0.82rem;
                color: #bbb;
                margin: 0;
                line-height: 1.5;
                text-align: left;
            }

            .cookie-text-block a {
                color: #ef9a9a;
                text-decoration: none;
            }

            .cookie-text-block a:hover {
                text-decoration: underline;
            }

            .cookie-actions {
                display: flex;
                gap: 10px;
                flex-shrink: 0;
                flex-wrap: wrap;
                align-items: center;
            }

            .cookie-btn {
                padding: 10px 22px;
                border-radius: 8px;
                border: none;
                font-family: 'Poppins', sans-serif;
                font-weight: 600;
                font-size: 0.88rem;
                cursor: pointer;
                transition: all 0.25s ease;
                white-space: nowrap;
            }

            .cookie-btn-accept {
                background: #c62828;
                color: white;
            }

            .cookie-btn-accept:hover {
                background: #e53935;
                transform: translateY(-2px);
                box-shadow: 0 6px 18px rgba(198, 40, 40, 0.4);
            }

            .cookie-btn-refuse {
                background: transparent;
                color: #bbb;
                border: 1px solid #555;
            }

            .cookie-btn-refuse:hover {
                border-color: #bbb;
                color: #fff;
            }

            .cookie-btn-info {
                background: transparent;
                color: #ef9a9a;
                border: 1px solid #555;
                padding: 10px 16px;
                font-size: 0.82rem;
            }

            .cookie-btn-info:hover {
                background: rgba(198,40,40,0.1);
                border-color: #c62828;
            }

            .cookie-close {
                background: none;
                border: none;
                color: #888;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 4px 8px;
                line-height: 1;
                transition: color 0.2s;
                align-self: flex-start;
            }

            .cookie-close:hover { color: #fff; }

            @media (max-width: 640px) {
                .cookie-inner { padding: 18px 16px; gap: 16px; }
                .cookie-icon-wrap { min-width: unset; }
                .cookie-actions { width: 100%; justify-content: stretch; }
                .cookie-btn { flex: 1; text-align: center; }
            }
        `;
        document.head.appendChild(style);

        // HTML du bandeau
        const banner = document.createElement('div');
        banner.id = 'ejce-cookie-banner';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Bandeau de consentement aux cookies');
        banner.innerHTML = `
            <div class="cookie-inner">
                <div class="cookie-icon-wrap">
                    <span class="cookie-icon">🍪</span>
                    <div class="cookie-text-block">
                        <h4>Ce site utilise des cookies</h4>
                        <p>
                            Nous utilisons des cookies essentiels au fonctionnement du site (authentification, préférences).
                            Aucun cookie de tracking publicitaire n'est utilisé.
                            <a href="/politique-confidentialite.html#cookies">En savoir plus</a>
                        </p>
                    </div>
                </div>
                <div class="cookie-actions">
                    <button class="cookie-btn cookie-btn-info" id="ejce-cookie-info" onclick="window.location.href='/politique-confidentialite.html#cookies'">
                        Détails
                    </button>
                    <button class="cookie-btn cookie-btn-refuse" id="ejce-cookie-refuse">
                        Refuser
                    </button>
                    <button class="cookie-btn cookie-btn-accept" id="ejce-cookie-accept">
                        ✓ Accepter
                    </button>
                </div>
                <button class="cookie-close" id="ejce-cookie-close" aria-label="Fermer" title="Fermer">✕</button>
            </div>
        `;

        document.body.appendChild(banner);

        // Afficher avec délai pour déclencher la transition CSS
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                banner.classList.add('show');
            });
        });

        // Événements boutons
        document.getElementById('ejce-cookie-accept').addEventListener('click', () => {
            saveConsent(true);
            removeBanner();
        });

        document.getElementById('ejce-cookie-refuse').addEventListener('click', () => {
            saveConsent(false);
            removeBanner();
        });

        document.getElementById('ejce-cookie-close').addEventListener('click', () => {
            // Fermer sans enregistrer = réapparaîtra à la prochaine visite
            removeBanner();
        });
    }

    // Point d'entrée
    function init() {
        if (!isConsentValid()) {
            // Attendre que le DOM soit prêt
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', createBanner);
            } else {
                // Délai court pour ne pas bloquer le rendu initial
                setTimeout(createBanner, 600);
            }
        }
    }

    // Fonction globale pour rouvrir le bandeau (utilisée depuis la page confidentialité)
    window.reopenCookieBanner = function () {
        localStorage.removeItem(CONSENT_KEY);
        localStorage.removeItem(CONSENT_DATE_KEY);
        createBanner();
    };

    init();

})();
