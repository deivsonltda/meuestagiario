<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/supabase.php';

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '[]', true);
if (!is_array($body)) $body = [];

$token = trim((string)($body['access_token'] ?? ''));
$pass  = (string)($body['password'] ?? '');

if ($token === '' || strlen($pass) < 8) {
  json_out(['ok' => false, 'error' => 'Dados inválidos.'], 400);
}

// Atualiza senha usando o access_token do usuário (Bearer dinâmico)
$res = supabase_request_with_bearer('PUT', '/auth/v1/user', [], [
  'password' => $pass
], $token);

if (!$res['ok']) {
  $msg = $res['error']['message'] ?? $res['error']['error_description'] ?? 'Falha ao redefinir senha.';
  json_out(['ok' => false, 'error' => $msg], 400);
}

json_out(['ok' => true]);