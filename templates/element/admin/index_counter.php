<?php
/**
 * Admin index / search: bake-style Paginator counter (left footer summary).
 *
 * Msgid placeholders: {{page}} {{pages}} {{current}} {{count}} (and {{start}} {{end}} if needed).
 *
 * @var \App\View\AppView $this
 */
?>
<?= $this->Paginator->counter(
	__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
) ?>
