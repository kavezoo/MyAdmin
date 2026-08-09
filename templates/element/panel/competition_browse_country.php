<?php
/**
 * Competition browse country Select2 (Member / Clubpresident).
 * Persists via ?country_id= + session (CompetitionBrowse).
 *
 * @var \App\View\AppView $this
 * @var int $browseCountryId
 * @var array<int, string> $browseCountryOptions
 * @var int $homeCountryId Optional — shown in help text when different
 */
$browseCountryId = (int)($browseCountryId ?? 0);
$browseCountryOptions = $browseCountryOptions ?? [];
$homeCountryId = (int)($homeCountryId ?? 0);
if ($browseCountryOptions === [] || $browseCountryId < 1) {
	return;
}

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/setups_index',
], ['block' => true]);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'pages/setups_index',
], ['block' => 'scriptBottom']);
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
	<?= $this->element('admin/working_country_select', [
		'workingCountryId' => $browseCountryId,
		'countryOptions' => $browseCountryOptions,
	]) ?>
	<?php if ($homeCountryId > 0 && $homeCountryId !== $browseCountryId): ?>
		<span class="text-muted small">
			<?= h(__('Your country: {0}', \App\Utility\AdminCountry::label($homeCountryId))) ?>
		</span>
	<?php endif; ?>
</div>
<p class="form-text mt-0 mb-3">
	<?= __('Choose which country’s competitions to list. You can apply to (or manage) competitions abroad.') ?>
</p>
