<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['provaGerada'])) {
    echo '<p class="text-center text-danger mt-5">Erro: Nenhuma prova foi gerada. Por favor, volte e gere uma nova prova.</p>';
    exit;
}

function limpar_texto_ia($texto) {
    $marcador = 'correta';
    $posicao = stripos($texto, $marcador);
    if ($posicao !== false) {
        return trim(substr($texto, 0, $posicao));
    }
    return $texto;
}

$jsonDaSessao = $_SESSION['provaGerada'];
$questoes = json_decode($jsonDaSessao, true);

if (!is_array($questoes)) {
    echo '<p class="text-center text-danger mt-5">Erro: Os dados da prova estão em formato inválido ou corrompido.</p>';
    exit;
}
include_once 'header.php'
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prova Gerada - MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../css/prova.css">
    <style>
        .card-footer-actions {
            background-color: transparent;
            border-top: 1px solid #eee;
            padding: 0.75rem 1.25rem;
        }
        .action-btn {
            padding: 0.25rem 0.6rem;
            font-size: 0.9rem;
        }
        .highlighted {
            background-color: #ffe066;
            border-radius: 3px;
            padding: 0 2px;
        }
        .highlight-btn {
            cursor: pointer;
            color: #ffc107;
            margin-left: 8px;
            font-size: 1.1em;
            vertical-align: middle;
        }
        .highlight-btn.active {
            color: #ffb300;
        }
        .highlighted {
    border-radius: 3px;
    padding: 0 2px;
    cursor: pointer;
}

.highlight-btn {
    cursor: pointer;
}

.feedback-btn.active {
    background-color: #e2e6ea !important;
    border-color: #ccc !important;
}

    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">Criada Pela MedLeap AI com Base na Sua Prova</h1>
        <?php if (count($questoes) > 0): ?>
            <form id="provaForm" action="processar_respostas.php" method="POST" novalidate>
                <?php foreach ($questoes as $index => $questao): ?>
                    <?php
                        $id_display_questao = htmlspecialchars($questao['id'] ?? ('#'.($index + 1)));
                        $tipo_questao = ucfirst(htmlspecialchars($questao['tipo'] ?? 'objetiva'));
                        $enunciado_limpo = limpar_texto_ia($questao['enunciado'] ?? 'Enunciado não disponível');
                        $enunciado_questao = nl2br(htmlspecialchars($enunciado_limpo));
                        $subarea_para_exibir = isset($questao['subarea']) && is_string($questao['subarea']) ? trim($questao['subarea']) : '';
                    ?>
                    <div class="card mb-4 questao-card">
                         <div class="card-header">
                            <h5 class="card-title mb-0">Questão <?php echo $id_display_questao.' - '; echo $tipo_questao?></h5>
                            <?php if (!empty($subarea_para_exibir)): ?>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($subarea_para_exibir); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                <strong>Enunciado:</strong>
                                <span class="highlightable" id="enunciado_<?php echo $index; ?>"><?php echo $enunciado_questao; ?></span>
                                <i class="fa-solid fa-highlighter highlight-btn" title="Marcar texto" data-target="enunciado_<?php echo $index; ?>"></i>
                            </p>
                            <div class="alternativas-container">
                                <?php if (isset($questao['alternativas']) && is_array($questao['alternativas']) && !empty($questao['alternativas'])): ?>
                                    <?php foreach ($questao['alternativas'] as $letra => $dados_alternativa): ?>
                                        <?php
                                            $texto_bruto_alternativa = 'Texto da alternativa não encontrado.';
                                            if (is_array($dados_alternativa) && isset($dados_alternativa['texto'])) {
                                                $texto_bruto_alternativa = $dados_alternativa['texto'];
                                            }
                                            $texto_da_alternativa = limpar_texto_ia($texto_bruto_alternativa);
                                        ?>
                                        <div class="form-check">
                                            <input 
                                                class="form-check-input" 
                                                type="radio" 
                                                name="questao_<?php echo $index; ?>" 
                                                id="questao_<?php echo $index . '_' . $letra; ?>"
                                                value="<?php echo htmlspecialchars($letra); ?>">
                                            <label class="form-check-label highlightable" id="alt_<?php echo $index . '_' . $letra; ?>" for="questao_<?php echo $index . '_' . $letra; ?>">
                                                <strong><?php echo htmlspecialchars($letra); ?></strong>
                                                <span><?php echo htmlspecialchars($texto_da_alternativa); ?></span>
                                            </label>
                                            <i class="fa-solid fa-highlighter highlight-btn" title="Marcar texto" data-target="alt_<?php echo $index . '_' . $letra; ?>"></i>
                                            <span class="descartar-btn" title="Descartar esta alternativa">
                                                <i class="fa-solid fa-scissors"></i>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-danger">Alternativas não disponíveis para esta questão.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer card-footer-actions d-flex justify-content-end align-items-center gap-2">
                           <button type="button" class="btn btn-sm btn-outline-success action-btn feedback-btn" title="Gostei da questão" data-questao-id="<?php echo $id_display_questao; ?>" data-feedback="like">
                               <i class="fa-solid fa-thumbs-up"></i>
                           </button>
                           <button type="button" class="btn btn-sm btn-outline-danger action-btn feedback-btn" title="Não gostei da questão" data-questao-id="<?php echo $id_display_questao; ?>" data-feedback="dislike">
                               <i class="fa-solid fa-thumbs-down"></i>
                           </button>
                           <button type="button" class="btn btn-sm btn-outline-warning action-btn report-btn" title="Reportar um problema" data-bs-toggle="modal" data-bs-target="#reportModal" data-questao-id="<?php echo $id_display_questao; ?>">
                               <i class="fa-solid fa-flag"></i>
                           </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg mt-4">Enviar Respostas</button>
                </div>
            </form>
        <?php else: ?>
            <p class="text-center text-muted mt-5">Nenhuma questão foi encontrada na prova gerada.</p>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="validacaoModal" tabindex="-1" aria-labelledby="validacaoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="validacaoModalLabel">Atenção!</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Por favor, responda a todas as perguntas antes de enviar.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="reportModalLabel">Reportar Problema na Questão <span id="reportarQuestaoId"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="reportForm">
                <input type="hidden" id="reportQuestaoIdInput" name="questaoId">
                <div class="mb-3">
                    <label for="reportComment" class="form-label">Por favor, descreva o problema que você encontrou (ex: erro no enunciado, alternativa incorreta, etc.):</label>
                    <textarea class="form-control" id="reportComment" name="comment" rows="4" required></textarea>
                </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="submitReportBtn">Enviar report</button>
          </div>
        </div>
      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const highlightColors = ['#ffe066', '#c5f6fa', '#d3f9d8']; // amarelo, azul claro, verde claro
    let highlightActive = false;
    let currentTarget = null;
    let currentButton = null;

    document.querySelectorAll('.highlight-btn').forEach(btn => {
        btn.style.cursor = 'pointer';
        btn.addEventListener('click', function (e) {
            if (highlightActive && currentTarget === this.dataset.target) {
                highlightActive = false;
                currentTarget = null;
                currentButton = null;
                btn.classList.remove('active');
                document.body.style.cursor = '';
                return;
            }
            document.querySelectorAll('.highlight-btn').forEach(b => b.classList.remove('active'));
            highlightActive = true;
            currentTarget = this.dataset.target;
            currentButton = btn;
            btn.classList.add('active');
            document.body.style.cursor = 'crosshair';
        });
    });

    document.querySelectorAll('.highlightable').forEach(el => {
        el.addEventListener('mouseup', function (e) {
            if (!highlightActive || currentTarget !== this.id) return;
            const selection = window.getSelection();
            if (!selection.rangeCount || !selection.toString().trim()) return;
            const range = selection.getRangeAt(0);
            if (!el.contains(range.commonAncestorContainer)) return;

            const span = document.createElement('span');
            span.className = 'highlighted';
            const cor = highlightColors[Math.floor(Math.random() * highlightColors.length)];
            span.style.backgroundColor = cor;
            span.style.borderRadius = '3px';
            span.style.padding = '0 2px';
            try {
                range.surroundContents(span);
            } catch (err) {
                alert('Não é possível grifar este trecho. Tente selecionar uma palavra simples.');
                return;
            }
            selection.removeAllRanges();
            highlightActive = false;
            currentTarget = null;
            if (currentButton) currentButton.classList.remove('active');
            document.body.style.cursor = '';
        });
    });

    document.querySelectorAll('.feedback-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            const parent = this.parentElement;
            const isActive = this.classList.contains('active');
            parent.querySelectorAll('.feedback-btn').forEach(btn => btn.classList.remove('active'));
            if (!isActive) {
                this.classList.add('active');
                animarFeedback(this.dataset.feedback, this);
            }
        });
    });
    document.querySelectorAll('.descartar-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        const formCheck = e.currentTarget.closest('.form-check');
        if (!formCheck) return;

        const radio = formCheck.querySelector('.form-check-input');
        const label = formCheck.querySelector('.form-check-label');
        const icon = e.currentTarget.querySelector('i');
        const isDescartada = formCheck.classList.contains('descartada-animada');

        if (isDescartada) {
            gsap.to(formCheck, {
                opacity: 1,
                x: 0,
                duration: 0.4,
                ease: 'power2.out',
                onComplete: () => {
                    if (radio) radio.disabled = false;
                    if (label) label.classList.remove('alternativa-descartada');
                    if (icon) {
                        icon.classList.remove('fa-rotate-left');
                        icon.classList.add('fa-scissors');
                    }
                    e.currentTarget.setAttribute('title', 'Descartar esta alternativa');
                }
            });
            formCheck.classList.remove('descartada-animada');
        } else {
            gsap.to(formCheck, {
                opacity: 0.4,
                x: -15,
                duration: 0.4,
                ease: 'power2.out',
                onComplete: () => {
                    if (radio) radio.disabled = true;
                    if (label) label.classList.add('alternativa-descartada');
                    if (icon) {
                        icon.classList.remove('fa-scissors');
                        icon.classList.add('fa-rotate-left');
                    }
                    e.currentTarget.setAttribute('title', 'Restaurar esta alternativa');
                }
            });
            formCheck.classList.add('descartada-animada');
        }
    });
});


    function animarFeedback(tipo, button) {
        const container = document.createElement('div');
        container.className = 'feedback-animation-container';
        container.style.position = 'fixed';
        container.style.width = '100%';
        container.style.height = '100%';
        container.style.pointerEvents = 'none';
        container.style.zIndex = '9999';
        container.style.top = '0';
        container.style.left = '0';
        document.body.appendChild(container);

        const rect = button.getBoundingClientRect();
        const startX = rect.left;
        const startY = rect.top + window.scrollY;

        for (let i = 0; i < 25; i++) {
            const icon = document.createElement('i');
            icon.className = `fa-solid ${tipo === 'like' ? 'fa-thumbs-up' : 'fa-thumbs-down'}`;
            icon.style.position = 'absolute';
            icon.style.left = `${startX}px`;
            icon.style.top = `${startY}px`;
            icon.style.color = tipo === 'like' ? '#198754' : '#dc3545';
            icon.style.fontSize = '2rem';
            container.appendChild(icon);

            gsap.fromTo(icon, {
                opacity: 1,
                scale: 1,
                x: 0,
                y: 0,
                rotation: 0
            }, {
                opacity: 0,
                scale: 0.5,
                x: (Math.random() - 0.5) * 100,
                y: tipo === 'like' ? -150 : 150,
                rotation: (Math.random() - 0.5) * 90,
                duration: 1,
                ease: "back.out(1.7)",
                onComplete: () => icon.remove()
            });
        }

        gsap.to(button, {
            scale: 1.2,
            duration: 0.2,
            yoyo: true,
            repeat: 1
        });

        setTimeout(() => container.remove(), 1500);
    }
});
</script>

</body>
<?php include_once 'footer.php' ?>
</html>