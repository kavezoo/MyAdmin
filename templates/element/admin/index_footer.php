<?php
/**
 * Admin index card footer: left = counter element, right = pagination.
 *
 * @var \App\View\AppView $this
 */
?>
<div class="float-left text-muted">
	<?= $this->element('admin/index_counter') ?>
</div>
<div class="float-right">
	<?= $this->element('admin/index_pagination') ?>
</div>
<div class="clearfix"></div>
