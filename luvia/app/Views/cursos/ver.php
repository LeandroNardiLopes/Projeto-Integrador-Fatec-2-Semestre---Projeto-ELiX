<?php /** Curso: módulos e lista semana a semana (RF18). */ $cid = (int)$curso['id']; ?>
<section class="cartao">
  <dl class="dados">
    <dt>Modalidade</dt><dd><?= e(MODALIDADES[$curso['modalidade']]) ?> <?= $curso['ativo'] ? '' : selo('inativo', 'neutro') ?></dd>
    <dt>Por semana</dt><dd><?= (int)$curso['aulas_online_semana'] ?> ao vivo · <?= (int)$curso['aulas_gravadas_semana'] ?> gravada(s)</dd>
    <dt>Duração</dt><dd><?= (int)$curso['duracao_semanas'] ?> semanas</dd>
    <dt>Descrição</dt><dd><?= e($curso['descricao'] ?? '—') ?></dd>
    <dt>Alunos ativos</dt><dd><?php if (!$alunos): ?>nenhum<?php else: foreach ($alunos as $i => $a): ?><?= $i ? ', ' : '' ?><a href="<?= e(url('alunos', 'ver', ['id' => $a['id']])) ?>"><?= e($a['nome']) ?></a><?php endforeach; endif; ?></dd>
  </dl>
  <p class="suave" style="margin-bottom:0">Mudanças aqui valem para os próximos alunos. Os cursos já montados continuam como estão e podem ser ajustados na ficha de cada aluno.</p>
</section>

<section class="cartao" id="modulos">
  <h2>Módulos</h2>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th>#</th><th>Módulo</th><th>Pilar</th><th>Semanas</th><th>Objetivo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($modulos as $m): ?>
      <tr>
        <td><?= (int)$m['ordem'] ?></td><td><strong><?= e($m['titulo']) ?></strong></td><td><?= e($m['pilar']) ?></td>
        <td><?= (int)$m['semana_inicio'] ?><?= $m['semana_fim'] != $m['semana_inicio'] ? ' a ' . (int)$m['semana_fim'] : '' ?></td>
        <td><small><?= e($m['objetivo'] ?? '') ?></small></td>
        <td class="num">
          <details class="inline"><summary class="btn btn-pequeno">Editar</summary>
            <div class="painel-form"><?php $mod = $m; require BASE_PATH . '/app/Views/cursos/_form_modulo.php'; ?>
              <form method="post" action="<?= e(url('cursos', 'excluir_modulo')) ?>" style="margin-top:8px"><?= csrf_campo() ?>
                <input type="hidden" name="curso_id" value="<?= $cid ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-pequeno btn-perigo" type="submit">Excluir módulo</button></form>
            </div>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php if (!$modulos): ?><p class="vazio">Nenhum módulo ainda.</p><?php endif; ?>
  <details class="bloco" style="margin-top:10px"><summary>+ Novo módulo</summary>
    <?php $mod = ['ordem' => count($modulos) + 1]; require BASE_PATH . '/app/Views/cursos/_form_modulo.php'; ?>
  </details>
</section>

<section class="cartao" id="lista">
  <h2>Lista do curso (semana a semana)</h2>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th>Semana</th><th>Tema</th><th>Estrutura-base</th><th>Tarefa</th><th>Módulo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($aulas as $a): ?>
      <tr>
        <td class="semana-num"><?= (int)$a['semana'] ?></td><td><?= e($a['titulo']) ?></td>
        <td><small><?= e($a['estrutura'] ?? '') ?></small></td><td><small><?= e($a['tarefa'] ?? '') ?></small></td>
        <td><small><?= e($a['modulo']) ?></small></td>
        <td class="num">
          <details class="inline"><summary class="btn btn-pequeno">Editar</summary>
            <div class="painel-form"><?php $aula = $a; require BASE_PATH . '/app/Views/cursos/_form_aula.php'; ?>
              <form method="post" action="<?= e(url('cursos', 'excluir_aula')) ?>" style="margin-top:8px"><?= csrf_campo() ?>
                <input type="hidden" name="curso_id" value="<?= $cid ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="btn btn-pequeno btn-perigo" type="submit">Excluir semana</button></form>
            </div>
          </details>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php if (!$aulas): ?><p class="vazio">Nenhuma semana cadastrada.</p><?php endif; ?>
  <?php if ($modulos): ?>
    <details class="bloco" style="margin-top:10px"><summary>+ Nova semana</summary>
      <?php $aula = ['semana' => count($aulas) + 1]; require BASE_PATH . '/app/Views/cursos/_form_aula.php'; ?>
    </details>
  <?php else: ?>
    <p class="suave">Cadastre um módulo antes de montar as semanas.</p>
  <?php endif; ?>
</section>
