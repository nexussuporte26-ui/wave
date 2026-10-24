<?php
session_start();
require_once 'conexao.php';

// VERIFICAÇÃO DE AUTENTICAÇÃO
$logado = isset($_SESSION['usuario_id']) && isset($_SESSION['nivel']);
$eh_admin = $logado && $_SESSION['nivel'] === 'admin';

// Se não estiver logado como admin, redireciona para login
if (!$eh_admin) {
    header("Location: login.php?erro=invalido");
    exit();
}

// Token CSRF usado pela aba Clientes (api_usuarios.php)
if (empty($_SESSION['csrf_usuarios'])) { $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(32)); }
$csrf_usuarios = $_SESSION['csrf_usuarios'];

// Dados do admin logado
$nome_admin = htmlspecialchars($_SESSION['nome'] ?? 'Admin');
$email_admin = htmlspecialchars($_SESSION['email'] ?? 'admin@wave.com.br');
$id_admin = $_SESSION['usuario_id'];

// LOGOUT
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// CADASTRO/EDIÇÃO DE PRODUTO
// Unificado em cadastro_produto.php (upload de imagem real, edição).
// O Dashboard só lista e linka para lá — evita ter dois formulários de
// produto divergentes gravando em colunas diferentes.
// (ação rápida de desativar direto na tabela é feita via
// api_produtos.php, chamada pelo JS mais abaixo)

// BUSCAR DADOS DO BANCO PARA MÉTRICAS
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
$resultado = $conn->query("SELECT id, nome, preco, estoque, categoria, ativo FROM produtos ORDER BY criado_em DESC LIMIT 50");
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
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300..800&display=swap" rel="stylesheet" />
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
      --font-display:'Manrope',system-ui,sans-serif;
      --font-body:'Manrope',system-ui,sans-serif;
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
    
    /* ── CLIENTES: gerenciamento de usuários ── */
    .u-bar { display: flex; gap: .8rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .u-bar input { flex: 1; min-width: 220px; }
    .u-bar select { width: auto; min-width: 160px; }
    .u-inp { width: 100%; padding: .75rem 1rem; border: 1px solid #e5e7eb; border-radius: .4rem; font-family: var(--font-body); font-size: .9rem; color: var(--text); background: #fff; }
    .u-inp:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(10,84,137,0.1); }
    .u-inp:disabled { background: #f3f4f6; color: var(--muted); }
    .badge-purple { background: #ede9fe; color: #6d28d9; }
    .u-btn-sm { padding: .4rem .9rem; font-size: .8rem; }
    .u-pager { display: flex; align-items: center; justify-content: center; gap: 1rem; padding-top: 1.2rem; font-size: .85rem; color: var(--muted); }
    .u-info { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem 1rem; background: #f8f9fa; border-radius: .4rem; padding: .8rem 1rem; margin-bottom: 1.2rem; font-size: .85rem; }
    .u-info small { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); font-weight: 700; }
    .u-msg { border-radius: .4rem; padding: .7rem 1rem; font-size: .85rem; font-weight: 500; margin-bottom: 1rem; line-height: 1.5; }
    .u-msg.ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    .u-msg.er { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
    .u-sec { border-top: 1px solid #e5e7eb; margin-top: 1.5rem; padding-top: 1.2rem; }
    .u-sec h3 { font-size: 1rem; color: var(--text); margin-bottom: .3rem; }
    .u-hint { font-size: .78rem; color: var(--muted); line-height: 1.5; margin-bottom: .8rem; }
    .u-row { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; }
    .u-row .u-inp { flex: 1; min-width: 160px; }
    .u-novasenha { background: #fffbeb; border: 1px solid #fde68a; border-radius: .4rem; padding: .8rem 1rem; margin-top: .8rem; font-size: .85rem; color: #92400e; }
    .u-novasenha code { background: #fff; padding: .2rem .5rem; border-radius: .3rem; font-size: .95rem; font-weight: 700; letter-spacing: .04em; color: var(--text); user-select: all; }
    .u-log-item { padding: .5rem 0; border-bottom: 1px solid #f0f0f0; font-size: .8rem; line-height: 1.5; }
    .u-log-item:last-child { border-bottom: none; }
    .u-log-item em { font-style: normal; color: var(--muted); }
    @media (max-width: 600px) { .u-info { grid-template-columns: 1fr; } }

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
    @media(max-width: 480px) {
      .header-logo img { height: 44px; }
      .header-info h1 { font-size: 1.5rem; }
      .header-right { margin-left: 0; }
      .metric { padding: 1.1rem; }
      .metric-value { font-size: 1.5rem; }
      .card-header, .card-body { padding: 1.1rem; }
    }
  </style>
</head>
<body>

<div class="container">
  
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <span></span> Wave Admin
    </div>
    
    <!-- HOME BUTTON DESTACADO -->
    <a href="index.php" style="display: block; padding: 1rem; background: linear-gradient(135deg, var(--green) 0%, #0d9488 100%); color: white; border-radius: 0.6rem; text-align: center; font-weight: 700; font-size: 0.95rem; margin-bottom: 1.5rem; text-decoration: none; transition: all .3s; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(22, 163, 74, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(22, 163, 74, 0.3)'">
       Voltar à Home
    </a>
    
    <nav class="sidebar-menu">
      <div class="menu-item active" onclick="showPage('dashboard', this)">
        <span></span> Dashboard
      </div>
      <div class="menu-item" onclick="showPage('vendas', this)">
        <span></span> Vendas
      </div>
      <div class="menu-item" onclick="showPage('produtos', this)">
        <span></span> Produtos
      </div>
      <div class="menu-item" onclick="showPage('clientes', this)">
        <span></span> Clientes
      </div>
      <div class="menu-item" onclick="showPage('estoque', this)">
        <span></span> Estoque
      </div>
      <a href="cadastro_produto.php" class="menu-item">
        <span></span> Novo Produto
      </a>
      <a href="categorias_admin.php" class="menu-item">
        <span></span> Categorias
      </a>
      <div class="menu-item" onclick="showPage('config', this)">
        <span></span> Configurações
      </div>
    </nav>
    
    <div class="sidebar-footer">
      <div style="padding: 0.5rem 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem;">
        <div style="font-weight: 600; font-size: 0.85rem;"><?= $nome_admin ?></div>
        <div style="font-size: 0.75rem; color: var(--muted);">Admin</div>
      </div>
      <a href="Dashboard.php?sair=1" style="padding: 0.6rem 1rem; text-align: center; background: #fef2f2; color: var(--red); border-radius: 0.4rem; font-weight: 600; display: block;"> Sair</a>
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
           Filtros
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
          <h2> Histórico de Vendas</h2>
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
              <div class="empty-icon"></div>
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
          <h2> Produtos</h2>
          <a href="cadastro_produto.php" style="padding: 0.6rem 1.2rem; background: var(--blue); color: white; border-radius: 0.5rem; font-weight: 600; display:inline-block;"> Novo</a>
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
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($produtos as $prod): ?>
                    <tr id="prod-row-<?= $prod['id'] ?>">
                      <td><strong><?= htmlspecialchars($prod['nome']) ?></strong></td>
                      <td><?= htmlspecialchars($prod['categoria']) ?></td>
                      <td>R$ <?= number_format($prod['preco'], 2, ',', '.') ?></td>
                      <td><?= $prod['estoque'] ?> un.</td>
                      <td>
                        <span class="badge <?= $prod['ativo'] ? 'badge-green' : 'badge-red' ?>">
                          <?= $prod['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                      </td>
                      <td style="white-space:nowrap">
                        <a href="cadastro_produto.php?id=<?= $prod['id'] ?>" style="color:var(--blue);font-weight:600;font-size:.82rem;margin-right:.8rem"> Editar</a>
                        <?php if ($prod['ativo']): ?>
                        <button type="button" onclick="desativarProduto(<?= $prod['id'] ?>, this)" style="border:none;background:none;color:var(--red);font-weight:600;font-size:.82rem;cursor:pointer;margin-right:.8rem"> Desativar</button>
                        <?php endif; ?>
                        <button type="button" onclick="excluirProduto(<?= $prod['id'] ?>, this)" style="border:none;background:none;color:#991b1b;font-weight:700;font-size:.82rem;cursor:pointer"> Excluir de vez</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty">
              <div class="empty-icon"></div>
              <p>Nenhum produto cadastrado</p>
              <a href="cadastro_produto.php" style="color:var(--blue);font-weight:600">Cadastrar o primeiro produto →</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PAGE: CLIENTES -->
    <div id="page-clientes" class="page">
      <div class="card">
        <div class="card-header">
          <h2> Clientes</h2>
          <span id="u-total" style="font-size:.85rem;color:var(--muted)"></span>
        </div>
        <div class="card-body">
          <div class="u-bar">
            <input type="text" class="u-inp" id="u-busca" placeholder="Buscar por nome, e-mail, telefone ou ID" oninput="buscarUsuarios()" autocomplete="off">
            <select class="u-inp" id="u-filtro" onchange="carregarUsuarios(1)" style="width:auto">
              <option value="">Todos</option>
              <option value="usuario">Só clientes</option>
              <option value="admin">Só administradores</option>
            </select>
          </div>
          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>E-mail</th>
                  <th>Telefone</th>
                  <th>Nível</th>
                  <th>Data Cadastro</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="u-tbody"></tbody>
            </table>
          </div>
          <div id="u-vazio" class="empty" style="display:none"><p>Nenhum usuário encontrado</p></div>
          <div id="u-pager" class="u-pager"></div>
        </div>
      </div>
    </div>

    <!-- PAGE: ESTOQUE -->
    <div id="page-estoque" class="page">
      <div class="card">
        <div class="card-header">
          <h2> Estoque</h2>
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
                    $msg = 'OK';
                    if ($estoque === 0) { $status = 'badge-red'; $msg = 'Esgotado'; }
                    elseif ($estoque <= $minimo) { $status = 'badge-yellow'; $msg = 'Crítico'; }
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
              <div class="empty-icon"></div>
              <p>Nenhum produto</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>


    <!-- PAGE: CONFIG -->
    <div id="page-config" class="page">
      <div class="card">
        <div class="card-header">
          <h2> Configurações</h2>
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
            <a href="Dashboard.php?sair=1" style="padding: 0.8rem 1.5rem; background: var(--red); color: white; border-radius: 0.5rem; text-align: center; font-weight: 600; margin-top: 1rem;"> Sair da Conta</a>
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
      <h2> Filtros</h2>
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
        <option value="confirmada"> Confirmada</option>
        <option value="enviada"> Enviada</option>
        <option value="entregue"> Entregue</option>
        <option value="cancelada"> Cancelada</option>
      </select>
    </div>

    <div class="filter-buttons">
      <button class="btn-primary" onclick="aplicarFiltros()" style="flex: 1;">✓ Filtrar</button>
      <button class="btn-secondary" onclick="limparFiltros()">Limpar</button>
    </div>
  </div>
</div>

<div id="modal-usuario" class="modal">
  <div class="modal-content" style="max-width:580px">
    <div class="modal-header">
      <h2 id="u-titulo">Editar usuário</h2>
      <button class="modal-close" onclick="fecharUsuario()">✕</button>
    </div>
    <div id="u-msg" class="u-msg" style="display:none"></div>
    <div id="u-corpo">
      <div class="u-info">
        <div><small>ID</small><span id="u-id"></span></div>
        <div><small>Cadastrado em</small><span id="u-criado"></span></div>
      </div>
      <form onsubmit="salvarUsuario(event)">
        <div class="form-group">
          <label>Nome</label>
          <input type="text" class="u-inp" id="u-nome" maxlength="150" required>
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input type="email" class="u-inp" id="u-email" maxlength="190" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Telefone / WhatsApp</label>
            <input type="text" class="u-inp" id="u-tel" maxlength="30" placeholder="(11) 99999-9999">
          </div>
          <div class="form-group">
            <label>Nível de acesso</label>
            <select class="u-inp" id="u-nivel">
              <option value="usuario">Cliente</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
        </div>
        <p class="u-hint" id="u-nivel-hint"></p>
        <div class="button-group" style="margin-top:1rem">
          <button type="submit" class="btn-primary" id="u-btn-salvar">Salvar alterações</button>
          <button type="button" class="btn-secondary" onclick="fecharUsuario()">Fechar</button>
        </div>
      </form>

      <div class="u-sec" id="u-senha-sec">
        <h3>Redefinir senha</h3>
        <p class="u-hint">A senha atual não pode ser exibida (ela é guardada criptografada). Se o cliente tiver algum problema para entrar, defina uma nova senha e passe para ele. Peça para trocá-la depois.</p>
        <div class="u-row">
          <input type="password" class="u-inp" id="u-nova-senha" placeholder="Nova senha (mín. 6 caracteres)" autocomplete="new-password" maxlength="72">
          <button type="button" class="btn-secondary u-btn-sm" onclick="gerarSenha()">Gerar senha</button>
        </div>
        <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--muted);margin:.6rem 0;text-transform:none;letter-spacing:0;font-weight:500">
          <input type="checkbox" id="u-ver-senha" onchange="document.getElementById('u-nova-senha').type = this.checked ? 'text' : 'password'"> Mostrar senha
        </label>
        <button type="button" class="btn-primary" id="u-btn-senha" onclick="redefinirSenha()">Redefinir senha</button>
        <div id="u-novasenha-box" class="u-novasenha" style="display:none"></div>
      </div>
      <p class="u-hint u-sec" id="u-senha-nota" style="display:none">Senhas de administradores não são redefinidas por aqui.</p>

      <div class="u-sec">
        <h3>Últimas alterações</h3>
        <div id="u-log"></div>
      </div>
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
    if (page === 'clientes' && !uCarregado) carregarUsuarios(1);
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
      alert('Selecione as datas!');
      return;
    }

    if (dataInicio > dataFim) {
      alert('Data início não pode ser maior que data fim!');
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
        alert(' ' + data.erro);
        return;
      }
      
      document.getElementById('metric-faturamento').textContent = 'R$ ' + data.faturamento.toLocaleString('pt-BR', {maximumFractionDigits: 0});
      document.getElementById('metric-vendas').textContent = data.vendas;
      document.getElementById('metric-ticket').textContent = 'R$ ' + data.ticket_medio.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('metric-clientes').textContent = data.clientes;
      
      // Mostrar filtros ativos
      mostrarFiltrosAtivos();
      
      fecharFiltros();
      alert('Filtros aplicados com sucesso!');
    })
    .catch(e => alert('Erro ao aplicar filtros'));
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
      tags.push(` Data: ${dataInicio} a ${dataFim}`);
    }
    
    if (filtroStatus) {
      const statusLabels = {
        'pendente': '⏳ Pendente',
        'confirmada': 'Confirmada',
        'enviada': 'Enviada',
        'entregue': 'Entregue',
        'cancelada': 'Cancelada'
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

  // Desativar produto direto na listagem
  function desativarProduto(id, btn) {
    if (!confirm('Desativar este produto? Ele deixa de aparecer no catálogo (mas continua no histórico de vendas).')) return;
    const fd = new FormData();
    fd.append('acao', 'desativar');
    fd.append('id', id);
    fetch('api_produtos.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.sucesso) {
          const row = document.getElementById('prod-row-' + id);
          if (row) row.remove();
        } else {
          alert(' ' + (d.erro || 'Não foi possível desativar o produto.'));
        }
      })
      .catch(() => alert('Erro de conexão ao desativar produto.'));
  }

  // Excluir produto PERMANENTEMENTE (apaga do banco, não dá pra desfazer)
  function excluirProduto(id, btn) {
    if (!confirm('Excluir este produto PERMANENTEMENTE?\n\nIsso apaga o produto do banco de dados de vez — diferente de "Desativar", não tem como desfazer.')) return;
    if (!confirm('Tem certeza mesmo? Essa é a confirmação final.')) return;
    const fd = new FormData();
    fd.append('acao', 'excluir_produto');
    fd.append('id', id);
    fetch('api_produtos.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.sucesso) {
          const row = document.getElementById('prod-row-' + id);
          if (row) row.remove();
        } else {
          alert(' ' + (d.erro || 'Não foi possível excluir o produto.'));
        }
      })
      .catch(() => alert('Erro de conexão ao excluir produto.'));
  }

  // ───────── CLIENTES / USUÁRIOS ─────────
  const CSRF_USUARIOS = <?= json_encode($csrf_usuarios) ?>;
  let uCarregado = false, uPagina = 1, uTimer = null, uAtual = null;

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function apiUsuarios(acao, dados = {}, post = false) {
    let url = 'api_usuarios.php', opts = {};
    if (post) {
      const fd = new FormData();
      fd.append('acao', acao);
      fd.append('csrf', CSRF_USUARIOS);
      Object.entries(dados).forEach(([k, v]) => fd.append(k, v));
      opts = { method: 'POST', body: fd };
    } else {
      url += '?' + new URLSearchParams({ acao, ...dados });
    }
    return fetch(url, opts).then(r => r.json())
      .catch(() => ({ sucesso: false, erro: 'Erro de conexão. Tente novamente.' }));
  }

  function buscarUsuarios() {
    clearTimeout(uTimer);
    uTimer = setTimeout(() => carregarUsuarios(1), 300);
  }

  function carregarUsuarios(p) {
    uCarregado = true;
    const tbody = document.getElementById('u-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted)">Carregando…</td></tr>';
    apiUsuarios('listar', {
      q: document.getElementById('u-busca').value.trim(),
      nivel: document.getElementById('u-filtro').value,
      p: p || 1
    }).then(d => {
      if (!d.sucesso) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--red)">' + esc(d.erro || 'Erro ao carregar.') + '</td></tr>';
        return;
      }
      uPagina = d.pagina;
      document.getElementById('u-total').textContent = d.total + (d.total === 1 ? ' usuário' : ' usuários');
      document.getElementById('u-vazio').style.display = d.usuarios.length ? 'none' : 'block';
      tbody.innerHTML = d.usuarios.map(u => `
        <tr>
          <td><strong>${esc(u.nome)}</strong></td>
          <td>${esc(u.email)}</td>
          <td>${esc(u.telefone || '-')}</td>
          <td><span class="badge ${u.nivel === 'admin' ? 'badge-purple' : 'badge-blue'}">${u.nivel === 'admin' ? 'Admin' : 'Cliente'}</span></td>
          <td>${esc(u.criado_em)}</td>
          <td><button class="btn-secondary u-btn-sm" onclick="abrirUsuario(${Number(u.id)})">Editar</button></td>
        </tr>`).join('');
      const pager = document.getElementById('u-pager');
      pager.innerHTML = d.paginas > 1
        ? (d.pagina > 1 ? '<button class="btn-secondary u-btn-sm" onclick="carregarUsuarios(' + (d.pagina - 1) + ')">← Anterior</button>' : '')
          + '<span>Página ' + d.pagina + ' de ' + d.paginas + '</span>'
          + (d.pagina < d.paginas ? '<button class="btn-secondary u-btn-sm" onclick="carregarUsuarios(' + (d.pagina + 1) + ')">Próxima →</button>' : '')
        : '';
    });
  }

  function uMsg(tipo, texto) {
    const el = document.getElementById('u-msg');
    if (!texto) { el.style.display = 'none'; return; }
    el.className = 'u-msg ' + (tipo === 'ok' ? 'ok' : 'er');
    el.textContent = texto;
    el.style.display = 'block';
  }

  function preencherUsuario(u, log) {
    uAtual = u;
    document.getElementById('u-titulo').textContent = 'Editar: ' + u.nome;
    document.getElementById('u-id').textContent = '#' + u.id;
    document.getElementById('u-criado').textContent = u.criado_em;
    document.getElementById('u-nome').value = u.nome;
    document.getElementById('u-email').value = u.email;
    document.getElementById('u-tel').value = u.telefone || '';
    const sel = document.getElementById('u-nivel');
    sel.value = u.nivel;
    sel.disabled = u.eh_proprio;
    document.getElementById('u-nivel-hint').textContent = u.eh_proprio
      ? 'Você não pode alterar o seu próprio nível de acesso.'
      : 'Mudar o nível pede confirmação. Administradores têm acesso total ao painel.';
    const podeSenha = u.nivel === 'usuario' && !u.eh_proprio;
    document.getElementById('u-senha-sec').style.display = podeSenha ? 'block' : 'none';
    document.getElementById('u-senha-nota').style.display = podeSenha ? 'none' : 'block';
    document.getElementById('u-senha-nota').textContent = u.eh_proprio
      ? 'Para trocar a sua própria senha, use a opção de alterar senha da sua conta.'
      : 'Senhas de administradores não são redefinidas por aqui.';
    renderLog(log || []);
  }

  function renderLog(log) {
    document.getElementById('u-log').innerHTML = log.length
      ? log.map(l => `<div class="u-log-item"><strong>${esc(l.campo)}</strong>: <em>${esc(l.antigo || '(vazio)')}</em> → ${esc(l.novo || '(vazio)')}<br><em>por ${esc(l.admin)} em ${esc(l.quando)}</em></div>`).join('')
      : '<p class="u-hint">Nenhuma alteração registrada para este usuário.</p>';
  }

  function abrirUsuario(id) {
    uMsg('', '');
    document.getElementById('u-nova-senha').value = '';
    document.getElementById('u-nova-senha').type = 'password';
    document.getElementById('u-ver-senha').checked = false;
    document.getElementById('u-novasenha-box').style.display = 'none';
    document.getElementById('u-corpo').style.display = 'none';
    document.getElementById('modal-usuario').classList.add('active');
    apiUsuarios('obter', { id }).then(d => {
      if (!d.sucesso) { uMsg('er', d.erro || 'Não foi possível carregar o usuário.'); return; }
      preencherUsuario(d.usuario, d.log);
      document.getElementById('u-corpo').style.display = 'block';
    });
  }

  function fecharUsuario() {
    document.getElementById('modal-usuario').classList.remove('active');
    document.getElementById('u-nova-senha').value = '';
    document.getElementById('u-novasenha-box').style.display = 'none';
  }

  function salvarUsuario(e) {
    e.preventDefault();
    if (!uAtual) return;
    const sel = document.getElementById('u-nivel');
    const mudouNivel = !sel.disabled && sel.value !== uAtual.nivel;
    if (mudouNivel) {
      const txt = sel.value === 'admin'
        ? 'Tornar este usuário ADMINISTRADOR? Ele terá acesso total ao painel.'
        : 'Remover o acesso de administrador deste usuário?';
      if (!confirm(txt)) return;
    }
    const btn = document.getElementById('u-btn-salvar');
    btn.disabled = true;
    apiUsuarios('salvar', {
      id: uAtual.id,
      nome: document.getElementById('u-nome').value,
      email: document.getElementById('u-email').value,
      telefone: document.getElementById('u-tel').value,
      nivel: sel.value,
      confirmar_nivel: mudouNivel ? '1' : '0'
    }, true).then(d => {
      btn.disabled = false;
      if (!d.sucesso) { uMsg('er', d.erro || 'Não foi possível salvar.'); return; }
      uMsg('ok', d.mensagem);
      preencherUsuario(d.usuario, d.log);
      carregarUsuarios(uPagina);
    });
  }

  function gerarSenha() {
    const alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    const bytes = new Uint32Array(10);
    crypto.getRandomValues(bytes);
    const senha = Array.from(bytes, b => alfabeto[b % alfabeto.length]).join('');
    const inp = document.getElementById('u-nova-senha');
    inp.value = senha;
    inp.type = 'text';
    document.getElementById('u-ver-senha').checked = true;
  }

  function redefinirSenha() {
    if (!uAtual) return;
    const inp = document.getElementById('u-nova-senha');
    const nova = inp.value;
    if (nova.length < 6) { uMsg('er', 'A senha deve ter no mínimo 6 caracteres.'); return; }
    if (!confirm('Redefinir a senha de ' + uAtual.nome + '?\n\nA senha antiga deixa de funcionar imediatamente.')) return;
    const btn = document.getElementById('u-btn-senha');
    btn.disabled = true;
    apiUsuarios('redefinir_senha', { id: uAtual.id, nova_senha: nova, confirmar: '1' }, true).then(d => {
      btn.disabled = false;
      if (!d.sucesso) { uMsg('er', d.erro || 'Não foi possível redefinir a senha.'); return; }
      uMsg('ok', d.mensagem);
      renderLog(d.log || []);
      const box = document.getElementById('u-novasenha-box');
      box.innerHTML = 'Nova senha (aparece só agora, anote para passar ao cliente):<br><code></code> <button type="button" class="btn-secondary u-btn-sm" onclick="copiarSenha()">Copiar</button>';
      box.querySelector('code').textContent = nova;
      box.style.display = 'block';
      inp.value = '';
      inp.type = 'password';
      document.getElementById('u-ver-senha').checked = false;
    });
  }

  function copiarSenha() {
    const t = document.querySelector('#u-novasenha-box code').textContent;
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(() => uMsg('ok', 'Senha copiada.'));
  }

  document.getElementById('modal-usuario').addEventListener('click', (e) => {
    if (e.target.id === 'modal-usuario') fecharUsuario();
  });

  // Abrir direto na aba Clientes (ex.: link antigo usuarios_admin.php)
  if (location.hash === '#clientes') {
    showPage('clientes', document.querySelector("[onclick*=\"showPage('clientes'\"]"));
  }
</script>

</body>
</html>