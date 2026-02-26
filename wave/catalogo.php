<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Catálogo — Wave Acessórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --blue:    #0A5489;
      --blue-dk: #073d66;
      --blue-lt: #1a7abf;
      --cream:   #FFF3E7;
      --sand:    #F5DEC8;
      --gold:    #C8963E;
      --gold-lt: #e8b96a;
      --white:   #FEFCF9;
      --text:    #1a2e3b;
      --muted:   #7a8d99;
      --font-display: 'Cormorant Garamond', Georgia, serif;
      --font-body:    'DM Sans', sans-serif;
      --ease: cubic-bezier(.22, .61, .36, 1);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: var(--font-body); background: var(--white); color: var(--text); overflow-x: hidden; }
    a { text-decoration: none; color: inherit; }
    img { display: block; max-width: 100%; }
    button { font-family: var(--font-body); cursor: pointer; }

    /* ══════════════════════════════
       NAV — idêntica à home
    ══════════════════════════════ */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 200;
      display: flex; align-items: center; justify-content: space-between;
      padding: .5rem 5%;
      background: rgba(255,243,231,.97);
      backdrop-filter: blur(12px);
      box-shadow: 0 2px 24px rgba(10,84,137,.10);
      border-bottom: 1.5px solid rgba(10,84,137,.08);
      transition: box-shadow .4s var(--ease);
    }
    nav.scrolled { box-shadow: 0 4px 32px rgba(10,84,137,.15); }
    .nav-logo-img { height: 62px; width: auto; transition: transform .3s var(--ease); }
    .nav-logo-img:hover { transform: scale(1.04); }
    .nav-links { display: flex; gap: 2.2rem; font-size: .78rem; letter-spacing: .14em; text-transform: uppercase; font-weight: 500; }
    .nav-links a { color: var(--blue); transition: color .25s; position: relative; padding-bottom: 3px; }
    .nav-links a.active { color: var(--gold); }
    .nav-links a::after { content:''; position:absolute; bottom:0; left:0; width:0; height:1.5px; background:var(--gold); transition:width .3s var(--ease); }
    .nav-links a:hover, .nav-links a.active { color: var(--gold); }
    .nav-links a:hover::after, .nav-links a.active::after { width: 100%; }
    .nav-icons { display: flex; gap: 1.2rem; align-items: center; }
    .nav-icons button { background:none; border:none; color:var(--blue); font-size:1.1rem; transition:color .25s; }
    .nav-icons button:hover { color: var(--gold); }
    .nav-cart { background:var(--blue); color:var(--cream) !important; padding:.45rem 1.1rem; border-radius:2rem; font-size:.75rem; letter-spacing:.1em; text-transform:uppercase; font-weight:500; transition:background .25s !important; }
    .nav-cart:hover { background: var(--gold) !important; }

    /* ══════════════════════════════
       PAGE HERO — menor, elegante
    ══════════════════════════════ */
    .page-hero {
      position: relative;
      padding: 9rem 5% 4rem;
      background: linear-gradient(145deg, var(--cream) 0%, #e8f4ff 60%, var(--cream) 100%);
      overflow: hidden;
    }
    .page-hero::before {
      content: '';
      position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(ellipse 55% 80% at 80% 50%, rgba(10,84,137,.08) 0%, transparent 60%);
    }
    /* ondas decorativas no fundo */
    .ph-waves { position: absolute; bottom: -2px; left: -5%; width: 110%; pointer-events: none; }
    .phw1 { animation: wvShift 10s ease-in-out infinite alternate; }
    .phw2 { animation: wvShift 15s ease-in-out infinite alternate-reverse; opacity: .5; }
    @keyframes wvShift { 0%{transform:translateX(0) scaleY(1);} 100%{transform:translateX(-3%) scaleY(1.06);} }

    .ph-circle { position: absolute; border-radius: 50%; background: radial-gradient(circle, rgba(10,84,137,.1) 0%, transparent 70%); pointer-events: none; }
    .phc1 { width:500px;height:500px; top:-180px;right:-80px; animation:floatY 9s ease-in-out infinite; }
    .phc2 { width:220px;height:220px; bottom:-60px;left:10%; animation:floatY 12s ease-in-out infinite reverse; }
    @keyframes floatY { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-20px);} }

    .ph-inner { position: relative; z-index: 2; }
    .ph-breadcrumb {
      display: flex; align-items: center; gap: .5rem;
      font-size: .72rem; letter-spacing: .15em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 1.2rem;
    }
    .ph-breadcrumb a { color: var(--blue); transition: color .2s; }
    .ph-breadcrumb a:hover { color: var(--gold); }
    .ph-breadcrumb span { color: var(--muted); }

    .ph-inner h1 {
      font-family: var(--font-display);
      font-size: clamp(2.8rem, 6vw, 5.5rem);
      font-weight: 600; line-height: 1; color: var(--blue);
      letter-spacing: -.02em; margin-bottom: .4rem;
    }
    .ph-inner h1 em { font-style: italic; color: var(--gold); }
    .ph-sub {
      font-size: 1rem; color: var(--muted); font-weight: 300;
      max-width: 420px; line-height: 1.7;
    }

    /* contador de resultados */
    .ph-count {
      margin-top: 1.4rem;
      display: inline-flex; align-items: center; gap: .6rem;
      font-size: .75rem; font-weight: 600; letter-spacing: .12em; text-transform: uppercase;
      color: var(--blue);
    }
    .ph-count::before { content:''; display:block; width:24px; height:1.5px; background:var(--gold); }

    /* ══════════════════════════════
       STRIP
    ══════════════════════════════ */
    .strip {
      background: var(--blue); color: var(--cream);
      display: flex; align-items: center; justify-content: center;
      gap: 3rem; padding: .7rem 5%;
      font-size: .72rem; letter-spacing: .12em; text-transform: uppercase;
    }
    .strip span::before { content: '✦'; margin-right: .6rem; color: var(--gold); }

    /* ══════════════════════════════
       LAYOUT: SIDEBAR + GRID
    ══════════════════════════════ */
    .catalog-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 2.5rem;
      padding: 3rem 5% 6rem;
      max-width: 1400px; margin: 0 auto;
    }

    /* ── SIDEBAR ── */
    .sidebar { display: flex; flex-direction: column; gap: 2rem; }

    .filter-block {
      background: var(--cream);
      border-radius: 1.2rem;
      padding: 1.6rem;
      border: 1px solid rgba(10,84,137,.07);
    }
    .filter-block h4 {
      font-family: var(--font-display);
      font-size: 1.1rem; font-weight: 600; color: var(--blue);
      margin-bottom: 1.1rem; padding-bottom: .7rem;
      border-bottom: 1px solid rgba(10,84,137,.1);
    }

    /* categorias no sidebar */
    .cat-list { display: flex; flex-direction: column; gap: .35rem; }
    .cat-btn {
      display: flex; align-items: center; justify-content: space-between;
      padding: .6rem .9rem; border-radius: .75rem; border: none;
      background: transparent; color: var(--text);
      font-size: .84rem; font-weight: 400; text-align: left;
      transition: all .22s; cursor: pointer;
    }
    .cat-btn:hover { background: rgba(10,84,137,.07); color: var(--blue); }
    .cat-btn.active { background: var(--blue); color: var(--cream); font-weight: 600; }
    .cat-btn .cat-count {
      font-size: .68rem; font-weight: 600; letter-spacing: .08em;
      background: rgba(10,84,137,.12); color: var(--blue);
      padding: .15rem .5rem; border-radius: 2rem;
      transition: inherit;
    }
    .cat-btn.active .cat-count { background: rgba(255,243,231,.25); color: var(--cream); }

    /* preços */
    .price-range { display: flex; flex-direction: column; gap: .8rem; }
    .price-row { display: flex; justify-content: space-between; font-size: .8rem; color: var(--muted); }
    .range-slider {
      -webkit-appearance: none; width: 100%; height: 4px;
      background: linear-gradient(to right, var(--blue) 60%, rgba(10,84,137,.15) 60%);
      border-radius: 2px; outline: none;
    }
    .range-slider::-webkit-slider-thumb {
      -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%;
      background: var(--blue); border: 2px solid var(--cream);
      box-shadow: 0 2px 8px rgba(10,84,137,.3); cursor: pointer;
    }
    .price-inputs { display: flex; gap: .5rem; }
    .price-input {
      flex: 1; padding: .5rem .7rem; border-radius: .6rem;
      border: 1.5px solid rgba(10,84,137,.2); background: var(--white);
      font-family: var(--font-body); font-size: .8rem; color: var(--text); outline: none;
    }
    .price-input:focus { border-color: var(--blue); }

    /* materiais / tags */
    .tag-list { display: flex; flex-wrap: wrap; gap: .5rem; }
    .tag-btn {
      padding: .35rem .85rem; border-radius: 2rem;
      border: 1.5px solid rgba(10,84,137,.18);
      background: transparent; color: var(--muted);
      font-size: .76rem; font-weight: 500;
      transition: all .2s; cursor: pointer;
    }
    .tag-btn:hover { border-color: var(--blue); color: var(--blue); }
    .tag-btn.active { border-color: var(--gold); background: var(--gold); color: #fff; }

    /* cores */
    .color-list { display: flex; flex-wrap: wrap; gap: .6rem; }
    .color-dot {
      width: 28px; height: 28px; border-radius: 50%; cursor: pointer;
      border: 2px solid transparent;
      transition: transform .2s, border-color .2s;
      position: relative;
    }
    .color-dot:hover { transform: scale(1.15); }
    .color-dot.active { border-color: var(--blue); box-shadow: 0 0 0 2px var(--white), 0 0 0 4px var(--blue); }

    /* btn limpar filtros */
    .clear-btn {
      width: 100%; padding: .75rem; border-radius: .8rem;
      border: 1.5px solid rgba(10,84,137,.2); background: transparent;
      color: var(--blue); font-size: .78rem; font-weight: 600;
      letter-spacing: .1em; text-transform: uppercase;
      transition: all .25s;
    }
    .clear-btn:hover { background: var(--blue); color: var(--cream); }

    /* ── MAIN CONTENT ── */
    .catalog-main { display: flex; flex-direction: column; gap: 1.8rem; }

    /* barra de ordenação */
    .sort-bar {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 1rem;
      padding: 1rem 1.4rem;
      background: var(--cream); border-radius: 1rem;
      border: 1px solid rgba(10,84,137,.07);
    }
    .sort-left { display: flex; align-items: center; gap: .8rem; }
    .sort-label { font-size: .75rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }
    .sort-select {
      padding: .45rem .9rem; border-radius: .6rem;
      border: 1.5px solid rgba(10,84,137,.18); background: var(--white);
      font-family: var(--font-body); font-size: .82rem; color: var(--text);
      outline: none; cursor: pointer;
      transition: border-color .2s;
    }
    .sort-select:focus { border-color: var(--blue); }

    .sort-right { display: flex; align-items: center; gap: .5rem; }
    .view-btn {
      width: 34px; height: 34px; border-radius: .6rem;
      border: 1.5px solid rgba(10,84,137,.18); background: transparent;
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; transition: all .2s;
    }
    .view-btn:hover, .view-btn.active { background: var(--blue); border-color: var(--blue); color: var(--cream); }

    /* filtros ativos (chips) */
    .active-filters { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .filter-chip {
      display: flex; align-items: center; gap: .4rem;
      padding: .3rem .75rem; border-radius: 2rem;
      background: rgba(10,84,137,.08); color: var(--blue);
      font-size: .74rem; font-weight: 600;
    }
    .chip-remove { background: none; border: none; color: var(--blue); font-size: .85rem; line-height: 1; padding: 0; cursor: pointer; opacity: .7; }
    .chip-remove:hover { opacity: 1; }

    /* ── PRODUCT GRID ── */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.6rem;
    }
    .products-grid.list-view {
      grid-template-columns: 1fr;
    }

    /* card padrão */
    .product-card {
      border-radius: 1.2rem; overflow: hidden;
      background: var(--white);
      box-shadow: 0 4px 24px rgba(10,84,137,.07);
      transition: transform .4s var(--ease), box-shadow .4s;
      cursor: pointer; position: relative;
    }
    .product-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(10,84,137,.15); }

    /* list view card */
    .products-grid.list-view .product-card {
      display: grid; grid-template-columns: 200px 1fr;
      transform: none !important;
    }
    .products-grid.list-view .product-card:hover { box-shadow: 0 8px 32px rgba(10,84,137,.15); }
    .products-grid.list-view .product-img { aspect-ratio: 1; }
    .products-grid.list-view .product-info { padding: 1.8rem 2rem; display: flex; flex-direction: column; justify-content: center; }

    .product-img { aspect-ratio: 1; position: relative; overflow: hidden; }
    .product-img-placeholder {
      width: 100%; height: 100%;
      display: flex; align-items: center; justify-content: center;
      font-size: 3.8rem; transition: transform .5s var(--ease);
    }
    .product-card:hover .product-img-placeholder { transform: scale(1.08); }

    /* cores de placeholder por tipo */
    .placeholder-shell  { background: linear-gradient(135deg, #cce5f5, #e8f4fc); }
    .placeholder-wave   { background: linear-gradient(135deg, #c8dff0, #deeefa); }
    .placeholder-flower { background: linear-gradient(135deg, #f5e4cc, #fdf3e7); }
    .placeholder-kit    { background: linear-gradient(135deg, #fce4cc, #fff3e7); }
    .placeholder-star   { background: linear-gradient(135deg, #e8f0fa, #d0e4f5); }
    .placeholder-sun    { background: linear-gradient(135deg, #fdf0d5, #fff8e8); }
    .placeholder-anchor { background: linear-gradient(135deg, #c8e6f5, #ddf0fb); }
    .placeholder-pearl  { background: linear-gradient(135deg, #f8ece0, #fff3e7); }
    .placeholder-coral  { background: linear-gradient(135deg, #fce0d0, #fff0e8); }
    .placeholder-drop   { background: linear-gradient(135deg, #d0e8f8, #e4f2fc); }
    .placeholder-moon   { background: linear-gradient(135deg, #e8e0f8, #f4f0fd); }
    .placeholder-ring   { background: linear-gradient(135deg, #f5e8d0, #fdf5e4); }

    .product-badge {
      position: absolute; top: .9rem; left: .9rem;
      background: var(--blue); color: var(--cream);
      font-size: .62rem; letter-spacing: .1em; text-transform: uppercase;
      padding: .22rem .65rem; border-radius: 2rem; font-weight: 600;
      z-index: 2;
    }
    .product-badge.sale  { background: #e05252; }
    .product-badge.new   { background: var(--blue); }
    .product-badge.promo { background: var(--gold); color: var(--blue-dk); }

    .product-wishlist {
      position: absolute; top: .9rem; right: .9rem;
      background: rgba(255,255,255,.88); backdrop-filter: blur(4px);
      width: 2.1rem; height: 2.1rem; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: .95rem; border: none; z-index: 2;
      transition: background .2s, transform .2s;
    }
    .product-wishlist:hover { background: #fff; transform: scale(1.15); }
    .product-wishlist.liked { color: #e05252; }

    .product-info { padding: 1.1rem 1.3rem 1.4rem; }
    .product-category {
      font-size: .66rem; font-weight: 600; letter-spacing: .18em; text-transform: uppercase;
      color: var(--gold); margin-bottom: .3rem;
    }
    .product-info h4 {
      font-family: var(--font-display);
      font-size: 1.15rem; font-weight: 600;
      color: var(--text); margin-bottom: .25rem; line-height: 1.2;
    }
    .product-sub { font-size: .76rem; color: var(--muted); margin-bottom: .85rem; }
    .product-footer { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .price-block { display: flex; flex-direction: column; gap: .1rem; }
    .price-main { font-size: 1.05rem; font-weight: 600; color: var(--blue); }
    .price-old  { font-size: .78rem; color: var(--muted); text-decoration: line-through; }

    .btn-add {
      background: var(--blue); color: var(--cream); border: none;
      padding: .5rem 1.1rem; border-radius: 2rem;
      font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; font-weight: 600;
      transition: background .25s, transform .2s;
    }
    .btn-add:hover { background: var(--gold); transform: translateY(-1px); }

    /* stars */
    .stars { font-size: .75rem; color: var(--gold); margin-bottom: .3rem; letter-spacing: .05em; }

    /* ── PAGINATION ── */
    .pagination {
      display: flex; align-items: center; justify-content: center;
      gap: .5rem; padding: 1rem 0 .5rem;
    }
    .pg-btn {
      width: 38px; height: 38px; border-radius: .7rem;
      border: 1.5px solid rgba(10,84,137,.18);
      background: transparent; color: var(--blue);
      font-size: .84rem; font-weight: 600;
      display: flex; align-items: center; justify-content: center;
      transition: all .2s;
    }
    .pg-btn:hover, .pg-btn.active { background: var(--blue); border-color: var(--blue); color: var(--cream); }
    .pg-btn.active { pointer-events: none; }
    .pg-arrow { font-size: 1rem; }

    /* ══════════════════════════════
       BANNER DESTAQUE (mid-page)
    ══════════════════════════════ */
    .mid-banner {
      grid-column: 1 / -1;
      border-radius: 1.6rem; overflow: hidden;
      background: linear-gradient(120deg, #073d66 0%, #0A5489 60%, #1a7abf 100%);
      padding: 3rem 4rem;
      display: flex; align-items: center; justify-content: space-between;
      gap: 2rem; position: relative;
    }
    .mid-banner::before {
      content: '';
      position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(ellipse 50% 80% at 80% 50%, rgba(200,150,62,.25) 0%, transparent 60%);
    }
    .mb-waves { position: absolute; bottom: -2px; left: -5%; width: 110%; opacity: .15; pointer-events: none; }
    .banner-text { position: relative; z-index: 2; }
    .banner-tag {
      display: inline-flex; align-items: center; gap: .5rem;
      font-size: .68rem; font-weight: 700; letter-spacing: .25em; text-transform: uppercase;
      color: var(--gold); margin-bottom: .9rem;
    }
    .banner-tag::before { content:'✦'; }
    .banner-text h2 {
      font-family: var(--font-display);
      font-size: clamp(1.8rem, 3.5vw, 3rem);
      font-weight: 600; color: var(--cream); line-height: 1.05;
      margin-bottom: .5rem;
    }
    .banner-text h2 em { font-style: italic; color: var(--gold); }
    .banner-text p { font-size: .9rem; color: rgba(255,243,231,.65); font-weight: 300; max-width: 360px; }
    .banner-cta {
      position: relative; z-index: 2; flex-shrink: 0;
      background: var(--gold); color: var(--blue-dk);
      padding: .95rem 2.2rem; border-radius: 3rem;
      font-size: .82rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
      transition: background .3s, transform .2s;
      box-shadow: 0 8px 28px rgba(200,150,62,.4);
    }
    .banner-cta:hover { background: var(--gold-lt); transform: translateY(-2px); }

    /* ══════════════════════════════
       EMPTY STATE
    ══════════════════════════════ */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center; padding: 5rem 2rem;
    }
    .empty-state .emoji { font-size: 4rem; margin-bottom: 1rem; }
    .empty-state h3 { font-family: var(--font-display); font-size: 1.8rem; font-weight: 600; color: var(--blue); margin-bottom: .5rem; }
    .empty-state p { font-size: .9rem; color: var(--muted); font-weight: 300; }

    /* ══════════════════════════════
       FOOTER — igual à home
    ══════════════════════════════ */
    footer { background: var(--blue-dk); color: rgba(255,243,231,.8); padding: 4rem 5% 2rem; }
    .footer-top {
      display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr;
      gap: 3rem; padding-bottom: 3rem;
      border-bottom: 1px solid rgba(255,243,231,.12); margin-bottom: 2rem;
    }
    .footer-logo { height: 72px; width: auto; display: block; margin-bottom: 1rem; filter: brightness(0) invert(1) sepia(1) saturate(0) brightness(2.2); opacity: .82; }
    .footer-brand p { font-size: .85rem; line-height: 1.7; font-weight: 300; max-width: 240px; }
    .footer-social { display: flex; gap: .8rem; margin-top: 1.5rem; }
    .social-btn { width:2.2rem;height:2.2rem;border-radius:50%;border:1px solid rgba(255,243,231,.25);display:flex;align-items:center;justify-content:center;font-size:.9rem;cursor:pointer;transition:all .25s; }
    .social-btn:hover { background:var(--gold);border-color:var(--gold); }
    .footer-col h5 { font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:1.2rem;font-weight:600; }
    .footer-col ul { list-style:none;display:flex;flex-direction:column;gap:.65rem; }
    .footer-col ul li { font-size:.85rem;font-weight:300;cursor:pointer;transition:color .25s; }
    .footer-col ul li:hover { color:var(--cream); }
    .footer-bottom { display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:.75rem;color:rgba(255,243,231,.4); }
    .footer-slogan { font-family:var(--font-display);font-style:italic;font-size:.95rem;color:rgba(255,243,231,.35);letter-spacing:.04em; }

    /* ══════════════════════════════
       ANIMATIONS
    ══════════════════════════════ */
    .fade-in { opacity:0; transform:translateY(28px); transition:opacity .65s var(--ease), transform .65s var(--ease); }
    .fade-in.visible { opacity:1; transform:translateY(0); }
    .fade-in:nth-child(2){transition-delay:.07s;}
    .fade-in:nth-child(3){transition-delay:.14s;}
    .fade-in:nth-child(4){transition-delay:.21s;}
    .fade-in:nth-child(5){transition-delay:.1s;}
    .fade-in:nth-child(6){transition-delay:.17s;}

    /* mobile filter toggle */
    .filter-toggle {
      display: none; width: 100%;
      padding: .85rem 1.2rem; border-radius: .9rem;
      background: var(--cream); border: 1.5px solid rgba(10,84,137,.15);
      color: var(--blue); font-size: .82rem; font-weight: 600;
      letter-spacing: .1em; text-transform: uppercase;
      align-items: center; justify-content: space-between;
    }

    /* ══════════════════════════════
       RESPONSIVE
    ══════════════════════════════ */
    @media (max-width: 1100px) {
      .catalog-layout { grid-template-columns: 220px 1fr; }
      .products-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 860px) {
      .catalog-layout { grid-template-columns: 1fr; }
      .sidebar { display: none; }
      .sidebar.open { display: flex; }
      .filter-toggle { display: flex; }
      .products-grid { grid-template-columns: repeat(2, 1fr); }
      .mid-banner { flex-direction: column; text-align: center; padding: 2.5rem 2rem; }
      .banner-text p { max-width: 100%; }
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
    }
    @media (max-width: 540px) {
      .products-grid { grid-template-columns: 1fr; }
      .footer-top { grid-template-columns: 1fr; }
      .products-grid.list-view .product-card { grid-template-columns: 140px 1fr; }
      .nav-logo-img { height: 48px; }
    }
  </style>
</head>
<body>

  <!-- ══ NAV ══ -->
  <nav id="mainNav">
    <a href="waves.html"><img class="nav-logo-img" src="logo.png" alt="Wave Acessórios" /></a>
    <div class="nav-links">
      <a href="index.php">início</a>
      <a href="waves.html">Destaques</a>
      <a href="catalogo.html" class="active">Catálogo</a>
      <a href="#">Contato</a>
    </div>
    <div class="nav-icons">
      <button>🔍</button>
      <button>♡</button>
      <a class="nav-cart" href="#">Sacola (0)</a>
    </div>
  </nav>

  <!-- ══ PAGE HERO ══ -->
  <div class="page-hero">
    <svg class="ph-waves phw1" viewBox="0 0 1440 140" preserveAspectRatio="none" style="height:110px">
      <path fill="rgba(10,84,137,.07)" d="M0,70 C240,30 480,110 720,70 C960,30 1200,110 1440,70 L1440,140 L0,140Z"/>
    </svg>
    <svg class="ph-waves phw2" viewBox="0 0 1440 140" preserveAspectRatio="none" style="height:80px">
      <path fill="rgba(200,150,62,.06)" d="M0,90 C180,50 360,120 540,90 C720,60 900,120 1080,90 C1260,60 1380,90 1440,80 L1440,140 L0,140Z"/>
    </svg>
    <div class="ph-circle phc1"></div>
    <div class="ph-circle phc2"></div>
    <div class="ph-inner">
      <div class="ph-breadcrumb">
        <a href="waves.html">Início</a>
        <span>›</span>
        <span>Catálogo</span>
      </div>
      <h1>Nossa <em>coleção</em></h1>
      <p class="ph-sub">Peças artesanais feitas com alma — explore todos os acessórios e encontre o que é seu.</p>
      <span class="ph-count">157 peças disponíveis</span>
    </div>
  </div>

  <!-- ══ STRIP ══ -->
  <div class="strip">
    <span>Frete grátis acima de R$199</span>
    <span>Parcele em até 6x sem juros</span>
    <span>Feito à mão com amor</span>
  </div>

  <!-- ══ CATALOG LAYOUT ══ -->
  <div class="catalog-layout">

    <!-- ── SIDEBAR ── -->
    <button class="filter-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">
      <span>Filtros</span>
      <span>⚙️</span>
    </button>

    <aside class="sidebar">

      <!-- Categorias -->
      <div class="filter-block">
        <h4>Categorias</h4>
        <div class="cat-list">
          <button class="cat-btn active" onclick="setCategory(this,'Todos')">
            Todos <span class="cat-count">157</span>
          </button>
          <button class="cat-btn" onclick="setCategory(this,'Colares')">
            🐚 Colares <span class="cat-count">48</span>
          </button>
          <button class="cat-btn" onclick="setCategory(this,'Pulseiras')">
            🌊 Pulseiras <span class="cat-count">62</span>
          </button>
          <button class="cat-btn" onclick="setCategory(this,'Brincos')">
            🌺 Brincos <span class="cat-count">35</span>
          </button>
          <button class="cat-btn" onclick="setCategory(this,'Kits')">
            🎁 Kits Presente <span class="cat-count">12</span>
          </button>
        </div>
      </div>

      <!-- Preço -->
      <div class="filter-block">
        <h4>Faixa de preço</h4>
        <div class="price-range">
          <input type="range" class="range-slider" min="0" max="300" value="180" oninput="updatePrice(this)"/>
          <div class="price-row">
            <span>R$ 0</span>
            <span id="priceMax">R$ 180</span>
          </div>
          <div class="price-inputs">
            <input class="price-input" type="text" placeholder="Mín" value="R$ 0"/>
            <input class="price-input" type="text" placeholder="Máx" value="R$ 180"/>
          </div>
        </div>
      </div>

      <!-- Material -->
      <div class="filter-block">
        <h4>Material</h4>
        <div class="tag-list">
          <button class="tag-btn active" onclick="toggleTag(this)">Prata 925</button>
          <button class="tag-btn" onclick="toggleTag(this)">Ouro 18k</button>
          <button class="tag-btn" onclick="toggleTag(this)">Macramê</button>
          <button class="tag-btn" onclick="toggleTag(this)">Resina</button>
          <button class="tag-btn" onclick="toggleTag(this)">Concha</button>
          <button class="tag-btn" onclick="toggleTag(this)">Cristal</button>
          <button class="tag-btn" onclick="toggleTag(this)">Couro</button>
        </div>
      </div>

      <!-- Cores -->
      <div class="filter-block">
        <h4>Cores</h4>
        <div class="color-list">
          <div class="color-dot active" style="background:#0A5489" title="Azul" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#C8963E" title="Dourado" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#FFF3E7;border:1.5px solid #ddd" title="Creme" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#1a2e3b" title="Preto" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#e8b0a0" title="Rosa" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#a0c8a0" title="Verde" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#d0a0d0" title="Lilás" onclick="toggleColor(this)"></div>
          <div class="color-dot" style="background:#f5e4cc" title="Areia" onclick="toggleColor(this)"></div>
        </div>
      </div>

      <!-- Limpar filtros -->
      <button class="clear-btn" onclick="clearFilters()">Limpar filtros</button>

    </aside>

    <!-- ── MAIN ── -->
    <main class="catalog-main">

      <!-- sort bar -->
      <div class="sort-bar">
        <div class="sort-left">
          <span class="sort-label">Ordenar por</span>
          <select class="sort-select">
            <option>Mais relevantes</option>
            <option>Menor preço</option>
            <option>Maior preço</option>
            <option>Mais novos</option>
            <option>Mais vendidos</option>
          </select>
        </div>
        <div class="sort-right">
          <span class="sort-label" style="margin-right:.3rem">Visualizar</span>
          <button class="view-btn active" id="gridBtn" onclick="setView('grid')" title="Grade">⊞</button>
          <button class="view-btn" id="listBtn" onclick="setView('list')" title="Lista">☰</button>
        </div>
      </div>

      <!-- filtros ativos -->
      <div class="active-filters" id="activeFilters">
        <span style="font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)">Filtros:</span>
        <div class="filter-chip">Prata 925 <button class="chip-remove" onclick="this.parentElement.remove()">✕</button></div>
        <div class="filter-chip">Azul <button class="chip-remove" onclick="this.parentElement.remove()">✕</button></div>
      </div>

      <!-- GRID DE PRODUTOS -->
      <div class="products-grid" id="productsGrid">

        <!-- COLARES -->
        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-shell">🐚</div>
            <span class="product-badge new">Novo</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Colares</p>
            <div class="stars">★★★★★</div>
            <h4>Colar Concha do Mar</h4>
            <p class="product-sub">Coleção Brisa — Prata 925</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 89,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-wave">🌊</div>
            <span class="product-badge promo">-20%</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Pulseiras</p>
            <div class="stars">★★★★☆</div>
            <h4>Pulseira Ondas</h4>
            <p class="product-sub">Macramê Artesanal</p>
            <div class="product-footer">
              <div class="price-block">
                <span class="price-main">R$ 45,00</span>
                <span class="price-old">R$ 56,00</span>
              </div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-flower">🌺</div>
            <span class="product-badge new">Destaque</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Brincos</p>
            <div class="stars">★★★★★</div>
            <h4>Brinco Flor Azul</h4>
            <p class="product-sub">Resina & Prata</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 59,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-kit">✨</div>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Kits</p>
            <div class="stars">★★★★★</div>
            <h4>Kit Verão Wave</h4>
            <p class="product-sub">Colar + Pulseira + Brinco</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 149,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-star">⭐</div>
            <span class="product-badge sale">-15%</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Colares</p>
            <div class="stars">★★★★☆</div>
            <h4>Colar Estrela do Mar</h4>
            <p class="product-sub">Prata 925 com banho dourado</p>
            <div class="product-footer">
              <div class="price-block">
                <span class="price-main">R$ 102,00</span>
                <span class="price-old">R$ 120,00</span>
              </div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-sun">☀️</div>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Brincos</p>
            <div class="stars">★★★★★</div>
            <h4>Brinco Sol de Verão</h4>
            <p class="product-sub">Ouro 18k & Cristal</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 78,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <!-- BANNER MID-PAGE -->
        <div class="mid-banner fade-in">
          <svg class="mb-waves" viewBox="0 0 1440 100" preserveAspectRatio="none" style="height:80px">
            <path fill="white" d="M0,50 C240,20 480,80 720,50 C960,20 1200,80 1440,50 L1440,100 L0,100Z"/>
          </svg>
          <div class="banner-text">
            <p class="banner-tag">Kits exclusivos</p>
            <h2>Presenteie com <em>amor do mar</em></h2>
            <p>Kits artesanais embalados com carinho — a escolha perfeita para quem você ama.</p>
          </div>
          <a class="banner-cta" href="#">Ver kits →</a>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-anchor">⚓</div>
            <span class="product-badge new">Novo</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Pulseiras</p>
            <div class="stars">★★★★★</div>
            <h4>Pulseira Âncora</h4>
            <p class="product-sub">Aço inox & Couro natural</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 67,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-pearl">🦪</div>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Colares</p>
            <div class="stars">★★★★☆</div>
            <h4>Colar Pérola do Mar</h4>
            <p class="product-sub">Pérola natural & Prata</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 135,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-coral">🪸</div>
            <span class="product-badge promo">-10%</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Brincos</p>
            <div class="stars">★★★★★</div>
            <h4>Brinco Coral Rosa</h4>
            <p class="product-sub">Resina coral & Prata 925</p>
            <div class="product-footer">
              <div class="price-block">
                <span class="price-main">R$ 49,00</span>
                <span class="price-old">R$ 54,00</span>
              </div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-drop">💧</div>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Colares</p>
            <div class="stars">★★★★★</div>
            <h4>Colar Gota Azul</h4>
            <p class="product-sub">Cristal aqua & Ouro 18k</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 98,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-moon">🌙</div>
            <span class="product-badge new">Novo</span>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Brincos</p>
            <div class="stars">★★★★☆</div>
            <h4>Brinco Lua Crescente</h4>
            <p class="product-sub">Prata 925 & Madrepérola</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 72,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

        <div class="product-card fade-in">
          <div class="product-img">
            <div class="product-img-placeholder placeholder-ring">💍</div>
            <button class="product-wishlist" onclick="toggleWish(this)">♡</button>
          </div>
          <div class="product-info">
            <p class="product-category">Kits</p>
            <div class="stars">★★★★★</div>
            <h4>Kit Noite de Verão</h4>
            <p class="product-sub">Brinco + Colar + Embalagem</p>
            <div class="product-footer">
              <div class="price-block"><span class="price-main">R$ 179,00</span></div>
              <button class="btn-add">+ Sacola</button>
            </div>
          </div>
        </div>

      </div><!-- /products-grid -->

      <!-- Paginação -->
      <div class="pagination">
        <button class="pg-btn pg-arrow">‹</button>
        <button class="pg-btn active">1</button>
        <button class="pg-btn">2</button>
        <button class="pg-btn">3</button>
        <button class="pg-btn">4</button>
        <button class="pg-btn">…</button>
        <button class="pg-btn">13</button>
        <button class="pg-btn pg-arrow">›</button>
      </div>

    </main>
  </div>

  <!-- ══ FOOTER ══ -->
  <footer>
    <div class="footer-top">
      <div class="footer-brand">
        <img class="footer-logo" src="IMG_2267.PNG" alt="Wave Acessórios" />
        <p>Acessórios praianos feitos com alma, para quem ama o mar e vive com leveza.</p>
        <div class="footer-social">
          <div class="social-btn">📸</div>
          <div class="social-btn">📌</div>
          <div class="social-btn">💬</div>
        </div>
      </div>
      <div class="footer-col">
        <h5>Loja</h5>
        <ul><li>Novidades</li><li>Colares</li><li>Pulseiras</li><li>Brincos</li><li>Kits Presente</li></ul>
      </div>
      <div class="footer-col">
        <h5>Informações</h5>
        <ul><li>Nossa História</li><li>Entregas e Prazos</li><li>Trocas e Devoluções</li><li>Formas de Pagamento</li></ul>
      </div>
      <div class="footer-col">
        <h5>Atendimento</h5>
        <ul><li>WhatsApp</li><li>E-mail</li><li>Seg–Sex, 9h às 18h</li></ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Wave Acessórios — Todos os direitos reservados.</span>
      <span class="footer-slogan">Sinta a vibe, viva o estilo. ✦ 2026</span>
    </div>
  </footer>

  <script>
    // Nav scroll
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 60));

    // Fade-in
    const obs = new IntersectionObserver(es => {
      es.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));

    // Wishlist
    function toggleWish(btn) {
      const on = btn.textContent === '♥';
      btn.textContent = on ? '♡' : '♥';
      btn.classList.toggle('liked', !on);
    }

    // Categoria
    function setCategory(btn, name) {
      document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }

    // Preço range
    function updatePrice(input) {
      document.getElementById('priceMax').textContent = 'R$ ' + input.value;
      const pct = (input.value / input.max) * 100;
      input.style.background = `linear-gradient(to right, var(--blue) ${pct}%, rgba(10,84,137,.15) ${pct}%)`;
    }

    // Tag toggle
    function toggleTag(btn) { btn.classList.toggle('active'); }

    // Color toggle
    function toggleColor(dot) { dot.classList.toggle('active'); }

    // Limpar filtros
    function clearFilters() {
      document.querySelectorAll('.cat-btn').forEach((b,i) => b.classList.toggle('active', i===0));
      document.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.color-dot').forEach(d => d.classList.remove('active'));
    }

    // View toggle
    function setView(mode) {
      const grid = document.getElementById('productsGrid');
      const gBtn = document.getElementById('gridBtn');
      const lBtn = document.getElementById('listBtn');
      if (mode === 'list') {
        grid.classList.add('list-view');
        lBtn.classList.add('active'); gBtn.classList.remove('active');
      } else {
        grid.classList.remove('list-view');
        gBtn.classList.add('active'); lBtn.classList.remove('active');
      }
    }

    // Paginação
    document.querySelectorAll('.pg-btn:not(.pg-arrow)').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.pg-btn:not(.pg-arrow)').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        window.scrollTo({ top: 300, behavior: 'smooth' });
      });
    });
  </script>
</body>
</html>
