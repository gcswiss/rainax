<?php
declare(strict_types=1);

page_hero('Rechtliches', 'Datenschutz', '', 'sky');
?>
<section class="section">
    <div class="wrap narrow prose reveal"><?= paragraphs($c['rechtliches']['datenschutz'] ?? '') ?></div>
</section>
