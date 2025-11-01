const btnReadme = document.getElementById('btn-readme');
const modalReadme = document.getElementById('readme-modal');
const modalContent = document.getElementById('readme-content');
const btnClose = document.getElementById('readme-close');

const showModal = () => {
  if (modalReadme) {
    modalReadme.style.display = 'block';
  }
};

const hideModal = () => {
  if (modalReadme) {
    modalReadme.style.display = 'none';
  }
};

btnReadme?.addEventListener('click', async () => {
  if (!modalContent) {
    return;
  }

  try {
    const res = await fetch('/readme', { headers: { Accept: 'text/html' } });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}`);
    }

    const html = await res.text();
    modalContent.innerHTML = html;
    showModal();
  } catch (error) {
    modalContent.innerHTML = '<p style="color:red;">No se pudo cargar el README.</p>';
    showModal();
  }
});

btnClose?.addEventListener('click', () => {
  hideModal();
});

modalReadme?.addEventListener('click', (event) => {
  if (event.target === modalReadme) {
    hideModal();
  }
});
