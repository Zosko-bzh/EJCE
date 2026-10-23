document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('app');

    // Exemple d'appel API vers le backend Symfony
    fetch('/api/status')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur de connexion avec le serveur');
            }
            return response.json();
        })
        .then(data => {
            app.innerHTML = `
                <h2>Statut du Backend</h2>
                <p><strong>Message :</strong> ${data.message}</p>
                <p><strong>Environnement :</strong> ${data.env}</p>
                <p>Connexion réussie entre le Front-End (JS) et le Back-End (Symfony).</p>
            `;
        })
        .catch(error => {
            app.innerHTML = `
                <h2>Statut du Backend</h2>
                <p style="color: red;"><strong>Erreur :</strong> Impossible de joindre l'API Symfony (/api/status).</p>
                <p>Assurez-vous que le conteneur PHP tourne et que Symfony est bien installé dans le dossier /back.</p>
            `;
            console.error(error);
        });
});
