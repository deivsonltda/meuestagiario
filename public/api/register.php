<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/supabase.php';

function back_to_register(string $msg): void {
  $base = app_config('base_url', '');
  $q = http_build_query(['error' => $msg]);
  header("Location: {$base}/register.php?{$q}");
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  back_to_register('Método inválido.');
}

$full_name = trim((string)($_POST['full_name'] ?? ''));
$email     = strtolower(trim((string)($_POST['email'] ?? '')));
$phoneRaw  = trim((string)($_POST['phone'] ?? ''));
$password  = (string)($_POST['password'] ?? '');

// tracking
$lid = trim((string)($_POST['lid'] ?? ''));
$ref = trim((string)($_POST['ref'] ?? ''));
$utm_source   = trim((string)($_POST['utm_source'] ?? ''));
$utm_medium   = trim((string)($_POST['utm_medium'] ?? ''));
$utm_campaign = trim((string)($_POST['utm_campaign'] ?? ''));
$utm_content  = trim((string)($_POST['utm_content'] ?? ''));
$utm_term     = trim((string)($_POST['utm_term'] ?? ''));

// validações básicas
if ($full_name === '' || mb_strlen($full_name) < 6) back_to_register('Informe seu nome completo.');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) back_to_register('E-mail inválido.');

$phone = preg_replace('/\D+/', '', $phoneRaw); // só dígitos
if ($phone === '' || strlen($phone) < 10 || strlen($phone) > 13) {
  back_to_register('Telefone inválido. Use DDD + número.');
}

if (strlen($password) < 8) back_to_register('A senha deve ter no mínimo 8 caracteres.');

// antifraude simples (rate limit por IP: 10 tentativas/30min)
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlKey = 'reg_rl_' . md5($ip);
if (!isset($_SESSION[$rlKey])) $_SESSION[$rlKey] = [];
$_SESSION[$rlKey] = array_values(array_filter($_SESSION[$rlKey], fn($t) => $t > (time() - 1800)));
if (count($_SESSION[$rlKey]) >= 10) back_to_register('Muitas tentativas. Tente novamente em alguns minutos.');
$_SESSION[$rlKey][] = time();

// 1) checar duplicidade em profiles (email/phone)
$profilesPath = '/rest/v1/profiles';

// checa email
$checkEmail = supabase_request('GET', $profilesPath, [
  'select' => 'id',
  'email'  => 'eq.' . $email,
  'limit'  => 1,
]);
if ($checkEmail['ok'] && !empty($checkEmail['data'][0]['id'])) {
  back_to_register('Este e-mail já está cadastrado.');
}

// checa phone
$checkPhone = supabase_request('GET', $profilesPath, [
  'select' => 'id',
  'phone'  => 'eq.' . $phone,
  'limit'  => 1,
]);
if ($checkPhone['ok'] && !empty($checkPhone['data'][0]['id'])) {
  back_to_register('Este telefone já está cadastrado.');
}

// 2) cria no Supabase Auth
// Obs: usando service_role no backend, via supabase_request()
$signup = supabase_request('POST', '/auth/v1/signup', [], [
  'email' => $email,
  'password' => $password,
]);

if (!$signup['ok']) {
  // supabase costuma responder com "User already registered" etc.
  $msg = $signup['error']['message'] ?? $signup['error']['msg'] ?? 'Falha ao criar usuário.';
  back_to_register($msg);
}

$userId = $signup['data']['user']['id'] ?? null;
if (!$userId) {
  back_to_register('Falha ao obter ID do usuário.');
}

// 3) cria perfil (com tracking)
$insertProfile = supabase_request('POST', $profilesPath, [], [[
  'id' => $userId,
  'email' => $email,
  'phone' => $phone,
  'full_name' => $full_name,
  'waha_lid' => ($lid !== '' ? $lid : null),
  'ref' => ($ref !== '' ? $ref : null),
  'utm_source' => ($utm_source !== '' ? $utm_source : null),
  'utm_medium' => ($utm_medium !== '' ? $utm_medium : null),
  'utm_campaign' => ($utm_campaign !== '' ? $utm_campaign : null),
  'utm_content' => ($utm_content !== '' ? $utm_content : null),
  'utm_term' => ($utm_term !== '' ? $utm_term : null),
]]);

if (!$insertProfile['ok']) {
  // Se falhar aqui, você fica com user no Auth sem perfil.
  // (Depois a gente cria um job/repair, mas por ora devolve erro.)
  back_to_register('Usuário criado, mas falhou ao salvar perfil. Fale com o suporte.');
}

// sucesso: marca cookie servidor-side também (pra bot identificar)
setcookie('sb_registered', '1', [
  'expires' => time() + (60 * 60 * 24 * 30),
  'path' => '/',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'httponly' => false,
  'samesite' => 'Lax',
]);

$base = app_config('base_url', '');
header("Location: {$base}/register.php?success=1");
exit;