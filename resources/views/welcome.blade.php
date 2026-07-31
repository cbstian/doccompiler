<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('landing.meta.title') }}</title>
<meta name="description" content="{{ __('landing.meta.description') }}">

@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="grain"></div>

<header class="site-header">
  <div class="wrap header-inner">
    <a href="#top" class="logo">
      <span class="logo-bracket">&gt;</span>doc<span class="accent-text">compiler</span><span class="cursor">_</span>
    </a>
    <nav class="site-nav" aria-label="Primary">
      <a href="#features">{{ __('landing.nav.features') }}</a>
      <a href="#pipeline">{{ __('landing.nav.pipeline') }}</a>
      <a href="#quickstart">{{ __('landing.nav.quickstart') }}</a>
      <a href="https://github.com/procodigo/doccompiler" class="nav-github" target="_blank" rel="noopener">
        <svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
        <span>{{ __('landing.nav.github') }}</span>
      </a>
      <span class="lang-switcher">
        <a href="{{ url('/en') }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}" aria-label="English">EN</a>
        <span class="lang-sep">|</span>
        <a href="{{ url('/es') }}" class="{{ app()->getLocale() === 'es' ? 'is-active' : '' }}" aria-label="Español">ES</a>
      </span>
    </nav>
  </div>
</header>

<main id="top">

  <!-- ============ HERO ============ -->
  <section class="hero">
    <div class="wrap hero-grid">
      <div class="hero-copy" data-reveal>
        <p class="eyebrow">{!! __('landing.hero.eyebrow') !!}</p>
        <h1>{!! __('landing.hero.heading') !!}</h1>
        <p class="hero-sub">{{ __('landing.hero.sub') }}</p>
        <div class="hero-actions">
          <a class="btn btn-primary" href="https://github.com/procodigo/doccompiler" target="_blank" rel="noopener">
            {{ __('landing.hero.btn_github') }}
          </a>
          <a class="btn btn-ghost" href="#quickstart">{{ __('landing.hero.btn_quickstart') }}</a>
        </div>
        <ul class="hero-meta">
          <li><span class="dot"></span>{{ __('landing.hero.meta_laravel') }}</li>
          <li><span class="dot"></span>{{ __('landing.hero.meta_docker') }}</li>
          <li><span class="dot"></span>{{ __('landing.hero.meta_own_hw') }}</li>
        </ul>
      </div>

      <div class="hero-visual" data-reveal>
        <div class="compiler-panel" id="compilerPanel">
          <div class="panel-titlebar">
            <span class="tb-dot"></span><span class="tb-dot"></span><span class="tb-dot"></span>
            <span class="tb-label">{!! __('landing.compiler.label') !!}</span>
          </div>

          <div class="panel-body">
            <div class="chip-rail" id="chipRail" aria-hidden="true">
              <span class="chip" data-fmt="pdf">.pdf</span>
              <span class="chip" data-fmt="docx">.docx</span>
              <span class="chip" data-fmt="xlsx">.xlsx</span>
              <span class="chip" data-fmt="csv">.csv</span>
              <span class="chip" data-fmt="md">.md</span>
              <span class="chip" data-fmt="txt">.txt</span>
            </div>

            <div class="pipe-arrow" aria-hidden="true">
              <span class="pipe-track"></span>
              <span class="pipe-dot" id="pipeDot"></span>
            </div>

            <div class="output-pane">
              <div class="output-head">
                <span class="dim">{{ __('landing.compiler.output') }}</span>
                <span class="token-counter"><span id="tokenCount">0</span> {{ __('landing.compiler.tokens_saved') }}</span>
              </div>
              <pre class="output-stream" id="outputStream" aria-hidden="true"></pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ FEATURES ============ -->
  <section class="features" id="features">
    <div class="wrap">
      <p class="section-eyebrow" data-reveal>{{ __('landing.features.eyebrow') }}</p>
      <h2 data-reveal>{{ __('landing.features.heading') }}</h2>

      <div class="feature-grid">
        @foreach (__('landing.features.cards') as $card)
        <article class="feature-card" data-reveal>
          <p class="feature-tag">{{ $card['tag'] }}</p>
          <h3>{{ $card['title'] }}</h3>
          <p>{{ $card['body'] }}</p>
        </article>
        @endforeach
      </div>
    </div>
  </section>

  <!-- ============ PIPELINE ============ -->
  <section class="pipeline" id="pipeline">
    <div class="wrap">
      <p class="section-eyebrow" data-reveal>{{ __('landing.pipeline.eyebrow') }}</p>
      <h2 data-reveal>{{ __('landing.pipeline.heading') }}</h2>

      <ol class="pipeline-list">
        @foreach (__('landing.pipeline.steps') as $i => $step)
        <li data-reveal>
          <span class="pl-index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
          <div>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['body'] }}</p>
          </div>
        </li>
        @endforeach
      </ol>
    </div>
  </section>

  <!-- ============ QUICKSTART ============ -->
  <section class="quickstart" id="quickstart">
    <div class="wrap quickstart-grid">
      <div data-reveal>
        <p class="section-eyebrow">{{ __('landing.quickstart.eyebrow') }}</p>
        <h2>{{ __('landing.quickstart.heading') }}</h2>
        <p class="hero-sub">{{ __('landing.quickstart.sub') }}</p>
      </div>

      <div class="code-stack" data-reveal>
        <div class="code-block">
          <div class="code-head">
            <span>{{ __('landing.quickstart.terminal_label') }}</span>
            <button class="copy-btn" data-copy-target="cmd-1">{{ __('landing.quickstart.copy') }}</button>
          </div>
          <pre id="cmd-1">git clone https://github.com/procodigo/doccompiler.git
cd doccompiler
cp .env.example .env
docker compose up -d</pre>
        </div>

        <div class="code-block">
          <div class="code-head">
            <span>{{ __('landing.quickstart.curl_label') }}</span>
            <button class="copy-btn" data-copy-target="cmd-2">{{ __('landing.quickstart.copy') }}</button>
          </div>
          <pre id="cmd-2">curl -X POST http://localhost/api/v1/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "document=@invoice.pdf"</pre>
        </div>
      </div>
    </div>
  </section>

</main>

<footer class="site-footer">
  <div class="wrap footer-inner">
    <div>
      <p class="logo footer-logo">
        <span class="logo-bracket">&gt;</span>doc<span class="accent-text">compiler</span>
      </p>
      <p class="footer-note">{{ __('landing.footer.note') }}</p>
    </div>
    <nav class="footer-links" aria-label="Footer">
      <a href="https://github.com/procodigo/doccompiler" target="_blank" rel="noopener">{{ __('landing.footer.github') }}</a>
      <a href="https://github.com/procodigo/doccompiler/issues" target="_blank" rel="noopener">{{ __('landing.footer.issues') }}</a>
      <a href="https://github.com/procodigo/doccompiler/releases" target="_blank" rel="noopener">{{ __('landing.footer.releases') }}</a>
      <a href="https://github.com/procodigo/doccompiler#readme" target="_blank" rel="noopener">{{ __('landing.footer.docs') }}</a>
    </nav>
  </div>
</footer>

<script>
window.__docLabels = {
    copied: @json(__('landing.quickstart.copied')),
    fallback: 'Select & copy'
};
</script>

</body>
</html>
