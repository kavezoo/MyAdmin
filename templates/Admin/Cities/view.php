<?php
/**
 * City view.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\City $city
 * @var string $countryLabel
 */
$this->Html->css(['pages/index'], ['block' => true]);
$countryLabel = (string)($countryLabel ?? \App\Utility\AdminCountry::label((int)$city->country_id));
?>
<div class="row">
	<div class="col-12 col-xxl-10 p-2 pt-3">
		<div class="card mb-3 shadow border border-2">
			<div class="card-header">
				<div class="float-left">
					<h3><i class="fa fa-map-marker"></i> <?= __('City details') ?></h3>
					<?= h((string)$city->name) ?>
					<?php if (!empty($city->zip)): ?>
						— <code><?= h((string)$city->zip) ?></code>
					<?php endif; ?>
				</div>
				<div class="float-right">
					<a role="button" href="<?= $this->Url->build($this->get('indexListUrl') ?? ['action' => 'index']) ?>" class="btn btn-outline-secondary">
						<i class="fa fa-times"></i>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<dl class="row record-view-fields mb-0">
					<div class="record-view-row"><dt><?= __('ID') ?></dt><dd><?= h((string)$city->id) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Country') ?></dt><dd><?= h($countryLabel) ?></dd></div>
					<div class="record-view-row"><dt><?= __('County') ?></dt><dd><?= h($city->county !== null ? (string)$city->county->name : '—') ?></dd></div>
					<div class="record-view-row"><dt><?= __('Name') ?></dt><dd><?= h((string)$city->name) ?></dd></div>
					<div class="record-view-row"><dt><?= __('Short name') ?></dt><dd><?= h((string)$city->shortname) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('ZIP') ?></dt><dd><?= h((string)($city->zip ?? '')) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Latitude') ?></dt><dd><?= h((string)$city->lat) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Longitude') ?></dt><dd><?= h((string)$city->lng) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Latitude (import)') ?></dt><dd><?= h((string)$city->lat2) ?: '—' ?></dd></div>
					<div class="record-view-row"><dt><?= __('Longitude (import)') ?></dt><dd><?= h((string)$city->lng2) ?: '—' ?></dd></div>
				</dl>
			</div>
			<div class="card-footer">
				<div class="record-view-footer-actions">
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-pencil"></i></span>' . __('Edit'),
						['action' => 'edit', $city->id],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
			</div>
		</div>
	</div>
</div>
