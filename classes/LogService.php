<?php
// Verificação dupla para evitar conflitos de timezone em diferentes ambientes
if (function_exists('date_default_timezone_set')) {
    $currentTz = @date_default_timezone_get();
    if (!$currentTz || $currentTz === 'UTC') {
        date_default_timezone_set('America/Sao_Paulo');
    }
} else {
    date_default_timezone_set('America/Sao_Paulo');
}

class LogService {
    private $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function logAction(int $idAluno, string $acao) {
        $sql = "INSERT INTO log (idAluno, acao, dataHora) VALUES (?, ?, NOW())";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $idAluno, $acao);
            $stmt->execute();
            $stmt->close();
            return true;
        } catch (Exception $e) {
            error_log("Erro no LogService: " . $e->getMessage());
            return false;
        }
    }

    public function getLogs($limit = 10, $offset = 0) {
        $sql = "SELECT l.*, a.nome as nomeAluno FROM log l LEFT JOIN aluno a ON l.idAluno = a.idAluno ORDER BY l.dataHora DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        return $logs;
    }
}
?>