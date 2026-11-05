<?php
// Kopie als lib/config.php speichern und anpassen. config.php wird nicht ins Git eingecheckt.
return [
    // Absender und Empfänger für Buchungs-E-Mails
    'salon_email'   => 'kontakt@jordancoiffeur.ch',
    'mail_from'     => 'Jordan Coiffeur & Barbershop <noreply@jordancoiffeur.ch>',
    'site_url'      => 'https://jordancoiffeur.ch',

    // Einmaliger Einrichtungsschlüssel: wird nur beim allerersten Admin-Login gebraucht,
    // um das Admin-Passwort festzulegen. Danach ohne Bedeutung. Bitte durch etwas Langes ersetzen.
    'setup_key'     => 'BITTE-AENDERN',

    // Zeitzone
    'timezone'      => 'Europe/Zurich',
];
