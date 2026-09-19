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
