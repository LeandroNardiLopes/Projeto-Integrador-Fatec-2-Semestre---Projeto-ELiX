<?php /** Ações de uma parcela (pagar e excluir). Espera $p. */ ?>
<?php if ($p['status'] !== 'pago'): ?>
<span class="botoes">
  <details class="inline"><summary class="btn btn-pequeno btn-destaque">Registrar pagamento</summary>
    <div class="painel-form">
      <form method="post" action="<?= e(url('financeiro', 'pagar')) ?>" class="form"><?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <label>Data do pagamento<input type="date" name="data_pagamento" required value="<?= e(date('Y-m-d')) ?>"></label>
        <label>Forma<select name="forma_pagamento"><?php foreach (FORMAS_PAGAMENTO as $f): ?><option><?= e($f) ?></option><?php endforeach; ?></select></label>
        <button class="btn btn-destaque" type="submit">Confirmar pagamento</button>
      </form>
    </div>
  </details>
  <form class="em-linha" method="post" action="<?= e(url('financeiro', 'excluir')) ?>"><?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-pequeno btn-perigo" type="submit" title="Excluir cobrança lançada por engano">Excluir</button></form>
</span>
<?php endif; ?>
