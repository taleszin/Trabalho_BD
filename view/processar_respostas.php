<?php
session_start();
require_once '../classes/config.php';
require_once '../classes/QuestaoService.php';
require_once '../classes/RespostaService.php';

$mensagem_salvamento = null;
$idProvaSalvaNoDB_nesta_execucao = null;
$erro_fatal = null;
$listaQuestoesIAGeradas = [];

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../view/login.php");
    exit;
}
$idAluno = (int)$_SESSION['id_usuario'];

if (!isset($_SESSION['provaGerada'])) {
    $erro_fatal = 'Nenhuma prova foi gerada ou a sessão expirou. Por favor, gere uma nova prova.';
} else {
    $dadosProvaIASerializados = $_SESSION['provaGerada'];
    $listaQuestoesIAGeradas = json_decode($dadosProvaIASerializados, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($listaQuestoesIAGeradas)) {
        $erro_fatal = 'Os dados da prova estão em um formato inválido ou corrompido.';
        $listaQuestoesIAGeradas = [];
    }
}

$respostasUsuarioForm = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erro_fatal && !empty($listaQuestoesIAGeradas)) {
    if (isset($conexao)) {
        $questaoService = new QuestaoService($conexao);
        $respostaService = new RespostaService($conexao);
        $conexao->begin_transaction();
        try {
            $dataProvaAtual = date('Y-m-d H:i:s');
            $idProvaSalvaNoDB_nesta_execucao = $questaoService->salvarProva($idAluno, $dataProvaAtual);

            foreach ($listaQuestoesIAGeradas as $index => $dadosQuestaoIA) {
                $enunciadoIA = $dadosQuestaoIA['enunciado'] ?? null;
                $alternativasComFeedbackIA = $dadosQuestaoIA['alternativas'] ?? null;
                $letraCorretaIA = $dadosQuestaoIA['gabarito'] ?? null;
                $subareaIA = $dadosQuestaoIA['subarea'] ?? "Não especificada";
                $tipoIA = $dadosQuestaoIA['tipo'] ?? 'objetiva';
                $comentarioGeralIA = $dadosQuestaoIA['comentario'] ?? null;

                $assuntosArray = $dadosQuestaoIA['assuntos_abordados'] ?? $dadosQuestaoIA['assuntos'] ?? [];
                $assuntosFormatados = null;
                if (is_array($assuntosArray) && !empty($assuntosArray)) {
                    $assuntosFormatados = implode(', ', array_map('trim', $assuntosArray));
                }

                if (empty($enunciadoIA) || empty($alternativasComFeedbackIA) || empty($letraCorretaIA)) {
                    throw new Exception("Dados essenciais incompletos para a questão (índice $index). Salvamento cancelado.");
                }

                $idQuestaoSalvaNoBanco = $questaoService->salvarQuestao($idProvaSalvaNoDB_nesta_execucao, $enunciadoIA, $tipoIA, $assuntosFormatados, $comentarioGeralIA, $subareaIA);
                $questaoService->salvarAlternativas($idQuestaoSalvaNoBanco, $alternativasComFeedbackIA, $letraCorretaIA);

                $chaveRespostaForm = "questao_" . $index;
                $letraRespondidaPeloUsuario = $respostasUsuarioForm[$chaveRespostaForm] ?? null;

                if ($letraRespondidaPeloUsuario !== null) {
                    $respostaEstaCorreta = (strtoupper($letraRespondidaPeloUsuario) === strtoupper($letraCorretaIA));
                    $respostaService->salvarResposta($idAluno, $idQuestaoSalvaNoBanco, $letraRespondidaPeloUsuario, $respostaEstaCorreta, $dataProvaAtual);
                }
            }
            $conexao->commit();
            $mensagem_salvamento = ['tipo' => 'success', 'texto' => "Prova salva. Agora o seu estudo é com base no que realmente cai!"];
            $_SESSION['ultima_prova_processada_id'] = $idProvaSalvaNoDB_nesta_execucao;
        } catch (Exception $e) {
            header("Location: ../index.php");
            error_log("Erro ao salvar prova: " . $e->getMessage());
            $mensagem_salvamento = ['tipo' => 'danger', 'texto' => "Ocorreu um erro ao salvar seus resultados."];
            exit;
        }
    }
}
$resultadosParaExibir = [];
$tiposValidosParaExibicao = ['objetiva', 'caso clínico'];
if (!$erro_fatal) {
    foreach ($listaQuestoesIAGeradas as $index => $questaoOriginal) {
        $erro_exibicao = null;
        $tipoQuestaoOriginal = $questaoOriginal['tipo'] ?? 'objetiva';
        if ($tipoQuestaoOriginal == 'Caso_clinico' || $tipoQuestaoOriginal == 'Caso_Clinico' || $tipoQuestaoOriginal == 'caso_clinico') {
            $tipoQuestaoOriginal = 'caso clínico'; 
        }
        $tipoQuestaoValido = is_string($tipoQuestaoOriginal) && in_array(strtolower(trim($tipoQuestaoOriginal)), $tiposValidosParaExibicao);

        if (empty($questaoOriginal['enunciado']) || empty($questaoOriginal['gabarito'])) {
            $erro_exibicao = "Dados da questão incompletos ou malformados.";
        }
        
        $resultadosParaExibir[] = [
            'id' => $questaoOriginal['id'] ?? ('#' . ($index + 1)),
            'enunciado' => $questaoOriginal['enunciado'] ?? 'Enunciado Indisponível',
            'subarea' => $questaoOriginal['subarea'] ?? 'Não especificada',
            'alternativas' => $questaoOriginal['alternativas'] ?? [],
            'resposta_usuario' => $respostasUsuarioForm["questao_" . $index] ?? null,
            'gabarito' => $questaoOriginal['gabarito'] ?? null,
            'comentario' => $questaoOriginal['comentario'] ?? null,
            'correta' => (!$erro_exibicao && ($respostasUsuarioForm["questao_" . $index] ?? null) !== null && strtoupper(($respostasUsuarioForm["questao_" . $index] ?? null)) === strtoupper($questaoOriginal['gabarito'])),
            'erro_exibicao' => $erro_exibicao,
            'assuntos' => $questaoOriginal['assuntos_abordados'] ?? $questaoOriginal['assuntos'] ?? []
        ];
    }
}
$_SESSION['resultadosProva'] = $resultadosParaExibir;
include_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado da Prova - MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/respostas.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"/>
    <style>
        .action-buttons .btn {
            font-size: 1.15rem;
            padding: 1.1rem 1.5rem;
            border-radius: 2.5rem;
            font-weight: 600;
            box-shadow: 0 4px 18px -8px #0d6efd1a;
            transition: transform 0.18s, box-shadow 0.18s;
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .action-buttons .btn:hover, .action-buttons .btn:focus {
            transform: translateY(-4px) scale(1.04);
            box-shadow: 0 8px 24px -6px #0d6efd33;
            z-index: 2;
        }
        .action-buttons .btn i {
            font-size: 1.4em;
        }
        .action-buttons {
            background: #f8fafc;
            border-radius: 2rem;
            box-shadow: 0 2px 16px -8px #0d6efd1a;
            padding: 2.2rem 1.2rem 1.2rem 1.2rem;
            margin-top: 3.5rem;
        }
        @media (max-width: 600px) {
            .action-buttons .btn {
                font-size: 1rem;
                padding: 0.9rem 1rem;
            }
            .action-buttons {
                padding: 1.2rem 0.5rem 0.7rem 0.5rem;
            }
        }
        .swal2-popup .reforco-card-popup {
            box-shadow: none;
            padding: 0;
            background: transparent;
        }
        .reforco-card-popup {
            background: linear-gradient(120deg, #f8fafc 80%, #e3f0ff 100%);
            border-radius: 1.5rem;
            box-shadow: 0 4px 32px -8px #0d6efd22;
            padding: 2.5rem 1.5rem 2rem 1.5rem;
            margin: 0 auto;
            max-width: 480px;
        }
        .reforco-card-popup h3 {
            font-weight: 700;
            color: #0d6efd;
        }
        .reforco-card-popup ul {
            margin: 1.2rem 0 1.8rem 0;
            padding-left: 1.2rem;
        }
        .reforco-card-popup li {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .reforco-card-popup .btn-group .btn {
            min-width: 140px;
            font-size: 1.05rem;
            border-radius: 2rem;
            padding: 0.7rem 1.2rem;
        }
        .reforco-card-popup .btn-group {
            gap: 0.7rem;
        }
        .reforco-card-popup .btn-minimal {
            background: #f8fafc;
            border: 1.5px solid #0d6efd;
            color: #0d6efd;
            font-weight: 500;
            transition: background 0.18s, color 0.18s;
        }
        .reforco-card-popup .btn-minimal:hover {
            background: #0d6efd;
            color: #fff;
        }
        .reforco-card-popup .btn-acao {
            background: #0d6efd;
            color: #fff;
            font-weight: 600;
            border: none;
        }
        .reforco-card-popup .btn-acao:hover {
            background: #084298;
            color: #fff;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
</head>
<body>
    <div class="container my-5">
        <header class="text-center mb-5">
            <h1 class="display-5 fw-bold">Diagnóstico Estratégico do Seu Simulado</h1>
            <p class="lead text-muted">Você acabou de viver um simulado no estilo da sua prova real. A MedLeap AI leu cada resposta, entendeu onde você erra — e agora te mostra exatamente por onde começar a evoluir 🚀🚀</p>
        </header>

        <?php if (isset($erro_fatal)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($erro_fatal); ?> <a href="parametros.php" class="alert-link">Clique aqui para voltar</a>.</div>
        <?php else: ?>
            
            <?php if (isset($mensagem_salvamento)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($mensagem_salvamento['tipo']); ?> text-center" role="alert">
                    <?php echo htmlspecialchars($mensagem_salvamento['texto']); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-5 summary-card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Seu Resultado em Detalhes</h2>
                    <div class="row align-items-center g-4">
                        <div class="col-lg-4 text-center">
                            <div class="chart-container">
                                <canvas id="graficoDesempenho"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div id="desempenho-textual" class="d-flex flex-column align-items-center text-center">
                            </div>
                        </div>
                        <div class="col-lg-4 text-center">
                            <div class="chart-container">
                                <canvas id="graficoDisciplinas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="text-center mb-4 section-title">Correção Clínica com Base na Literatura que Você Escolheu</h2>

            <?php foreach ($resultadosParaExibir as $res): ?>
                <div class="card shadow-sm mb-4 question-card">
                    <div class="card-body">
                        <?php if ($res['erro_exibicao']): ?>
                            <div class="alert alert-warning">
                                <h5 class="alert-heading">Questão <?php echo htmlspecialchars($res['id']); ?> - Problema nos Dados</h5>
                                <p><?php echo htmlspecialchars($res['erro_exibicao']); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="question-header">
                                <h5 class="card-title">Questão <?php echo htmlspecialchars($res['id']); ?></h5>
                                <div class="tags">
                                    <span class="badge tag-subarea"><?php echo htmlspecialchars($res['subarea']); ?></span>
                                    <span class="badge tag-result <?php echo $res['correta'] ? 'tag-correct' : 'tag-incorrect'; ?>">
                                        <?php echo $res['resposta_usuario'] === null ? 'Não Respondida' : ($res['correta'] ? 'Você Acertou' : 'Você Errou'); ?>
                                    </span>
                                </div>
                            </div>
                            <p class="card-text enunciado mt-3"><?php echo nl2br(htmlspecialchars($res['enunciado']));?></p>
                            <?php if (!empty($res['comentario'])): ?>
                                <p class="card-text comentario mt-2 text-muted"><strong>Comentário:</strong> <?php                      echo nl2br(htmlspecialchars($res['comentario'])); ?></p>
                            <?php endif; ?>
                            <hr>
                            <div class="alternatives-section mt-4">
                                <?php foreach ($res['alternativas'] as $letra => $dados_alt):
                                    $texto_alternativa = htmlspecialchars($dados_alt['texto'] ?? 'Texto não disponível');
                                    $feedback_alternativa = nl2br(htmlspecialchars($dados_alt['feedback'] ?? 'Feedback não disponível'));
                                    
                                    $classe_css_alternativa = '';
                                    $indicador_escolha_texto = '';

                                    if ($letra === $res['gabarito']) {
                                        $classe_css_alternativa .= ' correct-answer-highlight';
                                        $indicador_escolha_texto = '<span class="correct-choice-marker"> (Gabarito)</span>';
                                    }
                                    if ($letra === $res['resposta_usuario']) {
                                        $indicador_escolha_texto .= '<span class="user-choice-marker"> (Sua Resposta)</span>';
                                        if (!$res['correta']) {
                                            $classe_css_alternativa .= ' incorrect-user-answer-highlight';
                                        }
                                    }
                                ?>
                                    <div class="alternative-feedback <?php echo trim($classe_css_alternativa); ?>">
                                        <p class="mb-0">
                                            <strong class="alternative-letter"><?php echo htmlspecialchars($letra); ?>)</strong> <?php echo $texto_alternativa; ?>
                                            <?php echo $indicador_escolha_texto; ?>
                                        </p>
                                        <div class="feedback-text">
                                            <?php echo $feedback_alternativa; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="action-buttons d-grid gap-3 col-md-8 mx-auto animate__animated animate__fadeInUp">
                <a href="parametros.php" class="btn btn-primary btn-lg animate__animated animate__pulse animate__delay-1s" id="btn-novo-simulado">
                    <i class="fa-solid fa-plus"></i> Criar Novo Simulado no Mesmo Estilo
                </a>
                <a href="../backend/gerar_pdf.php" target="_blank" class="btn btn-success btn-lg animate__animated animate__pulse animate__delay-2s" id="btn-baixar-pdf">
                    <i class="fa-solid fa-file-arrow-down"></i> Baixar Simulado para Estudar ou Enviar a Alguém
                </a>
                <a href="index.php" class="btn btn-secondary btn-lg animate__animated animate__pulse animate__delay-3s" id="btn-enviar-ia">
                    <i class="fa-solid fa-upload"></i> Enviar Outra Prova para a IA Analisar
                </a>
                <a href="dashboard.php" class="btn btn-info btn-lg animate__animated animate__pulse animate__delay-4s" id="btn-dashboard">
                    <i class="fa-solid fa-chart-line"></i> Visualizar Desempenho Geral
                </a>
            </div>

        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const resultados = <?php echo json_encode($resultadosParaExibir); ?>;
            let assuntosDificeis = [];
            if (resultados && resultados.length > 0) {
                resultados.forEach(r => {
                    if (!r.correta && r.assuntos && Array.isArray(r.assuntos)) {
                        r.assuntos.forEach(a => {
                            a = (a || '').trim();
                            if (a && !assuntosDificeis.includes(a)) assuntosDificeis.push(a);
                        });
                    }
                });
            }

            let acertos = 0;
            let erros = 0;
            let naoRespondidas = 0;
            const contagemDisciplinas = {};

            resultados.forEach(r => {
                if (r.erro_exibicao) return;
                if (r.resposta_usuario === null) {
                    naoRespondidas++;
                } else if (r.correta) {
                    acertos++;
                } else {
                    erros++;
                }
                const subarea = r.subarea || 'Não especificada';
                contagemDisciplinas[subarea] = (contagemDisciplinas[subarea] || 0) + 1;
            });

            const totalQuestoesValidas = acertos + erros + naoRespondidas;
            const percentualAcerto = totalQuestoesValidas > 0 ? ((acertos / totalQuestoesValidas) * 100).toFixed(1) : 0;
            
            $('#desempenho-textual').html(`
                <div class="stat-item"><h3 class="fw-bold text-success">${acertos}</h3><p class="text-muted mb-0">Acertos</p></div>
                <div class="stat-item"><h3 class="fw-bold text-danger">${erros}</h3><p class="text-muted mb-0">Erros</p></div>
                <div class="stat-item"><h3 class="fw-bold text-warning">${naoRespondidas}</h3><p class="text-muted mb-0">Não Resp.</p></div>
                <div class="stat-item"><h3 class="fw-bold text-primary">${percentualAcerto}%</h3><p class="text-muted mb-0">Aproveitamento</p></div>
            `);

            const ctxDesempenho = document.getElementById('graficoDesempenho').getContext('2d');
            new Chart(ctxDesempenho, {
                type: 'doughnut',
                data: {
                    labels: ['Acertos', 'Erros', 'Não Respondidas'],
                    datasets: [{
                        data: [acertos, erros, naoRespondidas],
                        backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: { 
                        legend: { display: false },
                        title: { display: true, text: 'Resultado Geral', font: {size: 16}, padding: { bottom: 20 } }
                    }
                }
            });

            const labelsDisciplinas = Object.keys(contagemDisciplinas);
            const dataDisciplinas = Object.values(contagemDisciplinas);
            const coresDisciplinas = labelsDisciplinas.map((_, i) => `hsl(${(i * 40 + 200) % 360}, 65%, 60%)`);

            const ctxDisciplinas = document.getElementById('graficoDisciplinas').getContext('2d');
            new Chart(ctxDisciplinas, {
                type: 'pie',
                data: {
                    labels: labelsDisciplinas,
                    datasets: [{
                        data: dataDisciplinas,
                        backgroundColor: coresDisciplinas,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
                         title: { display: true, text: 'Distribuição por Disciplina', font: {size: 16}, padding: { bottom: 20 } }
                    }
                }
            });

            if (assuntosDificeis.length > 0) {
                setTimeout(function() {
                    const htmlAssuntos = `<span class="fw-bold">${assuntosDificeis.join(', ').replace(/, ([^,]*)$/, ' e $1')}</span>`;
                    Swal.fire({
                        title: 'Análise minuciosa da sua sessão',
                        html: `
                            <div class="reforco-card-popup animate__animated animate__fadeInDown">
                                <p class="text-start fs-6 lh-base mb-2">
                                    <span class="text-primary fw-bold">Atenção:</span> Você demonstrou dificuldade nos tópicos:<br>
                                    ${htmlAssuntos}
                                </p>
                                <p class="fs-6 mb-3">Que tal um treino rápido e focado para ficar fera nesses assuntos?</p>
                                <div class="btn-group mt-2 w-100 d-flex justify-content-center" role="group">
                                    <a href="parametros.php?reforco=1&assuntos=${encodeURIComponent(assuntosDificeis.join(','))}" class="btn btn-acao btn-lg flex-fill">
                                        <i class="fa-solid fa-bolt"></i> Reforçar Agora
                                    </a>
                                    <a href="flashcards.php?assuntos=${encodeURIComponent(assuntosDificeis.join(','))}" class="btn btn-minimal btn-lg flex-fill">
                                        <i class="fa-solid fa-clone"></i> Flashcards
                                    </a>
                                </div>
                                <p class="mt-4 text-muted small">Aproveite o momento: quanto antes reforçar, mais fácil consolidar!</p>
                            </div>
                        `,
                        icon: false,
                        showConfirmButton: false,
                        showCloseButton: true,
                        width: 520,
                        padding: '1.5em',
                        background: 'rgba(255,255,255,0.98)',
                        customClass: {
                            title: 'h4 mb-3',
                            popup: 'rounded-3 shadow-lg'
                        }
                    });
                }, 3500);
            }
        });
    </script>
</body>
</html>