<?php
session_start();
require_once 'conexão.php';

// 🔐 VERIFICAÇÃO DE AUTENTICAÇÃO
$logado = isset($_SESSION['usuario_id']) && isset($_SESSION['nivel']);
$eh_admin = $logado && $_SESSION['nivel'] === 'admin';

// Se não estiver logado como admin, redireciona para login
if (!$eh_admin) {
    header("Location: login.php?erro=invalido");
    exit();
}

// Dados do admin logado
$nome_admin = htmlspecialchars($_SESSION['nome'] ?? 'Admin');
$email_admin = htmlspecialchars($_SESSION['email'] ?? 'admin@wave.com.br');
$id_admin = $_SESSION['usuario_id'];

// 🚪 LOGOUT
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// 📝 PROCESSAR FORMULÁRIO DE CADASTRO DE PRODUTO
$msg_produto = '';
$tipo_msg_produto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_produto'])) {
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $preco = floatval($_POST['preco'] ?? 0);
    $preco_promo = $_POST['preco_promo'] ? floatval($_POST['preco_promo']) : null;
    $estoque = intval($_POST['estoque'] ?? 0);
    $estoque_min = intval($_POST['estoque_min'] ?? 5);
    $imagem_url = trim($_POST['imagem_url'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Validações
    if (!$nome || !$categoria || $preco <= 0) {
        $msg_produto = '❌ Preencha todos os campos obrigatórios corretamente!';
        $tipo_msg_produto = 'erro';
    } elseif ($preco_promo && $preco_promo >= $preco) {
        $msg_produto = '❌ O preço promocional deve ser menor que o preço original!';
        $tipo_msg_produto = 'erro';
    } else {
        // Inserir no banco
        $stmt = $conn->prepare("
            INSERT INTO produtos 
            (nome, imagem_url, descricao, preco, preco_promo, estoque, estoque_min, categoria, sku, destaque, ativo, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sssddiiisis",
            $nome,
            $imagem_url,
            $descricao,
            $preco,
            $preco_promo,
            $estoque,
            $estoque_min,
            $categoria,
            $sku,
            $destaque,
            $ativo
        );

        if ($stmt->execute()) {
            $msg_produto = '✅ Produto cadastrado com sucesso!';
            $tipo_msg_produto = 'sucesso';
            $_POST = [];
        } else {
            $msg_produto = '❌ Erro ao cadastrar: ' . $conn->error;
            $tipo_msg_produto = 'erro';
        }
    }
}

// 📊 BUSCAR DADOS DO BANCO PARA MÉTRICAS
$metricas = [
    'total_produtos' => 0,
    'produtos_ativos' => 0,
    'total_estoque' => 0,
    'estoque_critico' => 0,
    'total_clientes' => 0,
];

$resultado = $conn->query("SELECT COUNT(*) as total FROM produtos");
$metricas['total_produtos'] = $resultado->fetch_assoc()['total'];

$resultado = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE ativo = 1");
$metricas['produtos_ativos'] = $resultado->fetch_assoc()['total'];

$resultado = $conn->query("SELECT SUM(estoque) as total FROM produtos");
$row = $resultado->fetch_assoc();
$metricas['total_estoque'] = $row['total'] ?? 0;

$resultado = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE estoque <= estoque_min AND ativo = 1");
$metricas['estoque_critico'] = $resultado->fetch_assoc()['total'];

$resultado = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE nivel = 'usuario'");
$metricas['total_clientes'] = $resultado->fetch_assoc()['total'];

// LISTAR CLIENTES
$clientes = [];
$resultado = $conn->query("SELECT id, nome, email, telefone, criado_em FROM usuarios WHERE nivel = 'usuario' ORDER BY criado_em DESC LIMIT 50");
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $clientes[] = $row;
    }
}

// LISTAR PRODUTOS RECENTES
$produtos = [];
$resultado = $conn->query("SELECT id, nome, preco, estoque, categoria, ativo, destaque FROM produtos ORDER BY criado_em DESC LIMIT 50");
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $produtos[] = $row;
    }
}

// LISTAR VENDAS COM DETALHES
$vendas = [];
$resultado = $conn->query("
    SELECT 
        v.id,
        v.numero_venda,
        v.data_venda,
        v.total_valor,
        v.total_itens,
        v.status,
        COALESCE(u.nome, 'Cliente Anônimo') as cliente_nome,
        COALESCE(u.email, 'N/A') as cliente_email,
        GROUP_CONCAT(p.nome SEPARATOR ', ') as produtos_nomes
    FROM vendas v
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN vendas_itens vi ON v.id = vi.venda_id
    LEFT JOIN produtos p ON vi.produto_id = p.id
    GROUP BY v.id
    ORDER BY v.data_venda DESC
    LIMIT 100
");
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $vendas[] = $row;
    }
}

// ESTATÍSTICAS DE VENDAS
$stats_vendas = [
    'total_vendas' => 0,
    'valor_total' => 0,
    'ticket_medio' => 0,
];

$res = $conn->query("SELECT COUNT(*) as total FROM vendas");
$stats_vendas['total_vendas'] = $res->fetch_assoc()['total'];

$res = $conn->query("SELECT SUM(total_valor) as total FROM vendas");
$row = $res->fetch_assoc();
$stats_vendas['valor_total'] = $row['total'] ?? 0;

if ($stats_vendas['total_vendas'] > 0) {
    $stats_vendas['ticket_medio'] = $stats_vendas['valor_total'] / $stats_vendas['total_vendas'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Wave Admin Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --blue:#0A5489;
      --cream:#FFF3E7;
      --white:#FEFCF9;
      --text:#1a2e3b;
      --muted:#7a8d99;
      --green:#16a34a;
      --red:#dc2626;
      --yellow:#d97706;
      --gold:#C8963E;
      --font-display:'Cormorant Garamond',Georgia,serif;
      --font-body:'DM Sans',sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-body); background: #f8f9fa; color: var(--text); }
    a { text-decoration: none; color: inherit; }
    
    /* LAYOUT */
    .container { display: flex; min-height: 100vh; }
    
    /* SIDEBAR */
    .sidebar { 
      width: 220px; 
      background: var(--white); 
      border-right: 1px solid #e5e7eb; 
      padding: 2rem 1rem; 
      overflow-y: auto;
    }
    .sidebar-logo { 
      font-size: 1.3rem; 
      font-weight: 700; 
      color: var(--blue); 
      margin-bottom: 2.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .sidebar-menu { display: flex; flex-direction: column; gap: 0.3rem; }
    .menu-item { 
      padding: 0.8rem 1rem; 
      border-radius: 0.5rem; 
      cursor: pointer; 
      font-size: 0.9rem; 
      color: var(--muted); 
      transition: all .3s;
      display: flex;
      align-items: center;
      gap: 0.7rem;
    }
    .menu-item:hover { background: #f0f5f8; color: var(--blue); }
    .menu-item.active { background: #e0ecf6; color: var(--blue); font-weight: 600; }
    
    .sidebar-footer { 
      margin-top: auto; 
      padding-top: 1.5rem; 
      border-top: 1px solid #e5e7eb;
    }
    
    /* MAIN */
    .main { flex: 1; padding: 2rem; overflow-y: auto; }
    
    /* HEADER */
    .header { 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      margin-bottom: 2rem;
      gap: 2rem;
    }
    .header-logo { 
      display: flex; 
      align-items: center; 
      gap: 1rem;
    }
    .header-logo img { 
      height: 60px; 
      width: auto;
    }
    .header-info h1 { 
      font-size: 2rem; 
      color: var(--blue); 
      margin-bottom: 0.3rem;
    }
    .header-info p { 
      font-size: 0.85rem; 
      color: var(--muted);
    }
    .header-right { 
      display: flex; 
      align-items: center; 
      gap: 1rem;
      margin-left: auto;
    }
    .btn-filtros { 
      padding: 0.6rem 1.5rem; 
      background: var(--blue); 
      color: white; 
      border: none;
      border-radius: 0.5rem; 
      cursor: pointer; 
      font-size: 0.9rem;
      font-weight: 600;
      transition: all .3s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-filtros:hover { opacity: 0.9; }
    
    /* ALERT */
    .alert { 
      padding: 1rem; 
      border-radius: 0.6rem; 
      margin-bottom: 1rem;
      border-left: 4px solid;
    }
    .alert.sucesso { 
      background: #f0fdf4; 
      border-left-color: var(--green); 
      color: var(--green);
    }
    .alert.erro { 
      background: #fef2f2; 
      border-left-color: var(--red); 
      color: var(--red);
    }
    
    /* METRICS */
    .metrics { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
      gap: 1rem; 
      margin-bottom: 2rem;
    }
    .metric { 
      background: var(--white); 
      padding: 1.5rem; 
      border-radius: 0.6rem; 
      border: 1px solid #e5e7eb;
    }
    .metric-label { 
      font-size: 0.8rem; 
      color: var(--muted); 
      margin-bottom: 0.5rem;
      font-weight: 600;
    }
    .metric-value { 
      font-size: 2rem; 
      color: var(--blue); 
      font-weight: 700;
    }
    .metric-sub { 
      font-size: 0.8rem; 
      color: var(--muted); 
      margin-top: 0.3rem;
    }
    
    /* CARDS */
    .card { 
      background: var(--white); 
      border-radius: 0.6rem; 
      border: 1px solid #e5e7eb; 
      margin-bottom: 1.5rem;
      overflow: hidden;
    }
    .card-header { 
      padding: 1.5rem; 
      border-bottom: 1px solid #e5e7eb; 
      display: flex; 
      justify-content: space-between;
      align-items: center;
    }
    .card-header h2 { 
      font-size: 1.2rem; 
      color: var(--text);
    }
    .card-body { padding: 1.5rem; }
    
    /* TABLE */
    table { 
      width: 100%; 
      border-collapse: collapse; 
      font-size: 0.9rem;
    }
    thead { background: #f8f9fa; }
    th { 
      padding: 1rem; 
      text-align: left; 
      font-weight: 600; 
      color: var(--text); 
      font-size: 0.8rem;
    }
    td { 
      padding: 1rem; 
      border-bottom: 1px solid #e5e7eb;
    }
    tbody tr:hover { background: #f8f9fa; }
    
    .badge { 
      display: inline-block; 
      padding: 0.4rem 0.8rem; 
      border-radius: 0.4rem; 
      font-size: 0.75rem; 
      font-weight: 600;
    }
    .badge-green { background: #dffcf0; color: var(--green); }
    .badge-red { background: #fee2e2; color: var(--red); }
    .badge-yellow { background: #fef3c7; color: var(--yellow); }
    .badge-blue { background: #dbeafe; color: #1e40af; }
    
    /* EMPTY */
    .empty { 
      text-align: center; 
      padding: 2rem; 
      color: var(--muted);
    }
    .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; }
    
    /* PAGES */
    .page { display: none; }
    .page.active { display: block; }
    
    /* FORM STYLES */
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; font-size: .8rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .5rem; }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-row.full { grid-template-columns: 1fr; }
    
    input[type="text"],
    input[type="email"],
    input[type="number"],
    input[type="url"],
    textarea,
    select {
      width: 100%;
      padding: .75rem 1rem;
      border: 1px solid #e5e7eb;
      border-radius: .4rem;
      font-family: var(--font-body);
      font-size: .9rem;
      color: var(--text);
    }
    
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus,
    input[type="url"]:focus,
    textarea:focus,
    select:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(10,84,137,0.1);
    }
    
    textarea { resize: vertical; min-height: 100px; }
    
    .checkbox-group { display: flex; gap: 2rem; margin-top: .5rem; flex-wrap: wrap; }
    .checkbox-item { display: flex; align-items: center; gap: .5rem; }
    .checkbox-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--blue); }
    .checkbox-item label { margin: 0; cursor: pointer; font-size: .9rem; color: var(--text); }
    
    .price-info { background: rgba(10,84,137,.05); padding: 1rem; border-radius: .4rem; border-left: 4px solid var(--gold); margin-top: .5rem; font-size: .85rem; color: var(--muted); }
    
    .button-group { display: flex; gap: 1rem; margin-top: 2rem; }
    
    button { border: none; padding: .75rem 1.5rem; border-radius: .4rem; font-family: var(--font-body); font-weight: 600; font-size: .9rem; cursor: pointer; transition: all .3s; }
    
    .btn-primary { background: var(--blue); color: white; }
    .btn-primary:hover { opacity: 0.9; }
    
    .btn-secondary { background: #f0f5f8; color: var(--muted); border: 1px solid #e5e7eb; }
    .btn-secondary:hover { background: #e0ecf6; }
    
    /* MODAL */
    .modal { 
      display: none; 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      background: rgba(0,0,0,0.5); 
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    .modal.active { display: flex; }
    
    .modal-content { 
      background: var(--white); 
      border-radius: 0.8rem; 
      width: 90%; 
      max-width: 500px; 
      padding: 2rem;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .modal-header { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 1.5rem;
    }
    .modal-header h2 { 
      font-size: 1.3rem; 
      color: var(--text);
    }
    .modal-close { 
      background: none; 
      border: none; 
      font-size: 1.5rem; 
      cursor: pointer; 
      color: var(--muted);
    }
    .modal-close:hover { color: var(--text); }
    
    .filter-group { 
      margin-bottom: 1.5rem;
    }
    .filter-label { 
      font-size: 0.85rem; 
      font-weight: 600; 
      color: var(--text); 
      margin-bottom: 0.5rem;
      display: block;
    }
    .filter-input, .filter-select { 
      width: 100%; 
      padding: 0.7rem; 
      border: 1px solid #e5e7eb; 
      border-radius: 0.4rem; 
      font-size: 0.9rem;
      font-family: var(--font-body);
    }
    .filter-input[type="date"] {
      cursor: pointer;
      caret-color: transparent;
    }
    .filter-input:focus, .filter-select:focus { 
      outline: none; 
      border-color: var(--blue); 
      box-shadow: 0 0 0 3px rgba(10,84,137,0.1);
    }
    
    .filter-buttons { 
      display: flex; 
      gap: 1rem; 
      margin-top: 2rem;
    }
    
    /* RESPONSIVE */
    @media(max-width: 768px) {
      .container { flex-direction: column; }
      .sidebar { width: 100%; padding: 1rem; }
      .main { padding: 1rem; }
      .metrics { grid-template-columns: 1fr; }
      .header { flex-direction: column; align-items: flex-start; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="container">
  
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <span></span>
      
    </div>
    
    <!-- HOME BUTTON DESTACADO -->
    <a href="index.php" style="display: block; padding: 1rem; background: linear-gradient(135deg, var(--green) 0%, #0d9488 100%); color: white; border-radius: 0.6rem; text-align: center; font-weight: 700; font-size: 0.95rem; margin-bottom: 1.5rem; text-decoration: none; transition: all .3s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(22, 163, 74, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(22, 163, 74, 0.3)'">
      🏠 Voltar à Home
    </a>
    
    <nav class="sidebar-menu">
      <div class="menu-item active" onclick="showPage('dashboard', this)">
        <span>📊</span> Dashboard
      </div>
      <div class="menu-item" onclick="showPage('vendas', this)">
        <span>🛍️</span> Vendas
      </div>
      <div class="menu-item" onclick="showPage('produtos', this)">
        <span>📦</span> Produtos
      </div>
      <div class="menu-item" onclick="showPage('clientes', this)">
        <span>👥</span> Clientes
      </div>
      <div class="menu-item" onclick="showPage('estoque', this)">
        <span>📋</span> Estoque
      </div>
      <a href="javascript:void(0)" class="menu-item" onclick="showPage('cadastro-produto', this)">
        <span>➕</span> Novo Produto
      </a>
      <div class="menu-item" onclick="showPage('config', this)">
        <span>⚙️</span> Configurações
      </div>
    </nav>
    
    <div class="sidebar-footer">
      <div style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem;">
        <div style="font-weight: 600; font-size: 0.85rem;"><?= $nome_admin ?></div>
        <div style="font-size: 0.75rem; color: var(--muted);">Admin</div>
      </div>
      <a href="admin_dashboard.php?sair=1" style="padding: 0.6rem 1rem; text-align: center; background: #fef2f2; color: var(--red); border-radius: 0.4rem; font-weight: 600; display: block;">🚪 Sair</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">
    
    <!-- HEADER -->
    <div class="header">
      <div class="header-logo">
        <img src="logo-removebg-preview.png" alt="Wave">
      </div>
      <div class="header-info">
        <h1>Dashboard</h1>
        <p id="date-info">Carregando...</p>
      </div>
      <div class="header-right">
        <button class="btn-filtros" onclick="abrirFiltros()">
          🎚️ Filtros
        </button>
      </div>
    </div>

    <!-- FILTROS ATIVOS -->
    <div id="filtros-ativos-container" style="display: none; margin-bottom: 1.5rem;">
      <div style="padding: 1rem; background: #f0fdf4; border-radius: 0.6rem; border: 1px solid #bbf7d0;">
        <div style="font-size: 0.85rem; color: var(--muted); font-weight: 600; margin-bottom: 0.5rem;">Filtros ativos:</div>
        <div id="filtros-ativos-display" style="display: flex; gap: 0.5rem; flex-wrap: wrap;"></div>
      </div>
    </div>

    <!-- PAGE: DASHBOARD -->
    <div id="page-dashboard" class="page active">
      
      <!-- MÉTRICAS -->
      <div class="metrics">
        <div class="metric">
          <div class="metric-label">Faturamento</div>
          <div class="metric-value" id="metric-faturamento">R$ <?= number_format($stats_vendas['valor_total'], 0, ',', '.') ?></div>
          <div class="metric-sub">Total de vendas</div>
        </div>
        
        <div class="metric">
          <div class="metric-label">Comissões</div>
          <div class="metric-value">R$ 0,00</div>
          <div class="metric-sub">Não aplicável</div>
        </div>
        
        <div class="metric">
          <div class="metric-label">Vendas</div>
          <div class="metric-value" id="metric-vendas"><?= $stats_vendas['total_vendas'] ?></div>
          <div class="metric-sub">Total de transações</div>
        </div>
      </div>

      <!-- RESUMO -->
      <div class="card">
        <div class="card-header">
          <h2>Resumo do Período</h2>
        </div>
        <div class="card-body">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
            <div>
              <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem;">Clientes</div>
              <div style="font-size: 2rem; font-weight: 700; color: var(--blue);" id="metric-clientes"><?= $metricas['total_clientes'] ?></div>
            </div>
            <div>
              <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem;">Produtos</div>
              <div style="font-size: 2rem; font-weight: 700; color: var(--blue);" id="metric-produtos"><?= $metricas['total_produtos'] ?></div>
            </div>
            <div>
              <div style="font-size: 0.85rem; color: var(--muted); margin-bottom: 0.5rem;">Ticket Médio</div>
              <div style="font-size: 2rem; font-weight: 700; color: var(--gold);" id="metric-ticket">R$ <?= number_format($stats_vendas['ticket_medio'], 2, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGE: VENDAS -->
    <div id="page-vendas" class="page">
      <div class="card">
        <div class="card-header">
          <h2>📊 Histórico de Vendas</h2>
        </div>
        <div class="card-body">
          <?php if (!empty($vendas)): ?>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Venda #</th>
                    <th>Cliente</th>
                    <th>E-mail</th>
                    <th>Total</th>
                    <th>Data</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($vendas as $venda): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($venda['numero_venda']) ?></strong></td>
                      <td><?= htmlspecialchars($venda['cliente_nome']) ?></td>
                      <td><?= htmlspecialchars($venda['cliente_email']) ?></td>
                      <td><strong>R$ <?= number_format($venda['total_valor'], 2, ',', '.') ?></strong></td>
                      <td><?= date('d/m/Y', strtotime($venda['data_venda'])) ?></td>
                      <td>
                        <span class="badge <?php
                          $status = $venda['status'];
                          echo ($status === 'entregue') ? 'badge-green' : (($status === 'confirmada') ? 'badge-blue' : (($status === 'enviada') ? 'badge-yellow' : 'badge-red'));
                        ?>">
                          <?= ucfirst($venda['status']) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty">
              <div class="empty-icon">🛍️</div>
              <p>Nenhuma venda registrada</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PAGE: PRODUTOS -->
    <div id="page-produtos" class="page">
      <div class="card">
        <div class="card-header">
          <h2>📦 Produtos</h2>
          <button onclick="showPage('cadastro-produto')" style="padding: 0.6rem 1.2rem; background: var(--blue); color: white; border-radius: 0.5rem; font-weight: 600; border: none; cursor: pointer;">➕ Novo</button>
        </div>
        <div class="card-body">
          <?php if (!empty($produtos)): ?>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Estoque</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($produtos as $prod): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($prod['nome']) ?></strong></td>
                      <td><?= htmlspecialchars($prod['categoria']) ?></td>
                      <td>R$ <?= number_format($prod['preco'], 2, ',', '.') ?></td>
                      <td><?= $prod['estoque'] ?> un.</td>
                      <td>
                        <span class="badge <?= $prod['ativo'] ? 'badge-green' : 'badge-red' ?>">
                          <?= $prod['ativo'] ? '✅ Ativo' : '❌ Inativo' ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty">
              <div class="empty-icon">📦</div>
              <p>Nenhum produto cadastrado</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PAGE: CLIENTES -->
    <div id="page-clientes" class="page">
      <div class="card">
        <div class="card-header">
          <h2>👥 Clientes</h2>
        </div>
        <div class="card-body">
          <?php if (!empty($clientes)): ?>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Data Cadastro</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($clientes as $cliente): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($cliente['nome']) ?></strong></td>
                      <td><?= htmlspecialchars($cliente['email']) ?></td>
                      <td><?= htmlspecialchars($cliente['telefone'] ?? '-') ?></td>
                      <td><?= date('d/m/Y', strtotime($cliente['criado_em'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty">
              <div class="empty-icon">👥</div>
              <p>Nenhum cliente cadastrado</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PAGE: ESTOQUE -->
    <div id="page-estoque" class="page">
      <div class="card">
        <div class="card-header">
          <h2>📋 Estoque</h2>
        </div>
        <div class="card-body">
          <?php if (!empty($produtos)): ?>
            <div style="overflow-x: auto;">
              <table>
                <thead>
                  <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Estoque</th>
                    <th>Mínimo</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($produtos as $prod):
                    $estoque = $prod['estoque'];
                    $minimo = 5;
                    $status = 'badge-green';
                    $msg = '✅ OK';
                    if ($estoque === 0) { $status = 'badge-red'; $msg = '❌ Esgotado'; }
                    elseif ($estoque <= $minimo) { $status = 'badge-yellow'; $msg = '⚠️ Crítico'; }
                  ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($prod['nome']) ?></strong></td>
                      <td><?= htmlspecialchars($prod['categoria']) ?></td>
                      <td><?= $estoque ?> un.</td>
                      <td><?= $minimo ?> un.</td>
                      <td><span class="badge <?= $status ?>"><?= $msg ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty">
              <div class="empty-icon">📋</div>
              <p>Nenhum produto</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PAGE: CADASTRO DE PRODUTO -->
    <div id="page-cadastro-produto" class="page">
      <div class="card">
        <div class="card-header">
          <h2>➕ Novo Produto</h2>
        </div>
        <div class="card-body">
          
          <?php if ($msg_produto): ?>
            <div class="alert <?= $tipo_msg_produto ?>">
              <?= $msg_produto ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <input type="hidden" name="acao_produto" value="1">
            
            <!-- INFORMAÇÕES BÁSICAS -->
            <div style="margin-bottom: 2rem;">
              <h3 style="font-size: 1.1rem; color: var(--blue); margin-bottom: 1rem;">📦 Informações Básicas</h3>
              
              <div class="form-group">
                <label>Nome do Produto *</label>
                <input type="text" name="nome" placeholder="Ex: Pulseira Ondas do Mar" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Categoria *</label>
                  <select name="categoria" required>
                    <option value="">Selecione...</option>
                    <option value="Colares" <?= ($_POST['categoria'] ?? '') === 'Colares' ? 'selected' : '' ?>>🐚 Colares</option>
                    <option value="Pulseiras" <?= ($_POST['categoria'] ?? '') === 'Pulseiras' ? 'selected' : '' ?>>🌊 Pulseiras</option>
                    <option value="Brincos" <?= ($_POST['categoria'] ?? '') === 'Brincos' ? 'selected' : '' ?>>🌺 Brincos</option>
                    <option value="Anéis" <?= ($_POST['categoria'] ?? '') === 'Anéis' ? 'selected' : '' ?>>💍 Anéis</option>
                    <option value="Kits" <?= ($_POST['categoria'] ?? '') === 'Kits' ? 'selected' : '' ?>>🎁 Kits Presente</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>SKU (Código)</label>
                  <input type="text" name="sku" placeholder="Ex: WAV-001" value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>">
                </div>
              </div>

              <div class="form-group">
                <label>Descrição *</label>
                <textarea name="descricao" placeholder="Descreva o produto..." required><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- PREÇOS -->
            <div style="margin-bottom: 2rem;">
              <h3 style="font-size: 1.1rem; color: var(--blue); margin-bottom: 1rem;">💰 Preços</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Preço Original (R$) *</label>
                  <input type="number" name="preco" placeholder="99.90" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>">
                </div>

                <div class="form-group">
                  <label>Preço Promocional (R$)</label>
                  <input type="number" name="preco_promo" placeholder="79.90" step="0.01" min="0" value="<?= htmlspecialchars($_POST['preco_promo'] ?? '') ?>" oninput="atualizarDesconto()">
                </div>
              </div>

              <div class="price-info">
                <div id="priceInfo">Digite os preços acima para ver o desconto</div>
              </div>
            </div>

            <!-- IMAGEM -->
            <div style="margin-bottom: 2rem;">
              <h3 style="font-size: 1.1rem; color: var(--blue); margin-bottom: 1rem;">🖼️ Imagem</h3>
              
              <div class="form-group">
                <label>URL da Imagem</label>
                <input type="url" name="imagem_url" placeholder="https://..." value="<?= htmlspecialchars($_POST['imagem_url'] ?? '') ?>">
              </div>

              <?php if (!empty($_POST['imagem_url'])): ?>
                <div class="price-info">
                  <strong>Preview:</strong><br>
                  <img src="<?= htmlspecialchars($_POST['imagem_url']) ?>" alt="Preview" style="max-width: 150px; margin-top: .5rem; border-radius: .4rem;">
                </div>
              <?php endif; ?>
            </div>

            <!-- ESTOQUE -->
            <div style="margin-bottom: 2rem;">
              <h3 style="font-size: 1.1rem; color: var(--blue); margin-bottom: 1rem;">📊 Estoque</h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label>Quantidade *</label>
                  <input type="number" name="estoque" placeholder="100" min="0" required value="<?= htmlspecialchars($_POST['estoque'] ?? '0') ?>">
                </div>

                <div class="form-group">
                  <label>Estoque Mínimo</label>
                  <input type="number" name="estoque_min" placeholder="5" min="0" value="<?= htmlspecialchars($_POST['estoque_min'] ?? '5') ?>">
                </div>
              </div>
            </div>

            <!-- CONFIGURAÇÕES -->
            <div style="margin-bottom: 2rem;">
              <h3 style="font-size: 1.1rem; color: var(--blue); margin-bottom: 1rem;">⚙️ Configurações</h3>
              
              <div class="checkbox-group">
                <div class="checkbox-item">
                  <input type="checkbox" id="destaque" name="destaque" <?= !empty($_POST['destaque']) ? 'checked' : '' ?>>
                  <label for="destaque">⭐ Destacado na Home</label>
                </div>

                <div class="checkbox-item">
                  <input type="checkbox" id="ativo" name="ativo" checked <?= !isset($_POST['ativo']) || !empty($_POST['ativo']) ? 'checked' : '' ?>>
                  <label for="ativo">✅ Ativo</label>
                </div>
              </div>
            </div>

            <!-- BOTÕES -->
            <div class="button-group">
              <button type="submit" class="btn-primary">💾 Salvar Produto</button>
              <button type="button" class="btn-secondary" onclick="showPage('dashboard')">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PAGE: CONFIG -->
    <div id="page-config" class="page">
      <div class="card">
        <div class="card-header">
          <h2>⚙️ Configurações</h2>
        </div>
        <div class="card-body">
          <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
              <span style="color: var(--muted);">Nome</span>
              <strong><?= $nome_admin ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
              <span style="color: var(--muted);">E-mail</span>
              <strong><?= $email_admin ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--muted);">ID</span>
              <strong><?= $id_admin ?></strong>
            </div>
            <a href="admin_dashboard.php?sair=1" style="padding: 0.8rem 1.5rem; background: var(--red); color: white; border-radius: 0.5rem; text-align: center; font-weight: 600; margin-top: 1rem;">🚪 Sair da Conta</a>
          </div>
        </div>
      </div>
    </div>

  </main>

</div>

<!-- MODAL DE FILTROS -->
<div id="modal-filtros" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>🎚️ Filtros</h2>
      <button class="modal-close" onclick="fecharFiltros()">✕</button>
    </div>

    <div class="filter-group">
      <label class="filter-label">Por data</label>
      <div style="display: flex; gap: 0.5rem;">
        <input type="date" id="data-inicio" class="filter-input">
        <input type="date" id="data-fim" class="filter-input">
      </div>
    </div>

    <div class="filter-group">
      <label class="filter-label">Status da venda</label>
      <select id="status-venda" class="filter-select">
        <option value="">Todos</option>
        <option value="pendente">⏳ Pendente</option>
        <option value="confirmada">📦 Confirmada</option>
        <option value="enviada">🚚 Enviada</option>
        <option value="entregue">✅ Entregue</option>
        <option value="cancelada">❌ Cancelada</option>
      </select>
    </div>

    <div class="filter-buttons">
      <button class="btn-primary" onclick="aplicarFiltros()" style="flex: 1;">✓ Filtrar</button>
      <button class="btn-secondary" onclick="limparFiltros()">Limpar</button>
    </div>
  </div>
</div>

<script>
  // Dados iniciais
  let filtroAtivo = false;
  let filtroDataInicio = '';
  let filtroDataFim = '';
  let filtroStatus = '';

  // Definir data máxima como hoje
  function inicializarDatas() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    const dataHoje = `${ano}-${mes}-${dia}`;
    
    document.getElementById('data-inicio').max = dataHoje;
    document.getElementById('data-fim').max = dataHoje;
  }
  
  inicializarDatas();

  // Atualizar data
  function updateDate() {
    const dias = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
    const meses = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
    const d = new Date();
    document.getElementById('date-info').textContent = `${dias[d.getDay()]}, ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
  }
  updateDate();

  // Navegar entre páginas
  function showPage(page, btn) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    
    document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
    if (btn) btn.classList.add('active');
  }

  // FILTROS MODAL
  function abrirFiltros() {
    document.getElementById('modal-filtros').classList.add('active');
  }

  function fecharFiltros() {
    document.getElementById('modal-filtros').classList.remove('active');
  }

  function aplicarFiltros() {
    const dataInicio = document.getElementById('data-inicio').value;
    const dataFim = document.getElementById('data-fim').value;
    const statusVenda = document.getElementById('status-venda').value;

    if (!dataInicio || !dataFim) {
      alert('⚠️ Selecione as datas!');
      return;
    }

    if (dataInicio > dataFim) {
      alert('⚠️ Data início não pode ser maior que data fim!');
      return;
    }

    // Armazenar filtros
    filtroDataInicio = dataInicio;
    filtroDataFim = dataFim;
    filtroStatus = statusVenda;
    filtroAtivo = true;

    fetch('filtro_metricas.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `acao=filtrar&data_inicio=${dataInicio}&data_fim=${dataFim}&status=${statusVenda}`
    })
    .then(r => r.json())
    .then(data => {
      if (data.erro) {
        alert('❌ ' + data.erro);
        return;
      }
      
      document.getElementById('metric-faturamento').textContent = 'R$ ' + data.faturamento.toLocaleString('pt-BR', {maximumFractionDigits: 0});
      document.getElementById('metric-vendas').textContent = data.vendas;
      document.getElementById('metric-ticket').textContent = 'R$ ' + data.ticket_medio.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('metric-clientes').textContent = data.clientes;
      
      // Mostrar filtros ativos
      mostrarFiltrosAtivos();
      
      fecharFiltros();
      alert('✅ Filtros aplicados com sucesso!');
    })
    .catch(e => alert('❌ Erro ao aplicar filtros'));
  }

  function limparFiltros() {
    document.getElementById('data-inicio').value = '';
    document.getElementById('data-fim').value = '';
    document.getElementById('status-venda').value = '';
    filtroAtivo = false;
    location.reload();
  }

  function mostrarFiltrosAtivos() {
    const container = document.getElementById('filtros-ativos-container');
    const display = document.getElementById('filtros-ativos-display');
    
    if (!filtroAtivo) {
      container.style.display = 'none';
      return;
    }

    let tags = [];
    
    if (filtroDataInicio && filtroDataFim) {
      const dataInicio = new Date(filtroDataInicio).toLocaleDateString('pt-BR');
      const dataFim = new Date(filtroDataFim).toLocaleDateString('pt-BR');
      tags.push(`📅 Data: ${dataInicio} a ${dataFim}`);
    }
    
    if (filtroStatus) {
      const statusLabels = {
        'pendente': '⏳ Pendente',
        'confirmada': '📦 Confirmada',
        'enviada': '🚚 Enviada',
        'entregue': '✅ Entregue',
        'cancelada': '❌ Cancelada'
      };
      tags.push(`Status: ${statusLabels[filtroStatus] || filtroStatus}`);
    }

    display.innerHTML = tags.map(tag => `<span style="background: #bbf7d0; color: #166534; padding: 0.4rem 0.8rem; border-radius: 0.3rem; font-size: 0.8rem; font-weight: 600;">${tag}</span>`).join('');
    container.style.display = 'block';
  }

  // Fechar modal ao clicar fora
  document.getElementById('modal-filtros').addEventListener('click', (e) => {
    if (e.target.id === 'modal-filtros') fecharFiltros();
  });

  // Função de desconto de produtos
  function atualizarDesconto() {
    const preco = parseFloat(document.querySelector('input[name="preco"]').value) || 0;
    const promo = parseFloat(document.querySelector('input[name="preco_promo"]').value) || null;
    const infoDiv = document.getElementById('priceInfo');

    if (!promo) {
      infoDiv.innerHTML = '<strong>Sem promoção</strong>';
      return;
    }

    if (promo >= preco) {
      infoDiv.innerHTML = '<strong style="color: #dc2626">⚠️ Erro:</strong> Preço promo deve ser menor';
      return;
    }

    const desconto = ((preco - promo) / preco * 100).toFixed(1);
    const economia = (preco - promo).toFixed(2);

    infoDiv.innerHTML = `<strong>Original:</strong> R$ ${preco.toFixed(2).replace('.', ',')}<br><strong style="color: var(--gold)">Promo:</strong> R$ ${promo.toFixed(2).replace('.', ',')}<br><strong style="color: #16a34a">Desconto: ${desconto}%</strong>`;
  }

  window.addEventListener('DOMContentLoaded', atualizarDesconto);
</script>

</body>
</html>