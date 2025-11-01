// Microservice Communication HUD (modo superhéroe) - versión corregida
const MSC = (function() {

  // buscamos SIEMPRE los elementos en el momento
  function getPanel() {
    return document.getElementById('microservice-comm-panel');
  }
  function getSteps(panel) {
    return panel ? panel.querySelectorAll('.msc-step') : [];
  }
  function getStatus() {
    return document.getElementById('msc-status-text');
  }
  function getRetryBtn() {
    return document.getElementById('msc-retry');
  }

  // mostrar HUD inmediatamente
  function showPanel() {
    const panel = getPanel();
    if (!panel) return;
    panel.classList.remove('msc-hidden');
    clearStatus();
    setStep('send');
  }

  // activar paso
  function setStep(stepName) {
    const panel = getPanel();
    const steps = getSteps(panel);
    steps.forEach(step => {
      const active = step.dataset.step === stepName;
      step.classList.toggle('msc-active', active);
    });
  }

  // éxito
  function markSuccess(msg) {
    setStep('return');
    const status = getStatus();
    if (status) {
      status.className = 'msc-status msc-success';
      status.textContent = msg || '✅ Comunicación correcta. Microservicio funcionando (8080 ↔ 8081).';
    }
    const panel = getPanel();
    setTimeout(() => {
      if (panel) panel.classList.add('msc-hidden');
    }, 3500);
  }

  // error
  function markError(msg) {
    const status = getStatus();
    if (status) {
      status.className = 'msc-status msc-error';
      status.textContent = msg || '❌ Comunicación fallida. No se obtuvo respuesta del microservicio (8081).';
    }
    const retryBtn = getRetryBtn();
    if (retryBtn) retryBtn.classList.remove('msc-hidden');
  }

  // limpiar
  function clearStatus() {
    const status = getStatus();
    const retryBtn = getRetryBtn();
    if (status) {
      status.className = 'msc-status';
      status.textContent = '';
    }
    if (retryBtn) retryBtn.classList.add('msc-hidden');
  }

  // reintentar (visual)
  document.addEventListener('click', (e) => {
    const retryBtn = getRetryBtn();
    if (retryBtn && e.target === retryBtn) {
      clearStatus();
      showPanel();
    }
  });

  return {
    showPanel,
    setStep,
    markSuccess,
    markError,
  };
})();

// disponible global
if (typeof window !== 'undefined') {
  window.MSC = MSC;
}

// por si usas módulos
export { MSC };
