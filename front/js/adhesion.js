/**
 * adhesion.js — Scripts de la page d'adhésion EJCE
 * - Chargement dynamique du lien PDF d'inscription depuis l'API
 * - Envoi du dossier complété par l'utilisateur
 */

(function () {
    'use strict';

    /**
     * Charge l'URL du PDF d'inscription depuis l'API et met à jour le bouton de téléchargement.
     * Utilise le PDF de secours (fallback) si aucun PDF n'a été uploadé ou en cas d'erreur réseau.
     */
    async function loadPdfLink() {
        const btn = document.getElementById('pdfDownloadBtn');
        const meta = document.getElementById('pdfMeta');
        if (!btn) return;

        try {
            const res = await fetch('/api/adhesion-pdf');
            if (res.ok) {
                const data = await res.json();
                if (data.url) {
                    btn.href = data.url;
                    btn.textContent = '📄 Télécharger le dossier';
                    // Extraire la date depuis le nom du fichier : YYYYMMDD_HHmmss
                    if (meta) {
                        const match = data.url.match(/(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/);
                        if (match) {
                            meta.textContent = `Mis à jour le ${match[3]}/${match[2]}/${match[1]}`;
                        }
                    }
                    return;
                }
            }
        } catch (e) {
            // Erreur réseau, on passe au fallback
        }

        // Fallback si aucun PDF n'est uploadé par l'admin ou si l'API échoue
        btn.removeAttribute('download');
        btn.href = '#';
        btn.textContent = '❌ Dossier indisponible';
        if (meta) meta.textContent = "Aucun dossier d'inscription n'est actuellement disponible.";
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            alert("Le dossier d'inscription n'est pas encore disponible au téléchargement. Veuillez réessayer plus tard ou contacter le club.");
        });
    }

    /**
     * Gère l'envoi du dossier d'inscription complété par l'utilisateur.
     */
    function initUploadForm() {
        const form = document.getElementById('adhesion-form');
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('adhesionBtn');
            const msg = document.getElementById('adhesionMsg');
            const fileInput = document.getElementById('dossierInput');

            if (!fileInput || fileInput.files.length === 0) return;

            btn.disabled = true;
            btn.innerText = 'Envoi en cours...';

            const formData = new FormData();
            formData.append('dossier', fileInput.files[0]);

            try {
                const res = await fetch('/api/adhesion', {
                    method: 'POST',
                    body: formData
                });

                if (res.ok) {
                    msg.style.color = 'green';
                    msg.innerText = 'Dossier envoyé avec succès !';
                    form.reset();
                } else {
                    msg.style.color = 'red';
                    msg.innerText = "Erreur lors de l'envoi du dossier.";
                }
            } catch (err) {
                msg.style.color = 'red';
                msg.innerText = 'Erreur réseau.';
            } finally {
                btn.disabled = false;
                btn.innerText = 'Déposer le dossier';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadPdfLink();
        initUploadForm();
    });
})();
