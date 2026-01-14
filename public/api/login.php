<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/supabase.php';

function back(string $msg): void {
  $base = app_config('base_url', '');
  $q = http_build_query(['error' => $msg]);
  header("Location: {$base}/index.php?{$q}");
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') back('Método inválido.');

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$pass  = (string)($_POST['password'] ?? '');

if ($email === '' || $pass === '') back('Preencha email e senha.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('E-mail inválido.');

// Supabase Auth: token password grant
$res = supabase_request('POST', '/auth/v1/token', ['grant_type' => 'password'], [
  'email' => $email,
  'password' => $pass,
]);

if (!$res['ok']) {
  $msg = $res['error']['error_description'] ?? $res['error']['message'] ?? 'Falha no login.';
  back($msg);
}

$data = $res['data'] ?? [];
$access = $data['access_token'] ?? '';
$refresh = $data['refresh_token'] ?? '';
$expires_in = (int)($data['expires_in'] ?? 0);
$user = $data['user'] ?? [];

$userId = $user['id'] ?? '';
$userEmail = $user['email'] ?? $email;

if (!$userId) back('Falha ao obter usuário.');

// pega nome no profiles (se existir)
$name = '';
$p = supabase_request('GET', '/rest/v1/profiles', [
  'select' => 'full_name',
  'id' => 'eq.' . $userId,
  'limit' => 1,
]);
if ($p['ok'] && !empty($p['data'][0]['full_name'])) {
  $name = (string)$p['data'][0]['full_name'];
}
if ($name === '') $name = $userEmail;

login_user([
  'id' => $userId,
  'email' => $userEmail,
  'name' => $name,
  'access_token' => $access,
  'refresh_token' => $refresh,
  'expires_at' => time() + $expires_in,
]);

redirect('/app.php?page=dashboard');