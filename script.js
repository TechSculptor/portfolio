document.querySelectorAll('nav ul li a').forEach(anchor => {
    anchor.addEventListener('click', function(event) {
        event.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        document.getElementById(targetId).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

const text = "Développeur | Cybersécurité | Logiciel & Web";
let index = 0;

function typeEffect() {
    document.getElementById("typingEffect").textContent = text.substring(0, index);
    index++;
    if (index <= text.length) {
        setTimeout(typeEffect, 100);
    }
}

window.onload = typeEffect;
