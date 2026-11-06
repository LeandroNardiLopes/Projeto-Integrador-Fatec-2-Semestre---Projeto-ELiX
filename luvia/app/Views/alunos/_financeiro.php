<?php /** Ficha: financeiro do aluno (RF26 a RF29). */ $tipos = ['pago' => 'ok', 'pendente' => 'aviso', 'atrasado' => 'erro']; ?>
<section class="cartao" id="financeiro">
  <div class="cartao-cab"><h2>Financeiro</h2>
    <a class="btn btn-pequeno" href="<?= e(url('financeiro', null, ['aluno' => $id])) ?>">Abrir no financeiro</a></div>
  <?php if (!$parcelas): ?><p class="vazio">Nenhuma cobrança.</p><?php else: ?>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th>Vencimento</th><th>Descrição</th><th class="num">Valor</th><th>Situação</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($parcelas as $p): ?>
      <tr><td><?= e(data_br($p['vencimento'])) ?></td><td><?= e($p['descricao']) ?></td><td class="num"><?= e(moeda($p['valor'])) ?></td>
        <td><?= selo($p['status'], $tipos[$p['status']] ?? 'neutro') ?>
          <?php if ($p['status'] === 'pago'): ?><small><?= e(data_br($p['data_pagamento'])) ?> · <?= e($p['forma_pagamento'] ?? '') ?></small><?php endif; ?></td>
        <td class="num"><?php require BASE_PATH . '/app/Views/financeiro/_acoes.php'; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>
