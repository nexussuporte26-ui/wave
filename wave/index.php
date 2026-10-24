<?php
session_start();
require_once 'conexao.php';

if (isset($_GET['sair'])) { session_destroy(); header("Location: index.php"); exit(); }

$msg_senha = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'trocar_senha') {
    if (!isset($_SESSION['usuario_id'])) { $msg_senha = 'erro|Você precisa estar logado.'; }
    else {
        $uid  = (int)$_SESSION['usuario_id'];
        $atual = $_POST['senha_atual'] ?? ''; $nova = $_POST['senha_nova'] ?? ''; $conf = $_POST['senha_confirmar'] ?? '';
        $res  = $conn->query("SELECT senha FROM usuarios WHERE id=$uid LIMIT 1");
        $u    = $res ? $res->fetch_assoc() : null;
        if (!$u || !password_verify($atual, $u['senha'])) $msg_senha = 'erro|Senha atual incorreta.';
        elseif (strlen($nova) < 6) $msg_senha = 'erro|Nova senha deve ter no mínimo 6 caracteres.';
        elseif ($nova !== $conf) $msg_senha = 'erro|As senhas não coincidem.';
        else { $conn->query("UPDATE usuarios SET senha='".password_hash($nova,PASSWORD_DEFAULT)."' WHERE id=$uid"); $msg_senha = 'ok|Senha alterada com sucesso!'; }
    }
}

$logado   = isset($_SESSION['usuario_id']);
$nivel    = $logado ? ($_SESSION['nivel'] ?? 'usuario') : '';
$nome_s   = $logado ? htmlspecialchars($_SESSION['nome'] ?? '') : '';
$primeiro = $nome_s ? strtok($nome_s, ' ') : '';
$iniciais = '';
if ($logado && $nome_s) { $p = explode(' ',$nome_s); $iniciais = strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):'')); }
$udb = null;
if ($logado) { $uid=(int)$_SESSION['usuario_id']; $r=$conn->query("SELECT nome,email,telefone,criado_em FROM usuarios WHERE id=$uid LIMIT 1"); if($r&&$r->num_rows>0) $udb=$r->fetch_assoc(); }
$mt = $mv = '';
if ($msg_senha) [$mt,$mv] = explode('|',$msg_senha,2);
$eh_admin = $nivel === 'admin';

// ── Placeholder para produto/categoria sem imagem (sem emoji, ícone SVG simples) ──
define('IMG_PLACEHOLDER', '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:.35"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>');
define('EYE_ICON', '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>');

// ── Categorias (nome + imagem, editável pelo admin em categorias_admin.php) ──
$categorias = [];
$rc = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM produtos p WHERE p.categoria=c.nome AND p.ativo=1) as qtd FROM categorias c ORDER BY c.ordem, c.nome");
if ($rc) while ($row = $rc->fetch_assoc()) $categorias[] = $row;

// ── Mais vendidos: produtos com mais unidades vendidas neste mês ──
$mais_vendidos = [];
$rmv = $conn->query("
    SELECT p.id, p.nome, p.preco, p.categoria, p.imagem, SUM(vi.quantidade) as qtd_vendida
    FROM vendas_itens vi
    JOIN vendas v ON vi.venda_id = v.id
    JOIN produtos p ON vi.produto_id = p.id
    WHERE p.ativo = 1 AND v.data_venda >= DATE_FORMAT(NOW(), '%Y-%m-01')
    GROUP BY p.id
    ORDER BY qtd_vendida DESC
    LIMIT 8
");
if ($rmv) while ($row = $rmv->fetch_assoc()) $mais_vendidos[] = $row;

// Sem vendas suficientes este mês? Completa com produtos ativos aleatórios,
// pra sempre mostrar algo interessante ao invés de uma seção vazia.
if (count($mais_vendidos) < 8) {
    $ja_incluidos = array_map(fn($p) => (int)$p['id'], $mais_vendidos);
    $faltam = 8 - count($mais_vendidos);
    $excl = $ja_incluidos ? implode(',', $ja_incluidos) : '0';
    $rr = $conn->query("SELECT id, nome, preco, categoria, imagem FROM produtos WHERE ativo=1 AND id NOT IN ($excl) ORDER BY RAND() LIMIT $faltam");
    if ($rr) while ($row = $rr->fetch_assoc()) $mais_vendidos[] = $row;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wave Acessórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300..800&family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&display=swap" rel="stylesheet" />
  <link rel="preload" as="image" href="hero-praia.webp" type="image/webp" fetchpriority="high" />
  <style>
    :root {
      --blue:#0A5489; --blue-dk:#073d66; --blue-lt:#1a7abf;
      --cream:#FFF3E7; --sand:#F5DEC8; --gold:#C8963E;
      --white:#FEFCF9; --text:#1a2e3b; --muted:#7a8d99;
      --font-display:'Manrope',system-ui,sans-serif;
      --font-body:'Manrope',system-ui,sans-serif;
      --ease:cubic-bezier(.22,.61,.36,1);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:var(--font-body);background:var(--white);color:var(--text);overflow-x:hidden}
    a{text-decoration:none;color:inherit}
    img{display:block;max-width:100%}

    /* ── NAV ── */
    nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:.5rem 5%;background:rgba(255,243,231,.97);backdrop-filter:blur(12px);box-shadow:0 2px 24px rgba(10,84,137,.10);border-bottom:1.5px solid rgba(10,84,137,.08);transition:box-shadow .4s var(--ease)}
    nav.scrolled{box-shadow:0 4px 32px rgba(10,84,137,.15)}
    .nav-logo-img{height:62px;width:auto;transition:transform .3s var(--ease)}
    .nav-logo-img:hover{transform:scale(1.04)}
    .nav-links{display:flex;gap:2.2rem;font-size:.78rem;letter-spacing:.14em;text-transform:uppercase;font-weight:500}
    .nav-links a{color:var(--blue);transition:color .25s;position:relative;padding-bottom:3px}
    .nav-links a::after{content:'';position:absolute;bottom:0;left:0;width:0;height:1.5px;background:var(--gold);transition:width .3s var(--ease)}
    .nav-links a:hover{color:var(--gold)} .nav-links a:hover::after{width:100%}
    .nav-icons{display:flex;gap:.9rem;align-items:center}
    .nav-icons button{background:none;border:none;cursor:pointer;color:var(--blue);font-size:1.1rem;transition:color .25s}
    .nav-icons button:hover{color:var(--gold)}
    .nav-ham{display:none;background:none;border:none;cursor:pointer;color:var(--blue);padding:.3rem;align-items:center;justify-content:center;flex-shrink:0}
    .mobile-nav-panel{display:none;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:var(--white);border-top:1px solid rgba(10,84,137,.1);box-shadow:0 16px 32px rgba(10,84,137,.14);padding:.4rem 5% 1rem;z-index:200}
    .mobile-nav-panel.open{display:flex}
    .mobile-nav-panel a,.mobile-nav-panel button.mnp-item{display:block;width:100%;text-align:left;background:none;border:none;font-family:inherit;padding:.85rem .2rem;font-size:.85rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--blue);border-bottom:1px solid rgba(10,84,137,.07);cursor:pointer}
    .mobile-nav-panel a:last-child,.mobile-nav-panel button.mnp-item:last-child{border-bottom:none}
    .nav-cart{position:relative;background:var(--blue);color:var(--cream)!important;padding:.45rem 1.1rem;border-radius:2rem;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;font-weight:500;transition:background .25s!important;display:inline-flex;align-items:center;gap:.4rem}
    .cart-badge{background:var(--gold);color:#fff;border-radius:50%;width:18px;height:18px;font-size:.6rem;font-weight:700;display:none;align-items:center;justify-content:center;position:absolute;top:-5px;right:-5px;transition:transform .2s}
    .cart-badge.show{display:flex}
    .cart-badge.pop{transform:scale(1.45)}
    .nav-cart:hover{background:var(--gold)!important}
    .nav-login-btn{font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--blue);border:1.5px solid var(--blue);padding:.38rem .9rem;border-radius:2rem;font-weight:500;transition:all .25s;white-space:nowrap}
    .nav-login-btn:hover{background:var(--blue);color:var(--cream)}
    .profile-wrap{position:relative}
    .profile-trigger{display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.3rem .65rem .3rem .3rem;border-radius:2rem;border:1.5px solid rgba(10,84,137,.2);background:rgba(255,243,231,.85);transition:all .22s;user-select:none}
    .profile-trigger:hover,.profile-trigger.ab{border-color:var(--blue);background:var(--cream)}
    .nav-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue-dk),var(--blue-lt));color:#fff;font-size:.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .nav-av.adm{background:linear-gradient(135deg,#5b21b6,#8b5cf6)}
    .nav-nome{font-size:.78rem;font-weight:600;color:var(--blue);max-width:90px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .nav-arrow{font-size:.6rem;color:var(--muted);transition:transform .22s}
    .profile-trigger.ab .nav-arrow{transform:rotate(180deg)}
    .pd{position:absolute;top:calc(100% + .8rem);right:0;width:min(310px,calc(100vw - 2rem));background:var(--white);border:1px solid rgba(10,84,137,.12);border-radius:1.2rem;box-shadow:0 16px 50px rgba(10,84,137,.16);z-index:300;overflow:hidden;opacity:0;transform:translateY(-10px) scale(.97);pointer-events:none;transition:opacity .22s var(--ease),transform .22s var(--ease)}
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

    /* ── MODAL ── */
    .modal-bg{position:fixed;inset:0;background:rgba(7,61,102,.55);backdrop-filter:blur(6px);z-index:500;display:none;align-items:center;justify-content:center;padding:1rem}
    .modal-bg.ab{display:flex}
    .modal{background:var(--white);border-radius:1.4rem;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(7,61,102,.25);animation:mIn .3s var(--ease) both}
    @keyframes mIn{from{opacity:0;transform:scale(.95) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .mhead{padding:1.4rem 1.5rem 1rem;background:linear-gradient(135deg,var(--blue-dk),var(--blue));border-radius:1.4rem 1.4rem 0 0;display:flex;align-items:center;justify-content:space-between}
    .mav{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);border:2.5px solid rgba(255,255,255,.38);color:#fff;font-size:1.2rem;font-weight:700;display:flex;align-items:center;justify-content:center}
    .mhead-info{flex:1;margin-left:.9rem}
    .mhead-info h3{font-family:var(--font-display);font-size:1.35rem;font-weight:600;color:#fff}
    .mhead-info p{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.1rem}
    .mclose{background:rgba(255,255,255,.15);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center;transition:background .2s;flex-shrink:0}
    .mclose:hover{background:rgba(255,255,255,.28)}
    .mtabs{display:flex;border-bottom:1px solid rgba(10,84,137,.1);background:var(--cream);padding:0 1.5rem}
    .mtab{padding:.8rem .2rem;margin-right:1.4rem;font-size:.78rem;font-weight:600;color:var(--muted);border-bottom:2.5px solid transparent;cursor:pointer;transition:color .2s,border-color .2s;border:none;background:none;font-family:var(--font-body)}
    .mtab.on{color:var(--blue);border-bottom-color:var(--blue)}
    .mbody{padding:1.4rem 1.5rem}
    .tc{display:none}.tc.on{display:block}
    .mfg{display:flex;flex-direction:column;gap:.3rem;margin-bottom:.9rem}
    .mfg label{font-size:.7rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.055em}
    .mfw{display:flex;align-items:center;gap:.5rem;border:1.5px solid rgba(10,84,137,.2);border-radius:.6rem;padding:.6rem .85rem;background:var(--cream);transition:border-color .18s}
    .mfw:focus-within{border-color:var(--blue);background:var(--white)}
    .mfw input{border:none;outline:none;background:none;font-size:.88rem;color:var(--text);font-family:var(--font-body);flex:1}
    .mfw input[readonly]{color:var(--muted);cursor:default}
    .mr2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
    .malert{border-radius:.6rem;padding:.65rem .9rem;font-size:.8rem;font-weight:500;margin-bottom:1rem;display:flex;align-items:center;gap:.45rem}
    .malert.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
    .malert.er{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;animation:shake .35s ease}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
    .pbar{height:4px;border-radius:999px;background:rgba(10,84,137,.1);margin-top:.35rem;overflow:hidden}
    .pfill{height:100%;border-radius:999px;transition:width .3s,background .3s;width:0}
    .phint{font-size:.64rem;color:var(--muted);margin-top:.2rem}
    .mbtn{display:inline-flex;align-items:center;gap:.4rem;border:none;cursor:pointer;font-family:var(--font-body);border-radius:2rem;font-weight:600;transition:all .2s}
    .mbtn-p{background:var(--blue);color:var(--cream);padding:.65rem 1.6rem;font-size:.84rem;box-shadow:0 4px 14px rgba(10,84,137,.22)}
    .mbtn-p:hover{background:var(--gold);transform:translateY(-1px)}
    .mbtn-s{background:var(--cream);color:var(--blue);border:1.5px solid rgba(10,84,137,.2);padding:.62rem 1.2rem;font-size:.82rem}
    .mbtn-s:hover{border-color:var(--blue)}

    /* ── SEARCH OVERLAY ── */
    .search-overlay{display:none;position:fixed;inset:0;background:rgba(7,61,102,.6);backdrop-filter:blur(8px);z-index:200;align-items:flex-start;justify-content:center;padding:5rem 1rem 1rem}
    .search-overlay.ab{display:flex}
    .search-box{background:var(--white);border-radius:1.4rem;width:100%;max-width:600px;overflow:hidden;box-shadow:0 24px 80px rgba(7,61,102,.25);animation:sIn .28s var(--ease) both}
    @keyframes sIn{from{opacity:0;transform:translateY(-12px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
    .search-input-wrap{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.1)}
    .search-input-wrap input{flex:1;border:none;outline:none;font-family:var(--font-display);font-size:1.4rem;color:var(--text);background:none;min-width:0}
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

    /* ── HERO ── */
    .hero{position:relative;min-height:100vh;display:flex;align-items:center;overflow:hidden;background:linear-gradient(145deg,var(--cream) 0%,#e8f4ff 60%,var(--cream) 100%)}
    /* Foto de fundo do topo + película clara à esquerda para manter o texto legível */
    .hero::before{content:"";position:absolute;inset:0;z-index:0;background:url(hero-praia.webp) 50% 70%/cover no-repeat}
    .hero::after{content:"";position:absolute;inset:0;z-index:1;pointer-events:none;background:linear-gradient(90deg,rgba(255,243,231,.92) 0%,rgba(255,243,231,.80) 40%,rgba(255,243,231,0) 70%)}
    .hero .scroll-hint{z-index:2}
    .hero-waves,.hero-circle{display:none} /* decoração antiga, substituída pela foto (HTML mantido) */
    @media(max-width:900px){.hero::before{background-position:62% 70%}.hero::after{background:rgba(255,243,231,.84)}}
    .hero-waves{position:absolute;inset:0;pointer-events:none;overflow:hidden}
    .hero-waves svg{position:absolute;bottom:-2px;width:110%;left:-5%}
    .wave1{animation:waveShift 9s ease-in-out infinite alternate}
    .wave2{animation:waveShift 12s ease-in-out infinite alternate-reverse;opacity:.6}
    .wave3{animation:waveShift 7s ease-in-out infinite alternate;opacity:.35}
    @keyframes waveShift{0%{transform:translateX(0) scaleY(1)}100%{transform:translateX(-4%) scaleY(1.08)}}
    .hero-circle{position:absolute;border-radius:50%;background:radial-gradient(circle,rgba(10,84,137,.12) 0%,transparent 70%)}
    .hc1{width:600px;height:600px;top:-120px;right:-100px;animation:floatY 8s ease-in-out infinite}
    .hc2{width:300px;height:300px;bottom:10%;left:5%;animation:floatY 11s ease-in-out infinite reverse}
    @keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-24px)}}
    .hero-content{position:relative;z-index:2;padding:0 5%;max-width:700px;animation:heroIn 1.1s var(--ease) both}
    @keyframes heroIn{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
    .hero-tag{display:inline-block;font-size:.72rem;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);border:1px solid var(--gold);padding:.3rem .9rem;border-radius:2rem;margin-bottom:1.6rem}
    .hero h1{font-family:'Cormorant Garamond',Georgia,serif;font-size:clamp(3.2rem,8.5vw,7.5rem);font-weight:600;line-height:1.0;color:var(--blue);margin-bottom:1.8rem;letter-spacing:-.02em}
    .hero h1 em{font-style:italic;font-weight:300;color:var(--gold);display:block}
    .hero p{font-size:1.05rem;color:var(--muted);line-height:1.75;max-width:440px;margin-bottom:2.4rem;font-weight:300}
    .hero-btns{display:flex;gap:1rem;flex-wrap:wrap}
    .btn-primary{background:var(--blue);color:var(--cream);padding:.85rem 2.2rem;border-radius:3rem;font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;font-weight:500;transition:background .3s,transform .2s;box-shadow:0 8px 28px rgba(10,84,137,.25);border:none;cursor:pointer;font-family:var(--font-body)}
    .btn-primary:hover{background:var(--gold);transform:translateY(-2px)}
    .btn-outline{border:1.5px solid var(--blue);color:var(--blue);padding:.85rem 2.2rem;border-radius:3rem;font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;font-weight:500;transition:all .3s}
    .btn-outline:hover{background:var(--blue);color:var(--cream)}
    .scroll-hint{position:absolute;bottom:2.5rem;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:.5rem;font-size:.68rem;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);animation:heroIn 1.4s var(--ease) .5s both}
    .scroll-line{width:1px;height:48px;background:linear-gradient(to bottom,var(--blue),transparent);animation:lineDrop 1.8s ease-in-out infinite}
    @keyframes lineDrop{0%{transform:scaleY(0);transform-origin:top}50%{transform:scaleY(1);transform-origin:top}51%{transform:scaleY(1);transform-origin:bottom}100%{transform:scaleY(0);transform-origin:bottom}}
    .strip{background:var(--blue);color:var(--cream);display:flex;align-items:center;justify-content:center;gap:3rem;padding:.85rem 5%;overflow:hidden;font-size:.75rem;letter-spacing:.12em;text-transform:uppercase}
    .strip span::before{content:'✦';margin-right:.6rem;color:var(--gold)}
    section{padding:7rem 5%}
    .section-label{font-size:.72rem;letter-spacing:.25em;text-transform:uppercase;color:var(--gold);margin-bottom:.8rem}
    .section-title{font-family:var(--font-display);font-size:clamp(2rem,5vw,3.8rem);font-weight:600;color:var(--blue);line-height:1.05}
    .section-title em{font-style:italic}

    /* ── CATEGORIES ── */
    .categories{background:var(--cream)}.categories-header{text-align:center;margin-bottom:3.5rem}
    .cat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem}
    .cat-card{position:relative;overflow:hidden;border-radius:1.2rem;aspect-ratio:3/4;cursor:pointer;transition:transform .45s var(--ease),box-shadow .45s}
    .cat-card:hover{transform:translateY(-8px);box-shadow:0 20px 50px rgba(10,84,137,.2)}
    .cat-card-bg{position:absolute;inset:0;transition:transform .5s var(--ease)}
    .cat-card-bg img{width:100%;height:100%;object-fit:cover}
    .cat-card:hover .cat-card-bg{transform:scale(1.06)}
    .cat-card:nth-child(5n+1) .cat-card-bg{background:linear-gradient(160deg,#b8d8f0 0%,#0A5489 100%)}
    .cat-card:nth-child(5n+2) .cat-card-bg{background:linear-gradient(160deg,#f5e4cc 0%,#c8963e 100%)}
    .cat-card:nth-child(5n+3) .cat-card-bg{background:linear-gradient(160deg,#c8e6f5 0%,#073d66 100%)}
    .cat-card:nth-child(5n+4) .cat-card-bg{background:linear-gradient(160deg,#fce8d2 0%,#0A5489 100%)}
    .cat-card:nth-child(5n+5) .cat-card-bg{background:linear-gradient(160deg,#d6cdf0 0%,#4c2a8f 100%)}
    .cat-card-wave{position:absolute;bottom:0;left:0;right:0;height:60%;opacity:.15}
    .cat-card-wave svg{width:100%;height:100%}
    .cat-info{position:absolute;bottom:0;left:0;right:0;padding:1.5rem 1.4rem;background:linear-gradient(to top,rgba(7,61,102,.85) 0%,transparent 100%);color:#fff}
    .cat-info h3{font-family:var(--font-display);font-size:1.5rem;font-weight:600;letter-spacing:.04em;margin-bottom:.2rem}
    .cat-info span{font-size:.73rem;letter-spacing:.12em;text-transform:uppercase;opacity:.8}

    /* ── MAIS VENDIDOS ── */
    .mv-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.4rem}
    .mv-card{border-radius:1.2rem;overflow:hidden;background:var(--white);box-shadow:0 4px 22px rgba(10,84,137,.07);transition:transform .35s var(--ease),box-shadow .35s;position:relative;display:block}
    .mv-card:hover{transform:translateY(-6px);box-shadow:0 16px 38px rgba(10,84,137,.14)}
    .mv-img{aspect-ratio:1;position:relative;overflow:hidden;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center}
    .mv-img img{width:100%;height:100%;object-fit:cover}
    .mv-badge{position:absolute;top:.7rem;left:.7rem;background:var(--gold);color:#fff;font-size:.6rem;letter-spacing:.08em;text-transform:uppercase;padding:.22rem .6rem;border-radius:2rem;font-weight:700}
    .mv-info{padding:1rem 1.15rem 1.2rem}
    .mv-cat{font-size:.65rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.15rem}
    .mv-nome{font-family:var(--font-display);font-size:1.08rem;font-weight:600;margin-bottom:.35rem;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .mv-row{display:flex;align-items:center;justify-content:space-between;gap:.5rem}
    .mv-preco{font-size:.98rem;font-weight:700;color:var(--blue)}
    .mv-add{background:var(--blue);color:var(--cream);border:none;cursor:pointer;padding:.42rem .85rem;border-radius:2rem;font-size:.68rem;letter-spacing:.06em;text-transform:uppercase;font-weight:600;transition:background .22s;white-space:nowrap}
    .mv-add:hover{background:var(--gold)}
    @media(max-width:1100px){.mv-grid{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:760px){.mv-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.mv-grid{grid-template-columns:1fr}}

    /* ── BRAND STORY ── */
    .brand-story{background:var(--blue);display:grid;grid-template-columns:1fr 1fr;min-height:560px;border-radius:2rem;overflow:hidden;margin:0 3%}
    .story-visual{position:relative;overflow:hidden;background:linear-gradient(135deg,#0d6aaa 0%,#073d66 100%);display:flex;align-items:center;justify-content:center;padding:3rem}
    .story-wave-deco{position:absolute;inset:0;opacity:.12}.story-wave-deco svg{width:100%;height:100%}
    .story-emblem{position:relative;z-index:2;font-family:var(--font-display);font-size:9rem;color:rgba(255,243,231,.15);font-weight:600;line-height:1;user-select:none}
    .story-emblem-overlay{position:absolute;inset:0;z-index:3;display:flex;align-items:center;justify-content:center}
    .story-ring{width:220px;height:220px;border-radius:50%;border:1px solid rgba(255,243,231,.3);display:flex;align-items:center;justify-content:center;animation:rotateSlow 20s linear infinite}
    @keyframes rotateSlow{to{transform:rotate(360deg)}}
    .story-ring-inner{width:170px;height:170px;border-radius:50%;border:1px dashed rgba(200,150,62,.4);display:flex;align-items:center;justify-content:center;animation:rotateSlow 14s linear infinite reverse}
    .story-icon-center{font-size:3.5rem}
    /* Foto da equipe no bloco Nossa história (cobre o emblema decorativo, que fica oculto) */
    .story-visual .story-emblem,.story-visual .story-emblem-overlay{display:none}
    .story-photo{position:relative;z-index:4;display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;object-position:50% 50%;border-radius:1.2rem;box-shadow:0 18px 40px rgba(7,61,102,.45);border:1px solid rgba(255,243,231,.25);background:#073d66}
    .story-text{padding:4rem 3.5rem;display:flex;flex-direction:column;justify-content:center;color:var(--cream)}
    .story-text .section-label{color:var(--gold)}.story-text .section-title{color:var(--cream);margin-bottom:1.4rem}
    .story-text p{color:rgba(255,243,231,.75);line-height:1.8;font-size:.95rem;font-weight:300;margin-bottom:2rem}
    .story-stats{display:flex;gap:2.5rem;margin-bottom:2.5rem}
    .stat-item{display:flex;flex-direction:column;gap:.2rem}
    .stat-num{font-family:var(--font-display);font-size:2.4rem;font-weight:600;color:var(--cream);line-height:1}
    .stat-lbl{font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,243,231,.55)}
    .btn-light{display:inline-block;border:1.5px solid rgba(255,243,231,.5);color:var(--cream);padding:.85rem 2.2rem;border-radius:3rem;font-size:.85rem;letter-spacing:.08em;text-transform:uppercase;font-weight:500;transition:all .3s;align-self:flex-start}
    .btn-light:hover{background:rgba(255,243,231,.12);border-color:var(--cream)}

    /* ── FEATURES ── */
    .features{background:var(--cream);display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))}
    .feature-item{display:flex;flex-direction:column;align-items:center;text-align:center;padding:3rem 2rem;border-right:1px solid rgba(10,84,137,.1);transition:background .3s}
    .feature-item:last-child{border-right:none}.feature-item:hover{background:rgba(10,84,137,.04)}
    .feat-icon{font-size:2.2rem;margin-bottom:1rem}
    .feat-title{font-family:var(--font-display);font-size:1.15rem;font-weight:600;color:var(--blue);margin-bottom:.4rem}
    .feat-desc{font-size:.82rem;color:var(--muted);line-height:1.6;font-weight:300}

    /* ── NEWSLETTER ── */
    .newsletter{text-align:center;background:var(--white);position:relative;overflow:hidden}
    .newsletter::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 100%,rgba(10,84,137,.06) 0%,transparent 70%);pointer-events:none}
    .newsletter .section-title{margin-bottom:.8rem}
    .newsletter p{color:var(--muted);font-size:.95rem;max-width:420px;margin:0 auto 2.5rem;font-weight:300}
    .nl-form{display:flex;gap:.8rem;max-width:480px;margin:0 auto;justify-content:center;flex-wrap:wrap}
    .nl-form input{flex:1;min-width:220px;padding:.9rem 1.4rem;border-radius:3rem;border:1.5px solid rgba(10,84,137,.25);background:var(--cream);font-family:var(--font-body);font-size:.9rem;outline:none;color:var(--text);transition:border-color .25s,box-shadow .25s}
    .nl-form input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(10,84,137,.08)}
    .nl-form input::placeholder{color:var(--muted)}

    /* ── FOOTER ── */
    footer{background:var(--blue-dk);color:rgba(255,243,231,.8);padding:4rem 5% 2rem}
    .footer-top{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:3rem;padding-bottom:3rem;border-bottom:1px solid rgba(255,243,231,.12);margin-bottom:2rem}
    .footer-logo{height:72px;width:auto;display:block;margin-bottom:1rem;filter:brightness(0) invert(1) sepia(1) saturate(0) brightness(2.2);opacity:.82}
    .footer-brand p{font-size:.85rem;line-height:1.7;font-weight:300;max-width:240px}
    .footer-social{display:flex;gap:.8rem;margin-top:1.5rem}
    .social-btn{width:2.2rem;height:2.2rem;border-radius:50%;border:1px solid rgba(255,243,231,.25);display:flex;align-items:center;justify-content:center;font-size:.9rem;cursor:pointer;transition:all .25s}
    .social-btn:hover{background:var(--gold);border-color:var(--gold)}
    .footer-col h5{font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:1.2rem;font-weight:600}
    .footer-col ul{list-style:none;display:flex;flex-direction:column;gap:.65rem}
    .footer-col ul li{font-size:.85rem;font-weight:300;cursor:pointer;transition:color .25s}
    .footer-col ul li:hover{color:var(--cream)}
    .footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:.75rem;color:rgba(255,243,231,.4)}
    .footer-slogan{font-family:var(--font-display);font-style:italic;font-size:.95rem;color:rgba(255,243,231,.35);letter-spacing:.04em}

    /* ── ANIMATIONS ── */
    .fade-in{opacity:0;transform:translateY(30px);transition:opacity .7s var(--ease),transform .7s var(--ease)}
    .fade-in.visible{opacity:1;transform:translateY(0)}
    .fade-in:nth-child(2){transition-delay:.1s}.fade-in:nth-child(3){transition-delay:.2s}.fade-in:nth-child(4){transition-delay:.3s}

    /* ── TOAST ── */
    #toast{position:fixed;bottom:1.5rem;right:1.5rem;background:var(--blue);color:#fff;padding:.65rem 1.2rem;border-radius:2rem;font-size:.8rem;font-weight:500;z-index:999;opacity:0;transform:translateY(10px);transition:all .28s;pointer-events:none}

    /* ── RESPONSIVE ── */
    @media(max-width:1100px){.c-card,.c-card-cta{flex:0 0 calc((100% - 2 * 1.5rem) / 3)}}
    @media(max-width:760px){.c-card,.c-card-cta{flex:0 0 calc((100% - 1.5rem) / 2)}}
    @media(max-width:500px){.c-card,.c-card-cta{flex:0 0 80%}}
    @media(max-width:900px){.brand-story{grid-template-columns:1fr;margin:0;border-radius:0}.story-visual{min-height:300px}.footer-top{grid-template-columns:1fr 1fr}.nav-links{display:none}.nav-nome{display:none}.nav-ham{display:flex}}
    @media(max-width:600px){.story-visual{padding:1.4rem}.footer-top{grid-template-columns:1fr}.strip{gap:1.5rem;font-size:.65rem}.story-stats{gap:1.5rem}.story-text{padding:2.5rem 1.8rem}.nav-logo-img{height:48px}.mr2{grid-template-columns:1fr}.search-input-wrap input{font-size:1.05rem}.mhead{padding:1.1rem 1.1rem .85rem}.mbody{padding:1.1rem}.mtabs{padding:0 1.1rem}}
  </style>
</head>
<body>

<!-- NAV -->
<nav id="mainNav">
  <a href="index.php"><img class="nav-logo-img" src="logo-removebg-preview.png" alt="Wave Acessórios"/></a>
  <div class="nav-links">
    <a href="index.php">Início</a>
    <a href="catalogo.php">Catálogo</a>
    <a href="#contato">Contato</a>
    <?php if ($eh_admin): ?>
      <a href="Dashboard.php">Dashboard</a>
    <?php endif; ?>
  </div>
  <div class="nav-icons">
    <button class="nav-ham" id="navHam" onclick="toggleMobileNav()" aria-label="Abrir menu" aria-expanded="false" title="Menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    <button onclick="openSearch()" title="Buscar (Ctrl+K)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
    <button>♡</button>
    <a class="nav-cart" href="carrinho.php" id="navCartBtn">Sacola<span class="cart-badge" id="cartBadge">0</span></a>

    <?php if ($logado): ?>
      <div class="profile-wrap" id="pWrap">
        <div class="profile-trigger" id="pTrigger" onclick="toggleDrop()">
          <div class="nav-av <?= $eh_admin?'adm':'' ?>"><?= $iniciais?:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' ?></div>
          <span class="nav-nome"><?= $primeiro ?></span>
          <span class="nav-arrow">▾</span>
        </div>
        <div class="pd" id="pDrop">
          <div class="pd-head">
            <div class="pd-av <?= $eh_admin?'adm':'' ?>"><?= $iniciais?:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' ?></div>
            <div>
              <div class="pd-nome"><?= $nome_s ?></div>
              <div class="pd-email"><?= htmlspecialchars($udb['email']??'') ?></div>
              <div class="pd-badge"><?= $eh_admin?'Administrador':'Cliente' ?></div>
            </div>
          </div>
          <div class="pd-stats">
            <div class="pd-stat"><div class="pd-sv">0</div><div class="pd-sl">Pedidos</div></div>
            <div class="pd-stat"><div class="pd-sv">0</div><div class="pd-sl">Favoritos</div></div>
            <div class="pd-stat"><div class="pd-sv"><?= ($udb&&$udb['criado_em'])?(new DateTime($udb['criado_em']))->format('m/Y'):'—' ?></div><div class="pd-sl">Membro</div></div>
          </div>
          <div class="pd-menu">
            <button class="pd-item" onclick="abrirModal('dados')"><div class="pd-ic ib"></div>Minha conta</button>
            <button class="pd-item" onclick="abrirModal('pedidos')"><div class="pd-ic io"></div>Meus pedidos</button>
            <button class="pd-item" onclick="abrirModal('endereco')"><div class="pd-ic ig"></div>Endereço</button>
            <button class="pd-item" onclick="abrirModal('senha')"><div class="pd-ic ip"></div>Alterar senha</button>
            <?php if ($eh_admin): ?>
              <div class="pd-sep"></div>
              <div class="pd-adm"><div class="at"><span>Painel Admin</span><p>Gerencie a loja</p></div></div>
              <a href="Dashboard.php" class="pd-item"><div class="pd-ic ip"></div>Dashboard</a>
              <a href="cadastro_produto.php" class="pd-item"><div class="pd-ic ib"></div>Novo Produto</a>
            <?php endif; ?>
            <div class="pd-sep"></div>
            <a href="index.php?sair=1" class="pd-item sair"><div class="pd-ic ir"></div>Sair da conta</a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <a href="login.php" class="nav-login-btn">Entrar</a>
      <a href="cadastro.php"><button class="nav-login-btn">Cadastrar-se</button></a>
    <?php endif; ?>
  </div>

  <div class="mobile-nav-panel" id="mobileNavPanel">
    <a href="index.php">Início</a>
    <a href="catalogo.php">Catálogo</a>
    <a href="#contato">Contato</a>
    <?php if ($eh_admin): ?>
      <a href="Dashboard.php">Dashboard</a>
      <a href="cadastro_produto.php">Novo Produto</a>
    <?php endif; ?>
    <?php if ($logado): ?>
      <a href="index.php?sair=1">Sair da conta</a>
    <?php else: ?>
      <a href="login.php">Entrar</a>
      <a href="cadastro.php">Cadastrar-se</a>
    <?php endif; ?>
  </div>
</nav>

<!-- MODAL PERFIL -->
<?php if ($logado): ?>
<div class="modal-bg" id="modal" onclick="if(event.target===this)fecharModal()">
  <div class="modal">
    <div class="mhead">
      <div class="mav <?= $eh_admin?'adm':'' ?>"><?= $iniciais?:'<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' ?></div>
      <div class="mhead-info"><h3><?= $nome_s ?></h3><p><?= htmlspecialchars($udb['email']??'') ?></p></div>
      <button class="mclose" onclick="fecharModal()">✕</button>
    </div>
    <div class="mtabs">
      <button class="mtab on" id="mt-dados"    onclick="tab('dados')">Dados</button>
      <button class="mtab"    id="mt-pedidos"  onclick="tab('pedidos')">Pedidos</button>
      <button class="mtab"    id="mt-endereco" onclick="tab('endereco')">Endereço</button>
      <button class="mtab"    id="mt-senha"    onclick="tab('senha')">Senha</button>
    </div>
    <div class="mbody">
      <!-- DADOS -->
      <div class="tc on" id="tc-dados">
        <div class="mr2">
          <div class="mfg"><label>Nome completo</label><div class="mfw"><input type="text" value="<?= htmlspecialchars($udb['nome']??'') ?>" readonly/></div></div>
          <div class="mfg"><label>E-mail</label><div class="mfw"><input type="email" value="<?= htmlspecialchars($udb['email']??'') ?>" readonly/></div></div>
        </div>
        <div class="mr2">
          <div class="mfg"><label>Telefone / WhatsApp</label><div class="mfw"><input type="text" value="<?= htmlspecialchars($udb['telefone']??'Não informado') ?>" readonly/></div></div>
          <div class="mfg"><label>Membro desde</label><div class="mfw"><input type="text" value="<?php
            if ($udb&&$udb['criado_em']){ $ms=['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']; $d=new DateTime($udb['criado_em']); echo $ms[(int)$d->format('n')-1].'/'.$d->format('Y'); } else echo '—';
          ?>" readonly/></div></div>
        </div>
        <div class="mfg"><label>Nível de acesso</label><div class="mfw"><input type="text" value="<?= $eh_admin?'Administrador':'Cliente' ?>" readonly/></div></div>
        <p style="font-size:.72rem;color:var(--muted);margin-top:.4rem">Para alterar seus dados, entre em contato com o suporte.</p>
      </div>
      <!-- PEDIDOS -->
      <div class="tc" id="tc-pedidos">
        <div style="text-align:center;padding:2.5rem 1rem">
          <h3 style="font-family:var(--font-display);font-size:1.6rem;color:var(--blue);opacity:.65;margin-bottom:.4rem">Nenhum pedido ainda</h3>
          <p style="font-size:.84rem;color:var(--muted);max-width:270px;margin:0 auto 1.2rem;line-height:1.6">Quando fizer sua primeira compra, ela aparecerá aqui.</p>
          <button class="mbtn mbtn-p" onclick="fecharModal()">Explorar produtos →</button>
        </div>
      </div>
      <!-- ENDEREÇO -->
      <div class="tc" id="tc-endereco">
        <div style="text-align:center;padding:2rem 1rem">
          <h3 style="font-family:var(--font-display);font-size:1.5rem;color:var(--blue);opacity:.65;margin-bottom:.35rem">Nenhum endereço salvo</h3>
          <p style="font-size:.82rem;color:var(--muted);max-width:250px;margin:0 auto 1rem;line-height:1.6">Adicione um endereço para agilizar suas compras.</p>
          <button class="mbtn mbtn-p" onclick="showToast('Em breve!')">+ Adicionar endereço</button>
        </div>
      </div>
      <!-- SENHA -->
      <div class="tc" id="tc-senha">
        <?php if ($mv): ?>
          <div class="malert <?= $mt==='ok'?'ok':'er' ?>"><?= htmlspecialchars($mv) ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php" onsubmit="return valSenha()">
          <input type="hidden" name="action" value="trocar_senha"/>
          <div class="mfg"><label>Senha atual</label>
            <div class="mfw">
              <input type="password" name="senha_atual" id="sA" placeholder="Sua senha atual" required/>
              <button type="button" id="eyeA" onclick="vis('sA')" style="background:none;border:none;cursor:pointer;opacity:.55" title="Mostrar senha"><?= EYE_ICON ?></button>
            </div></div>
          <div class="mfg"><label>Nova senha</label>
            <div class="mfw">
              <input type="password" name="senha_nova" id="sN" placeholder="Mínimo 6 caracteres" oninput="medirSenha(this.value)" required/>
              <button type="button" id="eyeN" onclick="vis('sN')" style="background:none;border:none;cursor:pointer;opacity:.55" title="Mostrar senha"><?= EYE_ICON ?></button>
            </div>
            <div class="pbar"><div class="pfill" id="pf"></div></div>
            <div class="phint" id="ph">Digite a nova senha</div>
          </div>
          <div class="mfg"><label>Confirmar nova senha</label>
            <div class="mfw">
              <input type="password" name="senha_confirmar" id="sC" placeholder="Repita a nova senha" required/>
              <button type="button" id="eyeC" onclick="vis('sC')" style="background:none;border:none;cursor:pointer;opacity:.55" title="Mostrar senha"><?= EYE_ICON ?></button>
            </div></div>
          <div class="malert er" id="eS" style="display:none"></div>
          <div style="display:flex;gap:.7rem;flex-wrap:wrap;margin-top:.5rem">
            <button type="submit" class="mbtn mbtn-p">Salvar nova senha</button>
            <button type="button" class="mbtn mbtn-s" onclick="fecharModal()">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="toast"></div>

<!-- SEARCH OVERLAY -->
<div class="search-overlay" id="searchOverlay" onclick="if(event.target===this)closeSearch()">
  <div class="search-box">
    <div class="search-input-wrap">
      <span style="opacity:.45"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
      <input type="text" id="searchInput" placeholder="Buscar acessórios…" oninput="doSearch(this.value)" autocomplete="off"/>
      <button class="search-close" onclick="closeSearch()">✕</button>
    </div>
    <div class="search-results" id="searchResults">
      <div class="search-empty">Comece a digitar para buscar produtos…</div>
    </div>
    <div class="search-footer">Pressione <strong>Esc</strong> para fechar</div>
  </div>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hero-waves">
    <svg class="wave1" viewBox="0 0 1440 320" preserveAspectRatio="none" style="bottom:0;height:260px"><path fill="rgba(10,84,137,.09)" d="M0,224L48,197.3C96,171,192,117,288,117.3C384,117,480,171,576,181.3C672,192,768,160,864,149.3C960,139,1056,149,1152,165.3C1248,181,1344,203,1392,213.3L1440,224L1440,320L0,320Z"/></svg>
    <svg class="wave2" viewBox="0 0 1440 320" preserveAspectRatio="none" style="bottom:0;height:200px"><path fill="rgba(200,150,62,.08)" d="M0,256L60,240C120,224,240,192,360,186.7C480,181,600,203,720,213.3C840,224,960,224,1080,208C1200,192,1320,160,1380,144L1440,128L1440,320L0,320Z"/></svg>
    <svg class="wave3" viewBox="0 0 1440 320" preserveAspectRatio="none" style="bottom:0;height:140px"><path fill="rgba(10,84,137,.06)" d="M0,288L80,272C160,256,320,224,480,218.7C640,213,800,235,960,245.3C1120,256,1280,256,1360,256L1440,256L1440,320L0,320Z"/></svg>
  </div>
  <div class="hero-circle hc1"></div>
  <div class="hero-circle hc2"></div>
  <div class="hero-content">
    <span class="hero-tag">✦ Nova Coleção Verão 2026</span>
    <h1>Sinta a vibe,<br><em>viva o estilo.</em></h1>
    <p>Acessórios praianos criados com delicadeza e alma — para quem carrega o oceano dentro de si.</p>
    <div class="hero-btns">
      <a class="btn-primary" href="catalogo.php">Explorar coleção</a>
      <a class="btn-outline"  href="#historia">Nossa história</a>
    </div>
  </div>
  <div class="scroll-hint"><div class="scroll-line"></div>scroll</div>
</section>

<div class="strip">
  <span>Frete grátis acima de R$99,90</span>
  <span>Parcelamento em até 12x</span>
  <span>Garantia de 30 dias</span>
</div>

<?php if (!empty($mais_vendidos)): ?>
<!-- MAIS VENDIDOS -->
<section>
  <div class="categories-header">
    <p class="section-label">Escolha da galera</p>
    <h2 class="section-title">Mais <em>vendidos</em></h2>
  </div>
  <div class="mv-grid">
    <?php foreach ($mais_vendidos as $i => $p): ?>
    <div class="mv-card fade-in" data-id="<?= $p['id'] ?>">
      <?php if (!empty($p['qtd_vendida'])): ?><span class="mv-badge">Mais vendido</span><?php endif; ?>
      <a href="produto.php?id=<?= $p['id'] ?>" style="display:block;color:inherit">
        <div class="mv-img">
          <?php if (!empty($p['imagem'])): ?><img src="uploads/produtos/<?= htmlspecialchars($p['imagem']) ?>" alt="<?= htmlspecialchars($p['nome']) ?>"/><?php else: ?><?= IMG_PLACEHOLDER ?><?php endif; ?>
        </div>
      </a>
      <div class="mv-info">
        <div class="mv-cat"><?= htmlspecialchars($p['categoria']) ?></div>
        <a href="produto.php?id=<?= $p['id'] ?>" style="color:inherit"><div class="mv-nome"><?= htmlspecialchars($p['nome']) ?></div></a>
        <div class="mv-row">
          <span class="mv-preco">R$ <?= number_format((float)$p['preco'],2,',','.') ?></span>
          <button class="mv-add" onclick="addToCartHome(<?= $p['id'] ?>, this)">+ Sacola</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- BRAND STORY -->
<div class="brand-story" id="historia">
  <div class="story-visual">
    <div class="story-wave-deco"><svg viewBox="0 0 400 600" preserveAspectRatio="none"><path fill="white" d="M0,150 C100,100 200,200 300,150 C350,125 380,175 400,150 L400,600 L0,600Z"/><path fill="white" d="M0,280 C80,240 160,320 240,280 C320,240 370,290 400,270 L400,600 L0,600Z" opacity=".5"/><path fill="white" d="M0,400 C60,370 130,430 200,400 C270,370 330,410 400,390 L400,600 L0,600Z" opacity=".3"/></svg></div>
    <div class="story-emblem">W</div>
    <div class="story-emblem-overlay"><div class="story-ring"><div class="story-ring-inner"></div></div></div>
    <img class="story-photo" src="equipe-wave.jpg" srcset="equipe-wave-800.jpg 800w, equipe-wave.jpg 1280w" sizes="(max-width:900px) 92vw, 45vw" width="1280" height="720" loading="lazy" decoding="async" alt="Equipe da Wave Acessórios reunida em frente ao telão com o logo e o slogan da marca"/>
  </div>
  <div class="story-text">
    <p class="section-label">Nossa história</p>
    <h2 class="section-title">Nascida <em>do oceano</em></h2>
    <p>A Wave nasceu do amor pelo mar — da areia quente, do sal no cabelo e da liberdade que só a praia oferece. Cada peça é criada com cuidado artesanal para ser sua companheira de todas as marés.</p>
    <div class="story-stats">
      <div class="stat-item"><span class="stat-num">100%</span><span class="stat-lbl">Artesanal</span></div>
      <div class="stat-item"><span class="stat-num">♥</span><span class="stat-lbl">Com amor</span></div>
    </div>
    <a class="btn-light" href="#">Conheça a Wave →</a>
  </div>
</div>

<div class="features">
  <div class="feature-item"><div class="feat-icon"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="14" height="11"/><path d="M15 10h4l3 3v4h-7z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg></div><h4 class="feat-title">Entrega Rápida</h4><p class="feat-desc">Frete grátis em compras acima de R$99,90 para todo o Brasil.</p></div>
  <div class="feature-item"><div class="feat-icon"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 12V3.5a1.5 1.5 0 0 1 3 0V8"/><path d="M9 8V2.5a1.5 1.5 0 0 1 3 0V8"/><path d="M12 8V4.5a1.5 1.5 0 0 1 3 0V12"/><path d="M15 8.5a1.5 1.5 0 0 1 3 0V13a7 7 0 0 1-7 7h-2a6 6 0 0 1-5-3l-2.5-4.5a1.4 1.4 0 0 1 2.5-1.3L6 13"/></svg></div><h4 class="feat-title">100% Artesanal</h4><p class="feat-desc">Cada peça feita à mão com materiais selecionados.</p></div>
  <div class="feature-item"><div class="feat-icon"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg></div><h4 class="feat-title">Troca Fácil</h4><p class="feat-desc">30 dias para troca ou devolução sem burocracia.</p></div>
  <div class="feature-item"><div class="feat-icon"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="5" width="22" height="15" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div><h4 class="feat-title">Parcele Sem Juros</h4><p class="feat-desc">Em até 6x no cartão ou desconto via PIX.</p></div>
</div>

<section class="newsletter">
  <p class="section-label">Fique por dentro</p>
  <h2 class="section-title">Novidades direto para <em>você</em></h2>
  <p>Cadastre-se e ganhe 10% off na primeira compra, além de lançamentos em primeira mão.</p>
  <div class="nl-form">
    <input type="email" placeholder="Seu melhor e-mail"/>
    <button class="btn-primary">Quero desconto ✦</button>
  </div>
</section>

<footer id="contato">
  <div class="footer-top">
    <div class="footer-brand">
      <img class="footer-logo" src="logo-removebg-preview.png" alt="Wave Acessórios"/>
      <p>Acessórios praianos feitos com alma, para quem ama o mar e vive com leveza.</p>
      <div class="footer-social">
        <div class="social-btn" style="font-size:.62rem;font-weight:700;letter-spacing:.03em">IG</div>
        <div class="social-btn" style="font-size:.62rem;font-weight:700;letter-spacing:.03em">PI</div>
        <div class="social-btn" style="font-size:.62rem;font-weight:700;letter-spacing:.03em">WA</div>
      </div>
    </div>
    <div class="footer-col"><h5>Loja</h5><ul><li>Novidades</li><li>Colares</li><li>Pulseiras</li><li>Brincos</li><li>Kits Presente</li></ul></div>
    <div class="footer-col"><h5>Informações</h5><ul><li>Nossa História</li><li>Entregas e Prazos</li><li>Trocas e Devoluções</li><li>Formas de Pagamento</li></ul></div>
    <div class="footer-col"><h5>Atendimento</h5><ul><li>WhatsApp</li><li>E-mail</li><li>Seg–Sex, 9h às 18h</li></ul></div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 Wave Acessórios — Todos os direitos reservados.</span>
    <span class="footer-slogan">Sinta a vibe, viva o estilo. ✦ 2026</span>
  </div>
</footer>

<script>
  // ── Dados do PHP ──
  // All products for search
  const produtosAll = <?php
    $all = [];
    $ra = $conn->query("SELECT id, nome, preco, categoria, imagem FROM produtos WHERE ativo=1 ORDER BY id DESC");
    if($ra) while($rw=$ra->fetch_assoc()) $all[]=$rw;
    echo json_encode($all);
  ?>;

  // ── NAV scroll ──
  window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 60);
  });

  // ── Fade-in ──
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if(e.isIntersecting) e.target.classList.add('visible'); });
  }, {threshold:.12});
  document.querySelectorAll('.fade-in').forEach(el => obs.observe(el));

  // ── Dropdown perfil ──
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

  // ── Menu mobile (hamburguer) ──
  function toggleMobileNav() {
    const panel = document.getElementById('mobileNavPanel');
    const btn = document.getElementById('navHam');
    if (!panel || !btn) return;
    const open = panel.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  document.addEventListener('click', e => {
    const panel = document.getElementById('mobileNavPanel');
    const btn = document.getElementById('navHam');
    if (panel && btn && panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) {
      panel.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
  document.querySelectorAll('.mobile-nav-panel a').forEach(a => a.addEventListener('click', () => {
    document.getElementById('mobileNavPanel')?.classList.remove('open');
    document.getElementById('navHam')?.setAttribute('aria-expanded', 'false');
  }));

  // ── Search ──
  function openSearch(){document.getElementById('searchOverlay').classList.add('ab');setTimeout(()=>document.getElementById('searchInput')?.focus(),80);}
  function closeSearch(){document.getElementById('searchOverlay').classList.remove('ab');}
  document.addEventListener('keydown', e => {
    if(e.key==='Escape'){closeSearch();fecharModal();}
    if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();openSearch();}
  });
  const IMG_PLACEHOLDER = '<?= IMG_PLACEHOLDER ?>';
  function doSearch(q){
    const res=document.getElementById('searchResults');
    if(!q.trim()){res.innerHTML='<div class="search-empty">Comece a digitar para buscar produtos…</div>';return;}
    const found=produtosAll.filter(p=>p.nome.toLowerCase().includes(q.toLowerCase())||p.categoria.toLowerCase().includes(q.toLowerCase())).slice(0,7);
    if(!found.length){res.innerHTML=`<div class="search-empty">Nenhum produto encontrado para "<strong>${q}</strong>"</div>`;return;}
    res.innerHTML=found.map(p=>`<a class="search-item" href="produto.php?id=${p.id}">
      <div class="search-item-img">${p.imagem?`<img src="uploads/produtos/${p.imagem}" alt=""/>`:IMG_PLACEHOLDER}</div>
      <div class="search-item-info"><h5>${p.nome}</h5><span>${p.categoria}</span></div>
      <span class="search-item-price">R$ ${parseFloat(p.preco).toFixed(2).replace('.',',')}</span>
    </a>`).join('');
  }

  // ── Modal ──
  function abrirModal(t) {
    document.getElementById('modal')?.classList.add('ab');
    document.getElementById('pDrop')?.classList.remove('ab');
    document.getElementById('pTrigger')?.classList.remove('ab');
    tab(t);
  }
  function fecharModal() { document.getElementById('modal')?.classList.remove('ab'); }
  function tab(t) {
    document.querySelectorAll('.tc').forEach(c => c.classList.remove('on'));
    document.querySelectorAll('.mtab').forEach(b => b.classList.remove('on'));
    document.getElementById('tc-'+t)?.classList.add('on');
    document.getElementById('mt-'+t)?.classList.add('on');
  }

  // ── Senha ──
  function vis(id) {
    const i = document.getElementById(id);
    if(!i) return;
    const showing = i.type === 'password';
    i.type = showing ? 'text' : 'password';
    const btnId = {sA:'eyeA', sN:'eyeN', sC:'eyeC'}[id];
    const btn = document.getElementById(btnId);
    if(!btn) return;
    btn.title = showing ? 'Ocultar senha' : 'Mostrar senha';
    btn.innerHTML = showing
      ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.4 20.4 0 0 1 4.22-5.22M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
      : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
  }
  function medirSenha(v) {
    const f=document.getElementById('pf'), h=document.getElementById('ph');
    if(!f) return;
    let s=0;
    if(v.length>=6)s++; if(v.length>=10)s++; if(/[A-Z]/.test(v))s++;
    if(/[0-9]/.test(v))s++; if(/[^a-zA-Z0-9]/.test(v))s++;
    const pct=[0,20,40,65,85,100][s];
    const cors=['#e2e8f0','#dc2626','#f97316','#eab308','#16a34a','#0A5489'];
    const msgs=['','Muito fraca','Fraca','Média','Forte','Muito forte'];
    f.style.width=pct+'%'; f.style.background=cors[s];
    h.textContent=msgs[s]||''; h.style.color=cors[s];
  }
  function valSenha() {
    const a=document.getElementById('sA')?.value||'';
    const n=document.getElementById('sN')?.value||'';
    const c=document.getElementById('sC')?.value||'';
    const e=document.getElementById('eS');
    if(!a){e.textContent='Digite a senha atual.';e.style.display='flex';return false;}
    if(n.length<6){e.textContent='Mínimo 6 caracteres.';e.style.display='flex';return false;}
    if(n!==c){e.textContent='As senhas não coincidem.';e.style.display='flex';return false;}
    e.style.display='none'; return true;
  }

  // ── Toast ──
  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.opacity='1'; t.style.transform='translateY(0)';
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(10px)'; }, 2800);
  }

  // Cart badge
  function getCart(){ try{ return JSON.parse(localStorage.getItem('wave_cart')||'[]'); }catch(e){ return []; } }
  function saveCart(c){ try{ localStorage.setItem('wave_cart', JSON.stringify(c)); }catch(e){} }
  function updateCartBadge(){ var n=getCart().reduce(function(s,i){return s+i.qty;},0); var b=document.getElementById('cartBadge'); if(b){ b.textContent=n; b.classList.toggle('show',n>0); } }
  function addToCartHome(id, btn){
    const p = produtosAll.find(x=>x.id==id);
    if(!p) return;
    const cart = getCart();
    const ix = cart.findIndex(i=>i.id==id);
    if(ix>=0) cart[ix].qty++;
    else cart.push({id:p.id,nome:p.nome,preco:parseFloat(p.preco),imagem:p.imagem||'',categoria:p.categoria,qty:1});
    saveCart(cart); updateCartBadge();
    showToast(p.nome+' adicionado à sacola!');
  }
  updateCartBadge();
</script>
</body>
</html>