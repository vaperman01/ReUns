# ReUns — Etapas 4b y 5 (ofertas, compras e intercambios)

## 1. Copia de seguridad
phpMyAdmin → base `reuns` → **Exportar** → Continuar. Guarda el .sql.

## 2. Base de datos (en este orden, pestaña SQL de la base `reuns`)
1. `base_datos/etapa4b.sql`  (estado «intercambiado», categoría «ropa», índices)
2. `base_datos/etapa5.sql`   (tablas `ofertas`, `intercambios`, `historial_operaciones`)
Ambos se pueden ejecutar más de una vez y no borran datos.

## 3. Copiar archivos (misma ruta dentro de tu carpeta `reuns`)
Nuevos:  app/modelos/operacion.php · app/vistas/fila_operacion.php ·
         public/paginas/solicitar.php · intercambiar.php · operaciones.php ·
         public/assets/css/operaciones.css · public/assets/js/operaciones.js ·
         pruebas/prueba_operaciones.php
Reemplazan (versión corregida del paso 4 + botones del paso 5):
         app/modelos/producto.php · app/vistas/tarjeta_producto.php ·
         public/paginas/mis_productos.php · public/paginas/producto.php
(Si cambiaste algo a mano en esos 4 archivos, compáralos antes de reemplazar.)

## 4. Dos cambios pequeños a mano
**a) `app/ayudas/bootstrap.php`** — junto a los otros `require_once`, agrega (después de `modelos/usuario.php`):
```php
require_once APP.'/modelos/producto.php';
require_once APP.'/modelos/operacion.php';
```
(Si ya tienes la línea de `producto.php`, agrega solo la de `operacion.php`.)

**b) `app/vistas/cabecera.php`** — dentro de `if ($usr):`, antes del botón «Salir»:
```php
<a href="<?= BASE ?>/paginas/mis_productos.php">Mis productos</a>
<?php $pend = contar_pendientes_recibidas((int)$usr['id']); ?>
<a href="<?= BASE ?>/paginas/operaciones.php">Operaciones<?= $pend ? ' <b>'.$pend.'</b>' : '' ?></a>
```

## 5. Requisitos
PHP 8.1+ con las extensiones `mbstring` y `gd` (en XAMPP vienen activadas).

## 6. Pruebas automáticas (base aparte, para no tocar tus datos)
1. phpMyAdmin: crea la base `reuns_prueba` y ejecútale el esquema (`esquema.sql` con el nombre cambiado), `etapa4b.sql` y `etapa5.sql` cambiando `USE reuns;` por `USE reuns_prueba;`.
2. Shell de XAMPP, dentro de la carpeta del proyecto:
   `set DB_NAME=reuns_prueba`
   `php pruebas\prueba_operaciones.php`
   Debe terminar con «68 correctas, 0 fallidas». El script se niega a correr si la base no termina en `_prueba`.

## 7. Prueba manual (necesitas 2 cuentas verificadas: A y B, con sesiones en dos navegadores o uno en modo privado)
1. A publica «Laptop» (venta, S/ 1200) y «Polera» (intercambio).
2. B entra al detalle de la laptop → **Hacer una oferta** de S/ 1000 → aparece en Operaciones → Enviadas.
3. A → Operaciones → Recibidas: ve la oferta → **Aceptar**. El producto queda «reservado».
4. A y B pulsan **Confirmar**: pasa a Completada, la laptop queda «Vendido» y sale del catálogo; ambos lo ven en Historial.
5. B publica un producto de intercambio y propone cambiarlo por la «Polera» de A: A ve las fotos y datos del producto ofrecido, acepta, ambos confirman → los dos quedan «Intercambiado».
6. Casos de error: ofertar por tu propio producto, ofertar más que el precio, ofertar dos veces, pedir un producto reservado, abrir `operaciones.php` sin sesión.
