<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * LOGIN.PHP - VERSÃO FINAL CORRIGIDA
 * ═══════════════════════════════════════════════════════════════
 * 
 * Funcionalidades:
 * ✅ Login de usuários normais → index.php
 * ✅ Login de admins → admin_dashboard.php
 * ✅ Prepared statements (seguro)
 * ✅ Verificação de sessão existente
 */

session_start();
require_once 'conexão.php';

// Se já está logado, redireciona para página correta
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['nivel'] === 'admin') {
        header("Location: Dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$erro = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validação básica
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        // 🔐 Usar prepared statement (seguro contra SQL injection)
        $stmt = $conn->prepare("SELECT id, nome, email, senha, nivel FROM usuarios WHERE email = ? LIMIT 1");
        
        if ($stmt === false) {
            $erro = 'Erro ao processar login. Tente novamente.';
            error_log("Erro na preparação: " . $conn->error);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
                
                // ✅ Verifica a senha com hash
                if (password_verify($senha, $usuario['senha'])) {
                    // ✅ LOGIN BEM-SUCEDIDO - Salvar sessão
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nivel']      = $usuario['nivel'];
                    $_SESSION['nome']       = $usuario['nome'];
                    $_SESSION['email']      = $usuario['email'];
                    
                    // Log de login
                    error_log("✅ Login bem-sucedido: {$usuario['email']} ({$usuario['nivel']})");
                    
                    // 🎯 REDIRECIONAR BASEADO NO NÍVEL
                    if ($usuario['nivel'] === 'admin') {
                        // Admin vai para dashboard
                        header("Location: Dashboard.php");
                    } else {
                        // Usuário normal vai para home
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    // ❌ Senha incorreta
                    $erro = 'E-mail ou senha incorretos.';
                    error_log("❌ Senha incorreta: {$email}");
                }
            } else {
                // ❌ Usuário não encontrado
                $erro = 'E-mail ou senha incorretos.';
                error_log("❌ Usuário não encontrado: {$email}");
            }
            
            $stmt->close();
        }
    }
}

// Verificar erro via URL
if (!$erro && isset($_GET['erro']) && $_GET['erro'] === 'invalido') {
    $erro = 'E-mail ou senha incorretos.';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — Usewaves</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,800;1,700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="login.css"/>
  <style>
    /* ── TABS ── */
    .role-tabs{display:flex;background:#e2e8f0;border-radius:.6rem;padding:.2rem;gap:.18rem;margin-bottom:1.4rem}
    .role-tab{flex:1;text-align:center;padding:.44rem .4rem;border-radius:.44rem;font-size:.76rem;font-weight:600;cursor:pointer;border:none;background:none;color:#94a3b8;font-family:'DM Sans',sans-serif;transition:all .18s}
    .role-tab.on{background:#fff;color:#1e293b;box-shadow:0 1px 4px rgba(0,0,0,.1)}
    /* ── ADMIN BADGE ── */
    .adm-badge{display:none;align-items:center;gap:.4rem;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:.5rem;padding:.32rem .7rem;font-size:.72rem;font-weight:600;margin-bottom:1rem}
    /* ── ERROR ── */
    .f-error{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:.6rem;padding:.65rem .9rem;font-size:.8rem;font-weight:500;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;animation:shake .35s ease}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
    /* ── LOADING ── */
    .btn-login.loading{opacity:.72;pointer-events:none}
    .spin{width:15px;height:15px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none}
    .btn-login.loading .spin{display:block}
    .btn-login.loading .btxt{display:none}
    @keyframes spin{to{transform:rotate(360deg)}}

    /* ── SUCCESS MESSAGE ── */
    .f-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:.6rem;padding:.65rem .9rem;font-size:.8rem;font-weight:500;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
  </style>
</head>
<body>
<div id="loginPage">
  <svg class="lg-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" style="height:90px">
    <path fill="white" d="M0,60 C360,20 720,100 1080,60 C1260,40 1380,70 1440,60 L1440,120 L0,120Z"/>
  </svg>

  <div class="lg-left">
    <div class="lg-brand">
      <img src="logo.PNG" alt="Wave"/>
      <div class="lg-brand-t">Usewaves</div>
    </div>
    <h1 class="lg-h1">No ritmo do mar,<br><em>para um melhor verão.</em></h1>
    <p class="lg-sub">Bijuterias leves e cheias de charme para destacar sua beleza em qualquer dia de sol.</p>
    <div class="lg-stats">
      <div class="lg-stat"><span class="ls-ic">📈</span><div><div class="ls-t">+1000 clientes satisfeitos</div><div class="ls-s">Comunidade crescendo todo dia</div></div></div>
      <div class="lg-stat"><span class="ls-ic">📦</span><div><div class="ls-t">Frete grátis a partir de R$99,90</div><div class="ls-s">Entrega para todo o Brasil</div></div></div>
      <div class="lg-stat"><span class="ls-ic">✅</span><div><div class="ls-t">Parcelamento em até 12x</div><div class="ls-s">Cartão, PIX e boleto aceitos</div></div></div>
    </div>
  </div>

  <div class="lg-right">
    <h2>Acesse sua conta</h2>
    <p>Preencha com seu e-mail e senha para acessar sua conta na plataforma.</p>

    <!-- TABS -->
    <div class="role-tabs">
      <button class="role-tab on" id="tabUser" onclick="setRole('usuario')">👤 Sou cliente</button>
      <button class="role-tab" id="tabAdmin" onclick="setRole('admin')">⚙️ Admin</button>
    </div>
    <div class="adm-badge" id="admBadge">⚠️ Área restrita — somente administradores</div>

    <!-- Erro PHP -->
    <?php if ($erro): ?>
      <div class="f-error">❌ <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <div class="f-error" id="jsErr" style="display:none"></div>

    <!-- FORM com POST -->
    <form method="POST" action="login.php" id="frmLogin" onsubmit="return beforeSubmit()">
      <div class="f-grp">
        <label class="f-lbl">E-mail</label>
        <div class="f-wrap">
          <span class="f-ic">📧</span>
          <input class="f-input" type="email" name="email" id="lEmail"
            placeholder="Digite seu E-mail"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            autocomplete="email" required/>
        </div>
      </div>
      <div class="f-grp">
        <label class="f-lbl">Senha</label>
        <div class="f-wrap">
          <span class="f-ic">🔒</span>
          <input class="f-input" type="password" name="senha" id="lPass"
            placeholder="Digite sua senha"
            autocomplete="current-password" required/>
          <button type="button" class="f-eye" onclick="togglePass()">👁</button>
        </div>
      </div>
      <div class="f-row">
        <label class="f-chk"><input type="checkbox" name="lembrar" checked/> Lembrar acesso</label>
        <a href="recuperar_senha.php" class="f-fgt">Esqueci a senha</a>
      </div>

      <button type="submit" class="btn-login" id="btnSubmit">
        <div class="spin"></div>
        <span class="btxt" id="btnTxt">Entrar no painel →</span>
      </button>
    </form>

    <br/>
    <button class="btn-login" onclick="window.location.href='cadastro.php'">← Criar conta</button>
    <div class="lg-note"><a href="index.php">← Voltar para a loja</a></div>
  </div>
</div>

<script>
  function setRole(role) {
    document.getElementById('tabUser').classList.toggle('on', role === 'usuario');
    document.getElementById('tabAdmin').classList.toggle('on', role === 'admin');
    document.getElementById('admBadge').style.display = role === 'admin' ? 'flex' : 'none';
    document.getElementById('btnTxt').textContent = role === 'admin' ? 'Entrar como admin →' : 'Entrar no painel →';
    document.getElementById('jsErr').style.display = 'none';
  }
  
  function togglePass() {
    const i = document.getElementById('lPass');
    i.type = i.type === 'password' ? 'text' : 'password';
  }
  
  function beforeSubmit() {
    const email = document.getElementById('lEmail').value.trim();
    const senha = document.getElementById('lPass').value;
    const err = document.getElementById('jsErr');
    
    if (!email || !senha) {
      err.textContent = '❌ Preencha todos os campos.';
      err.style.display = 'flex';
      return false;
    }
    
    err.style.display = 'none';
    document.getElementById('btnSubmit').classList.add('loading');
    return true;
  }
  
  document.getElementById('lEmail').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('lPass').focus(); }
  });
</script>
</body>
</html>