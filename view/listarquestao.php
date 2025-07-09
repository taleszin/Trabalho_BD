<?php
include('../classes/config.php');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT q.idQuestao, q.Enunciado, a.letra, a.texto, a.correta
        FROM Questao q 
        JOIN Alternativa a ON q.idQuestao = a.idQuestao";

$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Listar Questões</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Visualizar Questões</a>
    </div>
</nav>

<div class="container mt-4" id="questoesContainer">
    <?php if ($result->num_rows > 0): ?>
        <?php
        $questoes = [];
        while($row = $result->fetch_assoc()) {
            $questaoId = $row['idQuestao'];
            $questoes[$questaoId]['id'] = $questaoId;
            $questoes[$questaoId]['enunciado'] = $row['Enunciado'];
            $questoes[$questaoId]['alternativas'][] = [
                'letra' => $row['letra'],
                'texto' => $row['texto'],
                'correta' => $row['correta']
            ];
        }
        foreach ($questoes as $questao):
        ?>
            <a href="detalharquestao.php?id=<?php echo $questao['id']; ?>" style="text-decoration: none; color: inherit;">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Questão <?php echo $questao['id']; ?></h5>
                        <p class="card-text"><?php echo $questao['enunciado']; ?></p>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($questao['alternativas'] as $index => $alt): ?>
                                <li class="list-group-item">
                                    <?php echo chr(65 + $index) . ") " . $alt['texto']; ?>
                                    <?php if ($alt['correta']) { echo " (Correta)"; } ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nenhuma questão encontrada.</p>
    <?php endif; ?>
</div>
</body>
</html>
