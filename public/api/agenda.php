<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/supabase.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

$user_key = current_user_key();
$month = trim((string)($_GET['month'] ?? '')); // formato: YYYY-MM

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'month inválido. Use YYYY-MM'], JSON_UNESCAPED_UNICODE);
  exit;
}

$start = $month . '-01T00:00:00Z';
$dt = new DateTime($start);
$dt->modify('first day of next month');
$end = $dt->format('Y-m-d\T00:00:00\Z');

/**
 * TABELA esperada no Supabase:
 * public.agenda_events
 * - id uuid
 * - user_key text
 * - title text
 * - start_at timestamptz
 * - end_at timestamptz
 * - reminder_minutes int (ex: 30)
 */
$table = '/rest/v1/agenda_events';

$q = [
  'select' => 'id,title,start_at,end_at,reminder_minutes',
  'user_key' => 'eq.' . $user_key,
  'start_at' => 'gte.' . $start,
  'start_at' => 'gte.' . $start,
  'start_at' => 'gte.' . $start,
];

// Como PHP não permite 3 chaves iguais no array, usamos range com and:
$q = [
  'select' => 'id,title,start_at,end_at,reminder_minutes',
  'user_key' => 'eq.' . $user_key,
  'and' => '(start_at.gte.' . $start . ',start_at.lt.' . $end . ')',
  'order' => 'start_at.asc'
];

$res = supabase_request('GET', $table, $q);

if (!$res['ok']) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Falha ao buscar eventos','details'=>$res['error']], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['ok'=>true,'items'=>$res['data'] ?? []], JSON_UNESCAPED_UNICODE);