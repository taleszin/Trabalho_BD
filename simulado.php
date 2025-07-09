<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>MedLeap - Gerador de Questões</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 700px;
    }
    .form-section {
      background-color: #ffffff;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
    }
    .form-section h2 {
      font-weight: 600;
      color: #0d6efd;
      margin-bottom: 20px;
    }
    .logo {
      font-size: 1.5rem;
      font-weight: 600;
      color: #0d6efd;
    }
    .logo i {
      color: #198754;
      margin-right: 8px;
    }
    footer {
      margin-top: 40px;
      text-align: center;
      font-size: 0.9rem;
      color: #6c757d;
    }
  </style>
</head>
<body>

  <div class="container mt-5">
    <div class="text-center mb-4">
      <div class="logo"><i class="bi bi-stethoscope"></i> MedLeap - Protótipo</div>
      <p class="text-muted">Gere questões médicas a partir de provas anteriores em PDF</p>
    </div>

    <div class="form-section">
      <h2><i class="bi bi-file-earmark-plus-fill"></i> Enviar PDF e Gerar Questões</h2>

      <form id="questionForm" action="backend/processar_pdf.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="pdfFile" class="form-label">Selecione um PDF de prova anterior:</label>
          <input class="form-control" type="file" id="pdfFile" name="pdfFile" accept="application/pdf" required>
        </div>

        <div class="mb-3">
          <label for="numQuestions" class="form-label">Número de questões a gerar:</label>
          <input type="range" class="form-range" id="numQuestions" name="numQuestions" min="5" max="20" value="30" required>
          <span id="numQuestionsValue">20</span>
        </div>

        <div class="mb-3">
          <label for="questionTypes" class="form-label">Tipos de Questões:</label>
          <div>
            <input type="checkbox" id="multipleChoice" name="questionTypes[]" value="multipleChoice" checked>
            <label for="multipleChoice">Múltipla Escolha</label>
          </div>
          <div>
            <input type="checkbox" id="trueFalse" name="questionTypes[]" value="trueFalse">
            <label for="trueFalse">Verdadeiro/Falso</label>
          </div>
          <div>
            <input type="checkbox" id="discursive" name="questionTypes[]" value="discursive">
            <label for="discursive">Discursivas</label>
          </div>
        </div>

        <div class="mb-3">
          <label for="topicDistribution" class="form-label">Distribuição de Assuntos:</label>
          <select class="form-select" id="topicDistribution" name="topicDistribution" required>
            <option value="proportional">Proporcional à prova original</option>
            <option value="equal">Distribuição igual entre os temas</option>
            <option value="specific">Foco em áreas específicas</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="difficulty" class="form-label">Nível de Dificuldade:</label>
          <select class="form-select" id="difficulty" name="difficulty" required>
            <option value="medium" selected>Médio</option>
            <option value="easy">Fácil</option>
            <option value="hard">Difícil</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="trainingMode" class="form-label">Modo de Treinamento:</label>
          <div>
            <input type="checkbox" id="feedback" name="trainingMode[]" value="feedback" checked>
            <label for="feedback">Feedback Imediato</label>
          </div>
          <div>
            <input type="checkbox" id="allowSkip" name="trainingMode[]" value="allowSkip" checked>
            <label for="allowSkip">Permitir Pular Questões</label>
          </div>
        </div>

        <div class="mb-3">
          <label for="timer" class="form-label">Cronômetro de Tempo Real:</label>
          <select class="form-select" id="timer" name="timer" required>
            <option value="yes" selected>Simular o tempo por questão</option>
            <option value="no">Não</option>
          </select>
        </div>

        <div class="mb-4">
          <label for="outputFormat" class="form-label">Formato da saída:</label>
          <select class="form-select" id="outputFormat" name="outputFormat" required>
            <option value="simple" selected>Lista simples com respostas imediatas</option>
            <option value="full">Simulado completo com respostas no final</option>
          </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Gerar Questões</button>
      </form>
    </div>

    <footer class="mt-5">
      © 2025 MedLeap · Protótipo teste
    </footer>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Atualiza o valor do número de questões conforme o usuário altera o range
    const numQuestionsInput = document.getElementById('numQuestions');
    const numQuestionsValue = document.getElementById('numQuestionsValue');
    numQuestionsInput.addEventListener('input', function() {
      numQuestionsValue.textContent = numQuestionsInput.value;
    });
  </script>
</body>
</html>
