<?php
/**
 * @var \App\View\AppView $this
 * @var string $lang
 */

use Cake\I18n\I18n;
?>
<h1><?= __('Member') ?></h1>
<p><?= __('Current language:') ?> <?= h($lang) ?> (<?= h(I18n::getLocale()) ?>)</p>
