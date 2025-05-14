document.addEventListener("DOMContentLoaded", () => {
  const contenido = `
      <section class="contenido">
          <h1>Acerca de Nosotros</h1>

          <h2>¿Quiénes somos?</h2>
          <p><strong>Voces del Proceso</strong> es una plataforma digital comprometida con la difusión de información clara, accesible y verificada sobre derechos laborales, reformas sociales y leyes que impactan la vida de las personas.</p>

          <h2>¿Qué hacemos?</h2>
          <p>Brindamos noticias, análisis y recursos prácticos que permiten a los ciudadanos conocer y ejercer sus derechos laborales, fomentando así el trabajo digno y el crecimiento económico sostenible.</p>

          <h2>Misión</h2>
          <p>Impulsar el acceso a un trabajo decente y al conocimiento de los derechos laborales, empoderando a las personas mediante información confiable y fomentando la participación social activa.</p>

          <h2>Visión</h2>
          <p>Convertirnos en el principal referente de información laboral y de desarrollo social en América Latina, promoviendo el respeto, la equidad y el bienestar común.</p>

          <h2>Valores</h2>
          <ul>
              <li><strong>Compromiso social:</strong> Trabajamos para mejorar la calidad de vida de las personas.</li>
              <li><strong>Veracidad:</strong> Publicamos únicamente información verificada y sustentada.</li>
              <li><strong>Accesibilidad:</strong> Hacemos que el conocimiento esté al alcance de todos.</li>
              <li><strong>Responsabilidad:</strong> Cumplimos con los más altos estándares éticos.</li>
          </ul>

          <h2>Información Adicional</h2>
          <p>En <strong>Voces del Proceso</strong>, nos dedicamos a brindar información precisa, actualizada y verificada sobre reformas, derechos laborales y leyes que impactan a trabajadores y ciudadanos.</p>
          <p>Somos un equipo comprometido con la difusión de noticias que promueven el trabajo decente, el crecimiento económico y el desarrollo sostenible.</p>
          <p>Nuestra misión es impulsar una ciudadanía informada, capaz de participar activamente en las decisiones que transforman nuestras comunidades.</p>
          <p>Creemos en la importancia de acercar la información de manera clara, accesible y basada en hechos, fomentando el diálogo y el análisis crítico.</p>
      </section>
      <div class="franja-final">
          <p>8 TRABAJO DECENTE Y CRECIMIENTO ECONÓMICO</p>
      </div>
      <div class="franja-derechos">
          <p>©2025 Voces del Proceso. Todos los derechos reservados.</p>
      </div>
  `;

  const contenedor = document.getElementById("contenido-principal");
  if (contenedor) {
      contenedor.innerHTML = contenido;
  }
});
