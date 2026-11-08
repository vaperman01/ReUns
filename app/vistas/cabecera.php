<?php 
require_once __DIR__ . '/../ayudas/bootstrap.php'; 
$usr = usuario_actual();
$titulo = ($titulo ?? 'ReUns') . ' | ReUns'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?></title>
    <link rel="icon" href="<?= BASE ?>/assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/social.css">
    <style>
        :root {
            --tierra-primario: #573f32;       
            --tierra-primario-hover: #433026; 
            --tierra-claro: #f4f0ec;          
            --tierra-acento: #a36b53;         
            --texto-oscuro: #2c2421;
            --borde-suave: #e6dfdb;
        }

        .cab { 
            background-color: #ffffff; 
            box-shadow: 0 4px 20px rgba(87, 63, 50, 0.05); 
            position: sticky; 
            top: 0; 
            z-index: 1030; 
            /* Sin bordes para que no se separe */
            border: none;
        }
        .cab__contenedor-interno {
            max-width: 1280px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .cab__fila { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding-top: 0.85rem; 
            padding-bottom: 0.85rem; 
            gap: 1.5rem; 
        }
        
        /* Buscador compacto cálido */
        .buscador-compacto { 
            position: relative; 
            flex: 1; 
            max-width: 500px; 
        }
        .buscador-compacto input {
            border-radius: 50rem;
            padding: 0.55rem 1rem 0.55rem 2.6rem;
            border: 1px solid var(--borde-suave);
            background-color: var(--tierra-claro);
            transition: all 0.2s ease;
            width: 100%;
            font-size: 0.9rem;
            color: var(--texto-oscuro);
        }
        .buscador-compacto input:focus {
            background-color: #fff;
            border-color: var(--tierra-primario);
            box-shadow: 0 0 0 3px rgba(87, 63, 50, 0.1);
            outline: none;
        }
        .buscador-compacto .buscador-icono {
            position: absolute;
            left: 0.95rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8c7a70;
            pointer-events: none;
        }

        /* Barra de categorías integrada y sin cortes */
        .cats-wrapper {
            background-color: #ffffff;
            padding-bottom: 0.5rem;
        }
        .cats-nav {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            white-space: nowrap;
            padding: 0.2rem 0;
            scrollbar-width: none; 
            align-items: center;
        }
        .cats-nav::-webkit-scrollbar { display: none; }
        .cats-nav a {
            color: #6b5b53;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.35rem 0.85rem;
            border-radius: 50rem;
            background-color: #faf8f6;
            border: 1px solid var(--borde-suave);
            transition: all 0.2s ease;
        }
        .cats-nav a:hover, .cats-nav a.activo {
            color: #ffffff;
            background-color: var(--tierra-primario);
            border-color: var(--tierra-primario);
            font-weight: 600;
        }
        .cats__trueque { 
            color: var(--tierra-acento) !important; 
            border-color: #ebd7d0 !important;
            background-color: #fcf6f4 !important;
        }
        .cats__trueque:hover, .cats__trueque.activo {
            background-color: var(--tierra-acento) !important;
            color: #ffffff !important;
            border-color: var(--tierra-acento) !important;
        }

        /* Acciones de Usuario */
        .cab__acciones { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
        }
        .insignia-badge { 
            font-size: 0.65rem; 
            padding: 0.25em 0.5em; 
            vertical-align: top; 
        }

        /* Botón de Publicar (Acción Principal Cálida) */
        .btn-publicar-calido {
            background-color: var(--tierra-primario);
            border: none;
            color: white;
            transition: all 0.2s ease;
        }
        .btn-publicar-calido:hover {
            background-color: var(--tierra-primario-hover);
            color: white;
            box-shadow: 0 4px 10px rgba(87, 63, 50, 0.2);
        }

        /* Botón de perfil */
        .btn-perfil-toggle {
            background-color: var(--tierra-claro);
            border: 1px solid var(--borde-suave);
            color: var(--texto-oscuro);
            border-radius: 50rem;
            padding: 0.35rem 1rem 0.35rem 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }
        .avatar-circulo {
            width: 30px;
            height: 30px;
            background-color: var(--tierra-primario);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* Menú Hover Inteligente con Tarjeta de Cuenta */
        .menu-usuario-hover-container {
            position: relative;
            display: inline-block;
        }
        .menu-usuario-hover-container::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 15px;
        }
        .menu-desplegable-flotante {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 245px;
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 15px 35px rgba(87, 63, 50, 0.12);
            border: 1px solid var(--borde-suave);
            padding: 0.5rem;
            z-index: 1050;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .menu-usuario-hover-container:hover .menu-desplegable-flotante {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .cuenta-banner {
            background: linear-gradient(135deg, #fbf9f7, var(--tierra-claro));
            border: 1px solid var(--borde-suave);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
        }
        .cuenta-etiqueta {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #8c7a70;
            font-weight: 600;
            display: block;
            margin-bottom: 0.1rem;
        }
        .cuenta-usuario-nombre {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--texto-oscuro);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .punto-activo {
            width: 7px;
            height: 7px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.4);
        }
        .dropdown-item-ux {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.55rem 0.85rem;
            font-size: 0.9rem;
            color: var(--texto-oscuro);
            font-weight: 500;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .dropdown-item-ux:hover {
            background-color: var(--tierra-claro);
            color: var(--tierra-primario);
            transform: translateX(4px);
            font-weight: 600;
        }
    </style>
</head>
<body>
<a class="saltar" href="#contenido">Ir al contenido</a>

<header class="cab">
  <div class="cab__contenedor-interno">
    <div class="cab__fila">
      <!-- Logo -->
      <a href="<?= BASE ?>/index.php" class="cab__logo">
          <img src="<?= BASE ?>/assets/img/logo.svg" alt="ReUns" width="130" height="36">
      </a>
      
      <!-- Buscador compacto -->
      <form class="buscador-compacto" action="<?= BASE ?>/paginas/catalogo.php" method="get" role="search">
        <span class="buscador-icono">🔍</span>
        <label class="solo-lector" for="q">Buscar productos</label>
        <input id="q" name="q" type="search" placeholder="Busca laptops, bicicletas, libros..." value="<?= e($_GET['q'] ?? '') ?>">
      </form>
      
      <!-- Acciones de Usuario -->
      <nav class="cab__acciones" aria-label="Cuenta">
        <?php if ($usr): 
          $nNot = function_exists('contar_notificaciones_no_leidas') ? contar_notificaciones_no_leidas((int)$usr['id']) : 0;
          $nMsg = function_exists('contar_mensajes_no_leidos') ? contar_mensajes_no_leidos((int)$usr['id']) : 0;
          $nOp  = function_exists('contar_pendientes_recibidas') ? contar_pendientes_recibidas((int)$usr['id']) : 0;
          $inicialUsuario = mb_strtoupper(mb_substr($usr['nombre'], 0, 1));
        ?>
          <!-- Botón de Acción Principal -->
          <a class="btn btn-publicar-calido btn-sm px-4 rounded-pill fw-semibold shadow-sm" href="<?= BASE ?>/paginas/publicar.php">Publicar</a>
          
          <!-- Notificaciones rápidas -->
          <a href="<?= BASE ?>/paginas/notificaciones.php" class="position-relative text-dark text-decoration-none p-2" aria-label="Notificaciones">
              <span style="font-size: 1.25rem;">🔔</span>
              <span class="position-absolute top-25 start-75 translate-middle badge rounded-pill bg-danger insignia-badge" <?= $nNot ? '' : 'hidden' ?> data-contador="notificaciones">
                  <?= $nNot ?>
              </span>
          </a>

          <!-- Menú Desplegable por Hover con Tarjeta de Cuenta Rediseñada -->
          <div class="menu-usuario-hover-container">
              <div class="btn-perfil-toggle">
                  <span class="avatar-circulo"><?= e($inicialUsuario) ?></span>
                  <span class="text-truncate d-none d-sm-inline" style="max-width: 110px;"><?= e($usr['nombre']) ?></span>
              </div>
              
              <div class="menu-desplegable-flotante shadow">
                  <div class="cuenta-banner">
                      <span class="cuenta-etiqueta">Cuenta activa</span>
                      <span class="cuenta-usuario-nombre text-truncate">
                          <span class="punto-activo"></span>
                          <?= e($usr['nombre']) ?>
                      </span>
                  </div>

                  <a class="dropdown-item-ux" href="<?= BASE ?>/paginas/perfil.php">Mi Perfil</a>
                  <a class="dropdown-item-ux" href="<?= BASE ?>/paginas/mis_productos.php">Mis productos</a>
                  <a class="dropdown-item-ux" href="<?= BASE ?>/paginas/favoritos.php">Favoritos</a>
                  <a class="dropdown-item-ux" href="<?= BASE ?>/paginas/operaciones.php">
                      <span>Operaciones</span>
                      <span class="badge bg-warning text-dark insignia-badge" <?= $nOp ? '' : 'hidden' ?> data-contador="operaciones"><?= $nOp ?></span>
                  </a>
                  <a class="dropdown-item-ux" href="<?= BASE ?>/paginas/mensajes.php">
                      <span>Mensajes</span>
                      <span class="badge bg-secondary insignia-badge" <?= $nMsg ? '' : 'hidden' ?> data-contador="mensajes"><?= $nMsg ?></span>
                  </a>

                  <?php if (in_array($usr['rol'], ['admin', 'moderador'], true)): ?>
                      <hr class="dropdown-divider my-1">
                      <a class="dropdown-item-ux fw-semibold text-danger" href="<?= BASE ?>/admin/index.php">Panel Admin</a>
                  <?php endif; ?>

                  <hr class="dropdown-divider my-1">
                  <div class="px-2 py-1">
                      <form action="<?= BASE ?>/paginas/logout.php" method="post">
                          <?= csrf_campo() ?>
                          <button class="btn btn-outline-secondary btn-sm w-100 rounded-pill" type="submit">Salir</button>
                      </form>
                  </div>
              </div>
          </div>

        <?php else: ?>
          <a class="btn btn-outline-secondary btn-sm rounded-pill px-3" href="<?= BASE ?>/paginas/login.php">Ingresar</a>
          <a class="btn btn-publicar-calido btn-sm rounded-pill px-3 shadow-sm" href="<?= BASE ?>/paginas/registro.php">Crear cuenta</a>
        <?php endif; ?>
      </nav> 
    </div>
  </div>

  <!-- Barra de Categorías totalmente integrada al header -->
  <div class="cats-wrapper">
      <div class="cab__contenedor-interno">
          <nav class="cats-nav" aria-label="Categorías">
              <a href="<?= BASE ?>/paginas/catalogo.php" class="<?= !isset($_GET['cat']) && empty($_GET['mod']) ? 'activo' : '' ?>">Todo</a>
              <?php foreach (categorias() as $k => [$n, $i]): ?>
                  <a href="<?= BASE ?>/paginas/catalogo.php?cat=<?= $k ?>" class="<?= (isset($_GET['cat']) && $_GET['cat'] == $k) ? 'activo' : '' ?>">
                      <?= $i . ' ' . e($n) ?>
                  </a>
              <?php endforeach; ?>
              <a href="<?= BASE ?>/paginas/catalogo.php?mod=intercambio" class="cats__trueque <?= (isset($_GET['mod']) && $_GET['mod'] == 'intercambio') ? 'activo' : '' ?>">Intercambios</a>
          </nav>
      </div>
  </div>
</header>

<main id="contenido">
<div class="contenedor mt-3">
    <?php mostrar_flash(); ?>
    <?php if ($usr && !$usr['email_verificado_en']): ?>
        <p class="aviso aviso--info">
            Verifica tu correo para poder publicar. 
            <a href="<?= BASE ?>/paginas/perfil.php">Reenviar correo</a>
        </p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>