/**
 * Add / Edit form behaviour (Select2, Tempus Dominus date/time, inputmask, primary-field focus).
 * cakephp-template: page-js:form
 *
 * Load on every Admin form.php (even without Select2) so #name gets focus.
 *
 * Date / time / datetime (JeffAdmin5 Tempus Dominus 6):
 *   .input-group.js-tempus-picker#picker-{field}
 *     data-picker-type="date|time|datetime"
 *     data-picker-value="Y-m-d|H:i:s|Y-m-d H:i:s" (ISO for setValue; optional)
 *   Formats: yyyy.MM.dd. | HH:mm:ss | yyyy.MM.dd HH:mm:ss
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
	 *   MyAdmin.config.numberFormat = { locale, decimal, thousand, groupSize, decimalDigits, placeholderInteger, placeholderDecimal }
 *   MyAdmin.config.dateFormat = { locale, intl, moment, startOfTheWeek, useTwentyFourHour, date, datetime, time }
 *   MyAdmin.config.trumbowygSvgPath
 *   MyAdmin.config.trumbowygUploadPath
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
	var numberGroupSize = parseInt(numberFormat.groupSize, 10) || 3;
	var numberDecimalDigits = parseInt(numberFormat.decimalDigits, 10);
	if (isNaN(numberDecimalDigits)) {
		numberDecimalDigits = 2;
	}
	// inputmask: empty groupSeparator disables grouping; keep at least a space for hu
	if (numberThousand === '') {
		numberThousand = ' ';
	}

	var dateFormat = cfg.dateFormat || {};
	var displayDateFormat = dateFormat.date || 'YYYY.MM.DD.';
	var displayDateTimeFormat = dateFormat.datetime || 'YYYY.MM.DD. HH:mm:ss';
	var displayTimeFormat = dateFormat.time || 'HH:mm:ss';
	var pickerIntlLocale = dateFormat.intl || (dateFormat.locale
		? String(dateFormat.locale).replace('_', '-')
		: 'hu-HU');
	var pickerMomentLocale = dateFormat.moment || String(pickerIntlLocale).split('-')[0] || 'hu';
	var trumbowygSvgPath = cfg.trumbowygSvgPath || '/plugins/trumbowyg/ui/icons.svg';
	var trumbowygUploadPath = cfg.trumbowygUploadPath || '/plugins/trumbowyg/texteditor-upload.php';

	/**
	 * Tempus: 0=Sunday … 6=Saturday.
	 * Prefer Intl.Locale.weekInfo (1=Monday … 7=Sunday); fallback PHP dateFormat.startOfTheWeek.
	 */
	var resolveStartOfWeek = function (intlLocale, fallback) {
		try {
			if (typeof Intl !== 'undefined' && Intl.Locale) {
				var loc = new Intl.Locale(intlLocale);
				var info = loc.weekInfo || (typeof loc.getWeekInfo === 'function' ? loc.getWeekInfo() : null);
				if (info && typeof info.firstDay === 'number') {
					return info.firstDay === 7 ? 0 : info.firstDay;
				}
			}
		} catch (err) { /* ignore */ }
		var n = Number(fallback);
		return (n >= 0 && n <= 6) ? n : 1;
	};
	var pickerStartOfWeek = resolveStartOfWeek(pickerIntlLocale, dateFormat.startOfTheWeek);
	// en_US → 12h AM/PM; hu/de/… → 24h (no DE/DU meridiem)
	var useTwentyFourHour = dateFormat.useTwentyFourHour !== false
		&& dateFormat.useTwentyFourHour !== 0
		&& dateFormat.useTwentyFourHour !== '0';

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
			moment.locale(pickerMomentLocale);
		}

		/**
		 * Tempus Dominus pickers — same options as JeffAdmin5 (zsfoto/jeffadmin5).
		 * Markup: .input-group.js-tempus-picker[data-picker-type=date|time|datetime][data-picker-value]
		 * Locale / format from MyAdmin.config.dateFormat (App.adminLocale).
		 */
		var tempusIcons = {
			type: 'icons',
			time: 'fa fa-clock-o',
			date: 'fa fa-calendar',
			up: 'fa fa-arrow-up',
			down: 'fa fa-arrow-down',
			previous: 'fa fa-chevron-left',
			next: 'fa fa-chevron-right',
			today: 'fa fa-calendar-check-o',
			clear: 'fa fa-times',
			close: 'fa fa-check'
		};

		var tempusLocalizationBase = {
			locale: pickerIntlLocale,
			startOfTheWeek: pickerStartOfWeek,
			dayViewHeaderFormat: { month: 'long', year: 'numeric' }
		};

		var tempusDisplayButtons = { today: true, clear: true, close: true };

		/** Shared clock block — time-only and datetime side-by-side use the same options. */
		var tempusClockComponents = {
			clock: true,
			hours: true,
			minutes: true,
			seconds: true,
			useTwentyfourHour: useTwentyFourHour
		};

		/**
		 * Tempus Dominus 6 ignores localization.format (uses Intl).
		 * Override formatInput + parseInput with moment (JeffAdmin5-compatible).
		 */
		var bindTempusFormat = function (picker, momentFormat) {
			if (!picker || !picker.dates) {
				return;
			}
			picker.dates.formatInput = function (date) {
				if (!date) {
					return '';
				}
				if (window.moment) {
					return moment(date).format(momentFormat);
				}
				var y = date.getFullYear();
				var m = String(date.getMonth() + 1).padStart(2, '0');
				var d = String(date.getDate()).padStart(2, '0');
				var H = String(date.getHours()).padStart(2, '0');
				var i = String(date.getMinutes()).padStart(2, '0');
				var s = String(date.getSeconds()).padStart(2, '0');
				if (momentFormat.indexOf('YYYY') !== -1 && (momentFormat.indexOf('HH') !== -1 || momentFormat.indexOf('h') !== -1)) {
					return y + '-' + m + '-' + d + ' ' + H + ':' + i + ':' + s;
				}
				if (momentFormat.indexOf('HH') !== -1 || momentFormat.indexOf('h') !== -1) {
					return H + ':' + i + ':' + s;
				}
				return y + '-' + m + '-' + d;
			};
			picker.dates.parseInput = function (value) {
				if (value === undefined || value === null || value === '') {
					return undefined;
				}
				if (value instanceof Date && !isNaN(value.getTime())) {
					return tempusDominus.DateTime.convert(value, pickerIntlLocale);
				}
				if (window.moment) {
					var parsed = moment(
						value,
						[
							momentFormat,
							'YYYY-MM-DD HH:mm:ss',
							'YYYY-MM-DD',
							'HH:mm:ss',
							moment.ISO_8601
						],
						true
					);
					if (!parsed.isValid()) {
						parsed = moment(value);
					}
					if (parsed.isValid()) {
						return tempusDominus.DateTime.convert(parsed.toDate(), pickerIntlLocale);
					}
				}
				try {
					return tempusDominus.DateTime.convert(new Date(value), pickerIntlLocale);
				} catch (err) {
					return undefined;
				}
			};
		};

		/**
		 * Set stored value — JeffAdmin5 (zsfoto/jeffadmin5):
		 *   picker.dates.setValue(picker.dates.parseInput(moment(...).toDate()), …)
		 */
		var setTempusValue = function (picker, value, momentParse) {
			if (!picker || value === undefined || value === null || value === '') {
				return;
			}
			try {
				if (!window.moment) {
					return;
				}
				var m = moment(value, momentParse, true);
				if (!m.isValid()) {
					m = moment(value);
				}
				if (!m.isValid()) {
					return;
				}
				picker.dates.setValue(
					picker.dates.parseInput(m.toDate()),
					picker.dates.lastPickedIndex
				);
			} catch (err) { /* ignore invalid initial value */ }
		};

		/**
		 * Init Tempus on wrapper. data-picker-value (ISO) is source of truth on edit.
		 * Clear locale-formatted input before construct so TD 6 native parse does not
		 * mis-read the display string; then setValue like JeffAdmin5.
		 */
		var initTempusPicker = function (field, value, options, momentFormat, momentParse, valueForSet) {
			var el = document.getElementById(field);
			if (!el || typeof tempusDominus === 'undefined') {
				return;
			}
			var input = el.querySelector('input');
			var hasValue = value !== undefined && value !== null && value !== '';
			if (input && hasValue) {
				input.value = '';
			}
			var picker = new tempusDominus.TempusDominus(el, $.extend(true, {
				localization: $.extend({}, tempusLocalizationBase),
				useCurrent: !hasValue,
				display: {
					icons: tempusIcons,
					buttons: tempusDisplayButtons,
					theme: 'light'
				}
			}, options));
			bindTempusFormat(picker, momentFormat);
			if (picker.viewDate && typeof picker.viewDate.setLocale === 'function') {
				picker.viewDate.setLocale(pickerIntlLocale);
			}
			setTempusValue(picker, valueForSet !== undefined ? valueForSet : value, momentParse);
		};

		var initDatePicker = function (field, value) {
			initTempusPicker(field, value, {
				localization: { format: displayDateFormat },
				display: {
					components: {
						calendar: true,
						date: true,
						month: true,
						year: true,
						decades: true,
						clock: false,
						hours: false,
						minutes: false,
						seconds: false,
						useTwentyfourHour: undefined
					}
				}
			}, displayDateFormat, 'YYYY-MM-DD');
		};

		var initDateTimePicker = function (field, value) {
			initTempusPicker(field, value, {
				localization: { format: displayDateTimeFormat },
				display: {
					sideBySide: true,
					components: $.extend({
						calendar: true,
						date: true,
						month: true,
						year: true,
						decades: true
					}, tempusClockComponents)
				}
			}, displayDateTimeFormat, 'YYYY-MM-DD HH:mm:ss');
		};

		var initTimePicker = function (field, value) {
			initTempusPicker(field, value, {
				localization: { format: displayTimeFormat },
				display: {
					components: $.extend({
						calendar: false,
						date: false,
						month: false,
						year: false,
						decades: false
					}, tempusClockComponents)
				}
			}, displayTimeFormat, 'YYYY-MM-DD HH:mm:ss', value ? ('2000-01-01 ' + value) : value);
		};

		$('.js-tempus-picker').each(function () {
			var id = this.id;
			if (!id) {
				return;
			}
			var type = String($(this).attr('data-picker-type') || 'date');
			var value = $(this).attr('data-picker-value');
			if (value === '') {
				value = undefined;
			}
			if (type === 'datetime') {
				initDateTimePicker(id, value);
			} else if (type === 'time') {
				initTimePicker(id, value);
			} else {
				initDatePicker(id, value);
			}
		});

		if ($.fn.inputmask) {
			/**
			 * Inputmask 5 + groupSeparator: `inputmode=decimal|numeric` breaks after ~3 digits
			 * (grouping). Force text inputmode. Disable k/m shortcuts. Space thousand OK for hu.
			 */
			var numericCommon = {
				groupSeparator: numberThousand,
				autoGroup: true,
				groupSize: numberGroupSize,
				allowMinus: true,
				rightAlign: false,
				placeholder: '',
				// Unmasked on submit; server middleware still normalizes locale strings as fallback
				autoUnmask: true,
				removeMaskOnSubmit: true,
				inputType: 'text',
				inputmode: 'text',
				shortcuts: null,
				positionCaretOnClick: 'lvp',
				clearIncomplete: false
			};

			$('.js-input-decimal').inputmask($.extend({}, numericCommon, {
				alias: 'decimal',
				radixPoint: numberDecimal,
				digits: numberDecimalDigits,
				digitsOptional: true,
				substituteRadixPoint: true
			}));

			// Integer: .js-input-integer + #pos / name=pos (every Admin form with Position)
			var $integerInputs = $('.js-input-integer, #pos, input[name="pos"], input[name$="[pos]"]')
				.filter(function () {
					return !$(this).hasClass('js-input-decimal');
				});
			$integerInputs.inputmask($.extend({}, numericCommon, {
				alias: 'integer',
				digits: 0
			}));

			// HTML attr (template / alias default) can override — keep text for reliable typing
			$('.js-input-decimal').add($integerInputs).attr('inputmode', 'text');
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

		if ($.fn.trumbowyg && $('.editor').length) {
			var trumbowygOptions = {
				svgPath: trumbowygSvgPath,
				btns: [
					['viewHTML'],
					['historyUndo', 'historyRedo'],
					['formatting'],
					['strong', 'em', 'del'],
					['superscript', 'subscript'],
					['foreColor', 'backColor'],
					['fontfamily'],
					['fontsize'],
					['lineheight'],
					['link'],
					['insertImage', 'upload', 'base64', 'noembed'],
					['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
					['unorderedList', 'orderedList'],
					['preformatted'],
					['highlight'],
					['table'],
					['specialChars'],
					['horizontalRule'],
					['removeformat'],
					['fullscreen']
				],
				plugins: {
					upload: {
						serverPath: trumbowygUploadPath,
						fileFieldName: 'fileToUpload'
					}
				}
			};

			$('.editor').trumbowyg(trumbowygOptions);

			$('#formLanguageTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
				var target = e.target.getAttribute('data-bs-target');
				if (!target) {
					return;
				}
				$(target).find('.editor').each(function () {
					var $editor = $(this);
					if ($editor.data('trumbowyg')) {
						$editor.trumbowyg('html', $editor.trumbowyg('html'));
					}
				});
			});
		}

		// After Select2 (and other plugins) — every form starts ready to type
		focusPrimaryFormField();
		window.setTimeout(focusPrimaryFormField, 0);
	});
})(window, jQuery);
