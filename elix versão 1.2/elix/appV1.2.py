"""
app.py
=====================================================================
ELIX - Plataforma de Mentoria em Comunicação Profissional Bilíngue
=====================================================================
Aplicação Flask principal. Aqui ficam TODAS as rotas (páginas) do
sistema: login, painel do professor e portal do aluno.

Como o código está organizado:
 1. Configuração inicial do Flask
 2. Funções auxiliares (regras de negócio: horário nobre, decorators)
 3. Rotas de autenticação (login/logout)
 4. Rotas do Painel do Professor
 5. Rotas do Portal do Aluno

Para rodar: veja o README.md na raiz do projeto.
=====================================================================
"""

from flask import Flask, render_template, request, redirect, url_for, session, flash, g
from werkzeug.security import check_password_hash, generate_password_hash
from datetime import datetime, date, timedelta
from functools import wraps
import os

from database import get_db, init_db, DATABASE_PATH

app = Flask(__name__)
app.secret_key = "troque-esta-chave-em-producao-por-algo-aleatorio"  # usado para criptografar a sessão


# =====================================================================
# 2. FUNÇÕES AUXILIARES (regras de negócio)
# =====================================================================

def is_horario_nobre(data_str: str, hora_str: str) -> bool:
    """
    Regra de negócio: horário nobre é:
      - Qualquer horário aos SÁBADOS, OU
      - Antes das 08h00 ou a partir das 20h00 (inclusive), de segunda a sexta.

    data_str: "YYYY-MM-DD"
    hora_str: "HH:MM"
    """
    data_obj = datetime.strptime(data_str, "%Y-%m-%d").date()
    hora_obj = datetime.strptime(hora_str, "%H:%M").time()

    # weekday(): segunda=0 ... domingo=6. Sábado = 5.
    if data_obj.weekday() == 5:
        return True

    if hora_obj.hour < 8 or hora_obj.hour >= 20:
        return True

    return False


def login_required(f):
    """Decorator: bloqueia a rota se o usuário não estiver logado."""
    @wraps(f)
    def wrapped(*args, **kwargs):
        if "usuario_id" not in session:
            flash("Faça login para continuar.", "erro")
            return redirect(url_for("login"))
        return f(*args, **kwargs)
    return wrapped


def professor_required(f):
    """Decorator: bloqueia a rota se o usuário não for do tipo professor."""
    @wraps(f)
    def wrapped(*args, **kwargs):
        if session.get("tipo") != "professor":
            flash("Acesso restrito à equipe Elix.", "erro")
            return redirect(url_for("portal_progresso"))
        return f(*args, **kwargs)
    return wrapped


def get_aluno_logado_id():
    """Retorna o id da tabela 'alunos' referente ao usuário logado."""
    db = get_db()
    row = db.execute(
        "SELECT id FROM alunos WHERE usuario_id = ?", (session["usuario_id"],)
    ).fetchone()
    db.close()
    return row["id"] if row else None


@app.context_processor
def inject_globals():
    """Disponibiliza variáveis para TODOS os templates automaticamente."""
    return {
        "usuario_nome": session.get("nome"),
        "usuario_tipo": session.get("tipo"),
        "ano_atual": date.today().year,
    }


# =====================================================================
# 3. AUTENTICAÇÃO
# =====================================================================

@app.route("/")
def index():
    if "usuario_id" not in session:
        return redirect(url_for("login"))
    if session["tipo"] == "professor":
        return redirect(url_for("dashboard"))
    return redirect(url_for("portal_progresso"))


@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        email = request.form["email"].strip().lower()
        senha = request.form["senha"]

        db = get_db()
        usuario = db.execute("SELECT * FROM usuarios WHERE email = ?", (email,)).fetchone()
        db.close()

        if usuario and check_password_hash(usuario["senha_hash"], senha):
            session["usuario_id"] = usuario["id"]
            session["nome"] = usuario["nome"]
            session["tipo"] = usuario["tipo"]
            flash(f"Bem-vindo(a), {usuario['nome']}!", "sucesso")
            return redirect(url_for("index"))

        flash("E-mail ou senha inválidos.", "erro")

    return render_template("login.html")


@app.route("/logout")
def logout():
    session.clear()
    flash("Você saiu da sua conta.", "sucesso")
    return redirect(url_for("login"))


# =====================================================================
# 4. PAINEL DO PROFESSOR
# =====================================================================

@app.route("/dashboard")
@login_required
@professor_required
def dashboard():
    db = get_db()

    # --- KPIs dos últimos 3 meses -----------------------------------
    limite = (date.today() - timedelta(days=90)).isoformat()

    aulas_realizadas = db.execute(
        "SELECT COUNT(*) AS n FROM agendamentos WHERE status = 'realizada' AND data_aula >= ?",
        (limite,),
    ).fetchone()["n"]

    aulas_canceladas = db.execute(
        "SELECT COUNT(*) AS n FROM agendamentos WHERE status = 'cancelada' AND data_aula >= ?",
        (limite,),
    ).fetchone()["n"]

    aulas_repostas = db.execute(
        "SELECT COUNT(*) AS n FROM frequencia f "
        "JOIN agendamentos a ON a.id = f.agendamento_id "
        "WHERE f.reposta = 1 AND a.data_aula >= ?",
        (limite,),
    ).fetchone()["n"]

    novos_alunos = db.execute(
        "SELECT COUNT(*) AS n FROM alunos WHERE data_inicio >= ?", (limite,)
    ).fetchone()["n"]

    total_marcadas = db.execute(
        "SELECT COUNT(*) AS n FROM frequencia f "
        "JOIN agendamentos a ON a.id = f.agendamento_id WHERE a.data_aula >= ?",
        (limite,),
    ).fetchone()["n"]
    total_presentes = db.execute(
        "SELECT COUNT(*) AS n FROM frequencia f "
        "JOIN agendamentos a ON a.id = f.agendamento_id "
        "WHERE f.presente = 1 AND a.data_aula >= ?",
        (limite,),
    ).fetchone()["n"]
    media_frequencia = round((total_presentes / total_marcadas) * 100) if total_marcadas else 0

    # Para desenhar barras de progresso, calculamos um "máximo" simples
    # entre os contadores de aulas, para as barras ficarem proporcionais.
    maior_valor_aulas = max(aulas_realizadas, aulas_canceladas, aulas_repostas, 1)

    # --- Lista resumida de alunos ativos -----------------------------
    alunos = db.execute(
        """
        SELECT al.id, u.nome, al.nicho,
               (SELECT COUNT(*) FROM atividades at WHERE at.aluno_id = al.id AND at.status = 'pendente') AS pendencias,
               (SELECT status FROM financeiro fi WHERE fi.aluno_id = al.id ORDER BY vencimento DESC LIMIT 1) AS status_financeiro
        FROM alunos al
        JOIN usuarios u ON u.id = al.usuario_id
        WHERE al.ativo = 1
        ORDER BY u.nome
        """
    ).fetchall()

    db.close()

    kpis = {
        "aulas_realizadas": aulas_realizadas,
        "aulas_canceladas": aulas_canceladas,
        "aulas_repostas": aulas_repostas,
        "novos_alunos": novos_alunos,
        "media_frequencia": media_frequencia,
        "maior_valor_aulas": maior_valor_aulas,
    }

    return render_template("dashboard.html", kpis=kpis, alunos=alunos)


@app.route("/alunos/<int:aluno_id>")
@login_required
@professor_required
def aluno_detalhe(aluno_id):
    db = get_db()

    aluno = db.execute(
        "SELECT al.*, u.nome, u.email FROM alunos al "
        "JOIN usuarios u ON u.id = al.usuario_id WHERE al.id = ?",
        (aluno_id,),
    ).fetchone()

    if aluno is None:
        db.close()
        flash("Aluno não encontrado.", "erro")
        return redirect(url_for("dashboard"))

    # Frequência (últimos agendamentos + presença)
    frequencia = db.execute(
        """
        SELECT a.data_aula, a.hora_inicio, a.horario_nobre, a.status AS status_aula,
               f.presente, f.reposta, f.id AS frequencia_id
        FROM agendamentos a
        LEFT JOIN frequencia f ON f.agendamento_id = a.id
        WHERE a.aluno_id = ?
        ORDER BY a.data_aula DESC, a.hora_inicio DESC
        """,
        (aluno_id,),
    ).fetchall()

    # Atividades
    atividades = db.execute(
        "SELECT * FROM atividades WHERE aluno_id = ? ORDER BY data_entrega", (aluno_id,)
    ).fetchall()

    # Conteúdo / progresso do curso
    etapas = db.execute(
        "SELECT * FROM etapas_curso WHERE aluno_id = ? ORDER BY ordem", (aluno_id,)
    ).fetchall()
    total_etapas = len(etapas)
    concluidas = len([e for e in etapas if e["status"] == "concluida"])
    progresso_pct = round((concluidas / total_etapas) * 100) if total_etapas else 0

    # Financeiro
    financeiro = db.execute(
        "SELECT * FROM financeiro WHERE aluno_id = ? ORDER BY vencimento DESC", (aluno_id,)
    ).fetchall()

    db.close()

    return render_template(
        "aluno_detalhe.html",
        aluno=aluno,
        frequencia=frequencia,
        atividades=atividades,
        etapas=etapas,
        progresso_pct=progresso_pct,
        financeiro=financeiro,
        hoje=date.today().isoformat(),
    )


@app.route("/alunos/<int:aluno_id>/frequencia/<int:agendamento_id>/marcar", methods=["POST"])
@login_required
@professor_required
def marcar_frequencia(aluno_id, agendamento_id):
    """Cria ou atualiza o registro de presença/falta de uma aula."""
    presente = 1 if request.form.get("presente") == "on" else 0
    reposta = 1 if request.form.get("reposta") == "on" else 0

    db = get_db()
    existente = db.execute(
        "SELECT id FROM frequencia WHERE agendamento_id = ?", (agendamento_id,)
    ).fetchone()

    if existente:
        db.execute(
            "UPDATE frequencia SET presente = ?, reposta = ? WHERE agendamento_id = ?",
            (presente, reposta, agendamento_id),
        )
    else:
        db.execute(
            "INSERT INTO frequencia (agendamento_id, aluno_id, presente, reposta) VALUES (?, ?, ?, ?)",
            (agendamento_id, aluno_id, presente, reposta),
        )

    # Marca o agendamento correspondente como "realizada"
    db.execute("UPDATE agendamentos SET status = 'realizada' WHERE id = ?", (agendamento_id,))
    db.commit()
    db.close()

    flash("Frequência atualizada.", "sucesso")
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


@app.route("/alunos/<int:aluno_id>/atividades/<int:atividade_id>/status", methods=["POST"])
@login_required
@professor_required
def atualizar_status_atividade(aluno_id, atividade_id):
    novo_status = request.form["status"]
    db = get_db()
    db.execute("UPDATE atividades SET status = ? WHERE id = ?", (novo_status, atividade_id))
    db.commit()
    db.close()
    flash("Status da atividade atualizado.", "sucesso")
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


@app.route("/alunos/<int:aluno_id>/etapas/<int:etapa_id>/concluir", methods=["POST"])
@login_required
@professor_required
def concluir_etapa(aluno_id, etapa_id):
    db = get_db()
    etapa = db.execute("SELECT status FROM etapas_curso WHERE id = ?", (etapa_id,)).fetchone()
    novo_status = "pendente" if etapa["status"] == "concluida" else "concluida"
    data_conclusao = date.today().isoformat() if novo_status == "concluida" else None
    db.execute(
        "UPDATE etapas_curso SET status = ?, data_conclusao = ? WHERE id = ?",
        (novo_status, data_conclusao, etapa_id),
    )
    db.commit()
    db.close()
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


@app.route("/alunos/<int:aluno_id>/financeiro/<int:financeiro_id>/pagar", methods=["POST"])
@login_required
@professor_required
def marcar_pago(aluno_id, financeiro_id):
    db = get_db()
    db.execute(
        "UPDATE financeiro SET status = 'pago', data_pagamento = ? WHERE id = ?",
        (date.today().isoformat(), financeiro_id),
    )
    db.commit()
    db.close()
    flash("Pagamento registrado.", "sucesso")
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


@app.route("/agenda", methods=["GET", "POST"])
@login_required
@professor_required
def agenda():
    db = get_db()

    if request.method == "POST":
        aluno_id = request.form["aluno_id"]
        data_aula = request.form["data_aula"]
        hora_inicio = request.form["hora_inicio"]
        hora_fim = request.form["hora_fim"]
        nobre = 1 if is_horario_nobre(data_aula, hora_inicio) else 0

        db.execute(
            "INSERT INTO agendamentos (aluno_id, data_aula, hora_inicio, hora_fim, horario_nobre, status) "
            "VALUES (?, ?, ?, ?, ?, 'agendada')",
            (aluno_id, data_aula, hora_inicio, hora_fim, nobre),
        )
        db.commit()
        flash("Aula agendada com sucesso." + (" ⭐ Horário nobre!" if nobre else ""), "sucesso")

    alunos = db.execute(
        "SELECT al.id, u.nome FROM alunos al JOIN usuarios u ON u.id = al.usuario_id "
        "WHERE al.ativo = 1 ORDER BY u.nome"
    ).fetchall()

    proximos = db.execute(
        """
        SELECT a.id, a.data_aula, a.hora_inicio, a.hora_fim, a.horario_nobre, a.status, u.nome AS aluno_nome
        FROM agendamentos a
        JOIN alunos al ON al.id = a.aluno_id
        JOIN usuarios u ON u.id = al.usuario_id
        WHERE a.data_aula >= date('now', '-1 day')
        ORDER BY a.data_aula, a.hora_inicio
        """
    ).fetchall()

    db.close()
    return render_template("agenda.html", alunos=alunos, proximos=proximos)


@app.route("/agenda/<int:agendamento_id>/cancelar", methods=["POST"])
@login_required
@professor_required
def cancelar_agendamento(agendamento_id):
    db = get_db()
    db.execute("UPDATE agendamentos SET status = 'cancelada' WHERE id = ?", (agendamento_id,))
    db.commit()
    db.close()
    flash("Aula cancelada.", "sucesso")
    return redirect(url_for("agenda"))


# =====================================================================
# 5. PORTAL DO ALUNO
# =====================================================================

@app.route("/portal/materiais")
@login_required
def portal_materiais():
    aluno_id = get_aluno_logado_id()
    db = get_db()
    materiais = db.execute(
        "SELECT * FROM materiais WHERE aluno_id IS NULL OR aluno_id = ? ORDER BY criado_em DESC",
        (aluno_id,),
    ).fetchall()
    db.close()
    return render_template("portal_materiais.html", materiais=materiais)


@app.route("/portal/aulas")
@login_required
def portal_aulas():
    aluno_id = get_aluno_logado_id()
    db = get_db()
    aulas = db.execute(
        "SELECT * FROM aulas_gravadas WHERE aluno_id IS NULL OR aluno_id = ? ORDER BY data_aula DESC",
        (aluno_id,),
    ).fetchall()
    db.close()
    return render_template("portal_aulas.html", aulas=aulas)


@app.route("/portal/progresso")
@login_required
def portal_progresso():
    aluno_id = get_aluno_logado_id()
    db = get_db()

    etapas = db.execute(
        "SELECT * FROM etapas_curso WHERE aluno_id = ? ORDER BY ordem", (aluno_id,)
    ).fetchall()
    total = len(etapas)
    concluidas = len([e for e in etapas if e["status"] == "concluida"])
    progresso_pct = round((concluidas / total) * 100) if total else 0

    atividades_pendentes = db.execute(
        "SELECT * FROM atividades WHERE aluno_id = ? AND status != 'entregue' ORDER BY data_entrega",
        (aluno_id,),
    ).fetchall()

    proxima_aula = db.execute(
        "SELECT * FROM agendamentos WHERE aluno_id = ? AND status = 'agendada' "
        "AND data_aula >= date('now') ORDER BY data_aula, hora_inicio LIMIT 1",
        (aluno_id,),
    ).fetchone()

    db.close()

    return render_template(
        "portal_progresso.html",
        etapas=etapas,
        progresso_pct=progresso_pct,
        atividades_pendentes=atividades_pendentes,
        proxima_aula=proxima_aula,
    )


# =====================================================================
# EXECUÇÃO
# =====================================================================

if __name__ == "__main__":
    # Se o banco ainda não existe, cria automaticamente na primeira vez.
    if not os.path.exists(DATABASE_PATH):
        init_db()
    app.run(debug=True)
