// ReUns - Etapa 6: chat con actualización por consulta periódica (cada 4 s, solo con la pestaña visible).
// Los textos se insertan con textContent: nunca se interpreta HTML de otros usuarios (sin XSS).
(() => {
  const lista = document.getElementById('mensajes');
  const form = document.getElementById('form-chat');
  if (!lista) return;
  const api = lista.dataset.api;
  let ultimo = parseInt(lista.dataset.ultimo || '0', 10);
  let ocupado = false;
  const error = document.getElementById('chat-error');
  const baja = () => { lista.scrollTop = lista.scrollHeight; };
  const mostrarError = t => { if (!error) return; error.textContent = t; error.hidden = !t; };

  function pintar(m) {
    document.getElementById('chat-vacio')?.remove();
    const li = document.createElement('li');
    li.className = 'msg ' + (m.mio ? 'msg--mio' : 'msg--otro');
    const p = document.createElement('p'); p.className = 'msg__texto'; p.textContent = m.texto;
    const t = document.createElement('time'); t.textContent = m.hora;
    li.append(p, t); lista.append(li);
    if (m.id > ultimo) ultimo = m.id;
  }

  async function traer() {
    if (ocupado || document.hidden) return;
    ocupado = true;
    try {
      const r = await fetch(api + '&desde=' + ultimo, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (r.status === 401) { mostrarError('Tu sesión expiró. Recarga la página para volver a entrar.'); return; }
      if (!r.ok) return;
      const d = await r.json();
      const cerca = lista.scrollHeight - lista.scrollTop - lista.clientHeight < 90;
      (d.mensajes || []).forEach(pintar);
      if ((d.mensajes || []).length && cerca) baja();
    } catch (e) { /* sin conexión: se reintenta en el siguiente ciclo */ }
    finally { ocupado = false; }
  }

  if (form) {
    const ta = form.elements.texto;
    const btn = form.querySelector('button[type=submit]');
    form.addEventListener('submit', async ev => {
      ev.preventDefault();
      if (!ta.value.trim()) return;
      btn.disabled = true; mostrarError('');
      try {
        const r = await fetch(api, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
        const d = await r.json().catch(() => ({}));
        if (r.ok) { ta.value = ''; ocupado = false; await traer(); baja(); }
        else mostrarError(d.error || 'No se pudo enviar el mensaje.');
      } catch (e) { form.submit(); }      // sin JS/sin fetch funcional: envío normal del formulario
      finally { btn.disabled = false; ta.focus(); }
    });
    ta.addEventListener('keydown', ev => {   // Enter envía; Mayús+Enter hace salto de línea
      if (ev.key === 'Enter' && !ev.shiftKey) { ev.preventDefault(); form.requestSubmit(); }
    });
  }

  baja();
  setInterval(traer, 4000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) traer(); });
})();
