-- =====================================================================
-- ELIX - Schema do Banco de Dados (SQLite)
-- Plataforma de Mentoria em Comunicação Profissional Bilíngue
-- =====================================================================
-- Este arquivo cria todas as tabelas do sistema e insere alguns dados
-- de exemplo para você já abrir o sistema e ver tudo funcionando.
-- =====================================================================

PRAGMA foreign_keys = ON;

-- ---------------------------------------------------------------------
-- USUARIOS: login do sistema. Pode ser "professor" (mentor/admin)
-- ou "aluno" (acessa apenas o Portal do Aluno).
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nome          TEXT NOT NULL,
    email         TEXT NOT NULL UNIQUE,
    senha_hash    TEXT NOT NULL,
    tipo          TEXT NOT NULL CHECK (tipo IN ('professor', 'aluno')),
    criado_em     TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------
-- ALUNOS: dados específicos de cada aluno (1 para 1 com usuarios)
-- ---------------------------------------------------------------------
CREATE TABLE alunos (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id      INTEGER NOT NULL UNIQUE REFERENCES usuarios(id) ON DELETE CASCADE,
    nicho           TEXT,                       -- ex: "Advogados", "Área de TI"
    data_inicio     TEXT NOT NULL DEFAULT (date('now')),
    contrato_link   TEXT,                       -- link externo (Drive, PDF, etc.)
    ativo           INTEGER NOT NULL DEFAULT 1  -- 1 = ativo, 0 = inativo
);

-- ---------------------------------------------------------------------
-- MATERIAIS: e-books, PDFs e arquivos do repositório de materiais.
-- Se aluno_id for NULL, o material é geral (aparece para todos os alunos).
-- ---------------------------------------------------------------------
CREATE TABLE materiais (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    aluno_id    INTEGER REFERENCES alunos(id) ON DELETE CASCADE,
    titulo      TEXT NOT NULL,
    tipo        TEXT NOT NULL DEFAULT 'PDF',   -- PDF, E-book, Planilha, etc.
    link        TEXT NOT NULL,                 -- URL de download/visualização
    criado_em   TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------
-- AULAS_GRAVADAS: links das aulas já gravadas.
-- Se aluno_id for NULL, a gravação é geral (aparece para todos).
-- ---------------------------------------------------------------------
CREATE TABLE aulas_gravadas (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    aluno_id    INTEGER REFERENCES alunos(id) ON DELETE CASCADE,
    titulo      TEXT NOT NULL,
    link        TEXT NOT NULL,
    data_aula   TEXT NOT NULL DEFAULT (date('now'))
);

-- ---------------------------------------------------------------------
-- ETAPAS_CURSO: conteúdo/currículo montado individualmente por aluno.
-- Cada linha é uma etapa do curso daquele aluno específico.
-- ---------------------------------------------------------------------
CREATE TABLE etapas_curso (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    aluno_id        INTEGER NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    titulo          TEXT NOT NULL,
    ordem           INTEGER NOT NULL DEFAULT 0,
    status          TEXT NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente', 'concluida')),
    data_conclusao  TEXT
);

-- ---------------------------------------------------------------------
-- AGENDAMENTOS: aulas marcadas (passadas e futuras).
-- horario_nobre é calculado e salvo no momento do agendamento.
-- ---------------------------------------------------------------------
CREATE TABLE agendamentos (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    aluno_id        INTEGER NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    data_aula       TEXT NOT NULL,          -- YYYY-MM-DD
    hora_inicio     TEXT NOT NULL,          -- HH:MM
    hora_fim        TEXT NOT NULL,          -- HH:MM
    horario_nobre   INTEGER NOT NULL DEFAULT 0,
    status          TEXT NOT NULL DEFAULT 'agendada'
                    CHECK (status IN ('agendada', 'realizada', 'cancelada', 'reposta')),
    observacao      TEXT
);

-- ---------------------------------------------------------------------
-- FREQUENCIA: presença/falta registrada para cada aula realizada.
-- Ligada a um agendamento (1 para 1), mas guardada separada para
-- manter o histórico simples de consultar.
-- ---------------------------------------------------------------------
CREATE TABLE frequencia (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    agendamento_id  INTEGER NOT NULL UNIQUE REFERENCES agendamentos(id) ON DELETE CASCADE,
    aluno_id        INTEGER NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    presente        INTEGER NOT NULL DEFAULT 1,   -- 1 = presente, 0 = faltou
    reposta         INTEGER NOT NULL DEFAULT 0,   -- 1 = essa falta já foi reposta
    registrado_em   TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------------------------------------------------------------------
-- ATIVIDADES: tarefas propostas para o aluno e o status de entrega.
-- ---------------------------------------------------------------------
CREATE TABLE atividades (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    aluno_id        INTEGER NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    titulo          TEXT NOT NULL,
    descricao       TEXT,
    data_entrega    TEXT,                        -- prazo (YYYY-MM-DD)
    status          TEXT NOT NULL DEFAULT 'pendente'
                    CHECK (status IN ('pendente', 'entregue', 'atrasada'))
);

-- ---------------------------------------------------------------------
-- FINANCEIRO: parcelas/pagamentos de cada aluno.
-- ---------------------------------------------------------------------
CREATE TABLE financeiro (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    aluno_id        INTEGER NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    descricao       TEXT NOT NULL DEFAULT 'Mensalidade',
    valor           REAL NOT NULL,
    vencimento      TEXT NOT NULL,               -- YYYY-MM-DD
    status          TEXT NOT NULL DEFAULT 'pendente'
                    CHECK (status IN ('pago', 'pendente', 'atrasado')),
    data_pagamento  TEXT
);

-- =====================================================================
-- DADOS DE EXEMPLO (SEED) — para você já testar o sistema funcionando
-- =====================================================================

-- Senha de todos os usuários de exemplo abaixo: "123456"
-- (hash gerado com werkzeug.security.generate_password_hash)

INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES
('Prof. Ana Beatriz', 'professor@elix.com', 'scrypt:32768:8:1$PLACEHOLDER_PROF', 'professor'),
('Carlos Mendes',      'carlos@aluno.com',   'scrypt:32768:8:1$PLACEHOLDER_ALU1', 'aluno'),
('Juliana Prado',      'juliana@aluno.com',  'scrypt:32768:8:1$PLACEHOLDER_ALU2', 'aluno');

INSERT INTO alunos (usuario_id, nicho, data_inicio, contrato_link, ativo) VALUES
(2, 'Advocacia',        date('now', '-90 days'), 'https://exemplo.com/contrato-carlos.pdf', 1),
(3, 'Tecnologia (TI)',  date('now', '-20 days'),  'https://exemplo.com/contrato-juliana.pdf', 1);

INSERT INTO materiais (aluno_id, titulo, tipo, link) VALUES
(NULL, 'E-book: Fundamentos do Método Indutivo', 'E-book', 'https://exemplo.com/ebook-metodo.pdf'),
(1, 'Glossário Jurídico Bilíngue', 'PDF', 'https://exemplo.com/glossario-juridico.pdf'),
(2, 'Vocabulário Técnico de TI', 'PDF', 'https://exemplo.com/vocab-ti.pdf');

INSERT INTO aulas_gravadas (aluno_id, titulo, link, data_aula) VALUES
(NULL, 'Aula 01 - Boas-vindas ao Método Indutivo', 'https://exemplo.com/aula01', date('now', '-30 days')),
(1, 'Aula 02 - Apresentações em Reuniões (Advocacia)', 'https://exemplo.com/aula02-carlos', date('now', '-10 days')),
(2, 'Aula 02 - Vocabulário de Daily Meetings', 'https://exemplo.com/aula02-juliana', date('now', '-5 days'));

INSERT INTO etapas_curso (aluno_id, titulo, ordem, status, data_conclusao) VALUES
(1, 'Diagnóstico de nível inicial', 1, 'concluida', date('now', '-85 days')),
(1, 'Fundamentos de pronúncia', 2, 'concluida', date('now', '-60 days')),
(1, 'Vocabulário jurídico essencial', 3, 'concluida', date('now', '-30 days')),
(1, 'Simulações de reunião em inglês', 4, 'pendente', NULL),
(1, 'Apresentação final', 5, 'pendente', NULL),
(2, 'Diagnóstico de nível inicial', 1, 'concluida', date('now', '-18 days')),
(2, 'Fundamentos de pronúncia', 2, 'pendente', NULL),
(2, 'Vocabulário técnico de TI', 3, 'pendente', NULL);

INSERT INTO agendamentos (aluno_id, data_aula, hora_inicio, hora_fim, horario_nobre, status) VALUES
(1, date('now', '-7 days'), '09:00', '10:00', 0, 'realizada'),
(1, date('now', '-3 days'), '19:30', '20:30', 1, 'realizada'),
(1, date('now', '+2 days'), '09:00', '10:00', 0, 'agendada'),
(2, date('now', '-4 days'), '07:30', '08:30', 1, 'realizada'),
(2, date('now', '-1 days'), '14:00', '15:00', 0, 'cancelada'),
(2, date('now', '+1 days'), '14:00', '15:00', 0, 'agendada');

INSERT INTO frequencia (agendamento_id, aluno_id, presente, reposta) VALUES
(1, 1, 1, 0),
(2, 1, 0, 1),
(4, 2, 1, 0);

INSERT INTO atividades (aluno_id, titulo, descricao, data_entrega, status) VALUES
(1, 'Redigir e-mail formal de follow-up', 'Praticar estrutura de e-mail pós-reunião.', date('now', '+3 days'), 'pendente'),
(1, 'Gravar áudio de apresentação pessoal', 'Enviar áudio de até 2 minutos.', date('now', '-2 days'), 'entregue'),
(2, 'Lista de vocabulário técnico', 'Traduzir 20 termos usados no dia a dia.', date('now', '-1 days'), 'atrasada');

INSERT INTO financeiro (aluno_id, descricao, valor, vencimento, status, data_pagamento) VALUES
(1, 'Mensalidade - Mês 1', 450.00, date('now', '-60 days'), 'pago', date('now', '-60 days')),
(1, 'Mensalidade - Mês 2', 450.00, date('now', '-30 days'), 'pago', date('now', '-29 days')),
(1, 'Mensalidade - Mês 3', 450.00, date('now', '+2 days'), 'pendente', NULL),
(2, 'Mensalidade - Mês 1', 480.00, date('now', '-5 days'), 'atrasado', NULL);
