<?php /** Ficha: atividades do aluno (RF22). */ $tiposAt = ['pendente' => 'aviso', 'entregue' => 'ok', 'atrasada' => 'erro']; ?>
<section class="cartao" id="atividades">
  <h2>Atividades</h2>
  <?php if (!$atividades): ?><p class="vazio">Nenhuma atividade.</p><?php endif; ?>
  <ul class="lista">
    <?php foreach ($atividades as $at): ?>
      <li><span><strong><?= e($at['titulo']) ?></strong> <?= selo($at['status'], $tiposAt[$at['status']]) ?>
        <span class="meta"><?= $at['data_entrega'] ? 'prazo ' . e(data_br($at['data_entrega'])) : 'sem prazo' ?><?= $at['descricao'] ? ' · ' . e($at['descricao']) : '' ?></span></span>
        <form class="em-linha" method="post" action="<?= e(url('atividades', 'status')) ?>"><?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int)$at['id'] ?>">
          <select name="status" aria-label="Status" style="width:auto;padding:3px 6px">
            <?php foreach (Atividade::STATUS as $v => $r): ?><option value="<?= $v ?>" <?= $at['status'] === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-pequeno" type="submit">Salvar</button>
        </form></li>
    <?php endforeach; ?>
  </ul>
  <?php if ($aluno['ativo']): ?>
  <details class="bloco" style="margin-top:10px"><summary>+ Nova atividade</summary>
    <form method="post" action="<?= e(url('atividades', 'salvar')) ?>" class="form-grade"><?= csrf_campo() ?>
      <input type="hidden" name="aluno_id" value="<?= $id ?>">
      <label>Título<input type="text" name="titulo" required maxlength="160" placeholder="ex.: Gravar o pitch de 30 s"></label>
      <label>Prazo<input type="date" name="data_entrega"></label>
      <label class="inteiro">Como fazer<input type="text" name="descricao" maxlength="500"></label>
      <div><button class="btn btn-destaque" type="submit">Salvar atividade</button></div>
    </form>
  </details>
  <?php endif; ?>
</section>
