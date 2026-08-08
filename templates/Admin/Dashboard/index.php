<?php
/**
 * @var \App\View\AppView $this
 */
use App\Utility\PanelNav;

$this->assign('title', __('Dashboard'));
$cards = PanelNav::forPrefix('Admin', $this->getRequest());
?>
<div class="row">
	<div class="col-12 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<h3 class="fw-bold"><i class="fa fa-tachometer"></i> <?= __('Dashboard') ?></h3>
			</div>
			<div class="card-body">
				<p class="mb-3"><?= __('Welcome to {0}. Choose a module below — each card describes where the button will take you.', h((string)($appName ?? \App\Utility\AppBrand::name()))) ?></p>
				<?= $this->element('panel/dashboard_nav_cards', ['cards' => $cards]) ?>
			</div>
		</div>
	</div>
</div>
