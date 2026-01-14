<?php
// public/api/profile.php

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/supabase.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

function bad($msg, $code = 400) {
  json_out(['ok' => false, 'error' => $msg], $code);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '{}', true);
if (!is_array($data)) $data = [];

$action = (string)($data['action'] ?? '');
if ($action !== 'update_profile') {
  bad('Ação inválida.');
}

$email = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
if ($email === '') {
  bad('Sessão inválida. Faça login novamente.', 401);
}

$full_name = trim((string)($data['full_name'] ?? ''));
$phone     = preg_replace('/\D+/', '', (string)($data['phone'] ?? ''));

// remove +55 se vier
if (strlen($phone) >= 12 && str_starts_with($phone, '55')) $phone = substr($phone, 2);

if ($full_name === '') bad('Nome é obrigatório.');
if ($phone !== '' && !(strlen($phone) === 10 || strlen($phone) === 11)) {
  bad('Telefone inválido. Use DDD + número (10 ou 11 dígitos), sem +55.');
}

// Primeiro: tenta atualizar (se existir)
$upd = supabase_request('PATCH', '/rest/v1/profiles', [
  'email' => 'eq.' . $email,
], [
  'full_name' => $full_name,
  'phone'     => $phone,
]);

// Se não atualizou nada (linha inexistente), faz INSERT (UPSERT simples)
if (empty($upd['ok'])) {
  $msg = $upd['error']['message'] ?? $upd['error']['msg'] ?? '';
  // se deu erro por não existir/0 rows, fazemos insert
  // (em muitos casos o PATCH retorna ok mesmo com 0 linhas; se seu supabase_request retornar rows, melhor)
}

// Garante que exista: se não existir, cria
$chk = supabase_request('GET', '/rest/v1/profiles', [
  'select' => 'id,email',
  'email'  => 'eq.' . $email,
  'limit'  => 1,
]);

if (!empty($chk['ok']) && empty($chk['data'][0])) {
  // cria novo profile
  $ins = supabase_request('POST', '/rest/v1/profiles', [], [
    'email'     => $email,
    'full_name' => $full_name,
    'phone'     => $phone,
  ]);

  if (empty($ins['ok'])) {
    $msg = $ins['error']['message'] ?? $ins['error']['msg'] ?? 'Falha ao criar profile.';
    bad($msg, 500);
  }
}

// Atualiza sessão (pra refletir imediatamente sem recarregar)
$_SESSION['user']['name']  = $full_name;
$_SESSION['user']['phone'] = $phone;

json_out(['ok' => true, 'data' => ['full_name' => $full_name, 'phone' => $phone]]);