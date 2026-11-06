<?php /** Formulário de semana da lista do curso. Espera $aula, $modulos e $cid. */ ?>
<form method="post" action="<?= e(url('cursos', 'salvar_aula')) ?>" class="form">
  <?= csrf_campo() ?><input type="hidden" name="curso_id" value="<?= $cid ?>">
  <?php if (!empty($aula['id'])): ?><input type="hidden" name="id" value="<?= (int)$aula['id'] ?>"><?php endif; ?>
  <div class="form-grade">
    <label>Semana<input type="number" name="semana" min="1" max="52" required value="<?= (int)($aula['semana'] ?? 1) ?>"></label>
    <label>Módulo
      <select name="modulo_id" required>
        <?php foreach ($modulos as $m): ?><option value="<?= (int)$m['id'] ?>" <?= (int)($aula['modulo_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['titulo']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="inteiro">Tema<input type="text" name="titulo" required maxlength="160" value="<?= e($aula['titulo'] ?? '') ?>"></label>
    <label class="inteiro">Estrutura-base<input type="text" name="estrutura" maxlength="160" value="<?= e($aula['estrutura'] ?? '') ?>"></label>
    <label class="inteiro">Tarefa da semana<input type="text" name="tarefa" maxlength="255" value="<?= e($aula['tarefa'] ?? '') ?>"></label>
  </div>
  <div><button class="btn btn-destaque" type="submit">Salvar semana</button></div>
</form>
