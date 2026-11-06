<?php /** Cursos (RF18). */ ?>
<div class="grade-3">
  <?php foreach ($cursos as $c): ?>
    <section class="cartao">
      <div class="cartao-cab"><h2><a href="<?= e(url('cursos', 'ver', ['id' => $c['id']])) ?>"><?= e($c['nome']) ?></a></h2>
        <?= $c['ativo'] ? selo(MODALIDADES[$c['modalidade']], 'info') : selo('inativo', 'neutro') ?></div>
      <p class="suave"><?= e($c['descricao'] ?? '') ?></p>
      <dl class="dados">
        <dt>Ao vivo por semana</dt><dd><?= (int)$c['aulas_online_semana'] ?></dd>
        <dt>Gravadas por semana</dt><dd><?= (int)$c['aulas_gravadas_semana'] ?></dd>
        <dt>Duração</dt><dd><?= (int)$c['duracao_semanas'] ?> semanas</dd>
        <dt>Alunos ativos</dt><dd><?= (int)$c['alunos_ativos'] ?></dd>
      </dl>
      <p style="margin-bottom:0"><a class="btn btn-pequeno" href="<?= e(url('cursos', 'ver', ['id' => $c['id']])) ?>">Ver módulos e semanas</a></p>
    </section>
  <?php endforeach; ?>
</div>
<?php if (!$cursos): ?><p class="vazio">Nenhum curso cadastrado.</p><?php endif; ?>
