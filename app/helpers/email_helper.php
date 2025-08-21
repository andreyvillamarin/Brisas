<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once APP_ROOT . '/app/libs/PHPMailer/src/Exception.php';
require_once APP_ROOT . '/app/libs/PHPMailer/src/PHPMailer.php';
require_once APP_ROOT . '/app/libs/PHPMailer/src/SMTP.php';

function send_order_emails($orderId, $orderData, $settings) {
    // Email para el Administrador
    send_admin_notification($orderId, $orderData, $settings);

    // Email para el Cliente (si proporcionó un correo)
    if (!empty($orderData['customer_email'])) {
        send_customer_confirmation($orderId, $orderData, $settings);
    }
}

function build_mailer($settings) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->AuthType   = 'LOGIN'; // Forzar autenticación LOGIN
        $mail->Username   = $settings['brevo_user'] ?? ''; // El email de tu cuenta de Brevo
        $mail->Password   = $settings['brevo_api_key'] ?? ''; // La API key v3
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom($settings['sender_email'] ?? 'no-reply@brisas.com', $settings['sender_name'] ?? 'Brisas Pedidos');
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return null;
    }
}

function send_admin_notification($orderId, $orderData, $settings) {
    if (empty($settings['admin_notification_email'])) return;

    $mail = build_mailer($settings);
    if (!$mail) return;

    try {
        $mail->addAddress($settings['admin_notification_email']);
        $mail->Subject = "Nuevo Pedido Recibido #{$orderId}";
        
        $body = "<h1>Nuevo Pedido #{$orderId}</h1><p>Se ha recibido un nuevo pedido con los siguientes detalles:</p>";
        $body .= "<ul>";
        foreach ($orderData as $key => $value) {
            if ($key !== 'cart') {
                $body .= "<li><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> " . htmlspecialchars($value) . "</li>";
            }
        }
        $body .= "</ul><hr><h3>Productos:</h3><table border='1' cellpadding='5' cellspacing='0'><tr><th>Producto</th><th>Cantidad</th></tr>";
        foreach ($orderData['cart'] as $item) {
            $body .= "<tr><td>" . htmlspecialchars($item['name']) . "</td><td>{$item['quantity']}</td></tr>";
        }
        $body .= "</table>";

        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("Admin mail error: {$mail->ErrorInfo}");
    }
}

function send_customer_confirmation($orderId, $orderData, $settings) {
    $mail = build_mailer($settings);
    if (!$mail) return;

    try {
        $mail->addAddress($orderData['customer_email']);
        $mail->Subject = "Confirmación de tu Pedido Brisas #{$orderId}";
        
        $logoUrl = APP_URL . '/' . ($settings['logo_frontend_url'] ?? '');
        $body = "<div style='font-family: sans-serif; color: #333;'><img src='{$logoUrl}' alt='Logo Brisas' style='max-height: 80px;'><h1 style='color: #aa182c;'>¡Gracias por tu pedido!</h1>";
        $body .= "<p>Hola " . htmlspecialchars($orderData['customer_name'] ?? $orderData['mercaderista_name']) . ", hemos recibido tu pedido #{$orderId} y lo estamos procesando.</p>";
        $body .= "<h3>Resumen de tu pedido:</h3><table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        $body .= "<thead style='background-color: #f2f2f2;'><tr><th>Producto</th><th>Cantidad</th></tr></thead><tbody>";
        foreach ($orderData['cart'] as $item) {
            $body .= "<tr><td>" . htmlspecialchars($item['name']) . "</td><td>{$item['quantity']}</td></tr>";
        }
        $body .= "</tbody></table><p style='margin-top: 20px;'>Gracias por preferirnos.</p><p><strong>Equipo Brisas</strong></p></div>";

        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("Customer mail error: {$mail->ErrorInfo}");
    }
}

?>