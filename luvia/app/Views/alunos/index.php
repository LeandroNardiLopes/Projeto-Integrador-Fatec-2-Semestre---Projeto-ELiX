<?php /** Lista de alunos com busca e filtros (RF09). */ ?>
<form class="filtros" method="get" action="index.php">
  <input type="hidden" name="p" value="alunos">
  <label>Buscar
    <input type="search" name="busca" value="<?= e($filtro['busca']) ?>" placeholder="nome, e-mail ou nicho">
  </label>
  <label>Situação
    <select name="situacao">
      <?php foreach (['ativos' => 'Ativos', 'inativos' => 'Inativos', 'todos' => 'Todos'] as $v => $r): ?>
        <option value="<?= $v ?>" <?= $filtro['situacao'] === $v ? 'selected' : '' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Nicho
    <select name="nicho">
      <option value="">Todos</option>
      <?php foreach ($nichos as $n): ?>
        <option value="<?= e($n) ?>" <?= $filtro['nicho'] === $n ? 'selected' : '' ?>><?= e($n) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Curso
    <select name="curso">
      <option value="">Todos</option>
      <?php foreach ($cursos as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $filtro['cursoId'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn" type="submit">Filtrar</button>
</form>

<section class="cartao">
  <?php if (!$alunos): ?>
    <p class="vazio">Nenhum aluno encontrado.</p>
  <?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Aluno</th><th>Curso</th><th>Nicho</th><th>Progresso</th><th>Próxima aula</th><th>Situação</th></tr></thead>
      <tbody>
      <?php foreach ($alunos as $a):
        $prog = $a['total_etapas'] > 0 ? 100 * $a['etapas_concluidas'] / $a['total_etapas'] : 0; ?>
        <tr>
          <td><a href="<?= e(url('alunos', 'ver', ['id' => $a['id']])) ?>"><strong><?= e($a['nome']) ?></strong></a>
            <div class="suave"><?= e($a['email'] ?? '') ?></div></td>
          <td><?= e($a['curso'] ?? '—') ?></td>
          <td><?= e($a['nicho'] ?? '—') ?></td>
          <td style="min-width:140px"><div class="barra"><span style="width: <?= (int)round($prog) ?>%"></span></div>
            <small><?= (int)$a['etapas_concluidas'] ?> de <?= (int)$a['total_etapas'] ?> semanas</small></td>
          <td><?= $a['proxima_aula'] ? e(data_curta($a['proxima_aula']) . ' ' . hora_br(substr($a['proxima_aula'], 11))) : '—' ?></td>
          <td><?= $a['ativo'] ? selo('ativo', 'ok') : selo('inativo', 'neutro') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
