<?php
require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/supabase.php';

function back(string $msg = '', bool $ok = false): void {
  $base = app_config('base_url', '');
  $q = $ok ? 'success=1' : http_build_query(['error' => $msg]);
  header("Location: {$base}/forgot.php?{$q}");
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') back('Método inválido.');

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) back('E-mail inválido.');

// Para funcionar bem, configure no Supabase Auth:
// - Site URL
// - Redirect URLs incluindo: {SEU_DOMINIO}/reset.php
$base = rtrim(app_config('base_url', ''), '/');
$redirectTo = $base . '/reset.php';

$res = supabase_request('POST', '/auth/v1/recover', [], [
  'email' => $email,
  'redirect_to' => $redirectTo,
]);

// Mesmo se falhar, por segurança não “vaza” se existe ou não.
// Mas se der erro bruto de config, você vai ver no log/retorno do supabase_request.
if (!$res['ok']) {
  // ainda assim devolve success para não enumerar contas
  back('', true);
}

back('', true);