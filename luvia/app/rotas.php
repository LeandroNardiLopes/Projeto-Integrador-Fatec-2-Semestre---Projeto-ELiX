<?php
/**
 * Rotas do sistema: cada módulo (p=) e as ações (a=) que ele aceita.
 * 'menu' define o item no menu lateral; 'publica' = não exige login.
 *
 * VERSÃO 1: só login, painel, agenda e alunos. Os outros módulos entram na versão 2.
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
];
