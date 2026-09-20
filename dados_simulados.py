"""
dados_simulados.py
=====================================================================
ATENÇÃO: Este arquivo é o "banco de dados" deste PROTÓTIPO.
Ele NÃO é um banco de dados de verdade — é só um conjunto de
listas e dicionários Python guardados na memória do programa.

Isso significa:
  - Os dados aqui são só para DEMONSTRAÇÃO da tela funcionando.
  - Toda vez que você reiniciar o servidor (python app.py),
    os dados voltam ao estado inicial definido neste arquivo.
  - Nada é salvo em disco.

Esse é o objetivo deste protótipo: mostrar o FRONTEND e o
BACKEND (Flask) já funcionando e conversando entre si, sem a
complexidade extra de configurar um banco de dados de verdade.
A versão completa (com SQLite) está no outro projeto entregue.
=====================================================================
"""

# --------------------------------------------------------------
# "Tabela" de usuários (login). Em um sistema real, a senha nunca
# ficaria em texto puro como aqui — isso é só para o protótipo.
# --------------------------------------------------------------
usuarios = {
    "professor@elix.com": {"id": 1, "nome": "Prof. Ana Beatriz", "senha": "123456", "tipo": "professor"},
    "carlos@aluno.com":   {"id": 2, "nome": "Carlos Mendes",      "senha": "123456", "tipo": "aluno", "aluno_id": 1},
    "juliana@aluno.com":  {"id": 3, "nome": "Juliana Prado",      "senha": "123456", "tipo": "aluno", "aluno_id": 2},
}

# --------------------------------------------------------------
# "Tabela" de alunos
# --------------------------------------------------------------
alunos = {
    1: {
        "id": 1, "nome": "Carlos Mendes", "nicho": "Advocacia",
        "contrato_link": "https://exemplo.com/contrato-carlos.pdf",
    },
    2: {
        "id": 2, "nome": "Juliana Prado", "nicho": "Tecnologia (TI)",
        "contrato_link": "https://exemplo.com/contrato-juliana.pdf",
    },
}

# --------------------------------------------------------------
# Materiais (e-books/PDFs). aluno_id None = material geral
# --------------------------------------------------------------
materiais = [
    {"aluno_id": None, "titulo": "E-book: Fundamentos do Método Indutivo", "tipo": "E-book", "link": "https://exemplo.com/ebook-metodo.pdf"},
    {"aluno_id": 1,    "titulo": "Glossário Jurídico Bilíngue",             "tipo": "PDF",    "link": "https://exemplo.com/glossario-juridico.pdf"},
    {"aluno_id": 2,    "titulo": "Vocabulário Técnico de TI",               "tipo": "PDF",    "link": "https://exemplo.com/vocab-ti.pdf"},
]

# --------------------------------------------------------------
# Aulas gravadas. aluno_id None = aula geral
# --------------------------------------------------------------
aulas_gravadas = [
    {"aluno_id": None, "titulo": "Aula 01 - Boas-vindas ao Método Indutivo",        "link": "https://exemplo.com/aula01",        "data": "01/08/2026"},
    {"aluno_id": 1,    "titulo": "Aula 02 - Apresentações em Reuniões (Advocacia)", "link": "https://exemplo.com/aula02-carlos", "data": "20/08/2026"},
    {"aluno_id": 2,    "titulo": "Aula 02 - Vocabulário de Daily Meetings",         "link": "https://exemplo.com/aula02-juliana","data": "25/08/2026"},
]

# --------------------------------------------------------------
# Etapas do curso (progresso individual por aluno)
# --------------------------------------------------------------
etapas_curso = [
    {"id": 1, "aluno_id": 1, "titulo": "Diagnóstico de nível inicial",       "status": "concluida"},
    {"id": 2, "aluno_id": 1, "titulo": "Fundamentos de pronúncia",           "status": "concluida"},
    {"id": 3, "aluno_id": 1, "titulo": "Vocabulário jurídico essencial",     "status": "concluida"},
    {"id": 4, "aluno_id": 1, "titulo": "Simulações de reunião em inglês",    "status": "pendente"},
    {"id": 5, "aluno_id": 1, "titulo": "Apresentação final",                 "status": "pendente"},
    {"id": 6, "aluno_id": 2, "titulo": "Diagnóstico de nível inicial",       "status": "concluida"},
    {"id": 7, "aluno_id": 2, "titulo": "Fundamentos de pronúncia",           "status": "pendente"},
    {"id": 8, "aluno_id": 2, "titulo": "Vocabulário técnico de TI",          "status": "pendente"},
]

# --------------------------------------------------------------
# Frequência (aulas já dadas): presença/falta por aluno
# --------------------------------------------------------------
frequencia = [
    {"aluno_id": 1, "data": "12/09/2026", "hora": "09:00", "presente": True,  "nobre": False},
    {"aluno_id": 1, "data": "16/09/2026", "hora": "19:30", "presente": False, "nobre": True},
    {"aluno_id": 2, "data": "15/09/2026", "hora": "07:30", "presente": True,  "nobre": True},
]

# --------------------------------------------------------------
# Atividades propostas
# --------------------------------------------------------------
atividades = [
    {"id": 1, "aluno_id": 1, "titulo": "Redigir e-mail formal de follow-up",       "prazo": "22/09/2026", "status": "pendente"},
    {"id": 2, "aluno_id": 1, "titulo": "Gravar áudio de apresentação pessoal",     "prazo": "17/09/2026", "status": "entregue"},
    {"id": 3, "aluno_id": 2, "titulo": "Lista de vocabulário técnico",            "prazo": "18/09/2026", "status": "atrasada"},
]

# --------------------------------------------------------------
# Financeiro
# --------------------------------------------------------------
financeiro = [
    {"id": 1, "aluno_id": 1, "descricao": "Mensalidade - Mês 1", "valor": 450.00, "vencimento": "20/07/2026", "status": "pago"},
    {"id": 2, "aluno_id": 1, "descricao": "Mensalidade - Mês 2", "valor": 450.00, "vencimento": "20/08/2026", "status": "pago"},
    {"id": 3, "aluno_id": 1, "descricao": "Mensalidade - Mês 3", "valor": 450.00, "vencimento": "20/09/2026", "status": "pendente"},
    {"id": 4, "aluno_id": 2, "descricao": "Mensalidade - Mês 1", "valor": 480.00, "vencimento": "14/09/2026", "status": "atrasado"},
]
