<?php
session_start();
require_once 'conexão.php';

// 🔐 Apenas admin
if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'admin') {
    echo json_encode(['erro' => 'Não autorizado']);
    exit();
}

// Se é uma requisição de filtro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'filtrar') {
    
    $data_inicio = $_POST['data_inicio'] ?? '';
    $data_fim = $_POST['data_fim'] ?? '';
    
    // Validar datas
    if (empty($data_inicio) || empty($data_fim)) {
        echo json_encode(['erro' => 'Datas obrigatórias']);
        exit();
    }
    
    // Converter para formato MySQL (YYYY-MM-DD)
    $inicio = $data_inicio . ' 00:00:00';
    $fim = $data_fim . ' 23:59:59';
    
    // FATURAMENTO NO PERÍODO
    $resultado = $conn->query("
        SELECT SUM(total_valor) as total 
        FROM vendas 
        WHERE data_venda BETWEEN '$inicio' AND '$fim'
    ");
    $row = $resultado->fetch_assoc();
    $faturamento = $row['total'] ?? 0;
    
    // NÚMERO DE VENDAS NO PERÍODO
    $resultado = $conn->query("
        SELECT COUNT(*) as total 
        FROM vendas 
        WHERE data_venda BETWEEN '$inicio' AND '$fim'
    ");
    $vendas = $resultado->fetch_assoc()['total'];
    
    // TICKET MÉDIO NO PERÍODO
    $ticket_medio = 0;
    if ($vendas > 0) {
        $ticket_medio = $faturamento / $vendas;
    }
    
    // Retornar JSON
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => true,
        'faturamento' => (float) $faturamento,
        'vendas' => (int) $vendas,
        'ticket_medio' => (float) $ticket_medio,
        'periodo' => "$data_inicio a $data_fim"
    ]);
    exit();
}
?>
