<?php /** Minha conta: perfil e senha (RF02), cópia de segurança (RF04) e registro de ações (RF03). */ ?>
<div class="grade-2">
  <section class="cartao">
    <h2>Meus dados</h2>
    <form method="post" action="<?= e(url('conta', 'perfil')) ?>" class="form"><?= csrf_campo() ?>
      <label>Nome<input type="text" name="nome" required maxlength="120" value="<?= e($u['nome']) ?>"></label>
      <label>E-mail de acesso<input type="email" name="email" required maxlength="160" value="<?= e($u['email']) ?>"></label>
      <p class="suave" style="margin:0">Perfil: mentora e administradora do sistema.</p>
      <div><button class="btn btn-destaque" type="submit">Salvar</button></div>
    </form>
  </section>
  <section class="cartao">
    <h2>Trocar senha</h2>
    <form method="post" action="<?= e(url('conta', 'senha')) ?>" class="form"><?= csrf_campo() ?>
      <label>Senha atual<input type="password" name="atual" required autocomplete="current-password"></label>
      <label>Nova senha (mínimo de 8 caracteres)<input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
      <label>Repita a nova senha<input type="password" name="confirma" required minlength="8" autocomplete="new-password"></label>
      <div><button class="btn btn-destaque" type="submit">Trocar senha</button></div>
    </form>
  </section>
</div>

<section class="cartao">
  <h2>Cópia de segurança</h2>
  <p>Os dados ficam só neste computador. Faça uma cópia de segurança toda semana e guarde o arquivo em outro lugar (pen drive, Google Drive ou e-mail).</p>
  <form method="post" action="<?= e(url('conta', 'backup')) ?>" class="em-linha"><?= csrf_campo() ?>
    <button class="btn btn-destaque" type="submit">Baixar cópia de segurança</button>
  </form>
  <details class="bloco" style="margin-top:14px"><summary>Restaurar uma cópia de segurança</summary>
    <div class="aviso aviso-aviso">Atenção: restaurar substitui todos os dados atuais pelos dados do arquivo.</div>
    <form method="post" action="<?= e(url('conta', 'restaurar')) ?>" enctype="multipart/form-data" class="form-grade"><?= csrf_campo() ?>
      <label>Arquivo (.sql gerado pelo Luvia)<input type="file" name="arquivo" accept=".sql" required></label>
      <label>Digite SIM para confirmar<input type="text" name="confirmo" required pattern="SIM" autocomplete="off"></label>
      <div style="align-self:end"><button class="btn btn-perigo" type="submit">Restaurar</button></div>
    </form>
  </details>
</section>

<section class="cartao">
  <div class="cartao-cab"><h2>Registro de ações</h2><a class="btn btn-pequeno" href="<?= e(url('conta', 'logs')) ?>">Ver registro</a></div>
  <p class="suave" style="margin:0">Acessos, tentativas de login, cancelamentos, inativações, pagamentos e cópias de segurança.</p>
</section>
