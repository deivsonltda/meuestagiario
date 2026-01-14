<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
require_once __DIR__ . '/../../../app/auth.php';
require_once __DIR__ . '/../../../app/supabase.php';

require_auth();

$clientId     = env('GOOGLE_OAUTH_CLIENT_ID', '');
$clientSecret = env('GOOGLE_OAUTH_CLIENT_SECRET', '');
$redirect     = env('GOOGLE_OAUTH_REDIRECT_URI', '');

if ($clientId === '' || $clientSecret === '' || $redirect === '') {
  http_response_code(500);
  echo "Google OAuth não configurado (CLIENT_ID/SECRET/REDIRECT).";
  exit;
}

$state = $_GET['state'] ?? '';
if ($state === '' || empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
  http_response_code(400);
  echo "State inválido.";
  exit;
}

if (!empty($_GET['error'])) {
  $return = $_SESSION['google_oauth_return'] ?? (rtrim(app_config('base_url',''),'/') . '/app.php?page=account&tab=integracoes');
  header('Location: ' . $return);
  exit;
}

$code = $_GET['code'] ?? '';
if ($code === '') {
  http_response_code(400);
  echo "Code ausente.";
  exit;
}

$profileEmail = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
if ($profileEmail === '') {
  http_response_code(401);
  echo "Sessão inválida (sem email).";
  exit;
}

/** Troca code por tokens */
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
  CURLOPT_POSTFIELDS => http_build_query([
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirect,
    'grant_type' => 'authorization_code',
  ]),
]);

$raw = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($raw === false || $http < 200 || $http >= 300) {
  http_response_code(500);
  echo "Falha token HTTP={$http}. {$err}";
  exit;
}

$token = json_decode($raw, true);
$accessToken  = (string)($token['access_token'] ?? '');
$refreshToken = (string)($token['refresh_token'] ?? '');
$scope        = (string)($token['scope'] ?? '');
$tokenType    = (string)($token['token_type'] ?? '');
$expiresIn    = (int)($token['expires_in'] ?? 0);

if ($accessToken === '') {
  http_response_code(500);
  echo "access_token vazio.";
  exit;
}

$expiresAt = null;
if ($expiresIn > 0) {
  $expiresAt = gmdate('c', time() + $expiresIn);
}

/** Pega o email do Google (userinfo) */
$googleEmail = '';
$ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$uRaw = curl_exec($ch);
$uHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($uRaw !== false && $uHttp >= 200 && $uHttp < 300) {
  $u = json_decode($uRaw, true);
  $googleEmail = (string)($u['email'] ?? '');
}

/** UPSERT na SUA google_accounts (1 por profile_email) */
$now = gmdate('c');

supabase_request('POST', '/rest/v1/google_accounts', [
  'on_conflict' => 'profile_email',
  'return' => 'minimal',
], [[
  'profile_email' => $profileEmail,
  'google_email'  => $googleEmail,
  'refresh_token' => $refreshToken,
  'access_token'  => $accessToken,
  'token_type'    => $tokenType,
  'scope'         => $scope,
  'expires_at'    => $expiresAt,
  'provider'      => 'google',
  'calendar_id'   => null,
  'revoked_at'    => null,
  'updated_at'    => $now,
]]);

/** Toggle do seu profiles: google_calendar_enabled = true */
supabase_request('PATCH', '/rest/v1/profiles', [
  'email' => 'eq.' . $profileEmail,
], [
  'google_calendar_enabled' => true,
]);

$return = $_SESSION['google_oauth_return'] ?? (rtrim(app_config('base_url',''),'/') . '/app.php?page=account&tab=integracoes');
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_return']);

header('Location: ' . $return);
exit;