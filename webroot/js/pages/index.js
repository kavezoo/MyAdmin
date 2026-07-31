/**
 * Index / list + view related-table behaviour
 * (record modal, linked/related modal, delete, last-visited, double-click).
 *
 * Expected config (set before this file loads):
 *   MyAdmin.config.rowDoubleClickAction  // 'modal' | 'edit' | 'none'
 *     — index saját sorai + view kapcsolt tábla sorai
 *   MyAdmin.config.recordGetUrl / editUrl / viewUrl  // index saját modul
 *   MyAdmin.config.categoryGetUrl / parentEditUrl / parentViewUrl / parentDeleteUrl
 *   MyAdmin.config.recordFieldLabels / categoryFieldLabels
 *   MyAdmin.config.entityFieldLabels  // { city: {…}, sample: {…}, parent: {…} } — view
 *
 * View / linked link: a.record-modal-link (vagy a.category-link) data-* attribútumokkal.
 * Kapcsolt tábla: table.related-records-table + data-get-url / edit / view / delete / labels / title
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
	var parentDeleteUrl = cfg.parentDeleteUrl || '';

	$(function () {
		var hasTables = $('.index-data-table').length > 0;
		var hasModalLinks = $('.record-modal-link, .category-link').length > 0;
		var hasRecordModal = $('#modalRecordView').length > 0;
		var hasLinkedModal = $('#modalLinkedRecordView').length > 0;

		if (!hasTables && !hasModalLinks && !hasRecordModal && !hasLinkedModal) {
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
				// *_count: leave blank (do not show 0 / —)
				if (key === 'city_count' || key === 'sample_count' || /_count$/.test(key)) {
					return '';
				}
				return '—';
			}
			return $('<div>').text(String(value)).html();
		};

		/**
		 * HABTM / hasMany name list for modals: [{ id, name }, …]
		 * Config: MyAdmin.config.relatedLinkFields[fieldKey] = { getUrl, editUrl, viewUrl, deleteUrl, labels, title, deleteFormPrefix }
		 */
		var isRelatedLinkList = function (value) {
			return Array.isArray(value)
				&& value.length > 0
				&& value.every(function (item) {
					return item
						&& typeof item === 'object'
						&& item.id != null
						&& item.name != null;
				});
		};

		var renderRelatedLinkList = function (key, items) {
			var linkCfg = (cfg.relatedLinkFields || {})[key];
			if (!linkCfg) {
				return $('<div>').text(items.map(function (item) {
					return String(item.name);
				}).join(', ')).html();
			}

			return items.map(function (item) {
				var $a = $('<a>', {
					href: '#',
					'class': 'record-modal-link',
					'data-id': item.id,
					'data-get-url': linkCfg.getUrl || '',
					'data-edit-url': linkCfg.editUrl || '',
					'data-view-url': linkCfg.viewUrl || '',
					'data-delete-url': linkCfg.deleteUrl || '',
					'data-delete-form-prefix': linkCfg.deleteFormPrefix || '',
					'data-labels': linkCfg.labels || '',
					'data-title': linkCfg.title || ''
				});
				$a.append(document.createTextNode(String(item.name)));
				$a.append($('<span class="record-modal-link-icon">&nbsp;<i class="fa fa-link" aria-hidden="true"></i></span>'));
				return $('<div>').append($a).html();
			}).join(', ');
		};

		var renderFieldsInto = function ($target, record, fieldLabels) {
			var html = '';
			Object.keys(fieldLabels).forEach(function (key) {
				if (!Object.prototype.hasOwnProperty.call(record, key)) {
					return;
				}
				var raw = record[key];
				var cell;
				var ddClass = '';
				if (isRelatedLinkList(raw)) {
					cell = renderRelatedLinkList(key, raw);
					ddClass = ' class="record-related-list"';
				} else if (Array.isArray(raw) && raw.length === 0) {
					cell = '—';
				} else {
					cell = formatRecordValue(key, raw);
				}
				html += '<div class="record-view-row">' +
					'<dt>' + fieldLabels[key] + '</dt>' +
					'<dd' + ddClass + '>' + cell + '</dd>' +
					'</div>';
			});
			$target.html(html);
		};

		var entityUrl = function (base, id) {
			if (!base) {
				return '#';
			}
			return base.replace(/\/$/, '') + '/' + encodeURIComponent(id);
		};

		var csrfToken = function () {
			return $('meta[name="csrfToken"]').attr('content') || '';
		};

		var submitPostDelete = function (deleteUrl, recordId) {
			var url = entityUrl(deleteUrl, recordId);
			if (!url || url === '#') {
				App.alertError(msg.deleteFormNotFound
					? msg.deleteFormNotFound.replace('{0}', recordId)
					: 'Delete URL missing.');
				return;
			}
			var $form = $('<form>', { method: 'POST', action: url, 'class': 'd-none' });
			$form.append($('<input>', { type: 'hidden', name: '_method', value: 'POST' }));
			$form.append($('<input>', { type: 'hidden', name: '_csrfToken', value: csrfToken() }));
			$('body').append($form);
			$form.trigger('submit');
		};

		var triggerDeleteForm = function (recordId, options) {
			options = options || {};
			var prefix = options.deleteFormPrefix || '';

			if (options.deleteFormSelector) {
				var $explicit = $(options.deleteFormSelector);
				if ($explicit.length && $explicit.is('form')) {
					$explicit.trigger('submit');
					return;
				}
			}

			if (prefix) {
				var $prefixed = $('#delete-form-' + prefix + '-' + recordId);
				if ($prefixed.length && $prefixed.is('form')) {
					$prefixed.trigger('submit');
					return;
				}
			}

			// Kapcsolt entitás: ne a saját modul #delete-form-{id}-jét használd
			if (options.deleteUrl) {
				submitPostDelete(options.deleteUrl, recordId);
				return;
			}

			var $form = $('#delete-form-' + recordId);
			if ($form.length && $form.is('form')) {
				$form.trigger('submit');
				return;
			}

			App.alertError(
				(msg.deleteFormNotFound || msg.deleteFormMissing || 'Delete form not found. ID: {0}').replace('{0}', recordId)
			);
		};

		var setModalDeleteEnabled = function ($btn, canDelete) {
			if (!$btn || !$btn.length) {
				return;
			}
			if (canDelete) {
				$btn.prop('disabled', false).removeClass('disabled').removeAttr('aria-disabled')
					.attr('title', '');
			} else {
				$btn.prop('disabled', true).addClass('disabled').attr('aria-disabled', 'true')
					.attr('title', msg.cannotDeleteHasChildren || 'Cannot delete this record because it has related child records.');
			}
		};

		var resolveCanDelete = function (record, $row) {
			if (record && typeof record.can_delete !== 'undefined') {
				return !!record.can_delete;
			}
			if ($row && $row.length) {
				var attr = $row.attr('data-can-delete');
				if (typeof attr !== 'undefined') {
					return attr === '1' || attr === 'true';
				}
			}
			return true;
		};

		var openRecordModal = function (recordId, $row) {
			if (!$modalRecordView.length) {
				return;
			}
			currentRecordId = recordId;
			$pendingLastVisitedRow = ($row && $row.length) ? $row : null;
			$modalRecordViewLabel.text((msg.recordDetails || 'Record details') + ' #' + recordId);
			$modalRecordViewLoading.removeClass('d-none');
			$modalRecordViewError.addClass('d-none').text('');
			$modalRecordViewFields.addClass('d-none').empty();
			setModalDeleteEnabled($('#btn-record-delete'), resolveCanDelete(null, $row));

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
				renderFieldsInto($modalRecordViewFields, res.record, recordFieldLabels);
				$modalRecordViewFields.removeClass('d-none');
				setModalDeleteEnabled($('#btn-record-delete'), resolveCanDelete(res.record, $row));
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
			if (!currentRecordId || $(this).prop('disabled') || $(this).hasClass('disabled')) {
				return;
			}
			App.confirmDelete({
				onConfirm: function () {
					triggerDeleteForm(currentRecordId);
				}
			});
		});

		$(document).on('click', '.index-data-table tbody a.btn-row-delete', function (e) {
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

			var $table = $btn.closest('table.related-records-table');
			var deleteOpts = {};
			if ($table.length) {
				deleteOpts.deleteFormPrefix = $table.attr('data-delete-form-prefix') || '';
				deleteOpts.deleteUrl = $table.attr('data-delete-url') || '';
			}

			App.confirmDelete({
				onConfirm: function () {
					triggerDeleteForm(recordId, deleteOpts);
				}
			});
		});

		// —— Linked / related entity modal (belongsTo, HABTM name, related tab) ——
		var $modalLinkedRecordView = $('#modalLinkedRecordView');
		var $modalLinkedRecordViewLabel = $('#modalLinkedRecordViewLabel');
		var $modalLinkedRecordViewLoading = $('#modalLinkedRecordViewLoading');
		var $modalLinkedRecordViewError = $('#modalLinkedRecordViewError');
		var $modalLinkedRecordViewFields = $('#modalLinkedRecordViewFields');
		var linkedContext = {
			id: null,
			getUrl: '',
			editUrl: '',
			viewUrl: '',
			deleteUrl: '',
			deleteFormPrefix: '',
			fieldLabels: null
		};

		var categoryFieldLabels = cfg.categoryFieldLabels || {
			id: 'ID',
			name: 'Name',
			pos: 'Position',
			visible: 'Visible',
			sample_count: 'Samples',
			created: 'Created',
			modified: 'Modified'
		};

		var entityFieldLabels = cfg.entityFieldLabels || {};

		var resolveLabels = function (key, fallback) {
			if (key && entityFieldLabels[key]) {
				return entityFieldLabels[key];
			}
			return fallback || categoryFieldLabels;
		};

		var resolveFromElement = function ($el) {
			var $table = $el.closest('table.related-records-table');
			var labelsKey = $el.attr('data-labels') || ($table.length ? $table.attr('data-labels') : '') || '';
			var pick = function (attr, fallback) {
				return $el.attr(attr)
					|| ($table.length ? $table.attr(attr) : '')
					|| fallback
					|| '';
			};

			return {
				getUrl: pick('data-get-url', categoryGetUrl),
				editUrl: pick('data-edit-url', parentEditUrl),
				viewUrl: pick('data-view-url', parentViewUrl),
				deleteUrl: pick('data-delete-url', parentDeleteUrl),
				deleteFormPrefix: pick('data-delete-form-prefix', ''),
				title: pick('data-title', msg.parentDetails || 'Parent details'),
				fieldLabels: resolveLabels(labelsKey, categoryFieldLabels),
				labelsKey: labelsKey
			};
		};

		var openLinkedRecordModal = function (recordId, options) {
			options = options || {};
			if (!$modalLinkedRecordView.length) {
				return;
			}

			linkedContext = {
				id: recordId,
				getUrl: options.getUrl || options.url || categoryGetUrl,
				editUrl: options.editUrl || parentEditUrl,
				viewUrl: options.viewUrl || parentViewUrl,
				deleteUrl: options.deleteUrl || parentDeleteUrl || '',
				deleteFormPrefix: options.deleteFormPrefix || '',
				fieldLabels: options.fieldLabels || categoryFieldLabels
			};

			$pendingLastVisitedRow = (options.$row && options.$row.length) ? options.$row : null;

			$modalLinkedRecordViewLabel.text((options.title || msg.parentDetails || 'Parent details') + ' #' + recordId);
			$modalLinkedRecordViewLoading.removeClass('d-none');
			$modalLinkedRecordViewError.addClass('d-none').text('');
			$modalLinkedRecordViewFields.addClass('d-none').empty();
			setModalDeleteEnabled($('#btn-linked-delete'), resolveCanDelete(null, options.$row));

			var modal = bootstrap.Modal.getOrCreateInstance($modalLinkedRecordView[0]);
			modal.show();

			$.ajax({
				url: entityUrl(linkedContext.getUrl, recordId),
				method: 'GET',
				dataType: 'json'
			}).done(function (res) {
				if (!res || res.success !== true || !res.record) {
					$modalLinkedRecordViewError
						.removeClass('d-none')
						.text((res && res.message) ? res.message : (msg.recordLoadFailed || 'Failed to load the record.'));
					return;
				}
				renderFieldsInto($modalLinkedRecordViewFields, res.record, linkedContext.fieldLabels);
				$modalLinkedRecordViewFields.removeClass('d-none');
				setModalDeleteEnabled($('#btn-linked-delete'), resolveCanDelete(res.record, options.$row));
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

		$(document).on('click', 'a.record-modal-link, a.category-link', function (e) {
			e.preventDefault();
			e.stopPropagation();

			var $link = $(this);
			var recordId = $link.attr('data-id');
			if (!recordId) {
				return;
			}

			var resolved = resolveFromElement($link);
			openLinkedRecordModal(recordId, {
				title: resolved.title,
				getUrl: resolved.getUrl,
				editUrl: resolved.editUrl,
				viewUrl: resolved.viewUrl,
				deleteUrl: resolved.deleteUrl,
				deleteFormPrefix: resolved.deleteFormPrefix,
				fieldLabels: resolved.fieldLabels,
				$row: $link.closest('tr')
			});
		});

		$('#btn-linked-view').on('click', function () {
			if (!linkedContext.id) {
				return;
			}
			window.location.href = entityUrl(linkedContext.viewUrl, linkedContext.id);
		});

		$('#btn-linked-edit').on('click', function () {
			if (!linkedContext.id) {
				return;
			}
			window.location.href = entityUrl(linkedContext.editUrl, linkedContext.id);
		});

		$('#btn-linked-delete').on('click', function () {
			if (!linkedContext.id || $(this).prop('disabled') || $(this).hasClass('disabled')) {
				return;
			}
			App.confirmDelete({
				onConfirm: function () {
					triggerDeleteForm(linkedContext.id, {
						deleteFormPrefix: linkedContext.deleteFormPrefix,
						deleteUrl: linkedContext.deleteUrl
					});
				}
			});
		});

		$(document).on('dblclick', '.index-data-table tbody tr', function (e) {
			if ($(e.target).closest('a, button, .btn').length) {
				return;
			}

			if (rowDoubleClickAction === 'none') {
				return;
			}

			var $row = $(this);
			var recordId = $row.attr('data-id') || String(this.id || '').replace(/^record-/, '').replace(/^related-[a-z]+-/, '');
			if (!recordId) {
				return;
			}

			var $table = $row.closest('table.related-records-table');

			// View page related tab (or any related-records-table)
			if ($table.length) {
				var related = resolveFromElement($table);
				if (rowDoubleClickAction === 'edit') {
					if (!related.editUrl) {
						return;
					}
					markLastVisitedRow($row);
					window.location.href = entityUrl(related.editUrl, recordId);
					return;
				}
				openLinkedRecordModal(recordId, {
					title: related.title,
					getUrl: related.getUrl,
					editUrl: related.editUrl,
					viewUrl: related.viewUrl,
					deleteUrl: related.deleteUrl,
					deleteFormPrefix: related.deleteFormPrefix,
					fieldLabels: related.fieldLabels,
					$row: $row
				});
				return;
			}

			// Index: own module rows
			if (rowDoubleClickAction === 'edit') {
				if (!editUrl) {
					return;
				}
				markLastVisitedRow($row);
				window.location.href = entityUrl(editUrl, recordId);
				return;
			}

			openRecordModal(recordId, $row);
		});

		if ($modalRecordView.length) {
			$modalRecordView.on('hidden.bs.modal', function () {
				if ($pendingLastVisitedRow && $pendingLastVisitedRow.length) {
					markLastVisitedRow($pendingLastVisitedRow);
					$pendingLastVisitedRow = null;
				}
			});
		}

		if ($modalLinkedRecordView.length) {
			$modalLinkedRecordView.on('hidden.bs.modal', function () {
				if ($pendingLastVisitedRow && $pendingLastVisitedRow.length) {
					markLastVisitedRow($pendingLastVisitedRow);
					$pendingLastVisitedRow = null;
				}
			});
		}
	});
})(window, jQuery);
