<?php
include('../classes/config.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$sql = "SELECT q.Enunciado, q.idAssunto, a.letra, a.texto, a.correta 
        FROM Questao q
        JOIN Alternativa a ON q.idQuestao = a.idQuestao
        WHERE q.idQuestao = $id";

$result = $conn->query($sql);
if (!$result) {
    die("Erro na consulta: " . $conn->error);
}

$questao = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (!isset($questao['enunciado'])) {
            $questao['enunciado'] = $row['Enunciado'];
            $questao['idAssunto'] = $row['idAssunto'];
            $questao['alternativas'] = [];
        }
        $questao['alternativas'][] = [
            'letra' => $row['letra'],
            'texto' => $row['texto'],
            'correta' => $row['correta']
        ];
    }
} else {
    die("Questão não encontrada.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhar Questão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .alternativa {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .alternativa:hover {
            background-color: #d4edda;
        }
        .alternativa.cut {
            opacity: 0.4;
            text-decoration: line-through;
            background-color: #f8d7da !important;
        }
        .icon-cut {
            cursor: pointer;
            font-size: 18px;
        }
        .selecionada {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Detalhar Questão</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Questão #<?php echo htmlspecialchars($id); ?></h5>
        </div>
        <div class="card-body">
            <h6 class="card-title">Enunciado</h6>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($questao['enunciado'])); ?></p>

            <h6 class="mt-4">Alternativas</h6>
            <ul class="list-group" id="lista-alternativas">
                <?php foreach ($questao['alternativas'] as $index => $alt): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center alternativa" 
                        id="alt-<?php echo $index; ?>" data-idQuestao="<?php echo $id; ?>" data-letra="<?php echo $alt['letra']; ?>" onclick="selecionar(this)">
                        <span><strong><?php echo strtoupper($alt['letra']); ?>)</strong> <?php echo htmlspecialchars($alt['texto']); ?></span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="icon-cut" onclick="cortar(event, 'alt-<?php echo $index; ?>')">✂️</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <button class="btn btn-primary mt-3" onclick="responder()">Responder</button>
            <a href="listarQuestoes.php" class="btn btn-secondary mt-3">Voltar</a>
        </div>
    </div>
</div>

<script>
    let selecionada = null;

    function selecionar(element) {
        if (element.classList.contains('cut')) return;
        document.querySelectorAll('.alternativa').forEach(el => el.classList.remove('selecionada'));
        element.classList.add('selecionada');
        selecionada = {
            idQuestao: element.getAttribute('data-idQuestao'),
            letra: element.getAttribute('data-letra')
        };
    }

    function cortar(event, id) {
        event.stopPropagation();
        const el = document.getElementById(id);
        el.classList.toggle('cut');
    }

    function responder() {
        if (!selecionada) {
            alert("Selecione uma alternativa");
            return;
        }
        
        $.ajax({
            url: '../classes/AlternativaService.php',
            method: 'POST',
            data: {
                idQuestao: selecionada.idQuestao,
                letraAlternativa: selecionada.letra
            },
            success: function(response) {
                let data = JSON.parse(response);
                alert(data.resultado);
                alert('deu certo')
            },
            error: function() {
                alert("Erro na requisição");
            }
        });
    }
</script>

</body>
</html>
