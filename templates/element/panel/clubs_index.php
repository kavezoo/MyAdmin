<?php
/**
 * Panel club directory index (Clubpresident / Member).
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Club> $clubs
 * @var array<int, string> $countryOptions
 * @var int $countryId
 * @var string $countryLabel
 * @var int $homeCountryId
 * @var int $myClubId
 * @var int|null $lastVisitedId
 */
$this->Html->css(['pages/index', 'pages/club_logo'], ['block' => true]);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);

$countryOptions = $countryOptions ?? [];
$countryId = (int)($countryId ?? 0);
$countryLabel = (string)($countryLabel ?? '');
$myClubId = (int)($myClubId ?? 0);
$homeCountryId = (int)($homeCountryId ?? 0);

$filterQuery = $this->request->getQueryParams();
unset($filterQuery['country_id']);
$filterQuery['page'] = '1';
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-sitemap"></i> <?= __('Clubs') ?></h3>
					<?php if ($countryLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing clubs in {0}', $countryLabel)) ?></div>
					<?php endif; ?>
					<?= __('Browse club profiles. Default filter is your country.') ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>" class="mb-0" id="club-browser-country-filter">
						<?php foreach ($filterQuery as $name => $value): ?>
							<?php if (!is_scalar($value) || (string)$name === \App\Utility\AdminSearch::queryParam()) {
								continue;
							} ?>
							<input type="hidden" name="<?= h((string)$name) ?>" value="<?= h((string)$value) ?>">
						<?php endforeach; ?>
						<select name="country_id"
							id="club-browser-country-id"
							class="form-select form-select-sm"
							style="min-width: 14rem;"
							onchange="this.form.submit()"
							aria-label="<?= h(__('Country')) ?>">
							<?php foreach ($countryOptions as $cid => $clabel): ?>
								<option value="<?= (int)$cid ?>"<?= $countryId === (int)$cid ? ' selected' : '' ?>><?= h($clabel) ?></option>
							<?php endforeach; ?>
						</select>
					</form>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<?= $this->element('admin/table_search') ?>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table">
					<thead>
						<tr>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="string short_name"><?= $this->Paginator->sort('short_name', __('Short name')) ?></th>
							<th scope="col" class="string city"><?= $this->Paginator->sort('Cities.name', __('City')) ?></th>
							<th scope="col" class="number count"><?= $this->Paginator->sort('user_count', __('Members')) ?></th>
							<th scope="col" class="number count"><?= $this->Paginator->sort('competition_count', __('Competitions')) ?></th>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (!is_object($clubs) || $clubs->count() === 0): ?>
							<tr>
								<td colspan="6" class="text-center text-muted py-4"><?= __('No records found.') ?></td>
							</tr>
						<?php else: ?>
							<?php foreach ($clubs as $row): ?>
								<?php
								$isMine = $myClubId > 0 && (int)$row->id === $myClubId;
								$isLast = isset($lastVisitedId) && (int)$lastVisitedId === (int)$row->id;
								$rowClass = trim(($isLast ? 'last-visited' : '') . ($isMine ? ' table-success' : ''));
								?>
								<tr id="record-<?= (int)$row->id ?>" data-id="<?= (int)$row->id ?>"<?= $rowClass !== '' ? ' class="' . h($rowClass) . '"' : '' ?>>
									<td class="string name">
										<div class="club-index-name-with-logo">
											<?php
											$logoStored = $row->get('logo');
											$clubLogoUrl = \App\Utility\ClubLogo::publicUrlFor(
												(int)$row->id,
												is_string($logoStored) ? $logoStored : null
											);
											?>
											<?php if ($clubLogoUrl !== ''): ?>
												<img src="<?= h($clubLogoUrl) ?>" alt="" class="club-logo-preview--sm" width="36" height="36">
											<?php else: ?>
												<span class="club-logo-placeholder--sm d-inline-flex align-items-center justify-content-center text-secondary" aria-hidden="true">
													<i class="fa fa-shield"></i>
												</span>
											<?php endif; ?>
											<span><?= h((string)$row->name) ?></span>
											<?php if ($isMine): ?>
												<span class="badge text-bg-primary ms-1"><?= __('My club') ?></span>
											<?php endif; ?>
										</div>
									</td>
									<td class="string short_name"><?= h((string)$row->short_name) ?></td>
									<td class="string city"><?= h((string)($row->city->name ?? '')) ?></td>
									<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount((int)$row->user_count, decimals: 0)) ?></td>
									<td class="number count text-end"><?= h(\App\Utility\LocaleNumberParser::formatCount((int)($row->competition_count ?? 0), decimals: 0)) ?></td>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $row->id],
											[
												'escape' => false,
												'class' => 'btn btn-sm btn-outline-secondary',
												'title' => h(__('View details')),
											]
										) ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<div class="card-footer">
				<?= $this->element('admin/index_footer') ?>
			</div>
		</div>
	</div>
</div>
