<?php

declare(strict_types=1);

$pageTitle = 'Clean Marvel Album — Álbumes';
$additionalStyles = ['/assets/css/albums.css', '/assets/css/readme.css'];
require __DIR__ . '/header.php';
?>

  <!-- HERO / HEADER -->
  <header class="app-hero">
    <div class="app-hero__inner">
      <div class="space-y-3">
        <h1 class="app-hero__title text-4xl sm:text-5xl">Clean Architecture with Marvel</h1>
        <p class="text-lg text-gray-300 max-w-2xl leading-snug sm:text-xl">
          Explora la arquitectura limpia inspirada en el universo Marvel.
        </p>
      </div>
      <div class="flex w-full flex-wrap items-center gap-4 md:flex-nowrap md:gap-6">
        <p class="app-hero__meta flex-1 min-w-[14rem]">Organiza tus colecciones con un toque heroico</p>
        <div class="flex items-center gap-3 ml-auto">
          <button id="btn-readme" class="btn app-hero__cta app-hero__cta-equal btn-readme" type="button">
            <span>README</span>
          </button>
          <a href="/comic" class="btn app-hero__cta app-hero__cta-equal">
            <span>Tu Comic</span>
            <span class="app-hero__cta-icon" aria-hidden="true">IA</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="site-main">
    <div class="max-w-7xl mx-auto py-8 px-4 space-y-8">
      <div id="focus-edit-backdrop" class="focus-edit-backdrop hidden"></div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- ASIDE IZQUIERDA: Crear Álbum -->
        <aside class="lg:col-span-1 self-start space-y-6">
        <section class="card section-lined rounded-2xl p-6 shadow-xl">
          <h2 class="text-3xl text-white mb-4">Crear Álbum</h2>
          <form id="album-form" class="space-y-4">
            <input id="album-name" class="w-full px-4 py-3 rounded-lg bg-slate-700 border border-slate-600 focus:border-[var(--marvel)] focus:ring-0 focus:outline-none text-white" type="text" placeholder="Nombre del nuevo álbum" required>
            <div class="grid grid-cols-1 gap-4">
              <label class="space-y-2 text-sm font-semibold text-gray-300">
                <span>Portada (URL)</span>
                <input id="album-cover-url" class="w-full px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 focus:border-[var(--marvel)] focus:ring-0 focus:outline-none text-white" type="url" placeholder="https://example.com/imagen.jpg">
              </label>
              <label class="space-y-2 text-sm font-semibold text-gray-300">
                <span>Subir portada</span>
                <input id="album-cover-file" class="w-full text-sm text-gray-200 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-[var(--marvel)] file:text-white hover:file:bg-red-700 cursor-pointer" type="file" accept="image/png,image/jpeg,image/webp">
              </label>
            </div>
            <button class="btn btn-primary w-full" type="submit">Crear Álbum</button>
          </form>
          <p id="album-message" class="text-sm mt-4 hidden msg-hidden"></p>
        </section>

        <!-- ACTIVIDAD: solo reciente con navegación (igual que heroes) -->
        <section class="card section-lined rounded-2xl p-6 shadow-xl">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <h2 class="text-3xl text-white">🔔 Actividad</h2>
            <div class="flex items-center gap-2">
              <button id="album-activity-prev" class="btn btn-secondary text-xs">⟵ Anterior</button>
              <button id="album-activity-next" class="btn btn-secondary text-xs">Siguiente ⟶</button>
              <button id="clear-album-activity" class="btn btn-danger text-xs">Limpiar</button>
            </div>
          </div>

          <div id="album-activity-empty" class="bg-slate-900/70 border border-slate-700 rounded-xl p-4 text-sm text-gray-300 italic">
            No hay actividad registrada.
          </div>

          <div id="album-activity-view" class="hidden bg-slate-900/80 border border-slate-700 rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between">
              <span id="album-activity-tag" class="inline-flex items-center px-2 py-1 rounded-md text-[0.65rem] font-black uppercase tracking-[0.18em] border">—</span>
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <time id="album-activity-date" class="font-mono text-amber-300/80">—</time>
                <span id="album-activity-counter">0/0</span>
              </div>
            </div>
            <p id="album-activity-title" class="text-sm text-gray-100 leading-tight">—</p>
          </div>
        </section>

        <section id="tests-box" class="card section-lined rounded-2xl p-6 shadow-xl space-y-4">
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <h2 class="text-3xl text-white">🧪 Tests</h2>
              <span id="test-status-chip" class="hidden inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-[0.7rem] font-black uppercase tracking-[0.18em] leading-none border border-slate-500/40 bg-slate-800/80 text-slate-200 shadow-sm transition-colors duration-150">—</span>
            </div>
            <button id="tests-toggle" type="button" role="button" class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-[0.7rem] font-black uppercase tracking-[0.18em] leading-none border border-slate-500/40 bg-slate-800/80 text-slate-200 shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-300/30" aria-expanded="true" aria-controls="tests-box">
              <span class="tests-toggle-label">Ocultar tests</span>
              <svg class="tests-toggle-icon h-3.5 w-3.5 opacity-90 transition-transform duration-150 transform rotate-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.939l3.71-3.71a.75.75 0 111.06 1.061l-4.24 4.243a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
          <div id="tests-body" class="space-y-4">
            <p class="text-sm text-slate-300 leading-relaxed">
              Ejecuta la batería de PHPUnit y revisa los resultados en tiempo real sin salir del panel.
            </p>
            <button id="run-tests-btn" class="btn btn-secondary w-full">
              ▶ Ejecutar tests
            </button>
            <p id="test-runner-message" class="hidden text-sm font-semibold"></p>
            <div id="tests-summary" class="hidden space-y-4">
              <div id="test-summary-grid" class="grid grid-cols-2 gap-3 text-sm"></div>
              <div id="test-status-breakdown" class="flex flex-wrap gap-2 text-xs"></div>
              <div class="space-y-2 relative">
                <h3 class="text-xs font-semibold tracking-[0.28em] uppercase text-slate-300 relative z-10">Resultados por test</h3>
                <div id="tests-tbody" class="relative z-10 pr-1 sm:pr-2 pt-2 pb-8 space-y-4"></div>
              </div>
            </div>
          </div>
        </section>
      </aside>

      <!-- MAIN DERECHA: Mis Álbumes -->
      <section class="lg:col-span-2 space-y-8">
        <section class="card section-lined rounded-2xl p-6 shadow-xl">
          <div class="albums-header mb-4">
            <h1 class="text-xl font-semibold text-slate-100 sm:text-2xl">Álbumes</h1>
          </div>

          <!-- Encabezado: contador + filtros + acciones -->
          <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
              <h2 class="text-3xl text-white">Mis Álbumes</h2>
              <span id="albums-counter" class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-slate-700 text-gray-100 border border-slate-600">
                0 álbumes
              </span>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-end sm:gap-3 w-full sm:w-auto sm:ml-auto">
              <div class="sm:w-72">
                <label class="block text-xs text-gray-400 mb-1">Buscar (por nombre)</label>
                <input id="filter-q" type="search" placeholder="Ej: vengadores, 2025…"
                  class="w-full px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 focus:border-[var(--marvel)] focus:ring-0 focus:outline-none text-white"/>
              </div>
              <div class="sm:w-52">
                <label class="block text-xs text-gray-400 mb-1">Ordenar</label>
                <select id="filter-order"
                  class="w-full px-3 py-2 rounded-lg bg-slate-700 border border-slate-600 focus:border-[var(--marvel)] focus:ring-0 focus:outline-none text-white">
                  <option value="recent">Recientes</option>
                  <option value="az">A → Z</option>
                  <option value="za">Z → A</option>
                </select>
              </div>
              <button id="refresh-albums" class="hidden">Refrescar</button>
            </div>
          </div>

          <div id="albums-grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"></div>
        </section>
      </section>
      </div>
    </div>

    <!-- Modal README -->
    <div id="readme-modal" class="readme-modal">
      <div class="readme-container">
        <div class="readme-header">
          <h2>README del proyecto</h2>
          <button id="readme-close" class="readme-close" type="button">×</button>
        </div>
        <div id="readme-content" class="readme-content"></div>
      </div>
    </div>
  </main>

<?php
$scripts = ['/assets/js/albums.js', '/assets/js/readme.js'];
require __DIR__ . '/footer.php';
