$(document).ready(function () {
    // Función para mostrar alertas flotantes
    function showAlert(type, title, message) {
        const alertId = 'alert-' + Date.now();
        let alertClass = '';

        switch (type) {
            case 'email':
                alertClass = 'alert-email';
                break;
            case 'server':
                alertClass = 'alert-server';
                break;
            default:
                alertClass = '';
        }

        const alertHTML = `
                    <div class="custom-alert ${alertClass}" id="${alertId}">
                        <div class="alert-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <div class="alert-title">${title}</div>
                            <div class="alert-message">${message}</div>
                        </div>
                        <button class="alert-close" onclick="closeAlert('${alertId}')">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="alert-progress"></div>
                    </div>
                `;

        $('#alertContainer').prepend(alertHTML);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            closeAlert(alertId);
        }, 5000);
    }

    // Función global para cerrar alertas
    window.closeAlert = function (alertId) {
        const alertElement = $('#' + alertId);
        alertElement.css('animation', 'slideOutRight 0.4s ease-out forwards');
        setTimeout(() => {
            alertElement.remove();
        }, 400);
    };

    // Mostrar error en campo específico
    function showFieldError(fieldId, errorId, message) {
        const field = $('#' + fieldId);
        const errorElement = $('#' + errorId);

        // Agregar clase de error al campo
        field.addClass('input-error');

        // Mostrar mensaje de error
        errorElement.find('span').text(message);
        errorElement.removeClass('d-none');

        // Auto-remover error después de 3 segundos
        setTimeout(() => {
            field.removeClass('input-error');
            errorElement.addClass('d-none');
        }, 3000);
    }

    // Toggle para mostrar/ocultar contraseña
    $('#togglePassword').on('click', function () {
        const passwordInput = $('#password');
        const icon = $(this).find('i');

        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }

        // Enfocar el campo de contraseña después de toggle
        passwordInput.focus();
    });

    // Login function
    $('#btnLogin').on('click', function () {
        // Limpiar errores previos
        $('.input-error').removeClass('input-error');
        $('.error-message').addClass('d-none');

        const btn = $(this);
        btn.addClass('loading');

        // Validación básica
        const correo = $('#correo').val().trim();
        const password = $('#password').val();

        let hasError = false;

        if (!correo) {
            showFieldError('correo', 'emailError', 'El correo electrónico es requerido');
            showAlert('email', 'Correo requerido', 'Por favor, ingresa tu correo electrónico');
            hasError = true;
        } else if (!correo.includes('@') || !correo.includes('.')) {
            showFieldError('correo', 'emailError', 'Ingresa un correo electrónico válido');
            showAlert('email', 'Correo inválido', 'El formato del correo electrónico no es válido');
            hasError = true;
        }

        if (!password) {
            showFieldError('password', 'passwordError', 'La contraseña es requerida');
            showAlert('default', 'Contraseña requerida', 'Por favor, ingresa tu contraseña');
            hasError = true;
        }

        if (hasError) {
            btn.removeClass('loading');
            return;
        }

        $.ajax({
            type: 'POST',
            url: '../app/auth/login_post.php',
            data: {
                correo: correo,
                password: password
            },
            success: function (resp) {
                if (resp.success) {
                    window.location.href = resp.rol === 'licenciado' ? 'proyectos_lic.php' : 'dashboard.php';
                } else {
                    showAlert('server', 'Acceso denegado', resp.error || 'Credenciales incorrectas');
                    btn.removeClass('loading');
                }
            },
            error: function () {
                showAlert('server', 'Error', 'Error de comunicación con el servidor');
                btn.removeClass('loading');
            }

        });
    });

    // Limpiar errores al empezar a escribir
    $('#correo, #password').on('input', function () {
        $(this).removeClass('input-error');
        const fieldId = $(this).attr('id');
        if (fieldId === 'correo') {
            $('#emailError').addClass('d-none');
        } else if (fieldId === 'password') {
            $('#passwordError').addClass('d-none');
        }
    });

    // Permitir login con Enter
    $('#correo, #password').on('keypress', function (e) {
        if (e.which === 13) {
            $('#btnLogin').click();
            e.preventDefault();
        }
    });

    // Auto-focus en el primer campo al cargar la página en móviles
    if (window.innerWidth <= 768) {
        $('#correo').focus();
    }

    // Prevenir zoom en iOS al hacer focus
    if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
        $('input, select, textarea').on('focus', function () {
            window.scrollTo(0, 0);
            document.body.scrollTop = 0;
        });
    }

    // Mejorar la experiencia táctil en móviles
    $('.btn-login, .form-control').on('touchstart', function () {
        $(this).addClass('active');
    }).on('touchend', function () {
        $(this).removeClass('active');
    });
});