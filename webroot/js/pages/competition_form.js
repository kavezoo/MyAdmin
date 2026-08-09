/**
 * Competition form — text template apply + city AJAX Select2.
 *
 * Config (MyAdmin.config):
 *   templateApplyUrl  — /…/competition-text-templates/applyData (append /{id})
 *   cityOptionsUrl    — city Select2 AJAX
 *   selectCity        — empty label
 *
 * Messages (MyAdmin.messages):
 *   applyTemplateTitle / applyTemplateConfirm / applyTemplateReplace
 *   yes / cancelButton
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};
	var cfg = App.config || {};
	var msg = App.messages || {};

	var APPLY_FIELDS = [
		'description'
	];

	function localeSlug(locale) {
		return String(locale || '')
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-|-$/g, '') || 'locale';
	}

	function fieldSelector(locale, field, isDefault) {
		var slug = localeSlug(locale);
		var id = field.replace(/_/g, '-') + '-' + slug;
		var $el = $('#' + id);
		if (!$el.length) {
			var nameAttr = isDefault
				? field
				: '_translations[' + locale + '][' + field + ']';
			$el = $('[name="' + nameAttr + '"]');
		}

		return $el;
	}

	function normalizeHtml(value) {
		return String(value || '')
			.replace(/\r\n/g, '\n')
			.replace(/\u00a0/g, ' ')
			.trim();
	}

	function getFieldValue($el) {
		if (!$el || !$el.length) {
			return '';
		}
		if ($el.hasClass('editor') && $el.next('.note-editor').length && typeof $el.summernote === 'function') {
			return $el.summernote('code');
		}
		if ($el.hasClass('editor') && $el.data('trumbowyg')) {
			var parts = $el.data('htmlStyleBlocks') || [];
			return parts.join('\n') + ($el.trumbowyg('html') || '');
		}

		return $el.val() || '';
	}

	function setFieldValue(locale, field, value, isDefault) {
		var $el = fieldSelector(locale, field, isDefault);
		if (!$el.length) {
			return;
		}
		var str = value == null ? '' : String(value);
		if ($el.hasClass('editor') && $el.next('.note-editor').length && typeof $el.summernote === 'function') {
			$el.summernote('code', str);
			$el.val(str);
		} else if ($el.hasClass('editor') && $el.data('trumbowyg')) {
			var parts = str.match(/<style\b[^>]*>[\s\S]*?<\/style>/gi) || [];
			var body = str.replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '');
			$el.data('htmlStyleBlocks', parts);
			$el.trumbowyg('html', body);
			$el.val(parts.join('\n') + body);
		} else {
			$el.val(str);
		}
		$el.trigger('change');
	}

	function collectTextSnapshot(fieldsByLocale, defaultLocale) {
		var snap = {};
		Object.keys(fieldsByLocale || {}).forEach(function (locale) {
			var row = fieldsByLocale[locale] || {};
			var isDefault = locale === defaultLocale;
			snap[locale] = {};
			APPLY_FIELDS.forEach(function (field) {
				if (Object.prototype.hasOwnProperty.call(row, field)) {
					snap[locale][field] = normalizeHtml(row[field]);
				} else {
					var $el = fieldSelector(locale, field, isDefault);
					snap[locale][field] = normalizeHtml(getFieldValue($el));
				}
			});
		});

		return snap;
	}

	function readCurrentTextSnapshot(defaultLocaleHint) {
		var snap = {};
		var defaultLocale = defaultLocaleHint || '';
		$('#form-horizontal').find('textarea.editor').each(function () {
			var $el = $(this);
			var name = String($el.attr('name') || '');
			var locale = defaultLocale;
			var field = '';
			var m = name.match(/^_translations\[([^\]]+)\]\[([^\]]+)\]$/);
			if (m) {
				locale = m[1];
				field = m[2];
			} else if (APPLY_FIELDS.indexOf(name) !== -1) {
				field = name;
				if (!locale) {
					locale = $el.data('i18n-locale') || 'default';
				}
			} else {
				return;
			}
			if (APPLY_FIELDS.indexOf(field) === -1) {
				return;
			}
			if (!snap[locale]) {
				snap[locale] = {};
			}
			snap[locale][field] = normalizeHtml(getFieldValue($el));
		});

		return snap;
	}

	function textSnapshotHasContent(snap) {
		var locales = Object.keys(snap || {});
		for (var i = 0; i < locales.length; i++) {
			var row = snap[locales[i]] || {};
			for (var j = 0; j < APPLY_FIELDS.length; j++) {
				if (normalizeHtml(row[APPLY_FIELDS[j]])) {
					return true;
				}
			}
		}

		return false;
	}

	function textSnapshotsDiffer(a, b) {
		if (!a || !b) {
			return textSnapshotHasContent(a) || textSnapshotHasContent(b);
		}
		var locales = {};
		Object.keys(a).forEach(function (k) { locales[k] = true; });
		Object.keys(b).forEach(function (k) { locales[k] = true; });
		return Object.keys(locales).some(function (locale) {
			return APPLY_FIELDS.some(function (field) {
				return normalizeHtml((a[locale] || {})[field]) !== normalizeHtml((b[locale] || {})[field]);
			});
		});
	}

	function applyTemplatePayload(payload, state) {
		if (!payload || !payload.fields) {
			return;
		}
		var defaultLocale = payload.defaultLocale || 'en_GB';
		Object.keys(payload.fields).forEach(function (locale) {
			var row = payload.fields[locale] || {};
			var isDefault = locale === defaultLocale;
			APPLY_FIELDS.forEach(function (field) {
				if (Object.prototype.hasOwnProperty.call(row, field)) {
					setFieldValue(locale, field, row[field], isDefault);
				}
			});
		});
		if (payload.id) {
			$('#competition-text-template-id').val(String(payload.id));
		}
		state.appliedTemplateId = parseInt(payload.id, 10) || 0;
		state.lastAppliedSnapshot = collectTextSnapshot(payload.fields, defaultLocale);
		state.defaultLocale = defaultLocale;
		if (typeof App.recaptureFormBaseline === 'function') {
			window.setTimeout(App.recaptureFormBaseline, 100);
		}
	}

	function confirmReplaceTemplate() {
		var title = msg.applyTemplateTitle || 'Replace text template?';
		var text = msg.applyTemplateConfirm
			|| 'Choosing another template replaces the description text. Any edits you made will be lost.';
		var confirmText = msg.applyTemplateReplace || msg.yes || 'Replace';
		var cancelText = msg.cancelButton || 'Cancel';
		var opts = {
			icon: 'warning',
			title: title,
			text: text,
			showCancelButton: true,
			focusCancel: true,
			confirmButtonText: confirmText,
			cancelButtonText: cancelText
		};
		if (typeof App.swal === 'function') {
			return App.swal(opts);
		}
		if (window.Swal && typeof window.Swal.fire === 'function') {
			return window.Swal.fire(opts);
		}
		if (typeof App.confirmDelete === 'function') {
			return App.confirmDelete({
				title: title,
				text: text,
				confirmButtonText: confirmText,
				icon: 'question'
			});
		}

		return Promise.resolve({ isConfirmed: false });
	}

	function initTemplateSelect() {
		var $sel = $('#competition-text-template-id');
		var applyUrl = cfg.templateApplyUrl || '';
		if (!$sel.length || !applyUrl || cfg.contentLocked) {
			return;
		}

		var state = {
			appliedTemplateId: parseInt($sel.val(), 10) || 0,
			lastAppliedSnapshot: null,
			defaultLocale: '',
			suppressChange: false
		};
		$sel.data('previousTemplateId', $sel.val() || '');

		function revertSelect() {
			state.suppressChange = true;
			var prev = $sel.data('previousTemplateId');
			$sel.val(prev === undefined || prev === null ? '' : prev).trigger('change');
			state.suppressChange = false;
		}

		function fetchAndApply(id) {
			var url = applyUrl.replace(/\/$/, '') + '/' + encodeURIComponent(id);
			return $.ajax({
				url: url,
				method: 'GET',
				dataType: 'json',
				cache: false
			}).done(function (data) {
				if (data && data.success === false) {
					revertSelect();
					return;
				}
				applyTemplatePayload(data, state);
				$sel.data('previousTemplateId', String(id));
			}).fail(function () {
				revertSelect();
			});
		}

		function needsReplaceConfirm(newId) {
			if (state.appliedTemplateId > 0 && state.appliedTemplateId !== newId) {
				return true;
			}
			if (state.appliedTemplateId < 1) {
				var current = readCurrentTextSnapshot(state.defaultLocale);
				if (textSnapshotHasContent(current)) {
					return true;
				}
			} else if (state.lastAppliedSnapshot) {
				var now = readCurrentTextSnapshot(state.defaultLocale);
				if (textSnapshotsDiffer(state.lastAppliedSnapshot, now)) {
					return true;
				}
			}

			return false;
		}

		$sel.on('change', function () {
			if (state.suppressChange) {
				return;
			}
			var id = parseInt($sel.val(), 10) || 0;
			if (id < 1) {
				$sel.data('previousTemplateId', '');
				return;
			}
			if (id === state.appliedTemplateId && state.lastAppliedSnapshot) {
				$sel.data('previousTemplateId', String(id));
				return;
			}

			var run = function () {
				fetchAndApply(id);
			};

			if (needsReplaceConfirm(id)) {
				confirmReplaceTemplate().then(function (result) {
					if (result && result.isConfirmed) {
						run();
					} else {
						revertSelect();
					}
				});
			} else {
				run();
			}
		});
	}

	function initCitySelect() {
		var $city = $('.js-competition-city');
		if (!$city.length || !$.fn.select2) {
			return;
		}
		var ajaxUrl = $city.data('ajax-url') || cfg.cityOptionsUrl || '';
		if (!ajaxUrl) {
			return;
		}
		if ($city.data('select2')) {
			$city.select2('destroy');
		}
		$city.select2({
			theme: 'bootstrap-5',
			width: '100%',
			allowClear: true,
			placeholder: cfg.selectCity || $city.find('option[value=""]').text() || '',
			minimumInputLength: 2,
			ajax: {
				url: ajaxUrl,
				dataType: 'json',
				delay: 250,
				data: function (params) {
					var countryId = parseInt($('#country-id').val(), 10)
						|| parseInt($city.data('country-id'), 10)
						|| 0;
					return {
						q: params.term || '',
						page: params.page || 1,
						country_id: countryId
					};
				},
				processResults: function (data) {
					return {
						results: (data && data.results) ? data.results : [],
						pagination: (data && data.pagination) ? data.pagination : { more: false }
					};
				}
			}
		});

		$('#country-id').on('change', function () {
			var cid = parseInt($(this).val(), 10) || 0;
			$city.data('country-id', cid);
			$city.val(null).trigger('change');
		});
	}

	function lockCompetitionForm() {
		var $form = $('#form-horizontal');
		if (!$form.length) {
			return;
		}

		$form.find('input:not([type="hidden"]), select, textarea').prop('disabled', true);
		$form.find('select.js-example-basic-single').each(function () {
			var $el = $(this);
			$el.prop('disabled', true);
			if ($el.data('select2')) {
				$el.trigger('change.select2');
			}
		});
		$form.find('textarea.editor').each(function () {
			var $ta = $(this);
			if ($ta.next('.note-editor').length && typeof $ta.summernote === 'function') {
				try {
					$ta.summernote('disable');
				} catch (e) {
					/* ignore */
				}
			}
		});
		$('#competitionPlaceholders .competition-placeholder-chip')
			.prop('disabled', true)
			.addClass('disabled')
			.attr('aria-disabled', 'true');
		$form.find('.input-group-text, [data-td-toggle]').css('pointer-events', 'none');
	}

	$(function () {
		cfg = App.config || cfg;
		msg = App.messages || msg;
		initTemplateSelect();
		initCitySelect();
		if (cfg.contentLocked) {
			lockCompetitionForm();
			setTimeout(lockCompetitionForm, 200);
			setTimeout(lockCompetitionForm, 800);
		}
	});
})(window, jQuery);
