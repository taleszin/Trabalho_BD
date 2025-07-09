<?php
// =================================================================
// SCRIPT DE INSERÇÃO DE USUÁRIO (ALUNO)
// =================================================================

// --- 1. INCLUI O ARQUIVO DE CONEXÃO ---
// O arquivo agora utiliza a variável $conexao do seu config.php
require_once '../classes/config.php';

// --- 2. PROCESSAMENTO DO FORMULÁRIO (QUANDO ENVIADO) ---
$mensagem = ""; // Variável para armazenar mensagens de sucesso ou erro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica se todos os campos foram enviados
    if (isset($_POST['nome'], $_POST['email'], $_POST['senha'], $_POST['idInstituicao']) && !empty($_POST['idInstituicao'])) {
        
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        // Converte o ID para inteiro por segurança
        $idInstituicao = (int)$_POST['idInstituicao'];
        
        // ** SEGURANÇA: Criptografa a senha antes de salvar **
        $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);

        // Prepara a query SQL para evitar injeção de SQL usando prepared statements do mysqli
        $sql = "INSERT INTO aluno (nome, email, senha, idInstituicao) VALUES (?, ?, ?, ?)";
        
        if ($stmt = $conexao->prepare($sql)) {
            // Associa os parâmetros da query com as variáveis
            // 'sssi' -> string, string, string, integer
            $stmt->bind_param('sssi', $nome, $email, $senhaHash, $idInstituicao);

            // Executa a query e verifica se foi bem-sucedida
            if ($stmt->execute()) {
                $mensagem = '<div class="alert alert-success mt-3" role="alert">Aluno inserido com sucesso!</div>';
            } else {
                // Trata erros, como email duplicado, etc.
                $mensagem = '<div class="alert alert-danger mt-3" role="alert">Erro ao inserir aluno: ' . $stmt->error . '</div>';
            }
            // Fecha o statement
            $stmt->close();
        } else {
             $mensagem = '<div class="alert alert-danger mt-3" role="alert">Erro ao preparar a consulta: ' . $conexao->error . '</div>';
        }

    } else {
        $mensagem = '<div class="alert alert-warning mt-3" role="alert">Por favor, preencha todos os campos.</div>';
    }
}

// --- 3. BUSCA DAS INSTITUIÇÕES PARA O DROPDOWN ---
$instituicoes = [];
$sql_instituicoes = "SELECT idInstituicao, nome FROM instituicao ORDER BY nome ASC";
$result = $conexao->query($sql_instituicoes);

if ($result && $result->num_rows > 0) {
    // Busca todos os resultados como um array associativo
    $instituicoes = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($conexao->error) {
    // Se não conseguir buscar as instituições, encerra o script
    die("Erro ao buscar instituições: " . $conexao->error);
}

// Fecha a conexão ao final do script
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Inserir Novo Aluno</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos adicionais para um visual melhor */
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="container">
        <h2 class="mb-4 text-center">Cadastrar Novo Aluno</h2>
        
        <!-- Exibe a mensagem de sucesso ou erro, se houver -->
        <?php echo $mensagem; ?>

        <!-- Formulário de inserção -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            
            <!-- Campo Nome -->
            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
            </div>
            
            <!-- Campo Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <!-- Campo Senha -->
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" required>
            </div>
            
            <!-- Campo Instituição (Dropdown) -->
            <div class="mb-3">
                <label for="idInstituicao" class="form-label">Instituição</label>
                <select class="form-select" id="idInstituicao" name="idInstituicao" required>
                    <option value="" selected disabled>-- Selecione uma instituição --</option>
                    <?php
                        // Itera sobre a lista de instituições buscada do banco
                        // e cria uma <option> para cada uma.
                        if (!empty($instituicoes)) {
                            foreach ($instituicoes as $instituicao) {
                                echo '<option value="' . htmlspecialchars($instituicao['idInstituicao']) . '">' . htmlspecialchars($instituicao['nome']) . '</option>';
                            }
                        }
                    ?>
                </select>
            </div>

            <!-- Botão de Envio -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Cadastrar Aluno</button>
            </div>

        </form>
    </div>

    <!-- Bootstrap 5 JS Bundle via CDN (opcional, mas recomendado) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
