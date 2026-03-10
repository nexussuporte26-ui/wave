<?php
session_start();
require_once 'conexão.php';
header('Content-Type: application/json');

$logado   = isset($_SESSION['usuario_id']);
$nivel    = $logado ? ($_SESSION['nivel'] ?? 'usuario') : '';
$eh_admin = $nivel === 'admin';
$uid      = $logado ? (int)$_SESSION['usuario_id'] : 0;

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// ── Helper ──
function json_ok($data=[]) { echo json_encode(array_merge(['sucesso'=>true],$data)); exit(); }
function json_err($msg)    { echo json_encode(['sucesso'=>false,'erro'=>$msg]); exit(); }

// ── Garantir estrutura da tabela ──
$conn->query("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS imagem VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS destaque TINYINT(1) DEFAULT 0");
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

switch ($acao) {

  // ── Adicionar produto (admin) ──
  case 'adicionar_produto':
    if (!$eh_admin) json_err('Acesso negado.');
    $nome      = trim($conn->real_escape_string($_POST['nome'] ?? ''));
    $categoria = trim($conn->real_escape_string($_POST['categoria'] ?? ''));
    $descricao = trim($conn->real_escape_string($_POST['descricao'] ?? ''));
    $preco     = floatval($_POST['preco'] ?? 0);
    $estoque   = max(0,(int)($_POST['estoque'] ?? 0));
    $destaque  = isset($_POST['destaque']) ? 1 : 0;
    if (!$nome || !$categoria || $preco <= 0) json_err('Campos obrigatórios faltando.');

    // Upload de imagem
    $imagem_nome = null;
    if (!empty($_FILES['imagem']['name'])) {
      $ext_ok=['jpg','jpeg','png','webp','gif'];
      $ext=strtolower(pathinfo($_FILES['imagem']['name'],PATHINFO_EXTENSION));
      if(!in_array($ext,$ext_ok)) json_err('Formato inválido.');
      if($_FILES['imagem']['size']>5*1024*1024) json_err('Imagem muito grande (max 5MB).');
      $dir='uploads/produtos/';
      if(!is_dir($dir)) @mkdir($dir,0755,true);
      $imagem_nome=uniqid('prod_').'.'.$ext;
      if(!move_uploaded_file($_FILES['imagem']['tmp_name'],$dir.$imagem_nome)) json_err('Falha ao salvar imagem.');
    }
    $img_sql = $imagem_nome ? "'".$conn->real_escape_string($imagem_nome)."'" : "NULL";
    $conn->query("INSERT INTO produtos (nome,categoria,descricao,preco,estoque,ativo,destaque,imagem) VALUES ('$nome','$categoria','$descricao',$preco,$estoque,1,$destaque,$img_sql)");
    json_ok(['id'=>$conn->insert_id]);

  // ── Toggle destaque (admin) ──
  case 'toggle_destaque':
    if (!$eh_admin) json_err('Acesso negado.');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) json_err('ID inválido.');
    $r = $conn->query("SELECT destaque FROM produtos WHERE id=$id LIMIT 1");
    if (!$r || $r->num_rows===0) json_err('Produto não encontrado.');
    $atual = (int)$r->fetch_assoc()['destaque'];
    $novo  = $atual ? 0 : 1;
    $conn->query("UPDATE produtos SET destaque=$novo WHERE id=$id");
    json_ok(['destaque'=>$novo]);

  // ── Desativar produto (admin) ──
  case 'desativar':
    if (!$eh_admin) json_err('Acesso negado.');
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) json_err('ID inválido.');
    $conn->query("UPDATE produtos SET ativo=0 WHERE id=$id");
    json_ok();

  // ── Listar produtos ──
  case 'listar':
    $cat  = trim($conn->real_escape_string($_GET['categoria'] ?? ''));
    $q    = trim($conn->real_escape_string($_GET['q'] ?? ''));
    $sql  = "SELECT id,nome,descricao,preco,categoria,estoque,imagem,destaque FROM produtos WHERE ativo=1";
    if ($cat)  $sql .= " AND categoria='$cat'";
    if ($q)    $sql .= " AND (nome LIKE '%$q%' OR categoria LIKE '%$q%')";
    $sql .= " ORDER BY destaque DESC, id DESC";
    $res  = $conn->query($sql);
    $list = [];
    if ($res) while($row=$res->fetch_assoc()) $list[]=$row;
    json_ok(['produtos'=>$list]);

  // ── Buscar produto único ──
  case 'produto':
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) json_err('ID inválido.');
    $r = $conn->query("SELECT * FROM produtos WHERE id=$id AND ativo=1 LIMIT 1");
    if (!$r || $r->num_rows===0) json_err('Produto não encontrado.');
    $p = $r->fetch_assoc();
    // Avaliações
    $avs=[];$media=0;
    $ra = @$conn->query("SELECT a.*,u.nome as user_nome FROM avaliacoes a JOIN usuarios u ON a.usuario_id=u.id WHERE a.produto_id=$id ORDER BY a.criado_em DESC");
    if($ra){while($row=$ra->fetch_assoc())$avs[]=$row; if(count($avs)>0)$media=array_sum(array_column($avs,'nota'))/count($avs);}
    json_ok(['produto'=>$p,'avaliacoes'=>$avs,'media'=>round($media,1),'total'=>count($avs)]);

  // ── Avaliar produto ──
  case 'avaliar':
    if (!$logado) json_err('Você precisa estar logado.');
    $pid   = (int)($_POST['produto_id'] ?? 0);
    $nota  = min(5,max(1,(int)($_POST['nota'] ?? 5)));
    $titulo= trim($conn->real_escape_string($_POST['titulo'] ?? ''));
    $texto = trim($conn->real_escape_string($_POST['texto'] ?? ''));
    if (!$pid || strlen($texto)<5) json_err('Dados inválidos.');
    // Checa duplicata
    $chk=$conn->query("SELECT id FROM avaliacoes WHERE produto_id=$pid AND usuario_id=$uid LIMIT 1");
    if($chk&&$chk->num_rows>0) json_err('Você já avaliou este produto.');
    $conn->query("INSERT INTO avaliacoes (produto_id,usuario_id,nota,titulo,texto,criado_em) VALUES ($pid,$uid,$nota,'$titulo','$texto',NOW())");
    json_ok(['id'=>$conn->insert_id]);

  default:
    json_err('Ação desconhecida: '.$acao);
}