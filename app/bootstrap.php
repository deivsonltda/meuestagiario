<?php
// app/bootstrap.php

// ------------------------------------------------------------
// 1) Carrega .env da RAIZ do projeto (../.env)
//    - Suporta linhas: KEY=VALUE
//    - Ignora comentários (# ...)
//    - Remove aspas "..." ou '...'
//    - NÃO sobrescreve variáveis já definidas no ambiente
// ------------------------------------------------------------
(function () {
  $root = realpath(__DIR__ . '/..'); // raiz do projeto (contém /app e /public)
  if (!$root) return;

  $envPath = $root . DIRECTORY_SEPARATOR . '.env';
  if (!is_file($envPath)) return;

  $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!$lines) return;

  foreach ($lines as $line) {
    $line = trim($line);

    // ignora vazias e comentários
    if ($line === '' || $line[0] === '#') continue;

    // remove comentários no fim da linha (preserva # dentro de aspas)
    $clean = '';
    $inQuotes = false;
    $len = strlen($line);

    for ($i = 0; $i < $len; $i++) {
      $ch = $line[$i];
      if ($ch === '"' || $ch === "'") $inQuotes = !$inQuotes;
      if (!$inQuotes && $ch === '#') break;
      $clean .= $ch;
    }

    $line = trim($clean);
    if ($line === '' || !str_contains($line, '=')) continue;

    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);

    if ($k === '') continue;

    // remove aspas
    if ($v !== '') {
      $first = $v[0];
      $last  = $v[strlen($v) - 1];
      if (
        ($first === '"' && $last === '"') ||
        ($first === "'" && $last === "'")
      ) {
        $v = substr($v, 1, -1);
      }
    }

    // NÃO sobrescreve se já existir
    if (getenv($k) !== false) continue;

    putenv("$k=$v");
    $_ENV[$k] = $v;
  }
})();

// ------------------------------------------------------------
// Helper env()
// ------------------------------------------------------------
function env(string $key, $default = null) {
  $v = getenv($key);
  if ($v === false || $v === null || $v === '') return $default;

  $vl = strtolower(trim((string)$v));
  if ($vl === 'true')  return true;
  if ($vl === 'false') return false;
  if ($vl === 'null')  return null;

  return $v;
}

// ------------------------------------------------------------
// 2) Carrega config
// ------------------------------------------------------------
$config = require __DIR__ . '/config.php';

// ------------------------------------------------------------
// 3) Sessão (evita warning se já estiver iniciada)
// ------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
  if (!empty($config['session_name'])) {
    session_name($config['session_name']);
  }
  session_start();
}

// ------------------------------------------------------------
// 4) Helpers globais
// ------------------------------------------------------------
function app_config(string $key, $default = null) {
  static $cfg = null;
  if ($cfg === null) {
    $cfg = require __DIR__ . '/config.php';
  }
  return $cfg[$key] ?? $default;
}

function is_api_request(): bool {
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  return strpos($uri, '/api/') !== false;
}

function redirect(string $path): void {
  $base = rtrim(app_config('base_url', ''), '/');
  header('Location: ' . $base . $path);
  exit;
}

function h($str): string {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Saída JSON padronizada
 */
function json_out($data, int $status = 200): void {
  if (!headers_sent()) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  }
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

// ------------------------------------------------------------
// 5) DEBUG DEFINITIVO PARA /api
//    - Fatal error vira JSON (nunca tela branca)
// ------------------------------------------------------------
if (is_api_request()) {
  ini_set('display_errors', '0');
  error_reporting(E_ALL);

  register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e) return;

    $fatalTypes = [
      E_ERROR,
      E_PARSE,
      E_CORE_ERROR,
      E_COMPILE_ERROR,
    ];

    if (!in_array($e['type'], $fatalTypes, true)) return;

    if (!headers_sent()) {
      http_response_code(500);
      header('Content-Type: application/json; charset=utf-8');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }

    echo json_encode([
      'ok' => false,
      'error' => 'fatal',
      'details' => [
        'type' => $e['type'],
        'message' => $e['message'],
        'file' => $e['file'],
        'line' => $e['line'],
      ],
    ], JSON_UNESCAPED_UNICODE);
  });
}