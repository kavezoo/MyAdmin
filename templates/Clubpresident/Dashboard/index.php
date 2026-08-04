<?php
/**
 * Club president panel — Dashboard (placeholder).
 *
 * @var \App\View\AppView $this
 */
$this->assign('title', __('Dashboard'));
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<p class="mb-2"><?= __('Welcome to the Club president panel.') ?></p>
				<p class="mb-0">
					<?= $this->Html->link(
						__('Review membership applicants'),
						['prefix' => 'Clubpresident', 'controller' => 'Applicants', 'action' => 'index']
					) ?>
				</p>
			</div>
		</div>
	</div>
</div>
