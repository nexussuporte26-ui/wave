<?php
/**
 * cadastro.php — Cadastro de novo cliente
 * Reaproveita o mesmo layout visual de login.php/login.css para manter
 * a identidade da loja. Sempre cria usuários com nivel='usuario'
 * (contas admin não são criadas por aqui).
 */
session_start();
require_once 'conexao.php';

// Se já está logado, manda pra página correta
if (isset($_SESSION['usuario_id'])) {
    header("Location: " . ($_SESSION['nivel'] === 'admin' ? 'Dashboard.php' : 'index.php'));
    exit();
}

$erro = '';
$nome_val = $email_val = $telefone_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_val = trim($_POST['nome'] ?? '');
    $email_val = trim($_POST['email'] ?? '');
    $telefone_val = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_conf = $_POST['senha_confirmar'] ?? '';

    if (!$nome_val || !$email_val || !$senha) {
        $erro = 'Preencha nome, e-mail e senha.';
    } elseif (!filter_var($email_val, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $senha_conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email_val);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erro = 'Este e-mail já está cadastrado. Tente fazer login.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $tel = $telefone_val ?: null;
            $ins = $conn->prepare("INSERT INTO usuarios (nome, email, senha, nivel, telefone, criado_em) VALUES (?, ?, ?, 'usuario', ?, NOW())");
            $ins->bind_param('ssss', $nome_val, $email_val, $hash, $tel);

            if ($ins->execute()) {
                // Login automático após cadastro
                $novo_id = $conn->insert_id;
                $_SESSION['usuario_id'] = $novo_id;
                $_SESSION['nivel'] = 'usuario';
                $_SESSION['nome'] = $nome_val;
                $_SESSION['email'] = $email_val;
                header("Location: index.php?bemvindo=1");
                exit();
            } else {
                $erro = 'Não foi possível concluir o cadastro. Tente novamente.';
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
  <title>Criar conta — Usewaves</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300..800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="login.css"/>
  <style>
    .role-tabs{display:flex;background:#e2e8f0;border-radius:.6rem;padding:.2rem;gap:.18rem;margin-bottom:1.4rem}
    .f-error{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;border-radius:.6rem;padding:.65rem .9rem;font-size:.8rem;font-weight:500;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;animation:shake .35s ease}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
    .btn-login.loading{opacity:.72;pointer-events:none}
    .spin{width:15px;height:15px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none}
    .btn-login.loading .spin{display:block}
    .btn-login.loading .btxt{display:none}
    @keyframes spin{to{transform:rotate(360deg)}}
    .f-row2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
    @media(max-width:480px){.f-row2{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div id="loginPage">
  <svg class="lg-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" style="height:90px">
    <path fill="white" d="M0,60 C360,20 720,100 1080,60 C1260,40 1380,70 1440,60 L1440,120 L0,120Z"/>
  </svg>

  <div class="lg-left">
    <div class="lg-brand">
      <img src="logo-removebg-preview.png" alt="Wave"/>
      <div class="lg-brand-t">Usewaves</div>
    </div>
    <h1 class="lg-h1">Junte-se à maré,<br><em>para um melhor verão.</em></h1>
    <p class="lg-sub">Crie sua conta e acompanhe pedidos, favoritos e ofertas exclusivas da coleção Wave.</p>
    <div class="lg-stats">
      <div class="lg-stat"><span class="ls-ic"></span><div><div class="ls-t">+1000 clientes satisfeitos</div><div class="ls-s">Comunidade crescendo todo dia</div></div></div>
      <div class="lg-stat"><span class="ls-ic"></span><div><div class="ls-t">Frete grátis a partir de R$99,90</div><div class="ls-s">Entrega para todo o Brasil</div></div></div>
      <div class="lg-stat"><span class="ls-ic"></span><div><div class="ls-t">Parcelamento em até 12x</div><div class="ls-s">Cartão, PIX e boleto aceitos</div></div></div>
    </div>
  </div>

  <div class="lg-right">
    <h2>Criar minha conta</h2>
    <p>Leva menos de um minuto. Você poderá comprar e avaliar produtos em seguida.</p>

    <?php if ($erro): ?>
      <div class="f-error"> <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <div class="f-error" id="jsErr" style="display:none"></div>

    <form method="POST" action="cadastro.php" id="frmCadastro" onsubmit="return beforeSubmit()">
      <div class="f-grp">
        <label class="f-lbl">Nome completo</label>
        <div class="f-wrap">
          <span class="f-ic"></span>
          <input class="f-input" type="text" name="nome" id="cNome" placeholder="Seu nome completo" value="<?= htmlspecialchars($nome_val) ?>" required/>
        </div>
      </div>
      <div class="f-grp">
        <label class="f-lbl">E-mail</label>
        <div class="f-wrap">
          <span class="f-ic"></span>
          <input class="f-input" type="email" name="email" id="cEmail" placeholder="Digite seu Email" value="<?= htmlspecialchars($email_val) ?>" autocomplete="email" required/>
        </div>
      </div>
      <div class="f-grp">
        <label class="f-lbl">Telefone / WhatsApp (opcional)</label>
        <div class="f-wrap">
          <span class="f-ic"></span>
          <input class="f-input" type="text" name="telefone" placeholder="(11) 99999-9999" value="<?= htmlspecialchars($telefone_val) ?>"/>
        </div>
      </div>
      <div class="f-row2">
        <div class="f-grp">
          <label class="f-lbl">Senha</label>
          <div class="f-wrap">
            <span class="f-ic"></span>
            <input class="f-input" type="password" name="senha" id="cPass" placeholder="Mínimo 6 caracteres" autocomplete="new-password" required/>
          </div>
        </div>
        <div class="f-grp">
          <label class="f-lbl">Confirmar senha</label>
          <div class="f-wrap">
            <span class="f-ic"></span>
            <input class="f-input" type="password" name="senha_confirmar" id="cPass2" placeholder="Repita a senha" autocomplete="new-password" required/>
          </div>
        </div>
      </div>

      <button class="btn-login" type="submit" id="btnSubmit" style="margin-top: 10px;">
        <div class="spin"></div>
        <span class="btxt">Criar conta →</span>
      </button>

      <div class="lg-note" style="margin-top: 25px;">
        Já tem uma conta? <a href="login.php">Entrar</a>
      </div>
      <div class="lg-note">
        <a href="index.php">← Voltar para a loja</a>
      </div>
    </form>
  </div>
</div>
<script>
  function beforeSubmit() {
    const nome = document.getElementById('cNome').value.trim();
    const email = document.getElementById('cEmail').value.trim();
    const p1 = document.getElementById('cPass').value;
    const p2 = document.getElementById('cPass2').value;
    const err = document.getElementById('jsErr');

    if (!nome || !email || !p1 || !p2) {
      err.textContent = 'Preencha todos os campos obrigatórios.';
      err.style.display = 'flex';
      return false;
    }
    if (p1.length < 6) {
      err.textContent = 'A senha deve ter no mínimo 6 caracteres.';
      err.style.display = 'flex';
      return false;
    }
    if (p1 !== p2) {
      err.textContent = 'As senhas não coincidem.';
      err.style.display = 'flex';
      return false;
    }
    err.style.display = 'none';
    document.getElementById('btnSubmit').classList.add('loading');
    return true;
  }
</script>
</body>
</html>
