<?php

declare(strict_types=1);

$pageTitle = 'Clean Marvel Album — Create Your Comic';
$additionalStyles = ['/assets/css/comic.css', '/assets/css/microservice-communication.css'];
require __DIR__ . '/header.php';
?>

  <!-- HERO / HEADER -->
  <header class="app-hero">
    <div class="app-hero__inner">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="space-y-3 max-w-3xl">
          <h1 class="app-hero__title text-4xl sm:text-5xl">Clean Architecture with Marvel</h1>
          <p class="text-lg text-gray-300 max-w-2xl leading-snug sm:text-xl">
            Crea tu cómic de forma divertida con IA.
          </p>
        </div>
      </div>
      <div class="flex w-full flex-wrap items-center gap-4 md:flex-nowrap md:gap-6">
        <p class="app-hero__meta flex-1 min-w-[14rem]">Selecciona héroes increíbles y arma historias inolvidables con un flujo limpio.</p>
        <div class="flex items-center gap-3 ml-auto">
          <a href="/albums" class="btn app-hero__cta app-hero__cta-equal">Inicio</a>
          <a href="/comic" class="btn app-hero__cta app-hero__cta-equal" aria-current="page">
            <span>Tu Comic</span>
            <span class="app-hero__cta-icon" aria-hidden="true">IA</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="site-main">
    <div class="max-w-7xl mx-auto py-8 px-4 space-y-8">
      <div class="flex flex-col lg:flex-row lg:items-start gap-8">
        <!-- ASIDE: Crear cómic + actividad -->
        <aside class="lg:w-1/3 space-y-6">
        <section class="card section-lined rounded-2xl p-6 shadow-xl space-y-6">
          <div class="space-y-2">
            <h2 class="text-3xl text-white">Crear tu cómic</h2>
            <p class="text-sm text-gray-300/80">Selecciona tus héroes favoritos, deja que la IA construya la historia y revisa el resultado al instante.</p>
          </div>

          <form id="comic-form" class="space-y-6">
            <input type="hidden" id="selected-heroes-input" name="heroIds" value="[]">

            <div class="space-y-3">
              <div class="flex items-center justify-between gap-3">
                <p class="text-xs uppercase tracking-[0.28em] text-gray-400">Héroes seleccionados</p>
                <span id="selected-heroes-count" class="text-xs font-semibold text-amber-300/80">0</span>
              </div>
              <p id="selected-heroes-empty" class="text-sm text-gray-400 bg-slate-900/60 border border-dashed border-slate-700 rounded-xl px-4 py-3 text-center">Aún no seleccionas héroes.</p>
              <div id="selected-heroes-list" class="flex flex-wrap gap-2"></div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end pt-2">
              <button type="submit" id="comic-generate" class="btn btn-primary w-full sm:w-auto">
                <span>Generar cómic</span>
              </button>
              <button type="button" id="comic-cancel" class="btn btn-secondary w-full sm:w-auto">Cancelar</button>
            </div>

            <p id="comic-message" class="text-sm hidden msg-hidden"></p>
          </form>
        </section>
        <section class="card section-lined rounded-2xl p-6 shadow-xl space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-3xl text-white">🔔 Actividad</h2>
            <div class="flex items-center gap-2">
              <button id="comic-activity-prev" class="btn btn-secondary text-xs">⟵ Anterior</button>
              <button id="comic-activity-next" class="btn btn-secondary text-xs">Siguiente ⟶</button>
              <button id="comic-activity-clear" class="btn btn-danger text-xs">Limpiar</button>
            </div>
          </div>

          <div id="comic-activity-empty" class="bg-slate-900/70 border border-slate-700 rounded-xl p-4 text-sm text-gray-300 italic">
            Todavía no registramos actividad. Selecciona héroes o genera tu cómic para comenzar.
          </div>

          <div id="comic-activity-view" class="hidden bg-slate-900/80 border border-slate-700 rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between">
              <span id="comic-activity-tag" class="inline-flex items-center px-2 py-1 rounded-md text-[0.65rem] font-black uppercase tracking-[0.18em] border">—</span>
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <time id="comic-activity-date" class="font-mono text-amber-300/80">—</time>
                <span id="comic-activity-counter">0/0</span>
              </div>
            </div>
            <p id="comic-activity-title" class="text-sm text-gray-100 leading-tight">—</p>
          </div>
        </section>
      </aside>

      <!-- MAIN: Comic generation -->
      <section class="lg:w-2/3 flex flex-col gap-6">
        <section id="hero-selection-section" class="card section-lined rounded-2xl p-6 shadow-xl space-y-6">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
              <h2 class="text-3xl text-white">Héroes disponibles</h2>
              <p class="text-sm text-gray-300/80 max-w-xl">Marca tus héroes favoritos para construir la historia perfecta. Puedes combinar héroes de distintos álbumes.</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
              <label class="flex items-center gap-3 bg-slate-800/60 border border-slate-700 rounded-lg px-3 py-2 shadow-inner">
                <span class="text-xs uppercase tracking-[0.25em] text-gray-400">Buscar</span>
                <input id="comic-hero-search" type="search" placeholder="Ej: Spider, Wakanda, Doom..." class="bg-transparent border-0 focus:ring-0 focus:outline-none text-sm text-gray-100 placeholder:text-slate-500 min-w-[12rem]"/>
              </label>
              <span id="comic-hero-count" class="inline-flex items-center justify-center rounded-lg bg-slate-800/70 border border-slate-700 px-3 py-1 text-xs font-semibold text-gray-200">0 héroes</span>
            </div>
          </div>

          <div id="comic-heroes-grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"></div>
          <p id="comic-heroes-empty" class="hidden text-sm text-gray-400 italic text-center py-10">No encontramos héroes que coincidan con tu búsqueda.</p>
        </section>

        <div id="microservice-comm-panel" class="msc-panel msc-hidden">
          <div class="msc-title">Canal seguro 8080 ↔ 8081 · Modo Superhéroe</div>
          <div class="msc-subtitle">Transmisión de órdenes al microservicio de IA</div>
          <ul class="msc-steps">
            <li class="msc-step" data-step="send">▶ Enviando datos desde 8080 → 8081…</li>
            <li class="msc-step" data-step="process">⚙ Procesando en 8081…</li>
            <li class="msc-step" data-step="return">⬅ Devolviendo respuesta a 8080…</li>
          </ul>
          <div class="msc-status" id="msc-status-text"></div>
          <button id="msc-retry" class="msc-retry msc-hidden" type="button">Reintentar transmisión</button>
          <div class="msc-glow"></div>
        </div>

        <section id="comic-slideshow-section" class="hidden relative card section-lined rounded-2xl p-6 shadow-xl space-y-4">
            <button id="close-comic-result" type="button" class="absolute top-4 right-4 text-gray-400 hover:text-white z-20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h2 id="generated-comic-title" class="text-4xl text-white text-center"></h2>
            <div id="hero-slideshow" class="relative w-full max-w-2xl mx-auto">
                <div id="slideshow-container" class="relative h-80 overflow-hidden rounded-lg">
                </div>
                <button id="slideshow-prev" type="button" class="absolute top-0 left-0 z-10 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white">
                        <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>
                    </span>
                </button>
                <button id="slideshow-next" type="button" class="absolute top-0 right-0 z-10 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white">
                        <svg class="w-4 h-4 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                    </span>
                </button>
            </div>
        </section>
        <section id="comic-story-section" class="hidden card section-lined rounded-2xl p-6 shadow-xl space-y-6">
            <div class="space-y-3">
              <h3 class="text-3xl text-white">Historia generada</h3>
              <div class="space-y-2">
                <p id="comic-output-story-summary" class="text-base text-gray-300 leading-relaxed"></p>
              </div>
              <div id="comic-output-panels" class="space-y-4"></div>
              <p id="comic-output-panels-empty" class="text-sm text-gray-400">Las viñetas generadas se mostrarán en este espacio.</p>
            </div>
        </section>
      </section>
      </div>
    </div>
  </main>

<?php
$scripts = ['/assets/js/microservice-communication.js', '/assets/js/comic.js'];
require __DIR__ . '/footer.php';
