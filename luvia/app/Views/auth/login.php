<h1 class="titulo-entrada">Entrar</h1>
<p class="sub-entrada">Gestão da sua mentoria em um só lugar.</p>
<?php if ($bloqueio > 0): ?>
  <div class="aviso aviso-erro">Muitas tentativas. Tente de novo em <?= (int)ceil($bloqueio / 60) ?> minuto(s).</div>
<?php endif; ?>
<form method="post" action="<?= e(url('login', 'entrar')) ?>" class="form">
  <?= csrf_campo() ?>
  <label>E-mail
    <input type="email" name="email" required autocomplete="username" autofocus>
  </label>
  <label>Senha
    <input type="password" name="senha" required autocomplete="current-password">
  </label>
  <button class="btn btn-destaque largo" type="submit">Entrar</button>
</form>
