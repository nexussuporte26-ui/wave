<?php
$host = "localhost"; // O local onde o banco está
$user = "root";      // Usuário padrão do XAMPP
$pass = "";          // Senha padrão do XAMPP (vazia)
$db   = "wave";      // O nome do banco que você criou

$conn = new mysqli($host, $user, $pass, $db);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Garante que acentos funcionem (Wave Acessórios)
$conn->set_charset("utf8mb4");
?>