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
 *
 * Unsaved changes: snapshot of #form-horizontal after widget init; leave via <a>/cancel
 * → MyAdmin.confirmLeave (Swal) only when a field value changed since load.
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
	 * Preserve <style> blocks across Trumbowyg contenteditable (browser strips them).
	 */
	var extractHtmlStyleBlocks = function (html) {
		var styles = [];
		var body = String(html == null ? '' : html).replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, function (block) {
			styles.push(block);
			return '';
		});
		return { styles: styles, body: body };
	};

	var mergeHtmlStyleBlocks = function (body, styles) {
		styles = Array.isArray(styles) ? styles.filter(Boolean) : [];
		body = String(body == null ? '' : body);
		if (!styles.length) {
			return body;
		}
		return styles.join('\n') + body;
	};

	var prepareEditorStyleBlocks = function ($editors) {
		$editors.each(function () {
			var $ta = $(this);
			var parts = extractHtmlStyleBlocks($ta.val());
			$ta.data('htmlStyleBlocks', parts.styles);
			if (parts.styles.length) {
				$ta.val(parts.body);
			}
		});
	};

	var enableTrumbowygStylePreservation = function ($editors) {
		$editors.each(function () {
			var $ta = $(this);
			if ($ta.data('tbwStylePreserveBound')) {
				return;
			}
			$ta.data('tbwStylePreserveBound', true);

			var readBodyHtml = function () {
				if ($ta.data('trumbowyg')) {
					return String($ta.trumbowyg('html') || '');
				}
				return String($ta.val() || '');
			};

			var rememberStylesFrom = function (html) {
				var extracted = extractHtmlStyleBlocks(html);
				if (extracted.styles.length) {
					$ta.data('htmlStyleBlocks', extracted.styles);
				}
				return extracted;
			};

			var writeMergedToTextarea = function () {
				var extracted = rememberStylesFrom(readBodyHtml());
				var styles = extracted.styles.length
					? extracted.styles
					: ($ta.data('htmlStyleBlocks') || []);
				$ta.val(mergeHtmlStyleBlocks(extracted.body, styles));
			};

			$ta.on('tbwchange.tbwStyles tbwblur.tbwStyles', function () {
				writeMergedToTextarea();
			});

			var api = $ta.data('trumbowyg');
			if (api && api.$box) {
				api.$box.on('click.tbwStyles', '.trumbowyg-viewHTML-button', function () {
					window.setTimeout(function () {
						if (api.$box.hasClass('trumbowyg-editor-hidden')) {
							writeMergedToTextarea();
						} else {
							var extracted = rememberStylesFrom($ta.val());
							$ta.val(extracted.body);
							$ta.trumbowyg('html', extracted.body);
							writeMergedToTextarea();
						}
					}, 0);
				});
			}
		});
	};

	/**
	 * Drag the bottom edge of a Trumbowyg box to change height (ns-resize handle).
	 */
	var enableTrumbowygVerticalResize = function ($editors) {
		var minHeight = 200;
		$editors.each(function () {
			var $ta = $(this);
			var api = $ta.data('trumbowyg');
			if (!api || !api.$box) {
				return;
			}
			var $box = api.$box;
			if ($box.data('tbwResizeBound')) {
				return;
			}
			$box.data('tbwResizeBound', true);

			var $handle = $('<div/>', {
				'class': 'trumbowyg-resize-handle',
				title: msg.editorResize || 'Resize editor',
				role: 'separator',
				'aria-orientation': 'horizontal'
			});
			$box.append($handle);

			var startY = 0;
			var startHeight = 0;

			var eventClientY = function (e) {
				var oe = e.originalEvent || e;
				if (oe.touches && oe.touches.length) {
					return oe.touches[0].clientY;
				}
				if (oe.changedTouches && oe.changedTouches.length) {
					return oe.changedTouches[0].clientY;
				}
				return e.clientY;
			};

			var onMove = function (e) {
				var next = Math.max(minHeight, startHeight + (eventClientY(e) - startY));
				$box.addClass('trumbowyg-resized');
				$box.css('height', next + 'px');
				e.preventDefault();
			};

			var onUp = function () {
				$(document).off('.tbwResize');
				$box.removeClass('trumbowyg-resizing');
				$('body').css('cursor', '');
			};

			$handle.on('mousedown.tbwResize touchstart.tbwResize', function (e) {
				if ($box.hasClass('trumbowyg-fullscreen')) {
					return;
				}
				startY = eventClientY(e);
				startHeight = $box.outerHeight();
				$box.addClass('trumbowyg-resizing');
				$('body').css('cursor', 'ns-resize');
				$(document)
					.on('mousemove.tbwResize touchmove.tbwResize', onMove)
					.on('mouseup.tbwResize touchend.tbwResize touchcancel.tbwResize', onUp);
				e.preventDefault();
			});
		});
	};

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

		var $form = $('#form-horizontal');
		var formBaseline = '';
		var allowLeave = false;
		var skipFieldNames = {
			_method: true,
			_csrfToken: true
		};

		/**
		 * Programmatic navigation (e.g. country Select2 reload) — skip beforeunload / link guards.
		 */
		App.allowFormLeave = function () {
			allowLeave = true;
		};

		App.isFormDirty = function () {
			return isFormDirty();
		};

		/**
		 * Re-take baseline after late widget init (phone prefix, Select2, title-case).
		 * Call when a page script mutates fields on load without user edits.
		 */
		App.recaptureFormBaseline = function () {
			captureBaseline();
		};

		/**
		 * Snapshot of meaningful form fields (after Select2 / Tempus / Trumbowyg init).
		 * Values are normalized so cosmetic widget defaults do not look “dirty”.
		 * @returns {string}
		 */
		var snapshotForm = function () {
			var data = {};
			$form.find('input, select, textarea').each(function () {
				var $el = $(this);
				var name = $el.attr('name');
				if (!name || skipFieldNames[name] || $el.is(':disabled')) {
					return;
				}
				// Select2 „+” modals sit outside #form-horizontal — ignore nested modals if any
				if ($el.closest('.modal').length) {
					return;
				}
				var type = ($el.attr('type') || '').toLowerCase();
				if (type === 'file' || type === 'submit' || type === 'button' || type === 'image') {
					return;
				}
				if (type === 'checkbox') {
					if (!Object.prototype.hasOwnProperty.call(data, name)) {
						data[name] = [];
					}
					if ($el.prop('checked')) {
						data[name].push(String($el.val() || '1'));
					}
					return;
				}
				if (type === 'radio') {
					if ($el.prop('checked')) {
						data[name] = String($el.val());
					} else if (!Object.prototype.hasOwnProperty.call(data, name)) {
						data[name] = '';
					}
					return;
				}
				if ($el.hasClass('editor') && $el.next('.note-editor').length && typeof $el.summernote === 'function') {
					data[name] = String($el.summernote('code') || '');
					return;
				}
				var val = $el.val();
				if ($.isArray(val)) {
					data[name] = val.map(String).join('\u0001');
					return;
				}
				var str = val == null ? '' : String(val);
				// Phone: empty or country-prefix-only → same as empty (init/focus puts +36, blur clears it)
				if ($el.hasClass('js-phone-intl')) {
					var phone = str.trim();
					var defPrefix = String($el.attr('data-default-prefix') || '').trim();
					if (phone === '' || phone === '+' || (defPrefix !== '' && phone === defPrefix)) {
						data[name] = '';
						return;
					}
					data[name] = phone;
					return;
				}
				data[name] = str;
			});
			return JSON.stringify(data);
		};

		var captureBaseline = function () {
			formBaseline = snapshotForm();
		};

		var isFormDirty = function () {
			if (allowLeave || formBaseline === '') {
				return false;
			}
			return snapshotForm() !== formBaseline;
		};

		var shouldGuardNavigationLink = function ($a, href) {
			if (!href || href === '#' || href.indexOf('#') === 0) {
				return false;
			}
			if (/^(javascript:|mailto:|tel:)/i.test(href)) {
				return false;
			}
			if ($a.attr('target') === '_blank' || $a.is('[download]')) {
				return false;
			}
			var toggle = $a.attr('data-bs-toggle') || '';
			if (toggle === 'modal' || toggle === 'tab' || toggle === 'dropdown'
				|| toggle === 'collapse' || toggle === 'offcanvas' || toggle === 'tooltip'
				|| toggle === 'popover') {
				return false;
			}
			if ($a.attr('data-bs-dismiss') || $a.hasClass('btn-row-delete')) {
				return false;
			}
			if ($a.closest('.modal').length) {
				return false;
			}
			return true;
		};

		var leaveTo = function (href) {
			allowLeave = true;
			window.location.href = href;
		};

		/**
		 * Primary field focus on every Admin form (#form-horizontal).
		 * Prefer #name; otherwise first visible text-like .form-control.
		 * Call after Select2/inputmask init — plugins can steal early focus.
		 */
		var focusPrimaryFormField = function () {
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
				placeholder: $el.data('placeholder') || 'Select…',
				closeOnSelect: false,
				tags: hasCreate,
				createTag: hasCreate ? select2CreateTag : undefined
			});

			// Locked option (e.g. en_GB country) — cannot unselect
			var lockedId = String($el.attr('data-locked-id') || $el.data('locked-id') || '');
			if (lockedId !== '') {
				$el.on('select2:unselecting', function (e) {
					var id = e.params && e.params.args && e.params.args.data
						? String(e.params.args.data.id)
						: '';
					if (id === lockedId) {
						e.preventDefault();
					}
				});
				var vals = $el.val() || [];
				if (!Array.isArray(vals)) {
					vals = [vals];
				}
				vals = vals.map(String);
				if (vals.indexOf(lockedId) === -1) {
					vals.unshift(lockedId);
					$el.val(vals).trigger('change');
				}
			}
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
			var $btn = $(this);
			var href = $btn.attr('href') || '';
			var navigate = function () {
				allowLeave = true;
				if (href && href !== '#') {
					window.location.href = href;
				} else if (indexUrl) {
					window.location.href = indexUrl;
				}
			};
			if (!isFormDirty()) {
				if (href && href !== '#') {
					return;
				}
				e.preventDefault();
				navigate();
				return;
			}
			e.preventDefault();
			App.confirmLeave({ onConfirm: navigate });
		});

		if ($.fn.summernote && $('.editor').length) {
			// JeffAdmin5: jeffAdminInitSummerNote(field, height, placeholder, lang, tabsize)
			// https://packagist.org/packages/zsfoto/jeffadmin5
			var summernoteLang = 'hu-HU';
			var docLang = String(document.documentElement.getAttribute('lang') || '');
			if (docLang) {
				var normalized = docLang.replace('_', '-');
				if (normalized.toLowerCase().indexOf('en') === 0) {
					summernoteLang = 'en-US';
				} else if (/^[a-z]{2}-[A-Z]{2}$/.test(normalized)) {
					summernoteLang = normalized;
				}
			}

			$('.editor').each(function () {
				var $ta = $(this);
				if ($ta.next('.note-editor').length) {
					return;
				}
				var height = parseInt($ta.attr('data-editor-height'), 10);
				if (!height || height < 120) {
					height = $ta.hasClass('editor-tall') ? 520 : 400;
				}
				$ta.summernote({
					placeholder: $ta.attr('placeholder') || '',
					tabsize: 2,
					height: height,
					lang: summernoteLang,
					// Keep pasted / source HTML (incl. <style>) — do not strip in code view
					codeviewFilter: false,
					codeviewIframeFilter: false,
					callbacks: {
						onChange: function (contents) {
							$ta.val(contents);
						},
						onBlur: function () {
							$ta.val($ta.summernote('code'));
						}
					}
				});
			});

			$form.on('submit.summernoteSync', function () {
				$('.editor').each(function () {
					var $ta = $(this);
					if ($ta.next('.note-editor').length && typeof $ta.summernote === 'function') {
						$ta.val($ta.summernote('code'));
					}
				});
			});

			// After showing a Bootstrap tab (description pane), refresh editors
			$(document).on('shown.bs.tab', 'a[data-bs-toggle="tab"], button[data-bs-toggle="tab"]', function (e) {
				var target = $(e.target).attr('data-bs-target');
				if (!target) {
					return;
				}
				$(target).find('.editor').each(function () {
					var $ta = $(this);
					if ($ta.next('.note-editor').length) {
						$ta.summernote('code', $ta.summernote('code'));
					}
				});
			});
		}

		/**
		 * Language tabs: hover-only tooltip on inner span (not on tab toggle — avoids stuck tooltip on focus).
		 */
		var $langTabs = $('.form-language-tabs');
		if ($langTabs.length && App.initHoverOnlyTooltips) {
			$langTabs.each(function () {
				var $root = $(this);
				App.initHoverOnlyTooltips($root.find('.js-hover-only-tooltip'));
				var langTabRoot = $root[0];
				$root.on('shown.bs.tab mousedown', '[data-bs-toggle="tab"]', function () {
					App.hideHoverOnlyTooltipsIn(langTabRoot);
				});
			});
		}

		// After Select2 (and other plugins) — every form starts ready to type
		focusPrimaryFormField();
		window.setTimeout(focusPrimaryFormField, 0);

		// Baseline after widgets settle (Select2 / Tempus / Trumbowyg / inputmask / phone / title-case)
		window.setTimeout(captureBaseline, 50);
		window.setTimeout(captureBaseline, 300);
		window.setTimeout(captureBaseline, 700);

		$form.on('submit', function () {
			allowLeave = true;
		});

		$(document).on('click.formUnsaved', 'a[href]', function (e) {
			if (allowLeave || !isFormDirty()) {
				return;
			}
			var $a = $(this);
			var href = $a.attr('href') || '';
			if (!shouldGuardNavigationLink($a, href)) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			App.confirmLeave({
				onConfirm: function () {
					leaveTo(href);
				}
			});
		});

		$(window).on('beforeunload.formUnsaved', function (e) {
			if (allowLeave || !isFormDirty()) {
				return;
			}
			e.preventDefault();
			e.returnValue = '';
			return '';
		});
	});
})(window, jQuery);
