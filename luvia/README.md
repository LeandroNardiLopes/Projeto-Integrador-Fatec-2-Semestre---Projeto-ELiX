# Luvia · Versão 1 (telas principais)

Sistema de gestão da mentoria em comunicação profissional bilíngue da mentora Lilian Mesquita.
Projeto Integrador (PI-2) · DSM · Fatec Itapira · 2º semestre de 2026.

Esta é a **versão 1**: login, painel, alunos e agenda. A versão 2 completa o sistema.

**Tecnologias:** HTML5, CSS3, PHP 8 e MySQL 8 (ou MariaDB, que vem no XAMPP). Arquitetura MVC, sem frameworks.
**Uso:** uma única usuária (a mentora, que também é a administradora). O sistema roda no computador dela, sem hospedagem.

---

## Como instalar no seu computador (Windows, com XAMPP)

### 1. Instale o XAMPP
Baixe em https://www.apachefriends.org (versão com PHP 8.2 ou mais nova) e instale com as opções padrão.

### 2. Coloque o sistema na pasta do XAMPP
Copie a pasta inteira do projeto para `C:\xampp\htdocs\` e renomeie para `luvia`.
O caminho final fica assim: `C:\xampp\htdocs\luvia\public\index.php`.

### 3. Ligue o Apache e o MySQL
Abra o **XAMPP Control Panel** e clique em **Start** em **Apache** e em **MySQL**.

### 4. Crie o banco de dados
1. No navegador, abra http://localhost/phpmyadmin
2. Clique em **Importar** (Import) no menu de cima.
3. Escolha o arquivo `database/luvia_mysql.sql` e clique em **Importar** (no fim da página).
4. Pronto: o banco `luvia` foi criado com as tabelas, as regras e os dados de exemplo.

> Importar de novo apaga e recria o banco. Faça isso só na instalação ou para voltar aos dados de exemplo.

### 5. Abra o sistema
Acesse http://localhost/luvia

No **primeiro acesso**, o sistema pede seu nome, e-mail e uma senha (mínimo de 8 caracteres).
Depois é só entrar com esse e-mail e essa senha.

### Se o MySQL do XAMPP tiver senha
Copie o arquivo `.env.example` com o nome `.env` e preencha `DB_USUARIO` e `DB_SENHA`.
Sem o `.env`, o sistema usa o padrão do XAMPP (usuário `root`, sem senha).

---

## O que tem no sistema

- **Login** com criação da senha no primeiro acesso (RF01).
- **Painel:** indicadores, alertas do dia, próximos compromissos, estatísticas dos últimos 3 meses e progresso dos alunos (RF30 a RF32).
- **Agenda:** semana de segunda a domingo, agendar aula (aluno) ou entrevista (lead), horário nobre automático, cancelar com motivo, presença e falta, reposição (RF13 a RF17, RF20).
- **Alunos:** lista com busca e filtros, cadastro com escolha do curso (o sistema monta as 12 semanas com as datas), edição, inativação e reativação, e a ficha com dados, Mapa da Cena, nível de fluência, cronograma do curso, aulas e frequência (RF05 a RF09, RF19).

**Fica para a versão 2:** leads, cursos e módulos, personalizar etapas, atividades, diário de bordo, materiais e gravações, financeiro, minha conta, registro de ações e cópia de segurança.

### Regras que o sistema garante (ficam no banco de dados)
- Horário nobre automático: qualquer horário aos sábados, ou aulas que começam antes das 08h00 ou a partir das 20h00.
- Dois compromissos não podem ocupar o mesmo horário.
- Todo cancelamento exige motivo e guarda data e hora.
- Só faltas têm reposição (uma por falta); a presença na reposição marca a falta como reposta.
- Aluno nunca é apagado, só inativado; ao inativar, as aulas futuras dele são canceladas.
- Parcelas e atividades vencidas viram "atrasadas" automaticamente.

### Dados de exemplo (todos fictícios)
- 3 cursos de 12 semanas: Mentoria VIP Online (5 encontros ao vivo por semana), Mentoria VIP Aulas Gravadas (3 videoaulas por semana) e Mentoria Híbrida (1 encontro ao vivo e 2 videoaulas por semana).
- 3 alunos fictícios, um em cada curso (Marina, Tiago e Helena Exemplo), com agenda, cronograma, frequência, atividades, materiais e parcelas.
- 3 leads em etapas diferentes do funil.

Para começar a usar de verdade, cadastre os alunos reais e inative os de exemplo (ou importe o `database/luvia_mysql.sql` de novo e apague os dados fictícios pelo phpMyAdmin).

---

## Organização do código (MVC)

```
luvia/
├── index.php              atalho que abre public/
├── public/                a única pasta acessível pelo navegador
│   ├── index.php          ponto de entrada: escolhe o controller pela URL (?p=modulo&a=acao)
│   └── assets/            CSS, logo e ícone
├── app/
│   ├── bootstrap.php      configuração, sessão segura e carregamento das classes
│   ├── rotas.php          módulos e ações que existem nesta versão
│   ├── Config/            leitura do .env
│   ├── Helpers/           conexão com o banco (Db), login (Auth) e funções de apoio
│   ├── Models/            uma classe por assunto; usa as views e procedures do banco
│   ├── Controllers/       uma classe por módulo; recebe o pedido e escolhe a tela
│   └── Views/             as telas (HTML + PHP)
├── database/
│   └── luvia_mysql.sql    tabelas, views, procedures e dados de exemplo
├── docs/                 documentação técnica desta versão (Word e PDF)
└── tests/
    └── e2e.js             teste automático de todas as telas (Playwright)
```

As regras de negócio ficam no banco, em **stored procedures** (ex.: `sp_agendar_aula`, `sp_cancelar_aula`, `sp_registrar_reposicao`) e as telas consultam **views** (ex.: `vw_resumo_dashboard`, `vw_cronograma_aluno`). O PHP só chama essas procedures e mostra as mensagens.

### Segurança
- Senha guardada com `password_hash` (nunca em texto).
- Todas as consultas usam *prepared statements* (proteção contra SQL injection).
- Todo texto mostrado na tela passa por `htmlspecialchars` (proteção contra XSS).
- Todo formulário tem token CSRF.
- Sessão protegida, que expira após 60 minutos sem uso; 5 senhas erradas bloqueiam o login por 5 minutos.
- As pastas `app/`, `database/` e o arquivo `.env` ficam bloqueados para o navegador.

---

## Testes realizados

Testado em PHP 8.3 com MySQL 8.0 e com MariaDB 10.11 (o banco do XAMPP): 53 verificações automáticas passaram, cobrindo login, primeiro acesso, bloqueio de SQL injection e XSS, token CSRF, todas as telas desta versão, conflito de horário, horário nobre, falta, reposição, cancelamento com motivo, cadastro com curso, edição, inativação e reativação.

Para rodar o teste automático (opcional, precisa do Node.js):
```bash
npm install playwright
npx playwright install chromium
# com o banco recém-importado e o sistema aberto em http://localhost/luvia/public
node tests/e2e.js http://localhost/luvia/public v1
```
