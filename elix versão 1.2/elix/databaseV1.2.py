"""
database.py
=====================================================================
Responsável por TUDO que envolve o banco de dados SQLite:
 - abrir/fechar a conexão
 - criar o banco a partir do schema.sql (com dados de exemplo)
 - fornecer uma função simples "get_db()" que o app.py usa

Não precisa mexer neste arquivo no dia a dia. As alterações de
regras de negócio ficam no app.py, nas queries de cada rota.
=====================================================================
"""

import sqlite3
import os
from werkzeug.security import generate_password_hash

# Caminho do arquivo do banco de dados (fica na mesma pasta do projeto)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATABASE_PATH = os.path.join(BASE_DIR, "elix.db")
SCHEMA_PATH = os.path.join(BASE_DIR, "schema.sql")


def get_db():
    """
    Abre e devolve uma conexão com o banco de dados.
    row_factory = sqlite3.Row permite acessar colunas pelo nome,
    ex: aluno["nome"] em vez de aluno[0] — muito mais fácil de ler.
    """
    conn = sqlite3.connect(DATABASE_PATH)
    conn.row_factory = sqlite3.Row
    # Garante que as regras de chave estrangeira (FOREIGN KEY) sejam respeitadas
    conn.execute("PRAGMA foreign_keys = ON")
    return conn


def init_db():
    """
    Cria o banco de dados do zero, executando o schema.sql.
    Se o arquivo elix.db já existir, ele é apagado e recriado
    (útil durante o desenvolvimento, para sempre começar limpo).

    Depois de criar as tabelas, substitui as senhas de exemplo
    (que no schema.sql estão como texto "PLACEHOLDER") por hashes
    reais e seguros, gerados pelo Werkzeug.
    """
    if os.path.exists(DATABASE_PATH):
        os.remove(DATABASE_PATH)

    conn = get_db()
    with open(SCHEMA_PATH, "r", encoding="utf-8") as f:
        conn.executescript(f.read())

    # Gera hashes de senha reais para os usuários de exemplo.
    # Senha de todos os usuários de exemplo: 123456
    senha_hash = generate_password_hash("123456")
    conn.execute("UPDATE usuarios SET senha_hash = ?", (senha_hash,))
    conn.commit()
    conn.close()
    print("✅ Banco de dados criado com sucesso em:", DATABASE_PATH)
    print("   Usuários de exemplo (senha para todos: 123456):")
    print("   - Professor: professor@elix.com")
    print("   - Aluno 1:   carlos@aluno.com")
    print("   - Aluno 2:   juliana@aluno.com")


if __name__ == "__main__":
    # Permite rodar "python database.py" para (re)criar o banco manualmente
    init_db()
