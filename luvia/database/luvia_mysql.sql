-- =====================================================================
-- LUVIA (antigo ELiX) - Banco de dados MySQL 8
-- Plataforma de gestão da mentoria em comunicação profissional bilíngue
-- PI-2 · DSM · Fatec Itapira · 2º semestre de 2026
-- ---------------------------------------------------------------------
-- Escopo: uma única usuária, a mentora, que também é a administradora. Sistema
-- rodando localmente no computador dela.
--
-- Conteúdo do arquivo:
--   1. Banco e tabelas (15 tabelas)
--   2. Views (9)
--   3. Stored procedures (10) + função auxiliar fn_horario_nobre
--   4. Dados de exemplo fictícios: 3 cursos, 3 alunos (1 por curso),
--      agenda, cronograma, materiais, atividades e financeiro
--
-- Como executar (terminal):
--   mysql -u root -p < luvia_mysql.sql
-- Ou abra no MySQL Workbench e execute o script inteiro (raio ⚡).
-- O script apaga e recria o banco "luvia" a cada execução.
-- =====================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;  -- garante os acentos corretos ao importar

DROP DATABASE IF EXISTS luvia;
CREATE DATABASE luvia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luvia;

-- =====================================================================
-- 1. TABELAS
-- =====================================================================

-- USUARIOS: login do sistema. Existe um único perfil: a mentora, que
-- também é a administradora do sistema.
CREATE TABLE usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(160) NOT NULL UNIQUE,
    senha_hash  VARCHAR(255) NOT NULL,
    perfil      ENUM('mentora_admin') NOT NULL DEFAULT 'mentora_admin',
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- LOGS_ACESSO: auditoria de login e ações importantes (RF03).
CREATE TABLE logs_acesso (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NULL,
    acao        VARCHAR(80)  NOT NULL,          -- ex.: 'login', 'logout', 'cancelou_aula'
    detalhe     VARCHAR(255) NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- CURSOS: modelos de curso oferecidos pela mentora (RF18).
CREATE TABLE cursos (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    nome                   VARCHAR(120) NOT NULL UNIQUE,
    modalidade             ENUM('online','gravada','hibrida') NOT NULL,
    aulas_online_semana    TINYINT NOT NULL DEFAULT 0,   -- encontros ao vivo por semana
    aulas_gravadas_semana  TINYINT NOT NULL DEFAULT 0,   -- videoaulas liberadas por semana
    duracao_semanas        TINYINT NOT NULL DEFAULT 12,
    descricao              VARCHAR(500) NULL,
    ativo                  TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT ck_curso_aulas CHECK (aulas_online_semana + aulas_gravadas_semana > 0)
) ENGINE=InnoDB;

-- CURSO_MODULOS: fases do curso, seguindo os pilares do método (Cenário,
-- Essência, Nicho, Atuação, Ensaio geral e Estreia).
CREATE TABLE curso_modulos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    curso_id      INT NOT NULL,
    ordem         TINYINT NOT NULL,
    titulo        VARCHAR(160) NOT NULL,
    pilar         VARCHAR(40)  NOT NULL,
    semana_inicio TINYINT NOT NULL,
    semana_fim    TINYINT NOT NULL,
    objetivo      VARCHAR(255) NULL,
    CONSTRAINT fk_modulo_curso FOREIGN KEY (curso_id) REFERENCES cursos(id),
    CONSTRAINT uq_modulo UNIQUE (curso_id, ordem)
) ENGINE=InnoDB;

-- CURSO_AULAS_MODELO: o que se trabalha em cada semana do curso (lista do curso).
CREATE TABLE curso_aulas_modelo (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    curso_id    INT NOT NULL,
    modulo_id   INT NOT NULL,
    semana      TINYINT NOT NULL,
    titulo      VARCHAR(160) NOT NULL,
    estrutura   VARCHAR(160) NULL,          -- estrutura-base da semana (ex.: I have to…)
    tarefa      VARCHAR(255) NULL,          -- tarefa entre encontros
    CONSTRAINT fk_aulamod_curso  FOREIGN KEY (curso_id)  REFERENCES cursos(id),
    CONSTRAINT fk_aulamod_modulo FOREIGN KEY (modulo_id) REFERENCES curso_modulos(id),
    CONSTRAINT uq_aulamod UNIQUE (curso_id, semana)
) ENGINE=InnoDB;

-- ALUNOS: cadastro dos alunos da mentora. Os alunos não fazem login no sistema.
CREATE TABLE alunos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(120) NOT NULL,
    email            VARCHAR(160) NULL,
    telefone         VARCHAR(30)  NULL,
    nicho            VARCHAR(80)  NULL,          -- ex.: 'Advocacia', 'Tecnologia (TI)'
    data_inicio      DATE         NOT NULL DEFAULT (CURRENT_DATE),
    contrato_link    VARCHAR(500) NULL,
    ativo            TINYINT(1)   NOT NULL DEFAULT 1,
    data_inativacao  DATE         NULL,
    motivo_inativacao VARCHAR(255) NULL,
    curso_id         INT NULL,                   -- curso contratado
    data_fim_prevista DATE        NULL,
    nivel_inicial    ENUM('pessoal','social','profissional','leadership','expand') NULL,
    nivel_alvo       ENUM('pessoal','social','profissional','leadership','expand') NULL,
    cena_objetivo    VARCHAR(255) NULL,          -- Mapa da Cena: o compromisso real do aluno
    data_cena        DATE         NULL,          -- data prevista desse compromisso
    criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aluno_curso FOREIGN KEY (curso_id) REFERENCES cursos(id)
) ENGINE=InnoDB;

-- LEADS: possíveis alunos e funil de acompanhamento (RF05).
CREATE TABLE leads (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(160) NULL,
    telefone    VARCHAR(30)  NULL,
    origem      VARCHAR(60)  NULL,               -- ex.: 'Instagram', 'Indicação', 'LinkedIn'
    nicho       VARCHAR(80)  NULL,
    status      ENUM('novo','em_contato','convertido','desistiu') NOT NULL DEFAULT 'novo',
    observacao  VARCHAR(500) NULL,
    aluno_id    INT NULL,                        -- preenchido quando o lead vira aluno
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lead_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

-- MATERIAIS: e-books, PDFs e outros. aluno_id NULL = material geral.
CREATE TABLE materiais (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id    INT NULL,
    titulo      VARCHAR(160) NOT NULL,
    tipo        VARCHAR(30)  NOT NULL DEFAULT 'PDF',
    link        VARCHAR(500) NOT NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

-- AULAS_GRAVADAS: links das gravações. aluno_id NULL = gravação geral.
CREATE TABLE aulas_gravadas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id    INT NULL,
    titulo      VARCHAR(160) NOT NULL,
    link        VARCHAR(500) NOT NULL,
    data_aula   DATE         NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_gravacao_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

-- ETAPAS_CURSO: currículo montado individualmente para cada aluno.
CREATE TABLE etapas_curso (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT NOT NULL,
    titulo          VARCHAR(160) NOT NULL,
    ordem           INT NOT NULL DEFAULT 0,
    semana          TINYINT NULL,               -- semana do curso (1 a 12)
    modulo          VARCHAR(160) NULL,          -- fase do curso de onde a etapa veio
    data_prevista   DATE NULL,                  -- início previsto da semana
    status          ENUM('pendente','concluida') NOT NULL DEFAULT 'pendente',
    data_conclusao  DATE NULL,
    CONSTRAINT fk_etapa_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

-- AGENDAMENTOS: aulas e entrevistas. Uma entrevista pode ser com um lead
-- (backlog US02: "o agendamento fica vinculado a um aluno ou lead").
CREATE TABLE agendamentos (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    tipo                   ENUM('aula','entrevista','reposicao') NOT NULL DEFAULT 'aula',
    aluno_id               INT NULL,
    lead_id                INT NULL,
    data_aula              DATE NOT NULL,
    hora_inicio            TIME NOT NULL,
    hora_fim               TIME NOT NULL,
    horario_nobre          TINYINT(1) NOT NULL DEFAULT 0,
    status                 ENUM('agendada','realizada','cancelada') NOT NULL DEFAULT 'agendada',
    motivo_cancelamento    VARCHAR(255) NULL,
    cancelado_em           DATETIME NULL,
    agendamento_origem_id  INT NULL,             -- se for reposição: a aula que foi perdida
    observacao             VARCHAR(500) NULL,
    criado_em              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ag_aluno  FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    CONSTRAINT fk_ag_lead   FOREIGN KEY (lead_id)  REFERENCES leads(id),
    CONSTRAINT fk_ag_origem FOREIGN KEY (agendamento_origem_id) REFERENCES agendamentos(id),
    CONSTRAINT ck_ag_pessoa CHECK (aluno_id IS NOT NULL OR lead_id IS NOT NULL),
    CONSTRAINT ck_ag_horas  CHECK (hora_fim > hora_inicio),
    INDEX idx_ag_data (data_aula, hora_inicio)
) ENGINE=InnoDB;

-- FREQUENCIA: presença ou falta de cada aula realizada.
CREATE TABLE frequencia (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    agendamento_id            INT NOT NULL UNIQUE,
    aluno_id                  INT NOT NULL,
    presente                  TINYINT(1) NOT NULL DEFAULT 1,
    reposta                   TINYINT(1) NOT NULL DEFAULT 0,
    reposicao_agendamento_id  INT NULL,
    registrado_em             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_freq_ag     FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id),
    CONSTRAINT fk_freq_aluno  FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    CONSTRAINT fk_freq_repos  FOREIGN KEY (reposicao_agendamento_id) REFERENCES agendamentos(id)
) ENGINE=InnoDB;

-- ATIVIDADES: tarefas propostas ao aluno.
CREATE TABLE atividades (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id      INT NOT NULL,
    titulo        VARCHAR(160) NOT NULL,
    descricao     VARCHAR(500) NULL,
    data_entrega  DATE NULL,
    status        ENUM('pendente','entregue','atrasada') NOT NULL DEFAULT 'pendente',
    CONSTRAINT fk_ativ_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

-- FINANCEIRO: parcelas e pagamentos de cada aluno.
CREATE TABLE financeiro (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT NOT NULL,
    descricao       VARCHAR(120) NOT NULL DEFAULT 'Mensalidade',
    valor           DECIMAL(10,2) NOT NULL,
    vencimento      DATE NOT NULL,
    status          ENUM('pendente','pago','atrasado') NOT NULL DEFAULT 'pendente',
    data_pagamento  DATE NULL,
    forma_pagamento VARCHAR(30) NULL,            -- ex.: 'Pix', 'Transferência'
    CONSTRAINT fk_fin_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    CONSTRAINT ck_fin_valor CHECK (valor >= 0)
) ENGINE=InnoDB;

-- DIARIO_BORDO: anotações da mentora sobre cada aluno (RF17).
CREATE TABLE diario_bordo (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id       INT NOT NULL,
    data_registro  DATE NOT NULL DEFAULT (CURRENT_DATE),
    anotacao       TEXT NOT NULL,
    CONSTRAINT fk_diario_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id)
) ENGINE=InnoDB;

-- Observação: não usamos ON DELETE CASCADE nos dados do aluno. O aluno
-- é INATIVADO (sp_inativar_aluno), nunca apagado, para preservar o histórico.


-- =====================================================================
-- 2. VIEWS
-- =====================================================================

-- Próximas aulas e entrevistas (Dashboard e Agenda).
CREATE VIEW vw_proximas_aulas AS
SELECT  ag.id              AS agendamento_id,
        ag.tipo,
        COALESCE(a.nome, l.nome) AS pessoa,
        CASE WHEN ag.aluno_id IS NOT NULL THEN 'aluno' ELSE 'lead' END AS vinculo,
        ag.data_aula,
        ag.hora_inicio,
        ag.hora_fim,
        ag.horario_nobre,
        ag.observacao
FROM agendamentos ag
LEFT JOIN alunos a ON a.id = ag.aluno_id
LEFT JOIN leads  l ON l.id = ag.lead_id
WHERE ag.status = 'agendada'
  AND ag.data_aula >= CURRENT_DATE
ORDER BY ag.data_aula, ag.hora_inicio;

-- Frequência por aluno (Detalhe do aluno).
CREATE VIEW vw_frequencia_aluno AS
SELECT  a.id   AS aluno_id,
        a.nome AS aluno,
        COUNT(f.id)                                   AS aulas_registradas,
        COALESCE(SUM(f.presente = 1), 0)              AS presencas,
        COALESCE(SUM(f.presente = 0), 0)              AS faltas,
        COALESCE(SUM(f.presente = 0 AND f.reposta = 1), 0) AS faltas_repostas,
        ROUND(100 * COALESCE(SUM(f.presente = 1), 0) / NULLIF(COUNT(f.id), 0), 1) AS percentual_frequencia
FROM alunos a
LEFT JOIN frequencia f ON f.aluno_id = a.id
WHERE a.ativo = 1
GROUP BY a.id, a.nome;

-- Progresso do curso por aluno (Detalhe do aluno e Dashboard).
CREATE VIEW vw_progresso_aluno AS
SELECT  a.id   AS aluno_id,
        a.nome AS aluno,
        COUNT(e.id)                          AS total_etapas,
        COALESCE(SUM(e.status = 'concluida'), 0) AS etapas_concluidas,
        ROUND(100 * COALESCE(SUM(e.status = 'concluida'), 0) / NULLIF(COUNT(e.id), 0), 1) AS percentual_progresso
FROM alunos a
LEFT JOIN etapas_curso e ON e.aluno_id = a.id
WHERE a.ativo = 1
GROUP BY a.id, a.nome;

-- Parcelas pendentes e atrasadas (Financeiro e Dashboard).
CREATE VIEW vw_financeiro_pendencias AS
SELECT  fi.id   AS financeiro_id,
        a.id    AS aluno_id,
        a.nome  AS aluno,
        fi.descricao,
        fi.valor,
        fi.vencimento,
        fi.status,
        GREATEST(DATEDIFF(CURRENT_DATE, fi.vencimento), 0) AS dias_atraso
FROM financeiro fi
JOIN alunos a ON a.id = fi.aluno_id
WHERE fi.status IN ('pendente','atrasado')
ORDER BY fi.vencimento;

-- Atividades pendentes ou atrasadas (Detalhe do aluno).
CREATE VIEW vw_atividades_pendentes AS
SELECT  at.id   AS atividade_id,
        a.id    AS aluno_id,
        a.nome  AS aluno,
        at.titulo,
        at.data_entrega,
        at.status
FROM atividades at
JOIN alunos a ON a.id = at.aluno_id
WHERE at.status IN ('pendente','atrasada')
  AND a.ativo = 1
ORDER BY at.data_entrega;

-- Funil de leads (tela Leads).
CREATE VIEW vw_funil_leads AS
SELECT  s.status,
        COUNT(l.id) AS quantidade
FROM (SELECT 'novo' AS status, 1 AS ordem UNION ALL
      SELECT 'em_contato', 2 UNION ALL
      SELECT 'convertido', 3 UNION ALL
      SELECT 'desistiu',   4) s
LEFT JOIN leads l ON l.status = s.status
GROUP BY s.status, s.ordem
ORDER BY s.ordem;

-- Resumo da operação (Dashboard). Uma linha só.
CREATE VIEW vw_resumo_dashboard AS
SELECT
    (SELECT COUNT(*) FROM alunos WHERE ativo = 1) AS alunos_ativos,
    (SELECT COUNT(*) FROM agendamentos
      WHERE status = 'agendada'
        AND YEARWEEK(data_aula, 1) = YEARWEEK(CURRENT_DATE, 1)) AS aulas_semana,
    (SELECT COALESCE(SUM(valor), 0) FROM financeiro WHERE status IN ('pendente','atrasado')) AS valor_a_receber,
    (SELECT COUNT(*) FROM financeiro WHERE status = 'atrasado') AS parcelas_atrasadas,
    (SELECT COUNT(*) FROM atividades WHERE status = 'atrasada') AS atividades_atrasadas,
    (SELECT COUNT(*) FROM leads WHERE status IN ('novo','em_contato')) AS leads_em_aberto;


-- Cronograma de cada aluno: semanas do curso, datas e situação (RF19, RF21).
CREATE VIEW vw_cronograma_aluno AS
SELECT  a.id    AS aluno_id,
        a.nome  AS aluno,
        c.nome  AS curso,
        e.semana,
        e.modulo,
        e.titulo,
        e.data_prevista                        AS inicio_semana,
        e.data_prevista + INTERVAL 6 DAY       AS fim_semana,
        e.status,
        CASE
          WHEN e.status = 'concluida' THEN 'concluída'
          WHEN CURRENT_DATE BETWEEN e.data_prevista AND e.data_prevista + INTERVAL 6 DAY THEN 'semana atual'
          WHEN e.data_prevista < CURRENT_DATE THEN 'atrasada'
          ELSE 'prevista'
        END AS situacao,
        (SELECT COUNT(*) FROM agendamentos ag
          WHERE ag.aluno_id = a.id AND ag.status <> 'cancelada'
            AND ag.data_aula BETWEEN e.data_prevista AND e.data_prevista + INTERVAL 6 DAY) AS aulas_ao_vivo_na_semana
FROM etapas_curso e
JOIN alunos a      ON a.id = e.aluno_id
LEFT JOIN cursos c ON c.id = a.curso_id
WHERE a.ativo = 1
ORDER BY a.nome, e.semana;

-- Cursos com quantidade de alunos ativos (tela Cursos).
CREATE VIEW vw_cursos_resumo AS
SELECT  c.id   AS curso_id,
        c.nome,
        c.modalidade,
        c.aulas_online_semana,
        c.aulas_gravadas_semana,
        c.duracao_semanas,
        (SELECT COUNT(*) FROM curso_modulos m WHERE m.curso_id = c.id) AS modulos,
        (SELECT COUNT(*) FROM alunos a WHERE a.curso_id = c.id AND a.ativo = 1) AS alunos_ativos
FROM cursos c
WHERE c.ativo = 1
ORDER BY c.id;

-- =====================================================================
-- 3. FUNÇÃO AUXILIAR E STORED PROCEDURES
-- =====================================================================
DELIMITER $$

-- Regra do horário nobre (igual à função is_horario_nobre do protótipo):
-- qualquer horário aos sábados OU antes das 08:00 ou a partir das 20:00.
CREATE FUNCTION fn_horario_nobre(p_data DATE, p_hora TIME)
RETURNS TINYINT
DETERMINISTIC
BEGIN
    IF DAYOFWEEK(p_data) = 7 THEN          -- 1 = domingo ... 7 = sábado
        RETURN 1;
    END IF;
    IF p_hora < '08:00:00' OR p_hora >= '20:00:00' THEN
        RETURN 1;
    END IF;
    RETURN 0;
END$$

-- Agenda uma aula/entrevista: confere conflito de horário, calcula o
-- horário nobre e devolve o id criado.
CREATE PROCEDURE sp_agendar_aula(
    IN  p_aluno_id    INT,
    IN  p_lead_id     INT,
    IN  p_tipo        VARCHAR(20),
    IN  p_data        DATE,
    IN  p_hora_inicio TIME,
    IN  p_hora_fim    TIME,
    IN  p_observacao  VARCHAR(500),
    OUT p_agendamento_id INT
)
BEGIN
    IF p_aluno_id IS NULL AND p_lead_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Informe um aluno ou um lead.';
    END IF;
    IF p_hora_fim <= p_hora_inicio THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A hora final deve ser maior que a inicial.';
    END IF;
    IF p_aluno_id IS NOT NULL AND
       (SELECT ativo FROM alunos WHERE id = p_aluno_id) IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aluno não encontrado.';
    END IF;
    IF p_aluno_id IS NOT NULL AND
       (SELECT ativo FROM alunos WHERE id = p_aluno_id) = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aluno inativo: não é possível agendar.';
    END IF;
    -- Conflito: outro compromisso ativo que se sobrepõe ao intervalo
    IF EXISTS (SELECT 1 FROM agendamentos
               WHERE data_aula = p_data
                 AND status = 'agendada'
                 AND p_hora_inicio < hora_fim
                 AND p_hora_fim   > hora_inicio) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Conflito de horário com outro agendamento.';
    END IF;

    INSERT INTO agendamentos (tipo, aluno_id, lead_id, data_aula, hora_inicio, hora_fim,
                              horario_nobre, observacao)
    VALUES (COALESCE(p_tipo, 'aula'), p_aluno_id, p_lead_id, p_data, p_hora_inicio, p_hora_fim,
            fn_horario_nobre(p_data, p_hora_inicio), p_observacao);

    SET p_agendamento_id = LAST_INSERT_ID();
END$$

-- Cancela com motivo e data (cancelamento rastreável - RF08).
CREATE PROCEDURE sp_cancelar_aula(
    IN p_agendamento_id INT,
    IN p_motivo         VARCHAR(255)
)
BEGIN
    DECLARE v_status VARCHAR(20);
    SELECT status INTO v_status FROM agendamentos WHERE id = p_agendamento_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Agendamento não encontrado.';
    END IF;
    IF v_status <> 'agendada' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Só é possível cancelar um agendamento com status agendada.';
    END IF;
    IF p_motivo IS NULL OR TRIM(p_motivo) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Informe o motivo do cancelamento.';
    END IF;

    UPDATE agendamentos
       SET status = 'cancelada',
           motivo_cancelamento = p_motivo,
           cancelado_em = NOW()
     WHERE id = p_agendamento_id;

    INSERT INTO logs_acesso (acao, detalhe)
    VALUES ('cancelou_aula', CONCAT('Agendamento ', p_agendamento_id, ': ', p_motivo));
END$$

-- Registra presença (1) ou falta (0) e marca a aula como realizada.
CREATE PROCEDURE sp_registrar_frequencia(
    IN p_agendamento_id INT,
    IN p_presente       TINYINT
)
BEGIN
    DECLARE v_aluno  INT;
    DECLARE v_status VARCHAR(20);

    SELECT aluno_id, status INTO v_aluno, v_status
      FROM agendamentos WHERE id = p_agendamento_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Agendamento não encontrado.';
    END IF;
    IF v_aluno IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Frequência só se aplica a agendamentos de aluno.';
    END IF;
    IF v_status = 'cancelada' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aula cancelada não recebe frequência.';
    END IF;

    -- Se já existir registro, atualiza; senão, cria.
    INSERT INTO frequencia (agendamento_id, aluno_id, presente)
    VALUES (p_agendamento_id, v_aluno, p_presente)
    ON DUPLICATE KEY UPDATE presente = VALUES(presente), registrado_em = NOW();

    UPDATE agendamentos SET status = 'realizada' WHERE id = p_agendamento_id;

    -- Se esta aula é uma reposição e o aluno compareceu, a falta original fica reposta.
    IF p_presente = 1 THEN
        UPDATE frequencia f
          JOIN agendamentos ag ON ag.id = p_agendamento_id
           SET f.reposta = 1
         WHERE f.agendamento_id = ag.agendamento_origem_id;
    END IF;
END$$

-- Agenda a reposição de uma falta e liga a nova aula à falta original.
CREATE PROCEDURE sp_registrar_reposicao(
    IN  p_frequencia_id INT,
    IN  p_data          DATE,
    IN  p_hora_inicio   TIME,
    IN  p_hora_fim      TIME,
    OUT p_agendamento_id INT
)
BEGIN
    DECLARE v_aluno    INT;
    DECLARE v_presente TINYINT;
    DECLARE v_origem   INT;
    DECLARE v_repos    INT;

    SELECT aluno_id, presente, agendamento_id, reposicao_agendamento_id
      INTO v_aluno, v_presente, v_origem, v_repos
      FROM frequencia WHERE id = p_frequencia_id;

    IF v_aluno IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Registro de frequência não encontrado.';
    END IF;
    IF v_presente = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Só faltas podem ter reposição.';
    END IF;
    IF v_repos IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Esta falta já tem uma reposição agendada.';
    END IF;

    CALL sp_agendar_aula(v_aluno, NULL, 'reposicao', p_data, p_hora_inicio, p_hora_fim,
                         'Reposição de falta', p_agendamento_id);

    UPDATE agendamentos SET agendamento_origem_id = v_origem WHERE id = p_agendamento_id;
    UPDATE frequencia   SET reposicao_agendamento_id = p_agendamento_id WHERE id = p_frequencia_id;
END$$

-- Conclui uma etapa do curso.
CREATE PROCEDURE sp_concluir_etapa(IN p_etapa_id INT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM etapas_curso WHERE id = p_etapa_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etapa não encontrada.';
    END IF;
    UPDATE etapas_curso
       SET status = 'concluida', data_conclusao = CURRENT_DATE
     WHERE id = p_etapa_id;
END$$

-- Dá baixa em uma parcela (RF16).
CREATE PROCEDURE sp_registrar_pagamento(
    IN p_financeiro_id   INT,
    IN p_data_pagamento  DATE,
    IN p_forma_pagamento VARCHAR(30)
)
BEGIN
    DECLARE v_status VARCHAR(20);
    SELECT status INTO v_status FROM financeiro WHERE id = p_financeiro_id;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Parcela não encontrada.';
    END IF;
    IF v_status = 'pago' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Esta parcela já está paga.';
    END IF;

    UPDATE financeiro
       SET status = 'pago',
           data_pagamento = COALESCE(p_data_pagamento, CURRENT_DATE),
           forma_pagamento = p_forma_pagamento
     WHERE id = p_financeiro_id;
END$$

-- Marca parcelas vencidas e atividades com prazo vencido como atrasadas.
-- O sistema chama esta procedure ao abrir o Dashboard.
CREATE PROCEDURE sp_atualizar_atrasos()
BEGIN
    UPDATE financeiro
       SET status = 'atrasado'
     WHERE status = 'pendente' AND vencimento < CURRENT_DATE;

    UPDATE atividades
       SET status = 'atrasada'
     WHERE status = 'pendente' AND data_entrega IS NOT NULL AND data_entrega < CURRENT_DATE;
END$$

-- Converte um lead em aluno (cria o aluno e marca o lead como convertido).
CREATE PROCEDURE sp_converter_lead(
    IN  p_lead_id  INT,
    OUT p_aluno_id INT
)
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SELECT status INTO v_status FROM leads WHERE id = p_lead_id;
    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lead não encontrado.';
    END IF;
    IF v_status = 'convertido' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este lead já foi convertido.';
    END IF;

    START TRANSACTION;
        INSERT INTO alunos (nome, email, telefone, nicho)
        SELECT nome, email, telefone, nicho FROM leads WHERE id = p_lead_id;
        SET p_aluno_id = LAST_INSERT_ID();

        UPDATE leads SET status = 'convertido', aluno_id = p_aluno_id WHERE id = p_lead_id;
    COMMIT;
END$$

-- Inativa o aluno sem apagar o histórico e cancela as aulas futuras.
CREATE PROCEDURE sp_inativar_aluno(
    IN p_aluno_id INT,
    IN p_motivo   VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM alunos WHERE id = p_aluno_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aluno não encontrado.';
    END IF;

    UPDATE alunos
       SET ativo = 0, data_inativacao = CURRENT_DATE, motivo_inativacao = p_motivo
     WHERE id = p_aluno_id;

    UPDATE agendamentos
       SET status = 'cancelada',
           motivo_cancelamento = 'Aluno inativado',
           cancelado_em = NOW()
     WHERE aluno_id = p_aluno_id
       AND status = 'agendada'
       AND data_aula >= CURRENT_DATE;
END$$

-- Aplica um curso ao aluno: copia as aulas-modelo para as etapas do aluno,
-- com a data prevista de cada semana (RF19). Depois de aplicadas, as etapas
-- são do aluno e podem ser personalizadas; mudar o modelo não as altera (RN11).
CREATE PROCEDURE sp_aplicar_curso(
    IN p_aluno_id    INT,
    IN p_curso_id    INT,
    IN p_data_inicio DATE
)
BEGIN
    DECLARE v_semanas TINYINT;
    IF NOT EXISTS (SELECT 1 FROM alunos WHERE id = p_aluno_id AND ativo = 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Aluno não encontrado ou inativo.';
    END IF;
    SELECT duracao_semanas INTO v_semanas FROM cursos WHERE id = p_curso_id AND ativo = 1;
    IF v_semanas IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Curso não encontrado ou inativo.';
    END IF;
    IF EXISTS (SELECT 1 FROM etapas_curso WHERE aluno_id = p_aluno_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este aluno já tem um curso montado.';
    END IF;

    UPDATE alunos
       SET curso_id = p_curso_id,
           data_inicio = p_data_inicio,
           data_fim_prevista = p_data_inicio + INTERVAL (v_semanas * 7 - 1) DAY
     WHERE id = p_aluno_id;

    INSERT INTO etapas_curso (aluno_id, titulo, ordem, semana, modulo, data_prevista)
    SELECT p_aluno_id, am.titulo, am.semana, am.semana, m.titulo,
           p_data_inicio + INTERVAL ((am.semana - 1) * 7) DAY
      FROM curso_aulas_modelo am
      JOIN curso_modulos m ON m.id = am.modulo_id
     WHERE am.curso_id = p_curso_id
     ORDER BY am.semana;
END$$

DELIMITER ;


-- =====================================================================
-- 4. DADOS DE EXEMPLO (fictícios)
-- ---------------------------------------------------------------------
-- Todos os nomes, e-mails, links e valores abaixo são FICTÍCIOS, só para
-- testar o sistema. Não há dados reais de alunos. As datas são calculadas a
-- partir da data de hoje, então a agenda e o cronograma estão sempre atuais.
-- =====================================================================

-- Usuária única. O hash da senha é gerado pela aplicação (password_hash do
-- PHP) no script de instalação; troque o valor abaixo no primeiro acesso.
INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
('Mentora (teste)', 'mentora@luvia.local', 'TROCAR_PELO_HASH_GERADO_PELA_APLICACAO', 'mentora_admin');

-- ---------------------------------------------------------------------
-- 4.1 CURSOS (12 semanas cada)
-- ---------------------------------------------------------------------
INSERT INTO cursos (nome, modalidade, aulas_online_semana, aulas_gravadas_semana, duracao_semanas, descricao) VALUES
('Mentoria VIP Online', 'online', 5, 0, 12,
 'Cinco encontros ao vivo por semana, só com o aluno, construídos sobre a cena real e o vocabulário do nicho dele.'),
('Mentoria VIP Aulas Gravadas', 'gravada', 0, 3, 12,
 'Três videoaulas por semana na plataforma, com tarefas de fala em voz alta e gravação diária; encontros ao vivo pontuais de acompanhamento.'),
('Mentoria Híbrida (Gravada e Online)', 'hibrida', 1, 2, 12,
 'Um encontro ao vivo por semana para ensaiar a cena do aluno e duas videoaulas gravadas para preparar o encontro.');

-- ---------------------------------------------------------------------
-- 4.2 MÓDULOS (as fases do método, iguais nos três cursos)
-- ---------------------------------------------------------------------
INSERT INTO curso_modulos (curso_id, ordem, titulo, pilar, semana_inicio, semana_fim, objetivo)
SELECT c.id, m.ordem, m.titulo, m.pilar, m.ini, m.fim, m.objetivo
FROM cursos c
CROSS JOIN (
  SELECT 1 AS ordem, 'Módulo 1 · Cenário' AS titulo, 'Cenário' AS pilar, 1 AS ini, 1 AS fim,
         'Mapear a cena real do aluno (compromisso, data, quem decide) e apresentar-se.' AS objetivo
  UNION ALL SELECT 2, 'Módulo 2 · Essência', 'Essência', 2, 3,
         'Organizar a ideia em português antes de traduzir; pitch com voz de comando.'
  UNION ALL SELECT 3, 'Módulo 3 · Nicho', 'Nicho', 4, 6,
         'Vocabulário de dentro: o próprio trabalho, a rotina e as perguntas da área.'
  UNION ALL SELECT 4, 'Módulo 4 · Atuação', 'Atuação', 7, 10,
         'Ensaiar a cena real com variações: trajetória, reunião, negociação e imprevistos.'
  UNION ALL SELECT 5, 'Módulo 5 · Ensaio geral', 'Corpo, Voz e Emoção', 11, 11,
         'Ensaio completo sob pressão, com atenção a postura, voz e segurança.'
  UNION ALL SELECT 6, 'Módulo 6 · Estreia', 'Estreia', 12, 12,
         'Simulação final completa e plano para depois da data.'
) m;

-- ---------------------------------------------------------------------
-- 4.3 LISTA DO CURSO: o que se trabalha em cada semana
-- A tarefa muda conforme a modalidade (ao vivo, gravada ou híbrida).
-- ---------------------------------------------------------------------
INSERT INTO curso_aulas_modelo (curso_id, modulo_id, semana, titulo, estrutura, tarefa)
SELECT c.id, m.id, w.semana, w.titulo, w.estrutura,
       CONCAT(
         CASE c.modalidade
           WHEN 'online'  THEN '5 encontros ao vivo. '
           WHEN 'gravada' THEN '3 videoaulas na plataforma, falando em voz alta nas pausas. '
           ELSE '2 videoaulas para preparar + 1 encontro ao vivo para ensaiar. '
         END,
         'Gravação diária de 20 a 30 s, de uma vez só, com o tema da semana e algo a mais.')
FROM cursos c
CROSS JOIN (
  SELECT 1 AS semana, 'Mapa da Cena e apresentação pessoal e profissional' AS titulo, 'I am… / I''m from… / I work as…' AS estrutura
  UNION ALL SELECT 2,  'Apresentar outra pessoa e a própria equipe', 'He is… / She is… / They are…'
  UNION ALL SELECT 3,  'Pitch: o problema que resolvo, como resolvo e uma prova', 'I help… to… / Last month…'
  UNION ALL SELECT 4,  'Rotina e obrigações de trabalho', 'I have to… / I need to… because…'
  UNION ALL SELECT 5,  'Vocabulário de dentro: o meu trabalho', 'Cognatos: -tion, -ty, -ly, -ence'
  UNION ALL SELECT 6,  'Perguntas e respostas da minha área', 'Do you…? / Does he…?'
  UNION ALL SELECT 7,  'Contar a trajetória profissional', 'Passado com did; histórias em blocos de três'
  UNION ALL SELECT 8,  'Reunião: propor, concordar e discordar', 'I''d suggest… / We should… / We could…'
  UNION ALL SELECT 9,  'Negociar e apresentar um projeto', 'will / may / might (grau de certeza)'
  UNION ALL SELECT 10, 'Perguntas inesperadas', 'Rephrase it / Could you repeat…?'
  UNION ALL SELECT 11, 'Ensaio geral sob pressão', 'A cena inteira, do começo ao fim'
  UNION ALL SELECT 12, 'Estreia: simulação final e plano para depois da data', 'A cena inteira, com perguntas inesperadas'
) w
JOIN curso_modulos m ON m.curso_id = c.id AND w.semana BETWEEN m.semana_inicio AND m.semana_fim;

-- ---------------------------------------------------------------------
-- 4.4 ALUNOS FICTÍCIOS (um em cada curso)
-- Início sempre numa segunda-feira, algumas semanas atrás.
-- ---------------------------------------------------------------------
SET @seg_atual = CURRENT_DATE - INTERVAL WEEKDAY(CURRENT_DATE) DAY;   -- segunda desta semana
SET @ini_a = @seg_atual - INTERVAL 3 WEEK;   -- Marina: está na semana 4
SET @ini_b = @seg_atual - INTERVAL 2 WEEK;   -- Tiago: está na semana 3
SET @ini_c = @seg_atual - INTERVAL 5 WEEK;   -- Helena: está na semana 6

INSERT INTO alunos (nome, email, nicho, contrato_link, nivel_inicial, nivel_alvo, cena_objetivo) VALUES
('Marina Exemplo', 'marina@exemplo.com', 'Advocacia', 'https://exemplo.com/contratos/marina.pdf',
 'social', 'profissional', 'Conduzir em inglês a reunião de alinhamento de contrato com um escritório parceiro no exterior.'),
('Tiago Exemplo',  'tiago@exemplo.com',  'Tecnologia (TI)', 'https://exemplo.com/contratos/tiago.pdf',
 'pessoal', 'profissional', 'Participar e falar com segurança nas reuniões diárias (daily) com o time internacional.'),
('Helena Exemplo', 'helena@exemplo.com', 'Comércio exterior', 'https://exemplo.com/contratos/helena.pdf',
 'profissional', 'leadership', 'Negociar prazos e condições com fornecedores estrangeiros numa feira internacional.');

SET @a = (SELECT id FROM alunos WHERE nome = 'Marina Exemplo');
SET @b = (SELECT id FROM alunos WHERE nome = 'Tiago Exemplo');
SET @c = (SELECT id FROM alunos WHERE nome = 'Helena Exemplo');

-- Monta o curso de cada aluno (copia a lista do curso com as datas de cada semana)
SET @curso_online  = (SELECT id FROM cursos WHERE modalidade = 'online');
SET @curso_gravada = (SELECT id FROM cursos WHERE modalidade = 'gravada');
SET @curso_hibrida = (SELECT id FROM cursos WHERE modalidade = 'hibrida');
CALL sp_aplicar_curso(@a, @curso_online,  @ini_a);
CALL sp_aplicar_curso(@b, @curso_gravada, @ini_b);
CALL sp_aplicar_curso(@c, @curso_hibrida, @ini_c);

-- A data da cena real fica uma semana depois do fim do curso
UPDATE alunos SET data_cena = data_fim_prevista + INTERVAL 7 DAY WHERE id IN (@a, @b, @c);

-- Semanas já encerradas ficam concluídas; a semana 5 da Helena fica pendente
-- (aparece como "atrasada" no cronograma, para testar o alerta).
UPDATE etapas_curso
   SET status = 'concluida', data_conclusao = data_prevista + INTERVAL 6 DAY
 WHERE data_prevista + INTERVAL 6 DAY < CURRENT_DATE
   AND NOT (aluno_id = @c AND semana = 5);

-- ---------------------------------------------------------------------
-- 4.5 AGENDA
-- Marina (VIP Online): segunda a sexta, 07h00-07h50 (horário nobre).
-- Helena (Híbrida): quartas, 20h00-21h00 (horário nobre).
-- Tiago (Gravadas): encontros pontuais de acompanhamento a cada 4 semanas,
--                   segunda, 18h00-18h50.
-- Passado = realizada; de hoje em diante = agendada. Até 3 semanas à frente.
-- ---------------------------------------------------------------------
-- Sequência de dias (0 a 99) feita com dígitos: funciona no MySQL e no MariaDB (XAMPP).
CREATE TABLE aux_dias (d DATE PRIMARY KEY);  -- tabela auxiliar, apagada logo abaixo
INSERT INTO aux_dias (d)
SELECT @ini_c + INTERVAL (u.n + 10 * t.n) DAY
  FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) u
 CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) t
 WHERE @ini_c + INTERVAL (u.n + 10 * t.n) DAY <= @seg_atual + INTERVAL 20 DAY;

INSERT INTO agendamentos (tipo, aluno_id, data_aula, hora_inicio, hora_fim, horario_nobre, status, observacao)
SELECT 'aula', x.aluno_id, x.d, x.hi, x.hf, fn_horario_nobre(x.d, x.hi),
       IF(x.d < CURRENT_DATE, 'realizada', 'agendada'), x.obs
FROM (
    SELECT @a AS aluno_id, d, TIME '07:00' AS hi, TIME '07:50' AS hf,
           IF(d = @ini_a, 'Primeiro encontro: Mapa da Cena', NULL) AS obs
      FROM aux_dias WHERE d >= @ini_a AND WEEKDAY(d) <= 4
    UNION ALL
    SELECT @c, d, TIME '20:00', TIME '21:00',
           IF(d = @ini_c + INTERVAL 2 DAY, 'Primeiro encontro: Mapa da Cena', NULL)
      FROM aux_dias WHERE WEEKDAY(d) = 2
    UNION ALL
    SELECT @b, d, TIME '18:00', TIME '18:50',
           IF(d = @ini_b, 'Encontro de boas-vindas e Mapa da Cena', 'Encontro pontual de acompanhamento')
      FROM aux_dias WHERE d >= @ini_b AND WEEKDAY(d) = 0 AND MOD(DATEDIFF(d, @ini_b) DIV 7, 4) = 0
) x
ORDER BY x.d, x.hi;

DROP TABLE aux_dias;

-- Um cancelamento com motivo (Helena, há duas semanas), para testar o RF15.
UPDATE agendamentos
   SET status = 'cancelada', motivo_cancelamento = 'Viagem a trabalho; aluna pediu para retomar na semana seguinte.',
       cancelado_em = TIMESTAMP(data_aula - INTERVAL 3 DAY, '10:00')
 WHERE aluno_id = @c AND data_aula = @seg_atual - INTERVAL 2 WEEK + INTERVAL 2 DAY;

-- Frequência das aulas já realizadas (todas com presença)...
INSERT INTO frequencia (agendamento_id, aluno_id, presente)
SELECT id, aluno_id, 1 FROM agendamentos WHERE status = 'realizada';

-- ...exceto uma falta da Marina (terça da semana 2), que vai ter reposição.
UPDATE frequencia f JOIN agendamentos ag ON ag.id = f.agendamento_id
   SET f.presente = 0
 WHERE ag.aluno_id = @a AND ag.data_aula = @ini_a + INTERVAL 8 DAY;

SET @falta = (SELECT f.id FROM frequencia f JOIN agendamentos ag ON ag.id = f.agendamento_id
               WHERE ag.aluno_id = @a AND f.presente = 0 LIMIT 1);
-- Reposição no próximo sábado, 10h00 (horário nobre)
SET @sab = CURRENT_DATE + INTERVAL IF(WEEKDAY(CURRENT_DATE) = 5, 7, MOD(12 - WEEKDAY(CURRENT_DATE), 7)) DAY;
CALL sp_registrar_reposicao(@falta, @sab, '10:00', '10:50', @repo);

-- ---------------------------------------------------------------------
-- 4.6 LEADS (funil) e uma entrevista agendada
-- ---------------------------------------------------------------------
INSERT INTO leads (nome, email, origem, nicho, status, observacao) VALUES
('Lead Exemplo 1', 'lead1@exemplo.com', 'Instagram', 'Medicina',             'novo',       'Pediu informações sobre a Mentoria Híbrida.'),
('Lead Exemplo 2', 'lead2@exemplo.com', 'Indicação', 'Diretoria comercial',  'em_contato', 'Quer se preparar para apresentações a clientes estrangeiros.'),
('Lead Exemplo 3', 'lead3@exemplo.com', 'LinkedIn',  'Agronegócio',          'desistiu',   'Adiou para o próximo semestre.');

SET @lead2 = (SELECT id FROM leads WHERE nome = 'Lead Exemplo 2');
CALL sp_agendar_aula(NULL, @lead2, 'entrevista',
                     @seg_atual + INTERVAL 8 DAY, '12:30', '13:15', 'Entrevista inicial (diagnóstico)', @entrev);

-- ---------------------------------------------------------------------
-- 4.7 MATERIAIS (gerais e por aluno) e AULAS GRAVADAS
-- ---------------------------------------------------------------------
INSERT INTO materiais (aluno_id, titulo, tipo, link) VALUES
(NULL, 'Mapa das estruturas-base (I am, I like to, I have to, I need to…)', 'PDF',    'https://exemplo.com/materiais/estruturas-base.pdf'),
(NULL, 'Modelo do Mapa da Cena',                                          'PDF',    'https://exemplo.com/materiais/mapa-da-cena.pdf'),
(NULL, 'Roteiro do pitch: problema, solução, prova e fecho',              'PDF',    'https://exemplo.com/materiais/roteiro-pitch.pdf'),
(NULL, 'E-book de revisão do Método Indutivo',                            'E-book', 'https://exemplo.com/materiais/ebook-revisao.pdf'),
(@a,   'Vocabulário de dentro: reuniões e contratos (Advocacia)',         'PDF',    'https://exemplo.com/materiais/vocab-advocacia.pdf'),
(@b,   'Vocabulário de dentro: daily meetings (TI)',                      'PDF',    'https://exemplo.com/materiais/vocab-ti.pdf'),
(@c,   'Vocabulário de dentro: negociação com fornecedores',              'PDF',    'https://exemplo.com/materiais/vocab-comex.pdf');

-- Videoaulas liberadas até hoje: Tiago (3 por semana: seg, qua, sex) e
-- Helena (2 por semana: seg e qua). Uma gravação geral de boas-vindas.
INSERT INTO aulas_gravadas (aluno_id, titulo, link, data_aula)
VALUES (NULL, 'Boas-vindas ao Método Indutivo', 'https://exemplo.com/videoaulas/boas-vindas', @ini_c);

INSERT INTO aulas_gravadas (aluno_id, titulo, link, data_aula)
SELECT e.aluno_id,
       CONCAT('Semana ', e.semana, ' · Videoaula ', k.n, ': ', e.titulo),
       CONCAT('https://exemplo.com/videoaulas/', e.aluno_id, '-s', e.semana, '-v', k.n),
       e.data_prevista + INTERVAL ((k.n - 1) * 2) DAY
FROM etapas_curso e
JOIN alunos a ON a.id = e.aluno_id
JOIN cursos c ON c.id = a.curso_id
JOIN (SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3) k ON k.n <= c.aulas_gravadas_semana
WHERE e.data_prevista + INTERVAL ((k.n - 1) * 2) DAY <= CURRENT_DATE;

-- Marina (VIP Online) recebe a gravação dos encontros de ensaio de cada semana encerrada.
INSERT INTO aulas_gravadas (aluno_id, titulo, link, data_aula)
SELECT aluno_id, CONCAT('Gravação do ensaio · Semana ', semana, ': ', titulo),
       CONCAT('https://exemplo.com/gravacoes/', aluno_id, '-s', semana), data_prevista + INTERVAL 4 DAY
FROM etapas_curso WHERE aluno_id = @a AND status = 'concluida';

-- ---------------------------------------------------------------------
-- 4.8 ATIVIDADES (uma entregue, uma pendente e uma atrasada por aluno)
-- ---------------------------------------------------------------------
INSERT INTO atividades (aluno_id, titulo, descricao, data_entrega, status) VALUES
(@a, 'Gravar a apresentação pessoal de 30 s', 'Gravar de uma vez só, sem ler, com algo a mais além do treinado.', @ini_a + INTERVAL 4 DAY, 'entregue'),
(@a, 'Gravar o pitch de 30 s', 'Problema que resolvo, como resolvo, uma prova com marcador de tempo e o fecho.', CURRENT_DATE - INTERVAL 2 DAY, 'pendente'),
(@a, 'Usar 10 palavras do vocabulário de contratos em frases faladas', 'Falar cada frase em voz alta e gravar as 3 melhores.', CURRENT_DATE + INTERVAL 3 DAY, 'pendente'),
(@b, 'Assistir às 3 videoaulas da semana 1 falando nas pausas', 'Responder em voz alta a cada pausa.', @ini_b + INTERVAL 6 DAY, 'entregue'),
(@b, 'Gravar a apresentação da equipe (He/She is…)', 'Apresentar 3 colegas do time em 30 s.', CURRENT_DATE - INTERVAL 1 DAY, 'pendente'),
(@b, 'Micro-narração diária da rotina de trabalho', 'Dizer baixinho, em inglês, cada ação do dia.', CURRENT_DATE + INTERVAL 3 DAY, 'pendente'),
(@c, 'Mapa da Cena da feira internacional', 'Compromisso, data, com quem vai negociar e o que está em jogo.', @ini_c + INTERVAL 4 DAY, 'entregue'),
(@c, 'Gravar 5 perguntas para fazer a um fornecedor', 'Perguntas com do/does, gravadas de uma vez só.', CURRENT_DATE - INTERVAL 3 DAY, 'pendente'),
(@c, 'Contar a trajetória profissional em 3 blocos', 'Usar after, then e so para ligar as ações.', CURRENT_DATE + INTERVAL 5 DAY, 'pendente');

-- ---------------------------------------------------------------------
-- 4.9 FINANCEIRO (VALORES FICTÍCIOS, só para teste)
-- Três parcelas por aluno; a 1ª paga. A Helena tem a 2ª vencida.
-- ---------------------------------------------------------------------
INSERT INTO financeiro (aluno_id, descricao, valor, vencimento, status, data_pagamento, forma_pagamento)
SELECT a.id, CONCAT('Parcela ', p.n, '/3 (valor fictício)'), 100.00,
       a.data_inicio + INTERVAL ((p.n - 1) * 30) DAY,
       IF(p.n = 1, 'pago', 'pendente'),
       IF(p.n = 1, a.data_inicio, NULL),
       IF(p.n = 1, 'Pix', NULL)
FROM alunos a
JOIN (SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3) p
WHERE a.id IN (@a, @b, @c);

-- ---------------------------------------------------------------------
-- 4.10 DIÁRIO DE BORDO (anotações de acompanhamento, sem dados sensíveis)
-- ---------------------------------------------------------------------
INSERT INTO diario_bordo (aluno_id, data_registro, anotacao) VALUES
(@a, @ini_a,                    'Mapa da Cena definido: reunião de contrato com escritório parceiro. Começamos pela apresentação pessoal.'),
(@a, @seg_atual - INTERVAL 3 DAY,'Pitch ficou mais curto e claro. Próximo passo: vocabulário de contratos.'),
(@b, @ini_b,                    'Boas-vindas feitas. Combinado assistir às videoaulas falando nas pausas e gravar todo dia.'),
(@c, @ini_c + INTERVAL 2 DAY,   'Cena: negociação com fornecedores. Já fala bem do próprio trabalho; foco em perguntas e negociação.'),
(@c, @seg_atual - INTERVAL 5 DAY,'Ensaiou a apresentação do produto. Próxima semana: contar a trajetória.');

-- Marca como atrasado o que já venceu (parcelas e atividades).
CALL sp_atualizar_atrasos();

-- =====================================================================
-- Fim do script. Testes rápidos:
--   SELECT * FROM vw_resumo_dashboard;
--   SELECT * FROM vw_cursos_resumo;
--   SELECT * FROM vw_cronograma_aluno;
--   SELECT * FROM vw_proximas_aulas;
-- =====================================================================
