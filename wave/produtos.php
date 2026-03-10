<?php
session_start();
require_once 'conexão.php';

if (isset($_GET['sair'])) { session_destroy(); header("Location: index.php"); exit(); }

// ── Ícones globais ──
$cat_icons = ['Colares'=>'🐚','Pulseiras'=>'🌊','Brincos'=>'🌺','Anéis'=>'💍','Kits'=>'🎁'];

// ── Garante colunas extras (compatível MySQL 5.x/8.x) ──
$_cols=[];$_rc=@$conn->query("SHOW COLUMNS FROM produtos");if($_rc)while($c=$_rc->fetch_assoc())$_cols[]=$c["Field"];if(!in_array("imagem",$_cols))@$conn->query("ALTER TABLE produtos ADD COLUMN imagem VARCHAR(255) DEFAULT NULL");
if(!in_array("destaque",$_cols))@$conn->query("ALTER TABLE produtos ADD COLUMN destaque TINYINT(1) DEFAULT 0");

// ── Buscar produto ──
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: catalogo.php"); exit(); }
$res = $conn->query("SELECT * FROM produtos WHERE id=$id AND ativo=1 LIMIT 1");
if (!$res || $res->num_rows === 0) { header("Location: catalogo.php"); exit(); }
$produto = $res->fetch_assoc();

// ── Sessão ──
$logado   = isset($_SESSION['usuario_id']);
$nivel    = $logado ? ($_SESSION['nivel'] ?? 'usuario') : '';
$nome_s   = $logado ? htmlspecialchars($_SESSION['nome'] ?? '') : '';
$primeiro = $nome_s ? strtok($nome_s, ' ') : '';
$iniciais = '';
if ($logado && $nome_s) { $p=explode(' ',$nome_s); $iniciais=strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):'')); }
$udb = null;
if ($logado) { $uid=(int)$_SESSION['usuario_id']; $r=$conn->query("SELECT nome,email FROM usuarios WHERE id=$uid LIMIT 1"); if($r&&$r->num_rows>0) $udb=$r->fetch_assoc(); }
$eh_admin = $nivel === 'admin';

// ── Avaliações – garante tabela e processa POST ──
$conn->query("CREATE TABLE IF NOT EXISTS avaliacoes (id INT AUTO_INCREMENT PRIMARY KEY, produto_id INT NOT NULL, usuario_id INT NOT NULL, nota TINYINT NOT NULL DEFAULT 5, titulo VARCHAR(150) DEFAULT '', texto TEXT NOT NULL, criado_em DATETIME DEFAULT CURRENT_TIMESTAMP, KEY idx_prod(produto_id))");
$msg_av = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='avaliar' && $logado) {
    $uid_av = (int)$_SESSION['usuario_id'];
    $nota   = min(5,max(1,(int)($_POST['nota']??5)));
    $titulo = $conn->real_escape_string(trim($_POST['titulo']??''));
    $texto  = $conn->real_escape_string(trim($_POST['texto']??''));
    $chk    = $conn->query("SELECT id FROM avaliacoes WHERE produto_id=$id AND usuario_id=$uid_av LIMIT 1");
    if ($chk && $chk->num_rows>0) $msg_av='erro|Você já avaliou este produto.';
    elseif (strlen(trim($_POST['texto']??''))<5) $msg_av='erro|Comentário muito curto (mín. 5 caracteres).';
    else { $conn->query("INSERT INTO avaliacoes (produto_id,usuario_id,nota,titulo,texto,criado_em) VALUES ($id,$uid_av,$nota,'$titulo','$texto',NOW())"); $msg_av='ok|Avaliação enviada! Obrigada 💙'; }
}
$avaliacoes=[]; $media_nota=0; $total_avs=0; $dist=[5=>0,4=>0,3=>0,2=>0,1=>0];
$ra=$conn->query("SELECT a.*,u.nome as uname FROM avaliacoes a JOIN usuarios u ON a.usuario_id=u.id WHERE a.produto_id=$id ORDER BY a.criado_em DESC");
if($ra){ while($row=$ra->fetch_assoc()){ $avaliacoes[]=$row; $dist[(int)$row['nota']]++; } }
$total_avs=count($avaliacoes);
$media_nota=$total_avs>0?array_sum(array_column($avaliacoes,'nota'))/$total_avs:0;
[$mt_av,$mv_av]=$msg_av?explode('|',$msg_av,2):['',''];

// ── Relacionados ──
$relacionados=[];
$cat_esc=$conn->real_escape_string($produto['categoria']);
$rr=$conn->query("SELECT id,nome,preco,categoria,imagem FROM produtos WHERE ativo=1 AND categoria='$cat_esc' AND id!=$id ORDER BY id DESC LIMIT 4");
if($rr) while($row=$rr->fetch_assoc()) $relacionados[]=$row;

// ── Vars de apoio ──
$img_src  = !empty($produto['imagem']) ? 'uploads/produtos/'.htmlspecialchars($produto['imagem']) : null;
$cat_icon = $cat_icons[$produto['categoria']] ?? '💎';
$est      = (int)($produto['estoque']??0);

function S($n,$max=5){ $h='<span class="stars">'; for($i=1;$i<=$max;$i++) $h.='<span class="'.($i<=$n?'s-on':'s-off').'">★</span>'; return $h.'</span>'; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?=htmlspecialchars($produto['nome'])?> — Wave Acessórios</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root{--blue:#0A5489;--blue-dk:#073d66;--blue-lt:#1a7abf;--cream:#FFF3E7;--sand:#F5DEC8;--gold:#C8963E;--white:#FEFCF9;--text:#1a2e3b;--muted:#7a8d99;--font-display:'Cormorant Garamond',Georgia,serif;--font-body:'DM Sans',sans-serif;--ease:cubic-bezier(.22,.61,.36,1)}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:var(--font-body);background:var(--white);color:var(--text);overflow-x:hidden}
    a{text-decoration:none;color:inherit}
    img{display:block;max-width:100%}

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
    .cart-badge{background:var(--gold);color:#fff;border-radius:50%;width:18px;height:18px;font-size:.6rem;font-weight:700;display:none;align-items:center;justify-content:center;position:absolute;top:-5px;right:-5px;transition:transform .2s}
    .cart-badge.show{display:flex}
    .cart-badge.pop{transform:scale(1.45)}
    .nav-login-btn{font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:var(--blue);border:1.5px solid var(--blue);padding:.36rem .85rem;border-radius:2rem;font-weight:500;transition:all .25s;white-space:nowrap}
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
    .pd-email{font-size:.67rem;color:rgba(255,255,255,.6);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .pd-menu{padding:.4rem}
    .pd-item{display:flex;align-items:center;gap:.6rem;padding:.5rem .65rem;border-radius:.65rem;font-size:.79rem;font-weight:500;color:var(--text);cursor:pointer;transition:background .15s;border:none;background:none;width:100%;text-align:left;font-family:var(--font-body);text-decoration:none}
    .pd-item:hover{background:var(--cream)}
    .pd-ic{width:26px;height:26px;border-radius:.4rem;display:flex;align-items:center;justify-content:center;font-size:.82rem}
    .ib{background:#dbeafe}.ip{background:#ede9fe}.ir{background:#fee2e2}
    .pd-sep{height:1px;background:rgba(10,84,137,.08);margin:.22rem .65rem}
    .pd-item.sair{color:#dc2626}.pd-item.sair:hover{background:#fef2f2}
    .pd-adm-label{margin:.3rem .5rem .1rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:.6rem;padding:.45rem .7rem;display:flex;align-items:center;gap:.45rem}
    .pd-adm-label span.t{font-size:.7rem;font-weight:600;color:#6d28d9;display:block}
    .pd-adm-label span.s{font-size:.6rem;color:var(--muted)}

    .search-overlay{display:none;position:fixed;inset:0;background:rgba(7,61,102,.6);backdrop-filter:blur(8px);z-index:200;align-items:flex-start;justify-content:center;padding-top:5rem}
    .search-overlay.ab{display:flex}
    .search-box{background:var(--white);border-radius:1.4rem;width:100%;max-width:600px;overflow:hidden;box-shadow:0 24px 80px rgba(7,61,102,.25);animation:sIn .28s var(--ease)}
    @keyframes sIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:translateY(0)}}
    .s-iw{display:flex;align-items:center;gap:.8rem;padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.1)}
    .s-iw input{flex:1;border:none;outline:none;font-family:var(--font-display);font-size:1.35rem;color:var(--text);background:none}
    .s-iw input::placeholder{color:var(--muted)}
    .s-close{background:rgba(10,84,137,.08);border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:.82rem;color:var(--blue)}
    .s-results{max-height:360px;overflow-y:auto;padding:.5rem}
    .s-item{display:flex;align-items:center;gap:.9rem;padding:.7rem .85rem;border-radius:.75rem;transition:background .15s;text-decoration:none;color:var(--text)}
    .s-item:hover{background:var(--cream)}
    .s-img{width:48px;height:48px;border-radius:.55rem;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;overflow:hidden}
    .s-img img{width:100%;height:100%;object-fit:cover}
    .s-info h5{font-family:var(--font-display);font-size:.95rem;font-weight:600;color:var(--text)}
    .s-info span{font-size:.72rem;color:var(--muted)}
    .s-price{margin-left:auto;font-weight:700;color:var(--blue);font-size:.9rem;white-space:nowrap}
    .s-empty{text-align:center;padding:2rem;color:var(--muted);font-size:.88rem}
    .s-footer{padding:.55rem 1.2rem;border-top:1px solid rgba(10,84,137,.08);font-size:.72rem;color:var(--muted);text-align:center}

    .admin-bar{background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;padding:.7rem 5%;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;font-size:.8rem;margin-top:72px}
    .admin-bar a{background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);padding:.35rem .85rem;border-radius:2rem;font-size:.72rem;font-weight:600;transition:background .2s;margin-left:.45rem}
    .admin-bar a:hover{background:rgba(255,255,255,.35)}

    .breadcrumb{padding:5.5rem 5% 0;display:flex;align-items:center;gap:.45rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);flex-wrap:wrap}
    .breadcrumb a{color:var(--blue);transition:color .2s}.breadcrumb a:hover{color:var(--gold)}
    .sep{opacity:.4}.cur{color:var(--text);opacity:.7}

    .product-main{display:grid;grid-template-columns:1fr 1fr;gap:4rem;padding:2rem 5% 5rem;max-width:1300px;margin:0 auto;align-items:start}
    .gallery{display:flex;flex-direction:column;gap:1rem;position:sticky;top:5.5rem}
    .g-main{border-radius:1.4rem;overflow:hidden;aspect-ratio:1;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;position:relative}
    .g-main img{width:100%;height:100%;object-fit:cover;transition:transform .5s var(--ease)}
    .g-main:hover img{transform:scale(1.04)}
    .g-placeholder{font-size:7rem;opacity:.32;user-select:none}
    .g-badge{position:absolute;top:1.1rem;left:1.1rem;color:var(--cream);font-size:.66rem;letter-spacing:.1em;text-transform:uppercase;padding:.28rem .75rem;border-radius:2rem;pointer-events:none}
    .g-badge.novo{background:var(--blue)}.g-badge.dest{background:#7c3aed}.g-badge.esg{background:#9ca3af}
    .g-thumbs{display:flex;gap:.65rem;flex-wrap:wrap}
    .g-thumb{width:60px;height:60px;border-radius:.65rem;overflow:hidden;border:2.5px solid transparent;cursor:pointer;transition:border-color .2s;background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
    .g-thumb.on{border-color:var(--blue)}.g-thumb img{width:100%;height:100%;object-fit:cover}

    .prod-info{display:flex;flex-direction:column;gap:1.1rem}
    .prod-cat{font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);font-weight:600}
    .prod-name{font-family:var(--font-display);font-size:clamp(1.9rem,3.8vw,2.9rem);font-weight:600;color:var(--blue);line-height:1.06;letter-spacing:-.01em}
    .rating-row{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
    .stars .s-on{color:var(--gold)}.stars .s-off{color:#d1d5db}.stars{font-size:.95rem;letter-spacing:.04em}
    .rating-count{font-size:.78rem;color:var(--muted)}
    .price-row{display:flex;align-items:center;gap:.9rem;flex-wrap:wrap}
    .price-main{font-family:var(--font-display);font-size:2.4rem;font-weight:700;color:var(--blue)}
    .price-pix{font-size:.82rem;color:#16a34a;font-weight:600;background:#f0fdf4;border:1px solid #bbf7d0;padding:.18rem .65rem;border-radius:2rem}
    .divider{height:1px;background:rgba(10,84,137,.1)}
    .prod-desc{font-size:.93rem;color:var(--muted);line-height:1.8;font-weight:300}
    .detail-list{display:flex;flex-direction:column;gap:.45rem}
    .detail-item{display:flex;align-items:center;gap:.55rem;font-size:.82rem}
    .qty-row{display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
    .qty-label{font-size:.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.1em}
    .qty-ctrl{display:flex;align-items:center;gap:.4rem;background:var(--cream);border-radius:2rem;padding:.28rem .55rem;border:1.5px solid rgba(10,84,137,.15)}
    .qty-btn{background:none;border:none;width:26px;height:26px;border-radius:50%;cursor:pointer;font-size:1rem;color:var(--blue);display:flex;align-items:center;justify-content:center;transition:background .2s}
    .qty-btn:hover{background:var(--blue);color:#fff}
    .qty-val{font-size:.9rem;font-weight:700;min-width:26px;text-align:center}
    .est-ok{color:#16a34a;font-size:.76rem;font-weight:500}.est-low{color:#f97316;font-size:.76rem;font-weight:500}.est-esg{color:#dc2626;font-size:.76rem;font-weight:500}
    .prod-actions{display:flex;gap:.8rem;flex-wrap:wrap}
    .btn-sacola{flex:1;min-width:170px;background:var(--blue);color:var(--cream);border:none;padding:.95rem 1.8rem;border-radius:3rem;font-size:.86rem;letter-spacing:.09em;text-transform:uppercase;font-weight:600;cursor:pointer;font-family:var(--font-body);transition:background .25s,transform .2s;box-shadow:0 8px 22px rgba(10,84,137,.22)}
    .btn-sacola:hover{background:var(--gold);transform:translateY(-2px)}
    .btn-sacola:disabled{background:#d1d5db;cursor:not-allowed;transform:none;box-shadow:none}
    .btn-wish{width:50px;height:50px;border-radius:50%;border:1.5px solid rgba(10,84,137,.2);background:var(--cream);color:var(--blue);font-size:1.25rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .25s;flex-shrink:0}
    .btn-wish:hover,.btn-wish.on{background:#fef2f2;border-color:#f87171;color:#dc2626}
    .share-row{display:flex;align-items:center;gap:.6rem;font-size:.76rem;color:var(--muted)}
    .share-btn{width:30px;height:30px;border-radius:50%;border:1px solid rgba(10,84,137,.15);background:var(--white);display:flex;align-items:center;justify-content:center;font-size:.88rem;cursor:pointer;transition:all .2s}
    .share-btn:hover{background:var(--blue);color:#fff;border-color:var(--blue)}
    .guarantees{background:var(--cream);border-radius:1rem;padding:.9rem 1.1rem;display:flex;gap:1rem;flex-wrap:wrap}
    .g-item{display:flex;align-items:center;gap:.4rem;font-size:.76rem;font-weight:500}

    .tabs-section{padding:0 5% 5rem;max-width:1300px;margin:0 auto}
    .tab-nav{display:flex;border-bottom:2px solid rgba(10,84,137,.1);margin-bottom:2.2rem;overflow-x:auto}
    .tab-btn{padding:.9rem 1.5rem;font-size:.8rem;font-weight:600;color:var(--muted);border-bottom:2.5px solid transparent;margin-bottom:-2px;cursor:pointer;transition:all .2s;background:none;border-top:none;border-left:none;border-right:none;font-family:var(--font-body);white-space:nowrap}
    .tab-btn.on{color:var(--blue);border-bottom-color:var(--blue)}
    .tab-btn:hover:not(.on){color:var(--text)}
    .tab-panel{display:none}.tab-panel.on{display:block}
    .desc-body{font-size:.93rem;color:var(--muted);line-height:1.9;font-weight:300;max-width:740px}
    .desc-body p{margin-bottom:.8rem}
    .spec-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:.9rem}
    .spec-item{background:var(--cream);border-radius:.8rem;padding:.9rem 1.1rem}
    .spec-lbl{font-size:.65rem;letter-spacing:.13em;text-transform:uppercase;color:var(--muted);margin-bottom:.22rem;font-weight:600}
    .spec-val{font-size:.92rem;font-weight:600;color:var(--text)}

    .rev-summary{display:flex;gap:2.5rem;align-items:center;margin-bottom:2rem;flex-wrap:wrap}
    .score-num{font-family:var(--font-display);font-size:4.2rem;font-weight:700;color:var(--blue);line-height:1;margin-bottom:.2rem}
    .score-sub{font-size:.75rem;color:var(--muted);margin-top:.25rem}
    .dist-bars{display:flex;flex-direction:column;gap:.4rem;flex:1;max-width:320px;min-width:160px}
    .dist-row{display:flex;align-items:center;gap:.6rem;font-size:.75rem;color:var(--muted)}
    .dist-bg{flex:1;height:6px;background:rgba(10,84,137,.08);border-radius:3px;overflow:hidden}
    .dist-fill{height:100%;background:var(--gold);border-radius:3px;transition:width .7s var(--ease)}
    .dist-n{width:9px;text-align:right;font-weight:600;color:var(--text)}
    .rev-list{display:flex;flex-direction:column;gap:1.1rem;margin-bottom:1.8rem}
    .rev-card{background:var(--cream);border-radius:1rem;padding:1.1rem 1.3rem}
    .rev-top{display:flex;align-items:center;gap:.8rem;margin-bottom:.55rem;flex-wrap:wrap}
    .rev-av{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue-dk),var(--blue-lt));color:#fff;font-size:.66rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .rev-name{font-weight:600;font-size:.84rem}
    .rev-date{font-size:.68rem;color:var(--muted);margin-left:auto}
    .rev-title{font-weight:600;font-size:.86rem;margin-bottom:.22rem}
    .rev-text{font-size:.82rem;color:var(--muted);line-height:1.6}
    .rev-form{background:var(--white);border:1.5px solid rgba(10,84,137,.12);border-radius:1.2rem;padding:1.5rem}
    .rev-form h4{font-family:var(--font-display);font-size:1.35rem;color:var(--blue);margin-bottom:.9rem}
    .star-picker{display:flex;gap:.3rem;cursor:pointer;margin-bottom:.35rem}
    .star-picker span{font-size:1.75rem;color:#d1d5db;transition:color .15s;cursor:pointer;user-select:none}
    .star-picker span.on{color:var(--gold)}
    .rf-g{display:flex;flex-direction:column;gap:.25rem;margin-bottom:.85rem}
    .rf-g label{font-size:.66rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
    .rf-inp{border:1.5px solid rgba(10,84,137,.15);border-radius:.65rem;padding:.6rem .85rem;font-family:var(--font-body);font-size:.87rem;color:var(--text);background:var(--cream);outline:none;transition:border-color .2s;width:100%}
    .rf-inp:focus{border-color:var(--blue);background:var(--white)}
    textarea.rf-inp{resize:vertical;min-height:82px}
    .btn-rev{background:var(--blue);color:var(--cream);border:none;padding:.68rem 1.7rem;border-radius:2rem;font-family:var(--font-body);font-size:.84rem;font-weight:600;cursor:pointer;transition:background .25s}
    .btn-rev:hover{background:var(--gold)}
    .malert{border-radius:.65rem;padding:.6rem .85rem;font-size:.79rem;font-weight:500;margin-bottom:.85rem;display:flex;align-items:center;gap:.4rem}
    .malert.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
    .malert.er{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626}
    .no-login-note{text-align:center;padding:1.3rem;font-size:.86rem;color:var(--muted)}
    .no-login-note a{color:var(--blue);font-weight:600}
    .no-reviews{text-align:center;padding:1.8rem;color:var(--muted);font-size:.86rem}

    .related-sec{padding:0 5% 5rem;max-width:1300px;margin:0 auto}
    .related-sec h3{font-family:var(--font-display);font-size:1.85rem;color:var(--blue);margin-bottom:1.3rem}
    .related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(195px,1fr));gap:1.3rem}
    .rel-card{border-radius:1.05rem;overflow:hidden;background:var(--white);box-shadow:0 4px 18px rgba(10,84,137,.07);transition:transform .38s var(--ease),box-shadow .38s;display:block;color:inherit}
    .rel-card:hover{transform:translateY(-5px);box-shadow:0 14px 34px rgba(10,84,137,.14)}
    .rel-img{aspect-ratio:1;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:3rem;overflow:hidden}
    .rel-img img{width:100%;height:100%;object-fit:cover}
    .rel-info{padding:.85rem .95rem 1rem}
    .rel-info h5{font-family:var(--font-display);font-size:.98rem;font-weight:600;margin-bottom:.12rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .rel-sub{font-size:.7rem;color:var(--muted)}
    .rel-price{font-size:.92rem;font-weight:700;color:var(--blue);margin-top:.3rem;display:block}

    #toast{position:fixed;bottom:1.5rem;right:1.5rem;background:var(--blue);color:#fff;padding:.6rem 1.1rem;border-radius:2rem;font-size:.78rem;font-weight:500;z-index:999;opacity:0;transform:translateY(10px);transition:all .28s;pointer-events:none;max-width:280px}

    @media(max-width:900px){.product-main{grid-template-columns:1fr;gap:2rem}.gallery{position:static}.nav-links{display:none}.nav-nome{display:none}}
    @media(max-width:600px){.product-main{padding:1.5rem 5% 3rem}.tabs-section,.related-sec{padding:0 5% 3rem}.price-main{font-size:2rem}}
  </style>
</head>
<body>

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
    <button class="nav-sb" onclick="toggleFavNav(this)">♡</button>
    <a class="nav-cart" href="carrinho.php">
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
          <div class="pd-adm-label"><span>⚙️</span><div><span class="t">Admin</span><span class="s">Painel de gestão</span></div></div>
          <a href="Dashboard.php" class="pd-item"><div class="pd-ic ip">🏠</div>Dashboard</a>
          <a href="catalogo.php#destaques" class="pd-item"><div class="pd-ic" style="background:#ede9fe">⭐</div>Destaques</a>
          <a href="cadastro_produto.php?id=<?=$id?>" class="pd-item"><div class="pd-ic ib">✏️</div>Editar produto</a>
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
<div class="search-overlay" id="sOverlay" onclick="if(event.target===this)closeSearch()">
  <div class="search-box">
    <div class="s-iw">
      <span style="font-size:1.05rem;opacity:.4">🔍</span>
      <input type="text" id="sInput" placeholder="Buscar acessórios…" oninput="doSearch(this.value)" autocomplete="off"/>
      <button class="s-close" onclick="closeSearch()">✕</button>
    </div>
    <div class="s-results" id="sResults"><div class="s-empty">Comece a digitar…</div></div>
    <div class="s-footer">Esc para fechar</div>
  </div>
</div>

<?php if($eh_admin):?>
<div class="admin-bar">
  <span>⚙️ <strong>Admin</strong> — <?=htmlspecialchars($produto['nome'])?></span>
  <div><a href="cadastro_produto.php?id=<?=$id?>">✏️ Editar</a><a href="catalogo.php">← Catálogo</a></div>
</div>
<?php endif;?>

<div class="breadcrumb" <?=$eh_admin?'style="padding-top:1.5rem"':''?>>
  <a href="index.php">Início</a><span class="sep">›</span>
  <a href="catalogo.php">Catálogo</a><span class="sep">›</span>
  <a href="catalogo.php?categoria=<?=urlencode($produto['categoria'])?>"><?=htmlspecialchars($produto['categoria'])?></a><span class="sep">›</span>
  <span class="cur"><?=htmlspecialchars($produto['nome'])?></span>
</div>

<div class="product-main">
  <!-- Galeria -->
  <div class="gallery">
    <div class="g-main">
      <?php if($img_src):?>
        <img src="<?=$img_src?>" alt="<?=htmlspecialchars($produto['nome'])?>"/>
      <?php else:?>
        <div class="g-placeholder"><?=$cat_icon?></div>
      <?php endif;?>
      <?php if($est===0):?><span class="g-badge esg">Esgotado</span>
      <?php elseif(!empty($produto['destaque'])):?><span class="g-badge dest">⭐ Destaque</span>
      <?php else:?><span class="g-badge novo">Novo</span>
      <?php endif;?>
    </div>
    <div class="g-thumbs">
      <div class="g-thumb on">
        <?php if($img_src):?><img src="<?=$img_src?>" alt=""/><?php else:?><?=$cat_icon?><?php endif;?>
      </div>
    </div>
  </div>

  <!-- Info -->
  <div class="prod-info">
    <div class="prod-cat"><?=htmlspecialchars($produto['categoria'])?></div>
    <h1 class="prod-name"><?=htmlspecialchars($produto['nome'])?></h1>

    <div class="rating-row">
      <?=S(round($media_nota))?>
      <span class="rating-count"><?=$total_avs>0?number_format($media_nota,1).' ('.$total_avs.' avaliação'.($total_avs>1?'s':'').')':'Sem avaliações ainda'?></span>
    </div>

    <div class="price-row">
      <span class="price-main">R$ <?=number_format((float)$produto['preco'],2,',','.')?></span>
      <span class="price-pix">💰 R$ <?=number_format((float)$produto['preco']*.95,2,',','.')?> no PIX</span>
    </div>

    <div class="divider"></div>

    <p class="prod-desc"><?=nl2br(htmlspecialchars(!empty($produto['descricao'])?$produto['descricao']:'Peça artesanal exclusiva criada com muito carinho pela equipe Wave.'))?></p>

    <div class="detail-list">
      <div class="detail-item"><span>🎨</span><span>Material artesanal selecionado</span></div>
      <div class="detail-item"><span>🌊</span><span>Coleção Verão <?=date('Y')?></span></div>
      <div class="detail-item"><span>📦</span><span>Embalagem exclusiva Wave</span></div>
    </div>

    <div class="divider"></div>

    <div class="qty-row">
      <span class="qty-label">Qtd</span>
      <div class="qty-ctrl">
        <button class="qty-btn" onclick="changeQty(-1)">−</button>
        <span class="qty-val" id="qtyVal">1</span>
        <button class="qty-btn" onclick="changeQty(1)">+</button>
      </div>
      <span class="<?=$est===0?'est-esg':($est<=5?'est-low':'est-ok')?>"><?=$est===0?'✗ Esgotado':($est<=5?'⚠ '.$est.' restante'.($est>1?'s':''):'✓ Em estoque')?></span>
    </div>

    <div class="prod-actions">
      <button class="btn-sacola" id="btnSacola" <?=$est===0?'disabled':''?> onclick="addToCart()">
        <?=$est===0?'Produto esgotado':'🛍 Adicionar à sacola'?>
      </button>
      <button class="btn-wish" id="wishBtn" onclick="toggleFav()">♡</button>
    </div>

    <div class="guarantees">
      <div class="g-item"><span>🚢</span><span>Frete grátis +R$99</span></div>
      <div class="g-item"><span>🔄</span><span>Troca em 30 dias</span></div>
      <div class="g-item"><span>💳</span><span>6x sem juros</span></div>
      <div class="g-item"><span>🔒</span><span>Compra segura</span></div>
    </div>

    <div class="share-row">
      <span>Compartilhar:</span>
      <button class="share-btn" onclick="shareWA()">💬</button>
      <button class="share-btn" onclick="copyLink()">🔗</button>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="tabs-section" id="reviews">
  <div class="tab-nav">
    <button class="tab-btn on"  onclick="switchTab('desc',this)">Descrição</button>
    <button class="tab-btn"     onclick="switchTab('spec',this)">Especificações</button>
    <button class="tab-btn"     onclick="switchTab('revs',this)">Avaliações (<?=$total_avs?>)</button>
  </div>

  <div class="tab-panel on" id="tp-desc">
    <div class="desc-body">
      <p><?=nl2br(htmlspecialchars(!empty($produto['descricao'])?$produto['descricao']:'Peça artesanal exclusiva criada com muito carinho pela equipe Wave.'))?></p>
      <p>Ideal para usar no dia a dia ou em ocasiões especiais. Perfeita para presentear quem você ama.</p>
    </div>
  </div>

  <div class="tab-panel" id="tp-spec">
    <div class="spec-grid">
      <div class="spec-item"><div class="spec-lbl">Categoria</div><div class="spec-val"><?=htmlspecialchars($produto['categoria'])?></div></div>
      <div class="spec-item"><div class="spec-lbl">Preço</div><div class="spec-val">R$ <?=number_format((float)$produto['preco'],2,',','.')?></div></div>
      <div class="spec-item"><div class="spec-lbl">Estoque</div><div class="spec-val"><?=$est>0?$est.' un.':'Esgotado'?></div></div>
      <div class="spec-item"><div class="spec-lbl">Código</div><div class="spec-val">WAVE-<?=str_pad($produto['id'],4,'0',STR_PAD_LEFT)?></div></div>
      <div class="spec-item"><div class="spec-lbl">Fabricação</div><div class="spec-val">Brasil 🇧🇷</div></div>
      <div class="spec-item"><div class="spec-lbl">Coleção</div><div class="spec-val">Verão <?=date('Y')?></div></div>
    </div>
  </div>

  <div class="tab-panel" id="tp-revs">
    <?php if($total_avs>0):?>
    <div class="rev-summary">
      <div style="text-align:center">
        <div class="score-num"><?=number_format($media_nota,1)?></div>
        <?=S(round($media_nota))?>
        <div class="score-sub"><?=$total_avs?> avaliação<?=$total_avs>1?'s':''?></div>
      </div>
      <div class="dist-bars">
        <?php for($i=5;$i>=1;$i--): $pct=$total_avs>0?round($dist[$i]/$total_avs*100):0;?>
        <div class="dist-row">
          <span class="dist-n"><?=$i?></span>
          <span style="color:var(--gold);font-size:.78rem">★</span>
          <div class="dist-bg"><div class="dist-fill" style="width:<?=$pct?>%"></div></div>
          <span><?=$dist[$i]?></span>
        </div>
        <?php endfor;?>
      </div>
    </div>
    <div class="rev-list">
      <?php foreach($avaliacoes as $av):
        $pn=explode(' ',$av['uname']); $in=strtoupper(substr($pn[0],0,1).(isset($pn[1])?substr($pn[1],0,1):''));
        $dav=(new DateTime($av['criado_em']))->format('d/m/Y');
      ?>
      <div class="rev-card">
        <div class="rev-top">
          <div class="rev-av"><?=$in?></div>
          <div><div class="rev-name"><?=htmlspecialchars($av['uname'])?></div><?=S((int)$av['nota'])?></div>
          <span class="rev-date"><?=$dav?></span>
        </div>
        <?php if(!empty($av['titulo'])):?><div class="rev-title"><?=htmlspecialchars($av['titulo'])?></div><?php endif;?>
        <div class="rev-text"><?=nl2br(htmlspecialchars($av['texto']))?></div>
      </div>
      <?php endforeach;?>
    </div>
    <?php else:?>
    <div class="no-reviews"><div style="font-size:2.2rem;opacity:.28;margin-bottom:.6rem">💬</div><p>Nenhuma avaliação ainda. Seja o primeiro!</p></div>
    <?php endif;?>

    <?php if($logado):?>
    <div class="rev-form">
      <h4>Deixe sua avaliação</h4>
      <?php if($mt_av):?><div class="malert <?=$mt_av==='ok'?'ok':'er'?>"><?=$mt_av==='ok'?'✅':'❌'?> <?=htmlspecialchars($mv_av)?></div><?php endif;?>
      <form method="POST">
        <input type="hidden" name="action" value="avaliar"/>
        <input type="hidden" name="nota" id="notaInput" value="5"/>
        <div class="rf-g"><label>Sua nota</label>
          <div class="star-picker" id="sPicker">
            <?php for($i=1;$i<=5;$i++):?><span class="on" data-v="<?=$i?>" onclick="pickStar(<?=$i?>)" onmouseover="hoverStar(<?=$i?>)" onmouseout="resetStars()">★</span><?php endfor;?>
          </div>
        </div>
        <div class="rf-g"><label>Título (opcional)</label><input class="rf-inp" type="text" name="titulo" placeholder="Resumo da experiência"/></div>
        <div class="rf-g"><label>Comentário *</label><textarea class="rf-inp" name="texto" placeholder="O que achou do produto?" required></textarea></div>
        <button class="btn-rev" type="submit">Enviar avaliação →</button>
      </form>
    </div>
    <?php else:?>
    <div class="no-login-note"><a href="login.php">Entre na sua conta</a> para avaliar.</div>
    <?php endif;?>
  </div>
</div>

<!-- RELACIONADOS -->
<?php if(!empty($relacionados)):?>
<div class="related-sec">
  <h3>Você também pode gostar</h3>
  <div class="related-grid">
    <?php foreach($relacionados as $rel):
      $ri=!empty($rel['imagem'])?'uploads/produtos/'.htmlspecialchars($rel['imagem']):null;
      $ic=$cat_icons[$rel['categoria']]??'💎';
    ?>
    <a class="rel-card" href="produto.php?id=<?=$rel['id']?>">
      <div class="rel-img"><?php if($ri):?><img src="<?=$ri?>" alt=""/><?php else:?><?=$ic?><?php endif;?></div>
      <div class="rel-info">
        <h5><?=htmlspecialchars($rel['nome'])?></h5>
        <span class="rel-sub"><?=htmlspecialchars($rel['categoria'])?></span>
        <span class="rel-price">R$ <?=number_format((float)$rel['preco'],2,',','.')?></span>
      </div>
    </a>
    <?php endforeach;?>
  </div>
</div>
<?php endif;?>

<div id="toast"></div>

<script>
const produtosAll=<?php $all=[];$ra=$conn->query("SELECT id,nome,preco,categoria,imagem FROM produtos WHERE ativo=1 ORDER BY id DESC");if($ra)while($rw=$ra->fetch_assoc())$all[]=$rw;echo json_encode($all);?>;
const prodAtual={id:<?=$id?>,nome:<?=json_encode($produto['nome'])?>,preco:<?=(float)$produto['preco']?>,imagem:<?=json_encode($produto['imagem']??'')?>,categoria:<?=json_encode($produto['categoria'])?>};
const catIcons={Colares:'🐚',Pulseiras:'🌊',Brincos:'🌺','Anéis':'💍',Kits:'🎁'};
let qty=1, maxQty=<?=$est?>;

// Cart
function getCart(){try{return JSON.parse(localStorage.getItem('wave_cart')||'[]')}catch(e){return[]}}
function saveCart(c){try{localStorage.setItem('wave_cart',JSON.stringify(c))}catch(e){}}
function updateBadge(){const n=getCart().reduce((s,i)=>s+i.qty,0);const b=document.getElementById('cartBadge');if(b){b.textContent=n;b.classList.toggle('show',n>0);}}

function changeQty(d){qty=Math.max(1,Math.min(maxQty||99,qty+d));document.getElementById('qtyVal').textContent=qty;}

function addToCart(){
  const c=getCart(),ix=c.findIndex(i=>i.id===prodAtual.id);
  if(ix>=0)c[ix].qty+=qty; else c.push({...prodAtual,qty});
  saveCart(c);updateBadge();
  showToast('✓ '+prodAtual.nome+' adicionado à sacola!');
  const b=document.getElementById('cartBadge');
  if(b){b.classList.add('pop');setTimeout(()=>b.classList.remove('pop'),320);}
}

function toggleFav(){const b=document.getElementById('wishBtn');b.classList.toggle('on');b.textContent=b.classList.contains('on')?'♥':'♡';showToast(b.classList.contains('on')?'♥ Favorito adicionado!':'Removido dos favoritos');}
function toggleFavNav(b){b.textContent=b.textContent==='♡'?'♥':'♡';}

// Dropdown
function toggleDrop(){document.getElementById('pDrop')?.classList.toggle('ab');document.getElementById('pTrigger')?.classList.toggle('ab');}
document.addEventListener('click',e=>{const w=document.getElementById('pWrap');if(w&&!w.contains(e.target)){document.getElementById('pDrop')?.classList.remove('ab');document.getElementById('pTrigger')?.classList.remove('ab');}});

// Search
function openSearch(){document.getElementById('sOverlay').classList.add('ab');setTimeout(()=>document.getElementById('sInput')?.focus(),80);}
function closeSearch(){document.getElementById('sOverlay').classList.remove('ab');}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeSearch();if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();openSearch();}});
function doSearch(q){
  const r=document.getElementById('sResults');
  if(!q.trim()){r.innerHTML='<div class="s-empty">Comece a digitar…</div>';return;}
  const f=produtosAll.filter(p=>p.nome.toLowerCase().includes(q.toLowerCase())||p.categoria.toLowerCase().includes(q.toLowerCase())).slice(0,7);
  if(!f.length){r.innerHTML=`<div class="s-empty">Nenhum resultado para "<strong>${q}</strong>"</div>`;return;}
  r.innerHTML=f.map(p=>`<a class="s-item" href="produto.php?id=${p.id}"><div class="s-img">${p.imagem?`<img src="uploads/produtos/${p.imagem}" alt=""/>`:catIcons[p.categoria]||'💎'}</div><div class="s-info"><h5>${p.nome}</h5><span>${p.categoria}</span></div><span class="s-price">R$ ${parseFloat(p.preco).toFixed(2).replace('.',',')}</span></a>`).join('');
}

// Tabs
function switchTab(id,btn){
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('on'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('on'));
  document.getElementById('tp-'+id)?.classList.add('on');
  if(btn)btn.classList.add('on');
}

// Stars
let picked=5;
function pickStar(v){picked=v;document.getElementById('notaInput').value=v;resetStars();}
function hoverStar(v){document.querySelectorAll('#sPicker span').forEach((s,i)=>s.style.color=i<v?'var(--gold)':'#d1d5db');}
function resetStars(){document.querySelectorAll('#sPicker span').forEach((s,i)=>s.style.color=i<picked?'var(--gold)':'#d1d5db');}

// Share
function shareWA(){window.open('https://wa.me/?text='+encodeURIComponent(document.title+' — '+window.location.href));}
function copyLink(){navigator.clipboard.writeText(window.location.href).then(()=>showToast('🔗 Link copiado!'));}

// Toast
function showToast(m){const t=document.getElementById('toast');t.textContent=m;t.style.opacity='1';t.style.transform='translateY(0)';setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(10px)';},2800);}

updateBadge();
if(window.location.hash==='#reviews'){setTimeout(()=>{switchTab('revs',document.querySelectorAll('.tab-btn')[2]);document.getElementById('tp-revs')?.scrollIntoView({behavior:'smooth'});},300);}
</script>
</body>
</html>