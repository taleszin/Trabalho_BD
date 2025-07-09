<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php"); // Redireciona para a página de login
    exit;
}
if (isset($_SESSION['resultadosProva'])) {
    $_SESSION['resultadosProva'] = null; // Limpa as respostas anteriores
}
// Verifica se o conteúdo extraído já está na sessão
if (!isset($_SESSION['conteudoExtraido'])) {
    // Caso não esteja, redireciona para a página inicial ou exibe um erro
    echo '<p>Erro: O conteúdo extraído não foi encontrado. Por favor, envie o PDF novamente.</p>';
    echo '<a href="../index.php" class="btn btn-primary">Voltar para o início</a>';
    exit;
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pega a literatura selecionada
    $literaturaSelecionada = $_POST['literatura_dropdown'] ?? '';
    // Se "outro" foi selecionado, pega o valor do campo de texto livre
    $literaturaFinal = ($literaturaSelecionada === 'outro') ? ($_POST['literatura_outro'] ?? '') : $literaturaSelecionada;

    // Salva os parâmetros enviados na sessão
    $_SESSION['parametrosSimulado'] = [
        'numero_questoes' => $_POST['numero_questoes'] ?? 1,
        'estilo' => $_POST['estilo'] ?? 'objetiva',
        'literatura' => $literaturaFinal // Salva o valor final da literatura
    ];
}

// Define as literaturas médicas padrão para o dropdown
$literaturasPadrao = [
    "Abbas Imunologia",
    "Adams Neuro",
    "Berne Fisiologia",
    "Boron & Boulpaep",
    "Boron Fisiologia",
    "Braunwald Cardiologia",
    "Cecil Clínica Médica",
    "DeGowin Propedêutica Clínica",
    "Diretrizes da SBC",
    "Diretrizes de Sociedades Médicas",
    "Diretrizes do Ministério da Saúde",
    "Diretrizes SBEM",
    "Diretrizes SBP",
    "Goodman Farmacologia",
    "Guyton Fisiologia",
    "Harper Bioquímica",
    "Harrison Clínica Médica",
    "Isabella Semiologia",
    "Junqueira Histologia",
    "Kaplan Psiquiatria",
    "Lehninger Bioquímica",
    "Machado Neuroanatomia",
    "Mandell Infectologia",
    "Moore Anatomia",
    "Moore Embriologia",
    "MS Brasil",
    "Murray & Nadel",
    "Murray Microbiologia",
    "Murray Pneumo",
    "Nelson Pediatria",
    "Neto e Martins Clínica Médica",
    "Netter Anatomia",
    "Netter Clínico",
    "Neves Parasitologia",
    "Novak Ginecologia",
    "Porto & Porto",
    "Porto Semiologia",
    "Rakel Medicina de Família",
    "Rang & Dale",
    "Rang Farmacologia",
    "Robbins & Cotran",
    "Robbins Patologia",
    "Rockwood Ortopedia",
    "Sabiston Cirurgia",
    "SBC Cardio",
    "Schwartz Cirurgia",
    "Tachdjian Ortopedia Pediátrica",
    "Thompson Genética",
    "Williams Endocrinologia",
    "Williams Obstetrícia"
];
include_once 'header.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parâmetros do Simulado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/parametros.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Monte Seu Simulado de Alto Impacto — Sob Medida para Sua Prova</h1>
        <form method="POST" action="../backend/gerarprova.php">
            <div class="mb-4">
                <label for="numero_questoes" class="form-label">Quantas Questões Você Quer Treinar Agora? (1 a 10):</label>
                <input type="range" class="form-range" id="numero_questoes" name="numero_questoes" min="1" max="10" value="5" oninput="document.getElementById('numero_questoes_valor').innerText = this.value;">
                <p class="text-center">Quantidade Selecionada (Arraste): <span id="numero_questoes_valor">6</span></p>
            </div>

            <div class="mb-4">
                <label for="estilo" class="form-label">Como Você Quer Ser Testado?</label>
                <select class="form-select" id="estilo" name="estilo" required>
                    <option value="objetiva" selected>Objetiva</option>
                    <option value="caso clinico">Caso Clínico</option>
                    <option value="mix">Mix</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="literatura_dropdown" class="form-label">Qual Referência Você Quer que a IA Siga?</label>
                <select class="form-select" id="literatura_dropdown" name="literatura_dropdown" required>
                    <option value="" disabled selected>Defina a literatura médica de referência</option>
                    <?php foreach ($literaturasPadrao as $lit): ?>
                        <option value="<?= htmlspecialchars($lit) ?>"><?= htmlspecialchars($lit) ?></option>
                    <?php endforeach; ?>
                    <option value="outro">Outro (especificar)</option>
                </select>
            </div>

            <div class="mb-4" id="div_literatura_outro" style="display: none;">
                <label for="literatura_outro" class="form-label">Especifique a Literatura:</label>
                <textarea class="form-control" id="literatura_outro" name="literatura_outro" rows="2" placeholder="Ex: Livro de Fisiologia do Berne e Levy"></textarea>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary w-50">Treinar com Questões no Estilo da Minha Prova</button>
            </div>
        </form>
    </div>
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <div id="loadingText" class="fw-bold fs-5">Iniciando geração da prova...</div>
        </div>
    </div>
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        const $literaturaDropdown = $('#literatura_dropdown');
        const $divLiteraturaOutro = $('#div_literatura_outro');
        const $literaturaOutro = $('#literatura_outro');

        function toggleLiteraturaOutro() {
            if ($literaturaDropdown.val() === 'outro') {
                $divLiteraturaOutro.show();
                $literaturaOutro.prop('required', true);
            } else {
                $divLiteraturaOutro.hide();
                $literaturaOutro.val('');
                $literaturaOutro.prop('required', false);
            }
        }

        toggleLiteraturaOutro();
        $literaturaDropdown.on('change', toggleLiteraturaOutro);

        document.getElementById('numero_questoes_valor').innerText = document.getElementById('numero_questoes').value;

        const mensagens = [
            'Aplicando parâmetros...',
            'Verificando conteúdo...',
            'Gerando prova...'
        ];

        let msgIndex = 0;
        let msgInterval;

        $('form').on('submit', function (e) {
            $('#loadingModal').modal('show');

            msgIndex = 0;
            $('#loadingText').text(mensagens[msgIndex]);

            msgInterval = setInterval(() => {
                msgIndex++;
                if (msgIndex < mensagens.length) {
                    $('#loadingText').fadeOut(1600, function () {
                        $(this).text(mensagens[msgIndex]).fadeIn(1600);
                    });
                } else if (msgIndex === mensagens.length) {
                    setTimeout(() => {
                        $('#loadingText').fadeOut(1600, function () {
                            $(this).text('Finalizando...').fadeIn(1600);
                        });
                    }, 2000);
                    clearInterval(msgInterval);
                }
            }, 2500);
        });

        $('#loadingModal').on('hidden.bs.modal', function () {
            clearInterval(msgInterval);
        });
    });
</script>
</body>
<?php include_once 'footer.php'?>
</html>