<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $member
 * @var string $feeField
 * @var string $feeLabel
 * @var bool $showEnabled
 */
echo $this->element('users/member_edit_form', [
	'member' => $member,
	'feeField' => $feeField ?? null,
	'feeLabel' => $feeLabel ?? null,
	'showEnabled' => $showEnabled ?? false,
]);
