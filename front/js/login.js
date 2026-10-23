document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const loginError = document.getElementById('loginError');

    const currentPath = window.location.pathname.replace(/\/+$/, '');
    const currentDir = currentPath.endsWith('/connexion.html') || currentPath.endsWith('/connexion')
        ? currentPath.slice(0, currentPath.lastIndexOf('/'))
        : currentPath;
    const normalizedDir = currentDir === '' ? '' : currentDir;
    const apiBase = `${window.location.origin}${normalizedDir}/api`;

    const apiCandidates = [
        'api',
        apiBase,
        `${window.location.origin}/api`,
        'http://127.0.0.1/api',
        'http://localhost/api',
        'http://127.0.0.1:8000/api',
        'http://localhost:8000/api',
        'http://127.0.0.1:8001/api',
        'http://localhost:8001/api',
        'http://127.0.0.1:8080/api',
        'http://localhost:8080/api'
    ];

    function buildUrl(base, endpoint) {
        const normalizedBase = base.endsWith('/') ? base.slice(0, -1) : base;
        const normalizedEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
        return `${normalizedBase}/${normalizedEndpoint}`;
    }

    async function requestJson(endpoint, options = {}) {
        const validCandidates = apiCandidates.filter(base => !base.startsWith('null/'));
        const urls = [...new Set(validCandidates.map(base => buildUrl(base, endpoint)))];
        let lastError = null;

        for (const url of urls) {
            try {
                const isCrossOrigin = url.startsWith('http') && !url.startsWith(window.location.origin);
                const fetchOptions = {
                    ...options,
                    credentials: isCrossOrigin ? 'include' : 'same-origin'
                };
                const response = await fetch(url, fetchOptions);
                const contentType = response.headers.get('content-type') || '';

                if (response.ok && contentType.includes('application/json')) {
                    return { response, data: await response.json() };
                }

                if (response.ok) {
                    const text = await response.text();
                    throw new Error(text.slice(0, 160));
                }

                return { response, data: null };
            } catch (error) {
                lastError = error;
            }
        }

        throw lastError || new Error('Impossible de joindre l’API.');
    }

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        loginError.style.display = 'none';

        try {
            const { response, data } = await requestJson('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, password: password })
            });

            if (!response.ok) {
                loginError.style.display = 'block';
                loginError.innerText = "Identifiants incorrects.";
                return;
            }

            if (data && data.role === 'admin') {
                window.location.href = '/admin';
            } else if (data) {
                window.location.href = '/licencie';
            } else {
                loginError.style.display = 'block';
                loginError.innerText = "Réponse invalide du serveur.";
            }
        } catch (error) {
            console.error('Erreur lors de la connexion', error);
            loginError.style.display = 'block';
            loginError.innerText = "Erreur de connexion au serveur.";
        }
    });

    // Option mot de passe oublié
    const forgotBtn = document.getElementById('forgotBtn');
    const forgotMsg = document.getElementById('forgotMsg');
    if (forgotBtn) {
        forgotBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            if (!email) {
                forgotMsg.style.color = "red";
                forgotMsg.innerText = "Veuillez entrer votre adresse email ci-dessus puis cliquer sur mot de passe oublié.";
                return;
            }

            forgotMsg.style.color = "blue";
            forgotMsg.innerText = "Envoi en cours...";

            try {
                const { response } = await requestJson('/forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email })
                });

                if (response.ok) {
                    forgotMsg.style.color = "green";
                    forgotMsg.innerText = "Un code a été envoyé à votre adresse email. Redirection...";
                    setTimeout(() => {
                        window.location.href = '/mot-de-passe-oublie.html';
                    }, 1000);
                } else {
                    forgotMsg.style.color = "red";
                    forgotMsg.innerText = "Erreur ou email introuvable.";
                }
            } catch (error) {
                forgotMsg.style.color = "red";
                forgotMsg.innerText = "Erreur réseau.";
            }
        });
    }
});
