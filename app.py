"""
app.py — ELIX (PROTÓTIPO SIMPLIFICADO)
=====================================================================
Esta é uma versão reduzida do Elix, feita para DEMONSTRAÇÃO do
frontend e do backend já conversando entre si.

O que É funcional aqui:
  - O servidor Flask (backend) de verdade;
  - As rotas, o login e a navegação entre páginas;
  - Os formulários que enviam dados para o backend e o backend
    que responde atualizando a tela.

O que NÃO é funcional (de propósito, para simplificar):
  - Não existe banco de dados de verdade. Os "dados" vêm do
    arquivo dados_simulados.py, guardados em memória.
  - Qualquer alteração feita pela tela (marcar presença, marcar
    atividade como entregue etc.) É aplicada de verdade nos
    dados em memória — então você VAI ver a tela mudar — mas
    tudo volta ao estado original quando o servidor reinicia.

Use este protótipo para mostrar rapidamente como as telas
funcionam, sem precisar configurar banco de dados nenhum.
=====================================================================
"""

from flask import Flask, render_template, request, redirect, url_for, session, flash
from functools import wraps

import dados_simulados as db  # "banco de dados" simulado (ver arquivo dados_simulados.py)

app = Flask(__name__)
app.secret_key = "chave-simples-so-para-o-prototipo"


# ---------------------------------------------------------------
# Funções auxiliares
# ---------------------------------------------------------------

def login_required(f):
    @wraps(f)
    def wrapped(*args, **kwargs):
        if "email" not in session:
            flash("Faça login para continuar.", "erro")
            return redirect(url_for("login"))
        return f(*args, **kwargs)
    return wrapped


def professor_required(f):
    @wraps(f)
    def wrapped(*args, **kwargs):
        if session.get("tipo") != "professor":
            flash("Acesso restrito à equipe Elix.", "erro")
            return redirect(url_for("portal"))
        return f(*args, **kwargs)
    return wrapped


@app.context_processor
def inject_globals():
    return {"usuario_nome": session.get("nome"), "usuario_tipo": session.get("tipo")}


# ---------------------------------------------------------------
# Autenticação
# ---------------------------------------------------------------

@app.route("/")
def index():
    if "email" not in session:
        return redirect(url_for("login"))
    return redirect(url_for("dashboard") if session["tipo"] == "professor" else url_for("portal"))


@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        email = request.form["email"].strip().lower()
        senha = request.form["senha"]

        usuario = db.usuarios.get(email)
        # Comparação simples de senha em texto — OK só porque isso é um protótipo demonstrativo.
        if usuario and usuario["senha"] == senha:
            session["email"] = email
            session["nome"] = usuario["nome"]
            session["tipo"] = usuario["tipo"]
            session["aluno_id"] = usuario.get("aluno_id")
            flash(f"Bem-vindo(a), {usuario['nome']}!", "sucesso")
            return redirect(url_for("index"))

        flash("E-mail ou senha inválidos.", "erro")

    return render_template("login.html")


@app.route("/logout")
def logout():
    session.clear()
    flash("Você saiu da sua conta.", "sucesso")
    return redirect(url_for("login"))


# ---------------------------------------------------------------
# Painel do professor (dados calculados a partir da memória)
# ---------------------------------------------------------------

@app.route("/dashboard")
@login_required
@professor_required
def dashboard():
    aulas_realizadas = len([f for f in db.frequencia if f["presente"]])
    aulas_faltas = len([f for f in db.frequencia if not f["presente"]])
    total_frequencia = len(db.frequencia)
    media_frequencia = round((aulas_realizadas / total_frequencia) * 100) if total_frequencia else 0

    lista_alunos = []
    for aluno in db.alunos.values():
        pendencias = len([a for a in db.atividades if a["aluno_id"] == aluno["id"] and a["status"] != "entregue"])
        fin = [f for f in db.financeiro if f["aluno_id"] == aluno["id"]]
        status_fin = fin[-1]["status"] if fin else "pendente"
        lista_alunos.append({**aluno, "pendencias": pendencias, "status_financeiro": status_fin})

    kpis = {
        "aulas_realizadas": aulas_realizadas,
        "aulas_faltas": aulas_faltas,
        "media_frequencia": media_frequencia,
        "total_alunos": len(db.alunos),
    }
    return render_template("dashboard.html", kpis=kpis, alunos=lista_alunos)


@app.route("/alunos/<int:aluno_id>")
@login_required
@professor_required
def aluno_detalhe(aluno_id):
    aluno = db.alunos.get(aluno_id)
    if not aluno:
        flash("Aluno não encontrado.", "erro")
        return redirect(url_for("dashboard"))

    freq = [f for f in db.frequencia if f["aluno_id"] == aluno_id]
    ativ = [a for a in db.atividades if a["aluno_id"] == aluno_id]
    etapas = [e for e in db.etapas_curso if e["aluno_id"] == aluno_id]
    fin = [f for f in db.financeiro if f["aluno_id"] == aluno_id]

    total = len(etapas)
    concluidas = len([e for e in etapas if e["status"] == "concluida"])
    progresso_pct = round((concluidas / total) * 100) if total else 0

    return render_template(
        "aluno_detalhe.html", aluno=aluno, frequencia=freq, atividades=ativ,
        etapas=etapas, progresso_pct=progresso_pct, financeiro=fin,
    )


@app.route("/alunos/<int:aluno_id>/etapas/<int:etapa_id>/concluir", methods=["POST"])
@login_required
@professor_required
def concluir_etapa(aluno_id, etapa_id):
    # Atualiza o dado diretamente na lista em memória (não existe "UPDATE" de banco aqui).
    for etapa in db.etapas_curso:
        if etapa["id"] == etapa_id:
            etapa["status"] = "pendente" if etapa["status"] == "concluida" else "concluida"
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


@app.route("/alunos/<int:aluno_id>/atividades/<int:atividade_id>/status", methods=["POST"])
@login_required
@professor_required
def atualizar_status_atividade(aluno_id, atividade_id):
    novo_status = request.form["status"]
    for atividade in db.atividades:
        if atividade["id"] == atividade_id:
            atividade["status"] = novo_status
    flash("Status da atividade atualizado (apenas em memória).", "sucesso")
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


@app.route("/alunos/<int:aluno_id>/financeiro/<int:financeiro_id>/pagar", methods=["POST"])
@login_required
@professor_required
def marcar_pago(aluno_id, financeiro_id):
    for lancamento in db.financeiro:
        if lancamento["id"] == financeiro_id:
            lancamento["status"] = "pago"
    flash("Pagamento marcado como pago (apenas em memória).", "sucesso")
    return redirect(url_for("aluno_detalhe", aluno_id=aluno_id))


# ---------------------------------------------------------------
# Portal do aluno (materiais + aulas + progresso em uma só página)
# ---------------------------------------------------------------

@app.route("/portal")
@login_required
def portal():
    aluno_id = session.get("aluno_id")

    materiais = [m for m in db.materiais if m["aluno_id"] is None or m["aluno_id"] == aluno_id]
    aulas = [a for a in db.aulas_gravadas if a["aluno_id"] is None or a["aluno_id"] == aluno_id]
    etapas = [e for e in db.etapas_curso if e["aluno_id"] == aluno_id]
    ativ_pendentes = [a for a in db.atividades if a["aluno_id"] == aluno_id and a["status"] != "entregue"]

    total = len(etapas)
    concluidas = len([e for e in etapas if e["status"] == "concluida"])
    progresso_pct = round((concluidas / total) * 100) if total else 0

    return render_template(
        "portal.html", materiais=materiais, aulas=aulas, etapas=etapas,
        progresso_pct=progresso_pct, atividades_pendentes=ativ_pendentes,
    )


if __name__ == "__main__":
    app.run(debug=True, port=5001)
