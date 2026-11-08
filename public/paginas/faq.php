<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$titulo = 'Preguntas frecuentes';
require APP . '/vistas/cabecera.php'; 
?>

<div class="contenedor py-4">
    <div style="max-width: 860px; margin: 0 auto;">
        
        <div class="mb-4">
            <a href="<?= BASE ?>/index.php" style="text-decoration:none; color:var(--gris); font-size:0.9rem;">← Volver al inicio</a>
            <h1 class="mt-2 mb-1" style="font-size:2.2rem; font-weight:700;">Preguntas Frecuentes (FAQ)</h1>
            <p style="color:var(--gris); font-size:0.95rem;">Resuelve tus dudas sobre cómo comprar, vender e intercambiar en ReUns.</p>
        </div>

        <!-- Lista de Acordeones -->
        <details class="faq-box" open>
            <summary>¿Qué es ReUns y cómo funciona?</summary>
            <div class="faq-respuesta">
                ReUns es una plataforma universitaria creada para facilitar la compra, venta e intercambio de productos (libros, tecnología, accesorios, ropa) directamente entre estudiantes y miembros de la comunidad.
            </div>
        </details>

        <details class="faq-box">
            <summary>¿Tiene algún costo publicar un producto?</summary>
            <div class="faq-respuesta">
                No. Publicar anuncios en ReUns es 100% gratuito.
            </div>
        </details>

        <details class="faq-box">
            <summary>¿Cómo funcionan los trueques o intercambios?</summary>
            <div class="faq-respuesta">
                Al publicar un producto puedes seleccionar la modalidad <strong>Intercambio</strong>. Otros usuarios podrán ofrecerte productos equivalentes y acordar si añaden o no una diferencia monetaria en soles (S/).
            </div>
        </details>

        <details class="faq-box">
            <summary>¿Dónde y cómo realizo el pago y la entrega del producto?</summary>
            <div class="faq-respuesta">
                Las entregas y pagos se acuerdan directamente entre comprador y vendedor. Recomendamos pactar entregas en zonas concurridas y seguras del campus universitario y verificar el estado del producto antes de realizar cualquier pago.
            </div>
        </details>

        <details class="faq-box">
            <summary>¿Por qué debo verificar mi correo electrónico?</summary>
            <div class="faq-respuesta">
                La verificación de correo ayuda a garantizar que los perfiles sean reales, manteniendo la comunidad limpia de spam o publicaciones fraudulentas.
            </div>
        </details>

        <!-- Bloque de llamada a la acción -->
        <div class="banda mt-4">
            <div>
                <h2 style="font-size:1.3rem; margin-bottom:4px;">¿Listo para empezar?</h2>
                <p style="margin:0; font-size:0.95rem;">Publica tus productos en minutos y conecta con otros estudiantes.</p>
            </div>
            <a class="btn btn--primario" href="<?= BASE ?>/paginas/publicar.php">Publicar producto</a>
        </div>

    </div>
</div>

<?php require APP . '/vistas/pie.php'; ?>