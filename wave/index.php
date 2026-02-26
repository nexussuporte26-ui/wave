<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wave Acessórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --blue:    #0A5489;
      --blue-dk: #073d66;
      --blue-lt: #1a7abf;
      --cream:   #FFF3E7;
      --sand:    #F5DEC8;
      --gold:    #C8963E;
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

    /* ── NAV ── */
    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
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
    .nav-links a::after { content:''; position:absolute; bottom:0; left:0; width:0; height:1.5px; background:var(--gold); transition:width .3s var(--ease); }
    .nav-links a:hover { color: var(--gold); }
    .nav-links a:hover::after { width: 100%; }

    .nav-icons { display: flex; gap: 1.2rem; align-items: center; }
    .nav-icons button { background:none; border:none; cursor:pointer; color:var(--blue); font-size:1.1rem; transition:color .25s; }
    .nav-icons button:hover { color: var(--gold); }
    .nav-cart { background:var(--blue); color:var(--cream) !important; padding:.45rem 1.1rem; border-radius:2rem; font-size:.75rem; letter-spacing:.1em; text-transform:uppercase; font-weight:500; transition:background .25s !important; }
    .nav-cart:hover { background: var(--gold) !important; }

    /* ── HERO ── */
    .hero { position:relative; min-height:100vh; display:flex; align-items:center; overflow:hidden; background:linear-gradient(145deg, var(--cream) 0%, #e8f4ff 60%, var(--cream) 100%); }
    .hero-waves { position:absolute; inset:0; pointer-events:none; overflow:hidden; }
    .hero-waves svg { position:absolute; bottom:-2px; width:110%; left:-5%; }
    .wave1 { animation: waveShift 9s ease-in-out infinite alternate; }
    .wave2 { animation: waveShift 12s ease-in-out infinite alternate-reverse; opacity:.6; }
    .wave3 { animation: waveShift 7s ease-in-out infinite alternate; opacity:.35; }
    @keyframes waveShift { 0%{transform:translateX(0) scaleY(1);} 100%{transform:translateX(-4%) scaleY(1.08);} }

    .hero-circle { position:absolute; border-radius:50%; background:radial-gradient(circle, rgba(10,84,137,.12) 0%, transparent 70%); }
    .hc1 { width:600px; height:600px; top:-120px; right:-100px; animation:floatY 8s ease-in-out infinite; }
    .hc2 { width:300px; height:300px; bottom:10%; left:5%; animation:floatY 11s ease-in-out infinite reverse; }
    @keyframes floatY { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-24px);} }

    .hero-content { position:relative; z-index:2; padding:0 5%; max-width:700px; animation:heroIn 1.1s var(--ease) both; }
    @keyframes heroIn { from{opacity:0;transform:translateY(40px);} to{opacity:1;transform:translateY(0);} }

    .hero-tag { display:inline-block; font-size:.72rem; letter-spacing:.22em; text-transform:uppercase; color:var(--gold); border:1px solid var(--gold); padding:.3rem .9rem; border-radius:2rem; margin-bottom:1.6rem; }

    .hero h1 {
      font-family: var(--font-display);
      font-size: clamp(3.2rem, 8.5vw, 7.5rem);
      font-weight: 600;
      line-height: 1.0;
      color: var(--blue);
      margin-bottom: 1.8rem;
      letter-spacing: -.02em;
    }
    .hero h1 em {
      font-style: italic;
      font-weight: 300;
      color: var(--gold);
      display: block;
    }

    /* slogan — mesmo tamanho do título */
    .hero-slogan {
      display: none;
    }

    .hero p { font-size:1.05rem; color:var(--muted); line-height:1.75; max-width:440px; margin-bottom:2.4rem; font-weight:300; }
    .hero-btns { display:flex; gap:1rem; flex-wrap:wrap; }
    .btn-primary { background:var(--blue); color:var(--cream); padding:.85rem 2.2rem; border-radius:3rem; font-size:.85rem; letter-spacing:.08em; text-transform:uppercase; font-weight:500; transition:background .3s, transform .2s; box-shadow:0 8px 28px rgba(10,84,137,.25); }
    .btn-primary:hover { background:var(--gold); transform:translateY(-2px); }
    .btn-outline { border:1.5px solid var(--blue); color:var(--blue); padding:.85rem 2.2rem; border-radius:3rem; font-size:.85rem; letter-spacing:.08em; text-transform:uppercase; font-weight:500; transition:all .3s; }
    .btn-outline:hover { background:var(--blue); color:var(--cream); }

    .scroll-hint { position:absolute; bottom:2.5rem; left:50%; transform:translateX(-50%); display:flex; flex-direction:column; align-items:center; gap:.5rem; font-size:.68rem; letter-spacing:.18em; text-transform:uppercase; color:var(--muted); animation:heroIn 1.4s var(--ease) .5s both; }
    .scroll-line { width:1px; height:48px; background:linear-gradient(to bottom, var(--blue), transparent); animation:lineDrop 1.8s ease-in-out infinite; }
    @keyframes lineDrop { 0%{transform:scaleY(0);transform-origin:top;} 50%{transform:scaleY(1);transform-origin:top;} 51%{transform:scaleY(1);transform-origin:bottom;} 100%{transform:scaleY(0);transform-origin:bottom;} }

    /* ── STRIP ── */
    .strip { background:var(--blue); color:var(--cream); display:flex; align-items:center; justify-content:center; gap:3rem; padding:.85rem 5%; overflow:hidden; font-size:.75rem; letter-spacing:.12em; text-transform:uppercase; }
    .strip span::before { content:'✦'; margin-right:.6rem; color:var(--gold); }

    /* ── SECTIONS ── */
    section { padding:7rem 5%; }
    .section-label { font-size:.72rem; letter-spacing:.25em; text-transform:uppercase; color:var(--gold); margin-bottom:.8rem; }
    .section-title { font-family:var(--font-display); font-size:clamp(2rem,5vw,3.8rem); font-weight:600; color:var(--blue); line-height:1.05; }
    .section-title em { font-style:italic; }

    /* ── CATEGORIES ── */
    .categories { background:var(--cream); }
    .categories-header { text-align:center; margin-bottom:3.5rem; }
    .cat-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:1.5rem; }
    .cat-card { position:relative; overflow:hidden; border-radius:1.2rem; aspect-ratio:3/4; cursor:pointer; transition:transform .45s var(--ease), box-shadow .45s; }
    .cat-card:hover { transform:translateY(-8px); box-shadow:0 20px 50px rgba(10,84,137,.2); }
    .cat-card-bg { position:absolute; inset:0; transition:transform .5s var(--ease); }
    .cat-card:hover .cat-card-bg { transform:scale(1.06); }
    .cat-card:nth-child(1) .cat-card-bg { background:linear-gradient(160deg,#b8d8f0 0%,#0A5489 100%); }
    .cat-card:nth-child(2) .cat-card-bg { background:linear-gradient(160deg,#f5e4cc 0%,#c8963e 100%); }
    .cat-card:nth-child(3) .cat-card-bg { background:linear-gradient(160deg,#c8e6f5 0%,#073d66 100%); }
    .cat-card:nth-child(4) .cat-card-bg { background:linear-gradient(160deg,#fce8d2 0%,#0A5489 100%); }
    .cat-card-wave { position:absolute; bottom:0; left:0; right:0; height:60%; opacity:.15; }
    .cat-card-wave svg { width:100%; height:100%; }
    .cat-icon { position:absolute; top:1.8rem; left:50%; transform:translateX(-50%); font-size:2.8rem; filter:drop-shadow(0 4px 12px rgba(0,0,0,.15)); }
    .cat-info { position:absolute; bottom:0; left:0; right:0; padding:1.5rem 1.4rem; background:linear-gradient(to top,rgba(7,61,102,.85) 0%,transparent 100%); color:#fff; }
    .cat-info h3 { font-family:var(--font-display); font-size:1.5rem; font-weight:600; letter-spacing:.04em; margin-bottom:.2rem; }
    .cat-info span { font-size:.73rem; letter-spacing:.12em; text-transform:uppercase; opacity:.8; }

    /* ── PRODUCTS ── */
    .featured-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:3rem; flex-wrap:wrap; gap:1rem; }
    .view-all { font-size:.8rem; letter-spacing:.12em; text-transform:uppercase; color:var(--blue); border-bottom:1px solid var(--blue); transition:color .25s, border-color .25s; }
    .view-all:hover { color:var(--gold); border-color:var(--gold); }
    .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:2rem; }
    .product-card { border-radius:1.2rem; overflow:hidden; background:var(--white); box-shadow:0 4px 24px rgba(10,84,137,.07); transition:transform .4s var(--ease), box-shadow .4s; cursor:pointer; }
    .product-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(10,84,137,.15); }
    .product-img { aspect-ratio:1; position:relative; overflow:hidden; }
    .product-img-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:4rem; transition:transform .5s var(--ease); }
    .product-card:hover .product-img-placeholder { transform:scale(1.08); }
    .product-card:nth-child(1) .product-img-placeholder { background:linear-gradient(135deg,#cce5f5,#e8f4fc); }
    .product-card:nth-child(2) .product-img-placeholder { background:linear-gradient(135deg,#f5e4cc,#fdf3e7); }
    .product-card:nth-child(3) .product-img-placeholder { background:linear-gradient(135deg,#c8e0f0,#e0f0fa); }
    .product-card:nth-child(4) .product-img-placeholder { background:linear-gradient(135deg,#fce4cc,#fff3e7); }
    .product-badge { position:absolute; top:1rem; left:1rem; background:var(--blue); color:var(--cream); font-size:.65rem; letter-spacing:.1em; text-transform:uppercase; padding:.25rem .7rem; border-radius:2rem; }
    .product-badge.sale { background:var(--gold); }
    .product-wishlist { position:absolute; top:1rem; right:1rem; background:rgba(255,255,255,.85); backdrop-filter:blur(4px); width:2.2rem; height:2.2rem; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1rem; cursor:pointer; transition:background .25s, transform .25s; }
    .product-wishlist:hover { background:#fff; transform:scale(1.15); }
    .product-info { padding:1.25rem 1.4rem 1.5rem; }
    .product-info h4 { font-family:var(--font-display); font-size:1.2rem; font-weight:600; color:var(--text); margin-bottom:.3rem; }
    .product-sub { font-size:.78rem; color:var(--muted); margin-bottom:.8rem; }
    .product-price { display:flex; align-items:center; gap:.7rem; justify-content:space-between; }
    .price-main { font-size:1.1rem; font-weight:600; color:var(--blue); }
    .price-old { font-size:.85rem; color:var(--muted); text-decoration:line-through; }
    .btn-add { background:var(--blue); color:var(--cream); border:none; cursor:pointer; padding:.5rem 1rem; border-radius:2rem; font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; transition:background .25s; }
    .btn-add:hover { background:var(--gold); }

    /* ── BRAND STORY ── */
    .brand-story { background:var(--blue); display:grid; grid-template-columns:1fr 1fr; min-height:560px; border-radius:2rem; overflow:hidden; margin:0 3%; }
    .story-visual { position:relative; overflow:hidden; background:linear-gradient(135deg,#0d6aaa 0%,#073d66 100%); display:flex; align-items:center; justify-content:center; padding:3rem; }
    .story-wave-deco { position:absolute; inset:0; opacity:.12; }
    .story-wave-deco svg { width:100%; height:100%; }
    .story-emblem { position:relative; z-index:2; font-family:var(--font-display); font-size:9rem; color:rgba(255,243,231,.15); font-weight:600; line-height:1; user-select:none; }
    .story-emblem-overlay { position:absolute; inset:0; z-index:3; display:flex; align-items:center; justify-content:center; }
    .story-ring { width:220px; height:220px; border-radius:50%; border:1px solid rgba(255,243,231,.3); display:flex; align-items:center; justify-content:center; animation:rotateSlow 20s linear infinite; }
    @keyframes rotateSlow { to{transform:rotate(360deg);} }
    .story-ring-inner { width:170px; height:170px; border-radius:50%; border:1px dashed rgba(200,150,62,.4); display:flex; align-items:center; justify-content:center; animation:rotateSlow 14s linear infinite reverse; }
    .story-icon-center { font-size:3.5rem; }
    .story-text { padding:4rem 3.5rem; display:flex; flex-direction:column; justify-content:center; color:var(--cream); }
    .story-text .section-label { color:var(--gold); }
    .story-text .section-title { color:var(--cream); margin-bottom:1.4rem; }
    .story-text p { color:rgba(255,243,231,.75); line-height:1.8; font-size:.95rem; font-weight:300; margin-bottom:2rem; }
    .story-stats { display:flex; gap:2.5rem; margin-bottom:2.5rem; }
    .stat-item { display:flex; flex-direction:column; gap:.2rem; }
    .stat-num { font-family:var(--font-display); font-size:2.4rem; font-weight:600; color:var(--cream); line-height:1; }
    .stat-lbl { font-size:.72rem; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,243,231,.55); }
    .btn-light { display:inline-block; border:1.5px solid rgba(255,243,231,.5); color:var(--cream); padding:.85rem 2.2rem; border-radius:3rem; font-size:.85rem; letter-spacing:.08em; text-transform:uppercase; font-weight:500; transition:all .3s; align-self:flex-start; }
    .btn-light:hover { background:rgba(255,243,231,.12); border-color:var(--cream); }

    /* ── FEATURES ── */
    .features { background:var(--cream); display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); }
    .feature-item { display:flex; flex-direction:column; align-items:center; text-align:center; padding:3rem 2rem; border-right:1px solid rgba(10,84,137,.1); transition:background .3s; }
    .feature-item:last-child { border-right:none; }
    .feature-item:hover { background:rgba(10,84,137,.04); }
    .feat-icon { font-size:2.2rem; margin-bottom:1rem; }
    .feat-title { font-family:var(--font-display); font-size:1.15rem; font-weight:600; color:var(--blue); margin-bottom:.4rem; }
    .feat-desc { font-size:.82rem; color:var(--muted); line-height:1.6; font-weight:300; }

    /* ── NEWSLETTER ── */
    .newsletter { text-align:center; background:var(--white); position:relative; overflow:hidden; }
    .newsletter::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse 70% 60% at 50% 100%,rgba(10,84,137,.06) 0%,transparent 70%); pointer-events:none; }
    .newsletter .section-title { margin-bottom:.8rem; }
    .newsletter p { color:var(--muted); font-size:.95rem; max-width:420px; margin:0 auto 2.5rem; font-weight:300; }
    .nl-form { display:flex; gap:.8rem; max-width:480px; margin:0 auto; justify-content:center; flex-wrap:wrap; }
    .nl-form input { flex:1; min-width:220px; padding:.9rem 1.4rem; border-radius:3rem; border:1.5px solid rgba(10,84,137,.25); background:var(--cream); font-family:var(--font-body); font-size:.9rem; outline:none; color:var(--text); transition:border-color .25s, box-shadow .25s; }
    .nl-form input:focus { border-color:var(--blue); box-shadow:0 0 0 3px rgba(10,84,137,.08); }
    .nl-form input::placeholder { color:var(--muted); }

    /* ── FOOTER ── */
    footer { background:var(--blue-dk); color:rgba(255,243,231,.8); padding:4rem 5% 2rem; }
    .footer-top { display:grid; grid-template-columns:1.6fr 1fr 1fr 1fr; gap:3rem; padding-bottom:3rem; border-bottom:1px solid rgba(255,243,231,.12); margin-bottom:2rem; }
    .footer-logo { height:72px; width:auto; display:block; margin-bottom:1rem; filter:brightness(0) invert(1) sepia(1) saturate(0) brightness(2.2); opacity:.82; }
    .footer-brand p { font-size:.85rem; line-height:1.7; font-weight:300; max-width:240px; }
    .footer-social { display:flex; gap:.8rem; margin-top:1.5rem; }
    .social-btn { width:2.2rem; height:2.2rem; border-radius:50%; border:1px solid rgba(255,243,231,.25); display:flex; align-items:center; justify-content:center; font-size:.9rem; cursor:pointer; transition:all .25s; }
    .social-btn:hover { background:var(--gold); border-color:var(--gold); }
    .footer-col h5 { font-size:.72rem; letter-spacing:.2em; text-transform:uppercase; color:var(--gold); margin-bottom:1.2rem; font-weight:600; }
    .footer-col ul { list-style:none; display:flex; flex-direction:column; gap:.65rem; }
    .footer-col ul li { font-size:.85rem; font-weight:300; cursor:pointer; transition:color .25s; }
    .footer-col ul li:hover { color:var(--cream); }
    .footer-bottom { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; font-size:.75rem; color:rgba(255,243,231,.4); }
    .footer-slogan { font-family:var(--font-display); font-style:italic; font-size:.95rem; color:rgba(255,243,231,.35); letter-spacing:.04em; }

    /* ── ANIMATIONS ── */
    .fade-in { opacity:0; transform:translateY(30px); transition:opacity .7s var(--ease), transform .7s var(--ease); }
    .fade-in.visible { opacity:1; transform:translateY(0); }
    .fade-in:nth-child(2) { transition-delay:.1s; }
    .fade-in:nth-child(3) { transition-delay:.2s; }
    .fade-in:nth-child(4) { transition-delay:.3s; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .brand-story { grid-template-columns:1fr; margin:0; border-radius:0; }
      .story-visual { min-height:300px; }
      .footer-top { grid-template-columns:1fr 1fr; }
      .nav-links { display:none; }
    }
    @media (max-width: 600px) {
      .footer-top { grid-template-columns:1fr; }
      .strip { gap:1.5rem; font-size:.65rem; }
      .story-stats { gap:1.5rem; }
      .story-text { padding:2.5rem 1.8rem; }
      .nav-logo-img { height:48px; }
    }
  </style>
</head>
<body>

  <!-- NAV -->
  <nav id="mainNav">
    <a href="index.php"><img class="nav-logo-img" src="logo.png" alt="Wave Acessórios" /></a>
    <div class="nav-links"><!-- ganchos -->
    <a href="index.php">início</a>
      <a href="#">Destaques</a>
      <a href="catalogo.php">Catálogo</a>
      <a href="#">Contato</a>
    </div>
    <div class="nav-icons">
      <button>🔍</button>
      <button>♡</button>
      <a class="nav-cart" href="#">Sacola (0)</a>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-waves">
      <svg class="wave1" viewBox="0 0 1440 320" preserveAspectRatio="none" style="bottom:0;height:260px">
        <path fill="rgba(10,84,137,.09)" d="M0,224L48,197.3C96,171,192,117,288,117.3C384,117,480,171,576,181.3C672,192,768,160,864,149.3C960,139,1056,149,1152,165.3C1248,181,1344,203,1392,213.3L1440,224L1440,320L0,320Z"/>
      </svg>
      <svg class="wave2" viewBox="0 0 1440 320" preserveAspectRatio="none" style="bottom:0;height:200px">
        <path fill="rgba(200,150,62,.08)" d="M0,256L60,240C120,224,240,192,360,186.7C480,181,600,203,720,213.3C840,224,960,224,1080,208C1200,192,1320,160,1380,144L1440,128L1440,320L0,320Z"/>
      </svg>
      <svg class="wave3" viewBox="0 0 1440 320" preserveAspectRatio="none" style="bottom:0;height:140px">
        <path fill="rgba(10,84,137,.06)" d="M0,288L80,272C160,256,320,224,480,218.7C640,213,800,235,960,245.3C1120,256,1280,256,1360,256L1440,256L1440,320L0,320Z"/>
      </svg>
    </div>
    <div class="hero-circle hc1"></div>
    <div class="hero-circle hc2"></div>

    <div class="hero-content">
      <span class="hero-tag">✦ Nova Coleção Verão 2026</span>
      <h1>Sinta a vibe,<br><em>viva o estilo.</em></h1>
      <p>Acessórios praianos criados com delicadeza e alma — para quem carrega o oceano dentro de si.</p>
      <div class="hero-btns">
        <a class="btn-primary" href="#">Explorar coleção</a>
        <a class="btn-outline" href="história">Nossa história</a><!-- ANCORAR  -->
      </div>
    </div>

    <div class="scroll-hint">
      <div class="scroll-line"></div>
      scroll
    </div>
  </section>

  <!-- STRIP -->
  <div class="strip">
    <span>Frete grátis acima de R$99,90</span>
    <span>Parcelamento em até 12x</span>
    <span>garantia de 30 dias</span>
  </div>

  <!-- CATEGORIES -->
  <section class="categories">
    <div class="categories-header">
      <p class="section-label">Navegue por categoria</p>
      <h2 class="section-title">Encontre o seu <em>estilo</em></h2>
    </div>
    <div class="cat-grid">
      <div class="cat-card fade-in">
        <div class="cat-card-bg"></div>
        <div class="cat-card-wave"><svg viewBox="0 0 300 200" preserveAspectRatio="none"><path fill="white" d="M0,100 C50,60 100,140 150,100 C200,60 250,140 300,100 L300,200 L0,200Z"/></svg></div>
        <div class="cat-icon">🐚</div>
        <div class="cat-info"><h3>Colares</h3><span>48 peças</span></div>
      </div>
      <div class="cat-card fade-in">
        <div class="cat-card-bg"></div>
        <div class="cat-card-wave"><svg viewBox="0 0 300 200" preserveAspectRatio="none"><path fill="white" d="M0,120 C60,80 120,160 180,120 C240,80 270,140 300,120 L300,200 L0,200Z"/></svg></div>
        <div class="cat-icon">🌊</div>
        <div class="cat-info"><h3>Pulseiras</h3><span>62 peças</span></div>
      </div>
      <div class="cat-card fade-in">
        <div class="cat-card-bg"></div>
        <div class="cat-card-wave"><svg viewBox="0 0 300 200" preserveAspectRatio="none"><path fill="white" d="M0,90 C40,130 100,50 160,90 C220,130 270,70 300,90 L300,200 L0,200Z"/></svg></div>
        <div class="cat-icon">🌺</div>
        <div class="cat-info"><h3>Brincos</h3><span>35 peças</span></div>
      </div>
      <div class="cat-card fade-in">
        <div class="cat-card-bg"></div>
        <div class="cat-card-wave"><svg viewBox="0 0 300 200" preserveAspectRatio="none"><path fill="white" d="M0,110 C70,70 140,150 200,110 C260,70 290,130 300,110 L300,200 L0,200Z"/></svg></div>
        <div class="cat-icon">🎁</div>
        <div class="cat-info"><h3>Kits Presente</h3><span>12 kits</span></div>
      </div>
    </div>
  </section>

  <!-- PRODUCTS -->
  <section>
    <div class="featured-header">
      <div>
        <p class="section-label">Mais desejados</p>
        <h2 class="section-title">Queridinhos da <em>estação</em></h2>
      </div>
      <a class="view-all" href="#">Ver todos →</a>
    </div>
    <div class="products-grid">
      <div class="product-card fade-in">
        <div class="product-img">
          <div class="product-img-placeholder">🐚</div>
          <span class="product-badge">Novo</span>
          <div class="product-wishlist">♡</div>
        </div>
        <div class="product-info">
          <h4>Colar Concha do Mar</h4>
          <p class="product-sub">Coleção Brisa — Prata 925</p>
          <div class="product-price">
            <span class="price-main">R$ 89,00</span>
            <button class="btn-add">+ Sacola</button>
          </div>
        </div>
      </div>
      <div class="product-card fade-in">
        <div class="product-img">
          <div class="product-img-placeholder">🌊</div>
          <span class="product-badge sale">-20%</span>
          <div class="product-wishlist">♡</div>
        </div>
        <div class="product-info">
          <h4>Pulseira Ondas</h4>
          <p class="product-sub">Macramê Artesanal</p>
          <div class="product-price">
            <div><span class="price-main">R$ 45,00</span><span class="price-old"> R$ 56,00</span></div>
            <button class="btn-add">+ Sacola</button>
          </div>
        </div>
      </div>
      <div class="product-card fade-in">
        <div class="product-img">
          <div class="product-img-placeholder">🌺</div>
          <span class="product-badge">Destaque</span>
          <div class="product-wishlist">♡</div>
        </div>
        <div class="product-info">
          <h4>Brinco Flor Azul</h4>
          <p class="product-sub">Resina & Prata</p>
          <div class="product-price">
            <span class="price-main">R$ 59,00</span>
            <button class="btn-add">+ Sacola</button>
          </div>
        </div>
      </div>
      <div class="product-card fade-in">
        <div class="product-img">
          <div class="product-img-placeholder">✨</div>
          <div class="product-wishlist">♡</div>
        </div>
        <div class="product-info">
          <h4>Kit Verão Wave</h4>
          <p class="product-sub">Colar + Pulseira + Brinco</p>
          <div class="product-price">
            <span class="price-main">R$ 149,00</span>
            <button class="btn-add">+ Sacola</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- BRAND STORY -->
  <div class="brand-story">
    <div class="story-visual">
      <div class="story-wave-deco">
        <svg viewBox="0 0 400 600" preserveAspectRatio="none">
          <path fill="white" d="M0,150 C100,100 200,200 300,150 C350,125 380,175 400,150 L400,600 L0,600Z"/>
          <path fill="white" d="M0,280 C80,240 160,320 240,280 C320,240 370,290 400,270 L400,600 L0,600Z" opacity=".5"/>
          <path fill="white" d="M0,400 C60,370 130,430 200,400 C270,370 330,410 400,390 L400,600 L0,600Z" opacity=".3"/>
        </svg>
      </div>
      <div class="story-emblem">W</div>
      <div class="story-emblem-overlay">
        <div class="story-ring">
          <div class="story-ring-inner">
            <span class="story-icon-center">🌊</span>
          </div>
        </div>
      </div>
    </div>
    <div class="story-text">
      <p class="section-label">Nossa história</p>
      <h2 class="section-title">Nascida <em>do oceano</em></h2>
      <p>A Wave nasceu do amor pelo mar — da areia quente, do sal no cabelo e da liberdade que só a praia oferece. Cada peça é criada com cuidado artesanal para ser sua companheira de todas as marés.</p>
      <div class="story-stats">
        <div class="stat-item"><span class="stat-num">500+</span><span class="stat-lbl">Peças criadas</span></div>
        <div class="stat-item"><span class="stat-num">100%</span><span class="stat-lbl">Artesanal</span></div>
        <div class="stat-item"><span class="stat-num">♥</span><span class="stat-lbl">Com amor</span></div>
      </div>
      <a class="btn-light" href="#">Conheça a Wave →</a>
    </div>
  </div>

  <!-- FEATURES -->
  <div class="features">
    <div class="feature-item"><div class="feat-icon">🚢</div><h4 class="feat-title">Entrega Rápida</h4><p class="feat-desc">Frete grátis em compras acima de R$199 para todo o Brasil.</p></div>
    <div class="feature-item"><div class="feat-icon">✋</div><h4 class="feat-title">100% Artesanal</h4><p class="feat-desc">Cada peça feita à mão com materiais selecionados.</p></div>
    <div class="feature-item"><div class="feat-icon">🔄</div><h4 class="feat-title">Troca Fácil</h4><p class="feat-desc">30 dias para troca ou devolução sem burocracia.</p></div>
    <div class="feature-item"><div class="feat-icon">💳</div><h4 class="feat-title">Parcele Sem Juros</h4><p class="feat-desc">Em até 6x no cartão ou desconto via PIX.</p></div>
  </div>

  <!-- NEWSLETTER -->
  <section class="newsletter">
    <p class="section-label">Fique por dentro</p>
    <h2 class="section-title">Novidades direto para <em>você</em></h2>
    <p>Cadastre-se e ganhe 10% off na primeira compra, além de lançamentos em primeira mão.</p>
    <div class="nl-form">
      <input type="email" placeholder="Seu melhor e-mail" />
      <button class="btn-primary">Quero desconto ✦</button>
    </div>
  </section>

  <!-- FOOTER -->
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
    const nav = document.getElementById('mainNav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 60));

    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.12 });
    document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));

    document.querySelectorAll('.product-wishlist').forEach(btn => {
      btn.addEventListener('click', () => {
        btn.textContent = btn.textContent === '♡' ? '♥' : '♡';
        btn.style.color = btn.textContent === '♥' ? '#c0392b' : '';
      });
    });
  </script>
</body>
</html>
