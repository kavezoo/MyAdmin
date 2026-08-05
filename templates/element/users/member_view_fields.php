<?php
/**
 * Read-only member fields (view page).
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $member
 */
use App\Auth\MembershipProfile;
use App\Utility\AdminCountry;
use App\Utility\MembershipFee;

$clubName = '';
if ($member->get('club') !== null) {
    $clubEntity = $member->get('club');
    if (is_object($clubEntity) && method_exists($clubEntity, 'get')) {
        $clubName = (string)($clubEntity->get('name') ?? '');
    }
}
$clubFeeDate = $member->get(MembershipFee::FIELD_CLUB);
$nationalFeeDate = $member->get(MembershipFee::FIELD_NATIONAL);
?>
<dl class="row mb-0">
	<dt class="col-sm-3"><?= __('ID') ?></dt>
	<dd class="col-sm-9"><?= h((string)$member->get('id')) ?></dd>

	<dt class="col-sm-3"><?= __('Name') ?></dt>
	<dd class="col-sm-9"><?= h(MembershipProfile::displayName($member)) ?></dd>

	<dt class="col-sm-3"><?= __('Email') ?></dt>
	<dd class="col-sm-9"><?= h((string)($member->get('email') ?? '')) ?></dd>

	<dt class="col-sm-3"><?= __('Phone') ?></dt>
	<dd class="col-sm-9"><?= h((string)($member->get('phone') ?? '')) ?></dd>

	<dt class="col-sm-3"><?= __('Role') ?></dt>
	<dd class="col-sm-9"><?= h((string)($member->get('role') ?? '')) ?></dd>

	<dt class="col-sm-3"><?= __('Country') ?></dt>
	<dd class="col-sm-9"><?= h(AdminCountry::label((int)($member->get('country_id') ?? 0))) ?></dd>

	<dt class="col-sm-3"><?= __('Club') ?></dt>
	<dd class="col-sm-9"><?= h($clubName) ?></dd>

	<dt class="col-sm-3"><?= __('Active') ?></dt>
	<dd class="col-sm-9">
		<?= (bool)$member->get('active')
			? '<i class="fa fa-check text-success"></i>'
			: '<i class="fa fa-times text-danger"></i>' ?>
	</dd>

	<dt class="col-sm-3"><?= __('Enabled') ?></dt>
	<dd class="col-sm-9">
		<?= (int)($member->get('enabled') ?? 0) === 1
			? '<i class="fa fa-check text-success"></i>'
			: '<i class="fa fa-times text-danger"></i>' ?>
	</dd>

	<dt class="col-sm-3"><?= h(MembershipFee::clubFeeLabel((int)($member->get('country_id') ?? 0))) ?></dt>
	<dd class="col-sm-9"><?= $clubFeeDate ? h(\App\Utility\LocaleDateParser::format($clubFeeDate, 'date')) : '' ?></dd>

	<dt class="col-sm-3"><?= h(MembershipFee::nationalFeeLabel((int)($member->get('country_id') ?? 0))) ?></dt>
	<dd class="col-sm-9"><?= $nationalFeeDate ? h(\App\Utility\LocaleDateParser::format($nationalFeeDate, 'date')) : '' ?></dd>

	<dt class="col-sm-3"><?= __('Created') ?></dt>
	<dd class="col-sm-9"><?= $member->get('created') ? h(\App\Utility\LocaleDateParser::format($member->get('created'), 'datetime_short')) : '' ?></dd>

	<dt class="col-sm-3"><?= __('Modified') ?></dt>
	<dd class="col-sm-9"><?= $member->get('modified') ? h(\App\Utility\LocaleDateParser::format($member->get('modified'), 'datetime_short')) : '' ?></dd>
</dl>
