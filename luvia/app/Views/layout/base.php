<?php
/** Layout das páginas internas: menu lateral + conteúdo. */
$paginaAtual = is_string($_GET['p'] ?? null) ? $_GET['p'] : 'painel';
$icones = [
    'painel' => '◐', 'agenda' => '▦', 'alunos' => '☺', 'leads' => '✦',
    'cursos' => '❖', 'materiais' => '▤', 'financeiro' => '$', 'conta' => '⚙',
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Luvia') ?> · Luvia</title>
<link rel="icon" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/luvia.css">
</head>
<body>
<div class="app">
  <aside class="lateral">
    <a class="marca" href="<?= e(url('painel')) ?>"><img src="assets/img/logo.png" alt="Luvia"></a>
    <nav class="menu" aria-label="Menu principal">
      <?php foreach ($GLOBALS['rotas'] as $chave => $r): if (empty($r['menu'])) continue; ?>
        <?php $ativo = $paginaAtual === $chave
            || ($chave === 'alunos' && in_array($paginaAtual, ['etapas', 'atividades', 'diario'], true)); ?>
        <a href="<?= e(url($chave)) ?>" class="<?= $ativo ? 'ativo' : '' ?>">
          <span class="ico" aria-hidden="true"><?= $icones[$chave] ?? '•' ?></span><?= e($r['menu'][0]) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="lateral-rodape">
      <div class="quem"><?= e(usuaria()['nome'] ?? '') ?><small>Mentora e administradora</small></div>
      <form method="post" action="<?= e(url('login', 'sair')) ?>">
        <?= csrf_campo() ?>
        <button class="btn-link-claro" type="submit">Sair</button>
      </form>
    </div>
  </aside>

  <main class="conteudo">
    <header class="topo">
      <h1><?= e($titulo ?? '') ?></h1>
      <?php if (!empty($acoesTopo)): ?><div class="acoes-topo"><?= $acoesTopo ?></div><?php endif; ?>
    </header>

    <?php foreach (pegar_avisos() as $av): ?>
      <div class="aviso aviso-<?= e($av['tipo']) ?>" role="status"><?= e($av['texto']) ?></div>
    <?php endforeach; ?>

    <?= $conteudo ?>
  </main>
</div>
</body>
</html>
