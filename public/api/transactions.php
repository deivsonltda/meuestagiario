<?php
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/supabase.php';

require_auth();
$userKey = current_user_key();
$action = $_GET['action'] ?? '';

if ($action === 'list') {
  $res = supabase_request('GET', '/rest/v1/transactions', [
    'select' => 'id,item,amount,type,status,date,category_id',
    'user_key' => 'eq.' . $userKey,
    'order' => 'date.desc'
  ]);

  if (!$res['ok']) json_out(['ok'=>false,'error'=>$res['error']], 500);
  json_out(['ok'=>true,'items'=>$res['data'] ?? []]);
}

if ($action === 'upsert') {
  $raw = json_decode(file_get_contents('php://input'), true) ?? [];

  $id = trim((string)($raw['id'] ?? ''));
  $item = trim((string)($raw['item'] ?? ''));
  $amount = (float)($raw['amount'] ?? 0);
  $type = ($raw['type'] ?? '');
  $status = ($raw['status'] ?? '');
  $date = trim((string)($raw['date'] ?? ''));

  $category_id = trim((string)($raw['category_id'] ?? ''));

  if ($item === '') json_out(['ok'=>false,'error'=>'Item obrigatório.'], 400);
  if ($amount <= 0) json_out(['ok'=>false,'error'=>'Valor inválido.'], 400);
  if (!in_array($type, ['income','expense'], true)) json_out(['ok'=>false,'error'=>'Tipo inválido.'], 400);
  if (!in_array($status, ['paid','due'], true)) json_out(['ok'=>false,'error'=>'Status inválido.'], 400);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_out(['ok'=>false,'error'=>'Data inválida (use YYYY-MM-DD).'], 400);

  // category_id pode ser null
  if ($category_id === '') $category_id = null;

  $payload = [
    'user_key' => $userKey,
    'item' => $item,
    'amount' => $amount,
    'type' => $type,
    'status' => $status,
    'date' => $date,
    'category_id' => $category_id,
  ];
  if ($id !== '') $payload['id'] = $id;

  $res = supabase_request('POST', '/rest/v1/transactions?on_conflict=id', [], $payload);

  if (!$res['ok']) json_out(['ok'=>false,'error'=>$res['error']], 500);
  json_out(['ok'=>true,'item'=>($res['data'][0] ?? null)]);
}

if ($action === 'delete') {
  $raw = json_decode(file_get_contents('php://input'), true) ?? [];
  $id = trim((string)($raw['id'] ?? ''));

  if ($id === '') json_out(['ok'=>false,'error'=>'ID obrigatório.'], 400);

  $res = supabase_request('DELETE', '/rest/v1/transactions', [
    'id' => 'eq.' . $id,
    'user_key' => 'eq.' . $userKey,
  ]);

  if (!$res['ok']) json_out(['ok'=>false,'error'=>$res['error']], 500);
  json_out(['ok'=>true]);
}

json_out(['ok'=>false,'error'=>'Ação inválida.'], 400);