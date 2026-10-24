<?php
/**
 * usuarios_admin.php
 * ──────────────────
 * A edição de usuários agora fica dentro do Dashboard (aba "Clientes").
 * Este arquivo só redireciona, para não quebrar links/favoritos antigos
 * (mesmo padrão do admin_dashboard.php).
 */
header("Location: Dashboard.php#clientes");
exit();
