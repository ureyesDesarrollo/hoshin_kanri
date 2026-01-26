<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema en Mantenimiento | Hoshin Kanri</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      color: #333;
    }

    .container {
      max-width: 500px;
      width: 100%;
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .header {
      background: linear-gradient(135deg, #006ec7 0%, #0084e9 100%);
      color: white;
      padding: 40px 30px;
      text-align: center;
    }

    .logo {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
      letter-spacing: -0.5px;
    }

    .status {
      display: inline-block;
      background: rgba(255, 255, 255, 0.2);
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 14px;
      margin-top: 5px;
      backdrop-filter: blur(10px);
    }

    .content {
      padding: 40px 30px;
    }

    .title {
      color: #2c3e50;
      font-size: 24px;
      margin-bottom: 15px;
      font-weight: 600;
      text-align: center;
    }

    .description {
      color: #7f8c8d;
      font-size: 16px;
      text-align: center;
      line-height: 1.6;
      margin-bottom: 30px;
    }

    .progress-container {
      margin: 30px 0;
    }

    .progress-bar {
      height: 8px;
      background: #ecf0f1;
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #006ec7, #0084e9);
      border-radius: 4px;
      width: 75%;
      animation: progressPulse 2s ease-in-out infinite;
    }

    @keyframes progressPulse {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.8;
      }
    }

    .countdown {
      text-align: center;
      margin: 30px 0;
    }

    .countdown-title {
      color: #7f8c8d;
      margin-bottom: 15px;
      font-size: 16px;
    }

    .countdown-display {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 10px;
    }

    .countdown-item {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 10px;
      min-width: 70px;
    }

    .countdown-value {
      font-size: 24px;
      font-weight: 700;
      color: #006ec7;
    }

    .countdown-label {
      font-size: 12px;
      color: #7f8c8d;
      margin-top: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 30px;
    }

    .btn {
      padding: 14px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      text-align: center;
      transition: all 0.2s ease;
      font-size: 15px;
      border: none;
      cursor: pointer;
    }

    .btn-primary {
      background: #006ec7;
      color: white;
    }

    .btn-primary:hover {
      background: #0056a3;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background: #f8f9fa;
      color: #333;
      border: 1px solid #e0e0e0;
    }

    .btn-secondary:hover {
      background: #e9ecef;
      transform: translateY(-1px);
    }

    .footer {
      background: #f8f9fa;
      padding: 20px 30px;
      text-align: center;
      border-top: 1px solid #e9ecef;
    }

    .contact {
      color: #7f8c8d;
      font-size: 14px;
      line-height: 1.5;
    }

    .contact a {
      color: #006ec7;
      text-decoration: none;
      font-weight: 500;
    }

    .contact a:hover {
      text-decoration: underline;
    }

    .refresh-notice {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      color: #27ae60;
      font-size: 14px;
      margin-top: 20px;
      padding: 12px;
      background: rgba(39, 174, 96, 0.1);
      border-radius: 8px;
    }

    @media (max-width: 480px) {
      .header {
        padding: 30px 20px;
      }

      .content {
        padding: 30px 20px;
      }

      .countdown-display {
        gap: 10px;
      }

      .countdown-item {
        min-width: 60px;
        padding: 12px;
      }

      .countdown-value {
        font-size: 20px;
      }
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="container">
    <!-- Encabezado -->
    <div class="header">
      <div class="logo">Hoshin Kanri</div>
      <h1>Sistema en Mantenimiento</h1>
      <div class="status">
        <i class="fas fa-tools"></i> Actualización en curso
      </div>
    </div>

    <!-- Contenido -->
    <div class="content">
      <h2 class="title">Estamos mejorando el sistema</h2>
      <p class="description">
        Estamos realizando tareas de mantenimiento programado para optimizar el rendimiento y agregar nuevas funcionalidades.
      </p>

      <!-- Barra de progreso -->
      <div class="progress-container">
        <div class="progress-bar">
          <div class="progress-fill"></div>
        </div>
      </div>

      <!-- Contador -->
      <div class="countdown">
        <div class="countdown-title">Tiempo estimado:</div>
        <div class="countdown-display">
          <div class="countdown-item">
            <div class="countdown-value" id="hours">02</div>
            <div class="countdown-label">Horas</div>
          </div>
          <div class="countdown-item">
            <div class="countdown-value" id="minutes">30</div>
            <div class="countdown-label">Minutos</div>
          </div>
          <div class="countdown-item">
            <div class="countdown-value" id="seconds">00</div>
            <div class="countdown-label">Segundos</div>
          </div>
        </div>
      </div>

      <!-- Notificación -->
      <div class="refresh-notice">
        <i class="fas fa-sync-alt fa-spin"></i>
        <span>Actualización automática cuando esté disponible</span>
      </div>

      <!-- Botones -->
      <div class="actions">
        <button class="btn btn-primary" onclick="checkStatus()">
          <i class="fas fa-sync"></i> Verificar estado
        </button>
        <a href="mailto:soporte@hoshinkanri.com" class="btn btn-secondary">
          <i class="fas fa-envelope"></i> Contactar soporte
        </a>
      </div>
    </div>

    <!-- Pie de página -->
    <div class="footer">
      <div class="contact">
        <p><strong>Soporte:</strong>
          <a href="mailto:soporte@hoshinkanri.com">soporte@hoshinkanri.com</a>
        </p>
        <p style="margin-top: 8px; font-size: 13px; color: #95a5a6;">
          © 2024 Hoshin Kanri System
        </p>
      </div>
    </div>
  </div>

  <script>
    // Contador regresivo
    function startCountdown() {
      const targetTime = new Date();
      targetTime.setHours(targetTime.getHours() + 2);
      targetTime.setMinutes(targetTime.getMinutes() + 30);

      function update() {
        const now = new Date().getTime();
        const distance = targetTime - now;

        if (distance < 0) {
          // Tiempo completado
          document.getElementById('hours').textContent = '00';
          document.getElementById('minutes').textContent = '00';
          document.getElementById('seconds').textContent = '00';
          checkStatus();
          return;
        }

        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
      }

      update();
      setInterval(update, 1000);
    }

    // Verificar estado del sistema
    function checkStatus() {
      const btn = document.querySelector('.btn-primary');
      const originalHTML = btn.innerHTML;

      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
      btn.disabled = true;

      // Simular verificación del servidor
      setTimeout(() => {
        // En un caso real, aquí harías una petición al servidor
        const isReady = Math.random() > 0.5; // 50% de probabilidad

        if (isReady) {
          showMessage('¡Sistema disponible! Redirigiendo...', 'success');
          setTimeout(() => {
            window.location.href = '/';
          }, 1500);
        } else {
          showMessage('El sistema sigue en mantenimiento.', 'info');
          btn.innerHTML = originalHTML;
          btn.disabled = false;
        }
      }, 1500);
    }

    // Mostrar mensaje
    function showMessage(text, type) {
      // Eliminar mensaje anterior si existe
      const existingMsg = document.querySelector('.message');
      if (existingMsg) existingMsg.remove();

      const colors = {
        success: '#27ae60',
        error: '#e74c3c',
        info: '#3498db'
      };

      const message = document.createElement('div');
      message.className = 'message';
      message.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                left: 20px;
                background: white;
                color: ${colors[type]};
                padding: 15px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                border-left: 4px solid ${colors[type]};
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
                text-align: center;
                font-weight: 500;
            `;

      message.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                ${text}
            `;

      document.body.appendChild(message);

      setTimeout(() => {
        message.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => message.remove(), 300);
      }, 3000);
    }

    // Agregar estilos de animación
    const style = document.createElement('style');
    style.textContent = `
            @keyframes slideIn {
                from { transform: translateY(-20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateY(0); opacity: 1; }
                to { transform: translateY(-20px); opacity: 0; }
            }
        `;
    document.head.appendChild(style);

    // Iniciar cuando la página cargue
    document.addEventListener('DOMContentLoaded', startCountdown);
  </script>
</body>

</html>
