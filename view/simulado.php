<?php
session_start();
require_once '../classes/config.php';
require_once '../classes/SimuladoService.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$idAlunoLogado = $_SESSION['id_usuario'];
$simuladoService = new SimuladoService($conexao);

$idProvaParaVer = filter_input(INPUT_GET, 'id_prova', FILTER_VALIDATE_INT);
$detalhesProva = null;
$listaProvas = [];

if ($idProvaParaVer) {
    $detalhesProva = $simuladoService->getDetalhesProva($idProvaParaVer, $idAlunoLogado);
    if (!$detalhesProva) {
        header("Location: simulado.php");
        exit;
    }
} else {
    $listaProvas = $simuladoService->getProvasByAluno($idAlunoLogado);
}

include_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Simulados - MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #005DFF; --primary-color-light: rgba(0, 93, 255, 0.08);
            --success-color: #03A678; --success-color-light: rgba(3, 166, 120, 0.1);
            --danger-color: #dc3545; --danger-color-light: rgba(220, 53, 69, 0.1);
            --warning-color: #ffc107; --warning-color-light: rgba(255, 193, 7, 0.1);
            --bg-main: #f0f2f5; --bg-card: #ffffff; --text-primary: #1d2b3a;
            --text-secondary: #5a6978; --border-color: #dee2e6;
            --border-radius: 12px; --shadow-md: 0 8px 16px rgba(0,0,0,0.07);
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-main); }
        .main-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 4rem 0; color: white; border-radius: 0 0 1.5rem 1.5rem; }
        .card-simulado { border: none; border-radius: var(--border-radius); box-shadow: var(--shadow-md); transition: all 0.3s ease; }
        .card-simulado:hover { transform: translateY(-5px); }
        .card-simulado .card-body { padding: 1.75rem; }
        .card-simulado .card-footer { background-color: #f8f9fa; }
        .score-badge { font-size: 1.1rem; font-weight: 700; padding: 0.6rem 1rem; }
        .questao-review-card { border-radius: var(--border-radius); border: 1px solid var(--border-color); margin-bottom: 1.5rem; }
        .form-check-label { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; border: 2px solid var(--border-color); border-radius: 10px; cursor: default; }
        .form-check-label strong { flex-shrink: 0; display: grid; place-items: center; width: 28px; height: 28px; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); background-color: var(--bg-main); border: 1px solid var(--border-color); border-radius: 50%; }
        .alternativa-correta { border-color: var(--success-color); background-color: var(--success-color-light); }
        .alternativa-correta strong { background-color: var(--success-color); border-color: var(--success-color); color: white; }
        .alternativa-errada { border-color: var(--danger-color); background-color: var(--danger-color-light); }
        .alternativa-errada strong { background-color: var(--danger-color); border-color: var(--danger-color); color: white; }
        .alternativa-feedback { font-size: 0.9em; background-color: var(--warning-color-light); border-left: 4px solid var(--warning-color); margin: 0.5rem 0 0.5rem 3.5rem; padding: 0.75rem; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="main-header text-center mb-5">
        <div class="container">
            <h1 class="display-5 fw-bold"><?= $idProvaParaVer ? 'Raio-X do seu Desempenho' : 'Sua Evolução de Desempenho' ?></h1>
            <p class="lead"><?= $idProvaParaVer ? 'Análise da sessão de ' . htmlspecialchars($detalhesProva['disciplinas']) . ' de ' . date('d/m/Y', strtotime($detalhesProva['dataProva'])) : 'Analise seu histórico, identifique padrões e acelere sua jornada rumo à aprovação.' ?></p>
        </div>
    </div>
    
    <div class="container" id="main-content">
        <?php if ($idProvaParaVer && $detalhesProva): ?>
            <div class="mb-4">
                <a href="simulado.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-2"></i>Voltar para a lista</a>
            </div>
            <?php foreach ($detalhesProva['questoes'] as $q): ?>
                <div class="card questao-review-card">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0 fw-bold">Questão #<?= $q['idQuestao'] ?> <span class="badge bg-secondary ms-2"><?= $q['disciplina'] ?></span></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text fs-5"><?= nl2br(htmlspecialchars($q['enunciado'])) ?></p>
                        <hr>
                        <div class="alternativas-container mt-3">
                            <?php foreach ($q['alternativas'] as $alt): ?>
                                <?php
                                    $classes = 'form-check-label';
                                    $icon = '';
                                    if ($alt['correta']) {
                                        $classes .= ' alternativa-correta';
                                        $icon = '<i class="fa-solid fa-check text-success ms-2"></i>';
                                    }
                                    if ($alt['letra'] === $q['resposta_aluno'] && !$alt['correta']) {
                                        $classes .= ' alternativa-errada';
                                        $icon = '<i class="fa-solid fa-xmark text-danger ms-2"></i>';
                                    }
                                ?>
                                <div class="mb-2">
                                    <div class="<?= $classes ?>">
                                        <strong><?= $alt['letra'] ?></strong>
                                        <span><?= htmlspecialchars($alt['texto']) . $icon ?></span>
                                    </div>
                                    <?php if (!empty($alt['feedback'])): ?>
                                        <div class="alternativa-feedback">
                                            <strong>Feedback:</strong> <?= htmlspecialchars($alt['feedback']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if (empty($listaProvas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-x" style="font-size: 5rem; color: #6c757d;"></i>
                    <h3 class="mt-3">Nenhum simulado encontrado</h3>
                    <p class="text-muted">Você ainda não completou nenhum simulado. Que tal começar um agora?</p>
                    <a href="gerar_prova.php" class="btn btn-primary mt-3">Criar Novo Simulado</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($listaProvas as $prova): ?>
                        <div class="col card-container">
                            <div class="card card-simulado h-100">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold text-primary">Sessão de estudos de <?= htmlspecialchars($prova['disciplinas']) ?></h5>
                                    <p class="card-text text-muted mb-4"><i class="bi bi-calendar-event me-2"></i><?= date('d/m/Y', strtotime($prova['dataProva'])) ?></p>
                                    <div class="mt-auto">
                                        <?php
                                            $percentual = $prova['total_questoes'] > 0 ? round(((int)$prova['total_acertos'] / $prova['total_questoes']) * 100) : 0;
                                            $corScore = $percentual >= 70 ? 'bg-success' : ($percentual >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="fw-bold">Seu Desempenho:</span>
                                            <span class="badge score-badge <?= $corScore ?>"><?= $percentual ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?= $percentual ?>%;" aria-valuenow="<?= $percentual ?>"></div>
                                        </div>
                                        <p class="text-center mt-2 text-muted">Acertou <?= (int)$prova['total_acertos'] ?> de <?= $prova['total_questoes'] ?> questões</p>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <a href="?id_prova=<?= $prova['idProva'] ?>" class="btn btn-primary w-100 fw-bold">Revisar Prova</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.querySelector('.card-container')) {
                gsap.from('.card-container', { duration: 0.7, opacity: 0, y: 50, stagger: 0.1, ease: 'power3.out' });
            }
            if (document.querySelector('.questao-review-card')) {
                gsap.from('.questao-review-card', { duration: 0.5, opacity: 0, x: -40, stagger: 0.08, ease: 'power2.out' });
            }
        });
    </script>
</body>
</html>
