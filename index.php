<?php
/**
 * ReUns - Redirección segura a la carpeta pública
 * Protege contra navegadores que solicitan /reuns/ directamente.
 */
header('Location: public/index.php');
exit;
