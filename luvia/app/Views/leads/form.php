<?php /** Cadastro e edição de lead (RF10). */ $novo = empty($lead['id']); ?>
<?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>
<form class="cartao form" method="post" action="<?= e(url('leads', $novo ? 'salvar' : 'atualizar')) ?>">
  <?= csrf_campo() ?>
  <?php if (!$novo): ?><input type="hidden" name="id" value="<?= (int)$lead['id'] ?>"><?php endif; ?>
  <div class="form-grade">
    <label>Nome *<input type="text" name="nome" required maxlength="120" value="<?= e($lead['nome'] ?? '') ?>"></label>
    <label>E-mail<input type="email" name="email" maxlength="160" value="<?= e($lead['email'] ?? '') ?>"></label>
    <label>Telefone<input type="text" name="telefone" maxlength="30" value="<?= e($lead['telefone'] ?? '') ?>"></label>
    <label>Origem
      <select name="origem"><option value="">—</option>
        <?php foreach (Lead::ORIGENS as $o): ?><option <?= ($lead['origem'] ?? '') === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Nicho<input type="text" name="nicho" maxlength="80" value="<?= e($lead['nicho'] ?? '') ?>"></label>
    <label>Etapa do funil
      <?php if (($lead['status'] ?? '') === 'convertido'): ?>
        <input type="text" value="Convertido" disabled><input type="hidden" name="status" value="convertido">
      <?php else: ?>
        <select name="status">
          <?php foreach (['novo', 'em_contato', 'desistiu'] as $s): ?><option value="<?= $s ?>" <?= ($lead['status'] ?? 'novo') === $s ? 'selected' : '' ?>><?= Lead::STATUS[$s] ?></option><?php endforeach; ?>
        </select>
      <?php endif; ?>
    </label>
    <label class="inteiro">Observação<textarea name="observacao" maxlength="500"><?= e($lead['observacao'] ?? '') ?></textarea></label>
  </div>
  <div class="acoes-form">
    <a class="btn" href="<?= e(url('leads')) ?>">Voltar</a>
    <button class="btn btn-destaque" type="submit"><?= $novo ? 'Cadastrar lead' : 'Salvar' ?></button>
  </div>
</form>
