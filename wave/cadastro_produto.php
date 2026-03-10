<?php
session_start();
require_once 'conexão.php';

// Somente admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel'] ?? '') !== 'admin') {
    header("Location: index.php"); exit();
}

$nome_s   = htmlspecialchars($_SESSION['nome'] ?? '');
$iniciais = '';
if ($nome_s) { $pts = explode(' ',$nome_s); $iniciais = strtoupper(substr($pts[0],0,1).(isset($pts[1])?substr($pts[1],0,1):'')); }

// Modo edição?
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produto_edit = null;
if ($edit_id) {
    $re = $conn->query("SELECT * FROM produtos WHERE id=$edit_id LIMIT 1");
    if ($re && $re->num_rows > 0) $produto_edit = $re->fetch_assoc();
}

// ── Criar tabela avaliacoes se não existir ──
$conn->query("CREATE TABLE IF NOT EXISTS avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  usuario_id INT NOT NULL,
  nota TINYINT NOT NULL DEFAULT 5,
  titulo VARCHAR(150) DEFAULT '',
  texto TEXT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY(produto_id), KEY(usuario_id)
)");

// ── Garantir coluna imagem em produtos ──
$conn->query("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS imagem VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS destaque TINYINT(1) DEFAULT 0");

// ── PROCESSAR FORM ──
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao     = $_POST['acao'] ?? '';
    $nome     = trim($conn->real_escape_string($_POST['nome'] ?? ''));
    $categoria= trim($conn->real_escape_string($_POST['categoria'] ?? ''));
    $descricao= trim($conn->real_escape_string($_POST['descricao'] ?? ''));
    $preco    = floatval($_POST['preco'] ?? 0);
    $estoque  = max(0, (int)($_POST['estoque'] ?? 0));
    $ativo    = isset($_POST['ativo']) ? 1 : 0;
    $destaque = isset($_POST['destaque']) ? 1 : 0;

    if (!$nome || !$categoria || $preco <= 0) {
        $msg = 'erro|Preencha todos os campos obrigatórios.';
    } else {
        // Upload de imagem
        $imagem_nome = $produto_edit['imagem'] ?? null; // mantém atual em edições
        if (!empty($_FILES['imagem']['name'])) {
            $ext_ok = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_ok)) {
                $msg = 'erro|Formato de imagem inválido. Use JPG, PNG, WEBP ou GIF.';
            } elseif ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
                $msg = 'erro|Imagem muito grande. Máximo 5MB.';
            } else {
                $dir = 'uploads/produtos/';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $imagem_nome = uniqid('prod_').'.'.$ext;
                if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $dir.$imagem_nome)) {
                    $msg = 'erro|Falha ao salvar imagem. Verifique permissões da pasta uploads/produtos/';
                    $imagem_nome = $produto_edit['imagem'] ?? null;
                }
            }
        }

        if (!$msg) {
            $img_sql = $imagem_nome ? "'".$conn->real_escape_string($imagem_nome)."'" : "NULL";
            if ($acao === 'editar' && $edit_id) {
                $conn->query("UPDATE produtos SET nome='$nome', categoria='$categoria', descricao='$descricao', preco=$preco, estoque=$estoque, ativo=$ativo, destaque=$destaque, imagem=$img_sql WHERE id=$edit_id");
                $msg = 'ok|Produto atualizado com sucesso! <a href="produto.php?id='.$edit_id.'" style="color:inherit;font-weight:700">Ver página →</a>';
            } else {
                $conn->query("INSERT INTO produtos (nome, categoria, descricao, preco, estoque, ativo, destaque, imagem) VALUES ('$nome','$categoria','$descricao',$preco,$estoque,$ativo,$destaque,$img_sql)");
                $new_id = $conn->insert_id;
                $msg = 'ok|Produto cadastrado! <a href="produto.php?id='.$new_id.'" style="color:inherit;font-weight:700">Ver página →</a>';
                // Reset para novo produto
                $produto_edit = null;
                $edit_id = 0;
            }
        }
    }
}

[$mt, $mv] = $msg ? explode('|', $msg, 2) : ['',''];
$pe = $produto_edit; // shortcut
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $pe ? 'Editar Produto' : 'Novo Produto' ?> — Wave Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root{--blue:#0A5489;--blue-dk:#073d66;--blue-lt:#1a7abf;--cream:#FFF3E7;--sand:#F5DEC8;--gold:#C8963E;--white:#FEFCF9;--text:#1a2e3b;--muted:#7a8d99;--font-display:'Cormorant Garamond',Georgia,serif;--font-body:'DM Sans',sans-serif;--ease:cubic-bezier(.22,.61,.36,1);}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:var(--font-body);background:var(--cream);color:var(--text);min-height:100vh}
    a{text-decoration:none;color:inherit}

    /* ── TOP BAR ── */
    .topbar{background:var(--blue-dk);color:rgba(255,243,231,.8);padding:.65rem 5%;display:flex;align-items:center;justify-content:space-between;gap:1rem;font-size:.78rem}
    .topbar-left{display:flex;align-items:center;gap:1.2rem}
    .topbar a{color:rgba(255,243,231,.65);transition:color .2s}
    .topbar a:hover{color:var(--gold)}
    .topbar-badge{background:#7c3aed;color:#fff;padding:.2rem .6rem;border-radius:999px;font-size:.65rem;font-weight:700}
    .topbar-av{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#8b5cf6);color:#fff;font-size:.62rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .topbar-right{display:flex;align-items:center;gap:.8rem}

    /* ── HEADER ── */
    .page-header{background:linear-gradient(135deg,var(--blue-dk),var(--blue));padding:2.5rem 5%;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}
    .page-header h1{font-family:var(--font-display);font-size:2rem;color:#fff;font-weight:600}
    .page-header p{font-size:.82rem;color:rgba(255,243,231,.65);margin-top:.2rem}
    .header-actions{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap}
    .hbtn{padding:.55rem 1.2rem;border-radius:2rem;font-size:.78rem;font-weight:600;cursor:pointer;font-family:var(--font-body);border:1.5px solid rgba(255,243,231,.3);color:rgba(255,243,231,.85);background:rgba(255,255,255,.1);transition:all .22s}
    .hbtn:hover{background:rgba(255,255,255,.2);border-color:rgba(255,243,231,.55)}
    .hbtn.primary{background:var(--gold);border-color:var(--gold);color:#fff}
    .hbtn.primary:hover{background:#b8832e}

    /* ── LAYOUT ── */
    .form-layout{display:grid;grid-template-columns:1fr 380px;gap:2rem;padding:2rem 5%;max-width:1300px;margin:0 auto;align-items:start}

    /* ── CARD ── */
    .card{background:var(--white);border-radius:1.2rem;box-shadow:0 4px 24px rgba(10,84,137,.07);overflow:hidden;margin-bottom:1.5rem}
    .card-head{padding:1.1rem 1.4rem;border-bottom:1px solid rgba(10,84,137,.08);display:flex;align-items:center;gap:.65rem}
    .card-head h3{font-family:var(--font-display);font-size:1.25rem;color:var(--blue);font-weight:600}
    .card-head .ch-icon{font-size:1.1rem}
    .card-body{padding:1.4rem}

    /* ── FORM FIELDS ── */
    .fg{display:flex;flex-direction:column;gap:.3rem;margin-bottom:1rem}
    .fg label{font-size:.68rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.07em}
    .fg label .req{color:#dc2626;margin-left:.2rem}
    .fw{display:flex;align-items:center;gap:.5rem;border:1.5px solid rgba(10,84,137,.15);border-radius:.65rem;padding:.6rem .9rem;background:var(--cream);transition:border-color .18s}
    .fw:focus-within{border-color:var(--blue);background:var(--white)}
    .fw input,.fw select,.fw textarea{border:none;outline:none;background:none;font-size:.88rem;color:var(--text);font-family:var(--font-body);flex:1}
    .fw textarea{resize:vertical;min-height:100px}
    .fw select option{background:var(--white)}
    .g2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .hint{font-size:.67rem;color:var(--muted);margin-top:.2rem}

    /* ── TOGGLE ── */
    .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid rgba(10,84,137,.07)}
    .toggle-row:last-child{border-bottom:none}
    .toggle-label{font-size:.88rem;font-weight:500;color:var(--text)}
    .toggle-sub{font-size:.72rem;color:var(--muted);margin-top:.1rem}
    .toggle{position:relative;width:44px;height:24px;flex-shrink:0}
    .toggle input{opacity:0;width:0;height:0;position:absolute}
    .toggle-track{position:absolute;inset:0;background:#d1d5db;border-radius:99px;cursor:pointer;transition:background .25s}
    .toggle input:checked + .toggle-track{background:var(--blue)}
    .toggle-track::before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;top:3px;left:3px;transition:transform .25s;box-shadow:0 1px 4px rgba(0,0,0,.2)}
    .toggle input:checked + .toggle-track::before{transform:translateX(20px)}

    /* ── IMAGE UPLOAD ── */
    .img-upload-zone{border:2px dashed rgba(10,84,137,.25);border-radius:1rem;padding:2rem;text-align:center;cursor:pointer;transition:all .25s;background:var(--cream);position:relative}
    .img-upload-zone:hover,.img-upload-zone.drag{border-color:var(--blue);background:rgba(10,84,137,.04)}
    .img-upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
    .img-upload-icon{font-size:2.5rem;opacity:.4;margin-bottom:.7rem}
    .img-upload-text{font-size:.85rem;color:var(--muted);line-height:1.55}
    .img-upload-text strong{color:var(--blue);display:block;font-size:.9rem}
    .img-preview-wrap{position:relative;border-radius:1rem;overflow:hidden;background:var(--cream);aspect-ratio:1;display:none}
    .img-preview-wrap.has-img{display:block}
    .img-preview-wrap img{width:100%;height:100%;object-fit:cover}
    .img-remove{position:absolute;top:.6rem;right:.6rem;background:rgba(220,38,38,.85);color:#fff;border:none;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:.85rem;display:flex;align-items:center;justify-content:center;transition:background .2s}
    .img-remove:hover{background:#dc2626}
    .img-meta{font-size:.72rem;color:var(--muted);margin-top:.5rem;text-align:center}
    .img-current{border-radius:1rem;overflow:hidden;margin-bottom:.8rem;aspect-ratio:1;background:var(--cream);display:flex;align-items:center;justify-content:center;font-size:3rem}
    .img-current img{width:100%;height:100%;object-fit:cover}

    /* ── ALERT ── */
    .malert{border-radius:.8rem;padding:.8rem 1.1rem;font-size:.84rem;font-weight:500;margin-bottom:1.2rem;display:flex;align-items:center;gap:.5rem;line-height:1.5}
    .malert.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
    .malert.er{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;animation:shake .35s ease}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}

    /* ── BTNS ── */
    .save-btn{width:100%;background:var(--blue);color:var(--cream);border:none;padding:1rem;border-radius:2rem;font-size:.9rem;font-weight:700;cursor:pointer;font-family:var(--font-body);transition:background .25s,transform .2s;letter-spacing:.06em;margin-top:.5rem}
    .save-btn:hover{background:var(--gold);transform:translateY(-1px)}
    .del-btn{width:100%;background:none;color:#dc2626;border:1.5px solid #fca5a5;padding:.7rem;border-radius:2rem;font-size:.82rem;font-weight:600;cursor:pointer;font-family:var(--font-body);transition:all .25s;margin-top:.6rem}
    .del-btn:hover{background:#fef2f2}

    /* ── PRODUCT PREVIEW ── */
    .preview-card{border-radius:1.1rem;overflow:hidden;background:var(--white);box-shadow:0 4px 20px rgba(10,84,137,.1)}
    .pc-img{aspect-ratio:1;background:linear-gradient(135deg,var(--cream),var(--sand));display:flex;align-items:center;justify-content:center;font-size:5rem;position:relative;overflow:hidden}
    .pc-img img{width:100%;height:100%;object-fit:cover}
    .pc-info{padding:1rem 1.1rem 1.2rem}
    .pc-cat{font-size:.68rem;color:var(--gold);font-weight:600;letter-spacing:.1em;text-transform:uppercase;margin-bottom:.2rem}
    .pc-name{font-family:var(--font-display);font-size:1.15rem;font-weight:600;color:var(--text);margin-bottom:.6rem}
    .pc-price{font-size:1.05rem;font-weight:700;color:var(--blue)}

    /* ── RESPONSIVE ── */
    @media(max-width:960px){.form-layout{grid-template-columns:1fr}}
    @media(max-width:600px){.g2{grid-template-columns:1fr}}
  </style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-left">
    <a href="index.php">🌊 Wave</a>
    <span style="opacity:.3">/</span>
    <a href="Dashboard.php">Dashboard</a>
    <span style="opacity:.3">/</span>
    <span style="color:rgba(255,243,231,.9)"><?= $pe ? 'Editar Produto' : 'Novo Produto' ?></span>
  </div>
  <div class="topbar-right">
    <span class="topbar-badge">⚙️ Admin</span>
    <div class="topbar-av"><?= $iniciais ?: '👤' ?></div>
    <a href="index.php?sair=1" style="opacity:.7">Sair</a>
  </div>
</div>

<!-- HEADER -->
<div class="page-header">
  <div>
    <h1><?= $pe ? '✏️ Editar Produto' : '✨ Novo Produto' ?></h1>
    <p><?= $pe ? 'Editando: '.htmlspecialchars($pe['nome']) : 'Adicione um novo produto ao catálogo Wave' ?></p>
  </div>
  <div class="header-actions">
    <?php if($pe): ?>
      <a href="produto.php?id=<?= $edit_id ?>" class="hbtn">👁 Ver página</a>
      <a href="catalogo.php" class="hbtn">← Catálogo</a>
    <?php else: ?>
      <a href="catalogo.php" class="hbtn">← Voltar ao catálogo</a>
    <?php endif; ?>
  </div>
</div>

<!-- FORM -->
<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="acao" value="<?= $pe ? 'editar' : 'adicionar' ?>"/>

  <div class="form-layout">
    <!-- COLUNA ESQUERDA -->
    <div>
      <?php if($mt): ?>
        <div class="malert <?= $mt==='ok'?'ok':'er' ?>"><?= $mt==='ok'?'✅':'❌' ?> <?= $mv ?></div>
      <?php endif; ?>

      <!-- Informações básicas -->
      <div class="card">
        <div class="card-head"><span class="ch-icon">📋</span><h3>Informações do Produto</h3></div>
        <div class="card-body">
          <div class="fg">
            <label>Nome do produto<span class="req">*</span></label>
            <div class="fw"><input type="text" name="nome" id="pNome" placeholder="Ex: Pulseira Ondas do Mar" required value="<?= htmlspecialchars($pe['nome'] ?? '') ?>" oninput="updatePreview()"/></div>
          </div>
          <div class="fg">
            <label>Categoria<span class="req">*</span></label>
            <div class="fw">
              <select name="categoria" id="pCat" required onchange="updatePreview()">
                <option value="">Selecione uma categoria…</option>
                <?php foreach(['Colares','Pulseiras','Brincos','Anéis','Kits'] as $c): ?>
                  <option value="<?= $c ?>" <?= ($pe['categoria']??'')===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="fg">
            <label>Descrição</label>
            <div class="fw"><textarea name="descricao" id="pDesc" placeholder="Descreva materiais, tamanho, inspiração…" oninput="updatePreview()"><?= htmlspecialchars($pe['descricao'] ?? '') ?></textarea></div>
          </div>
        </div>
      </div>

      <!-- Preço e Estoque -->
      <div class="card">
        <div class="card-head"><span class="ch-icon">💰</span><h3>Preço & Estoque</h3></div>
        <div class="card-body">
          <div class="g2">
            <div class="fg">
              <label>Preço (R$)<span class="req">*</span></label>
              <div class="fw"><span style="opacity:.5;font-size:.85rem">R$</span><input type="number" name="preco" id="pPreco" placeholder="0,00" step="0.01" min="0.01" required value="<?= $pe?number_format((float)$pe['preco'],2,'.',''):'' ?>" oninput="updatePreview()"/></div>
            </div>
            <div class="fg">
              <label>Estoque<span class="req">*</span></label>
              <div class="fw"><input type="number" name="estoque" placeholder="0" min="0" required value="<?= htmlspecialchars($pe['estoque'] ?? '0') ?>"/></div>
            </div>
          </div>
          <div class="hint">💡 Produtos com estoque 0 aparecerão como "Esgotado" na loja.</div>
        </div>
      </div>

      <!-- Upload de imagem -->
      <div class="card">
        <div class="card-head"><span class="ch-icon">🖼️</span><h3>Imagem do Produto</h3></div>
        <div class="card-body">
          <?php if(!empty($pe['imagem'])): ?>
            <div class="img-current"><img src="uploads/produtos/<?= htmlspecialchars($pe['imagem']) ?>" alt="Imagem atual"/></div>
            <p class="hint" style="margin-bottom:.8rem">Imagem atual: <?= htmlspecialchars($pe['imagem']) ?>. Selecione nova para substituir.</p>
          <?php endif; ?>

          <div class="img-preview-wrap" id="previewWrap">
            <img id="previewImg" src="" alt="Preview"/>
            <button type="button" class="img-remove" onclick="removeImg()">✕</button>
          </div>
          <div id="uploadZone" class="img-upload-zone" ondrop="handleDrop(event)" ondragover="e=>e.preventDefault()" ondragenter="this.classList.add('drag')" ondragleave="this.classList.remove('drag')">
            <input type="file" name="imagem" id="imgFile" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewFile(this)"/>
            <div class="img-upload-icon">📸</div>
            <div class="img-upload-text">
              <strong>Clique ou arraste uma imagem aqui</strong>
              JPG, PNG, WEBP ou GIF · Máximo 5 MB
            </div>
          </div>
          <p class="img-meta" id="imgMeta"></p>
        </div>
      </div>
    </div>

    <!-- COLUNA DIREITA -->
    <div>
      <!-- Prévia do card -->
      <div class="card" style="margin-bottom:1.5rem">
        <div class="card-head"><span class="ch-icon">👁</span><h3>Pré-visualização</h3></div>
        <div class="card-body" style="padding-bottom:1.5rem">
          <div class="preview-card">
            <div class="pc-img" id="pcImg">
              <span id="pcIcon"><?= !empty($pe['categoria'])?(['Colares'=>'🐚','Pulseiras'=>'🌊','Brincos'=>'🌺','Anéis'=>'💍','Kits'=>'🎁'][$pe['categoria']]??'💎'):'💎' ?></span>
              <?php if(!empty($pe['imagem'])): ?>
                <img src="uploads/produtos/<?= htmlspecialchars($pe['imagem']) ?>" id="pcImgTag" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover"/>
              <?php else: ?>
                <img id="pcImgTag" src="" style="display:none;position:absolute;inset:0;width:100%;height:100%;object-fit:cover"/>
              <?php endif; ?>
            </div>
            <div class="pc-info">
              <div class="pc-cat" id="pcCat"><?= htmlspecialchars($pe['categoria'] ?? 'Categoria') ?></div>
              <div class="pc-name" id="pcName"><?= htmlspecialchars($pe['nome'] ?? 'Nome do produto') ?></div>
              <div class="pc-price" id="pcPrice"><?= !empty($pe['preco'])?'R$ '.number_format((float)$pe['preco'],2,',','.') : 'R$ 0,00' ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Configurações -->
      <div class="card">
        <div class="card-head"><span class="ch-icon">⚙️</span><h3>Configurações</h3></div>
        <div class="card-body">
          <div class="toggle-row">
            <div><div class="toggle-label">Produto ativo</div><div class="toggle-sub">Visível no catálogo para clientes</div></div>
            <label class="toggle"><input type="checkbox" name="ativo" <?= ($pe['ativo']??1)?'checked':'' ?>><div class="toggle-track"></div></label>
          </div>
          <div class="toggle-row">
            <div><div class="toggle-label">⭐ Destacar produto</div><div class="toggle-sub">Aparece na seção de destaques</div></div>
            <label class="toggle"><input type="checkbox" name="destaque" <?= !empty($pe['destaque'])?'checked':'' ?>><div class="toggle-track"></div></label>
          </div>
        </div>
      </div>

      <!-- Salvar -->
      <div class="card">
        <div class="card-body">
          <button type="submit" class="save-btn">
            <?= $pe ? '💾 Salvar alterações' : '✨ Publicar produto' ?>
          </button>
          <?php if($pe): ?>
            <button type="button" class="del-btn" onclick="confirmarDelete(<?= $edit_id ?>)">🗑 Desativar produto</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
  const catIcons={Colares:'🐚',Pulseiras:'🌊',Brincos:'🌺','Anéis':'💍',Kits:'🎁'};

  function updatePreview(){
    const nome=document.getElementById('pNome')?.value||'Nome do produto';
    const cat=document.getElementById('pCat')?.value||'Categoria';
    const preco=parseFloat(document.getElementById('pPreco')?.value||0);

    document.getElementById('pcName').textContent=nome;
    document.getElementById('pcCat').textContent=cat||'Categoria';
    document.getElementById('pcIcon').textContent=catIcons[cat]||'💎';
    document.getElementById('pcPrice').textContent='R$ '+(preco>0?preco.toFixed(2).replace('.',','):'0,00');
  }

  function previewFile(input){
    const file=input.files[0];
    if(!file)return;
    const reader=new FileReader();
    reader.onload=e=>{
      const pw=document.getElementById('previewWrap');
      const pi=document.getElementById('previewImg');
      const pci=document.getElementById('pcImgTag');
      const uz=document.getElementById('uploadZone');
      pi.src=e.target.result;
      pci.src=e.target.result;
      pci.style.display='block';
      pw.classList.add('has-img');
      uz.style.display='none';
      document.getElementById('imgMeta').textContent=`${file.name} · ${(file.size/1024).toFixed(0)} KB`;
    };
    reader.readAsDataURL(file);
  }

  function removeImg(){
    document.getElementById('imgFile').value='';
    document.getElementById('previewWrap').classList.remove('has-img');
    document.getElementById('uploadZone').style.display='';
    document.getElementById('imgMeta').textContent='';
    const pci=document.getElementById('pcImgTag');
    if(pci){pci.src='';pci.style.display='none';}
  }

  function handleDrop(e){
    e.preventDefault();
    document.getElementById('uploadZone').classList.remove('drag');
    const file=e.dataTransfer.files[0];
    if(file&&file.type.startsWith('image/')){
      const dt=new DataTransfer();
      dt.items.add(file);
      document.getElementById('imgFile').files=dt.files;
      previewFile(document.getElementById('imgFile'));
    }
  }

  function confirmarDelete(id){
    if(confirm('Desativar este produto? Ele não aparecerá mais no catálogo.')){
      fetch('api_produtos.php',{method:'POST',body:Object.assign(new FormData(),{acao:'desativar',id})})
        .then(r=>r.json()).then(d=>{if(d.sucesso)window.location='catalogo.php';});
    }
  }
</script>
</body>
</html>