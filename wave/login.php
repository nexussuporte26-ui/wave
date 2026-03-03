<?php
session_start();
require_once 'conexão.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // Verifica a senha criptografada
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nivel'] = $usuario['nivel'];
            $_SESSION['nome'] = $usuario['nome'];

            // Redirecionamento por nível de acesso
            if ($usuario['nivel'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();
        }
    }
    // Se falhar, volta com erro
    header("Location: login.php?erro=invalido");
    exit();
     
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Usewaves</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <!-- ═══════════════════════════════════
     LOGIN
═══════════════════════════════════ -->
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
      <div class="lg-stat"><span class="ls-ic">📦</span><div><div class="ls-t">frete grátis apartir de R$99,90</div><div class="ls-s">Atualizado agora</div></div></div>
      <div class="lg-stat"><span class="ls-ic">✅</span><div><div class="ls-t">parcelamento em até 12x</div><div class="ls-s">Newsletter + Instagram</div></div></div>
    </div>
  </div>

  <div class="lg-right">
    <h2>Acesse sua conta</h2>
    <p>Preencha com seu e-mail e senha para acessar sua conta na plataforma.</p>
    <div class="f-grp">
      <label linkclass="f-lbl">E-mail</label>
      <div class="f-wrap">
        <span class="f-ic">📧</span>
        <input class="f-input" type="email" id="lEmail" placeholder="Digite seu Email" value=""/>
      </div>
    </div>
    <div class="f-grp">
      <label class="f-lbl">Senha</label>
      <div class="f-wrap">
        <span class="f-ic">🔒</span>
        <input class="f-input" type="password" id="lPass" placeholder="Digite sua senha" value=""/>
        <button class="f-eye" onclick="togglePass()">👁</button>
      </div>
    </div>
    <div class="f-row">
      <label class="f-chk"><input type="checkbox" checked/> Lembrar acesso</label>
      <a href="#" class="f-fgt">Esqueci a senha</a>
    </div>
    <button class="btn-login" onclick="doLogin()"><a href="index.php">Entrar no painel →</a></button>
    <br>
    <div>
    <button class="btn-login" onclick="doLogin()"><a href="cadastro.php">← Criar conta</a></button>
    </div>
    <div class="lg-note"><a href="index.php">← Voltar para a loja</a></div>
  </div>
</div>


<!-- ═══════════════════════════════════
     APP SHELL
═══════════════════════════════════ -->

</body>
</html>