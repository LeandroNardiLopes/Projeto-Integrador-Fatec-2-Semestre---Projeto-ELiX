<?php /** Registro de ações (RF03). */
$nomes = [
  'login' => 'Entrou no sistema', 'logout' => 'Saiu do sistema', 'login_falhou' => 'Tentativa de login recusada',
  'cancelou_aula' => 'Aula cancelada', 'aula_cancelada' => 'Aula cancelada', 'aluno_cadastrado' => 'Aluno cadastrado',
  'aluno_editado' => 'Aluno editado', 'aluno_inativado' => 'Aluno inativado', 'aluno_reativado' => 'Aluno reativado',
  'lead_convertido' => 'Lead convertido em aluno', 'pagamento_registrado' => 'Pagamento registrado',
  'senha_alterada' => 'Senha alterada', 'backup_gerado' => 'Cópia de segurança gerada',
]; ?>
<p><a href="<?= e(url('conta')) ?>">← Minha conta</a></p>
<section class="cartao">
  <?php if (!$logs): ?><p class="vazio">Nenhum registro.</p><?php else: ?>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead><tr><th>Quando</th><th>Ação</th><th>Detalhe</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr><td style="white-space:nowrap"><?= e(date('d/m/Y H:i', strtotime($l['criado_em']))) ?></td>
        <td><?= e($nomes[$l['acao']] ?? $l['acao']) ?></td><td><small><?= e($l['detalhe'] ?? '') ?></small></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>
