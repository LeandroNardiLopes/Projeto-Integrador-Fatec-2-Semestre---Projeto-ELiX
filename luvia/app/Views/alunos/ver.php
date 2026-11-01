<?php
/** Ficha do aluno: dados, cronograma do curso, aulas e frequência e, na versão completa, atividades, financeiro, materiais e diário (RF08). */
$id = (int)$aluno['id'];
$temEtapas = modulo_ativo('etapas');
$concluidas = count(array_filter($etapas, fn($e) => $e['status'] === 'concluida'));
$progresso = $etapas ? 100 * $concluidas / count($etapas) : 0;
?>
<nav class="abas" aria-label="Seções da ficha">
  <a href="#dados">Dados</a><a href="#cronograma">Curso e cronograma</a><a href="#aulas">Aulas e frequência</a>
  <?php if (isset($atividades)): ?><a href="#atividades">Atividades</a><?php endif; ?>
  <?php if (isset($parcelas)): ?><a href="#financeiro">Financeiro</a><?php endif; ?>
  <?php if (isset($materiais)): ?><a href="#materiais">Materiais</a><?php endif; ?>
  <?php if (isset($diario)): ?><a href="#diario">Diário de bordo</a><?php endif; ?>
</nav>

<section class="cartao" id="dados">
  <div class="ficha-cab">
    <div>
      <h2><?= e($aluno['nome']) ?> <?= $aluno['ativo'] ? selo('ativo', 'ok') : selo('inativo', 'neutro') ?></h2>
      <dl class="dados">
        <dt>Curso</dt><dd><?= e($aluno['curso'] ?? 'Ainda sem curso') ?><?= $aluno['modalidade'] ? ' · ' . e(MODALIDADES[$aluno['modalidade']]) : '' ?></dd>
        <dt>Período</dt><dd><?= e(data_br($aluno['data_inicio'])) ?> a <?= e(data_br($aluno['data_fim_prevista'])) ?></dd>
        <dt>Nicho</dt><dd><?= e($aluno['nicho'] ?? '—') ?></dd>
        <dt>Contato</dt><dd><?= e($aluno['email'] ?? '—') ?><?= $aluno['telefone'] ? ' · ' . e($aluno['telefone']) : '' ?></dd>
        <dt>Contrato</dt><dd><?= $aluno['contrato_link'] ? '<a href="' . e($aluno['contrato_link']) . '" target="_blank" rel="noopener noreferrer">abrir contrato</a>' : '—' ?></dd>
        <dt>Nível de fluência</dt><dd><?= e(NIVEIS[$aluno['nivel_inicial']] ?? '—') ?> → <?= e(NIVEIS[$aluno['nivel_alvo']] ?? '—') ?></dd>
        <dt>Cena</dt><dd><?= e($aluno['cena_objetivo'] ?? '—') ?><?= $aluno['data_cena'] ? ' (' . e(data_br($aluno['data_cena'])) . ')' : '' ?></dd>
        <?php if (!$aluno['ativo']): ?>
          <dt>Inativado em</dt><dd><?= e(data_br($aluno['data_inativacao'])) ?> · <?= e($aluno['motivo_inativacao'] ?? '') ?></dd>
        <?php endif; ?>
      </dl>
    </div>
    <div style="min-width:220px">
      <div class="kpi"><div class="rotulo">Progresso do curso</div><div class="valor"><?= e(pct($progresso)) ?></div>
        <div class="barra"><span style="width: <?= (int)round($progresso) ?>%"></span></div></div>
      <div class="kpi" style="margin-top:10px"><div class="rotulo">Frequência</div><div class="valor"><?= e(pct($frequencia['percentual_frequencia'] ?? null)) ?></div>
        <small><?= (int)($frequencia['presencas'] ?? 0) ?> presenças · <?= (int)($frequencia['faltas'] ?? 0) ?> faltas · <?= (int)($frequencia['faltas_repostas'] ?? 0) ?> repostas</small></div>
      <div style="margin-top:10px">
        <?php if ($aluno['ativo']): ?>
          <details class="inline">
            <summary class="btn btn-pequeno btn-perigo">Inativar aluno</summary>
            <div class="painel-form">
              <form method="post" action="<?= e(url('alunos', 'inativar')) ?>" class="form">
                <?= csrf_campo() ?><input type="hidden" name="id" value="<?= $id ?>">
                <label>Motivo *<input type="text" name="motivo" required maxlength="255"></label>
                <small>O histórico é mantido e as aulas futuras são canceladas.</small>
                <button class="btn btn-perigo" type="submit">Confirmar inativação</button>
              </form>
            </div>
          </details>
        <?php else: ?>
          <form method="post" action="<?= e(url('alunos', 'reativar')) ?>" class="em-linha">
            <?= csrf_campo() ?><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn btn-pequeno" type="submit">Reativar aluno</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="cartao" id="cronograma">
  <div class="cartao-cab"><h2>Curso e cronograma</h2>
    <?php if ($etapas): ?><span class="suave"><?= $concluidas ?> de <?= count($etapas) ?> semanas concluídas</span><?php endif; ?></div>

  <?php if (!$etapas): ?>
    <p class="vazio">Este aluno ainda não tem um curso montado.</p>
    <?php if ($temEtapas && $aluno['ativo']): ?>
      <form method="post" action="<?= e(url('etapas', 'aplicar')) ?>" class="form-grade">
        <?= csrf_campo() ?><input type="hidden" name="aluno_id" value="<?= $id ?>">
        <label>Curso
          <select name="curso_id" required>
            <?php foreach ($cursos as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>Início do curso<input type="date" name="data_inicio" required value="<?= e(date('Y-m-d')) ?>"></label>
        <div style="align-self:end"><button class="btn btn-destaque" type="submit">Montar o curso</button></div>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela cronograma">
        <thead><tr><th>Semana</th><th>Tema</th><th>Módulo</th><th>Período</th><th>Situação</th><?php if ($temEtapas): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($etapas as $et):
          $tipoSelo = ['concluída' => 'ok', 'semana atual' => 'nobre', 'atrasada' => 'erro', 'prevista' => 'neutro', 'sem data' => 'neutro'][$et['situacao']] ?? 'neutro'; ?>
          <tr class="<?= $et['situacao'] === 'semana atual' ? 'semana-atual' : '' ?>">
            <td class="semana-num"><?= $et['semana'] !== null ? (int)$et['semana'] : '—' ?></td>
            <td><?= e($et['titulo']) ?></td>
            <td><small><?= e($et['modulo'] ?? '') ?></small></td>
            <td><small><?= $et['data_prevista'] ? e(data_br($et['data_prevista']) . ' a ' . data_br($et['fim_semana'])) : '—' ?></small></td>
            <td><?= selo($et['situacao'], $tipoSelo) ?></td>
            <?php if ($temEtapas): ?>
            <td class="num">
              <span class="botoes">
                <?php if ($et['status'] === 'pendente'): ?>
                  <form class="em-linha" method="post" action="<?= e(url('etapas', 'concluir')) ?>"><?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= (int)$et['id'] ?>"><button class="btn btn-pequeno" type="submit">Concluir</button></form>
                <?php else: ?>
                  <form class="em-linha" method="post" action="<?= e(url('etapas', 'reabrir')) ?>"><?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= (int)$et['id'] ?>"><button class="btn btn-pequeno" type="submit">Reabrir</button></form>
                <?php endif; ?>
                <form class="em-linha" method="post" action="<?= e(url('etapas', 'mover')) ?>"><?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= (int)$et['id'] ?>"><input type="hidden" name="direcao" value="subir">
                  <button class="btn btn-pequeno" type="submit" title="Subir">↑</button></form>
                <form class="em-linha" method="post" action="<?= e(url('etapas', 'mover')) ?>"><?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= (int)$et['id'] ?>"><input type="hidden" name="direcao" value="descer">
                  <button class="btn btn-pequeno" type="submit" title="Descer">↓</button></form>
                <details class="inline">
                  <summary class="btn btn-pequeno">Editar</summary>
                  <div class="painel-form">
                    <form method="post" action="<?= e(url('etapas', 'salvar')) ?>" class="form"><?= csrf_campo() ?>
                      <input type="hidden" name="id" value="<?= (int)$et['id'] ?>"><input type="hidden" name="aluno_id" value="<?= $id ?>">
                      <label>Tema<input type="text" name="titulo" required maxlength="160" value="<?= e($et['titulo']) ?>"></label>
                      <label>Início da semana<input type="date" name="data_prevista" value="<?= e($et['data_prevista'] ?? '') ?>"></label>
                      <button class="btn btn-destaque" type="submit">Salvar</button>
                    </form>
                    <form method="post" action="<?= e(url('etapas', 'excluir')) ?>" style="margin-top:8px"><?= csrf_campo() ?>
                      <input type="hidden" name="id" value="<?= (int)$et['id'] ?>">
                      <button class="btn btn-pequeno btn-perigo" type="submit">Excluir esta etapa</button>
                    </form>
                  </div>
                </details>
              </span>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($temEtapas): ?>
      <details class="bloco" style="margin-top:12px">
        <summary>+ Adicionar etapa personalizada</summary>
        <form method="post" action="<?= e(url('etapas', 'salvar')) ?>" class="form-grade"><?= csrf_campo() ?>
          <input type="hidden" name="aluno_id" value="<?= $id ?>">
          <label>Tema<input type="text" name="titulo" required maxlength="160"></label>
          <label>Início da semana<input type="date" name="data_prevista"></label>
          <div style="align-self:end"><button class="btn btn-destaque" type="submit">Adicionar</button></div>
        </form>
      </details>
    <?php endif; ?>
  <?php endif; ?>
</section>

<section class="cartao" id="aulas">
  <div class="cartao-cab"><h2>Aulas e frequência</h2>
    <?php if ($aluno['ativo']): ?><a class="btn btn-pequeno" href="<?= e(url('agenda', 'novo', ['aluno' => $id])) ?>">+ Agendar aula</a><?php endif; ?></div>
  <?php if (!$aulas): ?>
    <p class="vazio">Nenhuma aula registrada.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <thead><tr><th>Data</th><th>Horário</th><th>Tipo</th><th>Situação</th><th>Frequência e ações</th></tr></thead>
        <tbody>
        <?php foreach ($aulas as $ag): ?>
          <tr>
            <td><?= e(data_curta($ag['data_aula'])) ?></td>
            <td><?= e(hora_br($ag['hora_inicio']) . '–' . hora_br($ag['hora_fim'])) ?> <?= $ag['horario_nobre'] ? selo('nobre', 'nobre') : '' ?></td>
            <td><?= e($ag['tipo'] === 'reposicao' ? 'reposição' : $ag['tipo']) ?></td>
            <td><?= e($ag['status']) ?><?= $ag['motivo_cancelamento'] ? '<div class="suave">' . e($ag['motivo_cancelamento']) . '</div>' : '' ?></td>
            <td><?php require BASE_PATH . '/app/Views/agenda/_acoes.php'; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php if (isset($atividades)) { require BASE_PATH . '/app/Views/alunos/_atividades.php'; } ?>
<?php if (isset($parcelas)) { require BASE_PATH . '/app/Views/alunos/_financeiro.php'; } ?>
<?php if (isset($materiais)) { require BASE_PATH . '/app/Views/alunos/_materiais.php'; } ?>
<?php if (isset($diario)) { require BASE_PATH . '/app/Views/alunos/_diario.php'; } ?>
