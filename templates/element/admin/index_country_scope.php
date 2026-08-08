<?php
/**
 * Admin index country scope UI (superuser Select2; admin sees label only).
 *
 * @var \App\View\AppView $this
 * @var int $filterCountryId
 * @var string $filterCountryLabel
 * @var array<int, string> $countryOptions
 * @var bool $canChangeCountry
 */
$filterCountryId = (int)($filterCountryId ?? $workingCountryId ?? 0);
$filterCountryLabel = (string)($filterCountryLabel ?? $workingCountryLabel ?? '');
$countryOptions = $countryOptions ?? [];
$canChangeCountry = !empty($canChangeCountry);
$workingCountryId = $filterCountryId;

$this->Html->css('pages/setups_index', ['block' => true]);
$this->Html->script('pages/setups_index', ['block' => 'scriptBottom']);
?>
<?php if ($canChangeCountry && $countryOptions !== []): ?>
	<?= $this->element('admin/working_country_select', [
		'workingCountryId' => $workingCountryId,
		'countryOptions' => $countryOptions,
	]) ?>
<?php endif; ?>
