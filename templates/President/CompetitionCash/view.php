<?php
/**
 * President — cash desk for selected competition (read-only till overview).
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
$hasOptions = $competitionOptions !== [];
$viewBase = $this->Url->build(['action' => 'view']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold mb-0"><i class="fa fa-cash-register"></i> <?= __('Cash desk') ?></h3>
					<div class="text-muted"><?= __('Payments grouped by check-in collector — how much each person should have in the till.') ?></div>
				</div>
				<div class="float-right">
					<a href="<?= h($this->Url->build(['action' => 'index'])) ?>" class="btn btn-sm btn-outline-secondary">
						<i class="fa fa-list"></i> <?= __('All competitions') ?>
					</a>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?php if (!$hasOptions): ?>
					<p class="mb-0 text-muted"><?= __('No competitions yet.') ?></p>
				<?php else: ?>
					<div class="mb-3">
						<label class="form-label fw-semibold" for="competition-id"><?= __('Competition') ?></label>
						<select
							id="competition-id"
							class="form-select form-select-lg"
							onchange="if(this.value){window.location.href=<?= json_encode($viewBase . '/') ?> + encodeURIComponent(this.value);}"
						>
							<?php foreach ($competitionOptions as $id => $label): ?>
								<option value="<?= h((string)$id) ?>"<?= (string)$id === (string)$competitionId ? ' selected' : '' ?>><?= h($label) ?></option>
							<?php endforeach; ?>
						</select>
						<div class="form-text"><?= __('Select a competition to see the check-in cash desk summary.') ?></div>
					</div>

					<?php if ($competition === null): ?>
						<p class="mb-0 text-muted"><?= __('Select a competition.') ?></p>
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
