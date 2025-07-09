
CREATE TABLE instituicao (
  idInstituicao INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  status BOOLEAN NOT NULL DEFAULT TRUE,
  sigla VARCHAR(20) NULL,
  UF CHAR(2) NULL
);

CREATE TABLE aluno (
  idAluno INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL UNIQUE,
  nome VARCHAR(255) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  idInstituicao INT NULL,
  avatar_url VARCHAR(255) NULL,
  data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idInstituicao) REFERENCES instituicao(idInstituicao)
);

CREATE TABLE assunto (
  idAssunto INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  status BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE prova (
  idProva INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  dataProva DATE NOT NULL,
  idAluno INT NOT NULL,
  status BOOLEAN NOT NULL DEFAULT TRUE,
  descricao TEXT NULL,
  FOREIGN KEY (idAluno) REFERENCES aluno(idAluno)
);

CREATE TABLE questao (
  idQuestao INT PRIMARY KEY AUTO_INCREMENT,
  enunciado TEXT NOT NULL,
  tipo VARCHAR(20) NOT NULL,
  disciplina VARCHAR(255) NOT NULL,
  idProva INT NULL, -- Uma questão pode existir sem estar em uma prova
  comentario TEXT NULL,
  status BOOLEAN NOT NULL DEFAULT TRUE,
  FOREIGN KEY (idProva) REFERENCES prova(idProva) ON DELETE SET NULL
);

CREATE TABLE questaomultipla (
  idQuestao INT PRIMARY KEY,
  letraCorreta CHAR(1) NOT NULL,
  FOREIGN KEY (idQuestao) REFERENCES questao(idQuestao) ON DELETE CASCADE
);

CREATE TABLE alternativa (
  idQuestao INT NOT NULL,
  letra CHAR(1) NOT NULL,
  texto TEXT NOT NULL,
  correta BOOLEAN NOT NULL DEFAULT FALSE,
  feedback TEXT NULL,
  PRIMARY KEY (idQuestao, letra),
  FOREIGN KEY (idQuestao) REFERENCES questao(idQuestao) ON DELETE CASCADE
);

CREATE TABLE questaodissertativa (
  idQuestao INT PRIMARY KEY,
  modeloResposta TEXT NOT NULL,
  FOREIGN KEY (idQuestao) REFERENCES questao(idQuestao) ON DELETE CASCADE
);

CREATE TABLE resposta (
  idResposta INT PRIMARY KEY AUTO_INCREMENT,
  idAluno INT NOT NULL,
  idQuestao INT NOT NULL,
  dataResposta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  correta BOOLEAN NOT NULL,
  textoResposta TEXT NULL,
  letraResposta CHAR(1) NULL,
  FOREIGN KEY (idAluno) REFERENCES aluno(idAluno) ON DELETE CASCADE,
  FOREIGN KEY (idQuestao) REFERENCES questao(idQuestao) ON DELETE CASCADE
);


CREATE TABLE comentarios (
  idComentario INT PRIMARY KEY AUTO_INCREMENT,
  idQuestao INT NOT NULL,
  idAluno INT NOT NULL,
  texto TEXT NOT NULL,
  dataComentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status BOOLEAN NOT NULL DEFAULT TRUE,
  comentario_pai_id INT NULL, 
  FOREIGN KEY (idQuestao) REFERENCES questao(idQuestao) ON DELETE CASCADE,
  FOREIGN KEY (idAluno) REFERENCES aluno(idAluno) ON DELETE CASCADE,
  FOREIGN KEY (comentario_pai_id) REFERENCES comentarios(idComentario) ON DELETE CASCADE
);

CREATE TABLE tag (
    idTag INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL UNIQUE,
    icone VARCHAR(50) NULL, 
    cor VARCHAR(20) NULL 
);
-- Tabela do Relacionamento Ternário (Aluno x Questao x Tag)
CREATE TABLE aluno_questao_tag (
    idAluno INT NOT NULL,
    idQuestao INT NOT NULL,
    idTag INT NOT NULL,
    data_marcacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
    PRIMARY KEY (idAluno, idQuestao, idTag),
    FOREIGN KEY (idAluno) REFERENCES aluno(idAluno) ON DELETE CASCADE,
    FOREIGN KEY (idQuestao) REFERENCES questao(idQuestao) ON DELETE CASCADE,
    FOREIGN KEY (idTag) REFERENCES tag(idTag) ON DELETE CASCADE
);