<?php
/**
 * Competition application detail fields (companions / lunch / pipes / comment).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var \App\Model\Entity\CompetitionsUser|null $application
 * @var bool $readonly
 * @var mixed $feeUser
 */
$application = $application ?? null;
$readonly = (bool)($readonly ?? false);
$lunch = (int)($application->lunch_for_the_attendant ?? 0);
$companions = (int)($application->companion_count ?? 0);
$special = (string)($application->special_lunch ?? '');
$comment = (string)($application->comment ?? '');
$lunchPrice = \App\Utility\CompetitionFees::lunchUnitPrice($competition);
$lunchDesc = trim((string)($competition->get('lunch_description') ?? ''));
?>
<?php if ($readonly): ?>
	<dl class="row record-view-fields mb-0">
		<div class="record-view-row">
			<dt><?= __('Companions') ?></dt>
			<dd><?= h(\App\Utility\LocaleNumberParser::format($companions, decimals: 0)) ?></dd>
		</div>
		<div class="record-view-row">
			<dt><?= __('Extra lunches') ?></dt>
			<dd>
				<?= h(\App\Utility\LocaleNumberParser::format($lunch, decimals: 0)) ?>
				<?php if ($lunchPrice > 0): ?>
					<span class="text-muted"> — <?= h(\App\Utility\CompetitionFees::format($lunch * $lunchPrice, $competition)) ?></span>
				<?php endif; ?>
			</dd>
		</div>
		<?php if ($lunchDesc !== ''): ?>
			<div class="record-view-row">
				<dt><?= __('Lunch') ?></dt>
				<dd><?= h($lunchDesc) ?></dd>
			</div>
		<?php endif; ?>
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
			<label class="form-label"><?= __('Companions') ?></label>
			<?= $this->Form->control('companion_count', \App\Utility\LocaleNumberParser::formIntegerOptions(
				$companions,
				['label' => false, 'id' => 'companion-count']
			)) ?>
			<div class="form-text"><?= __('How many companions are coming with you?') ?></div>
		</div>
		<div class="col-md-4">
			<label class="form-label"><?= __('Extra lunches') ?></label>
			<?= $this->Form->control('lunch_for_the_attendant', \App\Utility\LocaleNumberParser::formIntegerOptions(
				$lunch,
				['label' => false, 'id' => 'lunch-for-the-attendant']
			)) ?>
			<?php if ($lunchPrice > 0): ?>
				<div class="form-text"><?= h(__('Price per lunch: {0}', \App\Utility\CompetitionFees::format($lunchPrice, $competition))) ?></div>
			<?php endif; ?>
			<?php if ($lunchDesc !== ''): ?>
				<div class="form-text"><?= h($lunchDesc) ?></div>
			<?php endif; ?>
		</div>
		<div class="col-md-4">
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
		$feeUser = $feeUser ?? $application?->user ?? null;
		for ($i = 1; $i <= 3; $i++):
			$title = trim((string)$competition->get('racing_pipe_' . $i . '_title'));
			if ($title === '') {
				continue;
			}
			$pipeShown = true;
			$qty = (int)($application?->get('racing_pipe_' . $i . '_qty') ?? 0);
			$unit = $feeUser !== null
				? \App\Utility\CompetitionFees::pipeUnitPrice($competition, $i, $feeUser)
				: 0.0;
			$imgUrl = \App\Utility\CompetitionPipeImage::publicUrl((string)$competition->get('racing_pipe_' . $i . '_image'));
			?>
			<div class="col-md-4">
				<label class="form-label"><?= h($title) ?></label>
				<?php if ($imgUrl !== ''): ?>
					<div class="mb-2"><img src="<?= h($imgUrl) ?>" alt="<?= h($title) ?>" class="img-fluid rounded" style="max-height:120px;"></div>
				<?php endif; ?>
				<?= $this->Form->control('racing_pipe_' . $i . '_qty', \App\Utility\LocaleNumberParser::formIntegerOptions(
					$qty,
					['label' => false, 'id' => 'racing-pipe-' . $i . '-qty']
				)) ?>
				<?php if ($unit > 0): ?>
					<div class="form-text"><?= h(__('Price per pipe: {0}', \App\Utility\CompetitionFees::format($unit, $competition))) ?></div>
				<?php endif; ?>
			</div>
		<?php endfor; ?>
	</div>
	<?php
	$entry = $feeUser !== null ? \App\Utility\CompetitionFees::entryFee($competition, $feeUser) : 0.0;
	if ($entry > 0):
	?>
		<div class="alert alert-info py-2">
			<?= h(__('Your entry fee: {0}', \App\Utility\CompetitionFees::format($entry, $competition))) ?>
			<?php if (\App\Utility\CompetitionFees::isNationalMember($feeUser)): ?>
				<span class="text-muted"> — <?= h(__('national association member rate')) ?></span>
			<?php else: ?>
				<span class="text-muted"> — <?= h(__('non-member rate')) ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
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
