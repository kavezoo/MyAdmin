<?php
/**
 * Competition application detail fields (lunch / pipes / comment).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var \App\Model\Entity\CompetitionsUser|null $application
 * @var bool $readonly
 */
$application = $application ?? null;
$readonly = (bool)($readonly ?? false);
$lunch = (int)($application->lunch_for_the_attendant ?? 0);
$special = (string)($application->special_lunch ?? '');
$comment = (string)($application->comment ?? '');
?>
<?php if ($readonly): ?>
	<dl class="row record-view-fields mb-0">
		<div class="record-view-row">
			<dt><?= __('Lunch for attendant') ?></dt>
			<dd><?= h(\App\Utility\LocaleNumberParser::format($lunch, decimals: 0)) ?></dd>
		</div>
		<div class="record-view-row">
			<dt><?= __('Special lunch') ?></dt>
			<dd><?= $special !== '' ? h($special) : '—' ?></dd>
		</div>
		<?php for ($i = 1; $i <= 3; $i++):
			$title = trim((string)$competition->get('racing_pipe_' . $i . '_title'));
			if ($title === '') {
				continue;
			}
			$qty = (int)($application?->get('racing_pipe_' . $i . '_qty') ?? 0);
			?>
			<div class="record-view-row">
				<dt><?= h($title) ?></dt>
				<dd><?= h(\App\Utility\LocaleNumberParser::format($qty, decimals: 0)) ?></dd>
			</div>
		<?php endfor; ?>
		<div class="record-view-row">
			<dt><?= __('Comment') ?></dt>
			<dd><?= $comment !== '' ? nl2br(h($comment)) : '—' ?></dd>
		</div>
	</dl>
<?php else: ?>
	<div class="row mb-3">
		<div class="col-md-4">
			<label class="form-label"><?= __('Lunch for attendant') ?></label>
			<?= $this->Form->control('lunch_for_the_attendant', \App\Utility\LocaleNumberParser::formIntegerOptions(
				$lunch,
				['label' => false, 'id' => 'lunch-for-the-attendant']
			)) ?>
		</div>
		<div class="col-md-8">
			<label class="form-label"><?= __('Special lunch') ?></label>
			<?= $this->Form->control('special_lunch', [
				'label' => false,
				'class' => 'form-control',
				'value' => $special,
			]) ?>
		</div>
	</div>
	<div class="row mb-3">
		<?php
		$pipeShown = false;
		for ($i = 1; $i <= 3; $i++):
			$title = trim((string)$competition->get('racing_pipe_' . $i . '_title'));
			if ($title === '') {
				continue;
			}
			$pipeShown = true;
			$qty = (int)($application?->get('racing_pipe_' . $i . '_qty') ?? 0);
			?>
			<div class="col-md-4">
				<label class="form-label"><?= h($title) ?></label>
				<?= $this->Form->control('racing_pipe_' . $i . '_qty', \App\Utility\LocaleNumberParser::formIntegerOptions(
					$qty,
					['label' => false, 'id' => 'racing-pipe-' . $i . '-qty']
				)) ?>
			</div>
		<?php endfor; ?>
	</div>
	<?php if (!$pipeShown): ?>
		<?php /* no pipe types configured on this competition */ ?>
	<?php endif; ?>
	<div class="mb-3">
		<label class="form-label"><?= __('Comment') ?></label>
		<?= $this->Form->control('comment', [
			'label' => false,
			'class' => 'form-control',
			'type' => 'textarea',
			'rows' => 3,
			'value' => $comment,
		]) ?>
	</div>
<?php endif; ?>
