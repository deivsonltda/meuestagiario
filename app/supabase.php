<?php
// app/supabase.php

/**
 * Request padrão para REST (service_role no backend).
 * - Usa SUPABASE_SERVICE_ROLE_KEY por padrão (igual você já está usando).
 */
function supabase_request(string $method, string $path, array $query = [], $body = null): array
{
  return supabase_request_with_bearer($method, $path, $query, $body, app_config('supabase_service_role_key'));
}

/**
 * Request com Bearer customizado (ex: access_token do usuário para reset de senha).
 * Se $bearer_key vier vazio, tenta usar service_role.
 */
function supabase_request_with_bearer(string $method, string $path, array $query = [], $body = null, ?string $bearer_key = null): array
{
  $base = rtrim(app_config('supabase_url'), '/');
  $url  = $base . $path;

  if (!empty($query)) {
    $qs  = http_build_query($query);
    $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
  }

  $service = app_config('supabase_service_role_key');
  $key = $bearer_key ?: $service;

  if (!$key) {
    return [
      'ok' => false,
      'http' => 0,
      'error' => ['message' => 'SUPABASE_SERVICE_ROLE_KEY não configurada'],
      'data' => null,
    ];
  }

  $headers = [
    'apikey: ' . $service,               // apikey pode ser service_role aqui (backend)
    'Authorization: Bearer ' . $key,     // bearer pode ser access_token (reset) ou service_role (padrão)
    'Accept: application/json',
  ];

  $payload = null;
  if ($body !== null) {
    $headers[] = 'Content-Type: application/json';
    $payload = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
  }

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => strtoupper($method),
    CURLOPT_HTTPHEADER     => $headers,
  ]);

  if ($payload !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  }

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false) {
    return [
      'ok' => false,
      'http' => 0,
      'error' => ['message' => $err ?: 'Erro curl'],
      'data' => null,
    ];
  }

  $data = null;
  if ($resp !== '') $data = json_decode($resp, true);

  if ($data === null && $resp !== '' && json_last_error() !== JSON_ERROR_NONE) {
    $data = ['raw' => $resp];
  }

  $ok = ($http >= 200 && $http < 300);

  return [
    'ok' => $ok,
    'http' => $http,
    'error' => $ok ? null : (is_array($data) ? $data : ['raw' => $resp]),
    'data' => $data,
  ];
}