<?php
session_start();
require_once '../classes/config.php';
require_once '../classes/QuestaoService.php';
require_once '../classes/RespostaService.php';
$questaoService = new QuestaoService($conexao);
$respostaService = new RespostaService($conexao);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $idQuestao = filter_input(INPUT_POST, 'idQuestao', FILTER_VALIDATE_INT);
    $texto = filter_input(INPUT_POST, 'texto_comentario', FILTER_SANITIZE_STRING);
    $comentarioPaiId = filter_input(INPUT_POST, 'comentario_pai_id', FILTER_VALIDATE_INT) ?: null;
    $idAluno = $_SESSION['id_usuario'] ?? null;
    header('Content-Type: application/json');
    if ($idQuestao && $texto && $idAluno) {
        if ($questaoService->salvarComentario($idQuestao, $idAluno, $texto, $comentarioPaiId)) {
            $nomeAluno = $_SESSION['nome_usuario'] ?? 'Você';
            echo json_encode([
                'success' => true,
                'comment' => [
                    'texto' => $texto,
                    'nomeAluno' => $nomeAluno,
                    'dataComentario' => date('Y-m-d H:i:s'),
                    'isReply' => !empty($comentarioPaiId)
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar o comentário no banco de dados.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos ou usuário não está logado.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_resposta') {
    $idQuestao = filter_input(INPUT_POST, 'idQuestao', FILTER_VALIDATE_INT);
    $letraRespondida = filter_input(INPUT_POST, 'letra', FILTER_SANITIZE_STRING);
    $idAluno = $_SESSION['id_usuario'] ?? null;
    $dataResposta = date('Y-m-d H:i:s');
    header('Content-Type: application/json');
    if ($idQuestao && $letraRespondida && $idAluno) {
        $alternativas = $questaoService->getAlternativas($idQuestao);
        $letraCorreta = null;
        foreach ($alternativas as $alt) {
            if ($alt['correta']) {
                $letraCorreta = $alt['letra'];
                break;
            }
        }
        $respostaEstaCorreta = (strtoupper($letraRespondida) === strtoupper($letraCorreta));
        $respostaService->salvarResposta($idAluno, $idQuestao, $letraRespondida, $respostaEstaCorreta, $dataResposta);
        echo json_encode(['success' => true, 'correta' => $respostaEstaCorreta]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos ou usuário não está logado.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_details') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
        exit;
    }
    $idQuestao = filter_input(INPUT_GET, 'idQuestao', FILTER_VALIDATE_INT);
    if ($idQuestao) {
        $data = $questaoService->getQuestaoById($idQuestao);
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID da questão inválido.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'add_update_questao')) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
        exit;
    }
    $idQuestao = filter_input(INPUT_POST, 'idQuestao', FILTER_VALIDATE_INT);
    $enunciado = $_POST['enunciado'];
    $disciplina = $_POST['disciplina'];
    $assuntos = $_POST['assuntos'];
    $comentario = $_POST['comentario_professor'];
    $letraCorreta = $_POST['letraCorreta'];
    $alternativas = $_POST['alternativas'];
    try {
        if ($idQuestao) {
            $questaoService->updateQuestao($idQuestao, $enunciado, 'multipla', $assuntos, $comentario, $disciplina);
            $questaoService->salvarAlternativas($idQuestao, $alternativas, $letraCorreta);
            echo json_encode(['success' => true, 'message' => 'Questão atualizada com sucesso!']);
        } else {
            $newIdQuestao = $questaoService->salvarQuestao(null, $enunciado, 'multipla', $assuntos, $comentario, $disciplina);
            if ($newIdQuestao) {
                $questaoService->salvarAlternativas($newIdQuestao, $alternativas, $letraCorreta);
                echo json_encode(['success' => true, 'message' => 'Questão adicionada com sucesso!']);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Erro ao criar a questão. Possivelmente duplicada.']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_questao') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
        exit;
    }
    $idQuestao = filter_input(INPUT_POST, 'idQuestao', FILTER_VALIDATE_INT);
    if ($idQuestao && $questaoService->deleteQuestao($idQuestao)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover a questão.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_tag') {
    header('Content-Type: application/json');
    $idAluno = $_SESSION['id_usuario'] ?? null;
    $idQuestao = filter_input(INPUT_POST, 'idQuestao', FILTER_VALIDATE_INT);
    $idTag = filter_input(INPUT_POST, 'idTag', FILTER_VALIDATE_INT);

    if ($idAluno && $idQuestao && $idTag) {
        $newState = $questaoService->toggleTag($idAluno, $idQuestao, $idTag);
        echo json_encode(['success' => true, 'newState' => $newState]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    }
    exit;
}

$idAlunoLogado = $_SESSION['id_usuario'] ?? null;
$filtroDisciplina = $_GET['disciplina'] ?? '';
$filtroAssuntos = isset($_GET['assuntos']) && is_array($_GET['assuntos']) ? $_GET['assuntos'] : [];
$buscaTexto = $_GET['busca'] ?? '';
$ordem = $_GET['ordem'] ?? 'DESC';
$disciplinas = $questaoService->getDisciplinas();
$assuntos = [];
if ($filtroDisciplina) {
    $assuntos = $questaoService->getAssuntos($filtroDisciplina);
}
$paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itensPorPagina = 5;
$totalQuestoes = $questaoService->getTotalQuestoes($filtroDisciplina, $filtroAssuntos, $buscaTexto);
$totalPaginas = ceil($totalQuestoes / $itensPorPagina);
$questoes = $questaoService->getQuestoes($filtroDisciplina, $filtroAssuntos, $paginaAtual, $itensPorPagina, $idAlunoLogado, $buscaTexto, $ordem);
$queryParams = ['disciplina' => $filtroDisciplina, 'assuntos' => $filtroAssuntos, 'busca' => $buscaTexto, 'ordem' => $ordem];
$availableTags = $questaoService->getAvailableTags();

include_once 'header.php';

function render_comentarios($comentarios, $idQuestao, $queryParams, $isReply = false) {
    $class = $isReply ? 'comentario-resposta ps-4' : 'list-group list-group-flush';
    echo "<ul class='{$class}'>";
    foreach ($comentarios as $comentario) {
        $dataComentario = new DateTime($comentario['dataComentario']);
        $dataFormatted = $dataComentario->format('d/m/Y \à\s H:i');
        ?>
        <li class="list-group-item comentario-item">
            <div class="d-flex w-100 justify-content-between">
                <strong class="mb-1 text-primary"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($comentario['nomeAluno']); ?></strong>
                <small class="text-muted"><?= $dataFormatted; ?></small>
            </div>
            <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($comentario['texto'])); ?></p>
            <div class="comentario-actions">
                <button class="btn btn-sm btn-link text-decoration-none ps-0" type="button" data-bs-toggle="collapse" data-bs-target="#responder-<?= $comentario['idComentario']; ?>">
                    <i class="bi bi-reply me-1"></i>Responder
                </button>
            </div>
            <div class="collapse mt-3" id="responder-<?= $comentario['idComentario']; ?>">
                <form class="comment-form responder-form">
                    <input type="hidden" name="action" value="add_comment">
                    <input type="hidden" name="idQuestao" value="<?= $idQuestao; ?>">
                    <input type="hidden" name="comentario_pai_id" value="<?= $comentario['idComentario']; ?>">
                    <div class="mb-2">
                        <textarea name="texto_comentario" class="form-control form-control-sm" rows="2" placeholder="Escreva sua resposta..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Enviar Resposta</button>
                </form>
            </div>
            <?php
            if (!empty($comentario['respostas'])) {
                render_comentarios($comentario['respostas'], $idQuestao, $queryParams, true);
            }
            ?>
        </li>
        <?php
    }
    echo "</ul>";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Questões - MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        html, body { max-width: 100vw; overflow-x: hidden; }
        body { background: linear-gradient(135deg, #f6f8fa 60%, #e3f0ff 100%); font-family: 'Inter', 'Segoe UI', Arial, sans-serif; }
        .container { max-width: 950px; margin: 0 auto; }
        .questao-card { margin-bottom: 2.5rem; border: none; border-radius: 1.2rem; box-shadow: 0 6px 32px 0 rgba(0, 60, 120, 0.10), 0 1.5px 4px 0 rgba(0,0,0,.03); background: #fff; transition: box-shadow .2s; }
        .questao-card:hover { box-shadow: 0 12px 36px 0 rgba(0, 60, 120, 0.18), 0 2px 8px 0 rgba(0,0,0,.04); }
        .questao-header { background: linear-gradient(90deg, #0d6efd 0%, #3ec6e0 100%); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
        .questao-header h5 { font-weight: 700; letter-spacing: 0.02em; }
        .badge.bg-info-subtle { background: #e3f0ff !important; color: #0d6efd !important; font-weight: 600; font-size: 1.1em; }
        .card-body { padding: 2rem 1.5rem 1.5rem 1.5rem; }
        .alternativas-form .form-check { margin-bottom: 1.1rem; }
        .alternativas-form .form-check input[type="radio"] { display: none; }
        .alternativas-form .form-check label { display: flex; align-items: flex-start; padding: 1.1rem 1.5rem; border: 2px solid #e3e8f0; border-radius: 0.7rem; cursor: pointer; transition: background-color 0.3s, border-color 0.3s, color 0.3s; width: 100%; min-height: 54px; position: relative; background: #fafdff; font-size: 1.08em; font-weight: 500; }
        .alternativas-form .form-check label:hover { background-color: #f0f7ff; border-color: #b6d4fa; }
        .alternativas-form .form-check input[type="radio"]:checked + label { border-color: #0d6efd; background-color: #e9f5ff; color: #0c54a3; font-weight: 700; }
        .alternativa-letra { font-weight: bold; margin-right: 1.1rem; border: 2px solid #b6d4fa; border-radius: 50%; width: 38px; height: 38px; display: inline-flex; justify-content: center; align-items: center; transition: all 0.3s; flex-shrink: 0; margin-top: 2px; font-size: 1.15em; background: #fafdff; }
        .alternativas-form .form-check input[type="radio"]:checked + label .alternativa-letra { background-color: #0d6efd; border-color: #0d6efd; color: #fff; }
        .feedback-alternativa { display: block; padding: 1rem 1.2rem; margin-top: 0.7rem; border-radius: 0.7rem; font-size: 1.04em; background: #f8fafd; color: #333; box-shadow: 0 2px 12px rgba(0,0,0,0.04); position: relative; z-index: 2; width: 100%; white-space: pre-line; word-break: break-word; max-height: none; overflow: visible; border-left: 5px solid #e3e8f0; }
        .feedback-alternativa:not(.show-feedback) { display: none; }
        .feedback-alternativa.feedback-correto { background-color: #e6f9f0; color: #0f5132; border-left: 5px solid #198754; }
        .feedback-alternativa.feedback-incorreto { background-color: #fff0f3; color: #842029; border-left: 5px solid #dc3545; }
        .alternativas-form.respondida label { cursor: not-allowed; opacity: 0.98; }
        .alternativas-form.respondida .alternativa-correta { border-color: #198754 !important; background-color: #e6f9f0 !important; color: #0f5132 !important; }
        .alternativas-form.respondida .alternativa-correta .alternativa-letra { background-color: #198754; border-color: #198754; color: #fff; }
        .alternativas-form.respondida .alternativa-incorreta { border-color: #dc3545 !important; background-color: #fff0f3 !important; color: #842029 !important; }
        .alternativas-form.respondida .alternativa-incorreta .alternativa-letra { background-color: #dc3545; border-color: #dc3545; color: #fff; }
        .alternativas-form .badge.bg-primary { background: #0d6efd !important; font-weight: 600; font-size: 0.95em; margin-left: 0.7rem; }
        .alternativas-form .badge.bg-danger { background: #dc3545 !important; font-weight: 600; font-size: 0.95em; margin-left: 0.7rem; }
        .comentario-professor-area { display: none; margin-top: 1.2rem; padding: 1.1rem 1.3rem; background-color: #fffbe6; border: 1.5px solid #ffe69c; border-radius: .7rem; font-size: 1.04em; }
        .nav-tabs .nav-link { color: #495057; font-weight: 500; }
        .nav-tabs .nav-link.active { color: #0d6efd; border-color: #dee2e6 #dee2e6 #fff; font-weight: 700; }
        .comentario-item { border-bottom: 1px solid #eee; }
        .comentario-resposta { border-left: 3px solid #0d6efd; margin-top: 1rem; background: #f8f9fa; border-radius: .5rem; }
        .filtro-card { border-radius: 1rem; border: none; box-shadow: 0 2px 12px 0 rgba(0,0,0,.04); }
        .filtro-card .form-label { font-weight: 600; color: #0d6efd; }
        .pagination .page-link { border-radius: 0.5rem !important; margin: 0 0.15rem; font-weight: 500; }
        .pagination .page-item.active .page-link { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .assuntos-container { max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 0.375rem; background-color: #fff; }
        .tags-container { padding-top: 1rem; border-top: 1px solid #f0f0f0; margin-top: 1.5rem; }
        .btn-tag { transition: all 0.2s ease-in-out; }
        @media (max-width: 600px) {
            html, body { max-width: 100vw; overflow-x: hidden; }
            .container { max-width: 100vw; padding: 0 5px; }
            .questao-card { margin-bottom: 1.2rem; }
            .alternativas-form .form-check label { padding: 0.7rem 0.7rem; font-size: 0.98em; }
            .card-body { padding: 1.2rem 0.5rem 1rem 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <h1 class="h2 mb-0" style="font-weight:700;letter-spacing:.01em;color:#0d6efd;">Banco de Questões</h1>
        </div>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
        <div class="mb-4 text-end">
            <button class="btn btn-success" id="btn-nova-questao" data-bs-toggle="modal" data-bs-target="#modalQuestao">
                <i class="bi bi-plus-circle-fill me-2"></i>Adicionar Nova Questão
            </button>
        </div>
        <?php endif; ?>
        <div class="card filtro-card mb-5">
            <div class="card-body">
                <form action="questoes.php" method="GET">
                    <div class="row mb-3">
                        <div class="col">
                            <label for="busca" class="form-label">Buscar por palavra no enunciado</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="busca" name="busca" placeholder="Ex: cardiologia, república, etc..." value="<?= htmlspecialchars($buscaTexto) ?>">
                                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="disciplina" class="form-label">Filtrar por Disciplina</label>
                            <select name="disciplina" id="disciplina" class="form-select">
                                <option value="">Todas as Disciplinas</option>
                                <?php foreach ($disciplinas as $disciplina): ?>
                                    <option value="<?= htmlspecialchars($disciplina['disciplina']); ?>" <?= ($filtroDisciplina === $disciplina['disciplina']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($disciplina['disciplina']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ordem" class="form-label">Ordenar por</label>
                            <select name="ordem" id="ordem" class="form-select">
                                <option value="DESC" <?= ($ordem === 'DESC') ? 'selected' : '' ?>>Mais Recentes</option>
                                <option value="ASC" <?= ($ordem === 'ASC') ? 'selected' : '' ?>>Mais Antigas</option>
                            </select>
                        </div>
                        <?php if (!empty($assuntos)): ?>
                        <div class="col-md-4">
                            <label class="form-label">Filtrar por Assunto(s)</label>
                            <div class="assuntos-container">
                                <?php foreach ($assuntos as $assunto): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="assuntos[]" value="<?= htmlspecialchars($assunto) ?>" id="assunto_<?= md5($assunto) ?>" <?= in_array($assunto, $filtroAssuntos) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="assunto_<?= md5($assunto) ?>"><?= htmlspecialchars($assunto) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-9">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill me-2"></i>Aplicar Filtros</button>
                        </div>
                        <div class="col-md-3">
                            <a href="questoes.php" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg me-2"></i>Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php if (empty($questoes)): ?>
            <div class="alert alert-info text-center">Nenhuma questão encontrada com os filtros aplicados.</div>
        <?php else: ?>
            <?php foreach ($questoes as $questao): ?>
                <?php
                $jaRespondeu = false;
                $letraRespondida = null;
                $letraCorreta = null;
                $alunoTags = isset($questao['aluno_tags']) ? explode(',', $questao['aluno_tags']) : [];

                if (isset($_SESSION['id_usuario'])) {
                    $idAluno = $_SESSION['id_usuario'];
                    $jaRespondeu = $respostaService->jaRespondeu($idAluno, $questao['idQuestao']);
                    if ($jaRespondeu) {
                        $stmt = $conexao->prepare("SELECT letraResposta FROM resposta WHERE idAluno = ? AND idQuestao = ? ORDER BY idResposta DESC LIMIT 1");
                        $stmt->bind_param("ii", $idAluno, $questao['idQuestao']);
                        $stmt->execute();
                        $stmt->bind_result($letraRespondida);
                        $stmt->fetch();
                        $stmt->close();
                    }
                }
                $alternativas = $questaoService->getAlternativas($questao['idQuestao']);
                foreach ($alternativas as $alt) {
                    if ($alt['correta']) {
                        $letraCorreta = $alt['letra'];
                        break;
                    }
                }
                ?>
                <div class="card questao-card" id="questao-<?= $questao['idQuestao']; ?>">
                    <div class="card-header questao-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><strong>Questão #<?= $questao['idQuestao']; ?></strong></h5>
                        <span class="badge bg-info-subtle text-info-emphasis rounded-pill fs-6"><?= htmlspecialchars($questao['disciplina']); ?></span>
                    </div>
                    <div class="card-body">
                        <p class="card-text fs-5" style="font-weight:600;"><?= nl2br(htmlspecialchars($questao['enunciado'])); ?></p>
                        <?php if (!empty($alternativas)): ?>
                            <form class="alternativas-form mt-4 <?= $jaRespondeu ? 'respondida' : '' ?>" data-id-questao="<?= $questao['idQuestao']; ?>">
                                <?php foreach ($alternativas as $alt): ?>
                                    <?php
                                        $isChecked = $jaRespondeu && $letraRespondida === $alt['letra'];
                                        $isCorreta = $alt['correta'] ? true : false;
                                        $isRespondida = $jaRespondeu;
                                        $feedbackClass = '';
                                        $badge = '';
                                        if ($isRespondida) {
                                            if ($isCorreta) {
                                                $feedbackClass = 'feedback-correto show-feedback';
                                                if ($isChecked) {
                                                    $badge = '<span class="badge bg-primary ms-2">Sua resposta (Correta)</span>';
                                                } else {
                                                    $badge = '<span class="badge bg-success ms-2">Alternativa Correta</span>';
                                                }
                                            } elseif ($isChecked) {
                                                $feedbackClass = 'feedback-incorreto show-feedback';
                                                $badge = '<span class="badge bg-danger ms-2">Sua resposta (Errada)</span>';
                                            } else {
                                                $feedbackClass = 'show-feedback';
                                            }
                                        }
                                    ?>
                                    <div class="form-check">
                                        <input type="radio" name="alternativa_<?= $questao['idQuestao']; ?>" id="alt_<?= $questao['idQuestao']; ?>_<?= $alt['letra']; ?>" value="<?= $alt['letra']; ?>"
                                            <?= $isChecked ? 'checked' : '' ?> <?= $jaRespondeu ? 'disabled' : '' ?>>
                                        <label for="alt_<?= $questao['idQuestao']; ?>_<?= $alt['letra']; ?>"
                                            data-letra="<?= $alt['letra']; ?>"
                                            data-correta="<?= $alt['correta']; ?>"
                                            class="
                                                <?php if ($isRespondida && $isCorreta) echo 'alternativa-correta'; ?>
                                                <?php if ($isRespondida && !$isCorreta && $isChecked) echo 'alternativa-incorreta'; ?>
                                            ">
                                            <span class="alternativa-letra"><?= $alt['letra']; ?></span>
                                            <span><?= htmlspecialchars($alt['texto']); ?></span>
                                            <?= $isRespondida ? $badge : '' ?>
                                        </label>
                                        <div class="feedback-alternativa <?= $feedbackClass ?>" data-feedback-for="<?= $alt['letra']; ?>">
                                            <?= !empty($alt['feedback']) ? nl2br(htmlspecialchars($alt['feedback'])) : '<i>Sem feedback específico para esta alternativa.</i>' ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="mt-4">
                                    <?php if ($jaRespondeu): ?>
                                        <button type="button" class="btn btn-secondary w-100" disabled>Você já respondeu</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-responder w-100"><i class="bi bi-check2-square me-2"></i>Responder</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        <?php endif; ?>
                        <div class="mt-4">
                             <div class="comentario-professor-area" id="comentario-professor-<?= $questao['idQuestao']; ?>">
                                 <p><strong><i class="bi bi-mortarboard-fill me-2"></i>Comentário do Professor:</strong></p>
                                 <p class="mb-0"><?= !empty($questao['comentario']) ? nl2br(htmlspecialchars($questao['comentario'])) : '<i>Nenhum comentário disponível.</i>'; ?></p>
                             </div>
                        </div>
                        <?php if (isset($_SESSION['id_usuario'])): ?>
                        <div class="tags-container">
                            <?php foreach($availableTags as $tag): ?>
                                <?php $is_active = in_array($tag['idTag'], $alunoTags); ?>
                                <button
                                    class="btn btn-sm btn-tag <?= $is_active ? 'active' : '' ?>"
                                    data-id-questao="<?= $questao['idQuestao'] ?>"
                                    data-id-tag="<?= $tag['idTag'] ?>"
                                    style="--bs-btn-bg: <?= $is_active ? $tag['cor'] : 'transparent'; ?>; --bs-btn-color: <?= $is_active ? '#fff' : $tag['cor']; ?>; --bs-btn-border-color: <?= $tag['cor']; ?>; --bs-btn-hover-bg: <?= $tag['cor']; ?>; --bs-btn-hover-color: #fff; --bs-btn-active-bg: <?= $tag['cor']; ?>; --bs-btn-active-color: #fff; --bs-btn-active-border-color: <?= $tag['cor']; ?>;">
                                    <i class="<?= htmlspecialchars($tag['icone']) ?> me-1"></i>
                                    <?= htmlspecialchars($tag['nome']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="mt-4">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab-<?= $questao['idQuestao']; ?>" role="tablist">
                                    <button class="nav-link" id="nav-comentarios-tab-<?= $questao['idQuestao']; ?>" data-bs-toggle="tab" data-bs-target="#nav-comentarios-<?= $questao['idQuestao']; ?>" type="button" role="tab"><i class="bi bi-chat-quote me-1"></i>Comentários da Comunidade</button>
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent-<?= $questao['idQuestao']; ?>">
                                <div class="tab-pane fade p-3" id="nav-comentarios-<?= $questao['idQuestao']; ?>" role="tabpanel">
                                    <div class="comentarios-container">
                                        <?php
                                        $comentarios = $questaoService->getComentarios($questao['idQuestao']);
                                        if (!empty($comentarios)) {
                                            render_comentarios($comentarios, $questao['idQuestao'], $queryParams);
                                        } else {
                                            echo "<p class='text-muted text-center no-comments'><i>Seja o primeiro a comentar!</i></p>";
                                        }
                                        ?>
                                    </div>
                                    <hr>
                                    <div class="mt-3">
                                        <strong>Deixe seu comentário</strong>
                                        <form class="comment-form mt-2">
                                            <input type="hidden" name="action" value="add_comment">
                                            <input type="hidden" name="idQuestao" value="<?= $questao['idQuestao']; ?>">
                                            <div class="mb-2">
                                                <textarea name="texto_comentario" class="form-control" rows="3" placeholder="Compartilhe suas dúvidas ou observações..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-2"></i>Publicar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0 pt-0 text-end">
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?= $questao['idQuestao']; ?>" data-bs-toggle="modal" data-bs-target="#modalQuestao">
                            <i class="bi bi-pencil-square"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $questao['idQuestao']; ?>">
                            <i class="bi bi-trash3-fill"></i> Remover
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Navegação das questões" class="mt-5">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($paginaAtual <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $paginaAtual - 1])); ?>">Anterior</a>
                </li>
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?= ($i == $paginaAtual) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $i])); ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($paginaAtual >= $totalPaginas) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($queryParams, ['page' => $paginaAtual + 1])); ?>">Próxima</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <div class="modal fade" id="modalQuestao" tabindex="-1" aria-labelledby="modalQuestaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form id="form-questao">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalQuestaoLabel">Adicionar/Editar Questão</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_update_questao">
                        <input type="hidden" id="idQuestao" name="idQuestao">
                        
                        <div class="mb-3">
                            <label for="enunciado" class="form-label">Enunciado</label>
                            <textarea class="form-control" id="enunciado" name="enunciado" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="disciplina-modal" class="form-label">Disciplina</label>
                                <input type="text" class="form-control" id="disciplina-modal" name="disciplina" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="assuntos-modal" class="form-label">Assuntos (separados por vírgula)</label>
                                <input type="text" class="form-control" id="assuntos-modal" name="assuntos" required>
                            </div>
                        </div>
                        
                        <hr>
                        <h6>Alternativas</h6>
                        <div id="alternativas-container">
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="btn-add-alternativa"><i class="bi bi-plus"></i> Adicionar Alternativa</button>
                        
                        <hr>
                        <div class="mb-3">
                            <label for="comentario_professor" class="form-label">Comentário do Professor (Opcional)</label>
                            <textarea class="form-control" id="comentario_professor" name="comentario_professor" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Questão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            gsap.from('.questao-card', { duration: 0.5, opacity: 0, y: 30, stagger: 0.1, ease: 'power2.out' });
            document.querySelectorAll('.btn-responder').forEach(button => {
                button.addEventListener('click', function() {
                    const form = this.closest('.alternativas-form');
                    const idQuestao = form.dataset.idQuestao;
                    const respostaSelecionadaInput = form.querySelector(`input[name="alternativa_${idQuestao}"]:checked`);
                    if (!respostaSelecionadaInput) {
                        Swal.fire({ title: 'Atenção!', text: 'Por favor, selecione uma alternativa antes de responder.', icon: 'warning', confirmButtonColor: '#0d6efd' });
                        return;
                    }
                    const correctLabel = form.querySelector('label[data-correta="1"]');
                    const letraCorreta = correctLabel ? correctLabel.dataset.letra : null;
                    const letraSelecionada = respostaSelecionadaInput.value;
                    form.classList.add('respondida');
                    gsap.to(this, { height: 0, opacity: 0, duration: 0.3, onComplete: () => this.style.display = 'none' });
                    form.querySelectorAll('.form-check label').forEach(label => {
                        const letraLabel = label.dataset.letra;
                        const isCorrect = label.dataset.correta == '1';
                        const feedbackDiv = form.querySelector(`.feedback-alternativa[data-feedback-for="${letraLabel}"]`);
                        gsap.set(label, { scale: 1 });
                        feedbackDiv.classList.add('show-feedback');
                        feedbackDiv.style.display = 'block';
                        feedbackDiv.style.maxHeight = 'none';
                        feedbackDiv.style.overflow = 'visible';
                        let tl = gsap.timeline();
                        if (isCorrect) {
                            label.classList.add('alternativa-correta');
                            tl.to(label, { scale: 1.02, duration: 0.2, ease: 'power2.out' }).to(label, { scale: 1, duration: 0.2 });
                            feedbackDiv.classList.add('feedback-correto');
                            gsap.fromTo(feedbackDiv, { opacity: 0 }, { opacity: 1, duration: 0.4, delay: 0.3 });
                        }
                        else if (letraLabel === letraSelecionada) {
                            label.classList.add('alternativa-incorreta');
                            tl.to(label, { x: -5, duration: 0.05, repeat: 3, yoyo: true, ease: 'power1.inOut' });
                            feedbackDiv.classList.add('feedback-incorreto');
                            gsap.fromTo(feedbackDiv, { opacity: 0 }, { opacity: 1, duration: 0.4, delay: 0.3 });
                        }
                    });
                    const comentarioProfessor = document.getElementById(`comentario-professor-${idQuestao}`);
                    if (comentarioProfessor) {
                        gsap.fromTo(comentarioProfessor, { height: 0, opacity: 0, display: 'block' }, { height: 'auto', opacity: 1, duration: 0.5, delay: 0.5 });
                    }
                    fetch('questoes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'salvar_resposta',
                            idQuestao: idQuestao,
                            letra: letraSelecionada
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.correta) {
                                Swal.fire({ title: 'Acertou!', text: 'Sua resposta foi salva e está correta.', icon: 'success', timer: 1500, showConfirmButton: false, position: 'top-end', toast: true });
                            } else {
                                Swal.fire({ title: 'Resposta salva!', text: 'Sua resposta foi salva. Reveja o feedback para aprender mais.', icon: 'info', timer: 1800, showConfirmButton: false, position: 'top-end', toast: true });
                            }
                        } else {
                            Swal.fire({ title: 'Erro', text: data.message || 'Não foi possível salvar sua resposta.', icon: 'error' });
                        }
                    })
                    .catch(() => {
                        Swal.fire({ title: 'Erro', text: 'Não foi possível salvar sua resposta.', icon: 'error' });
                    });
                });
            });
            document.querySelectorAll('.alternativas-form.respondida .feedback-alternativa').forEach(function(div) {
                div.classList.add('show-feedback');
                div.style.display = 'block';
                div.style.maxHeight = 'none';
                div.style.overflow = 'visible';
            });
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalButtonHTML = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Publicando...`;
                    fetch('questoes.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({ title: 'Comentário publicado!', text: 'Seu comentário foi adicionado com sucesso.', icon: 'success', showConfirmButton: false, timer: 1800, timerProgressBar: true, position: 'top-end', toast: true, });
                            const newCommentHTML = createCommentHtml(data.comment);
                            const idQuestao = formData.get('idQuestao');
                            const comentarioPaiId = formData.get('comentario_pai_id');
                            if (comentarioPaiId) {
                                const parentCommentLi = this.closest('.list-group-item');
                                let replyList = parentCommentLi.querySelector('.comentario-resposta');
                                if (!replyList) {
                                    replyList = document.createElement('ul');
                                    replyList.className = 'comentario-resposta ps-4';
                                    parentCommentLi.appendChild(replyList);
                                }
                                replyList.insertAdjacentHTML('beforeend', newCommentHTML);
                                const newElement = replyList.lastElementChild;
                                gsap.from(newElement, { opacity: 0, y: 20, duration: 0.5 });
                            } else {
                                let container = document.querySelector(`#nav-comentarios-${idQuestao} .comentarios-container .list-group-flush`);
                                if (!container) {
                                    container = document.createElement('ul');
                                    container.className = 'list-group list-group-flush';
                                    document.querySelector(`#nav-comentarios-${idQuestao} .comentarios-container`).appendChild(container);
                                }
                                const noComments = container.querySelector('.no-comments');
                                if(noComments) noComments.remove();
                                container.insertAdjacentHTML('beforeend', newCommentHTML);
                                const newElement = container.lastElementChild;
                                gsap.from(newElement, { opacity: 0, y: 20, duration: 0.5 });
                            }
                            this.querySelector('textarea').value = '';
                        } else {
                            Swal.fire({ title: 'Oops!', text: data.message || 'Não foi possível publicar seu comentário.', icon: 'error' });
                        }
                    })
                    .catch(error => {
                        Swal.fire({ title: 'Erro de Rede', text: 'Houve um problema de conexão com o servidor.', icon: 'error' });
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonHTML;
                    });
                });
            });
            function createCommentHtml(comment) {
                let [datePart, timePart] = comment.dataComentario.split(' ');
                let [year, month, day] = datePart.split('-');
                let [hour, minute, second] = timePart.split(':');
                let date = new Date(Date.UTC(year, month - 1, day, hour, minute, second));
                date.setHours(date.getHours() - 3);
                let dataFormatted = `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()} às ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
                const escapedText = comment.texto.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                return `
                <li class="list-group-item comentario-item">
                    <div class="d-flex w-100 justify-content-between">
                        <strong class="mb-1 text-primary"><i class="bi bi-person-circle me-2"></i>${comment.nomeAluno}</strong>
                        <small class="text-muted">${dataFormatted}</small>
                    </div>
                    <p class="mb-1 mt-2">${escapedText.replace(/\n/g, '<br>')}</p>
                    <div class="comentario-actions">
                        <small class="text-muted fst-italic">Seu feedback já está ajudando outros estudantes !</small>
                    </div>
                </li>`;
            }
            if(window.location.hash) {
                const hash = window.location.hash;
                const tabTrigger = document.querySelector(`button[data-bs-target="#nav-comentarios-${hash.replace('#questao-', '')}"]`);
                if(tabTrigger) {
                    const tab = new bootstrap.Tab(tabTrigger);
                    tab.show();
                    document.querySelector(hash).scrollIntoView({ behavior: 'smooth' });
                }
            }
            
            document.getElementById('btn-nova-questao')?.addEventListener('click', function() {
                document.getElementById('form-questao').reset();
                document.getElementById('idQuestao').value = '';
                document.getElementById('modalQuestaoLabel').textContent = 'Adicionar Nova Questão';
                document.getElementById('alternativas-container').innerHTML = '';
                for (let i = 0; i < 5; i++) {
                    adicionarCampoAlternativa();
                }
            });

            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const idQuestao = this.dataset.id;
                    document.getElementById('modalQuestaoLabel').textContent = `Editar Questão #${idQuestao}`;
                    
                    fetch(`questoes.php?action=get_details&idQuestao=${idQuestao}`)
                        .then(res => res.json())
                        .then(response => {
                            if (response.success) {
                                const data = response.data;
                                document.getElementById('idQuestao').value = data.idQuestao;
                                document.getElementById('enunciado').value = data.enunciado;
                                document.getElementById('disciplina-modal').value = data.disciplina;
                                document.getElementById('assuntos-modal').value = data.assuntos;
                                document.getElementById('comentario_professor').value = data.comentario;
                                
                                const container = document.getElementById('alternativas-container');
                                container.innerHTML = '';
                                data.alternativas.forEach(alt => {
                                    adicionarCampoAlternativa(alt);
                                });
                            }
                        });
                });
            });

            document.getElementById('btn-add-alternativa')?.addEventListener('click', () => adicionarCampoAlternativa());

            function adicionarCampoAlternativa(alt = null) {
                const container = document.getElementById('alternativas-container');
                const index = container.children.length;
                const letra = String.fromCharCode(65 + index);

                const div = document.createElement('div');
                div.className = 'input-group mb-2';
                div.innerHTML = `
                    <div class="input-group-text">
                        <input class="form-check-input mt-0" type="radio" name="letraCorreta" value="${letra}" ${alt && alt.correta ? 'checked' : ''} required>
                        <label class="ms-2 fw-bold">${letra}</label>
                    </div>
                    <input type="text" class="form-control" name="alternativas[${letra}][texto]" placeholder="Texto da alternativa" value="${alt ? alt.texto.replace(/"/g, '&quot;') : ''}" required>
                    <input type="text" class="form-control" name="alternativas[${letra}][feedback]" placeholder="Feedback (opcional)" value="${alt && alt.feedback ? alt.feedback.replace(/"/g, '&quot;') : ''}">
                `;
                container.appendChild(div);
            }

            document.getElementById('form-questao')?.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('questoes.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Sucesso!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro!', data.message, 'error');
                    }
                });
            });

            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const idQuestao = this.dataset.id;
                    Swal.fire({
                        title: 'Você tem certeza?',
                        text: `A questão #${idQuestao} e todos os seus dados serão removidos permanentemente!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sim, remover!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('questoes.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: `action=delete_questao&idQuestao=${idQuestao}`
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                     Swal.fire('Removido!', 'A questão foi removida.', 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Erro!', data.message, 'error');
                                }
                            })
                        }
                    })
                });
            });

            document.querySelectorAll('.btn-tag').forEach(button => {
                button.addEventListener('click', function(e){
                    e.preventDefault();
                    const idQuestao = this.dataset.idQuestao;
                    const idTag = this.dataset.idTag;

                    fetch('questoes.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            action: 'toggle_tag',
                            idQuestao: idQuestao,
                            idTag: idTag
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.toggle('active');
                            const isActive = this.classList.contains('active');
                            const activeBg = this.style.getPropertyValue('--bs-btn-hover-bg');
                            const activeColor = this.style.getPropertyValue('--bs-btn-hover-color');
                            const inactiveBg = 'transparent';
                            const inactiveColor = this.style.getPropertyValue('--bs-btn-border-color');

                            this.style.backgroundColor = isActive ? activeBg : inactiveBg;
                            this.style.color = isActive ? activeColor : inactiveColor;
                        } else {
                            Swal.fire('Erro', 'Não foi possível alterar a tag.', 'error');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>