<?php /** Funil de leads (RF10 a RF12). */
$tipos = ['novo' => 'info', 'em_contato' => 'aviso', 'convertido' => 'ok', 'desistiu' => 'neutro']; ?>
<section class="kpis" aria-label="Funil">
  <?php foreach ($funil as $f): ?>
    <div class="kpi"><a href="<?= e(url('leads', null, ['status' => $f['status']])) ?>">
      <div class="rotulo"><?= e(Lead::STATUS[$f['status']] ?? $f['status']) ?></div><div class="valor"><?= (int)$f['quantidade'] ?></div></a></div>
  <?php endforeach; ?>
</section>

<form class="filtros" method="get" action="index.php">
  <input type="hidden" name="p" value="leads">
  <label>Buscar<input type="search" name="busca" value="<?= e($filtro['busca']) ?>" placeholder="nome, e-mail ou nicho"></label>
  <label>Etapa
    <select name="status"><option value="">Todas</option>
      <?php foreach (Lead::STATUS as $v => $r): ?><option value="<?= $v ?>" <?= $filtro['status'] === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
    </select>
  </label>
  <button class="btn" type="submit">Filtrar</button>
</form>

<section class="cartao">
  <?php if (!$leads): ?><p class="vazio">Nenhum lead encontrado.</p><?php else: ?>
  <div class="tabela-rolagem">
    <table class="tabela">
      <thead><tr><th>Lead</th><th>Origem</th><th>Nicho</th><th>Etapa</th><th>Entrevista</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($leads as $l): ?>
        <tr>
          <td><strong><?= e($l['nome']) ?></strong><div class="suave"><?= e($l['email'] ?? '') ?> <?= e($l['telefone'] ?? '') ?></div>
            <?php if ($l['observacao']): ?><div class="suave"><?= e($l['observacao']) ?></div><?php endif; ?></td>
          <td><?= e($l['origem'] ?? '—') ?></td>
          <td><?= e($l['nicho'] ?? '—') ?></td>
          <td><?= selo(Lead::STATUS[$l['status']], $tipos[$l['status']]) ?>
            <?php if ($l['aluno_id']): ?><div><a href="<?= e(url('alunos', 'ver', ['id' => $l['aluno_id']])) ?>">ver aluno</a></div><?php endif; ?></td>
          <td><?= $l['proxima_entrevista'] ? e(data_curta($l['proxima_entrevista']) . ' ' . hora_br(substr($l['proxima_entrevista'], 11))) : '—' ?></td>
          <td class="num">
            <span class="botoes">
            <?php if ($l['status'] !== 'convertido'): ?>
              <form class="em-linha" method="post" action="<?= e(url('leads', 'status')) ?>"><?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                <select name="status" aria-label="Mudar etapa" style="width:auto;padding:3px 6px">
                  <?php foreach (['novo', 'em_contato', 'desistiu'] as $s): ?>
                    <option value="<?= $s ?>" <?= $l['status'] === $s ? 'selected' : '' ?>><?= Lead::STATUS[$s] ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-pequeno" type="submit">Mudar</button>
              </form>
              <a class="btn btn-pequeno" href="<?= e(url('agenda', 'novo', ['lead' => $l['id']])) ?>">Entrevista</a>
              <form class="em-linha" method="post" action="<?= e(url('leads', 'converter')) ?>"><?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                <button class="btn btn-pequeno btn-destaque" type="submit">Converter em aluno</button>
              </form>
            <?php endif; ?>
              <a class="btn btn-pequeno" href="<?= e(url('leads', 'editar', ['id' => $l['id']])) ?>">Editar</a>
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
