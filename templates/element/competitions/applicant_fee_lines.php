<?php
/**
 * Applicant fee / hand-out lines (entry + qty × unit + total).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition $competition
 * @var \App\Model\Entity\CompetitionsUser $application
 * @var mixed $user
 */
$lines = \App\Utility\CompetitionFees::lineItems($competition, $application, $user);
?>
<ul class="list-unstyled mb-0 checkin-fee-lines">
	<?php foreach ($lines as $line):
		$isTotal = ($line['kind'] ?? '') === 'total';
		$isPipe = ($line['kind'] ?? '') === 'pipe';
		$isLunch = ($line['kind'] ?? '') === 'lunch';
		$qty = (int)($line['qty'] ?? 0);
		$unit = (float)($line['unit'] ?? 0);
		$amount = (float)($line['amount'] ?? 0);
		$amountFmt = \App\Utility\CompetitionFees::format($amount, $competition);
		?>
		<li class="checkin-fee-line<?= $isTotal ? ' checkin-fee-line-total' : '' ?>">
			<div class="checkin-fee-line-main d-flex justify-content-between align-items-start gap-2">
				<div class="checkin-fee-line-label">
					<strong class="checkin-fee-line-title"><?= h((string)$line['label']) ?></strong>
					<?php if (($isPipe || $isLunch) && $qty > 0): ?>
						<div class="checkin-fee-line-qty">
							<?= h(\App\Utility\LocaleNumberParser::format($qty, decimals: 0)) ?>
							×
							<?= h(\App\Utility\CompetitionFees::format($unit, $competition)) ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="checkin-fee-line-amount text-nowrap<?= $isTotal ? ' is-total' : '' ?>">
					<?= h($amountFmt !== '' ? $amountFmt : '—') ?>
				</div>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
