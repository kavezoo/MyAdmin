<?php
/**
 * Cash desk tables — payments grouped by check-in collector.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var list<array{collector_id: string, collector_name: string, rows: list<array{name: string, amount: float, paid_at: mixed}>, subtotal: float}> $paidCashGroups
 * @var float $paidCashTotal
 */
$competition = $competition ?? null;
$paidCashGroups = $paidCashGroups ?? [];
$paidCashTotal = (float)($paidCashTotal ?? 0);
if ($competition === null) {
	return;
}
?>
<?php if ($paidCashGroups === []): ?>
	<p class="mb-0 text-muted"><?= __('No payments recorded yet.') ?></p>
<?php else: ?>
	<?php foreach ($paidCashGroups as $group): ?>
		<div class="checkin-cash-box mb-3">
			<div class="checkin-cash-box__title">
				<?= h(__('Collected by: {0}', $group['collector_name'])) ?>
			</div>
			<table class="checkin-cash-table">
				<thead>
					<tr>
						<th scope="col"><?= __('Competitor') ?></th>
						<th scope="col" class="text-end"><?= __('Amount') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($group['rows'] as $row): ?>
						<tr>
							<td>
								<?= h((string)$row['name']) ?>
								<?php if (!empty($row['paid_at'])): ?>
									<div class="small text-muted"><?= h(\App\Utility\LocaleDateParser::format($row['paid_at'], 'datetime_short')) ?></div>
								<?php endif; ?>
							</td>
							<td class="text-end text-nowrap"><?= h(\App\Utility\CompetitionFees::format($row['amount'], $competition)) ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr class="checkin-cash-should-have">
						<th scope="row"><?= __('Should have in till') ?></th>
						<th class="text-end text-nowrap"><?= h(\App\Utility\CompetitionFees::format($group['subtotal'], $competition)) ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
	<?php endforeach; ?>

	<div class="checkin-cash-grand-total">
		<span><?= __('Grand total') ?></span>
		<strong class="text-nowrap"><?= h(\App\Utility\CompetitionFees::format($paidCashTotal, $competition)) ?></strong>
	</div>
<?php endif; ?>
