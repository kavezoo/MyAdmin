<?php
/**
 * Judge applicants — record result times.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Competition|null $competition
 * @var iterable<\App\Model\Entity\CompetitionsUser> $applicants
 * @var array<string, string> $competitionOptions
 * @var string $competitionId
 * @var string $competitionToken
 */
$this->Html->css(['pages/index'], ['block' => true]);
$competitionOptions = $competitionOptions ?? [];
$competitionToken = (string)($competitionToken ?? '');
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold mb-0">
						<i class="fa fa-gavel"></i>
						<?= $competition !== null ? h((string)$competition->name) : h(__('Judge')) ?>
					</h3>
					<div class="text-muted"><?= __('Record each competitor’s achieved time. Staff access is limited to the competition day.') ?></div>
					<?php if ($competitionToken !== ''): ?>
						<div class="small text-muted mt-1">
							<?= __('Competition API token (QR):') ?>
							<code class="user-select-all"><?= h($competitionToken) ?></code>
						</div>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<?php if ($competitionOptions !== []): ?>
						<form method="get" class="d-flex align-items-center gap-2 mb-0">
							<label class="mb-0 text-nowrap" for="competition-id"><?= __('Competition:') ?></label>
							<select name="competition_id" id="competition-id" class="form-select form-select-sm" onchange="this.form.submit()">
								<?php foreach ($competitionOptions as $id => $label): ?>
									<option value="<?= h((string)$id) ?>"<?= (string)$id === (string)$competitionId ? ' selected' : '' ?>><?= h($label) ?></option>
								<?php endforeach; ?>
							</select>
						</form>
					<?php endif; ?>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-0">
				<?php if ($competition === null): ?>
					<p class="mb-0 text-muted p-3"><?= __('No assigned competitions for today.') ?></p>
				<?php else: ?>
					<table class="table table-bordered table-hover mb-0 index-data-table">
						<thead>
							<tr>
								<th class="string"><?= __('Competitor') ?></th>
								<th class="string"><?= __('Result time') ?></th>
								<th class="actions"><?= __('Actions') ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$empty = true;
							foreach ($applicants as $app):
								$empty = false;
								$user = $app->user;
								$name = trim(((string)($user->last_name ?? '')) . ' ' . ((string)($user->first_name ?? '')));
								if ($name === '') {
									$name = (string)($user->email ?? $app->user_id);
								}
								$current = $app->result_time !== null && $app->result_time !== ''
									? \App\Utility\CompetitionResultTime::format((float)$app->result_time)
									: '';
								$userToken = \App\Utility\UuidObfuscator::encode((string)$app->user_id);
								?>
								<tr>
									<td class="string">
										<?= h($name) ?>
										<div class="small text-muted font-monospace text-break" title="<?= h(__('QR user token (obfuscated)')) ?>">
											<?= h($userToken) ?>
										</div>
									</td>
									<td class="string">
										<?= $current !== '' ? h($current) : '—' ?>
									</td>
									<td class="actions">
										<?= $this->Form->create(null, [
											'url' => ['action' => 'saveResult', $app->id],
											'class' => 'd-flex flex-wrap align-items-center gap-2',
										]) ?>
											<?= $this->Form->control('time', [
												'label' => false,
												'type' => 'text',
												'value' => $current,
												'placeholder' => 'mm:ss.SSS',
												'class' => 'form-control form-control-sm',
												'templates' => ['inputContainer' => '{{content}}'],
											]) ?>
											<button type="submit" class="btn btn-sm btn-primary">
												<i class="fa fa-save"></i> <?= __('Save') ?>
											</button>
										<?= $this->Form->end() ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ($empty): ?>
								<tr>
									<td colspan="3" class="text-center text-muted py-4"><?= __('No applicants for this competition.') ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
