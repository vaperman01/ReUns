<?php 
// Diagnóstico y control de errores seguro
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/ayudas/bootstrap.php';

// Variables seguras para evitar errores
$ofertas   = function_exists('buscar_productos') ? (buscar_productos(['oferta' => 1], 1, 4)['items'] ?? []) : []; 
$recientes = function_exists('buscar_productos') ? (buscar_productos([], 1, 8)['items'] ?? []) : []; 
$trueques  = function_exists('buscar_productos') ? (buscar_productos(['mod' => 'intercambio'], 1, 6)['items'] ?? []) : [];

$titulo = 'Compra, vende e intercambia'; 

require_once APP . '/vistas/cabecera.php'; 
?>

<!-- Estilos UX/UI Mejorados: Sin tarjetas pesadas de fondo, integración fluida -->
<style>
    :root {
        --tierra-primario: #573f32;
        --tierra-primario-hover: #433026;
        --tierra-claro: #f4f0ec;
        --tierra-acento: #a36b53;
        --borde-suave: #e6dfdb;
        --sombra-moderna: 0 20px 40px -10px rgba(87, 63, 50, 0.12);
    }

    /* Hero con atmósfera dinámica en gradiente y elementos flotantes sutiles */
    .hero-glass {
        position: relative;
        overflow: hidden;
        background: radial-gradient(circle at 85% 15%, rgba(163, 107, 83, 0.07) 0%, transparent 45%),
                    radial-gradient(circle at 15% 85%, rgba(87, 63, 50, 0.05) 0%, transparent 45%),
                    #ffffff;
        padding: 5rem 0;
    }

    /* Iconos ambientales flotantes suaves al fondo */
    .icono-flotante-1, .icono-flotante-2, .icono-flotante-3 {
        position: absolute;
        font-size: 2.2rem;
        opacity: 0.12;
        pointer-events: none;
        z-index: 1;
        animation: flotarIcono 7s ease-in-out infinite;
    }
    .icono-flotante-1 { top: 12%; left: 5%; animation-delay: 0s; }
    .icono-flotante-2 { bottom: 15%; right: 8%; animation-delay: 2.5s; font-size: 2.8rem; }
    .icono-flotante-3 { top: 55%; left: 48%; animation-delay: 5s; font-size: 1.8rem; }

    @keyframes flotarIcono {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-12px) rotate(6deg); }
    }

    /* Títulos y textos limpios integrados directamente en el Hero */
    .hero-contenido {
        position: relative;
        z-index: 2;
    }

    .hero-titulo {
        font-family: 'Poppins', sans-serif;
        font-weight: 700;
        font-size: 3rem;
        color: #2c2421;
        line-height: 1.15;
        margin-bottom: 1.25rem;
        letter-spacing: -0.02em;
    }

    .hero-texto {
        font-size: 1.15rem;
        color: #6b5b53;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    /* Contenedor del carrusel totalmente integrado sin cajas de fondo */
    .carousel-trueques-container {
        position: relative;
        z-index: 2;
    }

    .carousel-item-wrapper {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
    }

    /* Grid de Categorías con Glassmorphism y Zoom */
    .cat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 1.2rem;
    }

    .cat-item {
        background: #faf8f6;
        border: 1px solid var(--borde-suave);
        border-radius: 1.25rem;
        padding: 1.3rem 1rem;
        text-align: center;
        text-decoration: none;
        color: #2c2421;
        font-weight: 500;
        font-size: 0.95rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.6rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .cat-item span {
        font-size: 1.8rem;
        transition: transform 0.3s ease;
    }

    .cat-item:hover {
        background: #ffffff;
        border-color: var(--tierra-primario);
        color: var(--tierra-primario);
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(87, 63, 50, 0.08);
    }

    .cat-item:hover span {
        transform: scale(1.2) rotate(6deg);
    }

    /* Tarjetas de productos con elevación fluida */
    .card-dinamica {
        transition: transform 0.35s ease, box-shadow 0.35s ease;
    }
    .card-dinamica:hover {
        transform: translateY(-6px);
        box-shadow: var(--sombra-moderna);
    }

    /* Banda CTA con gradiente ambiental y luz interior */
    .banda-cta {
        background: linear-gradient(135deg, var(--tierra-primario) 0%, var(--tierra-primario-hover) 100%);
        color: #ffffff;
        border-radius: 1.75rem;
        padding: 3.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--sombra-moderna);
    }
    .banda-cta::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
        transform: rotate(25deg);
        pointer-events: none;
    }
    @media (max-width: 768px) {
        .banda-cta { flex-direction: column; text-align: center; padding: 2rem; }
    }
</style>

<!-- HERO PRINCIPAL CON INTEGRACIÓN FLUIDA -->
<section class="hero-glass">
    <!-- Iconos ambientales animados al fondo -->
    <div class="icono-flotante-1">🔄</div>
    <div class="icono-flotante-2">✨</div>
    <div class="icono-flotante-3">🤝</div>

    <div class="contenedor">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0 hero-contenido">
                <h1 class="hero-titulo">Lo que ya no usas, otra persona lo necesita.</h1>
                <p class="hero-texto">Compra barato, vende fácil o cambia un producto por otro. Todo entre personas, en soles.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a class="btn btn-primary px-4 py-3 rounded-pill fw-semibold shadow-sm" href="<?= BASE ?>/paginas/catalogo.php" style="background-color: var(--tierra-primario); border-color: var(--tierra-primario);">Ver productos</a>
                    <a class="btn btn-outline-secondary px-4 py-3 rounded-pill fw-semibold" href="<?= BASE ?>/paginas/publicar.php">Publicar el mío</a>
                </div>
            </div>
            
            <?php if (!empty($trueques)): ?>
                <div class="col-lg-6 carousel-trueques-container">
                    <!-- Carrusel automático de tarjetas de productos directamente flotando -->
                    <div id="carruselTruequesAutomatico" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="3500">
                        <div class="carousel-inner">
                            <?php 
                            $chunks = array_chunk($trueques, 2);
                            foreach ($chunks as $index => $par): 
                            ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <div class="carousel-item-wrapper py-2">
                                        <?php 
                                        foreach ($par as $p) {
                                            if (file_exists(APP . '/vistas/tarjeta_producto.php')) {
                                                echo '<div class="card-dinamica">';
                                                require APP . '/vistas/tarjeta_producto.php';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CATEGORÍAS INTERACTIVAS -->
<section class="seccion contenedor">
    <h2>Categorías</h2>
    <div class="cat-grid mt-3">
        <?php 
        $listaCats = function_exists('categorias') ? categorias() : [];
        foreach ($listaCats as $k => [$n, $i]): 
        ?>
            <a class="cat-item" href="<?= BASE ?>/paginas/catalogo.php?cat=<?= e($k) ?>">
                <span aria-hidden="true"><?= e($i) ?></span>
                <?= e($n) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- OFERTAS DINÁMICAS -->
<?php if (!empty($ofertas)): ?>
    <section class="seccion contenedor">
        <div class="seccion__cab">
            <h2>Ofertas</h2>
            <a href="<?= BASE ?>/paginas/catalogo.php?oferta=1" class="fw-semibold" style="color: var(--tierra-primario);">Ver más →</a>
        </div>
        <div class="grid">
            <?php foreach ($ofertas as $p): ?>
                <div class="card-dinamica">
                    <?php if (file_exists(APP . '/vistas/tarjeta_producto.php')) require APP . '/vistas/tarjeta_producto.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- BANDA CTA LLAMATIVA -->
<section class="seccion contenedor">
    <div class="banda-cta">
        <div>
            <h2 class="text-white mb-2">¿Tienes algo que cambiar o vender?</h2>
            <p class="mb-0 opacity-75 fs-5">Publica tu producto con fotos y propón un trueque, con o sin diferencia en soles.</p>
        </div>
        <a class="btn btn-light text-dark fw-semibold px-5 py-3 rounded-pill shadow-sm" href="<?= BASE ?>/paginas/publicar.php">Publicar producto</a>
    </div>
</section>

<!-- RECIENTES -->
<section class="seccion contenedor mb-5">
    <div class="seccion__cab">
        <h2>Publicados recientemente</h2>
        <a href="<?= BASE ?>/paginas/catalogo.php" class="fw-semibold" style="color: var(--tierra-primario);">Ver todo →</a>
    </div>
    <?php if (empty($recientes)): ?>
        <div class="vacio text-center py-4">
            <h3>Aún no hay productos</h3>
            <p>Sé la primera persona en publicar.</p>
            <a class="btn btn-primary rounded-pill px-4" href="<?= BASE ?>/paginas/publicar.php" style="background-color: var(--tierra-primario); border-color: var(--tierra-primario);">Publicar producto</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($recientes as $p): ?>
                <div class="card-dinamica">
                    <?php if (file_exists(APP . '/vistas/tarjeta_producto.php')) require APP . '/vistas/tarjeta_producto.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once APP . '/vistas/pie.php'; ?>