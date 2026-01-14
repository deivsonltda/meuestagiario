<?php
// public/api/account.php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/supabase.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

function bad($msg, $code = 400) {
  json_out(['ok' => false, 'error' => $msg], $code);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '[]', true);
if (!is_array($body)) $body = [];

$action = (string)($body['action'] ?? '');

if ($action !== 'change_password') {
  bad('Ação inválida.');
}

$newPass = (string)($body['password'] ?? '');
$confirm = (string)($body['password_confirm'] ?? '');

if (strlen($newPass) < 8) bad('A senha deve ter no mínimo 8 caracteres.');
if ($newPass !== $confirm) bad('As senhas não conferem.');

$email = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
if ($email === '') bad('Sessão inválida. Faça login novamente.', 401);

// 1) tenta achar o ID pelo profiles (por EMAIL, não por user_key)
$userId = '';
$res = supabase_request('GET', '/rest/v1/profiles', [
  'select' => 'id,email',
  'email'  => 'eq.' . $email,
  'limit'  => '1',
]);

if (!empty($res['ok']) && !empty($res['data'][0]['id'])) {
  $userId = (string)$res['data'][0]['id'];
}

// 2) se não achou no profiles, tenta achar no Auth Admin por email
if ($userId === '') {
  $list = supabase_request('GET', '/auth/v1/admin/users', [
    'page'     => '1',
    'per_page' => '200',
  ]);

  if (!empty($list['ok']) && !empty($list['data']['users']) && is_array($list['data']['users'])) {
    foreach ($list['data']['users'] as $u) {
      if (strtolower((string)($u['email'] ?? '')) === $email) {
        $userId = (string)($u['id'] ?? '');
        break;
      }
    }
  }
}

if ($userId === '') {
  bad('Não consegui localizar o usuário no Auth/Profiles.');
}

// 3) Atualiza senha no Auth Admin
$upd = supabase_request('PUT', '/auth/v1/admin/users/' . rawurlencode($userId), [], [
  'password' => $newPass,
]);

if (empty($upd['ok'])) {
  $msg = $upd['error']['message'] ?? $upd['error']['msg'] ?? 'Falha ao alterar senha.';
  bad($msg, 500);
}

json_out(['ok' => true]);