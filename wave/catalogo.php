<?php
session_start();
require_once 'conexão.php';

if (isset($_GET['sair'])) { session_destroy(); header("Location: index.php"); exit(); }

$logado   = isset($_SESSION['usuario_id']);
$nivel    = $logado ? ($_SESSION['nivel'] ?? 'usuario') : '';
$nome_s   = $logado ? htmlspecialchars($_SESSION['nome'] ?? '') : '';
$primeiro = $nome_s ? strtok($nome_s, ' ') : '';
$iniciais = '';
if ($logado && $nome_s) { $p = explode(' ',$nome_s); $iniciais = strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):'')); }
$udb = null;
if ($logado) { $uid=(int)$_SESSION['usuario_id']; $r=$conn->query("SELECT nome,email,telefone,criado_em FROM usuarios WHERE id=$uid LIMIT 1"); if($r&&$r->num_rows>0) $udb=$r->fetch_assoc(); }
$eh_admin = $nivel === 'admin';

// ═══════════════════════════════════════════════════════════════
// BUSCAR TODOS OS PRODUTOS ATIVOS
// ═══════════════════════════════════════════════════════════════
$produtos = [];
$result_prod = $conn->query("SELECT id, nome, descricao, preco, categoria, estoque, ativo, imagem FROM produtos WHERE ativo = 1 ORDER BY id DESC");
if ($result_prod) { while ($row = $result_prod->fetch_assoc()) $produtos[] = $row; }

// DESTAQUES (ADMIN)
$produtos_destaque = [];
if ($eh_admin) {
    $result_dest = $conn->query("SELECT id, nome, descricao, preco, categoria, estoque, destaque, imagem FROM produtos WHERE ativo = 1 ORDER BY destaque DESC, id DESC");
    if ($result_dest) { while ($row = $result_dest->fetch_assoc()) $produtos_destaque[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Catálogo — Wave Acessórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root{
      --blue:#0A5489;--blue-dk:#073d66;--blue-lt:#1a7abf;
      --cream:#FFF3E7;--sand:#F5DEC8;--gold:#C8963E;
      --white:#FEFCF9;--text:#1a2e3b;--muted:#7a8d99;
      --font-display:'Cormorant Garamond',Georgia,serif;
      --font-body:'DM Sans',sans-serif;
      --ease:cubic-bezier(.22,.61,.36,1);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:var(--font-body);background:var(--white);color:var(--text);overflow-x:hidden}
    a{text-decoration:none;color:inherit}

    /* ── NAV ── */
    nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:.5rem 5%;background:rgba(255,243,231,.97);backdrop-filter:blur(12px);box-shadow:0 2px 24px rgba(10,84,137,.10);border-bottom:1.5px solid rgba(10,84,137,.08);transition:box-shadow .4s var(--ease)}
    nav.scrolled{box-shadow:0 4px 32px rgba(10,84,137,.15)}
    .nav-logo-img{height:62px;width:auto;transition:transform .3s var(--ease)}
    .nav-logo-img:hover{transform:scale(1.04)}
    .nav-links{display:flex;gap:2.2rem;font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;font-weight:500}
    .nav-links a{color:var(--blue);transition:color .25s;position:relative;padding-bottom:3px}
    .nav-links a::after{content:'';position:absolute;bottom:0;left:0;width:0;height:1.5px;background:var(--gold);transition:width .3s var(--ease)}
    .nav-links a:hover{color:var(--gold)} .nav-links a:hover::after,.nav-links a.ativo::after{width:100%}
    .nav-links a.ativo{color:var(--gold)}
    .nav-icons{display:flex;gap:.9rem;align-items:center}
    .nav-icons button{background:none;border:none;cursor:pointer;color:var(--blue);font-size:1.1rem;transition:color .25s}
    .nav-icons button:hover{color:var(--gold)}
    .nav-cart{background:var(--blue);color:var(--cream)!important;padding:.45rem 1.1rem;border-radius:2rem;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;font-weight:500;transition:background .25s!important}
    .nav-cart:hover{background:var(--gold)!important}
    .nav-login-btn{font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--blue);border:1.5px solid var(--blue);padding:.38rem .9rem;border-radius:2rem;font-weight:500;transition:all .25s;white-space:nowrap}
    .nav-login-btn:hover{background:var(--blue);color:var(--cream)}
    .nav-cad-btn{background:var(--blue);color:var(--cream);border:none;padding:.42rem 1rem;border-radius:2rem;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;font-weight:500;cursor:pointer;font-family:var(--font-body);transition:background .25s}
    .nav-cad-btn:hover{background:var(--gold)}
    .profile-wrap{position:relative}
    .profile-trigger{display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.3rem .65rem .3rem .3rem;border-radius:2rem;border:1.5px solid rgba(10,84,137,.2);background:rgba(255,243,231,.85);transition:all .22s;user-select:none}
    .profile-trigger:hover,.profile-trigger.ab{border-color:var(--blue);background:var(--cream)}
    .nav-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue-dk),var(--blue-lt));color:#fff;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .nav-av.adm{background:linear-gradient(135deg,#5b21b6,#8b5cf6)}
    .nav-nome{font-size:.78rem;font-weight:600;color:var(--blue);max-width:90px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .nav-arrow{font-size:.6rem;color:var(--muted);transition:transform .22s}
    .profile-trigger.ab .nav-arrow{transform:rotate(180deg)}
    .pd{position:absolute;top:calc(100% + .8rem);right:0;width:310px;background:var(--white);border:1px solid rgba(10,84,137,.12);border-radius:1.2rem;box-shadow:0 16px 50px rgba(10,84,137,.16);z-index:300;overflow:hidden;opacity:0;transform:translateY(-10px) scale(.97);pointer-events:none;transition:opacity .22s var(--ease),transform .22s var(--ease)}
    .pd.ab{opacity:1;transform:translateY(0) scale(1);pointer-events:auto}
    .pd-head{padding:1.2rem 1.3rem 1rem;background:linear-gradient(135deg,var(--blue-dk),var(--blue));display:flex;align-items:center;gap:.9rem}
    .pd-av{width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.35);color:#fff;font-size:1rem;font-weight:700;flex-shrink:0;display:flex;align-items:center;justify-content:center}
    .pd-av.adm{background:rgba(139,92,246,.4);border-color:rgba(167,139,250,.5)}
    .pd-nome{font-size:.9rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:185px}
    .pd-email{font-size:.7rem;color:rgba(255,255,255,.65);margin-top:.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:185px}
    .pd-badge{display:inline-flex;align-items:center;background:rgba(255,255,255,.15);color:rgba(255,255,255,.9);border-radius:999px;padding:.15rem .5rem;font-size:.62rem;font-weight:600;margin-top:.3rem}
    .pd-stats{display:flex;border-bottom:1px solid rgba(10,84,137,.08)}
    .pd-stat{flex:1;padding:.65rem .5rem;text-align:center;border-right:1px solid rgba(10,84,137,.08)}
    .pd-stat:last-child{border-right:none}
    .pd-sv{font-size:.9rem;font-weight:700;color:var(--blue);font-family:var(--font-display)}
    .pd-sl{font-size:.6rem;color:var(--muted);margin-top:.06rem}
    .pd-menu{padding:.5rem}
    .pd-item{display:flex;align-items:center;gap:.7rem;padding:.6rem .75rem;border-radius:.7rem;font-size:.82rem;font-weight:500;color:var(--text);cursor:pointer;transition:background .15s;border:none;background:none;width:100%;text-align:left;font-family:var(--font-body);text-decoration:none}
    .pd-item:hover{background:var(--cream)}
    .pd-ic{width:30px;height:30px;border-radius:.45rem;display:flex;align-items:center;justify-content:center;font-size:.88rem;flex-shrink:0}
    .ib{background:#dbeafe}.ig{background:#dcfce7}.io{background:#fef3c7}.ip{background:#ede9fe}.ir{background:#fee2e2}
    .pd-sep{height:1px;background:rgba(10,84,137,.08);margin:.3rem .75rem}
    .pd-item.sair{color:#dc2626}.pd-item.sair:hover{background:#fef2f2}
    .pd-adm{margin:.4rem .6rem .2rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:.7rem;padding:.55rem .8rem;display:flex;align-items:center;gap:.55rem}
    .pd-adm .at span{font-size:.75rem;font-weight:600;color:#6d28d9;display:block}
    .pd-adm .at p{font-size:.64rem;color:var(--muted)}

    /* ── SEARCH OVERLAY ── */
    .search-overlay{display:none;position:fixed;inset:0;background:rgba(7,61,102,.6);backdrop-filter:blur(8px);z-index:200;align-items:flex-start;justify-content:center;padding-top:5rem}
    .search-overlay.ab{display:flex}
    .search-box{background:var(--white);border-radius:1.4rem;width:100%;max-width:600px;overflow:hidden;box-shadow:0 24px 80px rgba(7,61,102,.25);animation:sIn .28s var(--ease) both}
    @keyframes sIn{from{opacity:0;transform:translateY(-12px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
    .search-input-wrap{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.1)}
    .search-input-wrap input{flex:1;border:none;outline:none;font-family:var(--font-display);font-size:1.4rem;color:var(--text);background:none}
    .search-input-wrap input::placeholder{color:var(--muted)}
    .search-close{background:rgba(10,84,137,.08);border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:.85rem;color:var(--blue)}
    .search-results{max-height:380px;overflow-y:auto;padding:.6rem}
    .search-item{display:flex;align-items:center;gap:1rem;padding:.75rem .9rem;border-radius:.8rem;cursor:pointer;transition:background .18s;text-decoration:none;color:var(--text)}
    .search-item:hover{background:var(--cream)}
    .search-item-img{width:52px;height:52px;border-radius:.6rem;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0;overflow:hidden}
    .search-item-img img{width:100%;height:100%;object-fit:cover}
    .search-item-info h5{font-family:var(--font-display);font-size:1rem;font-weight:600;color:var(--text);margin-bottom:.15rem}
    .search-item-info span{font-size:.75rem;color:var(--muted)}
    .search-item-price{margin-left:auto;font-weight:700;color:var(--blue);font-size:.95rem;white-space:nowrap}
    .search-empty{text-align:center;padding:2.5rem;color:var(--muted);font-size:.9rem}
    .search-footer{padding:.6rem 1.2rem;border-top:1px solid rgba(10,84,137,.08);font-size:.74rem;color:var(--muted);text-align:center}

    /* ── PAGE HERO ── */
    .page-hero{padding:8rem 5% 4rem;background:linear-gradient(150deg,var(--cream) 0%,#e8f4ff 55%,var(--cream) 100%);position:relative;overflow:hidden}
    .page-hero::after{content:'';position:absolute;bottom:-1px;left:0;right:0;height:80px;background:var(--white);clip-path:ellipse(55% 100% at 50% 100%)}
    .page-hero-waves{position:absolute;inset:0;pointer-events:none}
    .page-hero-waves svg{position:absolute;bottom:0;width:110%;left:-5%}
    .hero-breadcrumb{display:flex;align-items:center;gap:.5rem;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:1.2rem}
    .hero-breadcrumb a{color:var(--blue);transition:color .2s}
    .hero-breadcrumb a:hover{color:var(--gold)}
    .hero-breadcrumb span{opacity:.4}
    .page-hero h1{font-family:var(--font-display);font-size:clamp(2.8rem,7vw,5.5rem);font-weight:600;color:var(--blue);line-height:1.0;letter-spacing:-.02em}
    .page-hero h1 em{font-style:italic;font-weight:300;color:var(--gold)}
    .page-hero p{margin-top:.9rem;font-size:1rem;color:var(--muted);font-weight:300;max-width:480px;line-height:1.75}
    .page-hero-deco{position:absolute;right:5%;top:50%;transform:translateY(-50%);font-family:var(--font-display);font-size:18rem;font-weight:700;color:rgba(10,84,137,.04);line-height:1;pointer-events:none;user-select:none}

    /* ── ADMIN BANNER ── */
    .admin-banner{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;padding:1rem 5%;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
    .admin-banner-text{display:flex;align-items:center;gap:.7rem;font-size:.85rem}
    .admin-banner-text strong{font-weight:700}
    .admin-banner a{background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);padding:.45rem 1.1rem;border-radius:2rem;font-size:.75rem;font-weight:600;transition:background .2s}
    .admin-banner a:hover{background:rgba(255,255,255,.35)}

    /* ── DESTAQUES ADMIN ── */
    .destaque-section{padding:4rem 5%;background:var(--white)}
    .destaque-inner{max-width:1300px;margin:0 auto}
    .section-label{font-size:.72rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:.6rem}
    .section-title{font-family:var(--font-display);font-size:clamp(2rem,4.5vw,3.2rem);font-weight:600;color:var(--blue);line-height:1.05}
    .section-title em{font-style:italic}
    .destaque-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}
    .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.8rem}
    .product-card{border-radius:1.2rem;overflow:hidden;background:var(--white);box-shadow:0 4px 24px rgba(10,84,137,.07);transition:transform .4s var(--ease),box-shadow .4s;cursor:pointer;position:relative}
    .product-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(10,84,137,.15)}
    .product-img{aspect-ratio:1;position:relative;overflow:hidden}
    .product-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;transition:transform .5s var(--ease);background:linear-gradient(135deg,var(--cream),var(--sand))}
    .product-card:hover .product-img-placeholder{transform:scale(1.08)}
    .product-badge{position:absolute;top:1rem;left:1rem;background:var(--blue);color:var(--cream);font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;padding:.25rem .7rem;border-radius:2rem}
    .product-badge.dest{background:#7c3aed}
    .product-badge.esg{background:#9ca3af}
    .product-wishlist{position:absolute;top:1rem;right:1rem;background:rgba(255,255,255,.85);backdrop-filter:blur(4px);width:2.2rem;height:2.2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;transition:background .25s,transform .25s;border:none}
    .product-wishlist:hover{background:#fff;transform:scale(1.15)}
    .product-info{padding:1.2rem 1.3rem 1.4rem}
    .product-info h4{font-family:var(--font-display);font-size:1.2rem;font-weight:600;color:var(--text);margin-bottom:.25rem}
    .product-sub{font-size:.78rem;color:var(--muted);margin-bottom:.8rem}
    .product-price{display:flex;align-items:center;gap:.7rem;justify-content:space-between}
    .price-main{font-size:1.1rem;font-weight:600;color:var(--blue)}
    .btn-add{background:var(--blue);color:var(--cream);border:none;cursor:pointer;padding:.5rem 1rem;border-radius:2rem;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;transition:background .25s;font-family:var(--font-body)}
    .btn-add:hover{background:var(--gold)}
    .admin-destaque-controls{display:flex;gap:.7rem;margin-top:.9rem;flex-wrap:wrap}
    .admin-destaque-controls button{padding:.45rem .9rem;border-radius:.5rem;border:1px solid var(--blue);background:var(--white);color:var(--blue);font-size:.72rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:var(--font-body)}
    .admin-destaque-controls button:hover{background:var(--blue);color:var(--cream)}
    .admin-destaque-controls button.destaque{background:#7c3aed;color:#fff;border-color:#7c3aed}
    .admin-destaque-controls .edit-btn{border-color:var(--gold);color:var(--gold)}
    .admin-destaque-controls .edit-btn:hover{background:var(--gold);color:#fff}
    .admin-destaque-controls .del-btn{border-color:#dc2626;color:#dc2626}
    .admin-destaque-controls .del-btn:hover{background:#dc2626;color:#fff}

    /* ── DIVIDER ── */
    .wave-divider{width:100%;overflow:hidden;line-height:0;background:var(--white)}
    .wave-divider svg{display:block;width:100%}

    /* ── CATÁLOGO PRINCIPAL ── */
    .catalog-section{padding:4rem 5% 6rem;background:var(--white)}
    .catalog-inner{max-width:1300px;margin:0 auto}
    .catalog-top{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}
    .catalog-top-right{display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
    .catalog-count{font-size:.8rem;color:var(--muted)}
    .catalog-filters{display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1.2rem}
    .filter-btn{padding:.5rem 1rem;border:1.5px solid rgba(10,84,137,.15);background:var(--white);border-radius:2rem;font-weight:500;color:var(--muted);cursor:pointer;transition:all .22s;font-family:var(--font-body);font-size:.78rem;letter-spacing:.04em}
    .filter-btn:hover{border-color:var(--blue);color:var(--blue)}
    .filter-btn.active{background:var(--blue);color:var(--cream);border-color:var(--blue)}
    .catalog-search{display:flex;align-items:center;gap:.5rem;border:1.5px solid rgba(10,84,137,.15);border-radius:2rem;padding:.5rem 1rem;background:var(--cream);transition:border-color .2s}
    .catalog-search:focus-within{border-color:var(--blue)}
    .catalog-search input{border:none;outline:none;background:none;font-family:var(--font-body);font-size:.85rem;color:var(--text);width:180px}
    .catalog-search input::placeholder{color:var(--muted)}
    .catalog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(265px,1fr));gap:1.8rem;margin-top:2rem}
    .pv{text-align:center;padding:5rem 2rem;color:var(--muted);grid-column:1/-1}
    .pv-icon{font-size:3.5rem;margin-bottom:1rem;opacity:.4}
    .pv h3{font-family:var(--font-display);font-size:2rem;color:var(--blue);opacity:.5;margin-bottom:.5rem}
    .pv p{font-size:.9rem;max-width:300px;margin:0 auto;line-height:1.65}

    /* ── TOAST ── */
    #toast{position:fixed;bottom:1.5rem;right:1.5rem;background:var(--blue);color:#fff;padding:.65rem 1.2rem;border-radius:2rem;font-size:.8rem;font-weight:500;z-index:999;opacity:0;transform:translateY(10px);transition:all .28s;pointer-events:none}

    /* ── FADE ── */
    .fade-in{opacity:0;transform:translateY(24px);transition:opacity .6s var(--ease),transform .6s var(--ease)}
    .fade-in.visible{opacity:1;transform:translateY(0)}
    .fade-in:nth-child(2){transition-delay:.07s}.fade-in:nth-child(3){transition-delay:.14s}.fade-in:nth-child(4){transition-delay:.21s}.fade-in:nth-child(5){transition-delay:.28s}.fade-in:nth-child(6){transition-delay:.35s}

    /* ── RESPONSIVE ── */
    @media(max-width:900px){.nav-links{display:none}.nav-nome{display:none}.page-hero-deco{display:none}}
    @media(max-width:600px){.page-hero{padding:7rem 5% 3.5rem}.catalog-section{padding:3rem 5% 4rem}}
  </style>
</head>
<body>

<!-- NAV -->
<nav id="mainNav">
  <a href="index.php"><img class="nav-logo-img" src="logo-removebg-preview.png" alt="Wave Acessórios"/></a>
  <div class="nav-links">
    <a href="index.php">Início</a>
    <a href="catalogo.php" class="ativo">Catálogo</a>
    <a href="index.php#contato">Contato</a>
    <?php if ($eh_admin): ?><a href="Dashboard.php">Dashboard</a><?php endif; ?>
  </div>
  <div class="nav-icons">
    <button onclick="openSearch()" title="Buscar (Ctrl+K)">🔍</button>
    <button>♡</button>
    <a class="nav-cart" href="carrinho.php">Sacola (0)</a>
    <?php if ($logado): ?>
      <div class="profile-wrap" id="pWrap">
        <div class="profile-trigger" id="pTrigger" onclick="toggleDrop()">
          <div class="nav-av <?= $eh_admin?'adm':'' ?>"><?= $iniciais?:'👤' ?></div>
          <span class="nav-nome"><?= $primeiro ?></span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="pd" id="pDrop">
          <div class="pd-head">
            <div class="pd-av <?= $eh_admin?'adm':'' ?>"><?= $iniciais?:'👤' ?></div>
            <div>
              <div class="pd-nome"><?= $nome_s ?></div>
              <div class="pd-email"><?= htmlspecialchars($udb['email']??'') ?></div>
              <div class="pd-badge"><?= $eh_admin?'⚙️ Administrador':'🛍️ Cliente' ?></div>
            </div>
          </div>
          <div class="pd-stats">
            <div class="pd-stat"><div class="pd-sv">0</div><div class="pd-sl">Pedidos</div></div>
            <div class="pd-stat"><div class="pd-sv">0</div><div class="pd-sl">Favoritos</div></div>
            <div class="pd-stat"><div class="pd-sv"><?= ($udb&&$udb['criado_em'])?(new DateTime($udb['criado_em']))->format('m/Y'):'—' ?></div><div class="pd-sl">Membro</div></div>
          </div>
          <div class="pd-menu">
            <button class="pd-item"><div class="pd-ic ib">👤</div>Minha conta</button>
            <button class="pd-item"><div class="pd-ic io">📦</div>Meus pedidos</button>
            <?php if ($eh_admin): ?>
              <div class="pd-sep"></div>
              <div class="pd-adm"><span style="font-size:1.1rem">⚙️</span><div class="at"><span>Painel Admin</span><p>Gerencie a loja</p></div></div>
              <a href="Dashboard.php" class="pd-item"><div class="pd-ic ip">🏠</div>Dashboard</a>
              <a href="cadastro_produto.php" class="pd-item"><div class="pd-ic ib">➕</div>Novo Produto</a>
            <?php endif; ?>
            <div class="pd-sep"></div>
            <a href="index.php?sair=1" class="pd-item sair"><div class="pd-ic ir">🚪</div>Sair da conta</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <a href="login.php" class="nav-login-btn">Entrar</a>
      <a href="cadastro.php"><button class="nav-login-btn">Cadastrar-se</button></a>
    <?php endif; ?>
  </div>
</nav>

<!-- ADMIN BANNER -->
<?php if ($eh_admin): ?>
<div class="admin-banner">
  <div class="admin-banner-text">
    <span>⚙️</span>
    <span><strong>Modo Admin</strong> — Você está gerenciando o catálogo. Os destaques só são visíveis para você.</span>
  </div>
  <a href="cadastro_produto.php">➕ Novo Produto</a>
</div>
<?php endif; ?>

<!-- SEARCH OVERLAY -->
<div class="search-overlay" id="searchOverlay" onclick="if(event.target===this)closeSearch()">
  <div class="search-box">
    <div class="search-input-wrap">
      <span style="font-size:1.1rem;opacity:.45">🔍</span>
      <input type="text" id="searchInput" placeholder="Buscar acessórios…" oninput="doSearch(this.value)" autocomplete="off"/>
      <button class="search-close" onclick="closeSearch()">✕</button>
    </div>
    <div class="search-results" id="searchResults">
      <div class="search-empty">Comece a digitar para buscar produtos…</div>
    </div>
    <div class="search-footer">Pressione <strong>Esc</strong> para fechar · <strong>Enter</strong> para ver todos</div>
  </div>
</div>

<!-- PAGE HERO -->
<div class="page-hero">
  <div class="page-hero-waves">
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" style="height:100px;bottom:0"><path fill="rgba(10,84,137,.06)" d="M0,60 C360,120 720,0 1080,60 C1260,90 1380,40 1440,60 L1440,120 L0,120Z"/></svg>
  </div>
  <div class="page-hero-deco">W</div>
  <div class="hero-breadcrumb">
    <a href="index.php">Início</a>
    <span>›</span>
    <span style="color:var(--gold)">Catálogo</span>
  </div>
  <h1>Nossa <em>coleção</em><br>completa</h1>
  <p>Explore cada peça feita com cuidado artesanal — colares, pulseiras, brincos e kits para todos os estilos.</p>
</div>

<!-- DESTAQUES (ADMIN ONLY) -->
<?php if ($eh_admin): ?>
<section class="destaque-section" id="destaques">
  <div class="destaque-inner">
    <div class="destaque-head">
      <div>
        <p class="section-label">Painel do Administrador</p>
        <h2 class="section-title">Produtos <em>Destacados</em></h2>
        <p style="font-size:.82rem;color:var(--muted);margin-top:.4rem">Gerencie quais produtos aparecem em destaque para seus clientes.</p>
      </div>
      <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
        <a href="Dashboard.php" style="display:inline-flex;align-items:center;gap:.5rem;background:#7c3aed;color:#fff;padding:.75rem 1.8rem;border-radius:2rem;font-size:.8rem;font-weight:600;transition:background .25s">🏠 Dashboard</a>
        <a href="cadastro_produto.php" style="display:inline-flex;align-items:center;gap:.5rem;background:var(--blue);color:var(--cream);padding:.75rem 1.8rem;border-radius:2rem;font-size:.8rem;font-weight:600;transition:background .25s">➕ Novo Produto</a>
      </div>
    </div>

    <?php if (!empty($produtos_destaque)): ?>
    <div class="products-grid">
      <?php foreach ($produtos_destaque as $p):
        $pimg = !empty($p['imagem']) ? 'uploads/produtos/'.htmlspecialchars($p['imagem']) : null;
        $icons=['Colares'=>'🐚','Pulseiras'=>'🌊','Brincos'=>'🌺','Anéis'=>'💍','Kits'=>'🎁'];
      ?>
      <div class="product-card fade-in">
        <a href="produto.php?id=<?= $p['id'] ?>" style="text-decoration:none;color:inherit;display:block">
          <div class="product-img">
            <?php if($pimg): ?>
              <img src="<?= $pimg ?>" alt="<?= htmlspecialchars($p['nome']) ?>" style="width:100%;height:100%;object-fit:cover;transition:transform .5s var(--ease)"/>
            <?php else: ?>
              <div class="product-img-placeholder"><?= $icons[$p['categoria']] ?? '💎' ?></div>
            <?php endif; ?>
            <?php if ($p['destaque']): ?><span class="product-badge dest">⭐ Destaque</span><?php endif; ?>
          </div>
        </a>
        <div class="product-info">
          <h4><?= htmlspecialchars($p['nome']) ?></h4>
          <p class="product-sub"><?= htmlspecialchars($p['categoria']) ?> · Estoque: <?= (int)$p['estoque'] ?></p>
          <div class="product-price">
            <span class="price-main">R$ <?= number_format((float)$p['preco'],2,',','.') ?></span>
          </div>
          <div class="admin-destaque-controls">
            <button class="<?= $p['destaque']?'destaque':'' ?>" onclick="toggleDestaque(<?= $p['id'] ?>, this)">
              <?= $p['destaque']?'⭐ Remover':'✨ Destacar' ?>
            </button>
            <a href="cadastro_produto.php?id=<?= $p['id'] ?>" class="edit-btn" style="padding:.45rem .9rem;border-radius:.5rem;border:1px solid var(--gold);color:var(--gold);font-size:.72rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:var(--font-body);display:inline-flex;align-items:center;gap:.3rem">✏️ Editar</a>
            <a href="produto.php?id=<?= $p['id'] ?>" style="padding:.45rem .9rem;border-radius:.5rem;border:1px solid var(--blue);color:var(--blue);font-size:.72rem;font-weight:600;font-family:var(--font-body);display:inline-flex;align-items:center;gap:.3rem">👁 Ver</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="pv">
      <div class="pv-icon">🌊</div>
      <h3>Nenhum produto cadastrado</h3>
      <p>Clique em "Novo Produto" para começar seu catálogo.</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Divisor admin/catálogo -->
<div style="height:2px;background:linear-gradient(90deg,transparent,rgba(124,58,237,.2),transparent);margin:0 5%"></div>
<?php endif; ?>

<!-- CATÁLOGO COMPLETO -->
<section class="catalog-section" id="catalogo">
  <div class="catalog-inner">
    <div class="catalog-top">
      <div>
        <p class="section-label">Todos os produtos</p>
        <h2 class="section-title">Explore <em>tudo</em></h2>
      </div>
      <div class="catalog-top-right">
        <div class="catalog-search">
          <span style="font-size:.9rem;opacity:.5">🔍</span>
          <input type="text" id="catalogSearchInput" placeholder="Buscar produto..." oninput="renderCatalogo()"/>
        </div>
        <span class="catalog-count" id="catalogCount"></span>
      </div>
    </div>

    <div class="catalog-filters" id="categoryFilters">
      <button class="filter-btn active" onclick="filterCatalog(event,'')">Todos</button>
      <button class="filter-btn" onclick="filterCatalog(event,'Colares')">🐚 Colares</button>
      <button class="filter-btn" onclick="filterCatalog(event,'Pulseiras')">🌊 Pulseiras</button>
      <button class="filter-btn" onclick="filterCatalog(event,'Brincos')">🌺 Brincos</button>
      <button class="filter-btn" onclick="filterCatalog(event,'Anéis')">💍 Anéis</button>
      <button class="filter-btn" onclick="filterCatalog(event,'Kits')">🎁 Kits Presente</button>
    </div>

    <div class="catalog-grid" id="catalogGrid"></div>
  </div>
</section>

<div id="toast"></div>

<script>
  const produtosData = <?php echo json_encode($produtos); ?>;
  let filtroAtual = '';

  // Nav scroll
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 60);
  });

  // Fade-in observer
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
  }, {threshold:0.1});
  document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));

  // Dropdown perfil
  function toggleDrop() {
    document.getElementById('pDrop')?.classList.toggle('ab');
    document.getElementById('pTrigger')?.classList.toggle('ab');
  }
  document.addEventListener('click', e => {
    const w = document.getElementById('pWrap');
    if (w && !w.contains(e.target)) {
      document.getElementById('pDrop')?.classList.remove('ab');
      document.getElementById('pTrigger')?.classList.remove('ab');
    }
  });

  // Toast
  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.opacity = '1';
    t.style.transform = 'translateY(0)';
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(10px)'; }, 2800);
  }

  // ── Search overlay ──
  function openSearch(){document.getElementById('searchOverlay').classList.add('ab');setTimeout(()=>document.getElementById('searchInput')?.focus(),80);}
  function closeSearch(){document.getElementById('searchOverlay').classList.remove('ab');}
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){closeSearch();}
    if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();openSearch();}
  });
  const catIcons={Colares:'🐚',Pulseiras:'🌊',Brincos:'🌺','Anéis':'💍',Kits:'🎁'};
  function doSearch(q){
    const res=document.getElementById('searchResults');
    if(!q.trim()){res.innerHTML='<div class="search-empty">Comece a digitar para buscar produtos…</div>';return;}
    const found=produtosData.filter(p=>p.nome.toLowerCase().includes(q.toLowerCase())||p.categoria.toLowerCase().includes(q.toLowerCase())).slice(0,7);
    if(!found.length){res.innerHTML=`<div class="search-empty">Nenhum produto encontrado para "<strong>${q}</strong>"</div>`;return;}
    res.innerHTML=found.map(p=>`<a class="search-item" href="produto.php?id=${p.id}">
      <div class="search-item-img">${p.imagem?`<img src="uploads/produtos/${p.imagem}" alt=""/>`:catIcons[p.categoria]||'💎'}</div>
      <div class="search-item-info"><h5>${p.nome}</h5><span>${p.categoria}</span></div>
      <span class="search-item-price">R$ ${parseFloat(p.preco).toFixed(2).replace('.',',')}</span>
    </a>`).join('');
  }

  // ── Catálogo grid ──
  function renderCatalogo() {
    const grid = document.getElementById('catalogGrid');
    const q = (document.getElementById('catalogSearchInput')?.value || '').toLowerCase();
    let filtrados = produtosData.filter(p => {
      const matchCat   = !filtroAtual || p.categoria === filtroAtual;
      const matchBusca = !q || p.nome.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q);
      return matchCat && matchBusca;
    });

    document.getElementById('catalogCount').textContent = filtrados.length + ' produto' + (filtrados.length !== 1 ? 's' : '');

    if (filtrados.length === 0) {
      grid.innerHTML = `<div class="pv"><div class="pv-icon">📭</div><h3>Nenhum produto encontrado</h3><p>Tente outro filtro ou busca.</p></div>`;
      return;
    }

    grid.innerHTML = filtrados.map((p, i) => `
      <div class="product-card fade-in visible" style="animation-delay:${i*0.05}s">
        <a href="produto.php?id=${p.id}" style="display:block;text-decoration:none;color:inherit">
          <div class="product-img">
            ${p.imagem
              ? `<img src="uploads/produtos/${p.imagem}" alt="${p.nome}" style="width:100%;height:100%;object-fit:cover;transition:transform .5s var(--ease)"/>`
              : `<div class="product-img-placeholder">${catIcons[p.categoria] || '💎'}</div>`
            }
            ${p.estoque<1?'<span class="product-badge esg">Esgotado</span>':''}
          </div>
        </a>
        <div class="product-info">
          <a href="produto.php?id=${p.id}" style="text-decoration:none;color:inherit"><h4>${p.nome}</h4></a>
          <p class="product-sub">${p.categoria}</p>
          <div class="product-price">
            <span class="price-main">R$ ${parseFloat(p.preco).toFixed(2).replace('.',',')}</span>
            <button class="btn-add" onclick="addSacola('${p.nome.replace(/'/g,'\\\'')}')" ${p.estoque<1?'disabled':''}>
              ${p.estoque<1?'Esgotado':'+ Sacola'}
            </button>
          </div>
        </div>
      </div>
    `).join('');
  }

  function filterCatalog(event, cat) {
    filtroAtual = cat;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    renderCatalogo();
  }

  function addSacola(nome) { showToast('✓ ' + nome + ' adicionado à sacola!'); }

  // Admin: toggle destaque
  function toggleDestaque(produtoId, btn) {
    const fd = new FormData();
    fd.append('acao', 'toggle_destaque');
    fd.append('id', produtoId);
    fetch('api_produtos.php', {method:'POST',body:fd})
      .then(r => r.json())
      .then(d => { if(d.sucesso) location.reload(); else showToast(d.erro || 'Erro ❌'); })
      .catch(e => showToast('Erro: ' + e.message));
  }

  // Init
  renderCatalogo();

  // Auto-select category from URL param
  const urlCat = new URLSearchParams(window.location.search).get('categoria');
  if (urlCat) {
    filtroAtual = urlCat;
    document.querySelectorAll('.filter-btn').forEach(b => {
      b.classList.toggle('active', b.textContent.trim().includes(urlCat));
    });
    renderCatalogo();
  }
</script>
</body>
</html>