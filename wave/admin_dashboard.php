<?php
/**
 * admin_dashboard.php
 * ───────────────────
 * Este nome de arquivo era usado por uma versão antiga/duplicada do
 * painel administrativo. O painel real e funcional é Dashboard.php.
 * Mantemos este arquivo só como redirecionamento, para não quebrar
 * links antigos (favoritos, e-mails, etc.) que ainda apontem para cá.
 */
session_start();
if (isset($_GET['sair'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
header("Location: Dashboard.php");
exit();
