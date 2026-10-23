// animation apparition au scroll

const elements = document.querySelectorAll(".card, .map, .stats");

const observer = new IntersectionObserver(entries => {

  entries.forEach(entry => {

    if (entry.isIntersecting) {
      entry.target.style.opacity = 1;
      entry.target.style.transform = "translateY(0px)";
    }

  });

});

elements.forEach(el => {

  el.style.opacity = 0;
  el.style.transform = "translateY(50px)";
  el.style.transition = "all 0.8s ease";

  observer.observe(el);

});



const blocks = document.querySelectorAll(".profile-card, .teacher-message, .actions");

const observer2 = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = 1;
      entry.target.style.transform = "translateY(0)";
    }
  });
});

blocks.forEach(el => {
  el.style.opacity = 0;
  el.style.transform = "translateY(40px)";
  el.style.transition = "0.7s ease";

  observer2.observe(el);
});

// messagerie 

function sendMessage() {

  const input = document.getElementById("messageInput");
  const chatBox = document.getElementById("chatBox");

  if (input.value.trim() === "") return;

  const message = document.createElement("div");
  message.classList.add("message", "user");

  message.innerHTML = `
<p>${input.value}</p>
<span>Vous</span>
`;

  chatBox.appendChild(message);

  input.value = "";

  /* scroll automatique */
  chatBox.scrollTop = chatBox.scrollHeight;


  /* simulation réponse enseignant */
  setTimeout(() => {

    const reply = document.createElement("div");
    reply.classList.add("message", "teacher");

    reply.innerHTML = `
<p>Bien reçu 👍 Continue comme ça !</p>
<span>Enseignant</span>
`;

    chatBox.appendChild(reply);
    chatBox.scrollTop = chatBox.scrollHeight;

  }, 1500);

}


// revisions 

const data = {

  "o-soto-gari": {
    title: "O Soto Gari",
    img: "O-soto-gari.jpg",
    video: "https://www.youtube.com/watch?v=c-A_nP7mKAc",
    desc: "Technique de projection majeure consistant à déséquilibrer l’adversaire vers l’arrière avant de faucher sa jambe."
  },

  "o-goshi": {
    title: "O Goshi",
    img: "O-goshi.jpg",
    video: "https://www.youtube.com/watch?v=yhu1mfy2vJ4",
    desc: "Projection de hanche où le judoka charge son adversaire pour le projeter au sol."
  },

  "ippon-seoi-nage": {
    title: "Ippon Seoi Nage",
    img: "Ippon-seoi-nage.jpg",
    video: "https://www.youtube.com/watch?v=FQnOlCxo4oI",
    desc: "Projection d'épaule rapide et efficace."
  },

  "de-ashi-barai": {
    title: "De Ashi Barai",
    img: "De-ashi-barai.jpg",
    video: "https://www.youtube.com/watch?v=4BUUvqxi_Kk",
    desc: "Balayage du pied basé sur le timing."
  },

  "uchi-mata": {
    title: "Uchi Mata",
    img: "Uchimata.png",
    video: "https://www.youtube.com/watch?v=iUpSu5J-bgw",
    desc: "Fauchage intérieur avancé."
  },

  "harai-goshi": {
    title: "Harai Goshi",
    img: "Harai-goshi.jpg",
    video: "https://www.youtube.com/watch?v=qTo8HlAAkOo",
    desc: "Projection combinant hanche et balayage."
  }

};


function showDetail(id) {

  document.getElementById("detailSection").style.display = "block";

  document.getElementById("detailTitle").innerText = data[id].title;
  document.getElementById("detailDesc").innerText = data[id].desc;
  document.getElementById("detailImg").src = data[id].img;
  document.getElementById("videoLink").href = data[id].video;

  window.scrollTo({
    top: document.getElementById("detailSection").offsetTop,
    behavior: "smooth"
  });

}

// fermer
function closeDetail() {
  document.getElementById("detailSection").style.display = "none";
}


// filtre
function filterSelection(category) {

  let cards = document.getElementsByClassName("card");

  for (let i = 0; i < cards.length; i++) {

    if (category === "all") {
      cards[i].style.display = "block";
    } else {
      cards[i].style.display = cards[i].classList.contains(category) ? "block" : "none";
    }

  }

}
// boutons


function showDetail(id) {

  document.getElementById("detailSection").style.display = "block";

  document.getElementById("detailTitle").innerText = data[id].title;
  document.getElementById("detailDesc").innerText = data[id].desc;
  document.getElementById("detailImg").src = data[id].img;

  // conversion en format embed
  let videoURL = data[id].video.replace("watch?v=", "embed/");
  document.getElementById("videoFrame").src = videoURL;

  window.scrollTo({
    top: document.getElementById("detailSection").offsetTop,
    behavior: "smooth"
  });

}

// responcive

document.addEventListener('DOMContentLoaded', () => {
  console.log("DOM chargé : on cherche les navs...");

  // Tous les headers .nav
  const navContainers = document.querySelectorAll('.nav');
  console.log("Nombre de .nav trouvés :", navContainers.length);

  if (navContainers.length === 0) {
    console.warn("Aucune .nav trouvée ! Vérifier le HTML.");
  }

  navContainers.forEach((navContainer, index) => {
    console.log(`Nav n°${index + 1} :`, navContainer);

    const hamburger = navContainer.querySelector('.hamburger');
    const menu = navContainer.querySelector('nav ul');

    if (!hamburger) {
      console.warn(`Pas de hamburger trouvé pour la nav n°${index + 1}`);
      return;
    } else {
      console.log("Hamburger trouvé :", hamburger);
    }

    if (!menu) {
      console.warn(`Pas de menu (ul) trouvé pour la nav n°${index + 1}`);
      return;
    } else {
      console.log("Menu trouvé :", menu);
    }

    // Toggle menu au clic
    hamburger.addEventListener('click', () => {
      console.log("Hamburger cliqué !");
      menu.classList.toggle('active');
      console.log("Menu actif :", menu.classList.contains('active'));
    });
  });
});


// animation accueil document.addEventListener('DOMContentLoaded', () => {
document.addEventListener('DOMContentLoaded', async () => {
  const hero = document.querySelector('.hero');
  if (!hero) return;

  let images = [];
  try {
    const res = await fetch('/api/carousel');
    const data = await res.json();
    images = data.map(img => img.urlStockage);
  } catch (e) {
    console.warn("Impossible de charger le carousel dynamique, repli sur images statiques.");
  }

  // Fallback si vide
  if (images.length === 0) {
    images = [
      'https://maquette-web-ejce.netlify.app/judo.jpg',
      'https://maquette-web-ejce.netlify.app/judo1.jpg',
      'https://maquette-web-ejce.netlify.app/judo2.jpg',
      'https://maquette-web-ejce.netlify.app/judo3.jpg'
    ];
  }

  let current = 0;

  // créer les slides
  images.forEach(src => {
    const div = document.createElement('div');
    div.classList.add('hero-slide');
    div.style.backgroundImage = `url('${src}')`;
    hero.prepend(div); // derrière le texte
  });

  const slides = hero.querySelectorAll('.hero-slide');
  if (slides.length > 0) {
    slides[current].classList.add('active');

    setInterval(() => {
      slides[current].classList.remove('active');
      current = (current + 1) % slides.length;
      slides[current].classList.add('active');
    }, 5000);
  }
});

// GLOBAL TOGGLE CHECK (Newsletter)
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch('/api/settings');
    const settings = await res.json();
    if (settings.newsletter_enabled === '0') {
      // Masquer tous les liens vers la newsletter (menu, footer, etc.)
      document.querySelectorAll('a[href*="newsletter"], a[href*="proposer-article"]').forEach(link => {
        const li = link.closest('li');
        if (li) li.style.display = 'none';
        else link.style.display = 'none';
      });

      // Si on est sur la page newsletter ou proposer un article, redirection vers l'accueil
      if (window.location.pathname.includes('newsletter.html') || window.location.pathname.includes('proposer-article.html')) {
        window.location.href = '/';
      }
    }
  } catch (e) { }
});

//Newsletter

document.querySelectorAll('.arrow').forEach(button => {
  button.addEventListener('click', () => {
    const card = button.closest('.article-card');
    console.log("Changement d'image pour l'article : " + card.querySelector('h2').innerText);
    // Ici on pourrait ajouter une logique de changement de source d'image
  });
});