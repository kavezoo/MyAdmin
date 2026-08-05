<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $member
 */
$this->Html->css(['pages/index'], ['block' => true]);
$this->assign('title', __('Member details'));
$memberId = (string)$member->get('id');
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-user"></i> <?= __('Member details') ?></h3>
			</div>
			<div class="card-body">
				<?= $this->element('users/member_view_fields', compact('member')) ?>
			</div>
			<div class="card-footer">
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-arrow-left"></i></span>' . __('Back to list'),
					['action' => 'index'],
					['escape' => false, 'class' => 'btn btn-outline-secondary']
				) ?>
				<?= $this->Html->link(
					'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
					['action' => 'edit', $memberId],
					['escape' => false, 'class' => 'btn btn-primary ms-2']
				) ?>
			</div>
		</div>
	</div>
</div>
