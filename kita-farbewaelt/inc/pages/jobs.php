<?php
declare(strict_types=1);

$j = $c['jobs'] ?? [];
$mail = $j['bewerbung_email'] ?? ($c['allgemein']['email'] ?? '');
page_hero('Jobs', $j['titel'] ?? '', $j['intro'] ?? '', 'ochre');
?>
<section class="section">
    <div class="wrap narrow">
        <?php if (!empty($j['stellen'])): ?>
            <div class="jobs">
                <?php foreach ($j['stellen'] as $i => $s): ?>
                    <article class="job reveal" style="--d:<?= $i * 90 ?>ms">
                        <div class="job__head">
                            <h2><?= e($s['titel'] ?? '') ?></h2>
                            <?php if (!empty($s['pensum'])): ?><span class="pill"><?= e($s['pensum']) ?></span><?php endif; ?>
                        </div>
                        <?= paragraphs($s['text'] ?? '') ?>
                        <div class="job__actions">
                            <a class="btn btn--green" href="mailto:<?= e($mail) ?>?subject=<?= rawurlencode('Bewerbung: ' . ($s['titel'] ?? '')) ?>">Jetzt bewerben <?= icon('arrow') ?></a>
                            <?php if (!empty($s['pdf'])): ?><a class="btn btn--ghost" href="<?= e(asset($s['pdf'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?> Inserat (PDF)</a><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty reveal">
                <?= rainbow('empty__rainbow') ?>
                <?= paragraphs($j['keine_stellen'] ?? '', 'lead') ?>
                <a class="btn btn--green" href="mailto:<?= e($mail) ?>?subject=<?= rawurlencode('Spontanbewerbung') ?>">Spontan bewerben <?= icon('arrow') ?></a>
            </div>
        <?php endif; ?>
    </div>
</section>
