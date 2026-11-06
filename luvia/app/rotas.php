<?php
/**
 * Rotas do sistema: cada módulo (p=) e as ações (a=) que ele aceita.
 * 'menu' define o item no menu lateral; 'publica' = não exige login.
 */
return [
    'login' => [
        'controller' => 'AuthController', 'publica' => true,
        'acoes' => ['index', 'entrar', 'sair', 'definir_senha'],
    ],
    'painel' => [
        'controller' => 'PainelController', 'menu' => ['Painel', 'painel'],
        'acoes' => ['index'],
    ],
    'agenda' => [
        'controller' => 'AgendaController', 'menu' => ['Agenda', 'agenda'],
        'acoes' => ['index', 'novo', 'salvar', 'cancelar', 'frequencia', 'reposicao'],
    ],
    'alunos' => [
        'controller' => 'AlunoController', 'menu' => ['Alunos', 'alunos'],
        'acoes' => ['index', 'novo', 'salvar', 'editar', 'atualizar', 'ver', 'inativar', 'reativar'],
    ],
    'leads' => [
        'controller' => 'LeadController', 'menu' => ['Leads', 'leads'],
        'acoes' => ['index', 'novo', 'salvar', 'editar', 'atualizar', 'status', 'converter'],
    ],
    'cursos' => [
        'controller' => 'CursoController', 'menu' => ['Cursos', 'cursos'],
        'acoes' => ['index', 'novo', 'salvar', 'editar', 'atualizar', 'ver',
                    'salvar_modulo', 'excluir_modulo', 'salvar_aula', 'excluir_aula'],
    ],
    'etapas' => [
        'controller' => 'EtapaController',
        'acoes' => ['aplicar', 'concluir', 'reabrir', 'salvar', 'excluir', 'mover'],
    ],
    'atividades' => [
        'controller' => 'AtividadeController',
        'acoes' => ['salvar', 'status'],
    ],
    'diario' => [
        'controller' => 'DiarioController',
        'acoes' => ['salvar', 'excluir'],
    ],
    'materiais' => [
        'controller' => 'MaterialController', 'menu' => ['Materiais', 'materiais'],
        'acoes' => ['index', 'salvar', 'excluir', 'salvar_gravacao', 'excluir_gravacao'],
    ],
    'financeiro' => [
        'controller' => 'FinanceiroController', 'menu' => ['Financeiro', 'financeiro'],
        'acoes' => ['index', 'salvar', 'gerar', 'pagar', 'excluir'],
    ],
    'conta' => [
        'controller' => 'ContaController', 'menu' => ['Minha conta', 'conta'],
        'acoes' => ['index', 'perfil', 'senha', 'logs', 'backup', 'restaurar'],
    ],
];
