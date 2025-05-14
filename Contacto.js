document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('.toggle-btn');

    botones.forEach(btn => {
        btn.addEventListener('click', () => {
            const respuesta = btn.nextElementSibling;
            const visible = respuesta.style.display === 'block';
            respuesta.style.display = visible ? 'none' : 'block';
            btn.innerHTML = btn.innerHTML.includes('⮟') ? btn.innerHTML.replace('⮟', '⮝') : btn.innerHTML.replace('⮝', '⮟');
        });
    });
});
