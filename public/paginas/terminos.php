<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$titulo = 'Términos de uso';
require APP . '/vistas/cabecera.php'; 
?>

<div class="contenedor py-4">
    <div style="max-width: 860px; margin: 0 auto;">
        
        <div class="mb-4">
            <a href="<?= BASE ?>/index.php" style="text-decoration:none; color:var(--gris); font-size:0.9rem;">← Volver al inicio</a>
            <h1 class="mt-2 mb-1" style="font-size:2.2rem; font-weight:700;">Términos y Condiciones</h1>
            <p style="color:var(--gris); font-size:0.9rem;">Última actualización: octubre de 2026</p>
        </div>

        <div class="legal-card">
            <section>
                <h2>1. Naturaleza de la Plataforma</h2>
                <p><strong>ReUns</strong> es un punto de encuentro digital para la comunidad universitaria enfocado en facilitar la compra, venta e intercambio de productos entre particulares. ReUns no actúa como vendedor directo ni intermediario financiero en los acuerdos entre partes.</p>
            </section>

            <section>
                <h2>2. Cuentas y Verificación</h2>
                <p>Para publicar o proponer intercambios debes contar con un perfil activo y verificado. El usuario es el único responsable de la actividad realizada desde su cuenta.</p>
            </section>

            <section>
                <h2>3. Publicación y Contenido Permitido</h2>
                <p>Toda publicación debe reflejar fielmente el estado del producto. Queda prohibido publicar artículos ilícitos, falsificaciones o material engañoso.</p>
            </section>

            <section>
                <h2>4. Acuerdos y Transacciones</h2>
                <p>Las valoraciones de los productos se fijan en soles (S/). Los puntos de entrega y métodos de pago son decisión libre de las partes involucradas.</p>
            </section>
        </div>

    </div>
</div>

<?php require APP . '/vistas/pie.php'; ?>