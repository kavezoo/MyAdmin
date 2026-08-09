<?php
/**
 * Competition staff names under title block (check-in first, then table judges).
 * Optional linked modal URLs (President / Admin) — Member panel shows plain text.
 *
 * @var \App\View\AppView $this
 * @var array{checkin?: list<array{id?: string, name?: string}|string>, judge?: list<array{id?: string, name?: string}|string>}|null $competitionStaffGroups
 * @var string|null $competitionId fallback load if groups not passed
 * @var array{getUrl?: string, editUrl?: string, viewUrl?: string, deleteUrl?: string, labels?: string, title?: string}|null $staffModal
 */
use App\Utility\CompetitionStaff;

$groups = $competitionStaffGroups ?? null;
if ($groups === null) {
	$id = trim((string)($competitionId ?? ''));
	$groups = $id !== '' ? CompetitionStaff::groupedDisplayPeople($id) : [
		CompetitionStaff::ROLE_CHECKIN => [],
		CompetitionStaff::ROLE_JUDGE => [],
	];
}

$normalizePeople = static function (array $list): array {
	$out = [];
	foreach ($list as $item) {
		if (is_string($item)) {
			$name = trim($item);
			if ($name !== '') {
				$out[] = ['id' => '', 'name' => $name];
			}
			continue;
		}
		if (!is_array($item)) {
			continue;
		}
		$name = trim((string)($item['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$out[] = [
			'id' => trim((string)($item['id'] ?? '')),
			'name' => $name,
		];
	}

	return $out;
};

$checkin = $normalizePeople($groups[CompetitionStaff::ROLE_CHECKIN] ?? []);
$judges = $normalizePeople($groups[CompetitionStaff::ROLE_JUDGE] ?? []);
if ($checkin === [] && $judges === []) {
	return;
}

$staffModal = $staffModal ?? null;
$canModal = is_array($staffModal) && trim((string)($staffModal['getUrl'] ?? '')) !== '';
$modalGetUrl = $canModal ? (string)$staffModal['getUrl'] : '';
$modalEditUrl = $canModal ? (string)($staffModal['editUrl'] ?? '') : '';
$modalViewUrl = $canModal ? (string)($staffModal['viewUrl'] ?? '') : '';
$modalDeleteUrl = $canModal ? (string)($staffModal['deleteUrl'] ?? '') : '';
$modalLabels = $canModal ? (string)($staffModal['labels'] ?? 'user') : 'user';
$modalTitle = $canModal ? (string)($staffModal['title'] ?? __('Member details')) : '';

$renderNames = function (array $people) use (
	$canModal,
	$modalGetUrl,
	$modalEditUrl,
	$modalViewUrl,
	$modalDeleteUrl,
	$modalLabels,
	$modalTitle
): string {
	$parts = [];
	foreach ($people as $person) {
		$name = (string)$person['name'];
		$id = (string)$person['id'];
		if ($canModal && $id !== '') {
			$parts[] = '<a href="#"'
				. ' class="record-modal-link"'
				. ' data-id="' . h($id) . '"'
				. ' data-get-url="' . h($modalGetUrl) . '"'
				. ' data-edit-url="' . h($modalEditUrl) . '"'
				. ' data-view-url="' . h($modalViewUrl) . '"'
				. ' data-delete-url="' . h($modalDeleteUrl) . '"'
				. ' data-labels="' . h($modalLabels) . '"'
				. ' data-title="' . h($modalTitle) . '"'
				. '>' . h($name)
				. '<span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>'
				. '</a>';
		} else {
			$parts[] = h($name);
		}
	}

	return implode(', ', $parts);
};
?>
<div class="competition-staff-under-title mt-2">
	<?php if ($checkin !== []): ?>
		<div class="competition-staff-under-title__row">
			<span class="competition-staff-under-title__role"><?= h(CompetitionStaff::roleLabel(CompetitionStaff::ROLE_CHECKIN)) ?>:</span>
			<span class="competition-staff-under-title__names"><?= $renderNames($checkin) ?></span>
		</div>
	<?php endif; ?>
	<?php if ($judges !== []): ?>
		<div class="competition-staff-under-title__row">
			<span class="competition-staff-under-title__role"><?= h(__('Table judges')) ?>:</span>
			<span class="competition-staff-under-title__names"><?= $renderNames($judges) ?></span>
		</div>
	<?php endif; ?>
</div>
