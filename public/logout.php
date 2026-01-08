<?php
require_once __DIR__ . '/../app/core/auth.php';

auth_logout();

header('Location: index.php');
exit;
