# Instalación (hasta la Etapa 4)
1. **XAMPP**: inicia **Apache** y **MySQL**. Copia la carpeta `reuns` a `C:\xampp\htdocs\`.
2. **phpMyAdmin** (http://localhost/phpmyadmin) → pestaña **SQL**: ejecuta, en este orden, `base_datos/esquema.sql` y luego `base_datos/etapa4.sql`
   (si ya habías hecho la Etapa 3, ejecuta solo `etapa4.sql`).
3. Copia `.env.example` como `.env` (con XAMPP, `root` sin contraseña ya funciona).
4. Abre http://localhost/reuns/public/ y crea tu cuenta. Los correos se guardan en `storage/correos/` (modo prueba): abre el `.txt` y copia el enlace para **verificar tu correo**; sin eso no puedes publicar.
5. Imágenes: necesitas la extensión **GD** de PHP (viene activada en XAMPP). Si al subir fotos ves "No pudimos leer la imagen", abre `C:\xampp\php\php.ini`, quita el `;` de `extension=gd` y reinicia Apache.
6. Administrador: en el *Shell* de XAMPP, `php base_datos\crear_admin.php`.
7. Productos de ejemplo (opcional): `php base_datos\cargar_demo.php`.
8. Pruebas: crea la base `reuns_prueba` (mismos SQL, cambiando el nombre `reuns` por `reuns_prueba` en `USE`), y ejecuta
   `set DB_NAME=reuns_prueba` y luego `php pruebas\prueba_auth.php` y `php pruebas\prueba_productos.php`.
