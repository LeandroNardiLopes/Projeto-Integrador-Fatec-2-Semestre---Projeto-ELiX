<?php /** Financeiro (RF26 a RF29). */
$tipos = ['pago' => 'ok', 'pendente' => 'aviso', 'atrasado' => 'erro']; ?>
<section class="kpis">
  <div class="kpi"><div class="rotulo">A receber</div><div class="valor"><?= e(moeda($totais['a_receber'] ?? 0)) ?></div></div>
  <div class="kpi <?= ($totais['atrasado'] ?? 0) > 0 ? 'alerta' : '' ?>"><div class="rotulo">Em atraso</div><div class="valor"><?= e(moeda($totais['atrasado'] ?? 0)) ?></div></div>
  <div class="kpi"><div class="rotulo">Recebido neste mês</div><div class="valor"><?= e(moeda($totais['recebido_mes'] ?? 0)) ?></div></div>
</section>

<div class="grade-2">
  <details class="cartao bloco"><summary>+ Nova cobrança</summary>
    <form method="post" action="<?= e(url('financeiro', 'salvar')) ?>" class="form-grade"><?= csrf_campo() ?>
      <label>Aluno<select name="aluno_id" required><option value="">—</option>
        <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?></select></label>
      <label>Descrição<input type="text" name="descricao" required maxlength="120" value="Mensalidade"></label>
      <label>Valor (R$)<input type="text" name="valor" required inputmode="decimal" placeholder="0,00"></label>
      <label>Vencimento<input type="date" name="vencimento" required></label>
      <div style="align-self:end"><button class="btn btn-destaque" type="submit">Registrar</button></div>
    </form>
  </details>
  <details class="cartao bloco"><summary>+ Gerar parcelas do contrato</summary>
    <form method="post" action="<?= e(url('financeiro', 'gerar')) ?>" class="form-grade"><?= csrf_campo() ?>
      <label>Aluno<select name="aluno_id" required><option value="">—</option>
        <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?></select></label>
      <label>Descrição<input type="text" name="descricao" required maxlength="100" value="Parcela"></label>
      <label>Valor de cada parcela (R$)<input type="text" name="valor" required inputmode="decimal" placeholder="0,00"></label>
      <label>Quantidade<input type="number" name="quantidade" min="1" max="24" required value="3"></label>
      <label>Primeiro vencimento<input type="date" name="primeiro_vencimento" required></label>
      <div style="align-self:end"><button class="btn btn-destaque" type="submit">Gerar parcelas</button></div>
    </form>
  </details>
</div>

<form class="filtros" method="get" action="index.php">
  <input type="hidden" name="p" value="financeiro">
  <label>Aluno<select name="aluno"><option value="">Todos</option>
    <?php foreach ($alunos as $a): ?><option value="<?= (int)$a['id'] ?>" <?= $filtro['alunoId'] === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['nome']) ?></option><?php endforeach; ?></select></label>
  <label>Situação<select name="status"><option value="">Todas</option>
    <?php foreach (['pendente' => 'Pendente', 'atrasado' => 'Atrasado', 'pago' => 'Pago'] as $v => $r): ?>
      <option value="<?= $v ?>" <?= $filtro['status'] === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></label>
  <label>Mês de vencimento<input type="month" name="mes" value="<?= e($filtro['mes']) ?>"></label>
  <button class="btn" type="submit">Filtrar</button>
</form>

<section class="cartao">
  <?php if (!$parcelas): ?><p class="vazio">Nenhuma cobrança encontrada.</p><?php else: ?>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th>Vencimento</th><th>Aluno</th><th>Descrição</th><th class="num">Valor</th><th>Situação</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($parcelas as $p): ?>
      <tr>
        <td><?= e(data_br($p['vencimento'])) ?></td>
        <td><a href="<?= e(url('alunos', 'ver', ['id' => $p['aluno_id']])) ?>"><?= e($p['aluno']) ?></a></td>
        <td><?= e($p['descricao']) ?></td>
        <td class="num"><?= e(moeda($p['valor'])) ?></td>
        <td><?= selo($p['status'], $tipos[$p['status']] ?? 'neutro') ?>
          <?php if ($p['status'] === 'atrasado'): ?><small><?= (int)$p['dias_atraso'] ?> dia(s)</small><?php endif; ?>
          <?php if ($p['status'] === 'pago'): ?><small><?= e(data_br($p['data_pagamento'])) ?> · <?= e($p['forma_pagamento'] ?? '') ?></small><?php endif; ?></td>
        <td class="num"><?php require BASE_PATH . '/app/Views/financeiro/_acoes.php'; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>
