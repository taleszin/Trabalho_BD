DELIMITER $$

CREATE TRIGGER trg_log_questao_insert
AFTER INSERT ON questao
FOR EACH ROW
BEGIN
    DECLARE aluno_id INT;
    
    SELECT idAluno INTO aluno_id FROM prova WHERE idProva = NEW.idProva;
    
    IF aluno_id IS NULL THEN
        SET aluno_id = 1; 
    END IF;

    INSERT INTO log (acao, idAluno, detalhes)
    VALUES ('CREATE', aluno_id, CONCAT('Nova questão criada: ID #', NEW.idQuestao, ', Disciplina: ', NEW.disciplina));
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER trg_log_questao_update
AFTER UPDATE ON questao
FOR EACH ROW
BEGIN
    -- Só registra o log se o enunciado da questão foi realmente alterado.
    IF OLD.enunciado <> NEW.enunciado THEN
        INSERT INTO log (acao, idAluno, detalhes)
        VALUES ('UPDATE', 1, CONCAT('Questão ID #', NEW.idQuestao, ' foi atualizada.')); -- Assumindo que a atualização é feita por um admin (ID 1)
    END IF;
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER trg_log_questao_delete
AFTER DELETE ON questao
FOR EACH ROW
BEGIN
    INSERT INTO log (acao, idAluno, detalhes)
    VALUES ('DELETE', 1, CONCAT('Questão ID #', OLD.idQuestao, ' (Disciplina: ', OLD.disciplina, ') foi removida.')); -- Assumindo que a exclusão é feita por um admin (ID 1)
END$$

DELIMITER ;
