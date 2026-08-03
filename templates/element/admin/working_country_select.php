<?php
/**
 * Admin working-country Select2 (Setups index header).
 *
 * Persists via ?country_id= → session + cookie (AdminCountry).
 *
 * @var \App\View\AppView $this
 * @var int $workingCountryId
 * @var array<int, string> $countryOptions
 */
$workingCountryId = (int)($workingCountryId ?? 0);
$countryOptions = $countryOptions ?? [];
$changeUrl = $this->Url->build(['action' => 'index']);
?>
<div class="working-country-select d-flex align-items-center gap-2 me-2">
	<label for="working-country-id" class="col-form-label col-form-label-sm mb-0 text-nowrap">
		<?= h(__('Country:')) ?>
	</label>
	<select
		id="working-country-id"
		class="js-example-basic-single form-select form-select-sm working-country-select__input"
		data-placeholder="<?= h(__('Select country...')) ?>"
		data-change-url="<?= h($changeUrl) ?>"
		aria-label="<?= h(__('Working country')) ?>"
	>
		<?php foreach ($countryOptions as $id => $label): ?>
			<option value="<?= (int)$id ?>"<?= (int)$id === $workingCountryId ? ' selected' : '' ?>>
				<?= h($label) ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
