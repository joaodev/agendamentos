<?php

$router = [];

/*
 * App routes
 */
$router['app'] = [
	['namespace' => 'app', 'route' => '/', 'controller' => 'index', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/politica-de-privacidade', 'controller' => 'politics', 'action' => 'user-view'],

    ['namespace' => 'app', 'route' => '/solicitar-demonstracao', 'controller' => 'demo', 'action' => 'index'],

	['namespace' => 'app', 'route' => '/inicio', 'controller' => 'index', 'action' => 'index'],
	
	['namespace' => 'app', 'route' => '/login', 'controller' => 'login', 'action' => 'index'],
    ['namespace' => 'app', 'route' => '/validar-token', 'controller' => 'login', 'action' => 'token-auth'],
    ['namespace' => 'app', 'route' => '/cancelar-token', 'controller' => 'login', 'action' => 'token-cancel'],
	['namespace' => 'app', 'route' => '/esqueci-a-senha', 'controller' => 'login', 'action' => 'forgot-password'],
	['namespace' => 'app', 'route' => '/validar-codigo', 'controller' => 'login', 'action' => 'code-validation'],
	['namespace' => 'app', 'route' => '/alterar-senha', 'controller' => 'login', 'action' => 'change-password'],
	['namespace' => 'app', 'route' => '/auth',  'controller' => 'login', 'action' => 'auth'],
	['namespace' => 'app', 'route' => '/sair',  'controller' => 'login', 'action' => 'logout'],

	['namespace' => 'app', 'route' => '/usuarios', 'controller' => 'user', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/usuarios/lista', 'controller' => 'user', 'action' => 'list'],
	['namespace' => 'app', 'route' => '/usuarios/detalhes', 'controller' => 'user', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/usuarios/cadastrar', 'controller' => 'user', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/usuarios/processa-cadastro', 'controller' => 'user', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/usuarios/editar', 'controller' => 'user', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/usuarios/processa-edicao', 'controller' => 'user', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/usuarios/excluir', 'controller' => 'user', 'action' => 'delete'],
	['namespace' => 'app', 'route' => '/usuarios/valor-existente', 'controller' => 'user', 'action' => 'field-exists'],
	['namespace' => 'app', 'route' => '/usuarios/acl', 'controller' => 'user', 'action' => 'acl'],
	['namespace' => 'app', 'route' => '/usuarios/altera-permissao', 'controller' => 'user', 'action' => 'alter-privilege'],

	['namespace' => 'app', 'route' => '/controle-acesso', 'controller' => 'role', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/controle-acesso/lista', 'controller' => 'role', 'action' => 'list'],
	['namespace' => 'app', 'route' => '/controle-acesso/detalhes', 'controller' => 'role', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/controle-acesso/cadastrar', 'controller' => 'role', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/controle-acesso/processa-cadastro', 'controller' => 'role', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/controle-acesso/editar', 'controller' => 'role', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/controle-acesso/processa-edicao', 'controller' => 'role', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/controle-acesso/excluir', 'controller' => 'role', 'action' => 'delete'],
	['namespace' => 'app', 'route' => '/controle-acesso/valor-existente', 'controller' => 'role', 'action' => 'field-exists'],

	['namespace' => 'app', 'route' => '/controle-acesso/permissoes', 'controller' => 'privilege', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/controle-acesso/altera-permissao', 'controller' => 'privilege', 'action' => 'change-privilege'],

	['namespace' => 'app', 'route' => '/configuracoes', 'controller' => 'config', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/configuracoes/processa-edicao', 'controller' => 'config', 'action' => 'update-process'],

	['namespace' => 'app', 'route' => '/politica-privacidade', 'controller' => 'politics', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/politica-privacidade/processa-edicao', 'controller' => 'politics', 'action' => 'update-process'],
	
	['namespace' => 'app', 'route' => '/termos-de-uso', 'controller' => 'use-terms', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/termos-de-uso/processa-edicao', 'controller' => 'use-terms', 'action' => 'update-process'],

    ['namespace' => 'app', 'route' => '/smtp', 'controller' => 'smtp', 'action' => 'index'],
    ['namespace' => 'app', 'route' => '/smtp/processa-edicao', 'controller' => 'smtp', 'action' => 'update-process'],

    ['namespace' => 'app', 'route' => '/logs', 'controller' => 'logs', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/logs/detalhes', 'controller' => 'logs', 'action' => 'read'],
	
	['namespace' => 'app', 'route' => '/meu-perfil', 'controller' => 'my-profile', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/meu-perfil/processa-edicao', 'controller' => 'my-profile', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/meu-perfil/valor-existente', 'controller' => 'my-profile', 'action' => 'field-exists'],

	['namespace' => 'app', 'route' => '/clientes', 'controller' => 'customers', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/clientes/detalhes', 'controller' => 'customers', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/clientes/cadastrar', 'controller' => 'customers', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/clientes/processa-cadastro', 'controller' => 'customers', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/clientes/editar', 'controller' => 'customers', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/clientes/processa-edicao', 'controller' => 'customers', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/clientes/excluir', 'controller' => 'customers', 'action' => 'delete'],
	['namespace' => 'app', 'route' => '/clientes/valor-existente', 'controller' => 'customers', 'action' => 'field-exists'],
	['namespace' => 'app', 'route' => '/clientes/busca-cliente', 'controller' => 'customers', 'action' => 'search'],

	['namespace' => 'app', 'route' => '/servicos', 'controller' => 'services', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/servicos/cadastrar', 'controller' => 'services', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/servicos/processa-cadastro', 'controller' => 'services', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/servicos/detalhes', 'controller' => 'services', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/servicos/editar', 'controller' => 'services', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/servicos/processa-edicao', 'controller' => 'services', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/servicos/excluir', 'controller' => 'services', 'action' => 'delete'],
	['namespace' => 'app', 'route' => '/servicos/valor-existente', 'controller' => 'services', 'action' => 'field-exists'],

	['namespace' => 'app', 'route' => '/despesas', 'controller' => 'expenses', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/despesas/cadastrar', 'controller' => 'expenses', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/despesas/processa-cadastro', 'controller' => 'expenses', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/despesas/detalhes', 'controller' => 'expenses', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/despesas/editar', 'controller' => 'expenses', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/despesas/processa-edicao', 'controller' => 'expenses', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/despesas/excluir', 'controller' => 'expenses', 'action' => 'delete'],

	['namespace' => 'app', 'route' => '/formas-pagamento', 'controller' => 'payment-types', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/formas-pagamento/cadastrar', 'controller' => 'payment-types', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/formas-pagamento/processa-cadastro', 'controller' => 'payment-types', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/formas-pagamento/detalhes', 'controller' => 'payment-types', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/formas-pagamento/editar', 'controller' => 'payment-types', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/formas-pagamento/processa-edicao', 'controller' => 'payment-types', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/formas-pagamento/excluir', 'controller' => 'payment-types', 'action' => 'delete'],
	['namespace' => 'app', 'route' => '/formas-pagamento/valor-existente', 'controller' => 'payment-types', 'action' => 'field-exists'],

    ['namespace' => 'app', 'route' => '/agendamentos', 'controller' => 'schedules', 'action' => 'index'],
    ['namespace' => 'app', 'route' => '/agendamentos/cadastrar', 'controller' => 'schedules', 'action' => 'create'],
    ['namespace' => 'app', 'route' => '/agendamentos/processa-cadastro', 'controller' => 'schedules', 'action' => 'create-process'],
    ['namespace' => 'app', 'route' => '/agendamentos/detalhes', 'controller' => 'schedules', 'action' => 'read'],
    ['namespace' => 'app', 'route' => '/agendamentos/editar', 'controller' => 'schedules', 'action' => 'update'],
    ['namespace' => 'app', 'route' => '/agendamentos/processa-edicao', 'controller' => 'schedules', 'action' => 'update-process'],
    ['namespace' => 'app', 'route' => '/agendamentos/excluir', 'controller' => 'schedules', 'action' => 'delete'],
    ['namespace' => 'app', 'route' => '/agendamentos/detalhes-servico', 'controller' => 'schedules', 'action' => 'service-details'],

	['namespace' => 'app', 'route' => '/agendamentos/cadastrar-servico', 'controller' => 'schedules', 'action' => 'create-service'],
	['namespace' => 'app', 'route' => '/agendamentos/cadastrar-cliente', 'controller' => 'schedules', 'action' => 'create-customer'],

    ['namespace' => 'app', 'route' => '/financeiro', 'controller' => 'financial', 'action' => 'index'],
    ['namespace' => 'app', 'route' => '/financeiro/cadastrar', 'controller' => 'financial', 'action' => 'create'],
    ['namespace' => 'app', 'route' => '/financeiro/processa-cadastro', 'controller' => 'financial', 'action' => 'create-process'],
    ['namespace' => 'app', 'route' => '/financeiro/detalhes', 'controller' => 'financial', 'action' => 'read'],
    ['namespace' => 'app', 'route' => '/financeiro/editar', 'controller' => 'financial', 'action' => 'update'],
    ['namespace' => 'app', 'route' => '/financeiro/processa-edicao', 'controller' => 'financial', 'action' => 'update-process'],
    ['namespace' => 'app', 'route' => '/financeiro/excluir', 'controller' => 'financial', 'action' => 'delete'],

	['namespace' => 'app', 'route' => '/planos', 'controller' => 'plans', 'action' => 'index'],
	['namespace' => 'app', 'route' => '/planos-usuario', 'controller' => 'plans', 'action' => 'user-plans'],
	['namespace' => 'app', 'route' => '/planos/cadastrar', 'controller' => 'plans', 'action' => 'create'],
	['namespace' => 'app', 'route' => '/planos/processa-cadastro', 'controller' => 'plans', 'action' => 'create-process'],
	['namespace' => 'app', 'route' => '/planos/detalhes', 'controller' => 'plans', 'action' => 'read'],
	['namespace' => 'app', 'route' => '/planos/editar', 'controller' => 'plans', 'action' => 'update'],
	['namespace' => 'app', 'route' => '/planos/processa-edicao', 'controller' => 'plans', 'action' => 'update-process'],
	['namespace' => 'app', 'route' => '/planos/excluir', 'controller' => 'plans', 'action' => 'delete'],
	['namespace' => 'app', 'route' => '/planos/valor-existente', 'controller' => 'plans', 'action' => 'field-exists'],
	['namespace' => 'app', 'route' => '/planos/plano-selecionado', 'controller' => 'plans', 'action' => 'selected-plan'],
];

$systemDir = match ($_SERVER['HTTP_HOST']) {
    'localhost' => "/agendamentos",
    default => "",
};

$app = new Core\Init\Bootstrap($router, $systemDir);