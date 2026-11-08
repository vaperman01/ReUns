<?php
function campo(string $name, string $label, string $tipo = 'text', array $o = []): void {
  $err = $o['errores'][$name] ?? ''; $val = $tipo === 'password' ? '' : ($o['valores'][$name] ?? '');
  $n = e($name);
  echo '<div class="campo"><label for="'.$n.'">'.e($label).'</label><input id="'.$n.'" name="'.$n.'" type="'.e($tipo).'" value="'.e($val).'"'
    .(isset($o['auto']) ? ' autocomplete="'.e($o['auto']).'"' : '').(($o['req'] ?? true) ? ' required' : '').($tipo==='password'?' maxlength="72"':'').($o['attr'] ?? '')
    .($err ? ' aria-invalid="true" aria-describedby="e-'.$n.'"' : '').'><p class="error" id="e-'.$n.'">'.e($err).'</p></div>';
}
function aviso_error(array $errores): void { if (!empty($errores['general'])) echo '<p class="aviso aviso--error" role="alert">'.e($errores['general']).'</p>'; }
function avatar(string $nombre, ?string $foto, int $px = 44): string {
  if ($foto && preg_match('/^[a-f0-9]{32}\.jpg$/', $foto)) return '<img class="avatar" src="'.BASE.'/uploads/perfiles/'.e($foto).'" alt="" width="'.$px.'" height="'.$px.'">';
  preg_match('/^./u', $nombre, $m); return '<div class="avatar" style="width:'.$px.'px;height:'.$px.'px" aria-hidden="true">'.e($m[0] ?? '?').'</div>';
}
function selector(string $name, string $label, array $opciones, ?string $sel, string $err = '', string $vacio = 'Elige…'): void {
  $n = e($name); echo '<div class="campo"><label for="'.$n.'">'.e($label).'</label><select id="'.$n.'" name="'.$n.'"'.($err ? ' aria-invalid="true"' : '').'><option value="">'.e($vacio).'</option>';
  foreach ($opciones as $v => $t) echo '<option value="'.e((string)$v).'"'.((string)$v === (string)$sel ? ' selected' : '').'>'.e($t).'</option>';
  echo '</select><p class="error">'.e($err).'</p></div>';
}
