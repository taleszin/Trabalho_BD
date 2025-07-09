<?php
include('../classes/config.php');
include('../classes/QuestaoService.php');

$questaoService = new QuestaoService();

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $idQuestao = $_GET['id'];
    $questao = $questaoService->getQuestao($idQuestao);
    $alternativas = $questaoService->obterAlternativas($idQuestao); // Recuperar alternativas
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idQuestao'])) {
    $idQuestao = $_POST['idQuestao'];
    $enunciado = $_POST['enunciado'];
    $idAssunto = $_POST['idAssunto'];
    $alternativas = [];
    for ($i = 0; $i < 4; $i++) {
        $alternativas[] = [
            'letra' => chr(65 + $i),
            'texto' => $_POST['alternativa' . $i],
            'correta' => isset($_POST['correta' . $i]) && $_POST['correta' . $i] == 'on' ? true : false
        ];
    }

    $questaoService->editarQuestao($idQuestao, $enunciado, $idAssunto, $alternativas);
    header('Location: visualizarQuestao.php?id=' . $idQuestao); // Redireciona após salvar
}

$questaoService->fecharConexao();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Questão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Editar Questão</a>
    </div>
</nav>

<div class="container mt-4">
    <h2>Editar Questão: <?php echo isset($questao) ? $questao['enunciado'] : ''; ?></h2>
    <form method="POST" action="editarQuestao.php">
        <input type="hidden" name="idQuestao" value="<?php echo isset($questao) ? $questao['id'] : ''; ?>">

        <div class="mb-3">
            <label for="enunciado" class="form-label">Enunciado</label>
            <textarea class="form-control" id="enunciado" name="enunciado" rows="3" required><?php echo isset($questao) ? $questao['enunciado'] : ''; ?></textarea>
        </div>

        <div class="mb-3">
            <label for="idAssunto" class="form-label">Assunto</label>
            <select class="form-select" id="idAssunto" name="idAssunto" required>
                <option value="" disabled>Selecione um assunto</option>
                <option value="1" <?php echo isset($questao) && $questao['idAssunto'] == 1 ? 'selected' : ''; ?>>Assunto 1</option>
                <option value="2" <?php echo isset($questao) && $questao['idAssunto'] == 2 ? 'selected' : ''; ?>>Assunto 2</option>
                <option value="3" <?php echo isset($questao) && $questao['idAssunto'] == 3 ? 'selected' : ''; ?>>Assunto 3</option>
            </select>
        </div>

        <h5>Alternativas</h5>
        <?php for ($i = 0; $i < 4; $i++): ?>
            <div class="mb-3">
                <label for="alternativa<?php echo $i; ?>" class="form-label">Alternativa <?php echo chr(65 + $i); ?></label>
                <input type="text" class="form-control" id="alternativa<?php echo $i; ?>" name="alternativa<?php echo $i; ?>" value="<?php echo isset($alternativas[$i]) ? $alternativas[$i]['texto'] : ''; ?>" required>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="correta<?php echo $i; ?>" id="correta<?php echo $i; ?>" <?php echo isset($alternativas[$i]) && $alternativas[$i]['correta'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="correta<?php echo $i; ?>">Alternativa Correta</label>
                </div>
            </div>
        <?php endfor; ?>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    </form>
</div>

</body>
</html>
