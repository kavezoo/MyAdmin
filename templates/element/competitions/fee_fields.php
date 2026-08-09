<?php
/**
 * Competition entry fee + racing-pipe prices (Admin / President form).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var array<string, string> $currencyOptions
 * @var string $defaultCurrency
 */
$competition = $competition ?? null;
if ($competition === null) {
	return;
}
$currencyOptions = $currencyOptions ?? \App\Utility\CountryCurrency::options();
$defaultCurrency = (string)($defaultCurrency ?? 'HUF');
$currency = strtoupper(trim((string)($competition->currency ?? '')));
if ($currency === '') {
	$currency = $defaultCurrency;
}
if (!isset($currencyOptions[$currency])) {
	$currencyOptions[$currency] = $currency;
}
?>
<div class="row">
	<div class="col-12 col-xxl-11">
		<hr class="my-4">
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('currency', __('Currency:'), ['for' => 'competition-currency']) ?>
	<div class="col-12 col-md-10 col-xl-3">
		<?= $this->Form->control('currency', [
			'label' => false,
			'type' => 'select',
			'options' => $currencyOptions,
			'value' => $currency,
			'class' => 'js-example-basic-single form-select',
			'id' => 'competition-currency',
			'data-default-currency' => $defaultCurrency,
		]) ?>
		<div class="form-text"><?= __('Defaults to the official currency of the competition country.') ?></div>
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('entry_fee_member', __('Entry fee (national member):'), ['for' => 'entry-fee-member', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-3">
		<?= $this->Form->control('entry_fee_member', \App\Utility\LocaleNumberParser::formDecimalOptions(
			$competition->entry_fee_member,
			0,
			['id' => 'entry-fee-member', 'label' => false]
		)) ?>
		<div class="form-text"><?= __('Applicant paid the national pipe association fee this year — usually the lower price.') ?></div>
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('entry_fee_non_member', __('Entry fee (not national member):'), ['for' => 'entry-fee-non-member', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-3">
		<?= $this->Form->control('entry_fee_non_member', \App\Utility\LocaleNumberParser::formDecimalOptions(
			$competition->entry_fee_non_member,
			0,
			['id' => 'entry-fee-non-member', 'label' => false]
		)) ?>
		<div class="form-text"><?= __('National association fee not paid for this year — usually the higher price.') ?></div>
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('lunch_price', __('Lunch price:'), ['for' => 'lunch-price', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-3">
		<?= $this->Form->control('lunch_price', \App\Utility\LocaleNumberParser::formDecimalOptions(
			$competition->get('lunch_price'),
			0,
			['id' => 'lunch-price', 'label' => false]
		)) ?>
		<div class="form-text"><?= h(__('Per extra lunch. Description is on the language tabs ({0}). Placeholder: {1}', '{{lunch_description}}', '{{lunch_price}}')) ?></div>
	</div>
</div>
<?php for ($i = 1; $i <= 3; $i++): ?>
	<div class="form-group row mb-2">
		<?= $this->Form->adminLabel(
			'racing_pipe_' . $i . '_price_member',
			__('Racing pipe {0} price (national member):', $i),
			['for' => 'racing-pipe-' . $i . '-price-member', 'required' => false]
		) ?>
		<div class="col-12 col-md-10 col-xl-3">
			<?= $this->Form->control('racing_pipe_' . $i . '_price_member', \App\Utility\LocaleNumberParser::formDecimalOptions(
				$competition->get('racing_pipe_' . $i . '_price_member'),
				0,
				['id' => 'racing-pipe-' . $i . '-price-member', 'label' => false]
			)) ?>
		</div>
	</div>
	<div class="form-group row mb-2">
		<?= $this->Form->adminLabel(
			'racing_pipe_' . $i . '_price_non_member',
			__('Racing pipe {0} price (not national member):', $i),
			['for' => 'racing-pipe-' . $i . '-price-non-member', 'required' => false]
		) ?>
		<div class="col-12 col-md-10 col-xl-3">
			<?= $this->Form->control('racing_pipe_' . $i . '_price_non_member', \App\Utility\LocaleNumberParser::formDecimalOptions(
				$competition->get('racing_pipe_' . $i . '_price_non_member'),
				0,
				['id' => 'racing-pipe-' . $i . '-price-non-member', 'label' => false]
			)) ?>
		</div>
	</div>
	<div class="form-group row mb-3">
		<?= $this->Form->adminLabel(
			'racing_pipe_' . $i . '_image_file',
			__('Racing pipe {0} photo:', $i),
			['for' => 'racing-pipe-' . $i . '-image-file', 'required' => false]
		) ?>
		<div class="col-12 col-md-10 col-xl-6">
			<?php
			$pipeImg = \App\Utility\CompetitionPipeImage::publicUrl((string)$competition->get('racing_pipe_' . $i . '_image'));
			if ($pipeImg !== ''):
			?>
				<div class="mb-2">
					<img src="<?= h($pipeImg) ?>" alt="" class="img-thumbnail" style="max-height:100px;">
				</div>
			<?php endif; ?>
			<?= $this->Form->control('racing_pipe_' . $i . '_image_file', [
				'label' => false,
				'type' => 'file',
				'accept' => 'image/jpeg,image/png,image/webp',
				'id' => 'racing-pipe-' . $i . '-image-file',
			]) ?>
			<div class="form-text"><?= h(__('Optional. Placeholder: {0}', '{{racing_pipe_' . $i . '_image}}')) ?></div>
		</div>
	</div>
<?php endfor; ?>
<div class="form-text mb-3 col-12 col-md-10 offset-md-2">
	<?= h(__('Pipe prices are per piece. Lunch fee = extra lunches × lunch price. Placeholders: {0}, {1}, {2}, {3}.', '{{entry_fee_member}}', '{{lunch_price}}', '{{lunch_description}}', '{{racing_pipe_N_image}}')) ?>
</div>
