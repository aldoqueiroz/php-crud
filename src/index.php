<?php
declare(strict_types=1);

// ---------- Configuração do banco ----------
$dbPath = '/var/www/data/crud.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erro ao conectar no SQLite: ' . $e->getMessage());
}

// Cria a tabela se não existir
$pdo->exec("
    CREATE TABLE IF NOT EXISTS contatos (
        id      INTEGER PRIMARY KEY AUTOINCREMENT,
        nome    TEXT NOT NULL,
        email   TEXT NOT NULL,
        telefone TEXT
    )
");

// ---------- Processamento do CRUD ----------
$acao = $_POST['acao'] ?? $_GET['acao'] ?? 'listar';
$msg  = '';

try {
    if ($acao === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("INSERT INTO contatos (nome, email, telefone) VALUES (:n, :e, :t)");
        $stmt->execute([
            ':n' => trim($_POST['nome'] ?? ''),
            ':e' => trim($_POST['email'] ?? ''),
            ':t' => trim($_POST['telefone'] ?? ''),
        ]);
        $msg = 'Contato cadastrado com sucesso!';
        $acao = 'listar';
    }

    if ($acao === 'atualizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("UPDATE contatos SET nome=:n, email=:e, telefone=:t WHERE id=:id");
        $stmt->execute([
            ':n'  => trim($_POST['nome'] ?? ''),
            ':e'  => trim($_POST['email'] ?? ''),
            ':t'  => trim($_POST['telefone'] ?? ''),
            ':id' => (int)($_POST['id'] ?? 0),
        ]);
        $msg = 'Contato atualizado com sucesso!';
        $acao = 'listar';
    }

    if ($acao === 'excluir' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("DELETE FROM contatos WHERE id = :id");
        $stmt->execute([':id' => (int)$_GET['id']]);
        $msg = 'Contato excluído com sucesso!';
        $acao = 'listar';
    }
} catch (PDOException $e) {
    $msg = 'Erro: ' . $e->getMessage();
}

// ---------- Carrega dados para edição, se for o caso ----------
$contatoEdicao = null;
if ($acao === 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM contatos WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $contatoEdicao = $stmt->fetch();
    if (!$contatoEdicao) {
        $msg = 'Contato não encontrado.';
        $acao = 'listar';
    }
}

// ---------- Lista todos ----------
$contatos = $pdo->query("SELECT * FROM contatos ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>CRUD PHP + SQLite + Docker</title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        background: #f4f5f7; margin: 0; padding: 2rem;
        color: #222;
    }
    .container { max-width: 900px; margin: 0 auto; }
    h1 { color: #0d4f8b; margin-top: 0; }
    h2 { color: #333; font-size: 1.15rem; margin-top: 2rem; }
    .card {
        background: #fff; border-radius: 8px; padding: 1.5rem;
        box-shadow: 0 2px 6px rgba(0,0,0,.08); margin-bottom: 1.5rem;
    }
    .msg {
        background: #d4edda; color: #155724; padding: .75rem 1rem;
        border-radius: 6px; margin-bottom: 1rem; border: 1px solid #c3e6cb;
    }
    label { display: block; font-size: .85rem; margin-bottom: .25rem; color: #555; }
    input[type=text], input[type=email] {
        width: 100%; padding: .55rem .7rem; border: 1px solid #ccc;
        border-radius: 6px; margin-bottom: .9rem; font-size: .95rem;
    }
    input:focus { outline: none; border-color: #0d4f8b; }
    button, .btn {
        background: #0d4f8b; color: #fff; border: none; padding: .6rem 1.2rem;
        border-radius: 6px; cursor: pointer; font-size: .9rem; text-decoration: none;
        display: inline-block;
    }
    button:hover, .btn:hover { background: #093a66; }
    .btn-danger { background: #c0392b; }
    .btn-danger:hover { background: #962d22; }
    .btn-secondary { background: #6c757d; }
    .btn-secondary:hover { background: #545b62; }
    table { width: 100%; border-collapse: collapse; margin-top: .5rem; }
    th, td { text-align: left; padding: .65rem .5rem; border-bottom: 1px solid #eee; font-size: .9rem; }
    th { background: #f8f9fa; color: #444; font-weight: 600; }
    tr:hover td { background: #fafbfc; }
    .actions a { margin-right: .5rem; font-size: .85rem; }
    .empty { color: #888; font-style: italic; padding: 1rem 0; }
</style>
</head>
<body>
<div class="container">
    <h1>🐘 CRUD PHP + SQLite</h1>
    <p>Rodando em Docker · <code>localhost:8080</code></p>

    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><?= $contatoEdicao ? '✏️ Editar contato' : '➕ Novo contato' ?></h2>
        <form method="post" action="index.php">
            <input type="hidden" name="acao" value="<?= $contatoEdicao ? 'atualizar' : 'criar' ?>">
            <?php if ($contatoEdicao): ?>
                <input type="hidden" name="id" value="<?= (int)$contatoEdicao['id'] ?>">
            <?php endif; ?>

            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" required
                   value="<?= htmlspecialchars($contatoEdicao['nome'] ?? '') ?>">

            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($contatoEdicao['email'] ?? '') ?>">

            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone"
                   value="<?= htmlspecialchars($contatoEdicao['telefone'] ?? '') ?>">

            <button type="submit"><?= $contatoEdicao ? 'Salvar alterações' : 'Cadastrar' ?></button>
            <?php if ($contatoEdicao): ?>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2>📋 Contatos cadastrados (<?= count($contatos) ?>)</h2>
        <?php if (empty($contatos)): ?>
            <p class="empty">Nenhum contato cadastrado ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contatos as $c): ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['telefone'] ?? '—') ?></td>
                        <td class="actions">
                            <a href="?acao=editar&id=<?= (int)$c['id'] ?>">Editar</a>
                            <a href="?acao=excluir&id=<?= (int)$c['id'] ?>"
                               class="btn-danger"
                               onclick="return confirm('Excluir este contato?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>