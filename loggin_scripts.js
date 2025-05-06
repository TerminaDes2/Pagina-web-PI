function mostrarFormulario() {
    document.getElementById("datos").style.display = "flex";
    document.getElementById("register").style.display = "none";
}

function mostrarFormulario2() {
    document.getElementById("datos").style.display = "none";
    document.getElementById("register").style.display = "flex";
}

$(document).ready(function() {
    var height = $(window).height();
    $('body').height(height);
});