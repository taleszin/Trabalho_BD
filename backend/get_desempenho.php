<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario']) || !isset($_GET['disciplina'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Acesso não autorizado ou parâmetros ausentes.']);
    exit;
}

require_once '../classes/config.php';
require_once '../classes/QuestaoService.php';

$idAluno = (int)$_SESSION['id_usuario'];
$disciplina = trim($_GET['disciplina']);

if (empty($disciplina)) {
    http_response_code(400);
    echo json_encode(['erro' => 'O nome da disciplina não pode ser vazio.']);
    exit;
}

try {
    $questaoService = new QuestaoService($conexao);
    $desempenho = $questaoService->getDesempenhoPorDisciplina($idAluno, $disciplina);
    echo json_encode($desempenho);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Erro na API de desempenho: ' . $e->getMessage());
    echo json_encode(['erro' => 'Ocorreu um erro interno ao buscar os dados de desempenho.']);
}

$conexao->close();
?>
