<?php
// Test email sending
$to = 'office@splm.ro';
$subject = 'Test Email - Contract Digital';
$message = '<html><body><h2>Test Email</h2><p>Dacă vezi acest mesaj, email-ul funcționează!</p></body></html>';

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type:text/html;charset=UTF-8\r\n";
$headers .= "From: Montessori <noreply@contractdigital.ro>\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "✅ mail() a returnat TRUE - email trimis (verifică inbox și SPAM)";
} else {
    echo "❌ mail() a returnat FALSE - serverul nu poate trimite email-uri";
}

echo "<hr>";
echo "<h3>Server Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "sendmail_path: " . ini_get('sendmail_path') . "<br>";
echo "SMTP: " . ini_get('SMTP') . "<br>";
?>