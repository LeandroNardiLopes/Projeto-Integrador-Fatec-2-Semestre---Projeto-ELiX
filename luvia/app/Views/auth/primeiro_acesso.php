<h1 class="titulo-entrada">Primeiro acesso</h1>
<p class="sub-entrada">Crie sua senha. Ela fica guardada de forma protegida e só você vai usar o sistema.</p>
<form method="post" action="<?= e(url('login', 'definir_senha')) ?>" class="form">
  <?= csrf_campo() ?>
  <label>Seu nome
    <input type="text" name="nome" required maxlength="120" value="<?= e($u['nome'] === 'Mentora (teste)' ? '' : $u['nome']) ?>">
  </label>
  <label>E-mail de acesso
    <input type="email" name="email" required maxlength="160" value="<?= e($u['email']) ?>">
  </label>
  <label>Nova senha (mínimo de 8 caracteres)
    <input type="password" name="senha" required minlength="8" autocomplete="new-password">
  </label>
  <label>Repita a senha
    <input type="password" name="confirma" required minlength="8" autocomplete="new-password">
  </label>
  <button class="btn btn-destaque largo" type="submit">Criar senha</button>
</form>
