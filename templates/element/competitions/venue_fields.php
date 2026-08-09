<?php
/**
 * Venue fields on competition form (Admin + President).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var array<int|string, string> $cityOptions
 * @var int $formCountryId
 * @var string $cityOptionsUrl
 */
$competition = $competition ?? null;
$cityOptions = $cityOptions ?? [];
$formCountryId = (int)($formCountryId ?? 0);
$cityOptionsUrl = (string)($cityOptionsUrl ?? '');
if ($competition === null) {
	return;
}
?>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('city_id', __('Venue city:'), ['for' => 'city-id', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-5">
		<?= $this->Form->control('city_id', [
			'label' => false,
			'type' => 'select',
			'options' => $cityOptions,
			'empty' => __('Search city (name or ZIP)…'),
			'class' => 'form-select js-competition-city',
			'id' => 'city-id',
			'data-ajax-url' => $cityOptionsUrl,
			'data-country-id' => $formCountryId,
		]) ?>
		<div class="form-text"><?= __('Type at least 2 characters. Label shows name and postal code.') ?></div>
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('venue_name', __('Venue name:'), ['for' => 'venue-name', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-5">
		<?= $this->Form->control('venue_name', [
			'label' => false,
			'type' => 'text',
			'class' => 'form-control',
			'id' => 'venue-name',
			'maxlength' => 250,
			'placeholder' => __('e.g. Ibafa Culture House'),
		]) ?>
		<div class="form-text"><?= __('Building or place name used in the announcement ({{venue_name}}).') ?></div>
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('venue_address', __('Venue address:'), ['for' => 'venue-address', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-5">
		<?= $this->Form->control('venue_address', [
			'label' => false,
			'type' => 'text',
			'class' => 'form-control',
			'id' => 'venue-address',
			'maxlength' => 255,
		]) ?>
	</div>
</div>
<div class="form-group row mb-3">
	<?= $this->Form->adminLabel('google_maps_url', __('Google Maps URL:'), ['for' => 'google-maps-url', 'required' => false]) ?>
	<div class="col-12 col-md-10 col-xl-8">
		<?= $this->Form->control('google_maps_url', [
			'label' => false,
			'type' => 'url',
			'class' => 'form-control',
			'id' => 'google-maps-url',
			'maxlength' => 1000,
			'placeholder' => 'https://maps.google.com/...',
		]) ?>
		<div class="form-text"><?= __('Paste a Google Maps place/share link, short link (maps.app.goo.gl), or embed iframe. Coordinates are read from the link — do not paste a search page that points elsewhere. Use {{map}} in the description where the map should appear.') ?></div>
	</div>
</div>
<div class="form-text mb-3 col-12 col-md-10 offset-md-2">
	<?= __('Logos in the announcement HTML: {{national_association_logo}} (country / national pipe association, PNG with transparency) and {{club_logo}} (organizer club). Upload the national logo on Countries; club logo on the club record.') ?>
</div>
