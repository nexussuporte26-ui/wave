<?php
/**
 * api_usuarios.php — API (JSON) de gerenciamento de usuários, usada pela aba
 * "Clientes" do Dashboard.php. Mesmo padrão do api_produtos.php.
 *
 * Segurança:
 *  - Exige login e confere o nível DIRETO NO BANCO a cada requisição
 *    (um admin rebaixado perde o acesso na hora).
 *  - Ações de escrita só aceitam POST + token CSRF da sessão.
 *  - Prepared statements em todas as consultas.
 *  - Senha: nunca é lida nem devolvida. Só é possível DEFINIR uma nova
 *    (gravada com password_hash) e apenas para contas de cliente.
 *  - Toda alteração (inclusive redefinição de senha, sem o valor) fica
 *    registrada em `usuarios_log`.
 */
session_start();
require_once 'conexao.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_ok($data = []) { echo json_encode(array_merge(['sucesso' => true], $data)); exit(); }
function json_err($msg, $http = 200) { http_response_code($http); echo json_encode(['sucesso' => false, 'erro' => $msg]); exit(); }
function tam($s) { return (int)preg_match_all('/./us', (string)$s); } // caracteres UTF-8, sem mbstring

// ── Autenticação + autorização (servidor, direto no banco) ──
if (!isset($_SESSION['usuario_id'])) json_err('Sessão expirada. Faça login novamente.', 401);
$admin_id = (int)$_SESSION['usuario_id'];
$st = $conn->prepare("SELECT nivel FROM usuarios WHERE id = ? LIMIT 1");
$st->bind_param('i', $admin_id);
$st->execute();
$adm = $st->get_result()->fetch_assoc();
$st->close();
if (!$adm || $adm['nivel'] !== 'admin') {
    if ($adm) $_SESSION['nivel'] = $adm['nivel'];
    json_err('Acesso negado.', 403);
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$escrita = in_array($acao, ['salvar', 'redefinir_senha'], true);
if ($escrita) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Método inválido.', 405);
    $tok = (string)($_SESSION['csrf_usuarios'] ?? '');
    if ($tok === '' || !hash_equals($tok, (string)($_POST['csrf'] ?? ''))) {
        json_err('Sessão expirada ou requisição inválida. Recarregue a página e tente de novo.', 403);
    }
}

// ── Tabela de log (idempotente) ──
$conn->query("CREATE TABLE IF NOT EXISTS usuarios_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  usuario_id INT NOT NULL,
  campo VARCHAR(50) NOT NULL,
  valor_antigo VARCHAR(255) DEFAULT NULL,
  valor_novo VARCHAR(255) DEFAULT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ulog_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function buscar_usuario($conn, $id) {
    $st = $conn->prepare("SELECT id, nome, email, telefone, nivel, criado_em FROM usuarios WHERE id = ? LIMIT 1");
    $st->bind_param('i', $id);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();
    $st->close();
    return $u ?: null;
}
function fmt_data($d, $com_hora = false) {
    if (!$d) return '—';
    $t = strtotime($d);
    return $t ? date($com_hora ? 'd/m/Y H:i' : 'd/m/Y', $t) : '—';
}
function saida_usuario($u, $admin_id) {
    return [
        'id' => (int)$u['id'], 'nome' => $u['nome'], 'email' => $u['email'],
        'telefone' => $u['telefone'] ?? '', 'nivel' => $u['nivel'],
        'criado_em' => fmt_data($u['criado_em']), 'eh_proprio' => ((int)$u['id'] === $admin_id),
    ];
}
function buscar_log($conn, $id) {
    $st = $conn->prepare("SELECT l.campo, l.valor_antigo, l.valor_novo, l.criado_em, a.nome AS admin_nome
                          FROM usuarios_log l LEFT JOIN usuarios a ON a.id = l.admin_id
                          WHERE l.usuario_id = ? ORDER BY l.id DESC LIMIT 10");
    $st->bind_param('i', $id);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return array_map(function ($l) {
        return ['campo' => $l['campo'], 'antigo' => $l['valor_antigo'], 'novo' => $l['valor_novo'],
                'quando' => fmt_data($l['criado_em'], true), 'admin' => $l['admin_nome'] ?? 'admin removido'];
    }, $rows);
}

switch ($acao) {

    // ── Listar / buscar ──
    case 'listar':
        $q = trim($_GET['q'] ?? '');
        $filtro = $_GET['nivel'] ?? '';
        $page = max(1, (int)($_GET['p'] ?? 1));
        $per = 25;
        $conds = []; $types = ''; $params = [];
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $conds[] = "(nome LIKE ? OR email LIKE ? OR telefone LIKE ? OR id = ?)";
            $types .= 'sssi'; array_push($params, $like, $like, $like, ctype_digit($q) ? (int)$q : 0);
        }
        if (in_array($filtro, ['usuario', 'admin'], true)) {
            $conds[] = "nivel = ?"; $types .= 's'; $params[] = $filtro;
        }
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $st = $conn->prepare("SELECT COUNT(*) AS c FROM usuarios $where");
        if ($params) $st->bind_param($types, ...$params);
        $st->execute();
        $total = (int)$st->get_result()->fetch_assoc()['c'];
        $st->close();

        $paginas = max(1, (int)ceil($total / $per));
        $page = min($page, $paginas);
        $offset = ($page - 1) * $per;
        $st = $conn->prepare("SELECT id, nome, email, telefone, nivel, criado_em FROM usuarios $where ORDER BY criado_em DESC, id DESC LIMIT ? OFFSET ?");
        $t2 = $types . 'ii'; $p2 = array_merge($params, [$per, $offset]);
        $st->bind_param($t2, ...$p2);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        json_ok(['usuarios' => array_map(fn($u) => saida_usuario($u, $admin_id), $rows),
                 'total' => $total, 'pagina' => $page, 'paginas' => $paginas]);

    // ── Dados de um usuário (+ histórico) ──
    case 'obter':
        $id = (int)($_GET['id'] ?? 0);
        $u = $id ? buscar_usuario($conn, $id) : null;
        if (!$u) json_err('Usuário não encontrado.');
        json_ok(['usuario' => saida_usuario($u, $admin_id), 'log' => buscar_log($conn, $id)]);

    // ── Salvar dados do perfil ──
    case 'salvar':
        $id = (int)($_POST['id'] ?? 0);
        $atual = $id ? buscar_usuario($conn, $id) : null;
        if (!$atual) json_err('Usuário não encontrado.');

        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $nivel    = $_POST['nivel'] ?? '';
        if ($id === $admin_id) $nivel = $atual['nivel']; // ninguém altera o próprio nível

        if (tam($nome) < 2 || tam($nome) > 150) json_err('O nome deve ter entre 2 e 150 caracteres.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || tam($email) > 190) json_err('Digite um e-mail válido.');
        if ($telefone !== '' && !preg_match('/^[0-9()+\-.\s]{8,30}$/', $telefone)) json_err('Telefone inválido. Use apenas números, espaços, parênteses, + e -.');
        if (!in_array($nivel, ['admin', 'usuario'], true)) json_err('Nível de acesso inválido.');

        $dup = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1");
        $dup->bind_param('si', $email, $id);
        $dup->execute();
        $existe = $dup->get_result()->num_rows > 0;
        $dup->close();
        if ($existe) json_err('Já existe outro usuário com esse e-mail.');

        if ($nivel !== $atual['nivel']) { // alteração crítica: confirmação explícita
            if (($_POST['confirmar_nivel'] ?? '') !== '1') json_err('Alterar o nível de acesso exige confirmação.');
            if ($atual['nivel'] === 'admin') {
                $r = $conn->query("SELECT COUNT(*) AS c FROM usuarios WHERE nivel = 'admin'");
                if ((int)$r->fetch_assoc()['c'] <= 1) json_err('O sistema precisa ter pelo menos um administrador.');
            }
        }

        $tel_db = $telefone !== '' ? $telefone : null;
        $mudancas = [];
        if ($nome !== $atual['nome'])   $mudancas['nome']  = [$atual['nome'], $nome];
        if ($email !== $atual['email']) $mudancas['email'] = [$atual['email'], $email];
        if (($tel_db ?? '') !== (string)($atual['telefone'] ?? '')) $mudancas['telefone'] = [$atual['telefone'], $tel_db];
        if ($nivel !== $atual['nivel']) $mudancas['nivel'] = [$atual['nivel'], $nivel];

        if (!$mudancas) json_ok(['mensagem' => 'Nenhuma alteração: os dados já estavam iguais.',
                                 'usuario' => saida_usuario($atual, $admin_id), 'log' => buscar_log($conn, $id)]);
        try {
            $conn->begin_transaction();
            $up = $conn->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, nivel = ? WHERE id = ?");
            $up->bind_param('ssssi', $nome, $email, $tel_db, $nivel, $id);
            $up->execute();
            $up->close();
            $lg = $conn->prepare("INSERT INTO usuarios_log (admin_id, usuario_id, campo, valor_antigo, valor_novo) VALUES (?, ?, ?, ?, ?)");
            foreach ($mudancas as $campo => [$antigo, $novo]) {
                $lg->bind_param('iisss', $admin_id, $id, $campo, $antigo, $novo);
                $lg->execute();
            }
            $lg->close();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('api_usuarios salvar: ' . $e->getMessage());
            json_err('Não foi possível salvar. Nada foi alterado.');
        }
        if ($id === $admin_id) { $_SESSION['nome'] = $nome; $_SESSION['email'] = $email; }
        json_ok(['mensagem' => 'Usuário atualizado com sucesso.',
                 'usuario' => saida_usuario(buscar_usuario($conn, $id), $admin_id), 'log' => buscar_log($conn, $id)]);

    // ── Redefinir senha de um CLIENTE (define uma nova; a antiga nunca é exibida) ──
    case 'redefinir_senha':
        $id = (int)($_POST['id'] ?? 0);
        $alvo = $id ? buscar_usuario($conn, $id) : null;
        if (!$alvo) json_err('Usuário não encontrado.');
        if ($id === $admin_id) json_err('Para trocar a sua própria senha, use a opção "Alterar senha" da sua conta.');
        if ($alvo['nivel'] !== 'usuario') json_err('Só é possível redefinir a senha de contas de cliente.');
        if (($_POST['confirmar'] ?? '') !== '1') json_err('A redefinição de senha exige confirmação.');

        $nova = (string)($_POST['nova_senha'] ?? '');
        if (strlen($nova) < 6) json_err('A senha deve ter no mínimo 6 caracteres.');
        if (strlen($nova) > 72) json_err('A senha deve ter no máximo 72 caracteres.');

        $hash = password_hash($nova, PASSWORD_DEFAULT);
        try {
            $conn->begin_transaction();
            $up = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ? AND nivel = 'usuario'");
            $up->bind_param('si', $hash, $id);
            $up->execute();
            $up->close();
            $campo = 'senha'; $novo_log = '(redefinida pelo administrador)'; $nulo = null;
            $lg = $conn->prepare("INSERT INTO usuarios_log (admin_id, usuario_id, campo, valor_antigo, valor_novo) VALUES (?, ?, ?, ?, ?)");
            $lg->bind_param('iisss', $admin_id, $id, $campo, $nulo, $novo_log);
            $lg->execute();
            $lg->close();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('api_usuarios redefinir_senha: ' . $e->getMessage());
            json_err('Não foi possível redefinir a senha. Nada foi alterado.');
        }
        json_ok(['mensagem' => 'Senha redefinida com sucesso.', 'log' => buscar_log($conn, $id)]);

    default:
        json_err('Ação desconhecida.');
}
