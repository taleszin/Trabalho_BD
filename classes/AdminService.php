<?php
class AdminService {
    private $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function getKpiStats(string $startDate, string $endDate): array {
        $stats = [];
        $queries = [
            'total_alunos' => "SELECT COUNT(idAluno) as total FROM aluno",
            'total_provas' => "SELECT COUNT(idProva) as total FROM prova WHERE DATE(dataProva) BETWEEN ? AND ?",
            'total_questoes' => "SELECT COUNT(idQuestao) as total FROM questao",
            'total_respostas' => "SELECT COUNT(idResposta) as total FROM resposta WHERE DATE(dataResposta) BETWEEN ? AND ?"
        ];
        
        $stats['total_alunos'] = $this->db->query($queries['total_alunos'])->fetch_assoc()['total'] ?? 0;
        $stats['total_questoes'] = $this->db->query($queries['total_questoes'])->fetch_assoc()['total'] ?? 0;

        $stmtProvas = $this->db->prepare($queries['total_provas']);
        $stmtProvas->bind_param('ss', $startDate, $endDate);
        $stmtProvas->execute();
        $stats['total_provas'] = $stmtProvas->get_result()->fetch_assoc()['total'] ?? 0;

        $stmtRespostas = $this->db->prepare($queries['total_respostas']);
        $stmtRespostas->bind_param('ss', $startDate, $endDate);
        $stmtRespostas->execute();
        $stats['total_respostas'] = $stmtRespostas->get_result()->fetch_assoc()['total'] ?? 0;

        return $stats;
    }

    public function getNewUserGrowth(string $startDate, string $endDate): array {
        $sql = "SELECT DATE(dataHora) as dia, COUNT(idLog) as novos_alunos FROM log WHERE acao = 'cadastro' AND DATE(dataHora) BETWEEN ? AND ? GROUP BY dia ORDER BY dia ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTopDisciplines(string $startDate, string $endDate, string $orderBy = 'total_respostas', int $limit = 5): array {
        $orderClause = $orderBy === 'dificuldade' ? 'AVG(r.correta) ASC' : 'COUNT(r.idQuestao) DESC';
        $sql = "
            SELECT q.disciplina, AVG(r.correta) * 100 AS percentual_acerto, COUNT(r.idQuestao) as total_respostas
            FROM resposta r JOIN questao q ON r.idQuestao = q.idQuestao
            WHERE DATE(r.dataResposta) BETWEEN ? AND ? AND q.disciplina IS NOT NULL AND q.disciplina != ''
            GROUP BY q.disciplina HAVING COUNT(r.idQuestao) > 3
            ORDER BY $orderClause LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $startDate, $endDate, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getRetentionCohorts(string $startDate, string $endDate, string $periodType = 'week'): array {
        if ($periodType !== 'day' && $periodType !== 'week') {
            throw new InvalidArgumentException("Tipo de período deve ser 'day' ou 'week'.");
        }

        $periodExpression = $periodType === 'week'
            ? "FLOOR(DATEDIFF(ua.activity_date, c.cohort_date) / 7)"
            : "DATEDIFF(ua.activity_date, c.cohort_date)";
        
        $periodColumnName = $periodType . '_number';

        $sql = "
            WITH cohort_users AS (
                SELECT idAluno, DATE(dataHora) as cohort_date
                FROM log WHERE acao = 'cadastro' AND DATE(dataHora) >= ?
            ),
            user_activity AS (
                SELECT DISTINCT idAluno, DATE(dataHora) as activity_date
                FROM log WHERE DATE(dataHora) BETWEEN ? AND ?
            )
            SELECT
                DATE_FORMAT(c.cohort_date, '%Y-%m-%d') as cohort,
                $periodExpression AS $periodColumnName,
                COUNT(DISTINCT c.idAluno) AS total_users
            FROM cohort_users c JOIN user_activity ua ON c.idAluno = ua.idAluno
            WHERE ua.activity_date >= c.cohort_date
            GROUP BY cohort, $periodColumnName ORDER BY cohort DESC, $periodColumnName ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $startDate, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getTopActiveUsers(string $startDate, string $endDate, int $limit = 5): array {
        $sql = "
            SELECT a.nome, COUNT(r.idResposta) as total_respostas
            FROM resposta r
            JOIN aluno a ON r.idAluno = a.idAluno
            WHERE DATE(r.dataResposta) BETWEEN ? AND ?
            GROUP BY a.idAluno, a.nome
            ORDER BY total_respostas DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $startDate, $endDate, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getInstitutionDistribution(): array {
        $sql = "
            SELECT i.sigla, COUNT(a.idAluno) as total_alunos
            FROM aluno a
            JOIN instituicao i ON a.idInstituicao = i.idInstituicao
            GROUP BY i.idInstituicao, i.sigla
            HAVING total_alunos > 0
            ORDER BY total_alunos DESC
        ";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Busca os últimos logs do sistema.
     * @param int $limit O número de logs a serem retornados.
     * @return array
     */
    public function getSystemLogs(int $limit = 10): array {
        $sql = "
            SELECT l.idLog, l.acao, l.dataHora, a.nome as nomeAluno
            FROM log l
            LEFT JOIN aluno a ON l.idAluno = a.idAluno
            ORDER BY l.dataHora DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>