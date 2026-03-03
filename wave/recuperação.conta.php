<?php
session_start();
require_once 'conexão.php';

$mensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Verifica se o e-mail existe no banco
    $sql_check = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows > 0) {
        // Lógica de envio de e-mail (Token)
        $mensagem = "sucesso";
    } else {
        $mensagem = "erro_nao_encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Usewaves</title>
    <link rel="stylesheet" href="login.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
            <h1 class="lg-h1">Recupere sua senha, <em>para um melhor verão.</em></h1>
            <p class="lg-sub">Informe seu e-mail cadastrado para enviarmos um link de redefinição de acesso com total segurança.</p>
            
            <div class="lg-stats">
                <div class="lg-stat"><span class="ls-ic">📈</span><div><div class="ls-t">+1000 clientes satisfeitos</div><div class="ls-s">R$18.420 acumulados</div></div></div>
                <div class="lg-stat"><span class="ls-ic">📦</span><div><div class="ls-t">frete grátis apartir de R$99,90</div><div class="ls-s">Atualizado agora</div></div></div>
                <div class="lg-stat"><span class="ls-ic">✅</span><div><div class="ls-t">parcelamento em até 12x</div><div class="ls-s">Newsletter + Instagram</div></div></div>
            </div>
        </div>

        <div class="lg-right">
            <h2>Recuperar acesso</h2>
            <p>Digite seu gmail de acesso, enviaremos as instruções para o seu e-mail.</p>

            <?php if($mensagem == "sucesso"): ?>
                <div style="background: rgba(0, 200, 150, 0.1); color: #00c896; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #00c896;">
                    ✅ Link enviado! Verifique sua caixa de entrada e spam.
                </div>
            <?php elseif($mensagem == "erro_nao_encontrado"): ?>
                <div style="background: rgba(255, 0, 0, 0.1); color: #ff4d4d; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #ff4d4d;">
                    ❌ Este e-mail não consta em nossa base de dados.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="f-grp">
                    <label class="f-lbl">E-mail (login)</label>
                    <div class="f-wrap">
                        <span class="f-ic">📧</span>
                        <input class="f-input" type="email" name="email" placeholder="Digite seu Email" required/>
                    </div>
                </div>

                <button class="btn-login" type="submit" style="margin-top: 10px;">Continuar →</button>
                
                <div class="lg-note" style="margin-top: 25px;">
                    <a href="login.php">← Voltar para o login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>