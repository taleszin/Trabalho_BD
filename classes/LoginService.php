<?php
include("config.php");
require_once __DIR__ . '/LogService.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['email']) && isset($data['senha'])) {
        $email = $data['email'];
        $senha = $data['senha'];
        $sql = "SELECT * FROM aluno WHERE email = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $ID = $row['idAluno'];
            $NOME = $row['nome'];
            if (password_verify($senha, $row['senha'])) {
                $_SESSION['id_usuario'] = $ID;
                $_SESSION['nome_usuario'] = $NOME;
                if ($ID == 1 || $ID == 4 || $ID == 35) { 
                 $_SESSION['is_admin'] = true;
                } else {
                    $_SESSION['is_admin'] = false;
                }
                $logService = new LogService($conexao);
                $logService->logAction($ID, 'Login');
                echo json_encode(["success" => true, "message" => "Login realizado com sucesso", "redirect" => "questoes.php"]);
                exit();
            } else {
                echo json_encode(["success" => false, "error" => "Senha incorreta."]);
                exit();
            }
        } else {
            echo json_encode(["success" => false, "error" => "Usuário inexistente."]);
            exit();
        }
    } else {
        echo json_encode(["success" => false, "error" => "Dados de entrada inválidos."]);
        exit();
    }
}
?>