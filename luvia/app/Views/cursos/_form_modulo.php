<?php /** Formulário de módulo. Espera $mod e $cid. */ ?>
<form method="post" action="<?= e(url('cursos', 'salvar_modulo')) ?>" class="form">
  <?= csrf_campo() ?><input type="hidden" name="curso_id" value="<?= $cid ?>">
  <?php if (!empty($mod['id'])): ?><input type="hidden" name="id" value="<?= (int)$mod['id'] ?>"><?php endif; ?>
  <div class="form-grade">
    <label>Ordem<input type="number" name="ordem" min="1" max="50" required value="<?= (int)($mod['ordem'] ?? 1) ?>"></label>
    <label>Título<input type="text" name="titulo" required maxlength="160" value="<?= e($mod['titulo'] ?? '') ?>"></label>
    <label>Pilar<input type="text" name="pilar" required maxlength="40" value="<?= e($mod['pilar'] ?? '') ?>" placeholder="Cenário, Essência, Nicho…"></label>
    <label>Semana inicial<input type="number" name="semana_inicio" min="1" max="52" required value="<?= (int)($mod['semana_inicio'] ?? 1) ?>"></label>
    <label>Semana final<input type="number" name="semana_fim" min="1" max="52" required value="<?= (int)($mod['semana_fim'] ?? 1) ?>"></label>
    <label class="inteiro">Objetivo<input type="text" name="objetivo" maxlength="255" value="<?= e($mod['objetivo'] ?? '') ?>"></label>
  </div>
  <div><button class="btn btn-destaque" type="submit">Salvar módulo</button></div>
</form>
