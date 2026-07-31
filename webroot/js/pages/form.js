/**
 * Add / Edit form behaviour (Select2, dates, inputmask, primary-field focus).
 * cakephp-template: page-js:form
 *
 * Load on every Admin form.php (even without Select2) so #name gets focus.
 *
 * Select2 „+” (single + multiple):
 *   Button: .btn-select2-add
 *     data-select2-target="#field-id"
 *     data-create-url="/admin/.../select2-create-..."
 *     data-bs-target="#modalSelect2Add..."
 *   Modal: .modal-select2-add + form.select2-add-form
 *     Display label field: [data-select2-text="1"] (usually name)
 *     Extra fields in the form are POSTed as-is (multi-field create later).
 *   Response: { success, id, text }
 *
 * Expected config:
 *   MyAdmin.config.indexUrl
 *   MyAdmin.config.numberFormat = { locale, decimal, thousand }
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};
	var msg = App.messages || {};
	var cfg = App.config || {};

	var indexUrl = cfg.indexUrl || '';
	var numberFormat = cfg.numberFormat || {};
	var numberDecimal = numberFormat.decimal || ',';
	var numberThousand = numberFormat.thousand || ' ';
	// inputmask: empty groupSeparator disables grouping; keep at least a space for hu
	if (numberThousand === '') {
		numberThousand = ' ';
	}

	var csrfToken = function () {
		var meta = document.querySelector('meta[name="csrfToken"]');
		if (meta && meta.getAttribute('content')) {
			return meta.getAttribute('content');
		}
		var input = document.querySelector('input[name="_csrfToken"]');
		return input ? input.value : '';
	};

	$(function () {
		if (!$('#form-horizontal').length) {
			return;
		}

		/**
		 * Primary field focus on every Admin form (#form-horizontal).
		 * Prefer #name; otherwise first visible text-like .form-control.
		 * Call after Select2/inputmask init — plugins can steal early focus.
		 */
		var focusPrimaryFormField = function () {
			var $form = $('#form-horizontal');
			var $field = $form.find('#name').first();
			if (!$field.length) {
				$field = $form.find('input.form-control, textarea.form-control')
					.not('[type="hidden"], [type="checkbox"], [type="radio"], [type="file"]')
					.filter(':visible')
					.first();
			}
			if ($field.length) {
				$field.trigger('focus');
			}
		};

		if (window.moment) {
			moment.locale('hu');
		}

		var datePickerLocale = {
			format: 'YYYY-MM-DD',
			separator: ' - ',
			applyLabel: msg.dateApply || 'Apply',
			cancelLabel: msg.cancelButton || 'Cancel',
			fromLabel: msg.dateFrom || 'From',
			toLabel: msg.dateTo || 'To',
			customRangeLabel: msg.dateCustomRange || 'Custom',
			weekLabel: msg.dateWeek || 'W',
			daysOfWeek: moment.weekdaysMin(),
			monthNames: moment.monthsShort(),
			firstDay: 1
		};

		if ($.fn.inputmask && $.fn.daterangepicker) {
			$('#datum').inputmask({
				alias: 'datetime',
				inputFormat: 'yyyy-mm-dd',
				placeholder: 'yyyy-mm-dd',
				clearIncomplete: true
			}).daterangepicker({
				singleDatePicker: true,
				showDropdowns: true,
				autoApply: true,
				locale: datePickerLocale
			});

			$('#datumido').inputmask({
				alias: 'datetime',
				inputFormat: 'yyyy-mm-dd HH:MM',
				placeholder: 'yyyy-mm-dd hh:mm',
				clearIncomplete: true
			}).daterangepicker({
				singleDatePicker: true,
				timePicker: true,
				timePicker24Hour: true,
				timePickerIncrement: 15,
				autoApply: true,
				locale: $.extend({}, datePickerLocale, {
					format: 'YYYY-MM-DD HH:mm'
				})
			});

			$('#ido').inputmask({
				alias: 'datetime',
				inputFormat: 'HH:MM',
				placeholder: 'hh:mm',
				clearIncomplete: true
			});

			$('.js-input-decimal, #netto').inputmask({
				alias: 'decimal',
				radixPoint: numberDecimal,
				groupSeparator: numberThousand,
				digits: 2,
				digitsOptional: true,
				allowMinus: true,
				rightAlign: false,
				placeholder: '',
				autoGroup: true,
				removeMaskOnSubmit: false
			});

			$('.js-input-integer, #szam, #pos').inputmask({
				alias: 'integer',
				groupSeparator: numberThousand,
				autoGroup: true,
				allowMinus: true,
				rightAlign: false,
				placeholder: '',
				removeMaskOnSubmit: false
			});
		}

		if (!$.fn.select2) {
			focusPrimaryFormField();
			return;
		}

		var select2CreateTag = function (params) {
			var term = $.trim(params.term);
			if (term === '') {
				return null;
			}
			return {
				id: term,
				text: term,
				newTag: true
			};
		};

		var resolveCreateUrlForSelect = function ($el) {
			var id = $el.attr('id');
			if (!id) {
				return '';
			}
			var $btn = $('.btn-select2-add[data-select2-target="#' + id + '"]').first();
			return $btn.length ? String($btn.data('create-url') || '') : '';
		};

		var $singleSelect = $('#parent-id');
		var $multipleSelects = $('.js-example-basic-multiple');

		if ($singleSelect.length) {
			var singleHasCreate = !!resolveCreateUrlForSelect($singleSelect);
			$singleSelect.select2({
				theme: 'bootstrap-5',
				width: '100%',
				tags: singleHasCreate,
				createTag: singleHasCreate ? select2CreateTag : undefined
			});
		}

		$multipleSelects.each(function () {
			var $el = $(this);
			var hasCreate = !!resolveCreateUrlForSelect($el);
			$el.select2({
				theme: 'bootstrap-5',
				width: '100%',
				placeholder: $el.data('placeholder') || msg.selectCities || msg.selectSamples || 'Select…',
				closeOnSelect: false,
				tags: hasCreate,
				createTag: hasCreate ? select2CreateTag : undefined
			});
		});

		/**
		 * Add/update option and select it.
		 * Multiple: keep existing selections and add the new id.
		 */
		var applySelect2SavedValue = function ($el, newId, newText) {
			newId = String(newId);
			var $existing = $el.find('option').filter(function () {
				return String(this.value) === newId;
			});

			if ($existing.length) {
				$existing.text(newText);
			} else {
				var selected = true;
				$el.append(new Option(newText, newId, selected, selected));
			}

			if ($el.prop('multiple')) {
				var values = $el.val() || [];
				if (!Array.isArray(values)) {
					values = values === null || values === undefined || values === '' ? [] : [values];
				}
				values = values.map(String);
				if (values.indexOf(newId) === -1) {
					values.push(newId);
				}
				$el.val(values).trigger('change');
			} else {
				$el.val(newId).trigger('change');
			}
		};

		/**
		 * @param {jQuery} $el
		 * @param {string} createUrl
		 * @param {object|string} payload  plain object (modal fields) or name string (tag)
		 * @param {object} options
		 */
		var saveSelect2NewValue = function ($el, createUrl, payload, options) {
			options = options || {};

			if (!$el.length || !createUrl) {
				App.alertError(msg.saveNewValueFailed || msg.failedToSave || 'Failed to save the new value.');
				return;
			}

			if ($el.data('select2-saving')) {
				return;
			}
			$el.data('select2-saving', true);

			var tempId = options.tempId || null;
			var data = typeof payload === 'string'
				? { name: payload }
				: $.extend({}, payload || {});

			data._csrfToken = csrfToken();

			var removeTempOption = function () {
				if (!tempId) {
					return;
				}
				$el.find('option').filter(function () {
					return this.value === tempId;
				}).remove();
			};

			var fail = function (message) {
				removeTempOption();
				if (typeof options.onError === 'function') {
					options.onError(message);
				} else {
					if (tempId && !$el.prop('multiple')) {
						$el.val(null).trigger('change');
					}
					App.alertError(message || msg.saveNewValueFailed || msg.failedToSave || 'Failed to save the new value.');
				}
			};

			$.ajax({
				url: createUrl,
				method: 'POST',
				dataType: 'json',
				headers: {
					'X-CSRF-Token': csrfToken()
				},
				data: data
			}).done(function (res) {
				if (!res || res.success !== true || res.id == null || res.id === '') {
					fail(res && res.message ? res.message : (msg.saveNewValueFailed || 'Failed to save the new value.'));
					return;
				}

				removeTempOption();
				applySelect2SavedValue(
					$el,
					String(res.id),
					res.text || data.name || String(res.id)
				);

				if (typeof options.onSuccess === 'function') {
					options.onSuccess(res);
				}
			}).fail(function (xhr) {
				var message = msg.saveNewValueFailed || 'Failed to save the new value.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				} else if (!xhr || xhr.status === 0) {
					message = msg.noServerResponseSaveFailed || 'No response from the server. Failed to save the new value.';
				}
				fail(message);
			}).always(function () {
				$el.data('select2-saving', false);
				if (typeof options.onAlways === 'function') {
					options.onAlways();
				}
			});
		};

		var onSelect2NewTag = function (e) {
			var data = e.params.data;
			if (!data || !data.newTag) {
				return;
			}
			var $el = $(this);
			var createUrl = resolveCreateUrlForSelect($el);
			saveSelect2NewValue($el, createUrl, $.trim(data.text), {
				tempId: data.id
			});
		};

		$singleSelect.on('select2:select', onSelect2NewTag);
		$multipleSelects.on('select2:select', onSelect2NewTag);

		$('.btn-select2-add').each(function () {
			bootstrap.Tooltip.getOrCreateInstance(this, {
				placement: 'top',
				html: true,
				title: msg.addTooltip || ('<b>' + (msg.add || 'Add') + '</b><br>' + (msg.addNewValue || 'Add a new value to the list.'))
			});
		});

		$('.modal-select2-add').on('show.bs.modal', function (e) {
			var $modal = $(this);
			var $btn = $(e.relatedTarget);
			if ($btn.length) {
				$modal.data('select2Target', $btn.data('select2-target') || '');
				$modal.data('createUrl', $btn.data('create-url') || '');
			}
		});

		$('.modal-select2-add').on('shown.bs.modal', function () {
			var $form = $(this).find('.select2-add-form');
			$form.find('.is-invalid').removeClass('is-invalid');
			$form[0] && $form[0].reset();
			var $focus = $form.find('[data-select2-text="1"]').first();
			if (!$focus.length) {
				$focus = $form.find('input, select, textarea').filter(':visible').first();
			}
			$focus.trigger('focus');
		});

		var collectSelect2FormPayload = function ($form) {
			var payload = {};
			$form.find('input, select, textarea').each(function () {
				var $field = $(this);
				var name = $field.attr('name');
				if (!name || $field.is(':disabled')) {
					return;
				}
				if ($field.is(':checkbox, :radio') && !$field.prop('checked')) {
					return;
				}
				payload[name] = $field.val();
			});
			return payload;
		};

		var validateSelect2Form = function ($form) {
			var ok = true;
			$form.find('[required]').each(function () {
				var $field = $(this);
				if ($.trim(String($field.val() || '')) === '') {
					$field.addClass('is-invalid');
					ok = false;
				} else {
					$field.removeClass('is-invalid');
				}
			});
			return ok;
		};

		var saveFromModal = function ($modal) {
			var targetSel = $modal.data('select2Target');
			var createUrl = $modal.data('createUrl');
			var $el = targetSel ? $(targetSel) : $();
			var $form = $modal.find('.select2-add-form');
			var $btnSave = $modal.find('.btn-select2-add-save');

			if (!validateSelect2Form($form)) {
				return;
			}

			var payload = collectSelect2FormPayload($form);
			$btnSave.prop('disabled', true);

			saveSelect2NewValue($el, createUrl, payload, {
				onSuccess: function () {
					var modal = bootstrap.Modal.getInstance($modal[0]);
					if (modal) {
						modal.hide();
					}
				},
				onError: function (message) {
					App.alertError(message || msg.saveNewValueFailed || msg.failedToSave || 'Failed to save the new value.');
				},
				onAlways: function () {
					$btnSave.prop('disabled', false);
				}
			});
		};

		$(document).on('click', '.modal-select2-add .btn-select2-add-save', function () {
			saveFromModal($(this).closest('.modal-select2-add'));
		});

		$(document).on('keydown', '.modal-select2-add .select2-add-form input', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				saveFromModal($(this).closest('.modal-select2-add'));
			}
		});

		$('#btn-cancel, #btn-close-form').on('click', function (e) {
			if ($(this).attr('href') && $(this).attr('href') !== '#') {
				return;
			}
			e.preventDefault();
			if (indexUrl) {
				window.location.href = indexUrl;
			}
		});

		// After Select2 (and other plugins) — every form starts ready to type
		focusPrimaryFormField();
		window.setTimeout(focusPrimaryFormField, 0);
	});
})(window, jQuery);
