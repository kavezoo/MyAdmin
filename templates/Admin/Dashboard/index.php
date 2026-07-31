<?php
/**
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
				<p class="mb-3"><?= __('Welcome to MyAdmin. Use the sidebar to manage your data.') ?></p>
				<div class="d-flex flex-wrap gap-2">
					<a class="btn btn-primary" href="<?= $this->Url->build(['controller' => 'Samples', 'action' => 'index']) ?>">
						<span class="btn-label"><i class="fa fa-table"></i></span><?= __('Samples') ?>
					</a>
					<a class="btn btn-outline-primary" href="<?= $this->Url->build(['controller' => 'Parents', 'action' => 'index']) ?>">
						<span class="btn-label"><i class="fa fa-folder"></i></span><?= __('Parents') ?>
					</a>
					<a class="btn btn-outline-primary" href="<?= $this->Url->build(['controller' => 'Cities', 'action' => 'index']) ?>">
						<span class="btn-label"><i class="fa fa-map-marker"></i></span><?= __('Cities') ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
