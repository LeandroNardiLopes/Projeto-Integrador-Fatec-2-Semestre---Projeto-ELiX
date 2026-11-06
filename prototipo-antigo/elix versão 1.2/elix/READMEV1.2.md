# Elix — MVP

Plataforma de Mentoria em Comunicação Profissional Bilíngue (Método Indutivo).

Stack: **Python + Flask** (backend), **SQLite** (banco de dados), **HTML + Tailwind CSS via CDN** (frontend, sem JavaScript de gráficos — tudo em barras de progresso e cards feitos com HTML/CSS puro, fácil de editar).

---

## 📁 Estrutura de arquivos

```
elix/
├── app.py                 → aplicação Flask (todas as rotas/páginas)
├── database.py             → conexão e criação do banco de dados
├── schema.sql               → estrutura das tabelas + dados de exemplo
├── requirements.txt         → dependências Python
├── elix.db                  → banco de dados SQLite (criado automaticamente)
└── templates/                → páginas HTML (Jinja2 + Tailwind)
    ├── base.html             → layout base (menu, cores, estrutura)
    ├── login.html
    ├── dashboard.html         → painel do professor
    ├── aluno_detalhe.html     → frequência, atividades, progresso, financeiro
    ├── agenda.html             → agendamento de aulas (com horário nobre)
    ├── portal_materiais.html   → portal do aluno: materiais
    ├── portal_aulas.html        → portal do aluno: aulas gravadas
    └── portal_progresso.html    → portal do aluno: progresso
```

---

## ▶️ Como rodar no seu computador (passo a passo)

### 1. Pré-requisito: ter o Python instalado
Verifique no terminal:
```bash
python3 --version
```
Se não tiver, baixe em [python.org/downloads](https://www.python.org/downloads/).

### 2. Abra o terminal na pasta do projeto
```bash
cd caminho/para/elix
```

### 3. Crie um ambiente virtual (recomendado, mas opcional)
```bash
python3 -m venv venv
```
Ativar o ambiente:
- **Windows:** `venv\Scripts\activate`
- **Mac/Linux:** `source venv/bin/activate`

### 4. Instale as dependências
```bash
pip install -r requirements.txt
```

### 5. Crie o banco de dados (só precisa fazer isso 1 vez, ou toda vez que quiser "resetar" os dados)
```bash
python database.py
```
Isso vai criar o arquivo `elix.db` já com dados de exemplo (2 alunos, aulas, materiais, financeiro etc.).

### 6. Rode o servidor
```bash
python app.py
```
Você vai ver algo como:
```
* Running on http://127.0.0.1:5000
```

### 7. Abra no navegador
Acesse: **http://127.0.0.1:5000**

---

## 🔑 Contas de acesso (dados de exemplo)

Todas com a senha: **123456**

| Perfil     | E-mail               |
|------------|-----------------------|
| Professor  | professor@elix.com    |
| Aluno 1    | carlos@aluno.com      |
| Aluno 2    | juliana@aluno.com     |

---

## 🧠 Como o código está organizado (para você ir entendendo aos poucos)

- **`schema.sql`** define as "tabelas" do banco — pense nelas como planilhas Excel conectadas entre si (ex: a tabela `alunos` se conecta com `financeiro`, `atividades`, etc. através do `aluno_id`).
- **`database.py`** só abre/fecha a conexão com o banco e cria as tabelas na primeira vez.
- **`app.py`** é o "cérebro": cada `@app.route(...)` é uma página do site. Dentro de cada rota, o código busca dados no banco (`db.execute("SELECT ...")`) e manda para um arquivo HTML (`render_template(...)`).
- **`templates/*.html`** são as páginas visuais. Usam **Jinja2** (a linguagem de template do Flask) — por isso você vê coisas como `{{ aluno.nome }}` (mostra um valor) e `{% for %}` (repete um bloco, como uma tabela).
- Todos os estilos vêm do **Tailwind CSS via CDN** — ou seja, você escreve classes direto no HTML (ex: `class="text-white bg-panel"`) sem precisar de arquivos `.css` separados.

## ⭐ Regra do "Horário Nobre"

Está centralizada na função `is_horario_nobre()` dentro do `app.py`:
- Qualquer horário aos **sábados**, OU
- Aulas **antes das 08h00** ou **a partir das 20h00**, de segunda a sexta.

Se quiser mudar essa regra no futuro, só editar essa função — o resto do sistema já usa ela automaticamente.

## 🛠️ Próximos passos sugeridos (fora do escopo deste MVP)
- Cadastro de novos alunos direto pela interface (hoje é feito só via banco/seed).
- Upload real de arquivos (hoje os materiais são links externos, ex: Google Drive).
- Envio de notificações automáticas de cobrança/lembrete de aula.
- Deploy em um servidor (ex: Render, Railway, PythonAnywhere) para acesso fora do seu computador.
