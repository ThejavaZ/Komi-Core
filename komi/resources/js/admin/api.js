const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

const defaultHeaders = {
  Accept: 'application/json',
  'X-Requested-With': 'XMLHttpRequest',
  'X-CSRF-TOKEN': csrfToken,
};

export function api(url, options = {}) {
  const headers = { ...defaultHeaders, ...options.headers };

  if (options.body && typeof options.body === 'string') {
    headers['Content-Type'] = 'application/json';
  }

  return fetch(url, { ...options, headers });
}

/**
 * Muestra un toast de error con boton para copiar los detalles.
 * Llamar en el catch de cada llamada API.
 */
export function showErrorToast(error, context = '') {
  const status = error?.status || error?.statusCode || 'N/A';
  const message = error?.message || error?.error || String(error);
  const url = error?.url || '';

  const detail = [
    context ? `[Context] ${context}` : '',
    `[Status] ${status}`,
    `[URL] ${url}`,
    `[Message] ${message}`,
  ].filter(Boolean).join('\n');

  // Eliminar toast anterior si existe
  const existing = document.getElementById('komi-error-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.id = 'komi-error-toast';
  toast.style.cssText = `
    position: fixed; bottom: 20px; right: 20px; z-index: 99999;
    background: #1e293b; color: #f87171; padding: 16px 20px;
    border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    font-family: monospace; font-size: 13px; max-width: 480px;
    border: 1px solid #334155; animation: slideUp 0.3s ease;
  `;

  toast.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <span style="font-weight:bold;color:#ef4444;">Error ${status}</span>
      <button id="komi-error-close" style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:18px;padding:0 4px;">&times;</button>
    </div>
    <div style="white-space:pre-wrap;word-break:break-all;color:#e2e8f0;margin-bottom:12px;max-height:150px;overflow-y:auto;">${detail}</div>
    <button id="komi-error-copy" style="
      background:#3b82f6;color:white;border:none;padding:6px 16px;
      border-radius:6px;cursor:pointer;font-size:12px;font-weight:bold;
    ">Copiar Error</button>
  `;

  document.body.appendChild(toast);

  // Animacion CSS
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
  `;
  document.head.appendChild(style);

  document.getElementById('komi-error-copy').addEventListener('click', () => {
    // Fallback: textarea + execCommand (funciona en HTTP sin HTTPS)
    const textarea = document.createElement('textarea');
    textarea.value = detail;
    textarea.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0';
    document.body.appendChild(textarea);
    textarea.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (_) { /* ignore */ }
    document.body.removeChild(textarea);

    if (ok) {
      const btn = document.getElementById('komi-error-copy');
      btn.textContent = 'Copiado!';
      btn.style.background = '#22c55e';
      setTimeout(() => {
        toast.remove();
        style.remove();
      }, 1200);
    }
  });

  document.getElementById('komi-error-close').addEventListener('click', () => {
    toast.remove();
    style.remove();
  });

  // Auto-cerrar despues de 12 segundos
  setTimeout(() => {
    if (document.getElementById('komi-error-toast')) {
      toast.remove();
      style.remove();
    }
  }, 12000);
}

/**
 * Wrapper que hace fetch y muestra toast automaticamente en error.
 * Uso: const data = await apiSafe('/admin/api/users/1', {}, 'Cargar usuario');
 */
export async function apiSafe(url, options = {}, context = '') {
  try {
    const res = await api(url, options);

    if (!res.ok) {
      let body;
      try {
        body = await res.json();
      } catch {
        body = { message: res.statusText };
      }
      const err = {
        status: res.status,
        statusCode: res.status,
        message: body?.message || body?.error || res.statusText,
        url,
      };
      showErrorToast(err, context);
      throw err;
    }

    return res;
  } catch (e) {
    if (!e?.status) {
      // Error de red o DNS
      showErrorToast({
        status: 'Network',
        message: e.message || 'Error de conexion',
        url,
      }, context);
    }
    throw e;
  }
}
