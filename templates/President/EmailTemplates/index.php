<?php
/**
 * Email templates index (President) — standard Admin CRUD list.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\EmailTemplate> $emailTemplates
 * @var int $filterLanguageId
 * @var string $filterLanguageLabel
 * @var array<int, string> $languageOptions
 * @var array<string, string> $slugOptions
 * @var int|null $lastVisitedId
 */
$filterLanguageId = (int)($filterLanguageId ?? 0);
$filterLanguageLabel = (string)($filterLanguageLabel ?? '');
$languageOptions = $languageOptions ?? [];
$slugOptions = $slugOptions ?? [];

$this->Html->css([
	'/plugins/select2-4.1.0/css/select2.min',
	'/plugins/select2-bootstrap-5-theme-1.3.0/select2-bootstrap-5-theme.min',
	'pages/index',
], ['block' => true]);
$this->Html->script('/plugins/select2-4.1.0/js/select2.full.min', ['block' => 'scriptBottom']);

$filterQuery = $this->request->getQueryParams();
unset($filterQuery['language_id']);
$filterQuery['page'] = '1';

/** Row double-click: 'modal' | 'edit' | 'none' */
$rowDoubleClickAction = 'modal';

$showIdColumn = true;
$showEnabledColumn = true;
$showVisibleColumn = true;
$showCreatedColumn = true;
$showModifiedColumn = true;

$showTimestampColumn = $showCreatedColumn || $showModifiedColumn;
$indexColspan = 5; // language, slug, name, subject, actions
if ($showIdColumn) {
	$indexColspan++;
}
if ($showEnabledColumn) {
	$indexColspan++;
}
if ($showVisibleColumn) {
	$indexColspan++;
}
if ($showTimestampColumn) {
	$indexColspan++;
}

$tooltipDetails = '<b>' . __('View details') . '</b><br>' . __('View the selected record details.');
$tooltipEdit = '<b>' . __('Edit') . '</b><br>' . __('Edit the selected record.');
$tooltipDelete = '<b>' . __('Delete') . '</b><br>' . __('Permanently delete the selected record.');

$rowDoubleClickHints = [
	'modal' => __('Double-click a row to view the record details.'),
	'edit' => __('Double-click a row to edit the record.'),
	'none' => '',
];
$rowDoubleClickHint = $rowDoubleClickHints[$rowDoubleClickAction] ?? $rowDoubleClickHints['modal'];

$config = [
	'rowDoubleClickAction' => $rowDoubleClickAction,
	'recordGetUrl' => $this->Url->build(['action' => 'recordGet']),
	'editUrl' => $this->Url->build(['action' => 'edit']),
	'viewUrl' => $this->Url->build(['action' => 'view']),
	'deleteUrl' => $this->Url->build(['action' => 'delete']),
	'recordHtmlFields' => ['body_html'],
	'recordMultilineFields' => ['body_text'],
	'recordFieldLabels' => [
		'id' => __('ID'),
		'country' => __('Country'),
		'language' => __('Language'),
		'slug' => __('Template'),
		'name' => __('Name'),
		'subject' => __('Subject'),
		'body_html' => __('HTML body'),
		'body_text' => __('Text body'),
		'enabled' => __('Enabled'),
		'visible' => __('Visible'),
		'pos' => __('Position'),
		'created' => __('Created'),
		'modified' => __('Modified'),
	],
	'entityFieldLabels' => [
		'email_template' => [
			'id' => __('ID'),
			'country' => __('Country'),
			'language' => __('Language'),
			'slug' => __('Template'),
			'name' => __('Name'),
			'subject' => __('Subject'),
			'body_html' => __('HTML body'),
			'body_text' => __('Text body'),
			'enabled' => __('Enabled'),
			'visible' => __('Visible'),
			'pos' => __('Position'),
			'created' => __('Created'),
			'modified' => __('Modified'),
		],
	],
];
$this->Html->scriptBlock(
	'window.MyAdmin = window.MyAdmin || {}; window.MyAdmin.config = Object.assign(window.MyAdmin.config || {}, '
	. json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
	. ');',
	['block' => 'script']
);
$this->Html->script(['pages/index'], ['block' => 'scriptBottom']);
?>
<div class="row mt-3">
	<div class="col-12 p-2 pt-0">
		<div class="card mb-3 border border-2 shadow">
			<div class="card-header">
				<div class="float-left">
					<h3 class="fw-bold"><i class="fa fa-envelope"></i> <?= __('Email templates') ?></h3>
					<?php if ($filterLanguageLabel !== ''): ?>
						<div class="text-muted"><?= h(__('Showing templates for {0}', $filterLanguageLabel)) ?></div>
					<?php elseif ($filterLanguageId < 1): ?>
						<div class="text-muted"><?= h(__('Showing templates for all languages')) ?></div>
					<?php endif; ?>
					<?php if ($rowDoubleClickHint !== ''): ?>
						<?= h($rowDoubleClickHint) ?>
					<?php endif; ?>
				</div>
				<div class="float-right d-flex align-items-center gap-2 flex-wrap justify-content-end">
					<form method="get" action="<?= h($this->Url->build(['action' => 'index'])) ?>"
						class="email-templates-language-filter mb-0"
						id="email-templates-language-filter">
						<?php foreach ($filterQuery as $name => $value): ?>
							<?php if (!is_scalar($value) || (string)$name === \App\Utility\AdminSearch::queryParam()) {
								continue;
							} ?>
							<input type="hidden" name="<?= h((string)$name) ?>" value="<?= h((string)$value) ?>">
						<?php endforeach; ?>
						<select name="language_id"
							id="email-templates-language-id"
							class="form-select form-select-sm"
							style="min-width: 12rem;"
							onchange="this.form.submit()"
							aria-label="<?= h(__('Language')) ?>">
							<option value="0"<?= $filterLanguageId < 1 ? ' selected' : '' ?>><?= h(__('All languages')) ?></option>
							<?php foreach ($languageOptions as $lid => $llabel): ?>
								<option value="<?= (int)$lid ?>"<?= $filterLanguageId === (int)$lid ? ' selected' : '' ?>><?= h($llabel) ?></option>
							<?php endforeach; ?>
						</select>
					</form>
					<span class="index-header-sep" aria-hidden="true">|</span>
					<?php
					$indexSearch = (string)($this->get('indexSearch') ?? '');
					$qKey = \App\Utility\AdminSearch::queryParam();
					$tooltipSearch = h('<b>' . __('Start search') . '</b><br>' . __('Search in the text fields of this list.'));
					$tooltipClear = h('<b>' . __('Clear search') . '</b><br>' . __('Clear the saved search and return to the last visited record.'));
					$hasSearch = $indexSearch !== '';
					?>
					<form method="get" class="table-search" role="search">
						<input type="hidden" name="language_id" value="<?= (int)$filterLanguageId ?>">
						<input type="search"
							class="form-control form-control-sm table-search-input"
							id="table-search-input"
							name="<?= h($qKey) ?>"
							value="<?= h($indexSearch) ?>"
							placeholder="<?= h(__('Search...')) ?>"
							autocomplete="off">
						<button type="submit"
							class="btn btn-sm btn-outline-secondary table-search-btn"
							data-bs-toggle="tooltip"
							data-bs-placement="bottom"
							data-bs-html="true"
							title="<?= $tooltipSearch ?>">
							<i class="fa fa-search" aria-hidden="true"></i>
							<span class="visually-hidden"><?= h(__('Start search')) ?></span>
						</button>
						<?php if ($hasSearch): ?>
							<a href="<?= h($this->Url->build(['?' => ['clear_search' => '1', 'language_id' => (string)$filterLanguageId]])) ?>"
								class="btn btn-sm btn-outline-secondary table-search-btn table-search-clear"
								role="button"
								data-bs-toggle="tooltip"
								data-bs-placement="bottom"
								data-bs-html="true"
								title="<?= $tooltipClear ?>">
								<i class="fa fa-times" aria-hidden="true"></i>
								<span class="visually-hidden"><?= h(__('Clear search')) ?></span>
							</a>
						<?php endif; ?>
					</form>
					<?= $this->element('admin/index_pagination', ['leadingSep' => true]) ?>
					<?= $this->Html->link(
						'<span class="btn-label"><i class="fa fa-plus"></i></span>' . __('New'),
						['action' => 'add'],
						['escape' => false, 'class' => 'btn btn-primary']
					) ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<div class="card-body p-2">
				<table class="table table-responsive-xl table-bordered table-hover table-striped mb-0 index-data-table" id="email-templates-index-table">
					<thead>
						<tr>
							<?php if ($showIdColumn): ?>
								<th scope="col" class="number id"><?= $this->Paginator->sort('id', '#') ?></th>
							<?php endif; ?>
							<th scope="col" class="string language"><?= $this->Paginator->sort('Languages.code', __('Language')) ?></th>
							<th scope="col" class="string slug"><?= $this->Paginator->sort('slug', __('Template')) ?></th>
							<th scope="col" class="string name"><?= $this->Paginator->sort('name', __('Name')) ?></th>
							<th scope="col" class="string subject"><?= $this->Paginator->sort('subject', __('Subject')) ?></th>
							<?php if ($showEnabledColumn): ?>
								<th scope="col" class="boolean enabled"><?= $this->Paginator->sort('enabled', __('Enabled')) ?></th>
							<?php endif; ?>
							<?php if ($showVisibleColumn): ?>
								<th scope="col" class="boolean visible"><?= $this->Paginator->sort('visible', __('Visible')) ?></th>
							<?php endif; ?>
							<?php if ($showTimestampColumn): ?>
								<th scope="col" class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
									<?php if ($showCreatedColumn): ?>
										<?= $this->Paginator->sort('created', __('Created')) ?>
									<?php endif; ?>
									<?php if ($showModifiedColumn): ?>
										<?= $this->Paginator->sort('modified', __('Modified')) ?>
									<?php endif; ?>
								</th>
							<?php endif; ?>
							<th scope="col" class="actions"><?= __('Actions') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ($emailTemplates->count() === 0): ?>
							<tr>
								<td colspan="<?= (int)$indexColspan ?>" class="text-center text-muted py-4"><?= __('No records found.') ?></td>
							</tr>
						<?php else: ?>
							<?php foreach ($emailTemplates as $row): ?>
								<?php
								$langCode = $row->language->code ?? '';
								$langLabel = $langCode !== ''
									? \App\Utility\AdminLanguage::loginLabel((string)$langCode)
									: \App\Utility\AdminLanguage::labelById((int)$row->language_id);
								$slugLabel = $slugOptions[(string)$row->slug] ?? (string)$row->slug;
								$isLastVisited = isset($lastVisitedId) && (int)$lastVisitedId === (int)$row->id;
								?>
								<tr id="record-<?= (int)$row->id ?>"
									data-id="<?= (int)$row->id ?>"
									data-can-delete="1"<?= $isLastVisited ? ' class="last-visited"' : '' ?>>
									<?php if ($showIdColumn): ?>
										<td class="number id"><?= h((string)$row->id) ?></td>
									<?php endif; ?>
									<td class="string language"><?= h($langLabel) ?></td>
									<td class="string slug"><?= h($slugLabel) ?></td>
									<td class="string name"><?= h((string)$row->name) ?></td>
									<td class="string subject"><?= h((string)$row->subject) ?></td>
									<?php if ($showEnabledColumn): ?>
										<td class="boolean enabled">
											<?= !empty($row->enabled)
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showVisibleColumn): ?>
										<td class="boolean visible">
											<?= !empty($row->visible)
												? '<i class="fa fa-check text-success"></i>'
												: '<i class="fa fa-times text-danger"></i>' ?>
										</td>
									<?php endif; ?>
									<?php if ($showTimestampColumn): ?>
										<td class="datetime<?= $showCreatedColumn ? ' created' : '' ?><?= $showModifiedColumn ? ' modified' : '' ?>">
											<?php if ($showCreatedColumn): ?>
												<?= $row->created ? h(\App\Utility\LocaleDateParser::format($row->created, 'datetime_short')) : '' ?>
											<?php endif; ?>
											<?php if ($showCreatedColumn && $showModifiedColumn && $row->modified): ?>
												<br>
											<?php endif; ?>
											<?php if ($showModifiedColumn): ?>
												<?= $row->modified ? h(\App\Utility\LocaleDateParser::format($row->modified, 'datetime_short')) : '' ?>
											<?php endif; ?>
										</td>
									<?php endif; ?>
									<td class="actions">
										<?= $this->Html->link(
											'<i class="fa fa-eye"></i>',
											['action' => 'view', $row->id],
											[
												'escape' => false,
												'role' => 'button',
												'class' => 'btn btn-outline-info',
												'data-bs-toggle' => 'tooltip',
												'data-bs-placement' => 'top',
												'data-bs-html' => 'true',
												'title' => $tooltipDetails,
											]
										) ?>
										<?= $this->Html->link(
											'<i class="fa fa-pencil"></i>',
											['action' => 'edit', $row->id],
											[
												'escape' => false,
												'role' => 'button',
												'class' => 'btn btn-outline-primary',
												'data-bs-toggle' => 'tooltip',
												'data-bs-placement' => 'top',
												'data-bs-html' => 'true',
												'title' => $tooltipEdit,
											]
										) ?>
										<a role="button" href="#" class="btn btn-outline-danger btn-row-delete" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="<?= h($tooltipDelete) ?>" data-id="<?= (int)$row->id ?>">
											<i class="fa fa-trash"></i>
										</a>
										<?= $this->Form->create(null, [
											'url' => ['action' => 'delete', $row->id],
											'id' => 'delete-form-' . $row->id,
											'class' => 'd-none js-row-delete-form',
										]) ?>
										<?= $this->Form->end() ?>
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
<?= $this->element('admin/modal_record_view') ?>
<?php
$this->Html->scriptBlock(<<<JS
(function ($) {
	var \$sel = $('#email-templates-language-filter select');
	\$sel.on('change', function () {
		$(this).closest('form').submit();
	});
	if ($.fn.select2) {
		\$sel.select2({
			theme: 'bootstrap-5',
			width: 'style',
			minimumResultsForSearch: 8
		});
	}
})(jQuery);
JS, ['block' => 'scriptBottom']);
?>
