<?php
const IMG_MAX_BYTES = 3 * 1024 * 1024;   // 3 MB por imagen
const IMG_MAX_PIXELES = 16000000;        // 16 megapíxeles (protege la memoria del servidor)
const IMG_TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
function dir_uploads(string $sub): string { $d = APP.'/../public/uploads/'.$sub; if (!is_dir($d)) mkdir($d, 0775, true); return $d; }
function normalizar_archivos(?array $f): array { // $_FILES (uno o varios) → lista simple, sin los vacíos
  if (!$f) return []; $out = [];
  if (is_array($f['name'])) { foreach ($f['name'] as $i => $_) if ($f['error'][$i] !== UPLOAD_ERR_NO_FILE)
    $out[] = ['name'=>$f['name'][$i], 'tmp_name'=>$f['tmp_name'][$i], 'error'=>$f['error'][$i], 'size'=>$f['size'][$i]]; }
  elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) $out[] = $f;
  return $out;
}
/** Valida y RE-CODIFICA la imagen (quita metadatos y cualquier código oculto). Devuelve [archivo|null, error|null]. */
function guardar_imagen(array $f, string $sub, int $lado = 1200, bool $cuadrada = false, bool $verificar = true): array {
  if ($f['error'] !== UPLOAD_ERR_OK) return [null, in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Cada imagen debe pesar máximo 3 MB.' : 'No se pudo subir la imagen. Inténtalo de nuevo.'];
  if ($verificar && !is_uploaded_file($f['tmp_name'])) return [null, 'Archivo no válido.'];
  if (filesize($f['tmp_name']) > IMG_MAX_BYTES) return [null, 'Cada imagen debe pesar máximo 3 MB.'];
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  if (!in_array($mime, IMG_TIPOS, true)) return [null, 'Solo se aceptan imágenes JPG, PNG o WebP.'];
  $info = @getimagesize($f['tmp_name']);
  if (!$info || $info[0] < 100 || $info[1] < 100) return [null, 'La imagen debe medir al menos 100 × 100 píxeles.'];
  if ($info[0] * $info[1] > IMG_MAX_PIXELES) return [null, 'La imagen es demasiado grande (máximo 16 megapíxeles). Redúcela e inténtalo de nuevo.'];
  $src = match ($mime) { 'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']), 'image/png' => @imagecreatefrompng($f['tmp_name']),
    default => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false };
  if (!$src) return [null, 'No pudimos leer la imagen. Prueba con otra.'];
  if ($mime === 'image/jpeg' && function_exists('exif_read_data')) { // respeta la orientación de fotos de celular
    $ex = @exif_read_data($f['tmp_name']); $giro = [3 => 180, 6 => -90, 8 => 90][$ex['Orientation'] ?? 0] ?? 0;
    if ($giro && ($r = imagerotate($src, $giro, 0))) $src = $r;
  }
  $w = imagesx($src); $h = imagesy($src);
  if ($cuadrada) { $lado_c = min($w, $h); $sx = intdiv($w - $lado_c, 2); $sy = intdiv($h - $lado_c, 2); $w = $h = $lado_c; $nw = $nh = min($lado, $lado_c); }
  else { $k = min(1, $lado / max($w, $h)); $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k)); $sx = $sy = 0; }
  $dst = imagecreatetruecolor($nw, $nh); imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // fondo blanco para PNG con transparencia
  imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $nw, $nh, $w, $h);
  $nombre = bin2hex(random_bytes(16)).'.jpg'; // nombre aleatorio: nunca se usa el nombre que envía el usuario
  $ok = imagejpeg($dst, dir_uploads($sub).'/'.$nombre, 82);
  return $ok ? [$nombre, null] : [null, 'No se pudo guardar la imagen.'];
}
function borrar_imagen(string $sub, ?string $archivo): void {
  if ($archivo && preg_match('/^[a-f0-9]{32}\.jpg$/', $archivo)) @unlink(dir_uploads($sub).'/'.$archivo);
}
