<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
require_once __DIR__ . '/../core/auth.php';
auth_require();

$user = auth_user();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <title>Hoshin Kanri</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
	<link rel="icon" type="image/png" href="/hoshin_kanri/public/assets/img/logo_empresa.png">
</head>

<body>

    <nav class="top-navbar">
        <div class="container-fluid h-100 px-3">
            <div class="d-flex justify-content-between align-items-center h-100">

                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle d-lg-none" id="mobileToggle">
                        <i class="fas fa-bars"></i>
                    </button>

                    <span class="navbar-brand">
                        <i class="fas fa-compass"></i> Hoshin Kanri
                        <input type="hidden" id="periodoId" value="1">
                    </span>
                </div>

                <div class="d-flex align-items-center gap-3 text-white">
                    <div class="text-end d-none d-md-block">
                        <div class="fw-semibold"><?= htmlspecialchars($user['nombre']) ?></div>
                        <div class="small opacity-75"><?= htmlspecialchars($user['rol']) ?></div>
                    </div>

                    <button id="btnLogout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>

            </div>
        </div>
    </nav>