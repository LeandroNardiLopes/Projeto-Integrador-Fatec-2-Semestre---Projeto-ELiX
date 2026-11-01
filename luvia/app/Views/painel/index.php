<?php /** Painel da mentora (RF30, RF31, RF32). */ ?>
<section class="kpis" aria-label="Indicadores">
  <div class="kpi"><a href="<?= e(url('alunos')) ?>"><div class="rotulo">Alunos ativos</div><div class="valor"><?= (int)($resumo['alunos_ativos'] ?? 0) ?></div></a></div>
  <div class="kpi"><a href="<?= e(url('agenda')) ?>"><div class="rotulo">Aulas agendadas nesta semana</div><div class="valor"><?= (int)($resumo['aulas_semana'] ?? 0) ?></div></a></div>
  <div class="kpi"><div class="rotulo">A receber</div><div class="valor"><?= e(moeda($resumo['valor_a_receber'] ?? 0)) ?></div></div>
  <div class="kpi <?= ($resumo['parcelas_atrasadas'] ?? 0) > 0 ? 'alerta' : '' ?>"><div class="rotulo">Parcelas atrasadas</div><div class="valor"><?= (int)($resumo['parcelas_atrasadas'] ?? 0) ?></div></div>
  <div class="kpi <?= ($resumo['atividades_atrasadas'] ?? 0) > 0 ? 'alerta' : '' ?>"><div class="rotulo">Atividades atrasadas</div><div class="valor"><?= (int)($resumo['atividades_atrasadas'] ?? 0) ?></div></div>
  <div class="kpi"><?php if (modulo_ativo('leads')): ?><a href="<?= e(url('leads')) ?>"><?php endif; ?>
    <div class="rotulo">Leads em aberto</div><div class="valor"><?= (int)($resumo['leads_em_aberto'] ?? 0) ?></div>
    <?php if (modulo_ativo('leads')): ?></a><?php endif; ?></div>
</section>

<div class="grade-2">
  <section class="cartao">
    <div class="cartao-cab"><h2>Hoje</h2><span class="suave"><?= e(data_curta(date('Y-m-d'))) ?></span></div>
    <?php if (!$hoje): ?>
      <p class="vazio">Nenhum compromisso para hoje.</p>
    <?php else: ?>
      <ul class="lista">
        <?php foreach ($hoje as $h): ?>
          <li><span><strong><?= e(hora_br($h['hora_inicio'])) ?></strong> · <?= e($h['pessoa']) ?>
            <span class="meta"><?= e($h['tipo']) ?></span></span>
            <?= $h['horario_nobre'] ? selo('horário nobre', 'nobre') : '' ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h3>Alertas do dia</h3>
    <?php $semAlerta = !$alertas['vencimentos'] && !$alertas['atividades'] && !$alertas['etapas']; ?>
    <?php if ($semAlerta): ?><p class="vazio">Tudo em dia.</p><?php endif; ?>
    <ul class="lista">
      <?php foreach ($alertas['vencimentos'] as $v): ?>
        <li><span><?= e($v['aluno']) ?> · <?= e($v['descricao']) ?>
          <span class="meta">vence em <?= e(data_br($v['vencimento'])) ?> · <?= e(moeda($v['valor'])) ?></span></span>
          <?= $v['status'] === 'atrasado' ? selo($v['dias_atraso'] . ' dia(s) de atraso', 'erro') : selo('a vencer', 'aviso') ?></li>
      <?php endforeach; ?>
      <?php foreach ($alertas['atividades'] as $a): ?>
        <li><span><a href="<?= e(url('alunos', 'ver', ['id' => $a['aluno_id']])) ?>"><?= e($a['aluno']) ?></a> · <?= e($a['titulo']) ?>
          <span class="meta">prazo <?= e(data_br($a['data_entrega'])) ?></span></span><?= selo('atividade atrasada', 'erro') ?></li>
      <?php endforeach; ?>
      <?php foreach ($alertas['etapas'] as $t): ?>
        <li><span><a href="<?= e(url('alunos', 'ver', ['id' => $t['aluno_id']])) ?>"><?= e($t['aluno']) ?></a> · semana <?= (int)$t['semana'] ?>: <?= e($t['titulo']) ?></span>
          <?= selo('etapa atrasada', 'aviso') ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="cartao">
    <div class="cartao-cab"><h2>Próximos compromissos</h2><a href="<?= e(url('agenda')) ?>">ver agenda</a></div>
    <?php if (!$proximas): ?>
      <p class="vazio">Nada agendado.</p>
    <?php else: ?>
      <ul class="lista">
        <?php foreach ($proximas as $p): ?>
          <li><span><strong><?= e(data_curta($p['data_aula'])) ?></strong> <?= e(hora_br($p['hora_inicio'])) ?> · <?= e($p['pessoa']) ?>
            <span class="meta"><?= e($p['tipo']) ?><?= $p['observacao'] ? ' · ' . e($p['observacao']) : '' ?></span></span>
            <?= $p['horario_nobre'] ? selo('horário nobre', 'nobre') : '' ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<div class="grade-2">
  <section class="cartao">
    <h2>Últimos 3 meses</h2>
    <?php
      $total = max(1, (int)$est['realizadas'] + (int)$est['canceladas']);
      $linhas = [
        ['Aulas realizadas', (int)$est['realizadas'], 100 * (int)$est['realizadas'] / $total, ''],
        ['Aulas canceladas', (int)$est['canceladas'], 100 * (int)$est['canceladas'] / $total, 'amarela'],
      ];
    ?>
    <?php foreach ($linhas as [$rot, $n, $p, $cls]): ?>
      <div class="linha-barra"><span><?= e($rot) ?></span><div class="barra <?= $cls ?>"><span style="width: <?= (int)round($p) ?>%"></span></div><strong class="num"><?= $n ?></strong></div>
    <?php endforeach; ?>
    <div class="linha-barra"><span>Frequência média</span><div class="barra"><span style="width: <?= (int)round((float)($est['frequencia_media'] ?? 0)) ?>%"></span></div><strong class="num"><?= e(pct($est['frequencia_media'])) ?></strong></div>
    <dl class="dados" style="margin-top:12px">
      <dt>Reposições realizadas</dt><dd><?= (int)$est['repostas'] ?></dd>
      <dt>Novos alunos</dt><dd><?= (int)$est['novos_alunos'] ?></dd>
    </dl>
  </section>

  <section class="cartao">
    <h2>Progresso dos alunos</h2>
    <?php if (!$est['progresso']): ?><p class="vazio">Nenhum aluno ativo.</p><?php endif; ?>
    <?php foreach ($est['progresso'] as $pg): ?>
      <div class="linha-barra">
        <a href="<?= e(url('alunos', 'ver', ['id' => $pg['aluno_id']])) ?>"><?= e($pg['aluno']) ?></a>
        <div class="barra"><span style="width: <?= (int)round((float)$pg['percentual_progresso']) ?>%"></span></div>
        <strong class="num"><?= e(pct($pg['percentual_progresso'])) ?></strong>
      </div>
    <?php endforeach; ?>
  </section>
</div>
