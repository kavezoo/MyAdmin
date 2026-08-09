<?php
/**
 * Summernote WYSIWYG assets — JeffAdmin5 pattern (zsfoto/jeffadmin5).
 *
 * @see https://packagist.org/packages/zsfoto/jeffadmin5
 * @see vendor/zsfoto/jeffadmin5/webroot/assets/js/jeffadmin5.js jeffAdminInitSummerNote()
 *
 * @var \App\View\AppView $this
 */
$this->Html->css([
	'/plugins/summernote/summernote-lite.min',
], ['block' => true]);
$this->Html->script([
	'/plugins/summernote/summernote-lite.min',
	'/plugins/summernote/lang/summernote-hu-HU.min',
], ['block' => 'scriptBottom']);
?>
