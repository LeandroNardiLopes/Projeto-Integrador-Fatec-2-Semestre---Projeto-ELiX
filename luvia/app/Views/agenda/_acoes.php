<?php
/**
 * Ações de um compromisso da agenda (usado na agenda e na ficha do aluno).
 * Espera $ag com: id, status, data_aula, aluno_id, frequencia_id, presente, reposta, reposicao_agendamento_id.
 */
$hojeStr = date('Y-m-d');
$ehAluno = !empty($ag['aluno_id']);
?>
<span class="botoes">
<?php if ($ag['status'] === 'agendada'): ?>
  <?php if ($ehAluno && $ag['data_aula'] <= $hojeStr): ?>
    <form class="em-linha" method="post" action="<?= e(url('agenda', 'frequencia')) ?>">
      <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$ag['id'] ?>"><input type="hidden" name="presente" value="1">
      <button class="btn btn-pequeno" type="submit" title="Registrar presença">Presente</button>
    </form>
    <form class="em-linha" method="post" action="<?= e(url('agenda', 'frequencia')) ?>">
      <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$ag['id'] ?>"><input type="hidden" name="presente" value="0">
      <button class="btn btn-pequeno btn-perigo" type="submit" title="Registrar falta">Faltou</button>
    </form>
  <?php endif; ?>
  <details class="inline">
    <summary class="btn btn-pequeno">Cancelar</summary>
    <div class="painel-form">
      <form method="post" action="<?= e(url('agenda', 'cancelar')) ?>" class="form">
        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$ag['id'] ?>">
        <label>Motivo do cancelamento *<input type="text" name="motivo" required maxlength="255"></label>
        <button class="btn btn-perigo" type="submit">Confirmar cancelamento</button>
      </form>
    </div>
  </details>
<?php elseif ($ag['status'] === 'realizada' && $ag['frequencia_id'] !== null): ?>
  <?php if ((int)$ag['presente'] === 1): ?>
    <?= selo('presente', 'ok') ?>
  <?php elseif ((int)$ag['reposta'] === 1): ?>
    <?= selo('falta reposta', 'info') ?>
  <?php elseif ($ag['reposicao_agendamento_id'] !== null): ?>
    <?= selo('falta · reposição agendada', 'aviso') ?>
  <?php else: ?>
    <?= selo('falta', 'erro') ?>
    <details class="inline">
      <summary class="btn btn-pequeno">Agendar reposição</summary>
      <div class="painel-form">
        <form method="post" action="<?= e(url('agenda', 'reposicao')) ?>" class="form">
          <?= csrf_campo() ?><input type="hidden" name="frequencia_id" value="<?= (int)$ag['frequencia_id'] ?>">
          <label>Data<input type="date" name="data" required min="<?= e($hojeStr) ?>"></label>
          <label>Início<input type="time" name="inicio" required></label>
          <label>Fim<input type="time" name="fim" required></label>
          <button class="btn btn-destaque" type="submit">Agendar reposição</button>
        </form>
      </div>
    </details>
  <?php endif; ?>
<?php elseif ($ag['status'] === 'cancelada'): ?>
  <?= selo('cancelada', 'neutro') ?>
<?php endif; ?>
</span>
