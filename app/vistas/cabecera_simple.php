<?php require_once __DIR__.'/../ayudas/bootstrap.php';
$titulo = ($titulo ?? 'ReUns') . ' | ReUns'; ?>
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Estilos propios -->
    <link rel="stylesheet" href="<?= BASE ?>/assets/css/base.css">
</head>
<body class="bg-light">
<a class="saltar" href="#contenido">Ir al contenido</a>

<!-- Cabecera con margen de 24px arriba para bajar el logo a su posición de la izquierda -->
<header class="bg-white border-bottom shadow-sm mb-4" style="padding-top: 24px; padding-bottom: 20px; margin-top: 0;">
    <div class="contenedor" style="display: flex; align-items: center; justify-content: flex-start;">
        <a href="<?= BASE ?>/index.php" style="display: inline-block;">
            <img src="<?= BASE ?>/assets/img/logo.svg" alt="ReUns" width="140" height="40" style="display: block;">
        </a>
    </div>
</header>

<main id="contenido">
<div class="contenedor">
    <?php mostrar_flash(); ?>
</div>