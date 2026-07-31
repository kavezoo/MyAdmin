<div class="modal fade custom-modal" id="modalRecordView" tabindex="-1" aria-labelledby="modalRecordViewLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content shadow-lg">
			<div class="modal-header">
				<h5 class="modal-title" id="modalRecordViewLabel"><?= __('Record details') ?></h5>
				<button type="button" class="btn btn-modal-close" data-bs-dismiss="modal" aria-label="<?= h(__('Close')) ?>">
					<i class="fa fa-times" aria-hidden="true"></i>
				</button>
			</div>
			<div class="modal-body">
				<div id="modalRecordViewLoading" class="text-muted py-3"><?= __('Loading…') ?></div>
				<div id="modalRecordViewError" class="alert alert-danger d-none mb-0" role="alert"></div>
				<dl id="modalRecordViewFields" class="record-view-fields d-none mb-0"></dl>
			</div>
			<div class="modal-footer justify-content-between">
				<div class="d-flex gap-2">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="btn-record-close">
						<span class="btn-label"><i class="fa fa-times"></i></span><?= __('Close') ?>
					</button>
					<button type="button" class="btn btn-primary" id="btn-record-edit">
						<span class="btn-label"><i class="fa fa-pencil"></i></span><?= __('Edit') ?>
					</button>
					<button type="button" class="btn btn-info" id="btn-record-view">
						<span class="btn-label"><i class="fa fa-eye"></i></span><?= __('View details') ?>
					</button>
				</div>
				<button type="button" class="btn btn-danger" id="btn-record-delete">
					<span class="btn-label"><i class="fa fa-trash"></i></span><?= __('Delete') ?>
				</button>
			</div>
		</div>
	</div>
</div>
