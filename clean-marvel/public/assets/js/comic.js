import { formatDateTime, showMessage } from '/assets/js/main.js';
import { MSC } from '/assets/js/microservice-communication.js';

const heroGrid = document.getElementById('comic-heroes-grid');
const heroSearchInput = document.getElementById('comic-hero-search');
const heroCountLabel = document.getElementById('comic-hero-count');
const heroEmptyState = document.getElementById('comic-heroes-empty');

const selectedHeroesList = document.getElementById('selected-heroes-list');
const selectedHeroesEmpty = document.getElementById('selected-heroes-empty');
const selectedHeroesCount = document.getElementById('selected-heroes-count');
const selectedHeroesInput = document.getElementById('selected-heroes-input');

const comicForm = document.getElementById('comic-form');
const comicCancelButton = document.getElementById('comic-cancel');
const comicGenerateButton = document.getElementById('comic-generate');
const comicMessage = document.getElementById('comic-message');

const heroSelectionSection = document.getElementById('hero-selection-section');
const comicSlideshowSection = document.getElementById('comic-slideshow-section');
const comicStorySection = document.getElementById('comic-story-section');
const generatedComicTitle = document.getElementById('generated-comic-title');
const slideshowContainer = document.getElementById('slideshow-container');
const slideshowPrev = document.getElementById('slideshow-prev');
const slideshowNext = document.getElementById('slideshow-next');
const closeComicResultButton = document.getElementById('close-comic-result');

const comicOutputStorySummary = document.getElementById('comic-output-story-summary');
const comicOutputPanels = document.getElementById('comic-output-panels');
const comicOutputPanelsEmpty = document.getElementById('comic-output-panels-empty');

const activityEmpty = document.getElementById('comic-activity-empty');
const activityView = document.getElementById('comic-activity-view');
const activityTag = document.getElementById('comic-activity-tag');
const activityDate = document.getElementById('comic-activity-date');
const activityCounter = document.getElementById('comic-activity-counter');
const activityTitle = document.getElementById('comic-activity-title');
const activityPrevButton = document.getElementById('comic-activity-prev');
const activityNextButton = document.getElementById('comic-activity-next');
const activityClearButton = document.getElementById('comic-activity-clear');

const communicationPanel = document.getElementById('microservice-comm-panel');
const communicationStatus = document.getElementById('msc-status-text');
const communicationRetryButton = document.getElementById('msc-retry');

const heroState = {
  all: [],
  filtered: [],
  selected: new Map()
};

const activityState = {
  entries: [],
  index: -1
};

const ACTIVITY_STORAGE_KEY = 'comic-activity-state';

let slideshowInterval = null;

const ACTIVITY_STYLES = {
  SELECCION: 'text-emerald-400 border-emerald-500/40',
  DESELECCION: 'text-slate-300 border-slate-500/40',
  COMIC: 'text-sky-300 border-sky-500/40',
  CANCELADO: 'text-rose-300 border-rose-500/40'
};

function escapeSelector(value) {
  if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
    return CSS.escape(value);
  }
  return String(value).replace(/([^\w-])/g, '\\$1');
}

let isGeneratingComic = false;
const comicGenerateLabelText = comicGenerateButton ? (comicGenerateButton.querySelector('span')?.textContent ?? 'Generar cómic') : 'Generar cómic';

function setGeneratingState(isGenerating) {
  isGeneratingComic = isGenerating;
  if (comicGenerateButton) {
    comicGenerateButton.disabled = isGenerating;
    comicGenerateButton.classList.toggle('opacity-70', isGenerating);
    const label = comicGenerateButton.querySelector('span');
    if (label) {
      label.textContent = isGenerating ? 'Generando...' : comicGenerateLabelText;
    }
  }
  if (comicCancelButton) {
    comicCancelButton.disabled = isGenerating;
    comicCancelButton.classList.toggle('opacity-70', isGenerating);
  }
}

function hideCommunicationPanel() {
  if (communicationPanel) {
    communicationPanel.classList.add('msc-hidden');
  }
  if (communicationStatus) {
    communicationStatus.className = 'msc-status';
    communicationStatus.textContent = '';
  }
  if (communicationRetryButton) {
    communicationRetryButton.classList.add('msc-hidden');
  }
}

/* 🔵 1) CLICK DEL BOTÓN: mostrar HUD YA */
if (comicGenerateButton) {
  comicGenerateButton.addEventListener('click', () => {
    if (isGeneratingComic) return;
    if (typeof MSC !== 'undefined' && typeof MSC.showPanel === 'function') {
      MSC.showPanel();
      if (typeof MSC.setStep === 'function') {
        MSC.setStep('send'); // primer paso visible
      }
      // opcional: pasamos a process un pelín después para que se note
      setTimeout(() => {
        if (typeof MSC.setStep === 'function') {
          MSC.setStep('process');
        }
      }, 200);
    }
  });
}

function clearGeneratedComic() {
  if (slideshowInterval) clearInterval(slideshowInterval);

  if (comicSlideshowSection) comicSlideshowSection.classList.add('hidden');
  if (comicStorySection) comicStorySection.classList.add('hidden');
  if (heroSelectionSection) heroSelectionSection.classList.remove('hidden');

  if (generatedComicTitle) generatedComicTitle.textContent = '';
  if (slideshowContainer) slideshowContainer.innerHTML = '';

  if (comicOutputStorySummary) {
    comicOutputStorySummary.textContent = 'Cuando generes un cómic con IA, la sinopsis y los paneles aparecerán aquí.';
  }
  if (comicOutputPanels) {
    comicOutputPanels.innerHTML = '';
  }
  if (comicOutputPanelsEmpty) {
    comicOutputPanelsEmpty.textContent = 'Las viñetas generadas se mostrarán en este espacio.';
    comicOutputPanelsEmpty.classList.remove('hidden');
  }

  hideCommunicationPanel();
}

let currentSlide = 0;
function showSlide(index) {
  const slides = slideshowContainer.children;
  if (!slides || slides.length === 0) return;
  Array.from(slides).forEach((slide, i) => {
    slide.classList.toggle('hidden', i !== index);
  });
}

function nextSlide() {
  const slides = slideshowContainer.children;
  if (!slides || slides.length <= 1) return;
  currentSlide = (currentSlide + 1) % slides.length;
  showSlide(currentSlide);
}

slideshowPrev.addEventListener('click', () => {
  const slides = slideshowContainer.children;
  if (!slides || slides.length <= 1) return;
  currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  showSlide(currentSlide);
  if (slideshowInterval) {
    clearInterval(slideshowInterval);
    slideshowInterval = setInterval(nextSlide, 3000);
  }
});

slideshowNext.addEventListener('click', () => {
  nextSlide();
  if (slideshowInterval) {
    clearInterval(slideshowInterval);
    slideshowInterval = setInterval(nextSlide, 3000);
  }
});

closeComicResultButton.addEventListener('click', () => {
  resetSelections();
  clearGeneratedComic();
});

function renderGeneratedComic(data) {
  if (!data) {
    clearGeneratedComic();
    return;
  }

  if (heroSelectionSection) heroSelectionSection.classList.add('hidden');
  if (comicSlideshowSection) comicSlideshowSection.classList.remove('hidden');
  if (comicStorySection) comicStorySection.classList.remove('hidden');

  const story = data.story || {};
  const panels = Array.isArray(story.panels) ? story.panels : [];

  if (generatedComicTitle) {
    generatedComicTitle.textContent = story.title || 'Cómic generado con IA';
  }

  const selectedHeroes = Array.from(heroState.selected.values());
  if (slideshowContainer) {
    slideshowContainer.innerHTML = '';
    if (selectedHeroes.length > 0) {
      selectedHeroes.forEach((hero, index) => {
        const slide = document.createElement('div');
        slide.className = 'transition-opacity duration-700 ease-in-out';
        if (index !== 0) slide.classList.add('hidden');
        slide.innerHTML = `<img src="${hero.imagen}" class="absolute block w-full h-full object-cover" alt="${hero.nombre}">`;
        slideshowContainer.appendChild(slide);
      });
    }

    const slides = slideshowContainer.children;
    if (slides.length > 1) {
      slideshowPrev.classList.remove('hidden');
      slideshowNext.classList.remove('hidden');
      if (slideshowInterval) clearInterval(slideshowInterval);
      slideshowInterval = setInterval(nextSlide, 3000);
    } else {
      slideshowPrev.classList.add('hidden');
      slideshowNext.classList.add('hidden');
      if (slideshowInterval) clearInterval(slideshowInterval);
    }
    currentSlide = 0;
    showSlide(0);
  }

  if (comicOutputStorySummary) {
    comicOutputStorySummary.textContent = story.summary || 'La IA generó este cómic en función de tu selección de héroes.';
  }

  if (comicOutputPanels) {
    comicOutputPanels.innerHTML = '';
    panels.forEach((panel, index) => {
      const panelCard = document.createElement('article');
      panelCard.className = 'rounded-xl border border-slate-700/60 bg-slate-900/50 p-4 space-y-3';

      if (panel.image) {
        const image = document.createElement('img');
        image.src = panel.image;
        image.alt = panel.title ? `Viñeta ${index + 1}: ${panel.title}` : `Viñeta ${index + 1}`;
        image.className = 'w-full rounded-lg border border-slate-700/50 object-cover';
        panelCard.appendChild(image);
      }

      const title = document.createElement('h4');
      title.className = 'text-lg font-semibold text-white';
      title.textContent = panel.title || `Viñeta ${index + 1}`;
      panelCard.appendChild(title);

      if (panel.description) {
        const description = document.createElement('p');
        description.className = 'text-sm text-gray-300 leading-relaxed';
        description.textContent = panel.description;
        panelCard.appendChild(description);
      }

      if (panel.caption) {
        const caption = document.createElement('p');
        caption.className = 'text-xs text-gray-400 italic';
        caption.textContent = panel.caption;
        panelCard.appendChild(caption);
      }

      comicOutputPanels.appendChild(panelCard);
    });
  }

  if (comicOutputPanelsEmpty) {
    comicOutputPanelsEmpty.classList.toggle('hidden', panels.length > 0);
    if (panels.length === 0) {
      comicOutputPanelsEmpty.textContent = 'La IA no devolvió viñetas. Intenta generar nuevamente.';
    }
  }
}

function updateActivityView() {
  const total = activityState.entries.length;
  if (total === 0) {
    activityEmpty.classList.remove('hidden');
    activityView.classList.add('hidden');
    return;
  }

  const entry = activityState.entries[activityState.index];
  activityEmpty.classList.add('hidden');
  activityView.classList.remove('hidden');

  const baseTagClasses = 'inline-flex items-center px-2 py-1 rounded-md text-[0.65rem] font-black uppercase tracking-[0.18em] border';
  activityTag.className = `${baseTagClasses} ${ACTIVITY_STYLES[entry.type] || 'text-gray-300 border-slate-500/40'}`;
  activityTag.textContent = entry.label;
  activityDate.textContent = formatDateTime(entry.date);
  activityCounter.textContent = `${activityState.index + 1}/${total}`;
  activityTitle.textContent = entry.message;
}

function persistActivityState() {
  if (typeof localStorage === 'undefined') return;
  try {
    const payload = {
      entries: activityState.entries.map(entry => ({
        ...entry,
        date: entry.date instanceof Date ? entry.date.toISOString() : entry.date
      })),
      index: activityState.index
    };
    localStorage.setItem(ACTIVITY_STORAGE_KEY, JSON.stringify(payload));
  } catch (error) {
    console.error('No se pudo guardar la actividad.', error);
  }
}

function hydrateActivityState() {
  if (typeof localStorage === 'undefined') return;
  try {
    const raw = localStorage.getItem(ACTIVITY_STORAGE_KEY);
    if (!raw) return;
    const payload = JSON.parse(raw);
    if (!payload || !Array.isArray(payload.entries)) return;

    activityState.entries = payload.entries.map(entry => ({
      ...entry,
      date: entry.date ? new Date(entry.date) : new Date()
    }));
    if (activityState.entries.length === 0) {
      activityState.index = -1;
      return;
    }
    const storedIndex = typeof payload.index === 'number' ? payload.index : activityState.entries.length - 1;
    activityState.index = Math.min(Math.max(storedIndex, 0), activityState.entries.length - 1);
  } catch (error) {
    console.error('No se pudo restaurar la actividad.', error);
    activityState.entries = [];
    activityState.index = -1;
  }
}

function pushActivity(entry) {
  const normalizedEntry = {
    ...entry,
    date: entry.date instanceof Date ? entry.date : new Date(entry.date || Date.now())
  };
  activityState.entries.push(normalizedEntry);
  activityState.index = activityState.entries.length - 1;
  persistActivityState();
  updateActivityView();
}

function handleActivityNavigation(direction) {
  const total = activityState.entries.length;
  if (total === 0) return;
  activityState.index = (activityState.index + direction + total) % total;
  persistActivityState();
  updateActivityView();
}

activityPrevButton.addEventListener('click', () => handleActivityNavigation(-1));
activityNextButton.addEventListener('click', () => handleActivityNavigation(1));
activityClearButton.addEventListener('click', () => {
  activityState.entries = [];
  activityState.index = -1;
  persistActivityState();
  updateActivityView();
});

function updateSelectedHeroesUI() {
  const heroes = Array.from(heroState.selected.values());
  selectedHeroesList.innerHTML = '';

  if (heroes.length === 0) {
    selectedHeroesEmpty.classList.remove('hidden');
  } else {
    selectedHeroesEmpty.classList.add('hidden');
    heroes.forEach(hero => {
      const badge = document.createElement('span');
      badge.className = 'selected-hero-badge';
      badge.innerHTML = `
        <span>${hero.nombre}</span>
        <button type="button" class="selected-hero-remove" aria-label="Quitar ${hero.nombre}" data-hero-id="${hero.heroId}">✕</button>
      `;
      selectedHeroesList.appendChild(badge);
    });
  }

  selectedHeroesCount.textContent = heroes.length.toString();
  selectedHeroesInput.value = JSON.stringify(heroes.map(hero => hero.heroId));
}

selectedHeroesList.addEventListener('click', (event) => {
  const button = event.target.closest('.selected-hero-remove');
  if (!button) return;
  const heroId = button.dataset.heroId;
  if (!heroId) return;
  const card = heroGrid.querySelector(`[data-hero-id="${escapeSelector(heroId)}"]`);
  const checkbox = card?.querySelector('input[type="checkbox"]');
  if (checkbox) {
    checkbox.checked = false;
    toggleHeroSelection(heroId, false);
  }
});

function toggleHeroSelection(heroId, shouldSelect) {
  const hero = heroState.all.find(item => (item.heroId || item.id || item.uuid) === heroId);
  if (!hero) return;

  const card = heroGrid.querySelector(`[data-hero-id="${escapeSelector(heroId)}"]`);
  if (shouldSelect) {
    heroState.selected.set(heroId, hero);
    card?.classList.add('is-selected');
    pushActivity({
      type: 'SELECCION',
      label: 'Selección',
      message: `Héroe añadido al cómic: ${hero.nombre}`,
      date: new Date()
    });
  } else {
    heroState.selected.delete(heroId);
    card?.classList.remove('is-selected');
    pushActivity({
      type: 'DESELECCION',
      label: 'Quitar',
      message: `Héroe retirado del cómic: ${hero.nombre}`,
      date: new Date()
    });
  }

  updateSelectedHeroesUI();
}

function buildHeroCard(hero) {
  const heroId = hero.heroId || hero.id || crypto.randomUUID();
  const isSelected = heroState.selected.has(heroId);
  const card = document.createElement('label');
  card.className = `hero-card cursor-pointer ${isSelected ? 'is-selected' : ''}`;
  card.dataset.heroId = heroId;
  card.innerHTML = `
    <input type="checkbox" class="hero-card-checkbox" data-hero-id="${heroId}" ${isSelected ? 'checked' : ''} aria-label="Seleccionar ${hero.nombre}">
    <img src="${hero.imagen || ''}" alt="${hero.nombre || 'Héroe Marvel'}" class="hero-card-image">
    <div class="flex flex-col gap-2 p-5">
      <h3 class="hero-card-title">${hero.nombre || 'Héroe sin nombre'}</h3>
      <p class="hero-card-meta">${hero.contenido ? hero.contenido.replace(/\n/g, ' ') : 'Sin descripción disponible.'}</p>
    </div>
  `;

  const checkbox = card.querySelector('input[type="checkbox"]');
  checkbox.addEventListener('change', (event) => {
    toggleHeroSelection(heroId, event.target.checked);
  });

  return card;
}

function renderHeroes() {
  const heroes = heroState.filtered;
  heroGrid.innerHTML = '';

  if (heroes.length === 0) {
    heroEmptyState.classList.remove('hidden');
    heroCountLabel.textContent = '0 héroes';
    return;
  }

  heroEmptyState.classList.add('hidden');
  heroCountLabel.textContent = `${heroes.length} ${heroes.length === 1 ? 'héroe' : 'héroes'}`;

  const fragment = document.createDocumentFragment();
  heroes.forEach(hero => fragment.appendChild(buildHeroCard(hero)));
  heroGrid.appendChild(fragment);
}

function applyHeroFilter() {
  const query = (heroSearchInput.value || '').trim().toLowerCase();
  if (!query) {
    heroState.filtered = [...heroState.all];
  } else {
    heroState.filtered = heroState.all.filter(hero => {
      const name = (hero.nombre || '').toLowerCase();
      const content = (hero.contenido || '').toLowerCase();
      return name.includes(query) || content.includes(query);
    });
  }
  renderHeroes();
}

heroSearchInput.addEventListener('input', () => {
  window.requestAnimationFrame(applyHeroFilter);
});

function resetSelections(options = {}) {
  const { suppressActivity = false } = options;
  heroState.selected.clear();
  heroGrid.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.checked = false;
    checkbox.closest('.hero-card')?.classList.remove('is-selected');
  });
  updateSelectedHeroesUI();
  if (!suppressActivity) {
    pushActivity({
      type: 'CANCELADO',
      label: 'Cancelado',
      message: 'Has descartado los cambios del cómic.',
      date: new Date()
    });
  }
}

comicCancelButton.addEventListener('click', () => {
  if (isGeneratingComic) return;
  comicForm.reset();
  resetSelections();
  clearGeneratedComic();
  hideCommunicationPanel();
  showMessage(comicMessage, 'Se limpió la selección y el resultado generado.');
});

/* 🔵 2) SUBMIT: forzamos HUD + pasamos a process */
comicForm.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (isGeneratingComic) return;

  const selectedHeroes = Array.from(heroState.selected.values());
  if (selectedHeroes.length === 0) {
    showMessage(comicMessage, 'Selecciona al menos un héroe antes de generar el cómic.', true);
    hideCommunicationPanel();
    return;
  }

  // Aseguramos que las secciones donde vive el HUD sean visibles antes de mostrarlo
  if (comicStorySection) {
    comicStorySection.classList.remove('hidden');
  }
  if (comicSlideshowSection) {
    comicSlideshowSection.classList.remove('hidden');
  }

  // forzamos HUD por si el click no se disparó
  if (typeof MSC !== 'undefined') {
    if (typeof MSC.showPanel === 'function') {
      MSC.showPanel();
    }
    if (typeof MSC.setStep === 'function') {
      MSC.setStep('send');
      setTimeout(() => {
        if (typeof MSC.setStep === 'function') {
          MSC.setStep('process');
        }
      }, 200);
    }
  }

  setGeneratingState(true);
  showMessage(comicMessage, 'Generando cómic con IA, esto puede tardar unos segundos...');

  try {
    const response = await fetch('/comics/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ heroIds: selectedHeroes.map(hero => hero.heroId) })
    });

    const payload = await response.json().catch(() => null);
    const storyText = typeof payload?.datos?.story?.summary === 'string' && payload.datos.story.summary.trim() !== ''
      ? payload.datos.story.summary
      : typeof payload?.datos?.story?.title === 'string'
        ? payload.datos.story.title.trim()
        : '';

    if (!response.ok || payload?.estado !== 'éxito' || storyText === '') {
      const errorMessage = payload?.message || 'No se pudo generar el cómic con IA.';
      throw new Error(errorMessage);
    }

    /* 🔵 3) AQUÍ metemos el delay para que se vean los pasos */
    if (typeof MSC !== 'undefined') {
      if (typeof MSC.setStep === 'function') {
        MSC.setStep('return'); // ya volvió del 8081
      }
      setTimeout(() => {
        if (typeof MSC.markSuccess === 'function') {
          MSC.markSuccess();
        }
      }, 550); // medio segundo para que el ojo lo vea
    }

    renderGeneratedComic(payload.datos);

    const storyTitle = payload?.datos?.story?.title || 'tu cómic';
    pushActivity({
      type: 'COMIC',
      label: 'Comic IA',
      message: `Generaste "${storyTitle}" con ${selectedHeroes.length} héroes.`,
      date: new Date()
    });

    showMessage(comicMessage, '¡Cómic generado con éxito!');
  } catch (error) {
    console.error(error);
    if (typeof MSC !== 'undefined' && typeof MSC.markError === 'function') {
      MSC.markError();
    }
    showMessage(comicMessage, error instanceof Error ? error.message : 'No se pudo generar el cómic.', true);
  } finally {
    setGeneratingState(false);
  }
});

async function loadHeroes() {
  try {
    const response = await fetch('/heroes', { cache: 'no-store' });
    if (!response.ok) throw new Error('No se pudieron cargar los héroes.');

    const payload = await response.json();
    if (payload?.estado !== 'éxito') {
      throw new Error(payload?.message || 'No se pudieron cargar los héroes.');
    }

    const heroes = Array.isArray(payload?.datos) ? payload.datos : [];

    heroState.all = heroes;
    heroState.filtered = [...heroState.all];
    renderHeroes();
  } catch (error) {
    heroCountLabel.textContent = '0 héroes';
    heroEmptyState.classList.remove('hidden');
    heroEmptyState.textContent = 'No pudimos cargar héroes. Intenta recargar la página.';
    console.error(error);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  clearGeneratedComic();
  loadHeroes();
  hydrateActivityState();
  updateActivityView();
});
