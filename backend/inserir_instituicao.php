<?php
include '../classes/config.php'; // Inclua seu arquivo de conexão

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sigla = $_POST['sigla'] ?? '';
    $uf = $_POST['uf'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if (empty($sigla) || empty($uf) || empty($nome)) {
        $mensagem = '<div class="alert alert-danger">Preencha todos os campos obrigatórios.</div>';
    } else {
        $sql = "INSERT INTO instituicao (sigla, UF, nome, status) VALUES (?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sssi", $sigla, $uf, $nome, $status);

        if ($stmt->execute()) {
            $mensagem = '<div class="alert alert-success">Instituição inserida com sucesso! ID: ' . $conexao->insert_id . '</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao inserir instituição: ' . htmlspecialchars($stmt->error) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inserir Instituição</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Inserir Instituição</h2>
    <?php if ($mensagem) echo $mensagem; ?>
    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label for="sigla" class="form-label">Sigla</label>
            <input type="text" class="form-control" id="sigla" name="sigla" maxlength="20" required>
        </div>
        <div class="mb-3">
            <label for="uf" class="form-label">UF</label>
            <input type="text" class="form-control" id="uf" name="uf" maxlength="2" required>
        </div>
        <div class="mb-3">
            <label for="nome" class="form-label">Nome da Instituição</label>
            <input type="text" class="form-control" id="nome" name="nome" maxlength="100" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="1" selected>Ativa</option>
                <option value="0">Inativa</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Inserir</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>