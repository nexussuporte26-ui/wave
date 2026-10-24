<?php
$host = "localhost";
$user = "root";
$pass = ""; // produção: senha da conta do InfinityFree (não versionar)
$db = "wave";

$conn = new mysqli($host, $user, $pass, $db);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Garante que acentos funcionem (Wave Acessórios)
$conn->set_charset("utf8mb4");

/**
 * ═══════════════════════════════════════════════════════════════
 * BOOTSTRAP DE SCHEMA (idempotente)
 * ═══════════════════════════════════════════════════════════════
 * Antes, cada página fazia seu próprio "ALTER TABLE ... ADD COLUMN
 * IF NOT EXISTS" (cadastro_produto.php, api_produtos.php, produto.php),
 * mas index.php e Dashboard.php já assumiam que as colunas "imagem" e
 * "destaque" existiam, e a tabela `avaliacoes` também não existia até
 * a primeira visita a uma página que a criava. Resultado: dependendo
 * de qual página o visitante abrisse primeiro, o site quebrava com
 * "Unknown column 'imagem'".
 *
 * Centralizando aqui, toda página que faz require_once('conexao.php')
 * garante o schema completo, sem depender de ordem de visita.
 * Isso roda 1x por request (é rápido) e não faz nada se já existir.
 */
$conn->query("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS imagem VARCHAR(255) DEFAULT NULL AFTER imagem_url");
$conn->query("ALTER TABLE produtos ADD COLUMN IF NOT EXISTS destaque TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo");

$conn->query("CREATE TABLE IF NOT EXISTS avaliacoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produto_id INT NOT NULL,
  usuario_id INT NOT NULL,
  nota TINYINT NOT NULL DEFAULT 5,
  titulo VARCHAR(150) DEFAULT '',
  texto TEXT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_avaliacoes_produto (produto_id),
  KEY idx_avaliacoes_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/**
 * Categorias administráveis (nome + imagem), usadas nos blocos grandes
 * do catálogo e da home. Antes eram uma lista fixa no código; agora o
 * admin cria/edita/reordena pelo categorias_admin.php.
 */
$conn->query("CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  imagem VARCHAR(255) DEFAULT NULL,
  ordem INT NOT NULL DEFAULT 0,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Semente inicial: só roda uma vez, quando a tabela está vazia
$rc = $conn->query("SELECT COUNT(*) as c FROM categorias");
if ($rc && (int)$rc->fetch_assoc()['c'] === 0) {
    $conn->query("INSERT INTO categorias (nome, ordem) VALUES ('Colares',1),('Pulseiras',2),('Brincos',3),('Anéis',4),('Kits',5)");
}
?>
