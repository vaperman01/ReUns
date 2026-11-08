<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$titulo = 'Política de privacidad';
require APP . '/vistas/cabecera.php'; 
?>

<div class="contenedor py-4">
    <div style="max-width: 860px; margin: 0 auto;">
        
        <div class="mb-4">
            <a href="<?= BASE ?>/index.php" style="text-decoration:none; color:var(--gris); font-size:0.9rem;">← Volver al inicio</a>
            <h1 class="mt-2 mb-1" style="font-size:2.2rem; font-weight:700;">Política de Privacidad</h1>
            <p style="color:var(--gris); font-size:0.9rem;">Última actualización: octubre de 2026</p>
        </div>

        <div class="legal-card">
            <section>
                <h2>1. Información que Recopilamos</h2>
                <p>Para garantizar un entorno seguro dentro de la comunidad ReUns, recopilamos únicamente los datos necesarios para la gestión del perfil de usuario y la interacción entre anunciantes:</p>
                <ul>
                    <li><strong>Datos de registro:</strong> Nombre, apellidos y dirección de correo electrónico.</li>
                    <li><strong>Datos de publicaciones:</strong> Título, descripción, precio, fotografías y categoría del producto publicado.</li>
                    <li><strong>Datos técnicos básicos:</strong> Dirección IP e información básica del navegador para fines de seguridad.</li>
                </ul>
            </section>

            <section>
                <h2>2. Uso de la Información</h2>
                <p>La información recopilada se utiliza exclusivamente para:</p>
                <ul>
                    <li>Permitir el acceso y la gestión de tus publicaciones e intercambios.</li>
                    <li>Enviar notificaciones relativas a la verificación de cuenta o avisos del sistema.</li>
                    <li>Prevenir fraudes, spams o accesos no autorizados.</li>
                </ul>
            </section>

            <section>
                <h2>3. Protección y Confidencialidad</h2>
                <p>Tus contraseñas se almacenan mediante algoritmos de encriptación seguros. ReUns no vende, alquila ni comparte datos personales con terceras empresas con fines comerciales.</p>
            </section>

            <section>
                <h2>4. Tus Derechos sobre Tus Datos</h2>
                <p style="margin-bottom:0;">Como usuario, tienes derecho a acceder, corregir o solicitar la eliminación total de tus datos personales e historial de publicaciones desde el apartado de tu perfil.</p>
            </section>
        </div>

    </div>
</div>

<?php require APP . '/vistas/pie.php'; ?>