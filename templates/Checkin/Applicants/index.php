<?php
/**
 * Check-in applicants — mobile-first cards (phone desk).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition|null $competition
 * @var iterable<\App\Model\Entity\CompetitionsUser> $applicants
 * @var array<string, string> $competitionOptions
 * @var string $competitionId
 * @var bool $unapprovedOnly
 * @var bool $unpaidOnly
 * @var bool $allFeesPaid
 */
$this->Html->css(['pages/index', 'pages/checkin'], ['block' => true]);
$applicants = $applicants ?? [];
$competitionOptions = $competitionOptions ?? [];
$unapprovedOnly = !empty($unapprovedOnly);
$unpaidOnly = !empty($unpaidOnly);
$allFeesPaid = !empty($allFeesPaid);
$hasMultiple = count($competitionOptions) > 1;
$applicantsEmpty = !is_object($applicants) || (method_exists($applicants, 'count') && $applicants->count() === 0);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold mb-0"><i class="fa fa-ticket"></i> <?= __('Check-in') ?></h3>
					<div class="text-muted"><?= __('Hand out requested racing pipes and collect the total fee. Access is limited to the competition day (Competition datetime date).') ?></div>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?= $this->element('admin/index_pagination', ['leadingSep' => false]) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body">
				<?php if ($competitionOptions === []): ?>
					<p class="mb-0 text-muted"><?= __('No assigned competitions for today. Check-in is only available on the calendar day of Competition datetime.') ?></p>
				<?php else: ?>
					<?php if ($hasMultiple): ?>
						<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>" class="mb-3" id="checkin-competition-form">
							<label class="form-label fw-semibold" for="competition-id"><?= __('Competition') ?></label>
							<select name="competition_id" id="competition-id" class="form-select form-select-lg" onchange="this.form.submit()">
								<?php foreach ($competitionOptions as $id => $label): ?>
									<option value="<?= h((string)$id) ?>"<?= (string)$id === (string)$competitionId ? ' selected' : '' ?>><?= h($label) ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ($unapprovedOnly): ?>
								<input type="hidden" name="unapproved_only" value="1">
							<?php endif; ?>
							<?php if ($unpaidOnly): ?>
								<input type="hidden" name="unpaid_only" value="1">
							<?php endif; ?>
							<div class="form-text"><?= __('Several competitions share this day — choose which desk list to use.') ?></div>
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
					<?php else:
						$pricesMissing = (float)($competition->entry_fee_member ?? 0) <= 0
							&& (float)($competition->entry_fee_non_member ?? 0) <= 0
							&& (float)($competition->lunch_price ?? 0) <= 0
							&& (float)($competition->racing_pipe_1_price_member ?? 0) <= 0
							&& (float)($competition->racing_pipe_1_price_non_member ?? 0) <= 0
							&& (float)($competition->racing_pipe_2_price_member ?? 0) <= 0
							&& (float)($competition->racing_pipe_2_price_non_member ?? 0) <= 0
							&& (float)($competition->racing_pipe_3_price_member ?? 0) <= 0
							&& (float)($competition->racing_pipe_3_price_non_member ?? 0) <= 0;
						?>
						<?php if ($pricesMissing): ?>
							<div class="alert alert-warning">
								<?= __('Competition fees are not set (all prices are 0). Set entry fee and pipe prices on the competition form — then totals will calculate as quantity × unit price.') ?>
							</div>
						<?php endif; ?>

						<?php if ($allFeesPaid): ?>
							<div class="alert alert-success checkin-all-paid-alert" role="status">
								<i class="fa fa-check-circle" aria-hidden="true"></i>
								<?= __('Everyone has settled their entry fee.') ?>
							</div>
						<?php endif; ?>

						<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>" class="mb-3 checkin-filter-switches" id="checkin-filter-form">
							<input type="hidden" name="competition_id" value="<?= h((string)$competitionId) ?>">
							<input type="hidden" name="unapproved_only" value="0" id="checkin-unapproved-only-off"
								<?= $unapprovedOnly ? 'disabled' : '' ?>>
							<input type="hidden" name="unpaid_only" value="0" id="checkin-unpaid-only-off"
								<?= $unpaidOnly ? 'disabled' : '' ?>>
							<div class="d-flex flex-wrap gap-3 align-items-center">
								<div class="form-check form-switch form-switch-lg mb-0">
									<input type="checkbox"
										class="form-check-input"
										id="checkin-unapproved-only"
										name="unapproved_only"
										value="1"
										<?= $unapprovedOnly ? 'checked' : '' ?>
										onchange="document.getElementById('checkin-unapproved-only-off').disabled = this.checked; document.getElementById('checkin-unpaid-only-off').disabled = document.getElementById('checkin-unpaid-only').checked; this.form.submit();">
									<label class="form-check-label" for="checkin-unapproved-only"><?= __('Only unapproved') ?></label>
								</div>
								<div class="form-check form-switch form-switch-lg mb-0">
									<input type="checkbox"
										class="form-check-input"
										id="checkin-unpaid-only"
										name="unpaid_only"
										value="1"
										<?= $unpaidOnly ? 'checked' : '' ?>
										onchange="document.getElementById('checkin-unpaid-only-off').disabled = this.checked; document.getElementById('checkin-unapproved-only-off').disabled = document.getElementById('checkin-unapproved-only').checked; this.form.submit();">
									<label class="form-check-label" for="checkin-unpaid-only"><?= __('Show only unpaid competitors') ?></label>
								</div>
							</div>
							<div class="form-text"><?= __('Unapproved = awaiting team assignment. Switches can be combined.') ?></div>
						</form>

						<div class="mb-3 checkin-search-wrap">
							<label class="form-label fw-semibold" for="checkin-applicant-filter"><?= __('Search applicants') ?></label>
							<input type="search"
								id="checkin-applicant-filter"
								class="form-control form-control-lg"
								placeholder="<?= h(__('Type a name, email…')) ?>"
								autocomplete="off"
								autofocus>
						</div>

						<?php if ($applicantsEmpty): ?>
							<p class="mb-0 text-muted">
								<?php if ($unapprovedOnly || $unpaidOnly): ?>
									<?= __('No applicants match the selected filters.') ?>
								<?php else: ?>
									<?= __('No applicants for this competition.') ?>
								<?php endif; ?>
							</p>
						<?php else: ?>
							<div id="checkin-applicants-list" class="checkin-applicant-list">
								<?php foreach ($applicants as $app):
									$user = $app->user;
									$name = trim(((string)($user->last_name ?? '')) . ' ' . ((string)($user->first_name ?? '')));
									if ($name === '') {
										$name = (string)($user->email ?? $app->user_id);
									}
									$email = (string)($user->email ?? '');
									$lines = \App\Utility\CompetitionFees::lineItems($competition, $app, $user);
									$feeTotal = 0.0;
									foreach ($lines as $line) {
										if (($line['kind'] ?? '') === 'total') {
											$feeTotal = (float)$line['amount'];
											break;
										}
									}
									$haystack = strtolower($name . ' ' . $email);
									foreach ($lines as $line) {
										$haystack .= ' ' . strtolower((string)$line['label']);
									}
									$year = \App\Utility\MembershipFee::currentYear();
									if (\App\Utility\CompetitionFees::isNationalMember($user, $year)) {
										$haystack .= ' ' . strtolower((string)__('National member'));
									} else {
										$haystack .= ' ' . strtolower((string)__('Club member only'));
									}
									$isPaid = !empty($app->fee_paid_at);
									$status = (string)($app->status ?? '');
									$isPending = $status === \App\Utility\CompetitionApplication::STATUS_PENDING;
									$statusLabel = \App\Utility\CompetitionApplication::statusLabel($status);
									$haystack .= ' ' . strtolower($statusLabel);
									?>
									<article class="checkin-applicant-card<?= $isPaid ? ' is-paid' : ' is-unpaid' ?><?= $isPending ? ' is-unapproved' : '' ?>" data-filter-text="<?= h($haystack) ?>">
										<div class="p-3">
											<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
												<div>
													<h4 class="checkin-applicant-name"><?= h($name) ?></h4>
													<?php if ($email !== '' && strcasecmp($email, $name) !== 0): ?>
														<div class="small text-muted"><?= h($email) ?></div>
													<?php endif; ?>
													<div class="d-flex flex-wrap gap-2 mt-1 mb-1">
														<?php if ($isPending): ?>
															<span class="badge text-bg-warning text-dark"><?= h($statusLabel) ?></span>
														<?php else: ?>
															<span class="badge text-bg-info"><?= h($statusLabel) ?></span>
														<?php endif; ?>
														<?php if ($isPaid): ?>
															<span class="badge bg-success"><?= __('Paid') ?></span>
														<?php else: ?>
															<span class="badge text-bg-secondary"><?= __('Unpaid') ?></span>
														<?php endif; ?>
													</div>
													<?= $this->element('competitions/checkin_member_info', [
														'user' => $user,
														'countryId' => (int)($competition->country_id ?? $user->country_id ?? 0),
														'membershipYear' => \App\Utility\MembershipFee::currentYear(),
													]) ?>
													<div class="checkin-companions-line text-muted mt-1">
														<?= h(__('Companions: {0}', \App\Utility\LocaleNumberParser::format((int)($app->companion_count ?? 0), decimals: 0))) ?>
														·
														<?= h(__('Extra lunches: {0}', \App\Utility\LocaleNumberParser::format((int)($app->lunch_for_the_attendant ?? 0), decimals: 0))) ?>
													</div>
												</div>
											</div>

											<?= $this->element('competitions/applicant_fee_lines', [
												'competition' => $competition,
												'application' => $app,
												'user' => $user,
											]) ?>

											<?php if ($isPaid): ?>
												<div class="small text-muted mt-2">
													<?= __('Paid at') ?>:
													<?= h(\App\Utility\LocaleDateParser::format($app->fee_paid_at, 'datetime_short')) ?>
													<?php
													$collector = $app->fee_collector ?? null;
													$collectorName = '';
													if ($collector !== null) {
														$collectorName = trim(
															((string)($collector->last_name ?? '')) . ' ' . ((string)($collector->first_name ?? ''))
														);
														if ($collectorName === '') {
															$collectorName = (string)($collector->email ?? '');
														}
													}
													?>
													<?php if ($collectorName !== ''): ?>
														· <?= h(__('Recorded by {0}', $collectorName)) ?>
													<?php endif; ?>
												</div>
											<?php else:
												$formId = 'mark-paid-form-' . (int)$app->id;
												$feeTotalFmt = \App\Utility\CompetitionFees::format($feeTotal, $competition);
												$swalHtml =
													'<div class="checkin-swal-name">' . h($name) . '</div>'
													. '<div class="checkin-swal-amount">' . h($feeTotalFmt !== '' ? $feeTotalFmt : '—') . '</div>'
													. '<div class="checkin-swal-ask">' . h(__('Mark this competitor as paid?')) . '</div>';
												?>
												<div class="checkin-applicant-actions">
													<?= $this->Form->create(null, [
														'url' => ['action' => 'markPaid', $app->id, '?' => array_filter([
															'unapproved_only' => $unapprovedOnly ? '1' : null,
															'unpaid_only' => $unpaidOnly ? '1' : null,
														])],
														'id' => $formId,
													]) ?>
													<button type="button"
														class="btn btn-success btn-checkin-mark-paid"
														data-form-id="<?= h($formId) ?>"
														data-swal-title="<?= h(__('Mark paid')) ?>"
														data-swal-html="<?= h($swalHtml) ?>"
														data-swal-confirm="<?= h(__('Mark paid')) ?>">
														<i class="fa fa-check"></i> <?= h(__('Mark paid')) ?>
														— <?= h($feeTotalFmt) ?>
													</button>
													<?= $this->Form->end() ?>
												</div>
											<?php endif; ?>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
							<p id="checkin-filter-empty" class="text-muted mt-3 mb-0 d-none"><?= __('No applicants match your search.') ?></p>
						<?php endif; ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
<?php
$this->Html->scriptBlock(<<<'JS'
(function () {
	var input = document.getElementById('checkin-applicant-filter');
	var list = document.getElementById('checkin-applicants-list');
	var emptyMsg = document.getElementById('checkin-filter-empty');
	if (input && list) {
		var cards = list.querySelectorAll('.checkin-applicant-card[data-filter-text]');
		input.addEventListener('input', function () {
			var q = (input.value || '').trim().toLowerCase();
			var visible = 0;
			cards.forEach(function (card) {
				var text = card.getAttribute('data-filter-text') || '';
				var show = !q || text.indexOf(q) !== -1;
				card.classList.toggle('d-none', !show);
				if (show) {
					visible++;
				}
			});
			if (emptyMsg) {
				emptyMsg.classList.toggle('d-none', visible > 0 || cards.length === 0);
			}
		});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.btn-checkin-mark-paid');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var formId = btn.getAttribute('data-form-id') || '';
		var form = formId ? document.getElementById(formId) : null;
		if (!form) {
			return;
		}
		var App = window.MyAdmin || {};
		var opts = {
			icon: 'question',
			title: btn.getAttribute('data-swal-title') || 'Mark paid',
			confirmButtonText: btn.getAttribute('data-swal-confirm') || 'Mark paid',
			confirmButtonColor: '#198754',
			onConfirm: function () {
				form.submit();
			}
		};
		var swalHtml = btn.getAttribute('data-swal-html') || '';
		if (swalHtml) {
			opts.html = swalHtml;
		} else {
			opts.text = btn.getAttribute('data-swal-text') || '';
		}
		if (typeof App.confirmDelete === 'function') {
			App.confirmDelete(opts);
			return;
		}
		if (window.Swal && typeof window.Swal.fire === 'function') {
			var fireOpts = {
				icon: opts.icon,
				title: opts.title,
				showCancelButton: true,
				focusCancel: true,
				confirmButtonText: opts.confirmButtonText,
				cancelButtonText: (App.messages && App.messages.cancelButton) || 'Cancel',
				confirmButtonColor: opts.confirmButtonColor,
				cancelButtonColor: '#6c757d',
				reverseButtons: true
			};
			if (opts.html) {
				fireOpts.html = opts.html;
			} else {
				fireOpts.text = opts.text || '';
			}
			window.Swal.fire(fireOpts).then(function (result) {
				if (result.isConfirmed) {
					form.submit();
				}
			});
		}
	});
})();
JS
, ['block' => true]);
?>
