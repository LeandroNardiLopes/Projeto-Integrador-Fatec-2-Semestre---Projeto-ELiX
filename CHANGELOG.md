# Histórico de versões · Luvia (antigo ELiX)

## Luvia 1.4.0 · 01/11/2026
Versão 1 do sistema: **telas principais**, na pasta [`luvia/`](luvia/).

- Sistema em HTML, CSS, PHP 8 e MySQL 8 / MariaDB (XAMPP), arquitetura MVC.
- Login com criação de senha no primeiro acesso; uma única usuária (mentora e administradora).
- Painel: indicadores, alertas do dia, próximos compromissos, estatísticas dos últimos 3 meses e progresso dos alunos.
- Agenda semanal: agendar aula ou entrevista, horário nobre automático, conflito de horário, presença, falta, reposição e cancelamento com motivo.
- Alunos: busca e filtros, cadastro com curso (12 semanas montadas com datas), edição, inativação e ficha com cronograma e frequência.
- Banco completo (`luvia/database/luvia_mysql.sql`): 15 tabelas, 9 views, 10 stored procedures e dados fictícios (3 cursos e 3 alunos).
- Documentação técnica da versão 1 em [`luvia/docs/`](luvia/docs/) (Word e PDF).
- Teste automático (`luvia/tests/e2e.js`): 53 de 53 verificações aprovadas em MySQL 8.0 e MariaDB 10.11.

**Próxima versão:** leads, cursos, ficha completa do aluno, materiais e gravações, financeiro, minha conta e cópia de segurança.

## Luvia 1.3.0
Atualização da documentação do projeto.
