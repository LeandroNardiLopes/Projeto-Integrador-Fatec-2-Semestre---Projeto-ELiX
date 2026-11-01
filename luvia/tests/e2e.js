/**
 * Teste de ponta a ponta do Luvia (navegador automático com Playwright).
 * Uso: node tests/e2e.js http://127.0.0.1:8000 [v1|v2]
 * Precisa do banco recém-criado com database/luvia_mysql.sql.
 */
const { chromium } = require('playwright');

const BASE = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const VERSAO = process.argv[3] || 'v2';
const EMAIL = 'mentora@luvia.local';
const SENHA = 'SenhaForte2026';
const resultados = [];

function ok(nome, cond, detalhe = '') {
  resultados.push({ nome, ok: !!cond, detalhe });
  console.log(`${cond ? 'PASSOU' : 'FALHOU'} · ${nome}${detalhe && !cond ? ' · ' + detalhe : ''}`);
}

async function semErroPhp(page, nome) {
  const html = await page.content();
  const erro = /(Warning|Notice|Deprecated|Fatal error|Uncaught|Parse error)\b.*?(on line|in \/)/i.test(html) || /Stack trace/.test(html);
  const status = page.__ultimoStatus || 200;
  ok(`${nome} abre sem erro`, !erro && status < 500, erro ? 'aviso/erro do PHP na página' : `HTTP ${status}`);
}

async function ir(page, caminho, nome) {
  const r = await page.goto(BASE + '/' + caminho);
  page.__ultimoStatus = r ? r.status() : 0;
  await semErroPhp(page, nome);
}

async function aviso(page) {
  const a = await page.locator('.aviso').allTextContents();
  return a.join(' | ');
}

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ acceptDownloads: true });
  const page = await ctx.newPage();
  const hoje = new Date();
  const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  const hojeIso = iso(hoje);

  // ---------- Acesso ----------
  await ir(page, 'index.php?p=painel', 'Painel sem login');
  ok('Página interna sem login vai para o login', page.url().includes('p=login'));
  ok('Primeiro acesso pede para criar a senha', await page.locator('text=Primeiro acesso').count() > 0);

  await page.fill('input[name=nome]', 'Lilian Mesquita');
  await page.fill('input[name=email]', EMAIL);
  await page.fill('input[name=senha]', 'curta');
  await page.fill('input[name=confirma]', 'curta');
  await page.evaluate(() => document.querySelectorAll('input[minlength]').forEach(i => i.removeAttribute('minlength')));
  await page.click('button[type=submit]');
  ok('Senha curta é recusada', (await aviso(page)).includes('8 caracteres'));

  await page.fill('input[name=nome]', 'Lilian Mesquita');
  await page.fill('input[name=email]', EMAIL);
  await page.fill('input[name=senha]', SENHA);
  await page.fill('input[name=confirma]', SENHA);
  await page.click('button[type=submit]');
  ok('Senha criada no primeiro acesso', (await aviso(page)).includes('Senha criada'));

  await page.fill('input[name=email]', EMAIL);
  await page.fill('input[name=senha]', 'errada123');
  await page.click('button[type=submit]');
  ok('Senha errada é recusada', (await aviso(page)).includes('incorretos'));

  await page.fill('input[name=email]', "' OR 1=1 -- ");
  await page.evaluate(() => document.querySelector('input[name=email]').type = 'text');
  await page.fill('input[name=email]', "' OR '1'='1' -- ");
  await page.fill('input[name=senha]', "' OR '1'='1");
  await page.click('button[type=submit]');
  ok('SQL injection no login não entra', page.url().includes('p=login') && (await aviso(page)).includes('incorretos'));

  await page.fill('input[name=email]', EMAIL);
  await page.fill('input[name=senha]', SENHA);
  await page.click('button[type=submit]');
  ok('Login correto abre o painel', page.url().includes('p=painel'));
  await semErroPhp(page, 'Painel');
  ok('Painel mostra alunos ativos = 3', (await page.locator('.kpi').first().textContent()).includes('3'));

  // ---------- Menu da versão ----------
  const menu = (await page.locator('.menu a').allTextContents()).map(t => t.trim());
  if (VERSAO === 'v1') {
    ok('Menu da v1 tem só Painel, Agenda e Alunos', menu.length === 3, menu.join(', '));
  } else {
    ok('Menu da v2 tem todos os módulos', menu.length === 8, menu.join(', '));
  }

  // ---------- Agenda ----------
  await ir(page, 'index.php?p=agenda', 'Agenda (semana atual)');
  ok('Agenda mostra compromissos', await page.locator('.compromisso').count() > 0);
  ok('Agenda destaca horário nobre', await page.locator('.compromisso.nobre').count() > 0);
  const prox = new Date(hoje); prox.setDate(prox.getDate() + 7);
  await ir(page, 'index.php?p=agenda&semana=' + iso(prox), 'Agenda (próxima semana)');

  // conflito: Marina tem aula 07:00-07:50 em dias úteis; achar o próximo dia útil
  const util = new Date(hoje); do { util.setDate(util.getDate() + 1); } while ([0, 6].includes(util.getDay()));
  await ir(page, 'index.php?p=agenda&a=novo', 'Agendar (formulário)');
  await page.selectOption('select[name=aluno_id]', { label: 'Tiago Exemplo' });
  await page.fill('input[name=data]', iso(util));
  await page.fill('input[name=inicio]', '07:20');
  await page.fill('input[name=fim]', '08:00');
  await page.click('form.cartao button[type=submit]');
  ok('RN04: conflito de horário é recusado', (await aviso(page)).includes('Conflito'));

  // agendamento válido hoje à noite (para registrar presença)
  await page.fill('input[name=data]', hojeIso);
  await page.fill('input[name=inicio]', '22:00');
  await page.fill('input[name=fim]', '22:30');
  await page.click('form.cartao button[type=submit]');
  ok('RF13: aula agendada', (await aviso(page)).includes('Agendado'));
  await semErroPhp(page, 'Agenda após agendar');

  // presença na aula de hoje
  const card = page.locator('.dia.hoje .compromisso', { hasText: 'Tiago Exemplo' }).filter({ hasText: '22:00' });
  await card.locator('button', { hasText: 'Faltou' }).click();
  ok('RF20: falta registrada', (await aviso(page)).includes('Falta registrada'));
  // reposição da falta
  const card2 = page.locator('.dia.hoje .compromisso', { hasText: 'Tiago Exemplo' }).filter({ hasText: '22:00' });
  await card2.locator('summary', { hasText: 'Agendar reposição' }).click();
  const sab = new Date(hoje); sab.setDate(sab.getDate() + ((6 - sab.getDay() + 7) % 7 || 7));
  await card2.locator('input[name=data]').fill(iso(sab));
  await card2.locator('input[name=inicio]').fill('15:00');
  await card2.locator('input[name=fim]').fill('15:50');
  await card2.locator('button', { hasText: 'Agendar reposição' }).click();
  ok('RF16: reposição agendada', (await aviso(page)).includes('Reposição agendada'));

  // cancelar sem motivo (HTML exige; força via remoção do required) e com motivo
  await ir(page, 'index.php?p=agenda&semana=' + iso(sab), 'Agenda da semana da reposição');
  const rep = page.locator('.compromisso', { hasText: 'Reposição' }).filter({ hasText: 'Tiago' }).first();
  await rep.locator('summary', { hasText: 'Cancelar' }).click();
  await rep.locator('input[name=motivo]').evaluate(i => i.removeAttribute('required'));
  await rep.locator('button', { hasText: 'Confirmar cancelamento' }).click();
  ok('RN05: cancelar sem motivo é recusado', (await aviso(page)).includes('motivo'));
  const rep2 = page.locator('.compromisso', { hasText: 'Reposição' }).filter({ hasText: 'Tiago' }).first();
  await rep2.locator('summary', { hasText: 'Cancelar' }).click();
  await rep2.locator('input[name=motivo]').fill('Aluno pediu outro horário');
  await rep2.locator('button', { hasText: 'Confirmar cancelamento' }).click();
  ok('RF15: cancelamento com motivo', (await aviso(page)).includes('Cancelado'));

  // ---------- Alunos ----------
  await ir(page, 'index.php?p=alunos', 'Lista de alunos');
  ok('Lista mostra os 3 alunos fictícios', await page.locator('table.tabela tbody tr').count() === 3);
  await ir(page, "index.php?p=alunos&busca=" + encodeURIComponent("' OR '1'='1"), 'Busca com SQL injection');
  ok('SQL injection na busca não traz alunos', await page.locator('table.tabela tbody tr').count() === 0);
  await ir(page, 'index.php?p=alunos&situacao=todos&nicho=Advocacia', 'Filtro por nicho');
  ok('Filtro por nicho funciona', await page.locator('table.tabela tbody tr').count() === 1);

  await ir(page, 'index.php?p=alunos&a=novo', 'Novo aluno (formulário)');
  await page.fill('input[name=nome]', '<script>alert(1)</script> Aluna Teste');
  await page.fill('input[name=email]', 'teste@exemplo.com');
  await page.fill('input[name=nicho]', 'Medicina');
  await page.fill('input[name=data_inicio]', hojeIso);
  await page.selectOption('select[name=curso_id]', { index: 3 });
  await page.fill('input[name=cena_objetivo]', 'Apresentar um caso clínico em congresso');
  await page.selectOption('select[name=nivel_inicial]', 'social');
  await page.selectOption('select[name=nivel_alvo]', 'profissional');
  await page.click('form.cartao button[type=submit]');
  ok('RF05/RF19: aluno cadastrado com curso montado', (await aviso(page)).includes('curso foi montado'));
  await semErroPhp(page, 'Ficha do novo aluno');
  const html = await page.content();
  ok('XSS: nome com <script> aparece escapado', html.includes('&lt;script&gt;') && !html.includes('<script>alert(1)</script>'));
  ok('Cronograma com 12 semanas', await page.locator('table.cronograma tbody tr').count() === 12);
  const fichaUrl = page.url();
  const novoId = new URL(fichaUrl).searchParams.get('id');

  await ir(page, 'index.php?p=alunos&a=editar&id=' + novoId, 'Editar aluno');
  await page.fill('input[name=nome]', 'Aluna Teste');
  await page.fill('input[name=contrato_link]', 'javascript:alert(1)');
  await page.evaluate(() => document.querySelector('input[name=contrato_link]').type = 'text');
  await page.click('form.cartao button[type=submit]');
  ok('Link de contrato inválido é recusado', (await aviso(page)).includes('http'));
  await page.fill('input[name=contrato_link]', 'https://exemplo.com/contrato.pdf');
  await page.click('form.cartao button[type=submit]');
  ok('RF06: aluno editado', (await aviso(page)).includes('atualizados'));

  for (const nome of ['Marina Exemplo', 'Tiago Exemplo', 'Helena Exemplo']) {
    await ir(page, 'index.php?p=alunos&situacao=todos', 'Lista');
    await page.click(`text=${nome}`);
    await semErroPhp(page, `Ficha ${nome}`);
  }
  ok('Ficha da Helena mostra semana atrasada', await page.locator('table.cronograma .selo-erro', { hasText: 'atrasada' }).count() > 0);

  // inativar e reativar
  await page.goto(fichaUrl.replace(/id=\d+/, 'id=' + novoId));
  await page.locator('summary', { hasText: 'Inativar aluno' }).click();
  await page.fill('#dados input[name=motivo]', 'Pausou o contrato');
  await page.click('text=Confirmar inativação');
  ok('RF07: aluno inativado', (await aviso(page)).includes('inativado'));
  await page.click('text=Reativar aluno');
  ok('RF07: aluno reativado', (await aviso(page)).includes('reativado'));

  if (VERSAO === 'v1') {
    ok('v1: rota de leads não existe', (await page.goto(BASE + '/index.php?p=leads')).status() === 404);
    ok('v1: rota de financeiro não existe', (await page.goto(BASE + '/index.php?p=financeiro')).status() === 404);
  } else {
    // ---------- Etapas ----------
    await page.goto(BASE + '/index.php?p=alunos&a=ver&id=' + novoId);
    await page.locator('table.cronograma tbody tr').first().locator('button', { hasText: 'Concluir' }).click();
    ok('RF21: semana concluída', (await aviso(page)).includes('concluída'));
    await page.locator('summary', { hasText: 'Adicionar etapa personalizada' }).click();
    await page.locator('#cronograma details.bloco input[name=titulo]').fill('Ensaio extra: palestra de 5 minutos');
    await page.locator('#cronograma details.bloco button[type=submit]').click();
    ok('RF19: etapa personalizada adicionada', await page.locator('table.cronograma tbody tr').count() === 13);
    await page.locator('table.cronograma tbody tr').last().locator('button[title=Subir]').click();
    const penultima = await page.locator('table.cronograma tbody tr').nth(11).textContent();
    ok('RF19: etapa movida para cima', penultima.includes('Ensaio extra'));

    // ---------- Atividades e diário ----------
    await page.locator('summary', { hasText: 'Nova atividade' }).click();
    await page.fill('#atividades input[name=titulo]', 'Gravar a apresentação pessoal');
    await page.fill('#atividades input[name=data_entrega]', hojeIso);
    await page.locator('#atividades button', { hasText: 'Salvar atividade' }).click();
    ok('RF22: atividade cadastrada', (await aviso(page)).includes('Atividade cadastrada'));
    await page.locator('#atividades select[name=status]').first().selectOption('entregue');
    await page.locator('#atividades button', { hasText: 'Salvar' }).first().click();
    ok('RF22: status da atividade alterado', (await aviso(page)).includes('Status'));
    await page.fill('#diario textarea[name=anotacao]', 'Primeira aula: Mapa da Cena definido.');
    await page.locator('#diario button', { hasText: 'Registrar' }).click();
    ok('RF23: anotação no diário', (await aviso(page)).includes('Anotação registrada'));
    await page.locator('#diario button', { hasText: 'Excluir' }).first().click();
    ok('RF23: anotação excluída', (await aviso(page)).includes('excluída'));

    // ---------- Leads ----------
    await ir(page, 'index.php?p=leads', 'Leads');
    await ir(page, 'index.php?p=leads&a=novo', 'Novo lead');
    await page.fill('input[name=nome]', 'Lead Teste');
    await page.fill('input[name=email]', 'leadteste@exemplo.com');
    await page.selectOption('select[name=origem]', 'Instagram');
    await page.click('form.cartao button[type=submit]');
    ok('RF10: lead cadastrado', (await aviso(page)).includes('Lead cadastrado'));
    const linhaLead = page.locator('tr', { hasText: 'Lead Teste' });
    await linhaLead.locator('select[name=status]').selectOption('em_contato');
    await linhaLead.locator('button', { hasText: 'Mudar' }).click();
    ok('RF11: etapa do funil alterada', (await aviso(page)).includes('Etapa'));
    await page.locator('tr', { hasText: 'Lead Teste' }).locator('button', { hasText: 'Converter em aluno' }).click();
    ok('RF12: lead convertido em aluno', (await aviso(page)).includes('convertido'));
    await semErroPhp(page, 'Ficha do lead convertido');
    await page.selectOption('#cronograma select[name=curso_id]', { index: 1 });
    await page.locator('#cronograma button', { hasText: 'Montar o curso' }).click();
    ok('RF19: curso montado na ficha', (await aviso(page)).includes('Curso montado'));

    // ---------- Cursos ----------
    await ir(page, 'index.php?p=cursos', 'Cursos');
    ok('Cursos mostra os 3 cursos', await page.locator('.grade-3 .cartao').count() === 3);
    await ir(page, 'index.php?p=cursos&a=ver&id=1', 'Curso VIP Online');
    ok('Curso tem 6 módulos e 12 semanas', await page.locator('#modulos tbody tr').count() === 6 && await page.locator('#lista tbody tr').count() === 12);
    await ir(page, 'index.php?p=cursos&a=novo', 'Novo curso');
    await page.fill('input[name=nome]', 'Sprint de 30 dias');
    await page.selectOption('select[name=modalidade]', 'gravada');
    await page.fill('input[name=aulas_online_semana]', '0');
    await page.fill('input[name=aulas_gravadas_semana]', '3');
    await page.fill('input[name=duracao_semanas]', '4');
    await page.click('form.cartao button[type=submit]');
    ok('RF18: curso criado', (await aviso(page)).includes('Curso criado'));
    await page.locator('summary', { hasText: 'Novo módulo' }).click();
    await page.fill('#modulos details.bloco input[name=titulo]', 'Módulo 1 · Cenário');
    await page.fill('#modulos details.bloco input[name=pilar]', 'Cenário');
    await page.fill('#modulos details.bloco input[name=semana_fim]', '4');
    await page.locator('#modulos details.bloco button').click();
    ok('RF18: módulo salvo', (await aviso(page)).includes('Módulo salvo'));
    await page.locator('summary', { hasText: 'Nova semana' }).click();
    await page.fill('#lista details.bloco input[name=titulo]', 'A vaga e a apresentação pessoal');
    await page.locator('#lista details.bloco button').click();
    ok('RF18: semana salva', (await aviso(page)).includes('Semana salva'));

    // ---------- Materiais ----------
    await ir(page, 'index.php?p=materiais', 'Materiais');
    await page.locator('summary', { hasText: 'Novo material' }).click();
    await page.fill('details.bloco >> nth=0 >> input[name=titulo]', 'Lista de verbos de rotina');
    await page.fill('details.bloco >> nth=0 >> input[name=link]', 'https://exemplo.com/verbos.pdf');
    await page.locator('details.bloco').nth(0).locator('button').click();
    ok('RF24: material cadastrado', (await aviso(page)).includes('Material cadastrado'));
    await page.locator('summary', { hasText: 'Nova gravação' }).click();
    await page.fill('details.bloco >> nth=1 >> input[name=titulo]', 'Aula extra de pronúncia');
    await page.fill('details.bloco >> nth=1 >> input[name=link]', 'https://exemplo.com/aula-extra');
    await page.locator('details.bloco').nth(1).locator('button').click();
    ok('RF25: gravação cadastrada', (await aviso(page)).includes('Gravação cadastrada'));

    // ---------- Financeiro ----------
    await ir(page, 'index.php?p=financeiro', 'Financeiro');
    await page.locator('summary', { hasText: 'Gerar parcelas' }).click();
    const gerar = page.locator('details', { hasText: 'Gerar parcelas' });
    await gerar.locator('select[name=aluno_id]').selectOption({ label: 'Aluna Teste' });
    await gerar.locator('input[name=valor]').fill('150,00');
    await gerar.locator('input[name=primeiro_vencimento]').fill(iso(new Date(hoje.getFullYear(), hoje.getMonth(), 31)));
    await gerar.locator('button').click();
    ok('RF26: 3 parcelas geradas', (await aviso(page)).includes('3 parcela'));
    const linhaPag = page.locator('tr', { hasText: 'Aluna Teste' }).first();
    await linhaPag.locator('summary', { hasText: 'Registrar pagamento' }).click();
    await linhaPag.locator('button', { hasText: 'Confirmar pagamento' }).click();
    ok('RF27: pagamento registrado', (await aviso(page)).includes('Pagamento registrado'));
    ok('RF28: parcela atrasada aparece', await page.locator('.selo-erro', { hasText: 'atrasado' }).count() > 0);

    // ---------- Conta ----------
    await ir(page, 'index.php?p=conta', 'Minha conta');
    await ir(page, 'index.php?p=conta&a=logs', 'Registro de ações');
    ok('RF03: log registra login e cancelamento', (await page.content()).includes('Entrou no sistema') && (await page.content()).includes('Aula cancelada'));
    await page.goto(BASE + '/index.php?p=conta');
    const [download] = await Promise.all([page.waitForEvent('download'), page.click('text=Baixar cópia de segurança')]);
    const caminho = '/tmp/luvia-backup-teste.sql';
    await download.saveAs(caminho);
    const fs = require('fs');
    const conteudo = fs.readFileSync(caminho, 'utf8');
    ok('RF04: cópia de segurança gerada', conteudo.startsWith('-- LUVIA BACKUP') && conteudo.includes('INSERT INTO `alunos`'));

    // arquivo alterado com comando perigoso é recusado
    fs.writeFileSync('/tmp/luvia-backup-alterado.sql', conteudo + 'DROP TABLE alunos;\n');
    await page.locator('summary', { hasText: 'Restaurar' }).click();
    await page.setInputFiles('input[name=arquivo]', '/tmp/luvia-backup-alterado.sql');
    await page.fill('input[name=confirmo]', 'SIM');
    await page.click('button:has-text("Restaurar")');
    ok('RF04: arquivo alterado é recusado', (await aviso(page)).includes('inesperado'));

    // apaga um aluno pelo banco? Não: restaura a cópia e confere que voltou
    await page.locator('summary', { hasText: 'Restaurar' }).click();
    await page.setInputFiles('input[name=arquivo]', caminho);
    await page.fill('input[name=confirmo]', 'SIM');
    await page.click('button:has-text("Restaurar")');
    ok('RF04: cópia restaurada', (await aviso(page)).includes('restaurada'));
    await page.fill('input[name=email]', EMAIL);
    await page.fill('input[name=senha]', SENHA);
    await page.click('button[type=submit]');
    ok('Login depois da restauração', page.url().includes('p=painel'));

    // troca de senha
    await page.goto(BASE + '/index.php?p=conta');
    await page.fill('input[name=atual]', 'errada');
    await page.fill('input[name=nova]', 'NovaSenha2026');
    await page.fill('input[name=confirma]', 'NovaSenha2026');
    await page.click('button:has-text("Trocar senha")');
    ok('RF02: senha atual errada é recusada', (await aviso(page)).includes('não confere'));
  }

  // ---------- CSRF e saída ----------
  const semToken = await page.request.post(BASE + '/index.php?p=agenda&a=cancelar', { form: { id: '1', motivo: 'teste' } });
  ok('CSRF: formulário sem token é recusado', semToken.status() === 400);
  await ir(page, 'index.php?p=nao_existe', 'Página inexistente');
  ok('Página inexistente responde 404', page.__ultimoStatus === 404);
  await page.goto(BASE + '/index.php?p=painel');
  await page.click('text=Sair');
  await page.goto(BASE + '/index.php?p=alunos');
  ok('Depois de sair, páginas internas pedem login', page.url().includes('p=login'));

  await browser.close();
  const falhas = resultados.filter(r => !r.ok);
  console.log(`\nRESULTADO: ${resultados.length - falhas.length} de ${resultados.length} testes passaram.`);
  process.exit(falhas.length ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
