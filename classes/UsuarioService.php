<?php
// /classes/UsuarioService.php

// Inclui seu arquivo de conexão com o banco de dados
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/LogService.php';
class UsuarioService {
    private $db; // Conexão mysqli

    /**
     * O construtor recebe a conexão mysqli.
     * @param mysqli $mysqli_connection
     */
    public function __construct($mysqli_connection) {
        $this->db = $mysqli_connection;
    }

    /**
     * Busca todas as instituições ativas no banco de dados para popular o dropdown.
     */
    public function getInstituicoes() {
        $sql = "SELECT idInstituicao, nome, sigla FROM instituicao WHERE status = 1 ORDER BY nome ASC";
        $result = $this->db->query($sql);

        if ($result) {
            $instituicoes = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $instituicoes]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'error' => 'Erro ao buscar instituições.']);
        }
    }

    /**
     * Registra um novo aluno no banco de dados.
     * @param object $data - Dados do formulário (nome, email, senha, idInstituicao)
     */
    public function registrar($data) {
        // 1. Validação e limpeza dos dados
        $nome = isset($data->nome) ? $this->db->real_escape_string(trim($data->nome)) : '';
        $email = isset($data->email) ? trim($data->email) : '';
        $senha = isset($data->senha) ? trim($data->senha) : '';
        $idInstituicao = isset($data->idInstituicao) ? filter_var(trim($data->idInstituicao), FILTER_VALIDATE_INT) : false;

        if (!$nome || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($senha) || $idInstituicao === false) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios e devem ser válidos.']);
            return;
        }

        if (strlen($senha) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'A senha deve ter no mínimo 8 caracteres.']);
            return;
        }

        // 2. Verificar se o e-mail já existe (com Prepared Statements para evitar SQL Injection)
        $stmt = $this->db->prepare("SELECT idAluno FROM aluno WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'error' => 'Este e-mail já está cadastrado.']);
            $stmt->close();
            return;
        }
        $stmt->close();

        // 3. Criptografar a senha (essencial para a segurança)
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // 4. Inserir o novo aluno no banco de dados (com Prepared Statements)
        $stmt = $this->db->prepare("INSERT INTO aluno (nome, email, senha, idInstituicao) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $nome, $email, $senhaHash, $idInstituicao);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $idNovoAluno = $this->db->insert_id;
            $logService = new LogService($this->db);
            $logService->logAction($idNovoAluno, 'Cadastro');
            echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso! Você já pode fazer o login.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Não foi possível concluir o cadastro. Tente novamente.']);
        }
        $stmt->close();
    }
}

// --- Roteador de Ações ---
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

// A variável $conexao vem do seu arquivo config.php
if (!isset($conexao) || $conexao->connect_error) {
     http_response_code(503); // Service Unavailable
     echo json_encode(['success' => false, 'error' => 'Falha na conexão com o serviço de dados.']);
     exit();
}

$service = new UsuarioService($conexao);

switch ($action) {
    case 'getInstituicoes':
        $service->getInstituicoes();
        break;
    case 'registrar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'));
            $service->registrar($data);
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
        }
        break;
    default:
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'error' => 'Ação desconhecida.']);
        break;
}

// Fecha a conexão com o banco de dados
$conexao->close();