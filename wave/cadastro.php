<?php
session_start();
require_once 'conexão.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telefone = mysqli_real_escape_string($conn, $_POST['telefone']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Criptografando a senha

    // Validações
    if (empty($nome) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($telefone) || empty($_POST['senha'])) {
        header("Location: cadastro.php?erro=campos_obrigatorios");
        exit();
    }

    // Verifica se o e-mail já está cadastrado
    $sql_check = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows == 0) {
        // Insere o novo usuário
        $sql_insert = "INSERT INTO usuarios (nome, email, telefone, senha, nivel) VALUES ('$nome', '$email', '$telefone', '$senha', 'cliente');";
        if ($conn->query($sql_insert) === TRUE) {
            $_SESSION['usuario_id'] = $conn->insert_id; // ID do novo usuário
            $_SESSION['nivel'] = 'cliente';
            header("Location: index.php"); // Redireciona para a página principal
            exit();
        } else {
            header("Location: cadastro.php?erro=erro_inserir");
            exit();
        }
    } else {
        // Se o e-mail já existir
        header("Location: cadastro.php?erro=email_existente");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Usewaves</title>
    <link rel="stylesheet" href="login.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
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
            <h1 class="lg-h1">No ritmo do mar,
<em>para um melhor verão.</em></h1>
            <p class="lg-sub">Bijuterias leves e cheias de charme para destacar sua beleza em qualquer dia de sol.</p>
            <div class="lg-stats">
                <div class="lg-stat"><span class="ls-ic">📈</span><div><div class="ls-t">+1000 clientes satisfeitos</div><div class="ls-s">R$18.420 acumulados</div></div></div>
                <div class="lg-stat"><span class="ls-ic">📦</span><div><div class="ls-t">frete grátis apartir de R$99,90</div><div class="ls-s">Atualizado agora</div></div></div>
                <div class="lg-stat"><span class="ls-ic">✅</span><div><div class="ls-t">parcelamento em até 12x</div><div class="ls-s">Newsletter + Instagram</div></div></div>
            </div>
        </div>

        <div class="lg-right">
            <h2>Crie sua conta</h2>
            <p>Preencha com suas informações para se cadastrar na plataforma.</p>
            <form method="POST" action="" id="cadastroForm">
                <div class="f-grp">
                    <label class="f-lbl">Nome Completo</label>
                    <div class="f-wrap">
                        <span class="f-ic">🧑</span>
                        <input class="f-input" type="text" name="nome" placeholder="Digite seu nome completo" required/>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl">E-mail (para login)</label>
                    <div class="f-wrap">
                        <span class="f-ic">📧</span>
                        <input class="f-input" type="email" name="email" placeholder="Digite seu Email" required/>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl">Telefone</label>
                    <div class="f-wrap">
                        <span class="f-ic">📞</span>
                        <input class="f-input" type="tel" name="telefone" id="telefone" placeholder="Digite seu telefone" required/>
                    </div>
                </div>
                <div class="f-grp">
                    <label class="f-lbl">Senha</label>
                    <div class="f-wrap">
                        <span class="f-ic">🔒</span>
                        <input class="f-input" type="password" name="senha" placeholder="Digite sua senha" required/>
                        <button class="f-eye" onclick="togglePass()">👁</button>
                    </div>
                </div>
                <div class="f-row">
                    <label class="f-chk"><input type="checkbox" checked/> Lembrar acesso</label>
                    <a href="#" class="f-fgt">Esqueci a senha</a>
                </div>
                <button class="btn-login" type="submit">Cadastrar →</button>
                <div class="lg-note"><a href="index.php" target="_blank">← Voltar para a loja</a></div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#telefone").intlTelInput({
                initialCountry: "br",
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
            });
        });
    </script>
</body>
</html>