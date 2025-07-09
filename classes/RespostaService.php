<?php
date_default_timezone_set('America/Sao_Paulo'); // Ou no seu config.php global
include_once("LogService.php");
class RespostaService
{
    private $conexao;

    public function __construct($conexao)
    {
        $this->conexao = $conexao;
    }

    public function salvarResposta(
        int $idAluno,
        int $idQuestao,
        string $letraResposta,
        bool $correta,
        string $dataResposta = null
    ): int
    {
        if (strlen($letraResposta) !== 1 || !ctype_alpha($letraResposta)) {
            throw new InvalidArgumentException("Letra da resposta inválida: '$letraResposta'.");
        }
        $letraNormalizada = strtoupper($letraResposta);

        if ($dataResposta === null) {
            $dataResposta = date('Y-m-d H:i:s');
        } elseif (!DateTime::createFromFormat('Y-m-d H:i:s', $dataResposta)) {
            throw new InvalidArgumentException("Formato da data inválido: '$dataResposta'. Use AAAA-MM-DD HH:MM:SS.");
        }

        // SQL não inclui mais a coluna 'subarea' que foi removida da tabela 'resposta'
        $sql = "INSERT INTO resposta (idAluno, idQuestao, letraResposta, correta, dataResposta) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conexao->prepare($sql);

        if (!$stmt) {
            throw new Exception("Falha ao preparar query para salvar resposta: " . $this->conexao->error);
        }

        $corretaInt = $correta ? 1 : 0;

        // Tipos de bind_param atualizados: iisis (int, int, string, int, string)
        $stmt->bind_param(
            "iisis",
            $idAluno,
            $idQuestao,
            $letraNormalizada,
            $corretaInt,
            // $subarea, // REMOVIDO
            $dataResposta
        );

        if ($stmt->execute()) {
            $idInserido = $this->conexao->insert_id;
            $stmt->close();
            return $idInserido;
        } else {
            $erro = $stmt->error;
            $stmt->close();
            error_log("Erro ao salvar resposta: $erro. Aluno: $idAluno, Questão: $idQuestao");
            throw new Exception("Falha ao executar query para salvar resposta: " . $erro);
        }
    }

    public function resumoGeral(int $idAluno): array
    {
        $sql = "SELECT
                    COUNT(*) AS totalRespondidas,
                    SUM(CASE WHEN correta = 1 THEN 1 ELSE 0 END) AS totalCorretas
                FROM resposta
                WHERE idAluno = ?";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Falha ao preparar query (resumoGeral): " . $this->conexao->error);
        }
        $stmt->bind_param("i", $idAluno);
        $metricas = ['totalRespondidas' => 0, 'totalCorretas' => 0, 'totalErradas' => 0, 'percentualCorretas' => 0.0];
        if ($stmt->execute()) {
            $resultado = $stmt->get_result()->fetch_assoc();
            if ($resultado) {
                $metricas['totalRespondidas'] = (int)$resultado['totalRespondidas'];
                $metricas['totalCorretas'] = (int)$resultado['totalCorretas'];
                if ($metricas['totalRespondidas'] > 0) {
                    $metricas['totalErradas'] = $metricas['totalRespondidas'] - $metricas['totalCorretas'];
                    $metricas['percentualCorretas'] = round(($metricas['totalCorretas'] / $metricas['totalRespondidas']) * 100, 2);
                }
            }
            $stmt->close();
        } else {
            $erro = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (resumoGeral): " . $erro);
        }
        return $metricas;
    }

    // Renomeado e modificado para usar questao.disciplina
    public function getResumoPorDisciplina(int $idAluno): array
    {
        $sql = "SELECT
                    q.disciplina, 
                    COUNT(r.idResposta) AS totalRespondidas,
                    SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) AS totalCorretas
                FROM resposta r
                JOIN questao q ON r.idQuestao = q.idQuestao
                WHERE r.idAluno = ? AND q.disciplina IS NOT NULL AND q.disciplina != ''
                GROUP BY q.disciplina
                ORDER BY q.disciplina ASC";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Falha ao preparar query (getResumoPorDisciplina): " . $this->conexao->error);
        }
        $stmt->bind_param("i", $idAluno);
        $desempenho = [];
        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            while ($linha = $resultado->fetch_assoc()) {
                $totalRespondidasNaDisciplina = (int)$linha['totalRespondidas'];
                $totalCorretasNaDisciplina = (int)$linha['totalCorretas'];
                $desempenho[] = [
                    'disciplina' => $linha['disciplina'], // Chave atualizada
                    'totalRespondidas' => $totalRespondidasNaDisciplina,
                    'totalCorretas' => $totalCorretasNaDisciplina,
                    'totalErradas' => $totalRespondidasNaDisciplina - $totalCorretasNaDisciplina,
                    'percentualCorretas' => ($totalRespondidasNaDisciplina > 0) ? round(($totalCorretasNaDisciplina / $totalRespondidasNaDisciplina) * 100, 2) : 0.0
                ];
            }
            $stmt->close();
        } else {
            $erro = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (getResumoPorDisciplina): " . $erro);
        }
        return $desempenho;
    }

    // Renomeado e modificado para usar getResumoPorDisciplina
    public function getDestaquesPorDisciplina(int $idAluno, int $minRespostasParaPior = 3): array
    {
        $resumoDisciplinas = $this->getResumoPorDisciplina($idAluno); 
        $melhor = null; $pior = null;
        if (empty($resumoDisciplinas)) return ['melhorDesempenho' => null, 'piorDesempenho' => null];
        
        $percentualMaisAlto = -1; $percentualMaisBaixo = 101;
        foreach ($resumoDisciplinas as $item) { 
            if ($item['percentualCorretas'] > $percentualMaisAlto) { $percentualMaisAlto = $item['percentualCorretas']; $melhor = $item; }
            if ($item['totalRespondidas'] >= $minRespostasParaPior && $item['percentualCorretas'] < $percentualMaisBaixo) { $percentualMaisBaixo = $item['percentualCorretas']; $pior = $item; }
        }
        if ($pior === null && !empty($resumoDisciplinas)) {
            $percentualMaisBaixoGeral = 101;
            foreach ($resumoDisciplinas as $item) { if ($item['percentualCorretas'] < $percentualMaisBaixoGeral) { $percentualMaisBaixoGeral = $item['percentualCorretas']; $pior = $item; } }
        }
        return ['melhorDesempenho' => $melhor, 'piorDesempenho' => $pior];
    }
    
    public function mediaGeralPlataforma(?int $idAlunoExcluir = null): ?float 
    {
        $sql = "SELECT
                    SUM(CASE WHEN correta = 1 THEN 1 ELSE 0 END) AS totalAcertos,
                    COUNT(idResposta) AS totalRespostas
                FROM resposta";
        $params = []; $types = "";
        if ($idAlunoExcluir !== null) {
            $sql .= " WHERE idAluno != ?";
            $params[] = $idAlunoExcluir; $types .= "i";
        }
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) { throw new Exception("Falha ao preparar query (mediaGeralPlataforma): " . $this->conexao->error); }
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $percentual = null;
        if ($stmt->execute()) {
            $res = $stmt->get_result()->fetch_assoc();
            if ($res && isset($res['totalRespostas']) && (int)$res['totalRespostas'] > 0) {
                $totalAcertos = isset($res['totalAcertos']) ? (int)$res['totalAcertos'] : 0;
                $percentual = round(($totalAcertos / (int)$res['totalRespostas']) * 100, 2);
            }
            $stmt->close();
        } else {
            $error = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (mediaGeralPlataforma): " . $error);
        }
        return $percentual;
    }

    // Renomeado e modificado para usar questao.disciplina
    public function getMediaPorDisciplinaPlataforma(?int $idAlunoExcluir = null): array 
    {
        $sql = "SELECT
                    q.disciplina,
                    SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) AS totalAcertos,
                    COUNT(r.idResposta) AS totalRespostas
                FROM resposta r
                JOIN questao q ON r.idQuestao = q.idQuestao
                WHERE q.disciplina IS NOT NULL AND q.disciplina != ''";
        
        $params = []; $types = "";
        if ($idAlunoExcluir !== null) {
            $sql .= " AND r.idAluno != ?";
            $params[] = $idAlunoExcluir; $types .= "i";
        }
        $sql .= " GROUP BY q.disciplina ORDER BY q.disciplina ASC";
        
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) { throw new Exception("Falha ao preparar query (getMediaPorDisciplinaPlataforma): " . $this->conexao->error); }
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $medias = [];
        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            while ($linha = $resultado->fetch_assoc()) {
                $percentual = 0.0;
                if (isset($linha['totalRespostas']) && (int)$linha['totalRespostas'] > 0) {
                    $totalAcertos = isset($linha['totalAcertos']) ? (int)$linha['totalAcertos'] : 0;
                    $percentual = round(($totalAcertos / (int)$linha['totalRespostas']) * 100, 2);
                }
                $medias[$linha['disciplina']] = $percentual;
            }
            $stmt->close();
        } else {
            $error = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (getMediaPorDisciplinaPlataforma): " . $error);
        }
        return $medias;
    }
    
    // Este método já usava questao.disciplina, então está correto.
    public function getDisciplinasRespondidasQuestao(int $idAluno): array
    {
        $sql = "SELECT DISTINCT q.disciplina 
                FROM resposta r
                JOIN questao q ON r.idQuestao = q.idQuestao
                WHERE r.idAluno = ? AND q.disciplina IS NOT NULL AND q.disciplina != ''
                ORDER BY q.disciplina ASC";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) { throw new Exception("Falha ao preparar query (getDisciplinasRespondidasQuestao): " . $this->conexao->error); }
        $stmt->bind_param("i", $idAluno);
        $disciplinas = [];
        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            while ($linha = $resultado->fetch_assoc()) {
                $disciplinas[] = $linha['disciplina'];
            }
            $stmt->close();
        } else {
            $error = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (getDisciplinasRespondidasQuestao): " . $error);
        }
        return $disciplinas;
    }

    // Este método já usava questao.disciplina para o filtro, então está correto.
    public function getEvolucaoDisciplinaPorPeriodo(int $idAluno, string $disciplina, string $agrupamento = 'day'): array
    {
        $dateFormatSql = '';
        switch (strtolower($agrupamento)) {
            case 'day': $dateFormatSql = '%Y-%m-%d'; break;
            case 'week': $dateFormatSql = '%x-W%v'; break; 
            case 'month': default: $dateFormatSql = '%Y-%m'; break;
        }

        $sql = "
            SELECT
                DATE_FORMAT(r.dataResposta, '$dateFormatSql') AS periodoAgrupado,
                COUNT(r.idResposta) AS totalRespondidas,
                SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) AS totalCorretas
            FROM resposta r
            JOIN questao q ON r.idQuestao = q.idQuestao
            WHERE r.idAluno = ? AND q.disciplina = ?
            GROUP BY periodoAgrupado
            ORDER BY periodoAgrupado ASC";
        
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) { throw new Exception("Falha ao preparar query (getEvolucaoDisciplinaPorPeriodo): " . $this->conexao->error); }
        $stmt->bind_param("is", $idAluno, $disciplina);
        
        $evolucao = [];
        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            while ($linha = $resultado->fetch_assoc()) {
                $totalRespondidas = (int)$linha['totalRespondidas'];
                $totalCorretas = (int)$linha['totalCorretas'];
                $evolucao[] = [
                    'periodo' => $linha['periodoAgrupado'],
                    'percentualCorretas' => ($totalRespondidas > 0) ? round(($totalCorretas / $totalRespondidas) * 100, 2) : 0.0,
                    'totalRespondidas' => $totalRespondidas,
                    'totalCorretas' => $totalCorretas
                ];
            }
            $stmt->close();
        } else {
            $error = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (getEvolucaoDisciplinaPorPeriodo): " . $error);
        }
        return $evolucao;
    }

    // Este método já usava questao.disciplina para o filtro, então está correto.
    public function getEvolucaoDisciplinaPorProva(int $idAluno, string $disciplina): array
    {
        $sql = "SELECT 
                    p.idProva,
                    p.dataProva, 
                    p.nome AS nomeProva,
                    COUNT(r.idResposta) AS totalRespondidasNaDisciplina, 
                    SUM(CASE WHEN r.correta = 1 THEN 1 ELSE 0 END) AS totalCorretasNaDisciplina 
                FROM resposta r
                JOIN questao q ON r.idQuestao = q.idQuestao
                JOIN prova p ON q.idProva = p.idProva
                WHERE r.idAluno = ? AND q.disciplina = ?
                GROUP BY p.idProva, p.dataProva, p.nome
                HAVING COUNT(r.idResposta) > 0 
                ORDER BY p.dataProva ASC, p.idProva ASC";

        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) { throw new Exception("Falha ao preparar query (getEvolucaoDisciplinaPorProva): " . $this->conexao->error); }
        $stmt->bind_param("is", $idAluno, $disciplina);
        $desempenhoProvas = [];
        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            while ($linha = $resultado->fetch_assoc()) {
                $totalRespondidas = (int)$linha['totalRespondidasNaDisciplina'];
                $totalCorretas = (int)$linha['totalCorretasNaDisciplina'];
                $desempenhoProvas[] = [
                    'idProva' => (int)$linha['idProva'],
                    'dataReferencia' => $linha['dataProva'],
                    'rotuloEixoX' => $linha['nomeProva'] ? ($linha['nomeProva'] . " (" . date("d/m/y", strtotime($linha['dataProva'])) . ")") : ("Sessão #" . $linha['idProva'] . " (" . date("d/m/y", strtotime($linha['dataProva'])) . ")"),
                    'percentualCorretas' => ($totalRespondidas > 0) ? round(($totalCorretas / $totalRespondidas) * 100, 2) : 0.0,
                    'totalRespondidasNaDisciplina' => $totalRespondidas,
                    'totalCorretasNaDisciplina' => $totalCorretas
                ];
            }
            $stmt->close();
        } else {
            $erro = $stmt->error; $stmt->close(); throw new Exception("Falha ao executar query (getEvolucaoDisciplinaPorProva): " . $erro);
        }
        return $desempenhoProvas;
    }
    public function jaRespondeu($idAluno, $idQuestao) {
    $stmt = $this->conexao->prepare("SELECT COUNT(*) FROM resposta WHERE idAluno = ? AND idQuestao = ?");
    $stmt->bind_param("ii", $idAluno, $idQuestao);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return $total > 0;
}
}
?>