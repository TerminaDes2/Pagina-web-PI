document.addEventListener("DOMContentLoaded", () => {
    fetch("noticias.php")
        .then(response => response.json())
        .then(data => {
            const noticiasContainer = document.getElementById("noticias-container");
            data.forEach(noticia => {
                const noticiaElement = document.createElement("div");
                noticiaElement.classList.add("noticia");
                noticiaElement.innerHTML = `
                    <img src="${noticia.imagen}" alt="${noticia.titulo}">
                    <div class="noticia-content">
                        <h3>${noticia.titulo}</h3>
                        <p>${noticia.resumen}</p>
                        <a href="${noticia.enlace}" class="btn btn-secondary">Leer más</a>
                    </div>
                `;
                noticiasContainer.appendChild(noticiaElement);
            });
        })
        .catch(error => console.error("Error al cargar las noticias:", error));
});

document.getElementById("subscribeForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const form = e.target;
    const email = form.email.value;
    const message = document.getElementById("subscribeMessage");

    fetch("suscribirse.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `email=${encodeURIComponent(email)}`
    })
    .then(response => response.json())
    .then(data => {
        message.textContent = data.message;
        message.style.color = data.status === "success" ? "limegreen" : "red";
        form.reset();
    })
    .catch(() => {
        message.textContent = "Ocurrió un error al enviar tu suscripción.";
        message.style.color = "red";
    });
});
