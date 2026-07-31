<?php
/**
 * @var \App\View\AppView $this
 * @var string $lang
 */

use Cake\I18n\I18n;
?>
<h1>Member</h1>
<p>Aktuális nyelv: <?= h($lang) ?> (<?= h(I18n::getLocale()) ?>)</p>
