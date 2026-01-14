<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/supabase.php';

require_auth();

$profileEmail = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
if ($profileEmail === '') json_out(['ok'=>false,'error'=>'Sessão inválida'], 401);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $res = supabase_request('GET', '/rest/v1/google_accounts', [
    'select' => 'id,google_email,provider,calendar_id,expires_at,created_at,updated_at,revoked_at',
    'profile_email' => 'eq.' . $profileEmail,
    'revoked_at' => 'is.null',
    'limit' => '1',
  ]);

  if (empty($res['ok'])) json_out(['ok'=>false,'error'=>'Falha ao buscar conta'], 500);

  $row = $res['data'][0] ?? null;
  json_out(['ok'=>true,'account'=>$row]);
}

if ($method === 'DELETE') {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '{}', true);
  $id = (string)($data['id'] ?? '');
  if ($id === '') json_out(['ok'=>false,'error'=>'ID obrigatório'], 400);

  // remove a conta (revoga localmente)
  $patch = supabase_request('PATCH', '/rest/v1/google_accounts', [
    'id' => 'eq.' . $id,
    'profile_email' => 'eq.' . $profileEmail,
  ], [
    'revoked_at' => gmdate('c'),
    'updated_at' => gmdate('c'),
    'access_token' => null,
    'refresh_token' => null,
    'expires_at' => null,
    'calendar_id' => null,
  ]);

  if (empty($patch['ok'])) json_out(['ok'=>false,'error'=>'Falha ao remover'], 500);

  // IMPORTANTE: você pediu que o toggle continue ATIVO.
  // Então NÃO mexemos em profiles.google_calendar_enabled aqui.

  json_out(['ok'=>true]);
}

json_out(['ok'=>false,'error'=>'Método não suportado'], 405);