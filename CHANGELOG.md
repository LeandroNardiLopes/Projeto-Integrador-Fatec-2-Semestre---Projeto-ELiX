# Histórico de versões · Luvia (antigo ELiX)

## Luvia 2.0 · 06/11/2026
Versão 2 do sistema: **completa**, na pasta [`luvia/`](luvia/) (substitui a versão 1 nessa pasta).

- Tudo da versão 1 (login, painel, agenda e alunos), mais:
- Leads: funil (novo, em contato, convertido, desistiu), entrevista e conversão em aluno.
- Cursos: cursos, módulos e lista semana a semana.
- Ficha do aluno completa: etapas personalizadas, atividades com prazo, diário de bordo, materiais, gravações e financeiro.
- Financeiro: cobranças, parcelas do contrato, pagamentos, atrasos automáticos e pendências.
- Minha conta: dados e senha, registro de ações e cópia de segurança com restauração.
- Documentação técnica v2 em [`luvia/docs/`](luvia/docs/) (Word e PDF).
- Diagrama de Casos de Uso (ESI) em [`luvia/docs/casos-de-uso/`](luvia/docs/casos-de-uso/): arquivo do draw.io, imagem e PDF de entrega.
- Organização do repositório: o protótipo antigo em Python/Flask foi para [`prototipo-antigo/`](prototipo-antigo/); o `luvia-v2-completo.zip` e o docx soltos na raiz (da 1.4.1) foram trocados pelos arquivos abertos na pasta `luvia/`.

## Luvia 1.4.1 · 03/11/2026
Envio do `luvia-v2-completo.zip` e da documentação técnica v2 na raiz do repositório.

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
