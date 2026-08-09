<?php
/**
 * Check-in cash desk — paid competitors grouped by who collected the money.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition|null $competition
 * @var array<string, string> $competitionOptions
 * @var string $competitionId
 * @var list<array{collector_id: string, collector_name: string, rows: list<array{name: string, amount: float, paid_at: mixed}>, subtotal: float}> $paidCashGroups
 * @var float $paidCashTotal
 */
$this->Html->css(['pages/index', 'pages/checkin'], ['block' => true]);
$competitionOptions = $competitionOptions ?? [];
$paidCashGroups = $paidCashGroups ?? [];
$paidCashTotal = (float)($paidCashTotal ?? 0);
$hasMultiple = count($competitionOptions) > 1;
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold mb-0"><i class="fa fa-cash-register"></i> <?= __('Cash desk') ?></h3>
					<div class="text-muted"><?= __('Payments grouped by check-in collector — how much each person should have in the till.') ?></div>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?php if ($competitionOptions === []): ?>
					<p class="mb-0 text-muted"><?= __('No assigned competitions for today. Check-in is only available on the calendar day of Competition datetime.') ?></p>
				<?php else: ?>
					<?php if ($hasMultiple): ?>
						<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>" class="mb-3">
							<label class="form-label fw-semibold" for="competition-id"><?= __('Competition') ?></label>
							<select name="competition_id" id="competition-id" class="form-select form-select-lg" onchange="this.form.submit()">
								<?php foreach ($competitionOptions as $id => $label): ?>
									<option value="<?= h((string)$id) ?>"<?= (string)$id === (string)$competitionId ? ' selected' : '' ?>><?= h($label) ?></option>
								<?php endforeach; ?>
							</select>
						</form>
					<?php elseif ($competition !== null): ?>
						<p class="mb-3">
							<span class="fw-semibold"><?= h((string)$competition->name) ?></span>
							<?php if ($competition->competition_datetime): ?>
								<span class="text-muted"> — <?= h(\App\Utility\LocaleDateParser::format($competition->competition_datetime, 'datetime_short')) ?></span>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<?php if ($competition === null): ?>
						<p class="mb-0 text-muted"><?= __('You are not allowed to access this competition.') ?></p>
					<?php else: ?>
						<?= $this->element('competitions/cash_desk_groups', [
							'competition' => $competition,
							'paidCashGroups' => $paidCashGroups,
							'paidCashTotal' => $paidCashTotal,
						]) ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
