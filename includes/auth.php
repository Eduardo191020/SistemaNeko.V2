<?php
// src/includes/auth.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function login(int $userId): void {
  $_SESSION['uid'] = $userId;
}

function logout(): void {
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
  }
}

function require_auth(): void {
  if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
  }
}

function current_user_id(): ?int {
  return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
}
