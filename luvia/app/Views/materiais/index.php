<?php /** Materiais (RF24) e aulas gravadas (RF25), gerais ou por aluno (RN10). */ ?>
<form class="filtros" method="get" action="index.php">
  <input type="hidden" name="p" value="materiais">
  <label>Mostrar
    <select name="aluno"><option value="">Todos (gerais e por aluno)</option>
      <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $alunoId === (int)$a['id'] ? 'selected' : '' ?>>Só de <?= e($a['nome']) ?></option><?php endforeach; ?>
    </select>
  </label>
  <button class="btn" type="submit">Filtrar</button>
</form>

<div class="grade-2">
  <section class="cartao">
    <h2>Materiais</h2>
    <?php if (!$materiais): ?><p class="vazio">Nenhum material.</p><?php endif; ?>
    <ul class="lista">
      <?php foreach ($materiais as $m): ?>
        <li><span><a href="<?= e($m['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($m['titulo']) ?></a>
          <span class="meta"><?= e($m['tipo']) ?> · <?= $m['aluno'] ? e($m['aluno']) : 'geral (todos os alunos)' ?></span></span>
          <form class="em-linha" method="post" action="<?= e(url('materiais', 'excluir')) ?>"><?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-pequeno btn-perigo" type="submit">Excluir</button></form></li>
      <?php endforeach; ?>
    </ul>
    <details class="bloco" style="margin-top:10px"><summary>+ Novo material</summary>
      <form method="post" action="<?= e(url('materiais', 'salvar')) ?>" class="form"><?= csrf_campo() ?>
        <label>Título<input type="text" name="titulo" required maxlength="160"></label>
        <div class="form-grade">
          <label>Tipo<select name="tipo"><?php foreach (Material::TIPOS as $t): ?><option><?= e($t) ?></option><?php endforeach; ?></select></label>
          <label>Para<select name="aluno_id"><option value="">Todos os alunos (geral)</option>
            <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?></select></label>
        </div>
        <label>Link (Google Drive, Dropbox etc.)<input type="url" name="link" required maxlength="500" placeholder="https://"></label>
        <div><button class="btn btn-destaque" type="submit">Salvar material</button></div>
      </form>
    </details>
  </section>

  <section class="cartao">
    <h2>Aulas gravadas</h2>
    <?php if (!$gravacoes): ?><p class="vazio">Nenhuma gravação.</p><?php endif; ?>
    <ul class="lista">
      <?php foreach ($gravacoes as $g): ?>
        <li><span><a href="<?= e($g['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($g['titulo']) ?></a>
          <span class="meta"><?= e(data_br($g['data_aula'])) ?> · <?= $g['aluno'] ? e($g['aluno']) : 'geral (todos os alunos)' ?></span></span>
          <form class="em-linha" method="post" action="<?= e(url('materiais', 'excluir_gravacao')) ?>"><?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><button class="btn btn-pequeno btn-perigo" type="submit">Excluir</button></form></li>
      <?php endforeach; ?>
    </ul>
    <details class="bloco" style="margin-top:10px"><summary>+ Nova gravação</summary>
      <form method="post" action="<?= e(url('materiais', 'salvar_gravacao')) ?>" class="form"><?= csrf_campo() ?>
        <label>Título<input type="text" name="titulo" required maxlength="160"></label>
        <div class="form-grade">
          <label>Data<input type="date" name="data_aula" required value="<?= e(date('Y-m-d')) ?>"></label>
          <label>Para<select name="aluno_id"><option value="">Todos os alunos (geral)</option>
            <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?></select></label>
        </div>
        <label>Link da gravação<input type="url" name="link" required maxlength="500" placeholder="https://"></label>
        <div><button class="btn btn-destaque" type="submit">Salvar gravação</button></div>
      </form>
    </details>
  </section>
</div>
