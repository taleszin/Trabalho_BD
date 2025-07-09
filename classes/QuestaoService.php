<?php
include_once("LogService.php");

class QuestaoService {
    private $conexao;

    public function __construct($conexao) {
        $this->conexao = $conexao;
    }

    public function salvarProva($idAluno, $dataProva) {
        $sql = "INSERT INTO prova (idAluno, dataProva) VALUES (?, ?)";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro prepare salvarProva: " . $this->conexao->error);
        }
        $stmt->bind_param("is", $idAluno, $dataProva);

        if ($stmt->execute()) {
            $idProvaInserida = $this->conexao->insert_id;
            $logService = new LogService($this->conexao);
            $logService->logAction($idAluno, 'prova ID: ' . $idProvaInserida);
            $stmt->close();
            return $idProvaInserida;
        } else {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Erro execute salvarProva: " . $error);
        }
    }

    public function salvarQuestao($idProva, $enunciado, $tipo, $assuntos, $comentarioGeral, $subarea, $status = true) {
        $sqlVerifica = "SELECT * FROM questao WHERE Enunciado = ?";
        $stmtVerifica = $this->conexao->prepare($sqlVerifica);
        if (!$stmtVerifica) {
            throw new Exception("Erro prepare verificar duplicidade: " . $this->conexao->error);
        }
        $stmtVerifica->bind_param("s", $enunciado);
        $stmtVerifica->execute();
        $stmtVerifica->store_result();

        if ($stmtVerifica->num_rows > 0) {
            $stmtVerifica->close();
            return null;
        }
        $stmtVerifica->close();
        
        $sql = "INSERT INTO questao (idProva, Enunciado, tipo, assuntos, comentario, disciplina, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro prepare salvarQuestao: " . $this->conexao->error);
        }
        
        $statusInt = $status ? 1 : 0;
        $stmt->bind_param("isssssi", $idProva, $enunciado, $tipo, $assuntos, $comentarioGeral, $subarea, $statusInt);
    
        if ($stmt->execute()) {
            $idQuestaoInserida = $this->conexao->insert_id;
            $stmt->close();
            return $idQuestaoInserida;
        } else {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Erro execute salvarQuestao: " . $error);
        }
    }

    public function salvarAlternativas($idQuestao, $alternativasComFeedback, $letraCorreta) {
        $stmtDelete = $this->conexao->prepare("DELETE FROM alternativa WHERE idQuestao = ?");
        $stmtDelete->bind_param("i", $idQuestao);
        $stmtDelete->execute();
        $stmtDelete->close();
        
        $sql = "INSERT INTO alternativa (idQuestao, letra, texto, feedback, correta) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro prepare salvarAlternativas: " . $this->conexao->error);
        }

        if (!is_array($alternativasComFeedback)) {
            error_log("QuestaoService::salvarAlternativas - dados de alternativas inválidos para Questao ID: $idQuestao.");
            $stmt->close();
            return; 
        }

        foreach ($alternativasComFeedback as $letra => $dadosAlternativa) {
            if (!is_array($dadosAlternativa) || !isset($dadosAlternativa['texto'])) {
                error_log("QuestaoService::salvarAlternativas - Estrutura da alternativa '$letra' inválida para Questao ID: $idQuestao.");
                continue; 
            }

            $textoAlternativa = $dadosAlternativa['texto'];
            $feedbackAlternativa = $dadosAlternativa['feedback'] ?? null; 
            $corretaInt = ($letra === $letraCorreta) ? 1 : 0;

            $stmt->bind_param(
                "isssi", 
                $idQuestao,
                $letra,
                $textoAlternativa,
                $feedbackAlternativa,
                $corretaInt
            );

            if (!$stmt->execute()) {
                error_log("Erro ao salvar alternativa $letra para Questao ID $idQuestao: " . $stmt->error);
            }
        }
        $stmt->close();
    }
    
    public function getDisciplinas(): array {
        $sql = "SELECT DISTINCT disciplina FROM questao WHERE disciplina IS NOT NULL AND disciplina != '' ORDER BY disciplina ASC";
        $result = $this->conexao->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAssuntos(?string $disciplina = null): array {
        $sql = "SELECT DISTINCT assuntos FROM questao WHERE assuntos IS NOT NULL AND assuntos != ''";
        
        if ($disciplina && $disciplina !== '') {
            $sql .= " AND disciplina = ?";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bind_param('s', $disciplina);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->conexao->query($sql);
        }

        $todasAsLinhas = $result->fetch_all(MYSQLI_ASSOC);
        $assuntosUnicos = [];
        foreach ($todasAsLinhas as $linha) {
            $assuntosDaLinha = array_map('trim', explode(',', $linha['assuntos']));
            foreach ($assuntosDaLinha as $assunto) {
                if (!empty($assunto) && !in_array($assunto, $assuntosUnicos)) {
                    $assuntosUnicos[] = $assunto;
                }
            }
        }
        
        sort($assuntosUnicos);
        
        return $assuntosUnicos;
    }

    public function getTotalQuestoes(?string $disciplina = null, ?array $assuntos = null, ?string $buscaTexto = null): int {
        $sql = "SELECT COUNT(q.idQuestao) as total FROM questao q WHERE q.status = 1";
        
        $params = [];
        $types = '';

        if ($disciplina && $disciplina !== '') {
            $sql .= " AND q.disciplina = ?";
            $types .= 's';
            $params[] = $disciplina;
        }
        
        if ($assuntos && !empty(array_filter($assuntos))) {
            foreach (array_filter($assuntos) as $assunto) {
                $sql .= " AND FIND_IN_SET(?, REPLACE(q.assuntos, ', ', ','))";
                $types .= 's';
                $params[] = $assunto;
            }
        }

        if ($buscaTexto && $buscaTexto !== '') {
            $sql .= " AND LOWER(q.enunciado) LIKE ?";
            $types .= 's';
            $params[] = '%' . strtolower($buscaTexto) . '%';
        }
        
        $stmt = $this->conexao->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        return $total;
    }

    public function getQuestoes(?string $disciplina = null, ?array $assuntos = null, int $page = 1, int $perPage = 10, ?int $idAluno = null, ?string $buscaTexto = null, ?string $ordem = 'DESC'): array {
        $offset = ($page - 1) * $perPage;
        $ordemValida = in_array(strtoupper($ordem), ['ASC', 'DESC']) ? strtoupper($ordem) : 'DESC';

        $sql = "SELECT q.idQuestao, q.enunciado, q.disciplina, q.comentario, qm.letraCorreta,
                       GROUP_CONCAT(aqt.idTag) as aluno_tags
                FROM questao q
                LEFT JOIN questaomultipla qm ON q.idQuestao = qm.idQuestao
                LEFT JOIN aluno_questao_tag aqt ON q.idQuestao = aqt.idQuestao AND aqt.idAluno = ?";

        $params = [$idAluno];
        $types = 'i';
        $whereClauses = ["q.status = 1"];

        if ($disciplina && $disciplina !== '') {
            $whereClauses[] = "q.disciplina = ?";
            $types .= 's';
            $params[] = $disciplina;
        }
        
        if ($assuntos && !empty(array_filter($assuntos))) {
            foreach (array_filter($assuntos) as $assunto) {
                $whereClauses[] = "FIND_IN_SET(?, REPLACE(q.assuntos, ', ', ','))";
                $types .= 's';
                $params[] = $assunto;
            }
        }

        if ($buscaTexto && $buscaTexto !== '') {
            $whereClauses[] = "LOWER(q.enunciado) LIKE ?";
            $types .= 's';
            $params[] = '%' . strtolower($buscaTexto) . '%';
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $sql .= " GROUP BY q.idQuestao ORDER BY q.idQuestao $ordemValida LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getAlternativas(int $idQuestao): array {
        $sql = "SELECT letra, texto, correta, feedback FROM alternativa WHERE idQuestao = ? ORDER BY letra ASC";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param('i', $idQuestao);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function salvarComentario(int $idQuestao, int $idAluno, string $texto, ?int $comentarioPaiId = null): bool {
        $sql = "INSERT INTO comentarios (idQuestao, idAluno, texto, comentario_pai_id, status) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->conexao->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed para salvarComentario: " . $this->conexao->error);
            return false;
        }
        $stmt->bind_param("iisi", $idQuestao, $idAluno, $texto, $comentarioPaiId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Execute failed para salvarComentario: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function getComentarios(int $idQuestao): array {
        $sql = "SELECT c.idComentario, c.texto, c.dataComentario, c.comentario_pai_id, a.nome as nomeAluno
                FROM comentarios c
                JOIN aluno a ON c.idAluno = a.idAluno
                WHERE c.idQuestao = ? AND c.status = 1
                ORDER BY c.comentario_pai_id ASC, c.dataComentario ASC";
        
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param('i', $idQuestao);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $comentariosAninhados = [];
        $comentariosPorId = [];

        foreach ($result as $comentario) {
            $comentario['respostas'] = [];
            $comentariosPorId[$comentario['idComentario']] = $comentario;
        }

        foreach ($comentariosPorId as $id => &$comentario) {
            if ($comentario['comentario_pai_id'] !== null && isset($comentariosPorId[$comentario['comentario_pai_id']])) {
                $comentariosPorId[$comentario['comentario_pai_id']]['respostas'][] = &$comentario;
            }
        }
        unset($comentario);

        foreach ($comentariosPorId as $comentario) {
            if ($comentario['comentario_pai_id'] === null) {
                $comentariosAninhados[] = $comentario;
            }
        }

        return $comentariosAninhados;
    }

    public function getQuestaoById(int $idQuestao): ?array {
        $sql = "SELECT q.idQuestao, q.enunciado, q.disciplina, q.assuntos, q.comentario, q.tipo 
                FROM questao q 
                WHERE q.idQuestao = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param('i', $idQuestao);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
            $result['alternativas'] = $this->getAlternativas($idQuestao);
        }
        return $result;
    }

    public function updateQuestao(int $idQuestao, string $enunciado, string $tipo, string $assuntos, ?string $comentarioGeral, string $subarea) {
        $sql = "UPDATE questao SET enunciado = ?, tipo = ?, assuntos = ?, comentario = ?, disciplina = ? WHERE idQuestao = ?";
        $stmt = $this->conexao->prepare($sql);
        $stmt->bind_param("sssssi", $enunciado, $tipo, $assuntos, $comentarioGeral, $subarea, $idQuestao);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function deleteQuestao(int $idQuestao): bool {
        $this->conexao->begin_transaction();
        try {
            $this->conexao->query("DELETE FROM alternativa WHERE idQuestao = $idQuestao");
            $this->conexao->query("DELETE FROM resposta WHERE idQuestao = $idQuestao");
            $this->conexao->query("DELETE FROM comentarios WHERE idQuestao = $idQuestao");
            $this->conexao->query("DELETE FROM questaomultipla WHERE idQuestao = $idQuestao");
            $this->conexao->query("DELETE FROM questaodissertativa WHERE idQuestao = $idQuestao");
            $this->conexao->query("DELETE FROM aluno_questao_tag WHERE idQuestao = $idQuestao");

            $stmt = $this->conexao->prepare("DELETE FROM questao WHERE idQuestao = ?");
            $stmt->bind_param('i', $idQuestao);
            $stmt->execute();
            
            $this->conexao->commit();
            return true;
        } catch (Exception $e) {
            $this->conexao->rollback();
            error_log("Erro ao deletar questão: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableTags(): array {
        $sql = "SELECT idTag, nome, icone, cor FROM tag ORDER BY idTag ASC";
        $result = $this->conexao->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function toggleTag(int $idAluno, int $idQuestao, int $idTag): string {
        $sqlCheck = "SELECT COUNT(*) as count FROM aluno_questao_tag WHERE idAluno = ? AND idQuestao = ? AND idTag = ?";
        $stmtCheck = $this->conexao->prepare($sqlCheck);
        $stmtCheck->bind_param('iii', $idAluno, $idQuestao, $idTag);
        $stmtCheck->execute();
        $exists = $stmtCheck->get_result()->fetch_assoc()['count'] > 0;
        $stmtCheck->close();

        if ($exists) {
            $sqlDelete = "DELETE FROM aluno_questao_tag WHERE idAluno = ? AND idQuestao = ? AND idTag = ?";
            $stmtDelete = $this->conexao->prepare($sqlDelete);
            $stmtDelete->bind_param('iii', $idAluno, $idQuestao, $idTag);
            $stmtDelete->execute();
            $stmtDelete->close();
            return "removed";
        } else {
            $sqlInsert = "INSERT INTO aluno_questao_tag (idAluno, idQuestao, idTag) VALUES (?, ?, ?)";
            $stmtInsert = $this->conexao->prepare($sqlInsert);
            $stmtInsert->bind_param('iii', $idAluno, $idQuestao, $idTag);
            $stmtInsert->execute();
            $stmtInsert->close();
            return "added";
        }
    }
}
?>