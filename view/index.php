<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
if (isset($_SESSION['provaGerada'])) {
    $_SESSION['provaGerada'] = null;
}
include_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta - MedLeap AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/comum.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .hero-section h1 {
            font-weight: 700;
            color: #0d6efd;
        }
        .hero-section .lead {
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            color: #6c757d;
        }
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 0.75rem;
            font-size: 2rem;
            color: #fff;
            background-color: #0d6efd;
        }
        .main-upload-card {
            border: 2px dashed #dee2e6;
            transition: all 0.3s ease-in-out;
        }
        .main-upload-card:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .btn-success {
            background-color: #198754;
        }
        .loading-modal-content {
            background-color: rgba(10, 25, 47, 0.95);
            backdrop-filter: blur(5px);
            color: #f8f9fa;
            border-radius: 1rem;
            text-align: center;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .loading-modal-content .spinner-border {
            width: 4rem;
            height: 4rem;
            color: #0d6efd;
            border-width: .3em;
        }
        .loading-modal-content #loading-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-top: 1rem;
            min-height: 2.5rem;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="hero-section text-center mb-5">
            <h1 class="display-4">MedLeap AI</h1>
            <p class="lead mt-3">A forma mais inteligente de se preparar. Transforme qualquer PDF de prova em um simulado estratégico e personalizado com o poder da Inteligência Artificial.</p>
        </div>

        <div class="row text-center g-4 my-5">
            <div class="col-lg-4"><div class="feature-icon mb-3 shadow"><i class="fas fa-file-pdf"></i></div><h4 class="fw-semibold mb-2">Análise de Padrões</h4><p class="text-muted">Nossa IA lê seu material, entende como seu professor elabora as questões e identifica os tópicos mais relevantes.</p></div>
            <div class="col-lg-4"><div class="feature-icon mb-3 shadow"><i class="fas fa-brain"></i></div><h4 class="fw-semibold mb-2">Simulados Sob Medida</h4><p class="text-muted">Receba questões inéditas e personalizadas, criadas para você treinar com foco total em suas necessidades.</p></div>
            <div class="col-lg-4"><div class="feature-icon mb-3 shadow"><i class="fas fa-book-medical"></i></div><h4 class="fw-semibold mb-2">Baseado em Evidências</h4><p class="text-muted">Escolha a literatura que a IA deve usar como base — de tratados como Harrison a diretrizes oficiais.</p></div>
        </div>

        <div id="resultado" class="row g-4 mb-4"></div>

        <div class="card shadow-sm border-0 mb-4 d-none" id="gerarCard">
            <div class="card-body p-4 text-center">
                <form action="parametros" method="POST"><button type="submit" class="btn btn-success btn-lg w-100 py-3"><i class="fas fa-cogs me-2"></i>Configurar e Gerar Simulado Personalizado</button></form>
            </div>
        </div>
        
        <div class="card shadow-lg border-0 main-upload-card" id="uploadCard">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center fw-bold mb-4">Vamos Começar!</h3>
                <form id="uploadForm" enctype="multipart/form-data" class="needs-validation d-flex flex-column gap-3" novalidate>
                    <div>
                        <label for="arquivo" class="form-label fs-5">1. Envie sua Prova ou Lista de Questões (PDF)</label>
                        <input type="file" name="arquivo" id="arquivo" accept="application/pdf" class="form-control form-control-lg" required>
                    </div>
                    <button type="submit" id = "btnGerarProva" class="btn btn-primary btn-lg w-100 py-3 mt-3"><i class="fas fa-arrow-up-from-bracket me-2"></i>Gerar Questões com IA no Estilo da Minha Prova</button>
                </form>
            </div>
        </div>

        <div class="modal fade" id="fileSizeModal" tabindex="-1" aria-labelledby="fileSizeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow"><div class="modal-header bg-warning-subtle"><h5 class="modal-title" id="fileSizeModalLabel">Arquivo Muito Grande</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body">O arquivo enviado excede o tamanho máximo permitido de 5MB. Por favor, escolha um arquivo menor.</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button></div></div></div>
        </div>

        <div class="modal fade" id="fileErrorModal" tabindex="-1" aria-labelledby="fileErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow"><div class="modal-header bg-danger-subtle"><h5 class="modal-title" id="fileErrorModalLabel">Erro no Arquivo</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body">O arquivo enviado não foi reconhecido como texto pela nossa IA. Por favor, envie um arquivo válido.</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button></div></div></div>
        </div>
    </div>

    <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content loading-modal-content">
                <div class="modal-body">
                    <div class="spinner-border mb-3" role="status">
                        <span class="visually-hidden">Analisando...</span>
                    </div>
                    <h2 class="mb-3" id="loading-title">Analisando seu documento...</h2>
                    <p id="loading-text">Nossa IA está em ação, um momento.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sugestoes.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadForm = document.getElementById('uploadForm');
        
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const fileInput = document.getElementById('arquivo');
                const file = fileInput.files[0];

                if (!file) {
                    alert('Por favor, selecione um arquivo para enviar.');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    new bootstrap.Modal(document.getElementById('fileSizeModal')).show();
                    return;
                }

                const loadingModalElement = document.getElementById('loadingModal');
                const loadingModal = new bootstrap.Modal(loadingModalElement);
                const loadingTextElement = document.getElementById('loading-text');
                const loadingTitleElement = document.getElementById('loading-title');
                
                const loadingMessages = [
                    "Identificando estilo da prova",
                    "Analisando o padrão de cobrança do seu professor...",
                    "Cruzando informações com nossa base de dados",
                    "Extraindo conteúdos relevantes",
                    "Quase pronto!"
                ];

                let messageIndex = 0;
                loadingTextElement.style.opacity = 1;
                loadingTextElement.textContent = loadingMessages[0];
                loadingModal.show();

                let messageInterval = setInterval(() => {
                    messageIndex = (messageIndex + 1) % loadingMessages.length;
                    loadingTextElement.style.opacity = 0;
                    setTimeout(() => {
                        loadingTextElement.textContent = loadingMessages[messageIndex];
                        loadingTextElement.style.opacity = 1;
                    }, 500);
                }, 3000);

                const formData = new FormData(uploadForm);
                const originalSubmitButton = uploadForm.querySelector('button[type="submit"]');
                const originalButtonText = originalSubmitButton.innerHTML;
                originalSubmitButton.disabled = true;
                originalSubmitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Analisando...`;

                fetch('../backend/processar_pdf.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) { throw new Error('Network response was not ok'); }
                    return response.json();
                })
                .then(data => {
                    if (data.json) {
                        const parsedData = JSON.parse(data.json);
                        document.getElementById('resultado').innerHTML = `
                            <div class="col-12"><div class="card mb-3"><div class="card-body"><h5 class="card-title">Resumo da Prova</h5><p class="card-text">${parsedData.resumo_prova}</p></div></div></div>
                            <div class="col-md-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title">Disciplina Principal</h5><p class="card-text"><span class="badge bg-secondary">${parsedData.subarea}</span></p></div></div></div>
                            <div class="col-md-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title">Conteúdos Abordados</h5><p class="card-text">${parsedData.disciplinas.map(d => `<span class="badge bg-info me-1">${d}</span>`).join('')}</p></div></div></div>
                        `;
                        document.getElementById('gerarCard').classList.remove('d-none');
                        document.getElementById('uploadCard').classList.add('d-none');
                    } else {
                        new bootstrap.Modal(document.getElementById('fileErrorModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    new bootstrap.Modal(document.getElementById('fileErrorModal')).show();
                })
                .finally(() => {
                    clearInterval(messageInterval);
                    loadingModal.hide();
                    originalSubmitButton.disabled = false;
                    originalSubmitButton.innerHTML = originalButtonText;
                });
            });
        }
    });
    </script>
<?php include_once 'footer.php'; ?>
</body>
</html>