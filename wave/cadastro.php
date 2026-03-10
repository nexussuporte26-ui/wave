<?php
session_start();
require_once 'conexão.php';

// Já logado? Redireciona
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome     = trim(mysqli_real_escape_string($conn, $_POST['nome'] ?? ''));
    $email    = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $telefone = trim(mysqli_real_escape_string($conn, $_POST['telefone'] ?? ''));
    $senha_raw = $_POST['senha'] ?? '';

    // Validações
    if (empty($nome) || empty($email) || empty($telefone) || empty($senha_raw)) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha_raw) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } else {
        // Verifica e-mail duplicado
        $check = $conn->query("SELECT id FROM usuarios WHERE email = '$email' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $erro = 'Este e-mail já está cadastrado. Faça login.';
        } else {
            $senha = password_hash($senha_raw, PASSWORD_DEFAULT);

            // ✅ nivel = 'usuario' (igual ao que o login.php verifica)
            $sql = "INSERT INTO usuarios (nome, email, telefone, senha, nivel)
                    VALUES ('$nome', '$email', '$telefone', '$senha', 'usuario')";

            if ($conn->query($sql)) {
                // Loga automaticamente após cadastro
                $_SESSION['usuario_id'] = $conn->insert_id;
                $_SESSION['nivel']      = 'usuario';
                $_SESSION['nome']       = $nome;   // ✅ salva o nome na sessão

                header("Location: index.php");
                exit();
            } else {
                $erro = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cadastro — Usewaves</title>
  <link rel="stylesheet" href="login.css"/>
  <style>
    /* Erro / sucesso inline */
    .f-error {
      background:#fef2f2; border:1px solid #fca5a5; color:#dc2626;
      border-radius:.6rem; padding:.65rem .9rem; font-size:.8rem;
      font-weight:500; margin-bottom:1rem;
      display:flex; align-items:center; gap:.5rem;
      animation: shake .35s ease;
    }
    @keyframes shake {
      0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)}
    }
    /* Força de senha */
    .pwd-bar { height:4px; border-radius:999px; background:#e2e8f0; margin-top:.4rem; overflow:hidden; }
    .pwd-fill { height:100%; border-radius:999px; transition:width .3s, background .3s; width:0%; }
    .pwd-hint { font-size:.67rem; color:#94a3b8; margin-top:.25rem; }
    /* Loading */
    .btn-login.loading { opacity:.72; pointer-events:none; }
    .spin { width:15px;height:15px;border:2px solid rgba(255,255,255,.3);
      border-top-color:#fff;border-radius:50%;
      animation:spin .7s linear infinite;display:none; }
    .btn-login.loading .spin { display:inline-block; }
    .btn-login.loading .btxt { display:none; }
    @keyframes spin { to{transform:rotate(360deg)} }
    /* Já tem conta */
    .have-account { text-align:center; margin-top:.7rem; font-size:.78rem; color:#94a3b8; }
    .have-account a { color:#2563eb; font-weight:600; text-decoration:none; }
    .have-account a:hover { text-decoration:underline; }
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
      <div class="lg-stat"><span class="ls-ic">📈</span><div><div class="ls-t">+1000 clientes satisfeitos</div><div class="ls-s">R$18.420 acumulados</div></div></div>
      <div class="lg-stat"><span class="ls-ic">📦</span><div><div class="ls-t">Frete grátis a partir de R$99,90</div><div class="ls-s">Atualizado agora</div></div></div>
      <div class="lg-stat"><span class="ls-ic">✅</span><div><div class="ls-t">Parcelamento em até 12x</div><div class="ls-s">Newsletter + Instagram</div></div></div>
    </div>
  </div>

  <div class="lg-right">
    <h2>Crie sua conta</h2>
    <p>Preencha com suas informações para se cadastrar na plataforma.</p>

    <!-- Erro PHP -->
    <?php if ($erro): ?>
      <div class="f-error">❌ <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <!-- Erro JS -->
    <div class="f-error" id="jsErr" style="display:none"></div>

    <form method="POST" action="cadastro.php" id="frmCadastro" onsubmit="return validar()">

      <div class="f-grp">
        <label class="f-lbl">Nome Completo</label>
        <div class="f-wrap">
          <span class="f-ic">🧑</span>
          <input class="f-input" type="text" name="nome" id="fNome"
            placeholder="Digite seu nome completo"
            value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required/>
        </div>
      </div>

      <div class="f-grp">
        <label class="f-lbl">E-mail</label>
        <div class="f-wrap">
          <span class="f-ic">📧</span>
          <input class="f-input" type="email" name="email" id="fEmail"
            placeholder="Digite seu e-mail"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
        </div>
      </div>

      <div class="f-grp">
        <label class="f-lbl">Telefone / WhatsApp</label>
        <div class="f-wrap">
          <span class="f-ic">📞</span>
          <input class="f-input" type="tel" name="telefone" id="fTel"
            placeholder="(11) 99999-9999"
            value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>" required/>
        </div>
      </div>

      <div class="f-grp">
        <label class="f-lbl">Senha</label>
        <div class="f-wrap">
          <span class="f-ic">🔒</span>
          <input class="f-input" type="password" name="senha" id="fSenha"
            placeholder="Mínimo 6 caracteres"
            oninput="medirSenha(this.value)" required/>
          <button type="button" class="f-eye" onclick="togglePass()">👁</button>
        </div>
        <!-- Barra de força da senha -->
        <div class="pwd-bar"><div class="pwd-fill" id="pwdFill"></div></div>
        <div class="pwd-hint" id="pwdHint">Digite uma senha segura</div>
      </div>

      <div class="f-row">
        <label class="f-chk"><input type="checkbox" id="termos" required/> Li e aceito os termos</label>
      </div>

      <button type="submit" class="btn-login" id="btnCad">
        <div class="spin"></div>
        <span class="btxt">Criar minha conta →</span>
      </button>
    </form>

    <div class="have-account">Já tem conta? <a href="login.php">Fazer login</a></div>
    <div class="lg-note"><a href="index.php">← Voltar para a loja</a></div>
  </div>
</div>

<script>
  function togglePass() {
    const i = document.getElementById('fSenha');
    i.type = i.type === 'password' ? 'text' : 'password';
  }

  function medirSenha(v) {
    const fill = document.getElementById('pwdFill');
    const hint = document.getElementById('pwdHint');
    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^a-zA-Z0-9]/.test(v)) score++;
    const pct   = [0, 20, 40, 65, 85, 100][score];
    const cores = ['#e2e8f0','#dc2626','#f97316','#eab308','#16a34a','#2563eb'];
    const msgs  = ['','Muito fraca','Fraca','Média','Forte','Muito forte'];
    fill.style.width      = pct + '%';
    fill.style.background = cores[score];
    hint.textContent      = msgs[score] || 'Digite uma senha';
    hint.style.color      = cores[score];
  }

  function validar() {
    const nome  = document.getElementById('fNome').value.trim();
    const email = document.getElementById('fEmail').value.trim();
    const tel   = document.getElementById('fTel').value.trim();
    const senha = document.getElementById('fSenha').value;
    const termos = document.getElementById('termos').checked;
    const err   = document.getElementById('jsErr');

    if (!nome || !email || !tel || !senha) {
      err.textContent = '❌ Preencha todos os campos.';
      err.style.display = 'flex'; return false;
    }
    if (senha.length < 6) {
      err.textContent = '❌ A senha deve ter no mínimo 6 caracteres.';
      err.style.display = 'flex'; return false;
    }
    if (!termos) {
      err.textContent = '❌ Aceite os termos para continuar.';
      err.style.display = 'flex'; return false;
    }
    err.style.display = 'none';
    document.getElementById('btnCad').classList.add('loading');
    return true;
  }
</script>
</body>
</html>