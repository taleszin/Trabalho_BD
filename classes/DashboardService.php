<?php
class DashboardService {
    private $db;

    public function __construct(mysqli $db) {
        $this->db = $db;
    }

    public function getStudentName(int $idAluno): string {
        $stmt = $this->db->prepare("SELECT nome FROM aluno WHERE idAluno = ?");
        $stmt->bind_param('i', $idAluno);
        $stmt->execute();
        $result = $stmt->get_result();
        $aluno = $result->fetch_assoc();
        return $aluno['nome'] ?? 'Aluno';
    }

    public function getOverall(int $idAluno): array {
        $stmt = $this->db->prepare("SELECT COUNT(idResposta) AS total, SUM(CASE WHEN correta = 1 THEN 1 ELSE 0 END) AS acertos FROM resposta WHERE idAluno = ?");
        $stmt->bind_param('i', $idAluno);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total = (int)($res['total'] ?? 0);
        $acertos = (int)($res['acertos'] ?? 0);
        $erros = $total - $acertos;
        $percentual = ($total > 0) ? round(($acertos / $total) * 100, 1) : 0;
        return ['total' => $total, 'acertos' => $acertos, 'erros' => $erros, 'percentual' => $percentual];
    }
    
    public function getByDiscipline(int $idAluno): array {
        $stmt = $this->db->prepare("SELECT q.disciplina, COUNT(r.idResposta) AS total, SUM(r.correta) AS acertos FROM resposta AS r JOIN questao AS q ON r.idQuestao = q.idQuestao WHERE r.idAluno = ? AND q.disciplina IS NOT NULL AND q.disciplina != '' GROUP BY q.disciplina ORDER BY total DESC LIMIT 10");
        $stmt->bind_param('i', $idAluno);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $total = (int)$row['total'];
            $acertos = (int)$row['acertos'];
            $data[] = ['disciplina' => $row['disciplina'], 'percentual' => ($total > 0) ? round(($acertos / $total) * 100, 1) : 0];
        }
        return array_reverse($data);
    }

    public function getPerformanceEvolution(int $idAluno): array {
        $query = "
            WITH dailyperformance AS (
                SELECT
                    DATE(r.dataResposta) AS dia_resposta,
                    COUNT(r.idResposta) AS daily_total,
                    SUM(r.correta) AS daily_correct
                FROM resposta r
                WHERE r.idAluno = ?
                GROUP BY dia_resposta
            ),
            cumulativeperformance AS (
                SELECT
                    dia_resposta,
                    SUM(daily_total) OVER (ORDER BY dia_resposta ASC ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS cumulative_total,
                    SUM(daily_correct) OVER (ORDER BY dia_resposta ASC ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS cumulative_correct
                FROM dailyperformance
            )
            SELECT
                DATE_FORMAT(dia_resposta, '%d/%m') AS data,
                (cumulative_correct / cumulative_total) * 100 AS percentual
            FROM cumulativeperformance
            ORDER BY dia_resposta ASC
            LIMIT 30
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $idAluno);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getComparativePerformance(int $idAluno): array {
        $query = "
            WITH alunototal AS (
                SELECT q.disciplina, COUNT(r.idResposta) AS total_respostas
                FROM resposta r JOIN questao q ON r.idQuestao = q.idQuestao
                WHERE r.idAluno = ?
                GROUP BY q.disciplina
            ),
            topdisciplinas AS (
                SELECT disciplina FROM alunototal ORDER BY total_respostas DESC LIMIT 5
            )
            SELECT 
                t.disciplina,
                AVG(CASE WHEN t.idAluno = ? THEN t.correta END) * 100 AS media_aluno,
                AVG(t.correta) * 100 AS media_plataforma
            FROM (
                SELECT r.idAluno, r.correta, q.disciplina
                FROM resposta r JOIN questao q ON r.idQuestao = q.idQuestao
                WHERE q.disciplina IN (SELECT disciplina FROM topdisciplinas)
            ) AS t
            GROUP BY t.disciplina
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $idAluno, $idAluno);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
