const form = document.getElementById('patient-form');
const list = document.getElementById('patient-list');
const statusEl = document.getElementById('status');

async function fetchPatients() {
  const res = await fetch('api.php');
  const payload = await res.json();
  renderPatients(payload.data || []);
}

function renderPatients(rows) {
  list.innerHTML = rows
    .map(
      (row) => `
      <tr>
        <td>${row.id}</td>
        <td>${escapeHtml(row.full_name)}</td>
        <td>${escapeHtml(row.email)}</td>
        <td>${escapeHtml(row.phone || '')}</td>
        <td>${row.created_at}</td>
      </tr>
    `
    )
    .join('');
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(form);
  const body = {
    full_name: formData.get('full_name'),
    email: formData.get('email'),
    phone: formData.get('phone'),
  };

  const res = await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  const payload = await res.json();

  if (!res.ok) {
    statusEl.textContent = payload.error || 'Failed to add patient';
    statusEl.style.color = 'red';
    return;
  }

  statusEl.textContent = payload.message;
  statusEl.style.color = 'green';
  form.reset();
  fetchPatients();
});

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

fetchPatients();
