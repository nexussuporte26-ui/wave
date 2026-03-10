<?php
session_start();
require_once 'conexão.php';

if (isset($_GET['sair'])) { session_destroy(); header("Location: index.php"); exit(); }

$logado   = isset($_SESSION['usuario_id']);
$nivel    = $logado ? ($_SESSION['nivel'] ?? 'usuario') : '';
$nome_s   = $logado ? htmlspecialchars($_SESSION['nome'] ?? '') : '';
$primeiro = $nome_s ? strtok($nome_s, ' ') : '';
$iniciais = '';
if ($logado && $nome_s) {
    $p = explode(' ', $nome_s);
    $iniciais = strtoupper(substr($p[0],0,1) . (isset($p[1]) ? substr($p[1],0,1) : ''));
}
$udb = null;
if ($logado) {
    $uid = (int)$_SESSION['usuario_id'];
    $r   = $conn->query("SELECT nome,email FROM usuarios WHERE id=$uid LIMIT 1");
    if ($r && $r->num_rows > 0) $udb = $r->fetch_assoc();
}
$eh_admin = $nivel === 'admin';

// Todos os produtos para busca global
$todos = [];
$rall  = $conn->query("SELECT id,nome,preco,categoria,imagem FROM produtos WHERE ativo=1 ORDER BY id DESC");
if ($rall) while ($row = $rall->fetch_assoc()) $todos[] = $row;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Sacola — Wave Acessórios</title>
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
    body{font-family:var(--font-body);background:var(--cream);color:var(--text);min-height:100vh}
    a{text-decoration:none;color:inherit}
    img{display:block;max-width:100%}

    /* ── NAV ── */
    nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:.5rem 5%;background:rgba(255,243,231,.97);backdrop-filter:blur(12px);box-shadow:0 2px 24px rgba(10,84,137,.10);border-bottom:1.5px solid rgba(10,84,137,.08)}
    .nav-logo-img{height:58px;width:auto}
    .nav-links{display:flex;gap:2rem;font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;font-weight:500}
    .nav-links a{color:var(--blue);transition:color .25s;position:relative;padding-bottom:3px}
    .nav-links a::after{content:'';position:absolute;bottom:0;left:0;width:0;height:1.5px;background:var(--gold);transition:width .3s var(--ease)}
    .nav-links a:hover{color:var(--gold)}.nav-links a:hover::after{width:100%}
    .nav-icons{display:flex;gap:.8rem;align-items:center}
    .nav-sb{background:none;border:none;cursor:pointer;color:var(--blue);font-size:1.1rem;transition:color .25s;padding:.3rem}
    .nav-sb:hover{color:var(--gold)}
    .nav-cart{position:relative;background:var(--blue);color:var(--cream)!important;padding:.42rem 1rem;border-radius:2rem;font-size:.74rem;letter-spacing:.1em;text-transform:uppercase;font-weight:500;transition:background .25s!important;display:inline-flex;align-items:center;gap:.4rem}
    .nav-cart:hover{background:var(--gold)!important}
    .nav-cart.ativo{background:var(--gold)!important}
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
    .s-overlay{display:none;position:fixed;inset:0;background:rgba(7,61,102,.6);backdrop-filter:blur(8px);z-index:200;align-items:flex-start;justify-content:center;padding-top:5rem}
    .s-overlay.ab{display:flex}
    .s-box{background:var(--white);border-radius:1.4rem;width:100%;max-width:600px;overflow:hidden;box-shadow:0 24px 80px rgba(7,61,102,.25);animation:sIn .28s var(--ease)}
    @keyframes sIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
    .s-iw{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.1)}
    .s-iw input{flex:1;border:none;outline:none;font-family:var(--font-display);font-size:1.35rem;color:var(--text);background:none}
    .s-iw input::placeholder{color:var(--muted)}
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
    .page-header{background:linear-gradient(135deg,var(--blue-dk) 0%,var(--blue) 60%,var(--blue-lt) 100%);padding:6.5rem 5% 3rem;position:relative;overflow:hidden}
    .page-header::after{content:'';position:absolute;bottom:-1px;left:0;right:0;height:60px;background:var(--cream);clip-path:ellipse(55% 100% at 50% 100%)}
    .ph-deco{position:absolute;right:4%;top:50%;transform:translateY(-50%);font-family:var(--font-display);font-size:16rem;font-weight:700;color:rgba(255,255,255,.04);line-height:1;pointer-events:none;user-select:none}
    .breadcrumb{display:flex;align-items:center;gap:.45rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,243,231,.5);margin-bottom:1rem;flex-wrap:wrap}
    .breadcrumb a{color:rgba(255,243,231,.65);transition:color .2s}.breadcrumb a:hover{color:var(--gold)}
    .breadcrumb .sep{opacity:.4}
    .page-header h1{font-family:var(--font-display);font-size:clamp(2.2rem,5vw,3.8rem);font-weight:600;color:#fff;line-height:1.05;letter-spacing:-.01em}
    .page-header h1 em{font-style:italic;font-weight:300;color:rgba(200,150,62,.85)}
    .page-header p{margin-top:.6rem;font-size:.9rem;color:rgba(255,243,231,.6);font-weight:300}

    /* ── LAYOUT ── */
    .cart-layout{display:grid;grid-template-columns:1fr 360px;gap:2rem;padding:2.5rem 5% 5rem;max-width:1300px;margin:0 auto;align-items:start}

    /* ── CART ITEMS ── */
    .cart-card{background:var(--white);border-radius:1.3rem;box-shadow:0 4px 22px rgba(10,84,137,.07);overflow:hidden;margin-bottom:1.2rem}
    .cart-card-head{padding:1.1rem 1.5rem;border-bottom:1px solid rgba(10,84,137,.08);display:flex;align-items:center;justify-content:space-between;gap:1rem}
    .cart-card-head h3{font-family:var(--font-display);font-size:1.3rem;color:var(--blue);font-weight:600;display:flex;align-items:center;gap:.5rem}
    .item-count{background:var(--blue);color:#fff;border-radius:2rem;padding:.15rem .55rem;font-size:.7rem;font-weight:700}
    .clear-all{background:none;border:none;color:var(--muted);font-size:.75rem;cursor:pointer;font-family:var(--font-body);transition:color .2s;display:flex;align-items:center;gap:.3rem}
    .clear-all:hover{color:#dc2626}

    /* Cart item row */
    .cart-item{display:flex;align-items:center;gap:1.2rem;padding:1.2rem 1.5rem;border-bottom:1px solid rgba(10,84,137,.06);transition:background .2s;animation:fadeIn .3s var(--ease)}
    @keyframes fadeIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
    .cart-item:last-child{border-bottom:none}
    .cart-item:hover{background:rgba(255,243,231,.4)}
    .ci-img{width:76px;height:76px;border-radius:.8rem;overflow:hidden;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;position:relative}
    .ci-img img{width:100%;height:100%;object-fit:cover}
    .ci-info{flex:1;min-width:0}
    .ci-cat{font-size:.65rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.15rem}
    .ci-nome{font-family:var(--font-display);font-size:1.05rem;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:.1rem}
    .ci-nome a{color:inherit;transition:color .2s}.ci-nome a:hover{color:var(--blue)}
    .ci-preco-unit{font-size:.75rem;color:var(--muted)}
    .ci-right{display:flex;align-items:center;gap:1rem;flex-shrink:0}
    .qty-ctrl{display:flex;align-items:center;gap:.35rem;background:var(--cream);border-radius:2rem;padding:.26rem .5rem;border:1.5px solid rgba(10,84,137,.14)}
    .qty-btn{background:none;border:none;width:24px;height:24px;border-radius:50%;cursor:pointer;font-size:.95rem;color:var(--blue);display:flex;align-items:center;justify-content:center;transition:background .18s;font-weight:700}
    .qty-btn:hover{background:var(--blue);color:#fff}
    .qty-val{font-size:.88rem;font-weight:700;min-width:22px;text-align:center}
    .ci-total{font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--blue);min-width:72px;text-align:right}
    .ci-del{background:none;border:none;color:#d1d5db;cursor:pointer;font-size:1.1rem;transition:color .2s;padding:.2rem;flex-shrink:0}
    .ci-del:hover{color:#dc2626}

    /* Empty state */
    .cart-empty{padding:4rem 2rem;text-align:center}
    .cart-empty-icon{font-size:4rem;opacity:.22;margin-bottom:1rem}
    .cart-empty h3{font-family:var(--font-display);font-size:1.7rem;color:var(--blue);margin-bottom:.5rem}
    .cart-empty p{font-size:.88rem;color:var(--muted);margin-bottom:1.5rem}
    .btn-go-catalog{display:inline-flex;align-items:center;gap:.5rem;background:var(--blue);color:var(--cream);padding:.75rem 2rem;border-radius:2rem;font-size:.82rem;font-weight:600;transition:background .25s}
    .btn-go-catalog:hover{background:var(--gold)}

    /* Free shipping progress */
    .shipping-bar-wrap{background:var(--white);border-radius:1.2rem;box-shadow:0 4px 22px rgba(10,84,137,.07);padding:1.2rem 1.5rem;margin-bottom:1.2rem}
    .sb-label{font-size:.78rem;color:var(--muted);margin-bottom:.55rem;display:flex;align-items:center;justify-content:space-between}
    .sb-label strong{color:var(--text);font-weight:600}
    .sb-track{height:8px;background:rgba(10,84,137,.1);border-radius:4px;overflow:hidden}
    .sb-fill{height:100%;background:linear-gradient(90deg,var(--blue),var(--blue-lt));border-radius:4px;transition:width .6s var(--ease)}
    .sb-msg{font-size:.75rem;margin-top:.45rem;color:var(--muted);text-align:center}
    .sb-msg.ok{color:#16a34a;font-weight:600}

    /* ── ORDER SUMMARY ── */
    .summary-card{background:var(--white);border-radius:1.3rem;box-shadow:0 4px 22px rgba(10,84,137,.07);overflow:hidden;position:sticky;top:6rem}
    .summary-head{padding:1.1rem 1.5rem;border-bottom:1px solid rgba(10,84,137,.08)}
    .summary-head h3{font-family:var(--font-display);font-size:1.3rem;color:var(--blue);font-weight:600}
    .summary-body{padding:1.3rem 1.5rem;display:flex;flex-direction:column;gap:.75rem}
    .sum-row{display:flex;align-items:center;justify-content:space-between;font-size:.85rem}
    .sum-row .lbl{color:var(--muted)}
    .sum-row .val{font-weight:600;color:var(--text)}
    .sum-row .val.green{color:#16a34a}
    .sum-row .val.big{font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--blue)}
    .sum-divider{height:1px;background:rgba(10,84,137,.08)}
    .coupon-wrap{display:flex;gap:.6rem;margin-top:.3rem}
    .coupon-input{flex:1;border:1.5px solid rgba(10,84,137,.15);border-radius:.65rem;padding:.58rem .85rem;font-family:var(--font-body);font-size:.82rem;outline:none;background:var(--cream);transition:border-color .2s}
    .coupon-input:focus{border-color:var(--blue);background:var(--white)}
    .coupon-btn{background:var(--blue);color:#fff;border:none;border-radius:.65rem;padding:.58rem 1rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--font-body);transition:background .2s;white-space:nowrap}
    .coupon-btn:hover{background:var(--gold)}
    .coupon-msg{font-size:.72rem;margin-top:.3rem;height:.95rem}
    .coupon-msg.ok{color:#16a34a}.coupon-msg.er{color:#dc2626}
    .btn-checkout{width:100%;background:var(--blue);color:var(--cream);border:none;padding:1rem;border-radius:2rem;font-size:.9rem;font-weight:700;cursor:pointer;font-family:var(--font-body);transition:background .25s,transform .2s;letter-spacing:.06em;margin-top:.4rem;display:flex;align-items:center;justify-content:center;gap:.5rem}
    .btn-checkout:hover{background:var(--gold);transform:translateY(-2px)}
    .btn-checkout:disabled{background:#d1d5db;cursor:not-allowed;transform:none}
    .security-note{display:flex;align-items:center;justify-content:center;gap:.35rem;font-size:.7rem;color:var(--muted);margin-top:.6rem}
    .payment-icons{display:flex;gap:.5rem;justify-content:center;margin-top:.8rem;flex-wrap:wrap}
    .pay-ic{background:var(--cream);border:1px solid rgba(10,84,137,.1);border-radius:.4rem;padding:.25rem .5rem;font-size:.65rem;color:var(--muted);font-weight:600}

    /* ── SUGGESTED PRODUCTS ── */
    .suggest-section{padding:0 5% 5rem;max-width:1300px;margin:0 auto}
    .suggest-section h3{font-family:var(--font-display);font-size:1.85rem;color:var(--blue);margin-bottom:1.3rem}
    .suggest-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(195px,1fr));gap:1.2rem}
    .sug-card{background:var(--white);border-radius:1rem;overflow:hidden;box-shadow:0 4px 16px rgba(10,84,137,.07);transition:transform .35s var(--ease),box-shadow .35s;display:block;color:inherit}
    .sug-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(10,84,137,.13)}
    .sug-img{aspect-ratio:1;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:2.8rem;overflow:hidden}
    .sug-img img{width:100%;height:100%;object-fit:cover}
    .sug-info{padding:.85rem .95rem 1rem}
    .sug-cat{font-size:.65rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.1rem}
    .sug-nome{font-family:var(--font-display);font-size:.98rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:.3rem}
    .sug-price{font-size:.92rem;font-weight:700;color:var(--blue)}
    .sug-add{width:100%;background:none;border:1.5px solid rgba(10,84,137,.2);border-radius:2rem;padding:.4rem;font-size:.72rem;font-weight:600;color:var(--blue);cursor:pointer;font-family:var(--font-body);transition:all .22s;margin-top:.5rem}
    .sug-add:hover{background:var(--blue);color:#fff;border-color:var(--blue)}

    /* ── TOAST ── */
    #toast{position:fixed;bottom:1.5rem;right:1.5rem;background:var(--blue);color:#fff;padding:.6rem 1.1rem;border-radius:2rem;font-size:.78rem;font-weight:500;z-index:999;opacity:0;transform:translateY(10px);transition:all .28s;pointer-events:none;max-width:280px}

    @media(max-width:1000px){.cart-layout{grid-template-columns:1fr}.summary-card{position:static}}
    @media(max-width:640px){.nav-links{display:none}.nav-nome{display:none}.ci-right{gap:.6rem}.ci-img{width:60px;height:60px}}
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
    <button class="nav-sb" onclick="openSearch()">🔍</button>
    <a class="nav-cart ativo" href="carrinho.php">
      🛍 Sacola
      <span class="cart-badge" id="cartBadge">0</span>
    </a>
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
          <a href="catalogo.php" class="pd-item"><div class="pd-ic ib">📦</div>Catálogo</a>
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
  <div class="ph-deco">🛍</div>
  <div class="breadcrumb">
    <a href="index.php">Início</a><span class="sep">›</span>
    <span style="color:rgba(255,243,231,.85)">Sacola</span>
  </div>
  <h1>Minha <em>sacola</em></h1>
  <p>Revise seus itens antes de finalizar o pedido</p>
</div>

<!-- MAIN LAYOUT -->
<div class="cart-layout" id="cartLayout">

  <!-- COLUNA ITENS -->
  <div>
    <!-- Frete grátis progress bar -->
    <div class="shipping-bar-wrap">
      <div class="sb-label">
        <span>Frete grátis a partir de <strong>R$ 99,00</strong></span>
        <span id="sbVal">R$ 0,00</span>
      </div>
      <div class="sb-track"><div class="sb-fill" id="sbFill" style="width:0%"></div></div>
      <div class="sb-msg" id="sbMsg">Adicione produtos para ganhar frete grátis!</div>
    </div>

    <!-- Itens da sacola -->
    <div class="cart-card">
      <div class="cart-card-head">
        <h3>Itens <span class="item-count" id="itemCount">0</span></h3>
        <button class="clear-all" onclick="clearCart()">🗑 Limpar sacola</button>
      </div>
      <div id="cartItems">
        <!-- preenchido por JS -->
      </div>
    </div>

    <!-- Continuar comprando -->
    <a href="catalogo.php" style="display:inline-flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--blue);font-weight:600;transition:color .2s;margin-top:.5rem">
      ← Continuar comprando
    </a>
  </div>

  <!-- COLUNA RESUMO -->
  <div>
    <div class="summary-card">
      <div class="summary-head"><h3>Resumo do pedido</h3></div>
      <div class="summary-body">

        <div class="sum-row">
          <span class="lbl">Subtotal</span>
          <span class="val" id="sumSubtotal">R$ 0,00</span>
        </div>
        <div class="sum-row">
          <span class="lbl">Desconto PIX (5%)</span>
          <span class="val green" id="sumPix">– R$ 0,00</span>
        </div>
        <div class="sum-row">
          <span class="lbl">Frete</span>
          <span class="val green" id="sumFrete">Calculando…</span>
        </div>
        <div class="sum-row" id="cupomRow" style="display:none">
          <span class="lbl">Cupom</span>
          <span class="val green" id="sumCupom">– R$ 0,00</span>
        </div>

        <div class="sum-divider"></div>

        <!-- Cupom -->
        <div>
          <div style="font-size:.72rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.4rem">Cupom de desconto</div>
          <div class="coupon-wrap">
            <input class="coupon-input" type="text" id="cupomInput" placeholder="WAVE10" maxlength="20"/>
            <button class="coupon-btn" onclick="aplicarCupom()">Aplicar</button>
          </div>
          <div class="coupon-msg" id="cupomMsg"></div>
        </div>

        <div class="sum-divider"></div>

        <div class="sum-row">
          <span class="lbl" style="font-weight:700;font-size:.9rem">Total</span>
          <span class="val big" id="sumTotal">R$ 0,00</span>
        </div>

        <button class="btn-checkout" id="btnCheckout" onclick="finalizarPedido()" disabled>
          🔒 Finalizar pedido
        </button>

        <div class="security-note">🔒 Pagamento 100% seguro e criptografado</div>

        <div class="payment-icons">
          <span class="pay-ic">Visa</span>
          <span class="pay-ic">Master</span>
          <span class="pay-ic">PIX</span>
          <span class="pay-ic">Boleto</span>
          <span class="pay-ic">Elo</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- SUGESTÕES -->
<div class="suggest-section">
  <h3>Você também pode gostar</h3>
  <div class="suggest-grid" id="suggestGrid">
    <!-- preenchido por JS -->
  </div>
</div>

<div id="toast"></div>

<script>
  const catIcons = {Colares:'🐚',Pulseiras:'🌊',Brincos:'🌺','Anéis':'💍',Kits:'🎁'};
  const FREE_SHIPPING = 99;
  const CUPONS = { 'WAVE10': 10, 'WAVE20': 20, 'PRAIA15': 15 }; // % de desconto

  // Dados de produtos do PHP
  const produtosAll = <?= json_encode($todos) ?>;

  let cupomAtual = null;
  let cupomDesc  = 0;

  // ── Cart helpers ──
  function getCart(){ try{ return JSON.parse(localStorage.getItem('wave_cart')||'[]'); }catch(e){ return []; } }
  function saveCart(c){ try{ localStorage.setItem('wave_cart', JSON.stringify(c)); }catch(e){} }

  function fmt(v){ return 'R$ ' + parseFloat(v).toFixed(2).replace('.', ','); }

  // ── Render cart ──
  function renderCart(){
    const cart   = getCart();
    const items  = document.getElementById('cartItems');
    const count  = document.getElementById('itemCount');
    const badge  = document.getElementById('cartBadge');
    const btnCO  = document.getElementById('btnCheckout');
    const total  = cart.reduce((s,i) => s+i.qty, 0);

    if (count) count.textContent = total;
    if (badge){ badge.textContent = total; badge.classList.toggle('show', total > 0); }

    if (!items) return;

    if (cart.length === 0) {
      items.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">🛍</div>
          <h3>Sua sacola está vazia</h3>
          <p>Explore nosso catálogo e adicione peças que você vai amar.</p>
          <a href="catalogo.php" class="btn-go-catalog">Ver catálogo →</a>
        </div>`;
      if(btnCO) btnCO.disabled = true;
      updateSummary([]);
      return;
    }

    if(btnCO) btnCO.disabled = false;

    items.innerHTML = cart.map((item, idx) => {
      const img   = item.imagem
        ? `<img src="uploads/produtos/${item.imagem}" alt="${item.nome}"/>`
        : (catIcons[item.categoria] || '💎');
      const total = (item.preco * item.qty).toFixed(2).replace('.', ',');
      return `
        <div class="cart-item" id="ci-${idx}">
          <div class="ci-img">${img}</div>
          <div class="ci-info">
            <div class="ci-cat">${item.categoria || ''}</div>
            <div class="ci-nome"><a href="produto.php?id=${item.id}">${item.nome}</a></div>
            <div class="ci-preco-unit">${fmt(item.preco)} cada</div>
          </div>
          <div class="ci-right">
            <div class="qty-ctrl">
              <button class="qty-btn" onclick="changeQty(${idx}, -1)">−</button>
              <span class="qty-val">${item.qty}</span>
              <button class="qty-btn" onclick="changeQty(${idx}, 1)">+</button>
            </div>
            <div class="ci-total">R$ ${total}</div>
            <button class="ci-del" onclick="removeItem(${idx})" title="Remover">✕</button>
          </div>
        </div>`;
    }).join('');

    updateSummary(cart);
    renderSuggestions(cart);
  }

  function updateSummary(cart){
    const subtotal = cart.reduce((s,i) => s + i.preco * i.qty, 0);
    const pix      = subtotal * 0.05;
    const frete    = subtotal >= FREE_SHIPPING || subtotal === 0 ? 0 : 9.90;
    const cupDesc  = cupomAtual ? subtotal * (cupomDesc / 100) : 0;
    const total    = Math.max(0, subtotal - pix - cupDesc + frete);

    document.getElementById('sumSubtotal').textContent = fmt(subtotal);
    document.getElementById('sumPix').textContent      = '– ' + fmt(pix);
    document.getElementById('sumFrete').textContent    = frete === 0 ? (subtotal > 0 ? '🎉 Grátis' : 'Calculando…') : fmt(frete);
    document.getElementById('sumFrete').className      = 'val' + (frete === 0 && subtotal > 0 ? ' green' : '');
    document.getElementById('sumTotal').textContent    = fmt(total);

    // Cupom
    const cr = document.getElementById('cupomRow');
    const cs = document.getElementById('sumCupom');
    if (cupomAtual && cr && cs){ cr.style.display='flex'; cs.textContent='– '+fmt(cupDesc); }
    else if(cr) cr.style.display='none';

    // Shipping bar
    const pct = Math.min(100, (subtotal / FREE_SHIPPING) * 100);
    const fill = document.getElementById('sbFill');
    const msg  = document.getElementById('sbMsg');
    const val  = document.getElementById('sbVal');
    if(fill) fill.style.width = pct + '%';
    if(val)  val.textContent = fmt(subtotal);
    if(msg){
      if(subtotal === 0) msg.textContent = 'Adicione produtos para ganhar frete grátis!';
      else if(subtotal >= FREE_SHIPPING){ msg.textContent = '🎉 Você ganhou frete grátis!'; msg.className='sb-msg ok'; }
      else{ const falta = FREE_SHIPPING - subtotal; msg.textContent = 'Falta '+fmt(falta)+' para frete grátis!'; msg.className='sb-msg'; }
    }
  }

  function changeQty(idx, d){
    const cart = getCart();
    if (!cart[idx]) return;
    cart[idx].qty = Math.max(1, cart[idx].qty + d);
    saveCart(cart);
    renderCart();
  }

  function removeItem(idx){
    const cart = getCart();
    cart.splice(idx, 1);
    saveCart(cart);
    renderCart();
    showToast('Item removido da sacola.');
  }

  function clearCart(){
    if (!confirm('Limpar toda a sacola?')) return;
    saveCart([]);
    renderCart();
    showToast('Sacola esvaziada.');
  }

  // ── Cupom ──
  function aplicarCupom(){
    const code  = (document.getElementById('cupomInput')?.value || '').trim().toUpperCase();
    const msg   = document.getElementById('cupomMsg');
    if (!code){ if(msg){ msg.textContent='Digite um cupom.'; msg.className='coupon-msg er'; } return; }
    if (CUPONS[code] !== undefined){
      cupomAtual = code;
      cupomDesc  = CUPONS[code];
      if(msg){ msg.textContent='✅ Cupom aplicado: '+cupomDesc+'% de desconto!'; msg.className='coupon-msg ok'; }
      renderCart();
    } else {
      cupomAtual = null; cupomDesc = 0;
      if(msg){ msg.textContent='❌ Cupom inválido ou expirado.'; msg.className='coupon-msg er'; }
      renderCart();
    }
  }

  // ── Sugestões ──
  function renderSuggestions(cart){
    const grid = document.getElementById('suggestGrid');
    if (!grid) return;
    const cartIds = cart.map(i => i.id);
    const sugs = produtosAll.filter(p => !cartIds.includes(p.id)).slice(0, 4);
    if (!sugs.length){ grid.parentElement.style.display='none'; return; }
    grid.innerHTML = sugs.map(p => {
      const img = p.imagem
        ? `<img src="uploads/produtos/${p.imagem}" alt="${p.nome}"/>`
        : (catIcons[p.categoria] || '💎');
      return `
        <a class="sug-card" href="produto.php?id=${p.id}">
          <div class="sug-img">${img}</div>
          <div class="sug-info">
            <div class="sug-cat">${p.categoria}</div>
            <div class="sug-nome">${p.nome}</div>
            <div class="sug-price">${fmt(p.preco)}</div>
            <button class="sug-add" onclick="event.preventDefault();addFromSug(${p.id})">+ Adicionar à sacola</button>
          </div>
        </a>`;
    }).join('');
  }

  function addFromSug(id){
    const p = produtosAll.find(x => x.id == id);
    if (!p) return;
    const cart = getCart();
    const ix = cart.findIndex(i => i.id == id);
    if (ix >= 0) cart[ix].qty++;
    else cart.push({ id:p.id, nome:p.nome, preco:parseFloat(p.preco), imagem:p.imagem||'', categoria:p.categoria, qty:1 });
    saveCart(cart);
    renderCart();
    showToast('✓ '+p.nome+' adicionado!');
  }

  // ── Finalizar ──
  function finalizarPedido(){
    <?php if(!$logado):?>
    showToast('⚠ Faça login para finalizar o pedido!');
    setTimeout(()=>window.location.href='login.php?redirect=carrinho.php', 1800);
    <?php else:?>
    showToast('🎉 Pedido enviado! Em breve entraremos em contato.');
    setTimeout(()=>{ saveCart([]); window.location.href='index.php'; }, 2200);
    <?php endif;?>
  }

  // ── Search overlay ──
  function openSearch(){ document.getElementById('sOverlay').classList.add('ab'); setTimeout(()=>document.getElementById('sInput')?.focus(),80); }
  function closeSearch(){ document.getElementById('sOverlay').classList.remove('ab'); }
  document.addEventListener('keydown', e => {
    if(e.key==='Escape') closeSearch();
    if((e.ctrlKey||e.metaKey)&&e.key==='k'){ e.preventDefault(); openSearch(); }
  });
  function doSearch(q){
    const r = document.getElementById('sResults');
    if(!q.trim()){ r.innerHTML='<div class="s-empty">Comece a digitar…</div>'; return; }
    const f = produtosAll.filter(p=>p.nome.toLowerCase().includes(q.toLowerCase())||p.categoria.toLowerCase().includes(q.toLowerCase())).slice(0,7);
    if(!f.length){ r.innerHTML=`<div class="s-empty">Nenhum resultado para "<strong>${q}</strong>"</div>`; return; }
    r.innerHTML = f.map(p=>`<a class="s-item" href="produto.php?id=${p.id}">
      <div class="s-img">${p.imagem?`<img src="uploads/produtos/${p.imagem}" alt=""/>`:catIcons[p.categoria]||'💎'}</div>
      <div class="s-info"><h5>${p.nome}</h5><span>${p.categoria}</span></div>
      <span class="s-price">R$ ${parseFloat(p.preco).toFixed(2).replace('.',',')}</span>
    </a>`).join('');
  }

  // ── Dropdown ──
  function toggleDrop(){
    document.getElementById('pDrop')?.classList.toggle('ab');
    document.getElementById('pTrigger')?.classList.toggle('ab');
  }
  document.addEventListener('click', e => {
    const w = document.getElementById('pWrap');
    if(w && !w.contains(e.target)){
      document.getElementById('pDrop')?.classList.remove('ab');
      document.getElementById('pTrigger')?.classList.remove('ab');
    }
  });

  // ── Toast ──
  function showToast(m){
    const t = document.getElementById('toast');
    t.textContent=m; t.style.opacity='1'; t.style.transform='translateY(0)';
    setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateY(10px)'; }, 2800);
  }

  // ── Init ──
  renderCart();
</script>
</body>
</html>