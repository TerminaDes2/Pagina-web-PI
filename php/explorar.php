<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

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
    // Reconstruir URL con los parámetros actuales
    $redirect = $_SERVER['PHP_SELF'];
    $params = [];
    
    if (isset($_GET['q']) && !empty($_GET['q'])) $params[] = 'q=' . urlencode($_GET['q']);
    if (isset($_GET['cat']) && is_numeric($_GET['cat'])) $params[] = 'cat=' . $_GET['cat'];
    if (isset($_GET['pagina']) && is_numeric($_GET['pagina'])) $params[] = 'pagina=' . $_GET['pagina'];
    if (isset($_GET['modo']) && in_array($_GET['modo'], ['buscar', 'categorias', 'noticias'])) $params[] = 'modo=' . $_GET['modo'];
    
    if (!empty($params)) {
        $redirect .= '?' . implode('&', $params);
    }
    
    header("Location: " . $redirect);
    exit();
}

// Determinar el modo de visualización (buscar, categorias, noticias)
$modo = isset($_GET['modo']) ? $_GET['modo'] : 'noticias';
if (!in_array($modo, ['buscar', 'categorias', 'noticias'])) {
    $modo = 'noticias'; // Valor predeterminado
}

// Variables para almacenar resultados
$articulos = [];
$total_articulos = 0;
$total_paginas = 1;

// Configuración de paginación
$articulos_por_pagina = 10; // Cambiado de 9 a 10
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$inicio = ($pagina_actual - 1) * $articulos_por_pagina;

// Procesar según el modo seleccionado
if ($modo === 'buscar') {
    // Lógica de búsqueda
    $busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (!empty($busqueda)) {
        $termino_busqueda = "%{$busqueda}%";
        
        // Contar total de resultados para paginación
        $sql_total = "SELECT COUNT(DISTINCT e.id_entrada) as total 
                      FROM entradas e
                      WHERE e.titulo LIKE ? OR e.contenido LIKE ?";
        $stmt_total = $conn->prepare($sql_total);
        $stmt_total->bind_param("ss", $termino_busqueda, $termino_busqueda);
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $fila_total = $result_total->fetch_assoc();
        $total_articulos = $fila_total['total'];
        $total_paginas = ceil($total_articulos / $articulos_por_pagina);
        $stmt_total->close();
        
        // Consulta para obtener resultados paginados
        $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, e.categoria, 
                   u.nombre as autor, i.imagen as imagen_principal, c.categoria as nombre_categoria
                FROM entradas e 
                LEFT JOIN (SELECT id_entrada, MIN(imagen) as imagen FROM imagenes GROUP BY id_entrada) i 
                    ON i.id_entrada = e.id_entrada
                LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
                LEFT JOIN categorias c ON e.categoria = c.id_categoria
                WHERE e.titulo LIKE ? OR e.contenido LIKE ?
                GROUP BY e.id_entrada
                ORDER BY e.fecha DESC
                LIMIT ?, ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $termino_busqueda, $termino_busqueda, $inicio, $articulos_por_pagina);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $articulos[] = procesarArticulo($row, $translator); // Añadir al array $articulos
        }
        $stmt->close();
        
        // Configuración de encabezados
        $titulo_pagina = $translator->__("Resultados para") . ': "' . htmlspecialchars($busqueda) . '"';
        $descripcion_pagina = sprintf($translator->__("%d resultados encontrados"), $total_articulos);
    } else {
        $titulo_pagina = $translator->__("Búsqueda");
        $descripcion_pagina = $translator->__("Introduce un término de búsqueda");
    }
} elseif ($modo === 'categorias') {
    // Verificar si se especificó una categoría
    if (isset($_GET['cat']) && is_numeric($_GET['cat'])) {
        $id_categoria = intval($_GET['cat']);
        
        // Obtener nombre de la categoría
        $stmt = $conn->prepare("SELECT categoria FROM categorias WHERE id_categoria = ?");
        $stmt->bind_param("i", $id_categoria);
        $stmt->execute();
        $stmt->bind_result($nombre_categoria);
        $categoria_existe = $stmt->fetch();
        $stmt->close();
        
        if (!$categoria_existe) {
            $titulo_pagina = $translator->__("Categoría no encontrada");
            $descripcion_pagina = $translator->__("La categoría solicitada no existe");
        } else {
            // Contar total de artículos en esta categoría
            $sql_total = "SELECT COUNT(*) as total FROM entradas WHERE categoria = ?";
            $stmt_total = $conn->prepare($sql_total);
            $stmt_total->bind_param("i", $id_categoria);
            $stmt_total->execute();
            $result_total = $stmt_total->get_result();
            $fila_total = $result_total->fetch_assoc();
            $total_articulos = $fila_total['total'];
            $total_paginas = ceil($total_articulos / $articulos_por_pagina);
            $stmt_total->close();
            
            // Obtener artículos de esta categoría
            $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, e.categoria, 
                   u.nombre as autor, i.imagen as imagen_principal, c.categoria as nombre_categoria
                   FROM entradas e 
                   LEFT JOIN (SELECT id_entrada, MIN(imagen) as imagen FROM imagenes GROUP BY id_entrada) i 
                       ON i.id_entrada = e.id_entrada
                   LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
                   LEFT JOIN categorias c ON e.categoria = c.id_categoria
                   WHERE e.categoria = ? 
                   ORDER BY e.fecha DESC
                   LIMIT ?, ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $id_categoria, $inicio, $articulos_por_pagina);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $articulos[] = procesarArticulo($row, $translator); // Añadir al array $articulos
            }
            $stmt->close();
            
            $titulo_pagina = htmlspecialchars($nombre_categoria);
            $descripcion_pagina = sprintf($translator->__("%d artículos en esta categoría"), $total_articulos);
        }
    } else {
        // Mostrar todas las categorías
        $sqlCategorias = "SELECT c.id_categoria, c.categoria, '' as descripcion, 
                      COUNT(e.id_entrada) as num_articulos,
                      i.imagen as imagen_categoria 
                      FROM categorias c 
                      LEFT JOIN entradas e ON e.categoria = c.id_categoria 
                      LEFT JOIN (
                          SELECT id_entrada, MIN(imagen) as imagen 
                          FROM imagenes 
                          GROUP BY id_entrada
                      ) i ON i.id_entrada = e.id_entrada
                      GROUP BY c.id_categoria
                      ORDER BY c.categoria ASC";
        
        $resultCategorias = $conn->query($sqlCategorias);
        $categorias = [];
        
        if ($resultCategorias === false) {
            $error = "Error en la consulta de categorías: " . $conn->error;
        } else {
            while ($row = $resultCategorias->fetch_assoc()) {
                $row['categoria'] = $translator->traducirTexto($row['categoria']);
                $row['descripcion'] = $translator->traducirTexto($row['descripcion']);
                $categorias[] = $row;
            }
        }
        
        $titulo_pagina = $translator->__("Explorar Categorías");
        $descripcion_pagina = $translator->__("Descubre todos nuestros artículos organizados por temas");
    }
} else {
    // Modo noticias (listado general)
    // Contar total de artículos para paginación
    $sql_total = "SELECT COUNT(*) as total FROM entradas";
    $result_total = $conn->query($sql_total);
    $fila_total = $result_total->fetch_assoc();
    $total_articulos = $fila_total['total'];
    $total_paginas = ceil($total_articulos / $articulos_por_pagina);

    // Consulta para obtener todas las publicaciones con paginación
    $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, e.categoria, 
             u.nombre as autor, i.imagen as imagen_principal, c.categoria as nombre_categoria
             FROM entradas e 
             LEFT JOIN (SELECT id_entrada, MIN(imagen) as imagen FROM imagenes GROUP BY id_entrada) i 
                 ON i.id_entrada = e.id_entrada 
             LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN categorias c ON e.categoria = c.id_categoria
             ORDER BY e.fecha DESC
             LIMIT ?, ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $inicio, $articulos_por_pagina);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $articulos[] = procesarArticulo($row, $translator); // Añadir al array $articulos
    }
    $stmt->close();
    
    $titulo_pagina = $translator->__("Todas las Noticias");
    $descripcion_pagina = $translator->__("Descubre todas nuestras publicaciones");
}

// Función para procesar artículos
function procesarArticulo(&$row, $translator) {
    $row['titulo'] = $translator->traducirTexto($row['titulo']);
    $row['contenido'] = $translator->traducirTexto($row['contenido']);
    $row['cita'] = $translator->traducirTexto($row['cita']);
    
    // Eliminar etiquetas h2 y su contenido antes de crear el extracto
    $contenido_limpio = preg_replace('/<h2>.*?<\/h2>/is', '', $row['contenido']);
    $contenido_limpio = strip_tags($contenido_limpio);
    
    // Obtener un extracto del contenido (primeras 20 palabras o menos)
    $palabras = explode(' ', $contenido_limpio);
    $palabras = array_slice($palabras, 0, 25);
    $row['extracto'] = implode(' ', $palabras) . (count(explode(' ', $contenido_limpio)) > 25 ? '...' : '');
    
    // Asegurar que la imagen_principal esté disponible (consistencia de nombres)
    if (isset($row['imagen']) && !isset($row['imagen_principal'])) {
        $row['imagen_principal'] = $row['imagen'];
    }
    
    return $row;
}

// Función para generar la URL de paginación manteniendo los demás parámetros
function getPaginationUrl($pagina, $modo, $busqueda = '', $categoria = 0) {
    $url = '?pagina=' . $pagina;
    
    if (!empty($modo)) $url .= '&modo=' . $modo;
    if (!empty($busqueda)) $url .= '&q=' . urlencode($busqueda);
    if ($categoria > 0) $url .= '&cat=' . $categoria;
    
    return $url;
}

// Obtener todas las categorías para el filtro
$categorias_filtro = [];
$sql_categorias_filtro = "SELECT id_categoria, categoria FROM categorias ORDER BY categoria";
$result_categorias_filtro = $conn->query($sql_categorias_filtro);

if ($result_categorias_filtro && $result_categorias_filtro->num_rows > 0) {
    while ($row = $result_categorias_filtro->fetch_assoc()) {
        $row['categoria'] = $translator->traducirTexto($row['categoria']);
        $categorias_filtro[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - Voces del Proceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/categorias.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos específicos para la página de exploración */
        .explorar-header {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e6ea 100%);
            padding: 40px 0 30px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            border-radius: 0 0 30px 30px;
        }
        
        .explorar-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #1d231e;
            font-weight: 700;
        }
        
        .explorar-header p {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 25px;
        }
        
        .explorar-filtros {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        
        .filtro-busqueda {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .filtro-busqueda input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filtro-busqueda input:focus {
            border-color: #719743;
            box-shadow: 0 0 0 3px rgba(113, 151, 67, 0.2);
            outline: none;
        }
        
        .filtro-busqueda i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: color 0.3s ease;
        }
        
        .filtro-busqueda input:focus + i {
            color: #719743;
        }
        
        .filtro-categorias select {
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 1rem;
            background-color: white;
            min-width: 180px;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: calc(100% - 15px) center;
            padding-right: 40px;
            cursor: pointer;
        }
        
        .filtro-categorias select:focus {
            border-color: #719743;
            box-shadow: 0 0 0 3px rgba(113, 151, 67, 0.2);
            outline: none;
        }
        
        .filtro-modos {
            display: flex;
            gap: 10px;
        }
        
        .filtro-modo-btn {
            background-color: #f0f4f8;
            border: 1px solid #ddd;
            padding: 10px 15px;
            border-radius: 30px;
            font-size: 0.95rem;
            color: #555;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filtro-modo-btn:hover {
            background-color: #e2e6ea;
        }
        
        .filtro-modo-btn.active {
            background-color: #719743;
            color: white;
            border-color: #719743;
        }
        
        /* Estilos para las tarjetas de artículos - MODIFICADO para diseño horizontal */
        .articulos-grid {
            display: flex;
            flex-direction: column;
            gap: 25px;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto 40px;
        }
        
        .articulo-card {
            background-color: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: row; /* Cambiado a horizontal */
            height: auto; /* Altura automática */
            border: 1px solid rgba(225,225,225,0.3);
        }
        
        .articulo-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.18);
        }
        
        .articulo-imagen {
            width: 300px; /* Ancho fijo para la imagen */
            height: 220px;
            min-width: 300px; /* Asegurar que la imagen no se comprima */
            overflow: hidden;
            position: relative;
        }
        
        .articulo-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .articulo-card:hover .articulo-imagen img {
            transform: scale(1.05);
        }
        
        .sin-imagen {
            background-color: #f5f5f7;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .sin-imagen i {
            font-size: 4em;
            color: #ccc;
        }
        
        .categoria-tag {
            position: absolute;
            top: 15px;
            left: 15px;
            background-color: rgba(255, 255, 255, 0.85);
            color: #555;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .articulo-card:hover .categoria-tag {
            background-color: rgba(113, 151, 67, 0.9);
            color: white;
        }
        
        .articulo-contenido {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex: 1;
            justify-content: center; /* Centrado vertical */
        }
        
        .articulo-titulo {
            font-size: 1.5rem;
            line-height: 1.3;
            margin-bottom: 15px;
            font-weight: 700;
            color: #1d231e;
            transition: color 0.3s ease;
        }
        
        .articulo-card:hover .articulo-titulo {
            color: #719743;
        }
        
        .articulo-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 15px;
            gap: 12px;
        }
        
        .articulo-meta .autor, 
        .articulo-meta .fecha {
            display: flex;
            align-items: center;
            gap: 5px;
            background-color: #f5f5f7;
            padding: 3px 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .articulo-meta .autor:hover, 
        .articulo-meta .fecha:hover {
            background-color: #e8eaee;
        }
        
        .articulo-extracto {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .btn-leer-mas {
            align-self: flex-start; /* Alineado a la izquierda */
            padding: 10px 25px;
            background-color: #f5f5f7;
            color: #555;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid #eee;
            margin-top: 10px;
        }
        
        .articulo-card:hover .btn-leer-mas {
            background-color: #719743;
            color: white;
            border-color: #719743;
        }
        
        /* Estilos para la paginación */
        .paginacion {
            display: flex;
            justify-content: center;
            margin: 40px 0;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .paginacion a, 
        .paginacion span {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #ddd;
            color: #555;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .paginacion a:hover {
            background-color: #f5f5f7;
            border-color: #ccc;
        }
        
        .paginacion .actual {
            background-color: #719743;
            color: white;
            border-color: #719743;
        }
        
        .paginacion .puntos {
            border: none;
            background: none;
            width: auto;
            padding: 0 5px;
        }
        
        /* Estilos para las tarjetas de categorías */
        .categorias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 0 20px;
            max-width: 1400px;
            margin: 0 auto 40px;
        }
        
        .categoria-card {
            background-color: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(225,225,225,0.3);
            position: relative;
        }
        
        .categoria-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.18);
        }
        
        .categoria-imagen {
            height: 180px;
            overflow: hidden;
            position: relative;
        }
        
        .categoria-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .categoria-card:hover .categoria-imagen img {
            transform: scale(1.05);
        }
        
        .categoria-imagen .sin-imagen {
            background: linear-gradient(135deg, #f5f5f7 0%, #eaeaea 100%);
            color: #aaa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        
        .categoria-imagen .sin-imagen i {
            font-size: 3em;
        }
        
        .categoria-contador {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background-color: rgba(255, 255, 255, 0.85);
            color: #555;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .categoria-card:hover .categoria-contador {
            background-color: rgba(113, 151, 67, 0.9);
            color: white;
        }
        
        .categoria-contenido {
            padding: 25px;
            text-align: center;
        }
        
        .categoria-titulo {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1d231e;
            transition: color 0.3s ease;
        }
        
        .categoria-card:hover .categoria-titulo {
            color: #719743;
        }
        
        .categoria-descripcion {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* Estilos para mensajes de resultados vacíos */
        .sin-resultados {
            text-align: center;
            padding: 60px 20px;
            max-width: 600px;
            margin: 0 auto;
            background-color: #f9f9f9;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .sin-resultados i {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }
        
        .sin-resultados h3 {
            font-size: 1.5rem;
            color: #555;
            margin-bottom: 15px;
        }
        
        .sin-resultados p {
            color: #777;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        /* Adaptaciones para móviles */
        @media (max-width: 768px) {
            .explorar-header {
                padding: 30px 0 20px;
            }
            
            .explorar-header h1 {
                font-size: 2rem;
            }
            
            .explorar-filtros {
                flex-direction: column;
                gap: 15px;
            }
            
            .filtro-busqueda {
                max-width: none;
            }
            
            .filtro-modos {
                justify-content: center;
            }
            
            .articulos-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                grid-auto-flow: row; /* En móvil volvemos a filas para mejor visualización */
                height: auto; /* Altura automática en móvil */
                gap: 20px;
            }
            
            .articulo-imagen {
                height: 180px;
            }
            
            .articulo-contenido {
                padding: 20px;
            }
            
            .articulo-titulo {
                font-size: 1.3rem;
            }
            
            .categoria-card {
                height: auto;
            }
        }
        
        @media (max-width: 480px) {
            .articulos-grid, .categorias-grid {
                grid-template-columns: 1fr;
                grid-auto-flow: row;
                height: auto;
            }
            
            .filtro-modo-btn {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            
            .articulo-imagen {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="explorar-header">
        <h1><?= $titulo_pagina ?></h1>
        <p><?= $descripcion_pagina ?></p>
        
        <div class="explorar-filtros">
            <form action="explorar.php" method="GET" class="filtro-busqueda">
                <input type="hidden" name="modo" value="buscar">
                <input type="text" name="q" placeholder="<?= $translator->__("Buscar artículos...") ?>" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                <i class="fas fa-search"></i>
            </form>
            
            <div class="filtro-categorias">
                <select id="filtro-categoria" onchange="cambiarCategoria(this.value)">
                    <option value="0"><?= $translator->__("Todas las categorías") ?></option>
                    <?php foreach($categorias_filtro as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>" <?= isset($_GET['cat']) && $_GET['cat'] == $cat['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filtro-modos">
                <a href="explorar.php?modo=noticias" class="filtro-modo-btn <?= $modo === 'noticias' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper"></i> <?= $translator->__("Noticias") ?>
                </a>
                <a href="explorar.php?modo=categorias" class="filtro-modo-btn <?= $modo === 'categorias' ? 'active' : '' ?>">
                    <i class="fas fa-folder-open"></i> <?= $translator->__("Categorías") ?>
                </a>
            </div>
        </div>
    </section>

    <?php if ($modo === 'categorias' && (!isset($_GET['cat']) || !is_numeric($_GET['cat']))): ?>
        <!-- Vista de todas las categorías -->
        <?php if (!empty($categorias)): ?>
            <div class="categorias-grid">
                <?php foreach ($categorias as $categoria): ?>
                    <a href="explorar.php?modo=categorias&cat=<?= $categoria['id_categoria'] ?>" class="categoria-card">
                        <div class="categoria-imagen">
                            <?php if (!empty($categoria['imagen_categoria'])): ?>
                                <img src="<?= htmlspecialchars($categoria['imagen_categoria']) ?>" alt="<?= htmlspecialchars($categoria['categoria']) ?>">
                            <?php else: ?>
                                <div class="sin-imagen">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                            <?php endif; ?>
                            <div class="categoria-contador">
                                <?= $categoria['num_articulos'] ?> <?= $translator->__("artículos") ?>
                            </div>
                        </div>
                        <div class="categoria-contenido">
                            <h2 class="categoria-titulo"><?= htmlspecialchars($categoria['categoria']) ?></h2>
                            <?php if (!empty($categoria['descripcion'])): ?>
                                <p class="categoria-descripcion"><?= htmlspecialchars($categoria['descripcion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sin-resultados">
                <i class="fas fa-folder-open"></i>
                <h3><?= $translator->__("No hay categorías disponibles") ?></h3>
                <p><?= $translator->__("No se encontraron categorías en el sistema.") ?></p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Vista de artículos (búsqueda, categoría específica o listado general) -->
        <?php if (empty($articulos) && ($modo === 'buscar' && !empty($busqueda) || $modo !== 'buscar')): ?>
            <div class="sin-resultados">
                <?php if ($modo === 'buscar'): ?>
                    <i class="fas fa-search"></i>
                    <h3><?= $translator->__("No se encontraron resultados") ?></h3>
                    <p><?= sprintf($translator->__("No hay artículos que coincidan con '%s'"), htmlspecialchars($busqueda)) ?></p>
                    <a href="explorar.php?modo=noticias" class="btn-leer-mas"><?= $translator->__("Ver todas las noticias") ?></a>
                <?php elseif (isset($_GET['cat']) && is_numeric($_GET['cat'])): ?>
                    <i class="fas fa-folder-open"></i>
                    <h3><?= $translator->__("No hay artículos en esta categoría") ?></h3>
                    <p><?= $translator->__("Esta categoría no tiene publicaciones actualmente.") ?></p>
                    <a href="explorar.php?modo=noticias" class="btn-leer-mas"><?= $translator->__("Ver todas las noticias") ?></a>
                <?php else: ?>
                    <i class="fas fa-newspaper"></i>
                    <h3><?= $translator->__("No hay noticias disponibles") ?></h3>
                    <p><?= $translator->__("No se encontraron artículos publicados.") ?></p>
                <?php endif; ?>
            </div>
        <?php elseif ($modo === 'buscar' && empty($busqueda)): ?>
            <div class="sin-resultados">
                <i class="fas fa-search"></i>
                <h3><?= $translator->__("Busca en nuestro contenido") ?></h3>
                <p><?= $translator->__("Introduce un término de búsqueda para encontrar artículos.") ?></p>
                <a href="explorar.php?modo=noticias" class="btn-leer-mas"><?= $translator->__("Ver todas las noticias") ?></a>
            </div>
        <?php elseif (!empty($articulos)): ?>
            <div class="articulos-grid">
                <?php foreach ($articulos as $articulo): ?>
                    <article class="articulo-card">
                        <div class="articulo-imagen">
                            <?php if (!empty($articulo['imagen_principal'])): ?>
                                <img src="<?= htmlspecialchars($articulo['imagen_principal']) ?>" alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                            <?php else: ?>
                                <div class="sin-imagen">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($articulo['nombre_categoria'])): ?>
                                <div class="categoria-tag"><?= htmlspecialchars($articulo['nombre_categoria']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="articulo-contenido">
                            <h2 class="articulo-titulo"><?= htmlspecialchars($articulo['titulo']) ?></h2>
                            <div class="articulo-meta">
                                <?php if (!empty($articulo['autor'])): ?>
                                <span class="autor"><i class="fas fa-user"></i> <?= htmlspecialchars($articulo['autor']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($articulo['fecha'])): ?>
                                <span class="fecha"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($articulo['fecha'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="articulo-extracto">
                                <?= htmlspecialchars($articulo['extracto']) ?>
                            </p>
                            <a href="publicacion.php?id=<?= $articulo['id_entrada'] ?>" class="btn-leer-mas"><?= $translator->__("Leer más") ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="<?= getPaginationUrl($pagina_actual - 1, $modo, isset($busqueda) ? $busqueda : '', isset($_GET['cat']) ? intval($_GET['cat']) : 0) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Mostrar un número limitado de enlaces a páginas
                    $inicio_rango = max(1, $pagina_actual - 2);
                    $fin_rango = min($total_paginas, $pagina_actual + 2);
                    
                    if ($inicio_rango > 1) {
                        echo '<a href="' . getPaginationUrl(1, $modo, isset($busqueda) ? $busqueda : '', isset($_GET['cat']) ? intval($_GET['cat']) : 0) . '">1</a>';
                        if ($inicio_rango > 2) echo '<span class="puntos">...</span>';
                    }
                    
                    for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                        if ($i == $pagina_actual) {
                            echo '<span class="actual">' . $i . '</span>';
                        } else {
                            echo '<a href="' . getPaginationUrl($i, $modo, isset($busqueda) ? $busqueda : '', isset($_GET['cat']) ? intval($_GET['cat']) : 0) . '">' . $i . '</a>';
                        }
                    }
                    
                    if ($fin_rango < $total_paginas) {
                        if ($fin_rango < $total_paginas - 1) echo '<span class="puntos">...</span>';
                        echo '<a href="' . getPaginationUrl($total_paginas, $modo, isset($busqueda) ? $busqueda : '', isset($_GET['cat']) ? intval($_GET['cat']) : 0) . '">' . $total_paginas . '</a>';
                    }
                    ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="<?= getPaginationUrl($pagina_actual + 1, $modo, isset($busqueda) ? $busqueda : '', isset($_GET['cat']) ? intval($_GET['cat']) : 0) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Función para cambiar de categoría
        function cambiarCategoria(categoriaId) {
            const nuevaURL = new URL(window.location.href);
            
            // Configurar el modo
            nuevaURL.searchParams.set('modo', 'categorias');
            
            // Manejar la categoría
            if (categoriaId && categoriaId != "0") {
                nuevaURL.searchParams.set('cat', categoriaId);
            } else {
                nuevaURL.searchParams.delete('cat');
            }
            
            // Quitar la página
            nuevaURL.searchParams.delete('pagina');
            
            // Redirigir
            window.location.href = nuevaURL.toString();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
