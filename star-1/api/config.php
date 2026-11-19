<?php
// Star 1 Coiffeur — Einstellungen für das Buchungsportal.
// Diese Datei wird nie an den Browser ausgeliefert (siehe .htaccess).

return [
    // Speicherort der Datenbank (SQLite). Der Ordner muss für PHP beschreibbar sein.
    'db_path' => __DIR__ . '/../data/star1.sqlite',

    // Erster Admin-Zugang. Beim ersten Login muss das Passwort geändert werden.
    'admin_user'         => 'admin',
    'admin_initial_pass' => 'Star1-Wil-2026',

    // E-Mail-Benachrichtigungen (über PHP mail() des Hosters)
    'mail_enabled' => true,
    'mail_from'    => 'info@star-1.ch',
    'mail_salon'   => 'info@star-1.ch',   // erhält eine Kopie jeder Online-Buchung

    'salon_name'    => 'Star 1 Coiffeur',
    'salon_address' => 'Untere Bahnhofstrasse 19, 9500 Wil SG',
    'salon_phone'   => '071 245 19 19',

    'timezone' => 'Europe/Zurich',
];
