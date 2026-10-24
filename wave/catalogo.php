<?php
session_start();
require_once 'conexao.php';

if (isset($_GET['sair'])) { session_destroy(); header("Location: index.php"); exit(); }

$logado   = isset($_SESSION['usuario_id']);
$nivel    = $logado ? ($_SESSION['nivel'] ?? 'usuario') : '';
$nome_s   = $logado ? htmlspecialchars($_SESSION['nome'] ?? '') : '';
$primeiro = $nome_s ? strtok($nome_s, ' ') : '';
$iniciais = '';
if ($logado && $nome_s) { $p = explode(' ', $nome_s); $iniciais = strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):'')); }
$udb = null;
if ($logado) { $uid=(int)$_SESSION['usuario_id']; $r=$conn->query("SELECT nome,email FROM usuarios WHERE id=$uid LIMIT 1"); if($r&&$r->num_rows>0) $udb=$r->fetch_assoc(); }
$eh_admin = $nivel === 'admin';

$cat_icons = ['Colares'=>'🐚','Pulseiras'=>'🌊','Brincos'=>'🌺','Anéis'=>'💍','Kits'=>'🎁'];

// ── Categoria selecionada via URL (?categoria=Colares) ──
$categoria_sel = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// ── Todos os produtos ativos (para grid + busca + contagem por categoria) ──
$todos = [];
$rall = $conn->query("SELECT id,nome,descricao,preco,categoria,estoque,imagem FROM produtos WHERE ativo=1 ORDER BY id DESC");
if ($rall) while ($row = $rall->fetch_assoc()) $todos[] = $row;

// ── Contagem por categoria (mantido para uso futuro/filtros) ──
$contagem_cat = [];
foreach ($todos as $p) {
    $c = $p['categoria'] ?: 'Outros';
    $contagem_cat[$c] = ($contagem_cat[$c] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Catálogo — Wave Acessórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300..800&display=swap" rel="stylesheet"/>
  <style>
    :root{
      --blue:#0A5489;--blue-dk:#073d66;--blue-lt:#1a7abf;
      --cream:#FFF3E7;--sand:#F5DEC8;--gold:#C8963E;
      --white:#FEFCF9;--text:#1a2e3b;--muted:#7a8d99;
      --font-display:'Manrope',system-ui,sans-serif;
      --font-body:'Manrope',system-ui,sans-serif;
      --ease:cubic-bezier(.22,.61,.36,1);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:var(--font-body);background:var(--cream);color:var(--text);min-height:100vh}
    a{text-decoration:none;color:inherit}
    img{display:block;max-width:100%}

    /* ── NAV (mesmo padrão das outras páginas) ── */
    nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:.5rem 5%;background:rgba(255,243,231,.97);backdrop-filter:blur(12px);box-shadow:0 2px 24px rgba(10,84,137,.10);border-bottom:1.5px solid rgba(10,84,137,.08)}
    .nav-logo-img{height:58px;width:auto}
    .nav-links{display:flex;gap:2rem;font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;font-weight:500}
    .nav-links a{color:var(--blue);transition:color .25s;position:relative;padding-bottom:3px}
    .nav-links a::after{content:'';position:absolute;bottom:0;left:0;width:0;height:1.5px;background:var(--gold);transition:width .3s var(--ease)}
    .nav-links a:hover{color:var(--gold)}.nav-links a:hover::after{width:100%}
    .nav-icons{display:flex;gap:.8rem;align-items:center}
    .nav-sb{background:none;border:none;cursor:pointer;color:var(--blue);font-size:1.1rem;transition:color .25s;padding:.3rem}
    .nav-sb:hover{color:var(--gold)}
    .nav-ham{display:none;background:none;border:none;cursor:pointer;color:var(--blue);padding:.3rem;align-items:center;justify-content:center;flex-shrink:0}
    .mobile-nav-panel{display:none;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:var(--white);border-top:1px solid rgba(10,84,137,.1);box-shadow:0 16px 32px rgba(10,84,137,.14);padding:.4rem 5% 1rem;z-index:200}
    .mobile-nav-panel.open{display:flex}
    .mobile-nav-panel a,.mobile-nav-panel button.mnp-item{display:block;width:100%;text-align:left;background:none;border:none;font-family:inherit;padding:.85rem .2rem;font-size:.85rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--blue);border-bottom:1px solid rgba(10,84,137,.07);cursor:pointer}
    .mobile-nav-panel a:last-child,.mobile-nav-panel button.mnp-item:last-child{border-bottom:none}
    .nav-cart{position:relative;background:var(--blue);color:var(--cream)!important;padding:.42rem 1rem;border-radius:2rem;font-size:.74rem;letter-spacing:.1em;text-transform:uppercase;font-weight:500;transition:background .25s!important;display:inline-flex;align-items:center;gap:.4rem}
    .nav-cart:hover{background:var(--gold)!important}
    .cart-badge{background:var(--blue-dk);color:#fff;border-radius:50%;width:18px;height:18px;font-size:.6rem;font-weight:700;display:none;align-items:center;justify-content:center;position:absolute;top:-5px;right:-5px}
    .cart-badge.show{display:flex}
    .nav-login-btn{font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--blue);border:1.5px solid var(--blue);padding:.36rem .85rem;border-radius:2rem;font-weight:500;transition:all .25s}
    .nav-login-btn:hover{background:var(--blue);color:var(--cream)}
    .profile-wrap{position:relative}
    .profile-trigger{display:flex;align-items:center;gap:.45rem;cursor:pointer;padding:.28rem .6rem .28rem .28rem;border-radius:2rem;border:1.5px solid rgba(10,84,137,.2);background:rgba(255,243,231,.85);transition:all .22s;user-select:none}
    .profile-trigger:hover,.profile-trigger.ab{border-color:var(--blue)}
    .nav-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--blue-dk),var(--blue-lt));color:#fff;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center}
    .nav-av.adm{background:linear-gradient(135deg,#5b21b6,#8b5cf6)}
    .nav-nome{font-size:.76rem;font-weight:600;color:var(--blue);max-width:80px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .nav-arrow{font-size:.58rem;color:var(--muted);transition:transform .22s}
    .profile-trigger.ab .nav-arrow{transform:rotate(180deg)}
    .pd{position:absolute;top:calc(100% + .8rem);right:0;width:260px;background:var(--white);border:1px solid rgba(10,84,137,.12);border-radius:1.2rem;box-shadow:0 16px 50px rgba(10,84,137,.16);z-index:300;overflow:hidden;opacity:0;transform:translateY(-10px) scale(.97);pointer-events:none;transition:opacity .22s,transform .22s}
    .pd.ab{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
    .pd-head{padding:1rem 1.2rem;background:linear-gradient(135deg,var(--blue-dk),var(--blue));display:flex;align-items:center;gap:.75rem}
    .pd-av{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.3);color:#fff;font-size:.85rem;font-weight:700;display:flex;align-items:center;justify-content:center}
    .pd-av.adm{background:rgba(139,92,246,.4)}
    .pd-nome{font-size:.84rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .pd-email{font-size:.67rem;color:rgba(255,255,255,.6)}
    .pd-menu{padding:.4rem}
    .pd-item{display:flex;align-items:center;gap:.6rem;padding:.5rem .65rem;border-radius:.65rem;font-size:.79rem;font-weight:500;color:var(--text);transition:background .15s;border:none;background:none;width:100%;text-align:left;font-family:var(--font-body);text-decoration:none;cursor:pointer}
    .pd-item:hover{background:var(--cream)}
    .pd-ic{width:26px;height:26px;border-radius:.4rem;display:flex;align-items:center;justify-content:center;font-size:.82rem}
    .ib{background:#dbeafe}.ip{background:#ede9fe}.ir{background:#fee2e2}
    .pd-sep{height:1px;background:rgba(10,84,137,.08);margin:.22rem .65rem}
    .pd-item.sair{color:#dc2626}.pd-item.sair:hover{background:#fef2f2}

    /* ── SEARCH OVERLAY ── */
    .s-overlay{display:none;position:fixed;inset:0;background:rgba(7,61,102,.6);backdrop-filter:blur(8px);z-index:200;align-items:flex-start;justify-content:center;padding:5rem 1rem 1rem}
    .s-overlay.ab{display:flex}
    .s-box{background:var(--white);border-radius:1.4rem;width:100%;max-width:600px;overflow:hidden;box-shadow:0 24px 80px rgba(7,61,102,.25);animation:sIn .28s var(--ease)}
    @keyframes sIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
    .s-iw{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.1)}
    .s-iw input{flex:1;border:none;outline:none;font-family:var(--font-display);font-size:1.35rem;color:var(--text);background:none}
    .s-close{background:rgba(10,84,137,.08);border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:.82rem;color:var(--blue)}
    .s-results{max-height:360px;overflow-y:auto;padding:.5rem}
    .s-item{display:flex;align-items:center;gap:.9rem;padding:.7rem .85rem;border-radius:.75rem;transition:background .15s;text-decoration:none;color:var(--text)}
    .s-item:hover{background:var(--cream)}
    .s-img{width:46px;height:46px;border-radius:.5rem;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;overflow:hidden}
    .s-img img{width:100%;height:100%;object-fit:cover}
    .s-info h5{font-family:var(--font-display);font-size:.92rem;font-weight:600}
    .s-info span{font-size:.7rem;color:var(--muted)}
    .s-price{margin-left:auto;font-weight:700;color:var(--blue);font-size:.88rem;white-space:nowrap}
    .s-empty{text-align:center;padding:2rem;color:var(--muted);font-size:.86rem}
    .s-footer{padding:.5rem 1.2rem;border-top:1px solid rgba(10,84,137,.08);font-size:.7rem;color:var(--muted);text-align:center}

    /* ── PAGE HEADER ── */
    .page-header{background:linear-gradient(135deg,var(--blue-dk) 0%,var(--blue) 60%,var(--blue-lt) 100%);padding:6.5rem 5% 4.75rem;position:relative;overflow:hidden}
    .page-header::after{content:'';position:absolute;bottom:-1px;left:0;right:0;height:60px;background:var(--cream);clip-path:ellipse(55% 100% at 50% 100%)}
    .breadcrumb{display:flex;align-items:center;gap:.45rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,243,231,.5);margin-bottom:1rem;flex-wrap:wrap}
    .breadcrumb a{color:rgba(255,243,231,.65);transition:color .2s}.breadcrumb a:hover{color:var(--gold)}
    .breadcrumb .sep{opacity:.4}
    .page-header h1{font-family:var(--font-display);font-size:clamp(2.2rem,5vw,3.8rem);font-weight:600;color:#fff;line-height:1.05;letter-spacing:-.01em}
    .page-header h1 em{font-style:italic;font-weight:300;color:rgba(200,150,62,.85)}
    .page-header p{margin-top:.6rem;font-size:.9rem;color:rgba(255,243,231,.6);font-weight:300}

    section{padding:0 5% 4.5rem;max-width:1360px;margin:0 auto}
    .sec-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:1.8rem;flex-wrap:wrap;gap:.8rem}
    .sec-label{font-size:.72rem;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:.4rem}
    .sec-title{font-family:var(--font-display);font-size:clamp(1.7rem,3.4vw,2.5rem);font-weight:600;color:var(--blue)}
    .sec-title em{font-style:italic}

    /* ── PRODUCT CARDS ── */
    .p-card{border-radius:1.2rem;overflow:hidden;background:var(--white);box-shadow:0 4px 22px rgba(10,84,137,.07);transition:transform .35s var(--ease),box-shadow .35s;position:relative}
    .p-card:hover{transform:translateY(-6px);box-shadow:0 16px 38px rgba(10,84,137,.14)}
    .p-img{aspect-ratio:1;position:relative;overflow:hidden;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:3.2rem}
    .p-img img{width:100%;height:100%;object-fit:cover}
    .p-badge{position:absolute;top:.7rem;left:.7rem;background:#9ca3af;color:#fff;font-size:.62rem;letter-spacing:.08em;text-transform:uppercase;padding:.22rem .6rem;border-radius:2rem;font-weight:700}
    .p-info{padding:1rem 1.15rem 1.2rem}
    .p-cat{font-size:.65rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.15rem}
    .p-nome{font-family:var(--font-display);font-size:1.08rem;font-weight:600;margin-bottom:.35rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .p-row{display:flex;align-items:center;justify-content:space-between;gap:.5rem}
    .p-preco{font-size:.98rem;font-weight:700;color:var(--blue)}
    .p-add{background:var(--blue);color:var(--cream);border:none;cursor:pointer;padding:.42rem .85rem;border-radius:2rem;font-size:.68rem;letter-spacing:.06em;text-transform:uppercase;font-weight:600;transition:background .22s;white-space:nowrap}
    .p-add:hover{background:var(--gold)}
    .p-add:disabled{background:#d1d5db;cursor:not-allowed}
    .p-admin-row{display:flex;gap:.5rem;margin-top:.6rem;padding-top:.6rem;border-top:1px dashed rgba(10,84,137,.15)}
    .p-admin-row a{font-size:.68rem;font-weight:700;color:var(--blue)}

    /* ── FILTROS + GRID ── */
    .filters-bar{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.6rem}
    .chip-row{display:flex;gap:.55rem;flex-wrap:wrap}
    .chip{background:var(--white);border:1.5px solid rgba(10,84,137,.16);color:var(--blue);padding:.42rem 1.05rem;border-radius:2rem;font-size:.76rem;font-weight:600;cursor:pointer;transition:all .2s}
    .chip:hover{border-color:var(--blue)}
    .chip.on{background:var(--blue);color:#fff;border-color:var(--blue)}
    .g-search{display:flex;align-items:center;gap:.5rem;background:var(--white);border:1.5px solid rgba(10,84,137,.16);border-radius:2rem;padding:.5rem 1rem;min-width:220px}
    .g-search input{border:none;outline:none;background:none;font-size:.82rem;flex:1;color:var(--text)}
    .products-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.4rem}
    .empty-state{text-align:center;padding:4rem 1rem;color:var(--muted)}
    .empty-state .ic{font-size:3rem;opacity:.3;margin-bottom:.8rem}
    @media(max-width:1100px){.products-grid{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:760px){.products-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.products-grid{grid-template-columns:1fr}}

    /* ── CARROSSEL DO CATÁLOGO ── */
    .carousel-outer{position:relative;margin-bottom:.5rem}
    .carousel-scroll{display:flex;gap:1.4rem;overflow-x:auto;scroll-behavior:smooth;padding:.3rem .2rem 1.1rem;scrollbar-width:none;-ms-overflow-style:none}
    .carousel-scroll::-webkit-scrollbar{display:none}
    .car-item{flex:0 0 250px}
    .car-nav{position:absolute;top:38%;transform:translateY(-50%);width:38px;height:38px;border-radius:50%;background:var(--white);box-shadow:0 6px 20px rgba(10,84,137,.2);border:1.5px solid rgba(10,84,137,.12);cursor:pointer;font-size:1.1rem;line-height:1;color:var(--blue);display:flex;align-items:center;justify-content:center;z-index:5;transition:all .2s}
    .car-nav:hover{background:var(--blue);color:#fff;border-color:var(--blue)}
    .car-nav.prev{left:-8px}
    .car-nav.next{right:-8px}
    .car-viewall{flex:0 0 200px;border-radius:1.2rem;border:2px dashed rgba(10,84,137,.28);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;cursor:pointer;background:rgba(255,255,255,.55);transition:all .25s;text-align:center;padding:1rem}
    .car-viewall:hover{background:var(--white);border-color:var(--blue);transform:translateY(-4px)}
    .car-viewall .cva-ic{font-size:1.9rem}
    .car-viewall .cva-txt{font-family:var(--font-display);font-size:1.05rem;font-weight:600;color:var(--blue)}
    .car-viewall .cva-sub{font-size:.68rem;color:var(--muted)}
    .grid-toggle-row{display:flex;justify-content:center;margin:1.6rem 0 0}
    @media(max-width:640px){.car-item,.car-viewall{flex-basis:190px}.car-nav{display:none}}

    #toast{position:fixed;bottom:1.5rem;right:1.5rem;background:var(--blue);color:#fff;padding:.6rem 1.1rem;border-radius:2rem;font-size:.78rem;font-weight:500;z-index:999;opacity:0;transform:translateY(10px);transition:all .28s;pointer-events:none;max-width:280px}
    @media(max-width:640px){.nav-links{display:none}.nav-nome{display:none}.nav-ham{display:flex}}
  </style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="index.php"><img class="nav-logo-img" src="logo-removebg-preview.png" alt="Wave"/></a>
  <div class="nav-links">
    <a href="index.php">Início</a>
    <a href="catalogo.php">Catálogo</a>
    <a href="index.php#contato">Contato</a>
    <?php if($eh_admin):?><a href="Dashboard.php">Dashboard</a><?php endif;?>
  </div>
  <div class="nav-icons">
    <button class="nav-ham" id="navHam" onclick="toggleMobileNav()" aria-label="Abrir menu" aria-expanded="false" title="Menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    <button class="nav-sb" onclick="openSearch()">🔍</button>
    <a class="nav-cart" href="carrinho.php">🛍 Sacola<span class="cart-badge" id="cartBadge">0</span></a>
    <?php if($logado):?>
    <div class="profile-wrap" id="pWrap">
      <div class="profile-trigger" id="pTrigger" onclick="toggleDrop()">
        <div class="nav-av <?=$eh_admin?'adm':''?>"><?=$iniciais?:'👤'?></div>
        <span class="nav-nome"><?=$primeiro?></span>
        <span class="nav-arrow">▾</span>
      </div>
      <div class="pd" id="pDrop">
        <div class="pd-head">
          <div class="pd-av <?=$eh_admin?'adm':''?>"><?=$iniciais?:'👤'?></div>
          <div><div class="pd-nome"><?=$nome_s?></div><div class="pd-email"><?=htmlspecialchars($udb['email']??'')?></div></div>
        </div>
        <div class="pd-menu">
          <?php if($eh_admin):?>
          <a href="Dashboard.php" class="pd-item"><div class="pd-ic ip">🏠</div>Dashboard</a>
          <a href="cadastro_produto.php" class="pd-item"><div class="pd-ic ib">➕</div>Novo produto</a>
          <div class="pd-sep"></div>
          <?php endif;?>
          <a href="index.php?sair=1" class="pd-item sair"><div class="pd-ic ir">🚪</div>Sair</a>
        </div>
      </div>
    </div>
    <?php else:?>
    <a href="login.php" class="nav-login-btn">Entrar</a>
    <?php endif;?>
  </div>

  <div class="mobile-nav-panel" id="mobileNavPanel">
    <a href="index.php">Início</a>
    <a href="catalogo.php">Catálogo</a>
    <a href="index.php#contato">Contato</a>
    <?php if($eh_admin):?>
      <a href="Dashboard.php">Dashboard</a>
      <a href="cadastro_produto.php">Novo produto</a>
    <?php endif;?>
    <?php if($logado):?>
      <a href="index.php?sair=1">Sair</a>
    <?php else:?>
      <a href="login.php">Entrar</a>
    <?php endif;?>
  </div>
</nav>

<!-- SEARCH -->
<div class="s-overlay" id="sOverlay" onclick="if(event.target===this)closeSearch()">
  <div class="s-box">
    <div class="s-iw">
      <span style="font-size:1rem;opacity:.4">🔍</span>
      <input type="text" id="sInput" placeholder="Buscar acessórios…" oninput="doSearch(this.value)" autocomplete="off"/>
      <button class="s-close" onclick="closeSearch()">✕</button>
    </div>
    <div class="s-results" id="sResults"><div class="s-empty">Comece a digitar…</div></div>
    <div class="s-footer">Esc para fechar</div>
  </div>
</div>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="breadcrumb"><a href="index.php">Início</a><span class="sep">›</span><span style="color:rgba(255,243,231,.85)">Catálogo</span></div>
  <h1>Nosso <em>catálogo</em></h1>
  <p>Peças artesanais feitas para acompanhar cada maré do seu verão.</p>
</div>

<!-- GRID COMPLETO -->
<section id="grid">
  <div class="sec-head">
    <div><p class="sec-label">Tudo em um só lugar</p><h2 class="sec-title">Catálogo <em>completo</em></h2></div>
  </div>
  <div class="filters-bar">
    <div class="chip-row" id="chipRow">
      <button class="chip<?=$categoria_sel===''?' on':''?>" data-cat="">Todos</button>
      <?php foreach($cat_icons as $cnome=>$cicone): ?>
        <button class="chip<?=$categoria_sel===$cnome?' on':''?>" data-cat="<?=htmlspecialchars($cnome)?>"><?=$cicone?> <?=$cnome?></button>
      <?php endforeach; ?>
    </div>
    <div class="g-search">
      <span style="opacity:.45">🔎</span>
      <input type="text" id="gridSearch" placeholder="Buscar no catálogo…"/>
    </div>
  </div>

  <!-- Carrossel (visão padrão, limitada) -->
  <div class="carousel-outer" id="carouselOuter">
    <button class="car-nav prev" id="carPrev" onclick="carScroll(-1)" aria-label="Anterior">‹</button>
    <div class="carousel-scroll" id="productsCarousel"></div>
    <button class="car-nav next" id="carNext" onclick="carScroll(1)" aria-label="Próximo">›</button>
  </div>

  <!-- Grid completo (aparece depois de clicar em "Mostrar tudo") -->
  <div class="products-grid" id="productsGrid" style="display:none"></div>
  <div class="grid-toggle-row" id="gridToggleRow" style="display:none">
    <button class="chip" onclick="voltarCarrossel()">← Ver menos</button>
  </div>

  <div class="empty-state" id="emptyState" style="display:none">
    <div class="ic">🔍</div>
    <p>Nenhum produto encontrado com esse filtro.</p>
  </div>
</section>

<div id="toast"></div>

<script>
  const catIcons = <?= json_encode($cat_icons) ?>;
  const produtosAll = <?= json_encode($todos) ?>;
  const ehAdmin = <?= $eh_admin ? 'true' : 'false' ?>;
  let categoriaAtiva = <?= json_encode($categoria_sel) ?>;

  // ── Carrossel: quantos itens mostrar antes de "Mostrar tudo" ──
  const CAROUSEL_LIMIT = 8;
  let modoExibicao = 'carrossel'; // 'carrossel' | 'tudo'

  function fmt(v){ return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ','); }

  function getCart(){ try{ return JSON.parse(localStorage.getItem('wave_cart')||'[]'); }catch(e){ return []; } }
  function saveCart(c){ try{ localStorage.setItem('wave_cart', JSON.stringify(c)); }catch(e){} }
  function updateBadge(){
    const n = getCart().reduce((s,i)=>s+i.qty,0);
    const b = document.getElementById('cartBadge');
    if(b){ b.textContent=n; b.classList.toggle('show', n>0); }
  }
  function addToCart(id){
    const p = produtosAll.find(x=>x.id==id);
    if(!p) return;
    const cart = getCart();
    const ix = cart.findIndex(i=>i.id==id);
    if(ix>=0) cart[ix].qty++;
    else cart.push({id:p.id,nome:p.nome,preco:parseFloat(p.preco),imagem:p.imagem||'',categoria:p.categoria,qty:1});
    saveCart(cart); updateBadge();
    showToast('✓ '+p.nome+' adicionado à sacola!');
  }

  function cardHTML(p){
    const img = p.imagem ? `<img src="uploads/produtos/${p.imagem}" alt="${p.nome}"/>` : (catIcons[p.categoria]||'💎');
    const esgotado = (p.estoque !== undefined && p.estoque !== null) ? p.estoque <= 0 : false;
    const admRowHTML = ehAdmin ? `<div class="p-admin-row"><a href="cadastro_produto.php?id=${p.id}">✏️ Editar</a></div>` : '';
    return `
      <div class="p-card" data-id="${p.id}">
        <a href="produto.php?id=${p.id}" style="display:block;color:inherit">
          <div class="p-img">
            ${esgotado ? '<span class="p-badge">Esgotado</span>' : ''}
            ${img}
          </div>
        </a>
        <div class="p-info">
          <div class="p-cat">${p.categoria||''}</div>
          <a href="produto.php?id=${p.id}" style="color:inherit"><div class="p-nome">${p.nome}</div></a>
          <div class="p-row">
            <span class="p-preco">${fmt(p.preco)}</span>
            <button class="p-add" ${esgotado?'disabled':''} onclick="addToCart(${p.id})">${esgotado?'Esgotado':'+ Sacola'}</button>
          </div>
          ${admRowHTML}
        </div>
      </div>`;
  }

  function viewAllCardHTML(total){
    return `
      <div class="car-viewall" onclick="verTudo()">
        <span class="cva-ic">🗂️</span>
        <span class="cva-txt">Mostrar tudo</span>
        <span class="cva-sub">${total} peça${total!=1?'s':''} no total</span>
      </div>`;
  }

  // ── Grid + filtros + carrossel ──
  function renderGrid(){
    const q = (document.getElementById('gridSearch').value||'').toLowerCase().trim();
    let list = produtosAll.slice();
    if(categoriaAtiva) list = list.filter(p=>p.categoria===categoriaAtiva);
    if(q) list = list.filter(p=>p.nome.toLowerCase().includes(q) || (p.categoria||'').toLowerCase().includes(q));

    const empty     = document.getElementById('emptyState');
    const carOuter  = document.getElementById('carouselOuter');
    const carTrack  = document.getElementById('productsCarousel');
    const grid      = document.getElementById('productsGrid');
    const toggleRow = document.getElementById('gridToggleRow');

    if(!list.length){
      carOuter.style.display='none';
      grid.style.display='none';
      toggleRow.style.display='none';
      empty.style.display='block';
      return;
    }
    empty.style.display='none';

    const precisaLimitar = list.length > CAROUSEL_LIMIT;

    if(modoExibicao === 'tudo' || !precisaLimitar){
      // Visão completa: grid tradicional com todos os itens do filtro atual
      carOuter.style.display='none';
      grid.style.display='grid';
      grid.innerHTML = list.map(p=>cardHTML(p)).join('');
      toggleRow.style.display = (modoExibicao==='tudo' && precisaLimitar) ? 'flex' : 'none';
    } else {
      // Visão em carrossel: limitada, com card "Mostrar tudo" no final
      grid.style.display='none';
      toggleRow.style.display='none';
      carOuter.style.display='block';
      const limitados = list.slice(0, CAROUSEL_LIMIT);
      carTrack.innerHTML = limitados.map(p=>`<div class="car-item">${cardHTML(p)}</div>`).join('') + viewAllCardHTML(list.length);
      carTrack.scrollLeft = 0;
    }
  }

  function verTudo(){
    modoExibicao = 'tudo';
    renderGrid();
    document.getElementById('grid').scrollIntoView({behavior:'smooth', block:'start'});
  }
  function voltarCarrossel(){
    modoExibicao = 'carrossel';
    renderGrid();
    document.getElementById('grid').scrollIntoView({behavior:'smooth', block:'start'});
  }
  function carScroll(dir){
    const track = document.getElementById('productsCarousel');
    if(!track) return;
    track.scrollBy({left: track.clientWidth * 0.85 * dir, behavior:'smooth'});
  }

  document.getElementById('chipRow').addEventListener('click', e=>{
    const btn = e.target.closest('.chip');
    if(!btn) return;
    categoriaAtiva = btn.dataset.cat;
    modoExibicao = 'carrossel';
    document.querySelectorAll('.chip').forEach(c=>c.classList.toggle('on', c===btn));
    renderGrid();
    const url = new URL(window.location);
    if(categoriaAtiva) url.searchParams.set('categoria', categoriaAtiva); else url.searchParams.delete('categoria');
    window.history.replaceState({}, '', url);
  });
  document.getElementById('gridSearch').addEventListener('input', ()=>{
    modoExibicao = 'carrossel';
    renderGrid();
  });

  // ── Nav dropdown ──
  function toggleDrop(){ document.getElementById('pDrop')?.classList.toggle('ab'); document.getElementById('pTrigger')?.classList.toggle('ab'); }
  document.addEventListener('click', e=>{
    const w=document.getElementById('pWrap');
    if(w && !w.contains(e.target)){ document.getElementById('pDrop')?.classList.remove('ab'); document.getElementById('pTrigger')?.classList.remove('ab'); }
  });

  // ── Menu mobile (hamburguer) ──
  function toggleMobileNav(){
    const panel=document.getElementById('mobileNavPanel');
    const btn=document.getElementById('navHam');
    if(!panel||!btn) return;
    const open=panel.classList.toggle('open');
    btn.setAttribute('aria-expanded', open?'true':'false');
  }
  document.addEventListener('click', e=>{
    const panel=document.getElementById('mobileNavPanel');
    const btn=document.getElementById('navHam');
    if(panel && btn && panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)){
      panel.classList.remove('open'); btn.setAttribute('aria-expanded','false');
    }
  });
  document.querySelectorAll('.mobile-nav-panel a').forEach(a=>a.addEventListener('click', ()=>{
    document.getElementById('mobileNavPanel')?.classList.remove('open');
    document.getElementById('navHam')?.setAttribute('aria-expanded','false');
  }));

  // ── Search overlay ──
  function openSearch(){ document.getElementById('sOverlay').classList.add('ab'); setTimeout(()=>document.getElementById('sInput')?.focus(),80); }
  function closeSearch(){ document.getElementById('sOverlay').classList.remove('ab'); }
  document.addEventListener('keydown', e=>{
    if(e.key==='Escape') closeSearch();
    if((e.ctrlKey||e.metaKey)&&e.key==='k'){ e.preventDefault(); openSearch(); }
  });
  function doSearch(q){
    const r = document.getElementById('sResults');
    if(!q.trim()){ r.innerHTML='<div class="s-empty">Comece a digitar…</div>'; return; }
    const f = produtosAll.filter(p=>p.nome.toLowerCase().includes(q.toLowerCase())||(p.categoria||'').toLowerCase().includes(q.toLowerCase())).slice(0,7);
    if(!f.length){ r.innerHTML=`<div class="s-empty">Nenhum resultado para "<strong>${q}</strong>"</div>`; return; }
    r.innerHTML = f.map(p=>`<a class="s-item" href="produto.php?id=${p.id}">
      <div class="s-img">${p.imagem?`<img src="uploads/produtos/${p.imagem}" alt=""/>`:catIcons[p.categoria]||'💎'}</div>
      <div class="s-info"><h5>${p.nome}</h5><span>${p.categoria}</span></div>
      <span class="s-price">${fmt(p.preco)}</span>
    </a>`).join('');
  }

  // ── Toast ──
  function showToast(m){
    const t=document.getElementById('toast');
    t.textContent=m; t.style.opacity='1'; t.style.transform='translateY(0)';
    setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateY(10px)'; }, 2600);
  }

  // Init
  renderGrid();
  updateBadge();
</script>
</body>
</html>
