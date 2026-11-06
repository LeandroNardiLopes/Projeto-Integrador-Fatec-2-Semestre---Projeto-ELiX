<?php /** Ficha: diário de bordo da mentora (RF23). */ ?>
<section class="cartao" id="diario">
  <h2>Diário de bordo</h2>
  <form method="post" action="<?= e(url('diario', 'salvar')) ?>" class="form" style="margin-bottom:12px"><?= csrf_campo() ?>
    <input type="hidden" name="aluno_id" value="<?= $id ?>">
    <div class="form-grade">
      <label>Data<input type="date" name="data_registro" required value="<?= e(date('Y-m-d')) ?>"></label>
      <label class="inteiro">Anotação<textarea name="anotacao" required maxlength="5000" placeholder="O que foi trabalhado, onde travou, próximo passo."></textarea></label>
    </div>
    <div><button class="btn btn-destaque" type="submit">Registrar</button></div>
  </form>
  <?php if (!$diario): ?><p class="vazio">Nenhuma anotação.</p><?php endif; ?>
  <ul class="lista">
    <?php foreach ($diario as $d): ?>
      <li><span><strong><?= e(data_br($d['data_registro'])) ?></strong><br><?= nl2br(e($d['anotacao'])) ?></span>
        <form class="em-linha" method="post" action="<?= e(url('diario', 'excluir')) ?>"><?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn btn-pequeno btn-perigo" type="submit">Excluir</button></form></li>
    <?php endforeach; ?>
  </ul>
</section>
