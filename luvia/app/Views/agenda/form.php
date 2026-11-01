<?php /** Agendar aula ou entrevista (RF13). */ ?>
<?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>

<form class="cartao form" method="post" action="<?= e(url('agenda', 'salvar')) ?>">
  <?= csrf_campo() ?>
  <div class="form-grade">
    <label>Tipo
      <select name="tipo" id="tipo">
        <option value="aula" <?= $d['tipo'] === 'aula' ? 'selected' : '' ?>>Aula (aluno)</option>
        <option value="entrevista" <?= $d['tipo'] === 'entrevista' ? 'selected' : '' ?>>Entrevista (lead)</option>
      </select>
    </label>
    <label>Aluno (para aula)
      <select name="aluno_id">
        <option value="">—</option>
        <?php foreach ($alunos as $a): ?>
          <option value="<?= (int)$a['id'] ?>" <?= (int)($d['aluno_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Lead (para entrevista)
      <select name="lead_id">
        <option value="">—</option>
        <?php foreach ($leads as $l): ?>
          <option value="<?= (int)$l['id'] ?>" <?= (int)($d['lead_id'] ?? 0) === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Data<input type="date" name="data" required value="<?= e($d['data']) ?>"></label>
    <label>Início<input type="time" name="inicio" required value="<?= e($d['inicio']) ?>"></label>
    <label>Fim<input type="time" name="fim" required value="<?= e($d['fim']) ?>"></label>
    <label class="inteiro">Observação<input type="text" name="observacao" maxlength="500" value="<?= e($d['observacao']) ?>"></label>
  </div>
  <p class="suave">As aulas acontecem de segunda a sexta, das 08h00 às 21h00. Horários antes das 08h00, a partir das 20h00 ou aos sábados são marcados como horário nobre. O sistema não deixa marcar dois compromissos no mesmo horário.</p>
  <div class="acoes-form">
    <a class="btn" href="<?= e(url('agenda')) ?>">Voltar</a>
    <button class="btn btn-destaque" type="submit">Agendar</button>
  </div>
</form>
