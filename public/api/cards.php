<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/supabase.php';

require_auth();

header('Content-Type: application/json; charset=utf-8');

$user_key = current_user_key();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---------------------------------------------------------
// Helpers
// ---------------------------------------------------------
function bad($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

function ok($data = []) {
  echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}

function num($v) {
  // aceita "45.000,50" / "45000.50" / "45000"
  $s = preg_replace('/[^\d,.-]/', '', (string)$v);
  if (substr_count($s, ',') === 1 && substr_count($s, '.') >= 1) {
    $s = str_replace('.', '', $s);     // tira milhar
    $s = str_replace(',', '.', $s);    // vírgula vira decimal
  } elseif (substr_count($s, ',') === 1 && substr_count($s, '.') === 0) {
    $s = str_replace(',', '.', $s);
  }
  return is_numeric($s) ? (float)$s : 0.0;
}

// ---------------------------------------------------------
// Supabase table
// ---------------------------------------------------------
// Tabela: "cards"
// colunas: id (uuid pk), user_key (text), name (text), limit (numeric), closing_day (int), is_default (bool), created_at
$table_path = '/rest/v1/cards';

// ---------------------------------------------------------
// GET: list
// ---------------------------------------------------------
if ($method === 'GET') {
  $q = [
    'select'   => 'id,name,limit,closing_day,is_default,created_at',
    'user_key' => 'eq.' . $user_key,
    'order'    => 'created_at.desc',
  ];

  $res = supabase_request('GET', $table_path, $q);
  if (!$res['ok']) bad($res['error'] ?? 'Falha ao listar cartões', 500);

  ok(['items' => $res['data'] ?? []]);
}

// ---------------------------------------------------------
// Read body
// ---------------------------------------------------------
$raw = file_get_contents('php://input');
$body = json_decode($raw ?: '[]', true);
if (!is_array($body)) $body = [];

$action = $body['action'] ?? '';

// ---------------------------------------------------------
// POST: upsert
// ---------------------------------------------------------
if ($method === 'POST' && $action === 'upsert') {
  $id          = trim((string)($body['id'] ?? ''));
  $name        = trim((string)($body['name'] ?? ''));
  $limit       = num($body['limit'] ?? 0);
  $closing_day = (int)($body['closing_day'] ?? 0);
  $is_default  = (bool)($body['is_default'] ?? false);

  if ($name === '') bad('Nome do cartão é obrigatório.');
  if ($closing_day < 1 || $closing_day > 28) bad('Dia de fechamento deve ser entre 1 e 28.');

  // Se marcar padrão: desmarca os outros antes (do mesmo usuário)
  if ($is_default) {
    // Se estiver editando, pode desmarcar todos mesmo; depois este fica true no update/insert
    $unsetRes = supabase_request(
      'PATCH',
      $table_path,
      ['user_key' => 'eq.' . $user_key],
      ['is_default' => false]
    );
    // não bloqueia se falhar? aqui vamos bloquear pra manter consistência
    if (!$unsetRes['ok']) bad($unsetRes['error'] ?? 'Falha ao atualizar cartão padrão', 500);
  }

  // payload base
  $payload = [
    'name'        => $name,
    'limit'       => $limit,
    'closing_day' => $closing_day,
    'is_default'  => $is_default,
  ];

  // ✅ EDITAR: quando tem ID, faz UPDATE (PATCH) ao invés de INSERT (POST)
  if ($id !== '') {
    $q = [
      'id'       => 'eq.' . $id,
      'user_key' => 'eq.' . $user_key,
    ];

    $res = supabase_request('PATCH', $table_path, $q, $payload);
    if (!$res['ok']) bad($res['error'] ?? 'Falha ao atualizar cartão', 500);

    ok();
  }

  // ✅ NOVO: quando NÃO tem ID, faz INSERT (POST)
  $payloadInsert = $payload + [
    'user_key' => $user_key,
  ];

  $res = supabase_request(
    'POST',
    $table_path,
    [],
    [$payloadInsert]
  );

  if (!$res['ok']) bad($res['error'] ?? 'Falha ao salvar cartão', 500);

  ok();
}

// ---------------------------------------------------------
// POST: delete
// ---------------------------------------------------------
if ($method === 'POST' && $action === 'delete') {
  $id = trim((string)($body['id'] ?? ''));
  if ($id === '') bad('ID inválido.');

  $q = [
    'id'       => 'eq.' . $id,
    'user_key' => 'eq.' . $user_key,
  ];

  $res = supabase_request('DELETE', $table_path, $q);
  if (!$res['ok']) bad($res['error'] ?? 'Falha ao excluir cartão', 500);

  ok();
}

bad('Ação inválida.');