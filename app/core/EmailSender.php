<?php
require __DIR__ . '/lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/lib/PHPMailer/src/SMTP.php';
require __DIR__ . '/lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailSender
{
  private $mail;

  public function __construct()
  {
    // Crear instancia de PHPMailer
    $this->mail = new PHPMailer(true);

    // Configuración SMTP
    $this->mail->isSMTP();
    $this->mail->Host = 'smtp.office365.com'; // Servidor SMTP
    $this->mail->SMTPAuth = true;
    $this->mail->Username = 'sistemapreparacion@progel.com.mx'; // Usuario SMTP
    $this->mail->Password = 'Progel#2026'; // Contraseña SMTP
    $this->mail->SMTPSecure = 'tls'; // Encriptación
    $this->mail->Port = 587; // Puerto SMTP
    $this->mail->CharSet = 'UTF-8'; // Codificación de caracteres


    // Configurar remitente predeterminado
    $this->mail->setFrom('sistemapreparacion@progel.com.mx', 'Sistemas');
  }

  // Método para enviar correos
  public function sendMail($asunto, $body, $destinatarios = [])
  {
    try {

      // 🔑 LIMPIAR ESTADO PREVIO
      $this->mail->clearAddresses();
      $this->mail->clearAttachments();

      // 🔑 CONFIGURACIÓN CLAVE
      $this->mail->CharSet  = 'UTF-8';
      $this->mail->Encoding = 'base64';
      $this->mail->isHTML(true);
      $this->mail->ContentType = 'text/html';

      // Destinatarios
      if (!empty($destinatarios)) {
        foreach ($destinatarios as $email) {
          $this->mail->addAddress($email);
        }
      } else {
        $this->mail->addAddress('desarrollo@progel.com.mx');
      }

      // Asunto y cuerpo
      $this->mail->Subject = $asunto;
      $this->mail->Body    = $body;

      // 🔑 ALT BODY SOLO TEXTO
      $this->mail->AltBody = strip_tags($body);

      // Enviar
      $this->mail->send();
      return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
      return false;
    }
  }
}
