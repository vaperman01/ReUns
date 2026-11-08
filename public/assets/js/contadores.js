// ReUns - Etapa 6: actualiza los indicadores (notificaciones, mensajes, operaciones) cada 30 s.
// Uso: <script src=".../contadores.js" data-api=".../notificaciones_api.php"></script>
(() => {
  const api = document.currentScript && document.currentScript.dataset.api;
  if (!api) return;
  const poner = (clave, n) => document.querySelectorAll('[data-contador="' + clave + '"]').forEach(el => {
    el.textContent = n > 99 ? '99+' : String(n);
    el.hidden = !(n > 0);
  });
  async function consultar() {
    if (document.hidden) return;
    try {
      const r = await fetch(api, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (!r.ok) return;
      const d = await r.json();
      ['notificaciones', 'mensajes', 'operaciones'].forEach(k => poner(k, d[k] || 0));
    } catch (e) { /* se reintenta luego */ }
  }
  setInterval(consultar, 30000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) consultar(); });
})();
