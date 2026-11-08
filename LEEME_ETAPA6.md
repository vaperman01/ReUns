# ReUns — Etapa 6 (chat, favoritos y notificaciones)

## 1. Base de datos (pestaña SQL de `reuns`; antes haz una copia de seguridad)
Ejecuta, en este orden, si aún no lo hiciste: `etapa4b.sql`, `etapa5.sql` y luego `etapa6.sql`.
Se pueden repetir sin riesgo y no borran datos. Crea: `favoritos`, `conversaciones`, `mensajes`, `notificaciones`.

## 2. Copiar archivos (misma ruta dentro de `reuns`)
app/modelos: notificacion.php · favorito.php · chat.php
public/paginas: chat_iniciar.php · conversacion.php · mensajes.php · chat_api.php · notificaciones.php ·
                notificaciones_api.php · favoritos.php · favorito_accion.php
public/assets: css/social.css · js/chat.js · js/contadores.js
pruebas: prueba_social.php
Reemplaza también `public/paginas/producto.php` (botones de favorito y chat).

## 3. Cambios pequeños a mano
**a) `app/ayudas/bootstrap.php`** — junto a los demás `require_once`, DESPUÉS de producto.php y operacion.php:
```php
require_once APP.'/modelos/notificacion.php';
require_once APP.'/modelos/favorito.php';
require_once APP.'/modelos/chat.php';
```
**b) `app/vistas/cabecera.php`** — dentro de `if ($usr):`, antes del botón «Salir» (sustituye los enlaces de la etapa 5 por estos):
```php
<?php $nNot = contar_notificaciones_no_leidas((int)$usr['id']); $nMsg = contar_mensajes_no_leidos((int)$usr['id']); $nOp = contar_pendientes_recibidas((int)$usr['id']); ?>
<a href="<?= BASE ?>/paginas/mis_productos.php">Mis productos</a>
<a href="<?= BASE ?>/paginas/favoritos.php">♥ Favoritos</a>
<a href="<?= BASE ?>/paginas/operaciones.php">Operaciones <b class="insignia" data-contador="operaciones" <?= $nOp ? '' : 'hidden' ?>><?= $nOp ?></b></a>
<a href="<?= BASE ?>/paginas/mensajes.php">Mensajes <b class="insignia" data-contador="mensajes" <?= $nMsg ? '' : 'hidden' ?>><?= $nMsg ?></b></a>
<a href="<?= BASE ?>/paginas/notificaciones.php" aria-label="Notificaciones">🔔 <b class="insignia" data-contador="notificaciones" <?= $nNot ? '' : 'hidden' ?>><?= $nNot ?></b></a>
```
y dentro del `<head>`: `<link rel="stylesheet" href="<?= BASE ?>/assets/css/social.css">` (para que las insignias se vean en todas las páginas).
**c) `app/vistas/pie.php`** — antes de `</body>`:
```php
<?php if (!empty($usr)): ?><script src="<?= BASE ?>/assets/js/contadores.js" data-api="<?= BASE ?>/paginas/notificaciones_api.php"></script><?php endif; ?>
```

## 4. Cómo funciona el «tiempo real»
XAMPP/PHP no mantiene conexiones abiertas (WebSockets) sin software extra. El chat pregunta al servidor cada 4 segundos
(solo con la pestaña visible) y los indicadores cada 30 s. Requiere solo PHP y MySQL; funciona igual en un hosting común.
Si algún día quisieran verdadero tiempo real, habría que agregar un servidor aparte (por ejemplo Ratchet/Mercure o Node con
WebSockets) y un proxy HTTPS: no se necesita ahora.

## 5. Pruebas automáticas (base `reuns_prueba`, con esquema + etapa4b + etapa5 + etapa6)
`set DB_NAME=reuns_prueba` y luego `php pruebas\prueba_social.php` → debe terminar con «0 fallidas».

## 6. Prueba manual (2 cuentas verificadas, A y B, en navegadores distintos)
1. B abre un producto de A → «Chatear con el vendedor» → escribe. En el navegador de A, en ≤ 5 s aparece 💬 en 🔔 y «Mensajes»; al abrir el chat, el indicador baja a 0.
2. B pulsa «♥ Guardar en favoritos» dos veces: sigue habiendo un solo favorito; «Quitar» lo elimina. Se ve en «♥ Favoritos».
3. B hace una oferta: A recibe notificación 🤝; A la acepta: B recibe otra. «Marcar leída» y «Marcar todas» funcionan.
4. Con una tercera cuenta C intenta abrir `conversacion.php?c=<id de A y B>`: debe decir «no encontrada».
