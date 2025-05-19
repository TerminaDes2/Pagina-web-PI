<?php
session_start();

//Uso de Cookie
require_once '../includes/auth.php';

// Librería PHPMailer para envío de correos de verificación
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Incluir los archivos necesarios de PHPMailer
require_once '../PHPMailer-master/src/PHPMailer.php';
require_once '../PHPMailer-master/src/SMTP.php';
require_once '../PHPMailer-master/src/Exception.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

// Conexión a la base de datos
include "../includes/db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Incluir el traductor
require_once '../includes/traductor.php';
$translator = new Translator($conn);

// Manejar cambio de idioma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener los datos actuales del usuario
$id_usuario = $_SESSION['usuario']['id_usuario'];
$stmt = $conn->prepare("SELECT nombre, primer_apellido, segundo_apellido, correo, imagen FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nombre, $primer_apellido, $segundo_apellido, $correo, $imagen_perfil);
$stmt->fetch();
$stmt->close();

// Actualizar la imagen en la sesión
if ($imagen_perfil !== $_SESSION['usuario']['imagen']) {
    $_SESSION['usuario']['imagen'] = $imagen_perfil;
}

// Obtener las publicaciones del usuario
$stmt = $conn->prepare("SELECT e.id_entrada, e.titulo, c.categoria as nombre_categoria, e.fecha 
                       FROM entradas e 
                       LEFT JOIN categorias c ON e.categoria = c.id_categoria
                       WHERE e.id_usuario = ?
                       ORDER BY e.id_entrada DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener los comentarios del usuario
$stmt = $conn->prepare("SELECT c.id_comentario, c.descripcion, c.fecha, e.titulo, e.id_entrada 
                       FROM comentarios c
                       JOIN entradas e ON c.id_entrada = e.id_entrada
                       WHERE c.id_usuario = ?
                       ORDER BY c.id_comentario DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$message = "";
$messageType = "";
if(isset($_GET['msg'])){
    $message = $_GET['msg'];
    $messageType = isset($_GET['msgType']) ? $_GET['msgType'] : "success";
}

// Procesar solicitud AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'verificar_password') {
        $password_actual = $_POST['password_actual'];
        $stmt = $conn->prepare("SELECT contra FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($hash_password);
        $stmt->fetch();
        $stmt->close();
        
        // Verificar la contraseña
        if (password_verify($password_actual, $hash_password)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $translator->__("La contraseña actual es incorrecta")]);
        }
        exit();
    } 
    // Procesar solicitud de envío de código para cambio de correo o contraseña
    else if ($_POST['action'] == 'enviar_codigo') {
        $tipo_cambio = $_POST['tipo_cambio']; // 'correo' o 'password'
        $nuevo_valor = isset($_POST['nuevo_valor']) ? $_POST['nuevo_valor'] : '';
        
        // Generar código aleatorio
        $codigo = rand(100000, 999999);
        
        // Guardar en sesión para verificación posterior
        $_SESSION['perfil_verificacion'] = [
            'codigo' => $codigo,
            'tipo' => $tipo_cambio,
            'nuevo_valor' => $nuevo_valor,
            'timestamp' => time()
        ];
        
        // Enviar correo con el código
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contactopoalce@gmail.com';
            $mail->Password = 'umrv wpyz taio bgwu';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom('contactopoalce@gmail.com', 'POALCE');
            $mail->addAddress($correo, "$nombre $primer_apellido");

            $mail->isHTML(true);
            
            // Cambiar título y contenido según el tipo de cambio
            if ($tipo_cambio == 'correo') {
                $mail->Subject = $translator->__('Verificación para cambio de correo');
                $mensaje = "<h2>" . $translator->__("Verificación de cambio") . "</h2>
                           <p>" . $translator->__("Hola") . " {$nombre} {$primer_apellido},</p>
                           <p>" . $translator->__("Has solicitado cambiar tu correo electrónico.") . "</p>
                           <p>" . $translator->__("Para completar este cambio, por favor utiliza el siguiente código de verificación:") . "</p>
                           <h3>" . $translator->__("Código de verificación:") . " </h3><h3 style='color: red;'>{$codigo}</h3>
                           <p>" . $translator->__("Si no has solicitado este cambio, ignora este mensaje y revisa la seguridad de tu cuenta.") . "</p>
                           <p>" . $translator->__("El equipo de POALCE") . "</p>";
            } else {
                $mail->Subject = $translator->__('Verificación para cambio de contraseña');
                $mensaje = "<h2>" . $translator->__("Verificación de cambio") . "</h2>
                           <p>" . $translator->__("Hola") . " {$nombre} {$primer_apellido},</p>
                           <p>" . $translator->__("Has solicitado cambiar tu contraseña.") . "</p>
                           <p>" . $translator->__("Para completar este cambio, por favor utiliza el siguiente código de verificación:") . "</p>
                           <h3>" . $translator->__("Código de verificación:") . " </h3><h3 style='color: red;'>{$codigo}</h3>
                           <p>" . $translator->__("Si no has solicitado este cambio, ignora este mensaje y revisa la seguridad de tu cuenta.") . "</p>
                           <p>" . $translator->__("El equipo de POALCE") . "</p>";
            }
            
            $mail->Body = $mensaje;
            $mail->send();
            
            echo json_encode(['success' => true, 'message' => $translator->__("Se ha enviado un código de verificación a tu correo electrónico")]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $translator->__("Error al enviar el correo: ") . $mail->ErrorInfo]);
        }
        exit();
    }
    // Verificar código ingresado
    else if ($_POST['action'] == 'verificar_codigo') {
        $codigo_ingresado = $_POST['codigo'];
        
        if (!isset($_SESSION['perfil_verificacion'])) {
            echo json_encode(['success' => false, 'error' => $translator->__("No hay solicitud de verificación pendiente")]);
            exit();
        }
        
        $verificacion = $_SESSION['perfil_verificacion'];
        
        // Verificar tiempo límite (10 minutos)
        if (time() - $verificacion['timestamp'] > 600) {
            echo json_encode(['success' => false, 'error' => $translator->__("El código ha expirado")]);
            unset($_SESSION['perfil_verificacion']);
            exit();
        }
        
        // Verificar código
        if ($codigo_ingresado == $verificacion['codigo']) {
            echo json_encode(['success' => true, 'tipo' => $verificacion['tipo'], 'nuevo_valor' => $verificacion['nuevo_valor']]);
        } else {
            echo json_encode(['success' => false, 'error' => $translator->__("Código incorrecto")]);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= isset($_SESSION['idioma']) ? $_SESSION['idioma'] : 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Mi Perfil") ?> - POALCE</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="/Pagina-web-PI/assets/img/POALCE-logo.ico" type="image/x-icon">
    <script>
        function mostrarPestana(pestanaId) {
            const contenidos = document.querySelectorAll('.perfil-contenido');
            contenidos.forEach(contenido => {
                contenido.style.display = 'none';
            });
            
            document.getElementById(pestanaId).style.display = 'block';
            
            const enlaces = document.querySelectorAll('.perfil-nav a');
            enlaces.forEach(enlace => {
                enlace.classList.remove('active');
                if (enlace.getAttribute('data-tab') === pestanaId) {
                    enlace.classList.add('active');
                }
            });
            
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 10);
        }
        
        function mostrarVistaPrevia() {
            const input = document.getElementById('imagen');
            const preview = document.getElementById('imagen-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function confirmarEliminarComentario(idComentario) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                text: '<?= $translator->__("Esta acción no se puede deshacer") ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../includes/eliminar_comentario.php?id=' + idComentario;
                }
            });
        }
        
        function confirmarEliminarPublicacion(idPublicacion) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                text: '<?= $translator->__("Esta acción eliminará permanentemente la publicación y todos sus comentarios") ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../includes/eliminar_publicacion.php?id=' + idPublicacion;
                }
            });
        }
        
        function mostrarModalVerificacion(tipo, nuevoValor = '') {
            $.ajax({
                url: 'perfil.php',
                type: 'POST',
                data: {
                    action: 'enviar_codigo',
                    tipo_cambio: tipo,
                    nuevo_valor: nuevoValor
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: '<?= $translator->__("Verificación requerida") ?>',
                            text: response.message,
                            icon: 'info',
                            showConfirmButton: true
                        });
                        
                        $("#verificacionModal").show();
                        $(".modal").addClass("active");
                        $("#tipo_verificacion").val(tipo);
                        $("#nuevo_valor_verificacion").val(nuevoValor);
                    } else {
                        Swal.fire({
                            title: '<?= $translator->__("Error") ?>',
                            text: response.error,
                            icon: 'error',
                            showConfirmButton: true
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: '<?= $translator->__("Error de conexión") ?>',
                        text: '<?= $translator->__("No se pudo conectar con el servidor") ?>',
                        icon: 'error',
                        showConfirmButton: true
                    });
                }
            });
        }
        
        function verificarYProcesar() {
            const formData = new FormData(document.getElementById('form-perfil'));
            const correoNuevo = formData.get('correo');
            const correoActual = '<?= $correo ?>';
            const contraNueva = formData.get('nueva_contra');
            
            if ((correoNuevo !== correoActual && correoNuevo) || contraNueva) {
                Swal.fire({
                    title: '<?= $translator->__("Verificación de seguridad") ?>',
                    text: '<?= $translator->__("Por favor ingresa tu contraseña actual para continuar") ?>',
                    input: 'password',
                    inputPlaceholder: '<?= $translator->__("Contraseña actual") ?>',
                    showCancelButton: true,
                    confirmButtonText: '<?= $translator->__("Verificar") ?>',
                    cancelButtonText: '<?= $translator->__("Cancelar") ?>',
                    inputValidator: (value) => {
                        if (!value) {
                            return '<?= $translator->__("Debes ingresar tu contraseña actual") ?>';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'perfil.php',
                            type: 'POST',
                            data: {
                                action: 'verificar_password',
                                password_actual: result.value
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    if (correoNuevo !== correoActual && correoNuevo) {
                                        mostrarModalVerificacion('correo', correoNuevo);
                                    } else if (contraNueva) {
                                        mostrarModalVerificacion('password', contraNueva);
                                    }
                                } else {
                                    Swal.fire({
                                        title: '<?= $translator->__("Error") ?>',
                                        text: response.error,
                                        icon: 'error',
                                        showConfirmButton: true
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: '<?= $translator->__("Error de conexión") ?>',
                                    text: '<?= $translator->__("No se pudo conectar con el servidor") ?>',
                                    icon: 'error',
                                    showConfirmButton: true
                                });
                            }
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: '<?= $translator->__("Verificación de seguridad") ?>',
                    text: '<?= $translator->__("Por favor ingresa tu contraseña actual para guardar los cambios") ?>',
                    input: 'password',
                    inputPlaceholder: '<?= $translator->__("Contraseña actual") ?>',
                    showCancelButton: true,
                    confirmButtonText: '<?= $translator->__("Verificar") ?>',
                    cancelButtonText: '<?= $translator->__("Cancelar") ?>',
                    inputValidator: (value) => {
                        if (!value) {
                            return '<?= $translator->__("Debes ingresar tu contraseña actual") ?>';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'perfil.php',
                            type: 'POST',
                            data: {
                                action: 'verificar_password',
                                password_actual: result.value
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    document.getElementById('form-perfil').submit();
                                } else {
                                    Swal.fire({
                                        title: '<?= $translator->__("Error") ?>',
                                        text: response.error,
                                        icon: 'error',
                                        showConfirmButton: true
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: '<?= $translator->__("Error de conexión") ?>',
                                    text: '<?= $translator->__("No se pudo conectar con el servidor") ?>',
                                    icon: 'error',
                                    showConfirmButton: true
                                });
                            }
                        });
                    }
                });
            }
            return false;
        }
        
        function verificarCodigo() {
            const codigo = $("#codigo_verificacion").val();
            const tipo = $("#tipo_verificacion").val();
            const nuevoValor = $("#nuevo_valor_verificacion").val();
            
            if (!codigo) {
                Swal.fire({
                    title: '<?= $translator->__("Error") ?>',
                    text: '<?= $translator->__("Debes ingresar el código de verificación") ?>',
                    icon: 'error',
                    showConfirmButton: true
                });
                return false;
            }
            
            $.ajax({
                url: 'perfil.php',
                type: 'POST',
                data: {
                    action: 'verificar_codigo',
                    codigo: codigo
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $("#verificacionModal").hide();
                        $(".modal").removeClass("active");
                        document.getElementById('form-perfil').submit();
                    } else {
                        Swal.fire({
                            title: '<?= $translator->__("Error") ?>',
                            text: response.error,
                            icon: 'error',
                            showConfirmButton: true
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: '<?= $translator->__("Error de conexión") ?>',
                        text: '<?= $translator->__("No se pudo conectar con el servidor") ?>',
                        icon: 'error',
                        showConfirmButton: true
                    });
                }
            });
            
            return false;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                mostrarPestana('perfil-info');
                document.querySelectorAll('input, select, textarea').forEach(el => {
                    el.classList.add('sharp-text');
                });
            }, 100);
            
            <?php if (!empty($message)): ?>
                Swal.fire({
                    icon: '<?= $messageType ?>',
                    title: '<?= $message ?>',
                    showConfirmButton: false,
                    timer: 3000
                });
            <?php endif; ?>
            
            $(".close").on("click", function () {
                $(this).closest(".modal").hide();
                $(".modal").removeClass("active");
            });
        });
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="perfil-container">
        <div class="perfil-header">
            <div class="perfil-avatar">
                <?php if (!empty($imagen_perfil)): ?>
                    <img src="../<?= htmlspecialchars($imagen_perfil) ?>" alt="<?= $translator->__("Foto de perfil") ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="perfil-info-header">
                <h1><?= htmlspecialchars("$nombre $primer_apellido $segundo_apellido") ?></h1>
                <p><?= htmlspecialchars($correo) ?></p>
                <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
                    <span class="perfil-badge"><?= $translator->__("Administrador") ?></span>
                <?php else: ?>
                    <span class="perfil-badge"><?= $translator->__("Usuario") ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="perfil-nav">
            <a href="#" data-tab="perfil-info" class="active" onclick="mostrarPestana('perfil-info'); return false;">
                <i class="fas fa-user"></i> <?= $translator->__("Mi Información") ?>
            </a>
            <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
            <a href="#" data-tab="perfil-publicaciones" onclick="mostrarPestana('perfil-publicaciones'); return false;">
                <i class="fas fa-newspaper"></i> <?= $translator->__("Mis Publicaciones") ?> (<?= count($publicaciones) ?>)
            </a>
            <?php endif; ?>
            <a href="#" data-tab="perfil-comentarios" onclick="mostrarPestana('perfil-comentarios'); return false;">
                <i class="fas fa-comments"></i> <?= $translator->__("Mis Comentarios") ?> (<?= count($comentarios) ?>)
            </a>
            <a href="../includes/cerrarsesion.php" class="cerrar-sesion">
                <i class="fas fa-sign-out-alt"></i> <?= $translator->__("Cerrar Sesión") ?>
            </a>
        </div>
        
        <div id="perfil-info" class="perfil-contenido">
            <h2><?= $translator->__("Actualizar mi información") ?></h2>
            
            <form id="form-perfil" action="../includes/guardar_usuario.php" method="POST" enctype="multipart/form-data" onsubmit="return verificarYProcesar()">
                <div class="form-group">
                    <label for="nombre"><?= $translator->__("Nombre") ?>:</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="primer_apellido"><?= $translator->__("Primer Apellido") ?>:</label>
                    <input type="text" id="primer_apellido" name="primer_apellido" value="<?= htmlspecialchars($primer_apellido) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="segundo_apellido"><?= $translator->__("Segundo Apellido") ?>:</label>
                    <input type="text" id="segundo_apellido" name="segundo_apellido" value="<?= htmlspecialchars($segundo_apellido) ?>">
                </div>
                
                <div class="form-group">
                    <label for="correo"><?= $translator->__("Correo Electrónico") ?>:</label>
                    <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required>
                    <small class="form-note"><?= $translator->__("Cambiar el correo electrónico requerirá verificación") ?></small>
                </div>
                
                <div class="form-group">
                    <label for="imagen"><?= $translator->__("Imagen de Perfil") ?>:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" onchange="mostrarVistaPrevia()">
                    <?php if (!empty($imagen_perfil)): ?>
                        <p class="imagen-actual"><?= $translator->__("Imagen actual") ?>: <a href="<?= htmlspecialchars($imagen_perfil) ?>" target="_blank"><?= basename($imagen_perfil) ?></a></p>
                    <?php endif; ?>
                    <img id="imagen-preview" src="#" alt="<?= $translator->__("Vista previa") ?>" style="display: none; max-width: 200px; margin-top: 10px;">
                </div>
                
                <div class="form-group">
                    <label for="nueva_contra"><?= $translator->__("Nueva Contraseña") ?> (<?= $translator->__("dejar en blanco para mantener la actual") ?>):</label>
                    <input type="password" id="nueva_contra" name="nueva_contra">
                    <small class="form-note"><?= $translator->__("Cambiar la contraseña requerirá verificación") ?></small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-save"></i> <?= $translator->__("Guardar Cambios") ?>
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
        <div id="perfil-publicaciones" class="perfil-contenido" style="display: none;">
            <h2><?= $translator->__("Mis Publicaciones") ?></h2>
            
            <?php if (empty($publicaciones)): ?>
                <div class="sin-contenido">
                    <i class="fas fa-newspaper"></i>
                    <p><?= $translator->__("Aún no has creado ninguna publicación.") ?></p>
                </div>
            <?php else: ?>
                <div class="lista-contenido">
                    <table class="tabla-contenido">
                        <thead>
                            <tr>
                                <th><?= $translator->__("Título") ?></th>
                                <th><?= $translator->__("Categoría") ?></th>
                                <th><?= $translator->__("Fecha") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publicaciones as $pub): ?>
                            <tr>
                                <td data-label="<?= $translator->__("Título") ?>"><?= htmlspecialchars($pub['titulo']) ?></td>
                                <td data-label="<?= $translator->__("Categoría") ?>"><?= htmlspecialchars($pub['nombre_categoria']) ?></td>
                                <td data-label="<?= $translator->__("Fecha") ?>"><?= $pub['fecha'] ?></td>
                                <td class="acciones" data-label="<?= $translator->__("Acciones") ?>">
                                    <a href="publicacion.php?id=<?= $pub['id_entrada'] ?>" class="btn-accion ver">
                                        <i class="fas fa-eye"></i> <?= $translator->__("Ver") ?>
                                    </a>
                                    <a href="editar_publicacion.php?id=<?= $pub['id_entrada'] ?>" class="btn-accion editar">
                                        <i class="fas fa-edit"></i> <?= $translator->__("Editar") ?>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmarEliminarPublicacion(<?= $pub['id_entrada'] ?>)" class="btn-accion eliminar">
                                        <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div id="perfil-comentarios" class="perfil-contenido" style="display: none;">
            <h2><?= $translator->__("Mis Comentarios") ?></h2>
            
            <?php if (empty($comentarios)): ?>
                <div class="sin-contenido">
                    <i class="fas fa-comments"></i>
                    <p><?= $translator->__("Aún no has realizado ningún comentario.") ?></p>
                </div>
            <?php else: ?>
                <div class="lista-contenido">
                    <table class="tabla-contenido">
                        <thead>
                            <tr>
                                <th><?= $translator->__("Comentario") ?></th>
                                <th><?= $translator->__("Publicación") ?></th>
                                <th><?= $translator->__("Fecha") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $com): ?>
                            <tr>
                                <td data-label="<?= $translator->__("Comentario") ?>"><?= htmlspecialchars(substr($com['descripcion'], 0, 100)) . (strlen($com['descripcion']) > 100 ? '...' : '') ?></td>
                                <td data-label="<?= $translator->__("Publicación") ?>"><?= htmlspecialchars($com['titulo']) ?></td>
                                <td data-label="<?= $translator->__("Fecha") ?>"><?= $com['fecha'] ?></td>
                                <td class="acciones" data-label="<?= $translator->__("Acciones") ?>">
                                    <a href="publicacion.php?id=<?= $com['id_entrada'] ?>#comentario-<?= $com['id_comentario'] ?>" class="btn-accion ver">
                                        <i class="fas fa-eye"></i> <?= $translator->__("Ver") ?>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmarEliminarComentario(<?= $com['id_comentario'] ?>)" class="btn-accion eliminar">
                                        <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de verificación -->
    <div id="verificacionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?= $translator->__("Verificación de seguridad") ?></h2>
            <p><?= $translator->__("Hemos enviado un código de verificación a tu correo electrónico. Por favor, ingrésalo para continuar:") ?></p>
            <form id="verificacionForm" onsubmit="return verificarCodigo()">
                <input type="hidden" id="tipo_verificacion" name="tipo_verificacion" value="">
                <input type="hidden" id="nuevo_valor_verificacion" name="nuevo_valor_verificacion" value="">
                <div class="form-group">
                    <label for="codigo_verificacion"><?= $translator->__("Código de verificación") ?>:</label>
                    <input type="text" id="codigo_verificacion" name="codigo_verificacion" placeholder="<?= $translator->__("Ingrese el código de 6 dígitos") ?>" required>
                </div>
                <div class="form-actions centered">
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-check"></i> <?= $translator->__("Verificar") ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>
