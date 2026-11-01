<?php /** Cadastro e edição de aluno (RF05, RF06). */ ?>
<?php foreach ($erros as $er): ?><div class="aviso aviso-erro"><?= e($er) ?></div><?php endforeach; ?>

<form class="cartao form" method="post" action="<?= e(url('alunos', $novo ? 'salvar' : 'atualizar')) ?>">
  <?= csrf_campo() ?>
  <?php if (!$novo): ?><input type="hidden" name="id" value="<?= (int)$aluno['id'] ?>"><?php endif; ?>

  <h2>Dados do aluno</h2>
  <div class="form-grade">
    <label>Nome *<input type="text" name="nome" required maxlength="120" value="<?= e($aluno['nome'] ?? '') ?>"></label>
    <label>E-mail<input type="email" name="email" maxlength="160" value="<?= e($aluno['email'] ?? '') ?>"></label>
    <label>Telefone<input type="text" name="telefone" maxlength="30" value="<?= e($aluno['telefone'] ?? '') ?>"></label>
    <label>Nicho de atuação
      <input type="text" name="nicho" maxlength="80" list="lista-nichos" value="<?= e($aluno['nicho'] ?? '') ?>" placeholder="ex.: Advocacia">
      <datalist id="lista-nichos"><?php foreach ($nichos as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>Data de início *<input type="date" name="data_inicio" required value="<?= e($aluno['data_inicio'] ?? '') ?>"></label>
    <label>Link do contrato<input type="url" name="contrato_link" maxlength="500" placeholder="https://" value="<?= e($aluno['contrato_link'] ?? '') ?>"></label>
  </div>

  <?php if ($novo): ?>
  <h2>Curso</h2>
  <div class="form-grade">
    <label>Curso contratado
      <select name="curso_id">
        <option value="">Escolher depois</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)($aluno['curso_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
            <?= e($c['nome']) ?> (<?= (int)$c['duracao_semanas'] ?> semanas)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <p class="suave inteiro">Ao escolher o curso, o sistema monta as semanas do aluno a partir da data de início. Depois você pode personalizar as etapas na ficha.</p>
  </div>
  <?php endif; ?>

  <h2>Mapa da Cena e nível de fluência</h2>
  <div class="form-grade">
    <label class="inteiro">Cena (o compromisso real em que o aluno vai usar o inglês)
      <input type="text" name="cena_objetivo" maxlength="255" value="<?= e($aluno['cena_objetivo'] ?? '') ?>"
             placeholder="ex.: Conduzir a reunião de contrato com o escritório parceiro">
    </label>
    <label>Data da cena<input type="date" name="data_cena" value="<?= e($aluno['data_cena'] ?? '') ?>"></label>
    <label>Nível inicial
      <select name="nivel_inicial"><option value="">—</option>
        <?php foreach (NIVEIS as $v => $r): ?><option value="<?= $v ?>" <?= ($aluno['nivel_inicial'] ?? '') === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Nível alvo
      <select name="nivel_alvo"><option value="">—</option>
        <?php foreach (NIVEIS as $v => $r): ?><option value="<?= $v ?>" <?= ($aluno['nivel_alvo'] ?? '') === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
      </select>
    </label>
  </div>

  <div class="acoes-form">
    <a class="btn" href="<?= e($novo ? url('alunos') : url('alunos', 'ver', ['id' => $aluno['id']])) ?>">Cancelar</a>
    <button class="btn btn-destaque" type="submit"><?= $novo ? 'Cadastrar aluno' : 'Salvar alterações' ?></button>
  </div>
</form>
