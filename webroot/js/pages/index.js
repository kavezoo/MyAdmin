/**
 * Index / list page behaviour (record modal, linked modal, delete, last-visited).
 * cakephp-template: page-js:index
 *
 * Expected config (set before this file loads):
 *   MyAdmin.config.rowDoubleClickAction  // 'modal' | 'edit' | 'none'
 *   MyAdmin.config.recordGetUrl
 *   MyAdmin.config.categoryGetUrl
 *   MyAdmin.config.editUrl
 *   MyAdmin.config.viewUrl
 *   MyAdmin.config.deleteUrl (optional base for delete)
 *   MyAdmin.config.recordFieldLabels
 *   MyAdmin.config.categoryFieldLabels
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};
	var msg = App.messages || {};
	var cfg = App.config || {};

	var rowDoubleClickAction = String(cfg.rowDoubleClickAction || 'modal').toLowerCase();
	var recordGetUrl = cfg.recordGetUrl || '';
	var categoryGetUrl = cfg.categoryGetUrl || '';
	var editUrl = cfg.editUrl || '';
	var viewUrl = cfg.viewUrl || '';
	var parentEditUrl = cfg.parentEditUrl || '/admin/parents/edit';
	var parentViewUrl = cfg.parentViewUrl || '/admin/parents/view';

	$(function () {
		if (!$('.index-data-table').length) {
			return;
		}

		var $modalRecordView = $('#modalRecordView');
		var $modalRecordViewLabel = $('#modalRecordViewLabel');
		var $modalRecordViewLoading = $('#modalRecordViewLoading');
		var $modalRecordViewError = $('#modalRecordViewError');
		var $modalRecordViewFields = $('#modalRecordViewFields');
		var currentRecordId = null;
		var $pendingLastVisitedRow = null;

		var markLastVisitedRow = function ($row) {
			if (!$row || !$row.length) {
				return;
			}
			$('.index-data-table tbody tr.last-visited').removeClass('last-visited');
			$row.addClass('last-visited');
		};

		var recordFieldLabels = cfg.recordFieldLabels || {
			id: 'ID',
			parent: 'Parent',
			name: 'Name',
			szam: 'Number',
			netto: 'Net',
			datum: 'Date',
			ido: 'Time',
			datumido: 'Date and time',
			logikai: 'Boolean',
			pos: 'Position',
			visible: 'Visible',
			city_count: 'Cities',
			cities: 'City list',
			created: 'Created',
			modified: 'Modified'
		};

		var formatRecordValue = function (key, value) {
			if (key === 'logikai' || key === 'visible' || key === 'valid') {
				return value
					? '<i class="fa fa-check text-success"></i> <span class="text-dark">' + (msg.yes || 'Yes') + '</span>'
					: '<i class="fa fa-times text-danger"></i> <span class="text-dark">' + (msg.no || 'No') + '</span>';
			}
			if (value == null || value === '') {
				return '—';
			}
			return $('<div>').text(String(value)).html();
		};

		var renderRecordFields = function (record) {
			var html = '';
			Object.keys(recordFieldLabels).forEach(function (key) {
				if (!Object.prototype.hasOwnProperty.call(record, key)) {
					return;
				}
				html += '<div class="record-view-row">' +
					'<dt>' + recordFieldLabels[key] + '</dt>' +
					'<dd>' + formatRecordValue(key, record[key]) + '</dd>' +
					'</div>';
			});
			$modalRecordViewFields.html(html);
		};

		var entityUrl = function (base, id) {
			if (!base) {
				return '#';
			}
			return base.replace(/\/$/, '') + '/' + encodeURIComponent(id);
		};

		var openRecordModal = function (recordId, $row) {
			currentRecordId = recordId;
			$pendingLastVisitedRow = ($row && $row.length) ? $row : null;
			$modalRecordViewLabel.text((msg.recordDetails || 'Record details') + ' #' + recordId);
			$modalRecordViewLoading.removeClass('d-none');
			$modalRecordViewError.addClass('d-none').text('');
			$modalRecordViewFields.addClass('d-none').empty();

			var modal = bootstrap.Modal.getOrCreateInstance($modalRecordView[0]);
			modal.show();

			$.ajax({
				url: entityUrl(recordGetUrl, recordId),
				method: 'GET',
				dataType: 'json'
			}).done(function (res) {
				if (!res || res.success !== true || !res.record) {
					$modalRecordViewError
						.removeClass('d-none')
						.text((res && res.message) ? res.message : (msg.recordLoadFailed || 'Failed to load the record.'));
					return;
				}
				renderRecordFields(res.record);
				$modalRecordViewFields.removeClass('d-none');
			}).fail(function (xhr) {
				var message = msg.recordLoadFailed || 'Failed to load the record.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				} else if (!xhr || xhr.status === 0) {
					message = msg.noServerResponse || 'No response from the server.';
				}
				$modalRecordViewError.removeClass('d-none').text(message);
			}).always(function () {
				$modalRecordViewLoading.addClass('d-none');
			});
		};

		var triggerDeleteForm = function (recordId) {
			var $form = $('#delete-form-' + recordId);
			if ($form.length) {
				$form.trigger('submit');
				return;
			}
			Swal.fire({
				icon: 'error',
				title: msg.deleteTitle || 'Delete',
				text: (msg.deleteFormNotFound || 'Delete form not found. ID: {0}').replace('{0}', recordId)
			});
		};

		$('#btn-record-view').on('click', function () {
			if (!currentRecordId) {
				return;
			}
			window.location.href = entityUrl(viewUrl, currentRecordId);
		});

		$('#btn-record-edit').on('click', function () {
			if (!currentRecordId) {
				return;
			}
			window.location.href = entityUrl(editUrl, currentRecordId);
		});

		$('#btn-record-delete').on('click', function () {
			if (!currentRecordId) {
				return;
			}
			App.confirmDelete({
				onConfirm: function () {
					triggerDeleteForm(currentRecordId);
				}
			});
		});

		$('.index-data-table tbody').on('click', 'a.btn-row-delete', function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $btn = $(this);
			var tip = bootstrap.Tooltip.getInstance($btn[0]);
			if (tip) {
				tip.hide();
			}

			var recordId = $btn.attr('data-id') || $btn.closest('tr').attr('data-id');
			if (!recordId) {
				return;
			}

			App.confirmDelete({
				onConfirm: function () {
					triggerDeleteForm(recordId);
				}
			});
		});

		var $modalLinkedRecordView = $('#modalLinkedRecordView');
		var $modalLinkedRecordViewLabel = $('#modalLinkedRecordViewLabel');
		var $modalLinkedRecordViewLoading = $('#modalLinkedRecordViewLoading');
		var $modalLinkedRecordViewError = $('#modalLinkedRecordViewError');
		var $modalLinkedRecordViewFields = $('#modalLinkedRecordViewFields');
		var currentLinkedRecordId = null;

		var categoryFieldLabels = cfg.categoryFieldLabels || {
			id: 'ID',
			name: 'Name',
			pos: 'Position',
			visible: 'Visible',
			sample_count: 'Samples',
			created: 'Created',
			modified: 'Modified'
		};

		var renderLinkedRecordFields = function (record, fieldLabels) {
			var html = '';
			Object.keys(fieldLabels).forEach(function (key) {
				if (!Object.prototype.hasOwnProperty.call(record, key)) {
					return;
				}
				html += '<div class="record-view-row">' +
					'<dt>' + fieldLabels[key] + '</dt>' +
					'<dd>' + formatRecordValue(key, record[key]) + '</dd>' +
					'</div>';
			});
			$modalLinkedRecordViewFields.html(html);
		};

		var openLinkedRecordModal = function (recordId, options) {
			options = options || {};
			currentLinkedRecordId = recordId;
			$pendingLastVisitedRow = (options.$row && options.$row.length) ? options.$row : null;

			$modalLinkedRecordViewLabel.text((options.title || msg.parentDetails || 'Parent details') + ' #' + recordId);
			$modalLinkedRecordViewLoading.removeClass('d-none');
			$modalLinkedRecordViewError.addClass('d-none').text('');
			$modalLinkedRecordViewFields.addClass('d-none').empty();

			var modal = bootstrap.Modal.getOrCreateInstance($modalLinkedRecordView[0]);
			modal.show();

			$.ajax({
				url: entityUrl(options.url || categoryGetUrl, recordId),
				method: 'GET',
				dataType: 'json'
			}).done(function (res) {
				if (!res || res.success !== true || !res.record) {
					$modalLinkedRecordViewError
						.removeClass('d-none')
						.text((res && res.message) ? res.message : (msg.recordLoadFailed || 'Failed to load the record.'));
					return;
				}
				renderLinkedRecordFields(res.record, options.fieldLabels || categoryFieldLabels);
				$modalLinkedRecordViewFields.removeClass('d-none');
			}).fail(function (xhr) {
				var message = msg.recordLoadFailed || 'Failed to load the record.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				} else if (!xhr || xhr.status === 0) {
					message = msg.noServerResponse || 'No response from the server.';
				}
				$modalLinkedRecordViewError.removeClass('d-none').text(message);
			}).always(function () {
				$modalLinkedRecordViewLoading.addClass('d-none');
			});
		};

		$('.index-data-table tbody').on('click', 'a.category-link', function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $link = $(this);
			var categoryId = $link.attr('data-id');
			if (!categoryId) {
				return;
			}

			openLinkedRecordModal(categoryId, {
				title: msg.parentDetails || 'Parent details',
				url: categoryGetUrl,
				fieldLabels: categoryFieldLabels,
				$row: $link.closest('tr')
			});
		});

		$('#btn-linked-view').on('click', function () {
			if (!currentLinkedRecordId) {
				return;
			}
			window.location.href = entityUrl(parentViewUrl, currentLinkedRecordId);
		});

		$('#btn-linked-edit').on('click', function () {
			if (!currentLinkedRecordId) {
				return;
			}
			window.location.href = entityUrl(parentEditUrl, currentLinkedRecordId);
		});

		$('#btn-linked-delete').on('click', function () {
			if (!currentLinkedRecordId) {
				return;
			}
			App.confirmDelete({
				onConfirm: function () {
					Swal.fire({
						icon: 'info',
						title: msg.deleteTitle || 'Delete',
						text: msg.deleteParentHint || 'You can delete the parent from the Parents list.'
					});
				}
			});
		});

		$('.index-data-table tbody').on('dblclick', 'tr', function (e) {
			if ($(e.target).closest('a, button, .btn').length) {
				return;
			}

			if (rowDoubleClickAction === 'none') {
				return;
			}

			var $row = $(this);
			var recordId = $row.attr('data-id') || String(this.id || '').replace(/^record-/, '');
			if (!recordId) {
				return;
			}

			if (rowDoubleClickAction === 'edit') {
				if (!editUrl) {
					return;
				}
				markLastVisitedRow($row);
				window.location.href = entityUrl(editUrl, recordId);
				return;
			}

			// Default: 'modal' — quick view
			openRecordModal(recordId, $row);
		});

		$modalRecordView.on('hidden.bs.modal', function () {
			if ($pendingLastVisitedRow && $pendingLastVisitedRow.length) {
				markLastVisitedRow($pendingLastVisitedRow);
				$pendingLastVisitedRow = null;
			}
		});

		$modalLinkedRecordView.on('hidden.bs.modal', function () {
			if ($pendingLastVisitedRow && $pendingLastVisitedRow.length) {
				markLastVisitedRow($pendingLastVisitedRow);
				$pendingLastVisitedRow = null;
			}
		});
	});
})(window, jQuery);
