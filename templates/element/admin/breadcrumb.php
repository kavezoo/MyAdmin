<?php
/**
 * @var \App\View\AppView $this
 */
$controller = (string)$this->request->getParam('controller');
$action = (string)$this->request->getParam('action');
$isIndex = $action === 'index';
$isForm = in_array($action, ['add', 'edit'], true);
$isView = $action === 'view';
$id = $this->request->getParam('pass.0');

$indexUrl = $this->Url->build(['action' => 'index']);
$addUrl = $this->Url->build(['action' => 'add']);
$editUrl = $id ? $this->Url->build(['action' => 'edit', $id]) : '#';
$viewUrl = $id ? $this->Url->build(['action' => 'view', $id]) : '#';

$crumbTitle = $this->fetch('breadcrumb') ?: ($this->get('breadcrumb') ?? $controller);
?>
<div class="row">
	<div class="col-xl-12">
		<div class="breadcrumb-holder breadcrumb-holder-actions border-bottom border-1">
			<div class="float-left toolbar-buttons">
				<?php if (!$isIndex): ?>
					<a role="button" href="<?= h($indexUrl) ?>" class="btn btn-outline-secondary" id="btn-back">
						<span class="btn-label"><i class="fa fa-arrow-left"></i></span><?= __('Back to list') ?>
					</a>
				<?php endif; ?>

				<?php if ($isIndex || $isView): ?>
					<a role="button" href="<?= h($addUrl) ?>" class="btn btn-success" id="btn-new" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="<?= h('<b>' . __('New') . '</b><br>' . __('Create a new record.')) ?>">
						<span class="btn-label"><i class="fa fa-plus"></i></span><?= __('New') ?>
					</a>
				<?php endif; ?>

				<?php if ($isForm): ?>
					<button type="submit" form="form-horizontal" class="btn btn-success" id="btn-save-breadcrumb" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="<?= h('<b>' . __('Save') . '</b><br>' . __('Save the form data.')) ?>">
						<span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?>
					</button>
				<?php endif; ?>

				<?php if ($isView && $id): ?>
					<a role="button" href="<?= h($editUrl) ?>" class="btn btn-primary" id="btn-edit" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="<?= h('<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.')) ?>">
						<span class="btn-label"><i class="fa fa-pencil"></i></span><?= __('Edit') ?>
					</a>
				<?php endif; ?>

				<?php if (($isView || $isForm) && $id): ?>
					<a role="button" href="<?= h($viewUrl) ?>" class="btn btn-info<?= $isView ? ' d-none' : '' ?>" id="btn-view" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="<?= h('<b>' . __('View details') . '</b><br>' . __('View the selected record.')) ?>">
						<span class="btn-label"><i class="fa fa-eye"></i></span><?= __('View details') ?>
					</a>
					<a role="button" href="#" class="btn btn-danger" id="btn-delete" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-html="true" title="<?= h('<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.')) ?>">
						<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Delete') ?>
					</a>
				<?php endif; ?>
			</div>
			<ol class="breadcrumb float-right">
				<li class="breadcrumb-item">
					<a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index']) ?>" class="text-secondary fw-bold"><?= __('Home') ?></a>
				</li>
				<li class="breadcrumb-item active"><?= h($crumbTitle) ?></li>
			</ol>
			<div class="clearfix"></div>
		</div>
	</div>
</div>
