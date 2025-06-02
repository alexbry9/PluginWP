<?php
function enviar_correo_smtp($destinatario, $asunto, $mensaje) {
    $smtp_server = "smtp.serviciodecorreo.es";
    $smtp_port = 587;
    $smtp_username = "soporte@evoluziona.es";
    $smtp_password = "Ev0l2025@";

    $headers = "From: soporte@evoluziona.es\r\n";
    $headers .= "Reply-To: soporte@evoluziona.es\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $socket = fsockopen($smtp_server, $smtp_port, $errno, $errstr, 10);
    if (!$socket) {
        error_log("Error SMTP: $errno - $errstr");
        return false;
    }

    $leer = function() use ($socket) {
        $response = '';
        while ($str = fgets($socket, 512)) {
            $response .= $str;
            if (substr($str, 3, 1) != '-') break;
        }
        return $response;
    };

    $leer();
    fputs($socket, "EHLO localhost\r\n"); $leer();
    fputs($socket, "STARTTLS\r\n"); $leer();
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    fputs($socket, "EHLO localhost\r\n"); $leer();
    fputs($socket, "AUTH LOGIN\r\n"); $leer();
    fputs($socket, base64_encode($smtp_username) . "\r\n"); $leer();
    fputs($socket, base64_encode($smtp_password) . "\r\n"); $leer();
    fputs($socket, "MAIL FROM: <$smtp_username>\r\n"); $leer();
    fputs($socket, "RCPT TO: <$destinatario>\r\n"); $leer();
    fputs($socket, "DATA\r\n"); $leer();
    $contenido = "Subject: $asunto\r\n$headers\r\n$mensaje\r\n.\r\n";
    fputs($socket, $contenido); $leer();
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}

?>