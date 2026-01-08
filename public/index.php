<?php
$LOGO_EMPRESA = 'assets/img/logo_empresa.png';
$NOMBRE_EMPRESA = 'Hoshin Kanri';
$SUBTITULO_EMPRESA = 'Sistema de Gestión Estratégica';
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Hoshin Kanri | Sistema de Gestión Estratégica</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Login CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
	
	<link rel="icon" type="image/png" href="/hoshin_kanri/public/assets/img/logo_empresa.png">
</head>

<body>
    <!-- Contenedor para alertas flotantes -->
    <div class="alert-container" id="alertContainer"></div>

    <div class="login-container">
        <!-- HERO SECTION -->
        <div class="login-hero">
            <div class="hero-content">
                <h1>Hoshin Kanri</h1>
                <p>Sistema de gestión estratégica para alinear objetivos, estrategias y ejecución.</p>
            </div>
        </div>

        <!-- FORM SECTION -->
        <div class="login-form">
            <div class="logo-section">
                <?php if (file_exists($LOGO_EMPRESA)): ?>
                    <img src="<?= $LOGO_EMPRESA ?>"
                        alt="Logo <?= htmlspecialchars($NOMBRE_EMPRESA) ?>"
                        class="logo-img">
                <?php else: ?>
                    <div style="width: 100px; height: 100px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-compass fa-2x text-white"></i>
                    </div>
                <?php endif; ?>

                <h2 class="mb-1 fw-bold"><?= $NOMBRE_EMPRESA ?></h2>
                <p class="text-muted"><?= $SUBTITULO_EMPRESA ?></p>
            </div>

            <!-- Mensajes de error específicos por campo -->
            <div id="emailError" class="error-message d-none">
                <i class="fas fa-exclamation-circle"></i>
                <span id="emailErrorText"></span>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email"
                        id="correo"
                        class="form-control border-start-0"
                        placeholder="correo@progel.com.mx"
                        autocapitalize="none"
                        autocorrect="off"
                        spellcheck="false">
                </div>
            </div>

            <div id="passwordError" class="error-message d-none mb-2">
                <i class="fas fa-exclamation-circle"></i>
                <span id="passwordErrorText"></span>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password"
                        id="password"
                        class="form-control border-start-0"
                        placeholder="Contraseña"
                        autocapitalize="none"
                        autocorrect="off">
                    <button class="input-group-text bg-light border-start-0"
                        type="button"
                        id="togglePassword"
                        style="cursor: pointer;">
                        <i class="fas fa-eye text-muted"></i>
                    </button>
                </div>
            </div>

            <button id="btnLogin" class="btn-login mb-3">
                <div class="spinner"></div>
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar sesión
                </span>
            </button>

            <!-- Links adicionales para móviles -->
            <!-- <div class="d-block d-md-none text-center mt-3">
                <small class="text-muted">
                    <a href="#" class="text-decoration-none">¿Olvidaste tu contraseña?</a> • 
                    <a href="#" class="text-decoration-none">Soporte técnico</a>
                </small>
            </div> -->
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Login JS -->
    <script src="js/auth/login.js"></script>
</body>

</html>