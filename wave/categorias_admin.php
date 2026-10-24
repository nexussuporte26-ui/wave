<?php
session_start();
require_once 'conexao.php';

// Somente admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel'] ?? '') !== 'admin') {
    header("Location: index.php"); exit();
}

$nome_s   = htmlspecialchars($_SESSION['nome'] ?? '');
$iniciais = '';
if ($nome_s) { $pts = explode(' ',$nome_s); $iniciais = strtoupper(substr($pts[0],0,1).(isset($pts[1])?substr($pts[1],0,1):'')); }

define('CAT_UPLOAD_DIR', 'uploads/categorias/');

function salvar_imagem_categoria($conn, $atual) {
    if (empty($_FILES['imagem']['name'])) return [$atual, null];
    $ext_ok = ['jpg','jpeg','png','webp','gif'];
    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ext_ok)) return [$atual, 'Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF.'];
    if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) return [$atual, 'Imagem muito grande. Máximo 5MB.'];
    if (!is_dir(CAT_UPLOAD_DIR)) @mkdir(CAT_UPLOAD_DIR, 0755, true);
    $novo_nome = uniqid('cat_').'.'.$ext;
    if (!move_uploaded_file($_FILES['imagem']['tmp_name'], CAT_UPLOAD_DIR.$novo_nome)) {
        return [$atual, 'Falha ao salvar imagem. Verifique permissões da pasta '.CAT_UPLOAD_DIR];
    }
    return [$novo_nome, null];
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome = trim($conn->real_escape_string($_POST['nome'] ?? ''));
        if (!$nome) {
            $msg = 'erro|Digite um nome para a categoria.';
        } else {
            $existe = $conn->query("SELECT id FROM categorias WHERE nome='$nome' LIMIT 1");
            if ($existe && $existe->num_rows > 0) {
                $msg = 'erro|Já existe uma categoria com esse nome.';
            } else {
                [$img, $erro_img] = salvar_imagem_categoria($conn, null);
                if ($erro_img) {
                    $msg = 'erro|'.$erro_img;
                } else {
                    $img_sql = $img ? "'".$conn->real_escape_string($img)."'" : "NULL";
                    $rmax = $conn->query("SELECT COALESCE(MAX(ordem),0) as m FROM categorias");
                    $ordem = ((int)$rmax->fetch_assoc()['m']) + 1;
                    $conn->query("INSERT INTO categorias (nome, imagem, ordem) VALUES ('$nome',$img_sql,$ordem)");
                    $msg = 'ok|Categoria "'.htmlspecialchars($nome).'" criada.';
                }
            }
        }
    }

    if ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($conn->real_escape_string($_POST['nome'] ?? ''));
        $r = $conn->query("SELECT * FROM categorias WHERE id=$id LIMIT 1");
        $cat = $r ? $r->fetch_assoc() : null;
        if (!$cat) {
            $msg = 'erro|Categoria não encontrada.';
        } elseif (!$nome) {
            $msg = 'erro|Digite um nome para a categoria.';
        } else {
            $dup = $conn->query("SELECT id FROM categorias WHERE nome='$nome' AND id!=$id LIMIT 1");
            if ($dup && $dup->num_rows > 0) {
                $msg = 'erro|Já existe outra categoria com esse nome.';
            } else {
                [$img, $erro_img] = salvar_imagem_categoria($conn, $cat['imagem']);
                if ($erro_img) {
                    $msg = 'erro|'.$erro_img;
                } else {
                    $img_sql = $img ? "'".$conn->real_escape_string($img)."'" : "NULL";
                    $conn->query("UPDATE categorias SET nome='$nome', imagem=$img_sql WHERE id=$id");
                    // Mantém os produtos já cadastrados apontando para o nome certo
                    if ($nome !== $cat['nome']) {
                        $nome_antigo = $conn->real_escape_string($cat['nome']);
                        $conn->query("UPDATE produtos SET categoria='$nome' WHERE categoria='$nome_antigo'");
                    }
                    $msg = 'ok|Categoria atualizada.';
                }
            }
        }
    }

    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        $r = $conn->query("SELECT * FROM categorias WHERE id=$id LIMIT 1");
        $cat = $r ? $r->fetch_assoc() : null;
        if ($cat) {
            $nome_esc = $conn->real_escape_string($cat['nome']);
            $emuso = $conn->query("SELECT COUNT(*) as c FROM produtos WHERE categoria='$nome_esc'");
            $qtd = $emuso ? (int)$emuso->fetch_assoc()['c'] : 0;
            if ($qtd > 0) {
                $msg = 'erro|Essa categoria tem '.$qtd.' produto(s) cadastrado(s). Mude a categoria desses produtos antes de excluir.';
            } else {
                $conn->query("DELETE FROM categorias WHERE id=$id");
                $msg = 'ok|Categoria excluída.';
            }
        }
    }
}

$categorias = [];
$r = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM produtos p WHERE p.categoria=c.nome) as qtd_produtos FROM categorias c ORDER BY c.ordem, c.nome");
if ($r) while ($row = $r->fetch_assoc()) $categorias[] = $row;

[$mt, $mv] = $msg ? explode('|', $msg, 2) : ['',''];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Categorias — Wave Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300..800&display=swap" rel="stylesheet"/>
  <style>
    :root{--blue:#0A5489;--blue-dk:#073d66;--blue-lt:#1a7abf;--cream:#FFF3E7;--sand:#F5DEC8;--gold:#C8963E;--white:#FEFCF9;--text:#1a2e3b;--muted:#7a8d99;--font-display:'Manrope',system-ui,sans-serif;--font-body:'Manrope',system-ui,sans-serif;--ease:cubic-bezier(.22,.61,.36,1);}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:var(--font-body);background:var(--cream);color:var(--text);min-height:100vh}
    a{text-decoration:none;color:inherit}

    .topbar{background:var(--blue-dk);color:rgba(255,243,231,.8);padding:.65rem 5%;display:flex;align-items:center;justify-content:space-between;gap:.7rem;font-size:.78rem;flex-wrap:wrap}
    .topbar-left{display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;min-width:0}
    .topbar-left a,.topbar-left span{white-space:nowrap}
    .topbar a{color:rgba(255,243,231,.65);transition:color .2s}
    .topbar a:hover{color:var(--gold)}
    .topbar-badge{background:#7c3aed;color:#fff;padding:.2rem .6rem;border-radius:999px;font-size:.65rem;font-weight:700}
    .topbar-av{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#8b5cf6);color:#fff;font-size:.62rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .topbar-right{display:flex;align-items:center;gap:.8rem}

    .page-header{background:linear-gradient(135deg,var(--blue-dk),var(--blue));padding:2.5rem 5%;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
    .page-header h1{font-family:var(--font-display);font-size:2rem;color:#fff;font-weight:600}
    .page-header p{font-size:.82rem;color:rgba(255,243,231,.65);margin-top:.2rem}
    .hbtn{padding:.55rem 1.2rem;border-radius:2rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--font-body);border:1.5px solid rgba(255,243,231,.3);color:rgba(255,243,231,.85);background:rgba(255,255,255,.1);transition:all .22s;display:inline-block}
    .hbtn:hover{background:rgba(255,255,255,.2);border-color:rgba(255,243,231,.55)}

    .wrap{max-width:1100px;margin:0 auto;padding:2rem 5%}

    .malert{border-radius:.8rem;padding:.8rem 1.1rem;font-size:.84rem;font-weight:500;margin-bottom:1.2rem;line-height:1.5}
    .malert.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
    .malert.er{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626}

    .card{background:var(--white);border-radius:1.2rem;box-shadow:0 4px 24px rgba(10,84,137,.07);overflow:hidden;margin-bottom:1.5rem}
    .card-head{padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.08)}
    .card-head h3{font-family:var(--font-display);font-size:1.25rem;color:var(--blue);font-weight:600}
    .card-body{padding:1.4rem}

    .fg{display:flex;flex-direction:column;gap:.3rem;margin-bottom:1rem}
    .fg label{font-size:.68rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em}
    .fw{display:flex;align-items:center;gap:.5rem;border:1.5px solid rgba(10,84,137,.15);border-radius:.65rem;padding:.6rem .9rem;background:var(--cream)}
    .fw:focus-within{border-color:var(--blue);background:var(--white)}
    .fw input{border:none;outline:none;background:none;font-size:.88rem;color:var(--text);font-family:var(--font-body);flex:1}
    .fw input[type=file]{padding:.1rem 0}

    .save-btn{background:var(--blue);color:var(--cream);border:none;padding:.7rem 1.6rem;border-radius:2rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:var(--font-body);transition:background .25s;letter-spacing:.04em}
    .save-btn:hover{background:var(--gold)}

    .cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1.2rem}
    .cat-row{background:var(--white);border-radius:1rem;box-shadow:0 4px 18px rgba(10,84,137,.07);overflow:hidden;display:flex;flex-direction:column}
    .cat-thumb{aspect-ratio:4/3;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
    .cat-thumb img{width:100%;height:100%;object-fit:cover}
    .cat-thumb-empty{opacity:.35}
    .cat-body{padding:1rem 1.1rem 1.2rem;display:flex;flex-direction:column;gap:.6rem}
    .cat-nome-atual{font-family:var(--font-display);font-size:1.15rem;font-weight:600;color:var(--text)}
    .cat-qtd{font-size:.72rem;color:var(--muted)}
    .cat-edit-form{display:flex;flex-direction:column;gap:.5rem;border-top:1px dashed rgba(10,84,137,.15);padding-top:.7rem;margin-top:.2rem}
    .cat-edit-form input[type=text]{border:1.5px solid rgba(10,84,137,.15);border-radius:.5rem;padding:.45rem .7rem;font-size:.82rem;font-family:var(--font-body);color:var(--text)}
    .cat-edit-form input[type=file]{font-size:.72rem}
    .row-btns{display:flex;gap:.5rem;margin-top:.2rem}
    .row-btn{flex:1;padding:.5rem;border-radius:.6rem;font-size:.76rem;font-weight:600;cursor:pointer;border:none;font-family:var(--font-body);transition:all .2s}
    .row-btn.save{background:var(--blue);color:#fff}
    .row-btn.save:hover{background:var(--gold)}
    .row-btn.del{background:none;border:1.5px solid #fca5a5;color:#dc2626}
    .row-btn.del:hover{background:#fef2f2}

    .empty-note{text-align:center;padding:3rem 1rem;color:var(--muted);font-size:.9rem}

    /* ── RESPONSIVE ── */
    @media(max-width:600px){.cat-grid{grid-template-columns:1fr}}
    @media(max-width:480px){
      .topbar{font-size:.7rem;padding:.6rem 4%}
      .page-header{padding:1.8rem 4%}
      .page-header h1{font-size:1.5rem}
      .wrap{padding:1.4rem 4%}
      .card-body{padding:1.1rem}
    }
  </style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="index.php">Wave</a>
    <span style="opacity:.3">/</span>
    <a href="Dashboard.php">Dashboard</a>
    <span style="opacity:.3">/</span>
    <span style="color:rgba(255,243,231,.9)">Categorias</span>
  </div>
  <div class="topbar-right">
    <span class="topbar-badge">Admin</span>
    <div class="topbar-av"><?= $iniciais ?: '?' ?></div>
    <a href="index.php?sair=1" style="opacity:.7">Sair</a>
  </div>
</div>

<div class="page-header">
  <div>
    <h1>Categorias</h1>
    <p>Gerencie os nomes e imagens das categorias exibidas na home e no catálogo</p>
  </div>
  <a href="Dashboard.php" class="hbtn">Voltar ao Dashboard</a>
</div>

<div class="wrap">
  <?php if ($mt): ?>
    <div class="malert <?= $mt==='ok'?'ok':'er' ?>"><?= $mv ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-head"><h3>Nova categoria</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="criar"/>
        <div class="fg">
          <label>Nome da categoria</label>
          <div class="fw"><input type="text" name="nome" placeholder="Ex: Tornozeleiras" required/></div>
        </div>
        <div class="fg">
          <label>Imagem (opcional — pode adicionar depois)</label>
          <div class="fw"><input type="file" name="imagem" accept="image/jpeg,image/png,image/webp,image/gif"/></div>
        </div>
        <button type="submit" class="save-btn">Criar categoria</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Categorias existentes</h3></div>
    <div class="card-body">
      <?php if (empty($categorias)): ?>
        <div class="empty-note">Nenhuma categoria cadastrada ainda.</div>
      <?php else: ?>
        <div class="cat-grid">
          <?php foreach ($categorias as $c): ?>
            <div class="cat-row">
              <div class="cat-thumb">
                <?php if (!empty($c['imagem'])): ?>
                  <img src="<?= CAT_UPLOAD_DIR.htmlspecialchars($c['imagem']) ?>" alt="<?= htmlspecialchars($c['nome']) ?>"/>
                <?php else: ?>
                  <svg class="cat-thumb-empty" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                <?php endif; ?>
              </div>
              <div class="cat-body">
                <div class="cat-nome-atual"><?= htmlspecialchars($c['nome']) ?></div>
                <div class="cat-qtd"><?= $c['qtd_produtos'] ?> produto<?= $c['qtd_produtos']!=1?'s':'' ?></div>
                <form method="POST" enctype="multipart/form-data" class="cat-edit-form">
                  <input type="hidden" name="acao" value="editar"/>
                  <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
                  <input type="text" name="nome" value="<?= htmlspecialchars($c['nome']) ?>" required/>
                  <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp,image/gif"/>
                  <div class="row-btns">
                    <button type="submit" class="row-btn save">Salvar</button>
                  </div>
                </form>
                <form method="POST" onsubmit="return confirm('Excluir a categoria &quot;<?= htmlspecialchars($c['nome'], ENT_QUOTES) ?>&quot;?')">
                  <input type="hidden" name="acao" value="excluir"/>
                  <input type="hidden" name="id" value="<?= $c['id'] ?>"/>
                  <button type="submit" class="row-btn del" style="width:100%">Excluir</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
