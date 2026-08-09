<?php
/**
 * Clubpresident — assign staff for one competition.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var list<\App\Model\Entity\CompetitionStaff> $competitionStaff
 * @var array<string, string> $staffRoles
 */
$this->Html->css([
	'pages/index',
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
], ['block' => true]);
$this->Html->script([
	'/plugins/select2-4.1.0/js/select2.full.min',
	'pages/competition_staff',
], ['block' => 'scriptBottom']);
$config = [
	'inputTooShort' => __('Please enter 2 or more characters'),
	'noResults' => __('No results found.'),
	'searching' => __('Searching…'),
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode(['competitionStaff' => $config], JSON_UNESCAPED_UNICODE)
	. ');',
	['block' => true]
);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="mb-3">
			<h3 class="mb-1"><?= h((string)$competition->name) ?></h3>
		</div>
		<?= $this->element('competitions/staff_assign', [
			'competition' => $competition,
			'competitionStaff' => $competitionStaff ?? [],
			'staffRoles' => $staffRoles ?? [],
			'staffAddUrl' => ['action' => 'staffAdd', $competition->id],
			'staffUserAjaxUrl' => $this->Url->build(['action' => 'userOptions']),
			'staffSearchCountryId' => (int)$competition->country_id,
			'staffBackUrl' => ['action' => 'index'],
		]) ?>
	</div>
</div>
