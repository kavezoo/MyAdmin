<?php
/**
 * Validation error summary + per-field messages (profile / complete-profile).
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $entity
 * @var array<string, string>|null $fieldLabels
 */
use App\Utility\EntityFormErrors;

$entity = $entity ?? null;
if ($entity === null || !$entity->hasErrors()) {
	return;
}

$fieldLabels = $fieldLabels ?? EntityFormErrors::profileFieldLabels();
$lines = EntityFormErrors::labeledLines($entity, $fieldLabels);
if ($lines === []) {
	return;
}
?>
<div class="alert alert-danger users-form-errors border border-danger" role="alert">
	<p class="mb-2 fw-bold">
		<i class="fa fa-exclamation-circle me-1" aria-hidden="true"></i>
		<?= __('Please correct the following:') ?>
	</p>
	<ul class="mb-0 ps-3">
		<?php foreach ($lines as $line): ?>
			<li><?= h($line) ?></li>
		<?php endforeach; ?>
	</ul>
</div>
