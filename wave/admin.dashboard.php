<?php
session_start();
if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] !== 'admin') {
    // Se não for admin, chuta de volta para o login ou área comum
    header("Location: login.php?acesso_negado");
    exit();
}
?>
<h1>Painel de Edição Wave Acessórios</h1>
<p>Bem-vinda de volta <?php echo $_SESSION['nome']; ?>. Você tem poder total aqui.</p>