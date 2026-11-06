<?php /** Ficha: materiais e gravações do aluno, incluindo os gerais (RF24, RF25). */ ?>
<section class="cartao" id="materiais">
  <div class="cartao-cab"><h2>Materiais e gravações</h2>
    <a class="btn btn-pequeno" href="<?= e(url('materiais', null, ['aluno' => $id])) ?>">Gerenciar</a></div>
  <div class="grade-2">
    <div>
      <h3>Materiais</h3>
      <?php if (!$materiais): ?><p class="vazio">Nenhum material.</p><?php endif; ?>
      <ul class="lista">
        <?php foreach ($materiais as $m): ?>
          <li><span><a href="<?= e($m['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($m['titulo']) ?></a>
            <span class="meta"><?= e($m['tipo']) ?><?= $m['aluno_id'] === null ? ' · geral' : '' ?></span></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h3>Gravações</h3>
      <?php if (!$gravacoes): ?><p class="vazio">Nenhuma gravação.</p><?php endif; ?>
      <ul class="lista">
        <?php foreach ($gravacoes as $g): ?>
          <li><span><a href="<?= e($g['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($g['titulo']) ?></a>
            <span class="meta"><?= e(data_br($g['data_aula'])) ?><?= $g['aluno_id'] === null ? ' · geral' : '' ?></span></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
