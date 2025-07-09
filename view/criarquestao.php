<?php
include('../classes/config.php');

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$assuntosResult = $conn->query("SELECT idAssunto, nome FROM Assunto WHERE status = 1");
$assuntos = [];
if ($assuntosResult->num_rows > 0) {
    while ($row = $assuntosResult->fetch_assoc()) {
        $assuntos[] = $row;
}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $enunciado = $_POST['enunciado'];
    $idAssunto = $_POST['assunto'];
    $status = isset($_POST['status']) ? 1 : 0;
    $alternativas = $_POST['alternativa'];
    $correta = $_POST['correta'];

   
    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare("INSERT INTO Questao (Enunciado, idAssunto, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $enunciado, $idAssunto, $status);
        $stmt->execute();

        $idQuestao = $stmt->insert_id; 
        $stmt->close();


        foreach ($alternativas as $index => $alternativa) {
            $letra = chr(65 + $index); // A, B, C, etc.
            $corretaFlag = ($correta == $letra) ? 1 : 0;
            $stmt = $conn->prepare("INSERT INTO Alternativa (idQuestao, letra, texto, correta) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $idQuestao, $letra, $alternativa, $corretaFlag);
            $stmt->execute();
            $stmt->close();
        }

       
        $conn->commit();
        $msg = "questão criada com sucesso!";
    } catch (Exception $e) {
        
        $conn->rollback();
        $msg = "erro ao criar a questão" . $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Questão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Criar Questão</a>
    </div>
</nav>

<div class="container mt-4">
    <h2>Cadastrar Nova Questão</h2>

    <?php if (isset($msg)): ?>
        <div class="alert alert-info" role="alert">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="criarQuestao.php">
        <div class="mb-3">
            <label for="enunciado" class="form-label">Enunciado</label>
            <textarea id="enunciado" name="enunciado" class="form-control" rows="4" required></textarea>
        </div>

        <div class="mb-3">
            <label for="assunto" class="form-label">Assunto</label>
            <select id="assunto" name="assunto" class="form-select" required>
                <option value="">Selecione um assunto</option>
                <?php foreach ($assuntos as $assunto): ?>
                    <option value="<?php echo $assunto['idAssunto']; ?>"><?php echo $assunto['nome']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="status" name="status">
            <label class="form-check-label" for="status">Ativar Questão</label>
        </div>

        <h4>Alternativas</h4>
        <div class="mb-3">
            <label for="alternativaA" class="form-label">Alternativa A</label>
            <input type="text" id="alternativaA" name="alternativa[]" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="alternativaB" class="form-label">Alternativa B</label>
            <input type="text" id="alternativaB" name="alternativa[]" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="alternativaC" class="form-label">Alternativa C</label>
            <input type="text" id="alternativaC" name="alternativa[]" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="alternativaD" class="form-label">Alternativa D</label>
            <input type="text" id="alternativaD" name="alternativa[]" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="correta" class="form-label">Alternativa Correta</label>
            <select id="correta" name="correta" class="form-select" required>
                <option value="A">Alternativa A</option>
                <option value="B">Alternativa B</option>
                <option value="C">Alternativa C</option>
                <option value="D">Alternativa D</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Registrar Questão</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
