# Luvia · Gestão de Mentoria (antigo ELiX)

Projeto Integrador (PI-2) · DSM · Fatec Itapira · 2º semestre de 2026.
Integrantes: Bruno Henrique, Leandro Nardi, Felippe Silva, Eliabe Miguel, Pedro Rogerio e Isac Gomes.

> **Versão atual: Luvia 2.0 = versão 2 (completa), na pasta [`luvia/`](luvia/).**
> HTML, CSS, PHP 8 e MySQL 8 / MariaDB (XAMPP), arquitetura MVC.

| O que | Onde |
|---|---|
| Código do sistema (versão 2 completa) | [`luvia/`](luvia/) |
| Como instalar e testar | [`luvia/README.md`](luvia/README.md) |
| Banco de dados (tabelas, views, procedures e dados fictícios) | [`luvia/database/luvia_mysql.sql`](luvia/database/luvia_mysql.sql) |
| Documentação técnica v2 (Word e PDF) | [`luvia/docs/`](luvia/docs/) |
| Diagrama de Casos de Uso (draw.io, PNG e PDF da ESI) | [`luvia/docs/casos-de-uso/`](luvia/docs/casos-de-uso/) |
| Levantamento de requisitos | [`Levantamento de Requisitos`](Levantamento%20de%20Requisitos) |
| Histórico de versões | [`CHANGELOG.md`](CHANGELOG.md) |
| Versão 1 (só telas principais) | release/tag `Luvia1.4.0` |
| Protótipo inicial em Python/Flask (só histórico do estudo) | [`prototipo-antigo/`](prototipo-antigo/) |

---

## Histórico: protótipo inicial (pasta `prototipo-antigo/`)

### Elix — Protótipo Simplificado (front-end + back-end, sem banco real)

Esta é uma versão **reduzida e demonstrativa** do Elix. O objetivo dela é mostrar
o front-end (HTML + Tailwind) e o back-end (Flask) já funcionando e conversando
entre si — **sem** a etapa de configurar um banco de dados de verdade.

## O que É funcional
- O servidor Flask, as rotas e a navegação entre as páginas;
- O login (com as contas de exemplo abaixo);
- Os formulários (marcar presença/etapa concluída/atividade entregue/pagamento) —
  eles realmente chamam o backend e atualizam o que aparece na tela.

## O que NÃO é funcional (de propósito)
- **Não há banco de dados.** Os dados ficam em `dados_simulados.py`, guardados
  em listas/dicionários Python na memória do programa.
- Qualquer alteração feita pela tela é aplicada só naquela execução do servidor.
  **Ao reiniciar (`python app.py`), tudo volta ao estado inicial.**

Essa é a diferença central para a versão completa (que usa SQLite de verdade e
guarda os dados em um arquivo `.db` no disco, mesmo depois de reiniciar).

## Estrutura

```
elix-prototype/
├── app.py                 → rotas Flask
├── dados_simulados.py      → "banco de dados" em memória (o coração deste protótipo)
└── templates/
    ├── base.html
    ├── login.html
    ├── dashboard.html
    ├── aluno_detalhe.html
    └── portal.html          → materiais + aulas + progresso do aluno, juntos
```

## Como rodar

```bash
cd elix-prototype
pip install flask
python app.py
```

Acesse: **http://127.0.0.1:5001** (porta diferente da versão completa, para
poder rodar os dois protótipos ao mesmo tempo, se quiser comparar).

## Contas de exemplo (senha: `123456`)

| Perfil     | E-mail               |
|------------|-----------------------|
| Professor  | professor@elix.com    |
| Aluno 1    | carlos@aluno.com      |
| Aluno 2    | juliana@aluno.com     |

## Próximo passo natural

Quando quiser evoluir este protótipo para a versão com persistência real,
use o outro projeto entregue (pasta `elix/`), que já implementa exatamente
as mesmas telas, mas salvando tudo em SQLite.
