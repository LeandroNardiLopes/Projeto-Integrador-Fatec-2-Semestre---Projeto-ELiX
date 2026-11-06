<?php /** Cadastro e edição de curso (RF18). */ $novo = empty($curso['id']); ?>
<?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>
<form class="cartao form" method="post" action="<?= e(url('cursos', $novo ? 'salvar' : 'atualizar')) ?>">
  <?= csrf_campo() ?>
  <?php if (!$novo): ?><input type="hidden" name="id" value="<?= (int)$curso['id'] ?>"><?php endif; ?>
  <div class="form-grade">
    <label class="inteiro">Nome do curso *<input type="text" name="nome" required maxlength="120" value="<?= e($curso['nome'] ?? '') ?>"></label>
    <label>Modalidade
      <select name="modalidade">
        <?php foreach (MODALIDADES as $v => $r): ?><option value="<?= $v ?>" <?= ($curso['modalidade'] ?? '') === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Encontros ao vivo por semana<input type="number" name="aulas_online_semana" min="0" max="7" required value="<?= (int)($curso['aulas_online_semana'] ?? 0) ?>"></label>
    <label>Videoaulas gravadas por semana<input type="number" name="aulas_gravadas_semana" min="0" max="7" required value="<?= (int)($curso['aulas_gravadas_semana'] ?? 0) ?>"></label>
    <label>Duração (semanas)<input type="number" name="duracao_semanas" min="1" max="52" required value="<?= (int)($curso['duracao_semanas'] ?? 12) ?>"></label>
    <label>Situação
      <select name="ativo"><option value="1" <?= (int)($curso['ativo'] ?? 1) === 1 ? 'selected' : '' ?>>Ativo</option>
        <option value="0" <?= (int)($curso['ativo'] ?? 1) === 0 ? 'selected' : '' ?>>Inativo</option></select>
    </label>
    <label class="inteiro">Descrição<textarea name="descricao" maxlength="500"><?= e($curso['descricao'] ?? '') ?></textarea></label>
  </div>
  <div class="acoes-form">
    <a class="btn" href="<?= e($novo ? url('cursos') : url('cursos', 'ver', ['id' => $curso['id']])) ?>">Voltar</a>
    <button class="btn btn-destaque" type="submit"><?= $novo ? 'Criar curso' : 'Salvar' ?></button>
  </div>
</form>
