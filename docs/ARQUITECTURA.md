# Arquitectura ReUns
PHP 8 + MySQL (XAMPP) + HTML/CSS/JS sin frameworks. `public/` es lo único accesible; `app/` guarda lógica y vistas.
Estilos: `public/assets/css/base.css` (variables de color en `:root`). Vistas compartidas: `app/vistas/`.
Etapa 2 usa `app/datos_demo.php` como datos de ejemplo; se reemplaza por MySQL (PDO) en las Etapas 3-4.
Seguridad ya aplicada: toda salida pasa por `e()` (anti-XSS); filtros GET se validan/convierten.

## Etapa 4 (catálogo)
- Tablas: `categorias` (con subcategorías), `productos`, `producto_imagenes`. Eliminar = `disponibilidad='eliminado'` (se conserva el historial para pedidos e intercambios).
- Imágenes (`app/ayudas/imagenes.php`): se valida tipo real (finfo), peso (3 MB) y tamaño; se RE-CODIFICAN con GD a JPG con nombre aleatorio (quita metadatos y código oculto). `public/uploads/.htaccess` bloquea ejecutar PHP.
- Permisos: toda edición/cambio de estado pasa por `producto_propio()` y el `UPDATE` incluye `usuario_id`; un ajeno recibe "no encontrado".
- Búsqueda: palabras con `LIKE` (título pesa el doble que la descripción) → orden por relevancia, fecha o precio; paginación de 12.
