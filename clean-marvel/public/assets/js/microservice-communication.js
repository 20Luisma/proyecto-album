// Microservice Communication HUD (modo superhéroe)
const MSC = (function() {
  const panel = document.getElementById('microservice-comm-panel');
  const steps = panel ? panel.querySelectorAll('.msc-step') : [];
  const status = document.getElementById('msc-status-text');
  const retryBtn = document.getElementById('msc-retry');

  function showPanel() {
    if (!panel) return;
    panel.classList.remove('msc-hidden');
    clearStatus();
    setStep('send');
    // animación rápida de arranque para que se vea "vivo"
    animateFlow();
  }

  function animateFlow() {
    const stepOrder = ['send', 'process', 'return'];
    stepOrder.forEach((stepName, index) => {
      setTimeout(() => setStep(stepName), index * 900);
    });
  }

  function setStep(stepName) {
    steps.forEach(step => {
      const active = step.dataset.step === stepName;
      step.classList.toggle('msc-active', active);
    });
  }

  function markSuccess(msg) {
    setStep('return');
    if (status) {
      status.className = 'msc-status msc-success';
      status.textContent = msg || '✅ Comunicación correcta. Microservicio funcionando (8080 ↔ 8081).';
    }
    // Ocultar después de unos segundos
    setTimeout(() => {
      if (panel) panel.classList.add('msc-hidden');
    }, 3500);
  }

  function markError(msg) {
    if (status) {
      status.className = 'msc-status msc-error';
      status.textContent = msg || '❌ Comunicación fallida. No se obtuvo respuesta del microservicio (8081).';
    }
    if (retryBtn) {
      retryBtn.classList.remove('msc-hidden');
    }
  }

  function clearStatus() {
    if (status) {
      status.className = 'msc-status';
      status.textContent = '';
    }
    if (retryBtn) retryBtn.classList.add('msc-hidden');
  }

  // reintento solo visual
  if (retryBtn) {
    retryBtn.addEventListener('click', () => {
      clearStatus();
      showPanel();
    });
  }

  return {
    showPanel,
    markSuccess,
    markError,
    setStep
  };
})();

if (typeof window !== 'undefined') {
  window.MSC = MSC;
}

export { MSC };
