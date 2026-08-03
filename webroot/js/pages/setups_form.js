/**
 * Setup form: type → value widget; name → slug suggestion.
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};
	var cfg = App.config || {};

	var slugify = function (text) {
		var s = String(text || '').toLowerCase().trim();
		try {
			s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		} catch (e) {
			/* older browsers */
		}
		s = s.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').replace(/_+/g, '_');
		return s;
	};

	var activePanel = function () {
		return $('#setup-value-widgets .setup-value-panel:not([hidden])').first();
	};

	var syncValueNames = function () {
		$('#setup-value-widgets .js-setup-value').each(function () {
			$(this).removeAttr('name');
		});
		var $panel = activePanel();
		var $field = $panel.find('.js-setup-value').first();
		if (!$field.length) {
			return;
		}
		if ($field.is(':checkbox')) {
			$field.attr('name', 'value');
		} else {
			$field.attr('name', 'value');
		}
	};

	var showType = function (type) {
		$('#setup-value-widgets .setup-value-panel').each(function () {
			var match = $(this).data('setup-type') === type;
			$(this).prop('hidden', !match);
		});
		syncValueNames();
	};

	$(function () {
		var $type = $('#type');
		var $name = $('#name');
		var $slug = $('#slug');
		if (!$type.length) {
			return;
		}

		var slugManual = false;
		var initialSlug = String($slug.val() || '');
		var suggested = slugify($name.val());
		if (initialSlug !== '' && initialSlug !== suggested) {
			slugManual = true;
		}

		$slug.on('input', function () {
			slugManual = true;
		});

		$name.on('input', function () {
			if (slugManual) {
				return;
			}
			$slug.val(slugify($name.val()));
		});

		$type.on('change', function () {
			showType(String($type.val() || 'string'));
		});

		showType(String($type.val() || cfg.currentType || 'string'));

		$('#form-horizontal').on('submit', function () {
			syncValueNames();
			var $field = activePanel().find('.js-setup-value').first();
			if ($field.is(':checkbox') && !$field.is(':checked')) {
				// Ensure boolean false is posted
				if (!$field.next('input[type=hidden][name=value]').length) {
					$('<input type="hidden" name="value" value="0">').insertAfter($field);
				}
			} else {
				activePanel().find('input[type=hidden][name=value]').remove();
			}
		});
	});
})(window, jQuery);
