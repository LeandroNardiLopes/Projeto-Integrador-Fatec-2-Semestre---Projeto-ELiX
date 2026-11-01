<?php /** Layout das páginas sem menu (login, primeiro acesso, erros). */ ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Luvia') ?> · Luvia</title>
<link rel="icon" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/luvia.css">
</head>
<body class="fundo-noite">
<main class="cartao-entrada">
  <img class="logo-entrada" src="assets/img/logo.png" alt="Luvia">
  <?php foreach (pegar_avisos() as $av): ?>
    <div class="aviso aviso-<?= e($av['tipo']) ?>" role="status"><?= e($av['texto']) ?></div>
  <?php endforeach; ?>
  <?= $conteudo ?>
</main>
</body>
</html>
