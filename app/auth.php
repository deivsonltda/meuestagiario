<?php
// app/auth.php
require_once __DIR__ . '/bootstrap.php';

function is_logged_in(): bool {
  return !empty($_SESSION['user']) && !empty($_SESSION['user']['email']) && !empty($_SESSION['user']['id']);
}

/**
 * Continua usando email como user_key (pra não quebrar suas tabelas).
 * Você já escopa por email hoje. :contentReference[oaicite:4]{index=4}
 */
function current_user_key(): string {
  $email = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
  return $email;
}

function require_auth(): void {
  if (is_logged_in()) return;

  if (function_exists('is_api_request') && is_api_request()) {
    json_out(['ok' => false, 'error' => 'unauthorized'], 401);
  }

  redirect('/index.php');
}

function login_user(array $user): void {
  $_SESSION['user'] = [
    'id'    => (string)($user['id'] ?? ''),
    'email' => strtolower(trim((string)($user['email'] ?? ''))),
    'name'  => (string)($user['name'] ?? ''),
  ];

  // opcional: manter access_token na sessão (útil pra recursos futuros)
  if (!empty($user['access_token'])) {
    $_SESSION['auth'] = [
      'access_token' => (string)$user['access_token'],
      'refresh_token' => (string)($user['refresh_token'] ?? ''),
      'expires_at' => (int)($user['expires_at'] ?? 0),
    ];
  }
}

function logout_user(): void {
  $_SESSION = [];

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"], $params["secure"], $params["httponly"]
    );
  }

  session_destroy();
}