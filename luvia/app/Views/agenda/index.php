<?php
/** Agenda da semana (RF17), com horário nobre destacado (RF14). */
$anterior = $segunda->modify('-7 days')->format('Y-m-d');
$proxima = $segunda->modify('+7 days')->format('Y-m-d');
$domingo = $segunda->modify('+6 days');
$nomes = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
$hojeStr = date('Y-m-d');
?>
<div class="semana-nav">
  <a class="btn btn-pequeno" href="<?= e(url('agenda', null, ['semana' => $anterior])) ?>">← Semana anterior</a>
  <strong><?= e($segunda->format('d/m')) ?> a <?= e($domingo->format('d/m/Y')) ?></strong>
  <a class="btn btn-pequeno" href="<?= e(url('agenda', null, ['semana' => $proxima])) ?>">Próxima semana →</a>
  <a class="btn btn-pequeno" href="<?= e(url('agenda')) ?>">Hoje</a>
  <span class="suave"><?= (int)$total ?> compromisso(s) · <?= (int)$nobres ?> em horário nobre</span>
</div>

<div class="semana">
  <?php for ($i = 0; $i < 7; $i++):
    $dia = $segunda->modify("+{$i} days");
    $chave = $dia->format('Y-m-d'); ?>
    <div class="dia <?= $chave === $hojeStr ? 'hoje' : '' ?>">
      <div class="dia-cab"><?= $nomes[$i] ?> <span class="suave"><?= $dia->format('d/m') ?></span></div>
      <div class="dia-corpo">
        <?php foreach ($porDia[$chave] ?? [] as $ag):
          $classes = trim(($ag['horario_nobre'] ? 'nobre ' : '') . ($ag['status'] === 'cancelada' ? 'cancelada ' : '') . ($ag['tipo'] === 'entrevista' ? 'entrevista' : '')); ?>
          <div class="compromisso <?= $classes ?>">
            <div><span class="hora"><?= e(hora_br($ag['hora_inicio'])) ?>–<?= e(hora_br($ag['hora_fim'])) ?></span>
              <?= $ag['horario_nobre'] ? selo('nobre', 'nobre') : '' ?></div>
            <div>
              <?php if ($ag['vinculo'] === 'aluno'): ?>
                <a href="<?= e(url('alunos', 'ver', ['id' => $ag['aluno_id']])) ?>"><?= e($ag['pessoa']) ?></a>
              <?php else: ?>
                <?= e($ag['pessoa']) ?> <small>(lead)</small>
              <?php endif; ?>
            </div>
            <small class="suave"><?= e(['aula' => 'Aula', 'entrevista' => 'Entrevista', 'reposicao' => 'Reposição'][$ag['tipo']] ?? $ag['tipo']) ?>
              <?= $ag['observacao'] ? ' · ' . e($ag['observacao']) : '' ?></small>
            <div class="acoes"><?php require BASE_PATH . '/app/Views/agenda/_acoes.php'; ?></div>
          </div>
        <?php endforeach; ?>
        <a class="suave" style="font-size:.78rem" href="<?= e(url('agenda', 'novo', ['data' => $chave])) ?>">+ agendar</a>
      </div>
    </div>
  <?php endfor; ?>
</div>
<p class="suave" style="margin-top:12px">Horário nobre: qualquer horário aos sábados, ou aulas que começam antes das 08h00 ou a partir das 20h00.</p>
