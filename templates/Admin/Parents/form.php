<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ParentRecord $parent
 */
$this->Html->css(['pages/form'], ['block' => true]);
$this->Html->script(['pages/form'], ['block' => 'scriptBottom']);
$isEdit = !$parent->isNew();
?>
<div class="row">
	<div class="col-12 col-xxl-11 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-check-square-o"></i> <?= $isEdit ? __('Edit parent') : __('New parent') ?></h3>
					<?= $isEdit ? __('Edit the selected record.') : __('Create a new record.') ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary"><i class="fa fa-times"></i></a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?= $this->Form->create($parent, ['id' => 'form-horizontal', 'autocomplete' => 'off']) ?>
					<div class="form-group row mb-3">
						<label for="name" class="col-sm-3 col-md-2 col-form-label"><?= __('Name:') ?></label>
						<div class="col-12 col-md-10 col-xl-5">
							<?= $this->Form->control('name', ['label' => false, 'class' => 'form-control', 'id' => 'name', 'autofocus' => true]) ?>
						</div>
					</div>
					<div class="form-group row mb-3">
						<label for="pos" class="col-sm-3 col-md-2 col-form-label"><?= __('Position:') ?></label>
						<div class="col-12 col-md-10 col-xl-3">
							<?= $this->Form->control('pos', ['label' => false, 'class' => 'form-control', 'id' => 'pos']) ?>
						</div>
					</div>
					<div class="form-group row mb-3">
						<div class="d-none d-md-block col-md-2"></div>
						<div class="col-12 col-md-10">
							<div class="form-check form-switch">
								<?= $this->Form->checkbox('visible', ['class' => 'form-check-input', 'id' => 'visible']) ?>
								<label class="form-check-label" for="visible"><?= __('Visible') ?></label>
							</div>
						</div>
					</div>
				<?= $this->Form->end() ?>
			</div>
			<div class="card-footer">
				<button type="submit" form="form-horizontal" class="btn btn-success"><span class="btn-label"><i class="fa fa-save"></i></span><?= __('Save') ?></button>
				<a href="<?= $this->Url->build(['action' => 'index']) ?>" class="btn btn-outline-secondary ms-3"><span class="btn-label"><i class="fa fa-times"></i></span><?= __('Cancel') ?></a>
			</div>
		</div>
	</div>
</div>
