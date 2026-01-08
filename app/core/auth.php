<?php
function auth_start()
{
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

function auth_user()
{
  auth_start();
  return $_SESSION['usuario'] ?? null;
}

function auth_login($usuario)
{
  auth_start();
  $_SESSION['usuario'] = $usuario;
}

function auth_require()
{
  if (!auth_user()) {
    header('Location: /hoshin_kanri/public/index.php');
    exit;
  }
}

function auth_logout()
{
  auth_start();
  session_destroy();
}
