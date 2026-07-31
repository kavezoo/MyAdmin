/**
 * Shared app helpers for MyAdmin / future CakePHP 5 templates.
 * Layout-level: safe to load on every admin page.
 *
 * cakephp-template: layout-js
 *
 * Translated strings: window.MyAdmin.messages (set from PHP layout via __()).
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};

	App.config = App.config || {};
	App.messages = App.messages || {};

	var t = function (key, fallback) {
		var messages = App.messages || {};
		return messages[key] || fallback;
	};

	App.initTooltips = function (selector) {
		selector = selector || '[data-bs-toggle="tooltip"]';
		$(selector).each(function () {
			bootstrap.Tooltip.getOrCreateInstance(this);
		});
	};

	/**
	 * SweetAlert2 delete confirmation.
	 */
	App.confirmDelete = function (options) {
		options = options || {};
		return Swal.fire({
			icon: options.icon || 'warning',
			title: options.title || t('deleteTitle', 'Delete'),
			text: options.text || t('deleteConfirm', 'Do you really want to delete the selected record?'),
			showCancelButton: true,
			focusCancel: true,
			confirmButtonText: options.confirmButtonText || t('deleteButton', 'Delete'),
			cancelButtonText: options.cancelButtonText || t('cancelButton', 'Cancel'),
			confirmButtonColor: options.confirmButtonColor || '#dc3545',
			cancelButtonColor: options.cancelButtonColor || '#6c757d',
			reverseButtons: true
		}).then(function (result) {
			if (result.isConfirmed && typeof options.onConfirm === 'function') {
				options.onConfirm();
			}
			return result;
		});
	};

	/**
	 * SweetAlert2 message (replaces window.alert everywhere in Admin).
	 *
	 * @param {string|object} options Message string, or { icon, title, text, confirmButtonText }
	 * @returns {Promise}
	 */
	App.alert = function (options) {
		if (typeof options === 'string') {
			options = { text: options };
		}
		options = options || {};
		var icon = options.icon || 'error';
		var defaultTitle = icon === 'error'
			? t('errorTitle', 'Error')
			: (icon === 'success' ? t('successTitle', 'Success') : t('infoTitle', 'Info'));

		return Swal.fire({
			icon: icon,
			title: options.title || defaultTitle,
			text: options.text || options.message || '',
			confirmButtonText: options.confirmButtonText || t('okButton', 'OK'),
			confirmButtonColor: options.confirmButtonColor || '#0d6efd'
		});
	};

	/** Shortcut: error icon. */
	App.alertError = function (text, title) {
		return App.alert({
			icon: 'error',
			title: title,
			text: text
		});
	};

	$(function () {
		App.initTooltips();

		$('#btn-delete').on('click', function (e) {
			e.preventDefault();
			App.confirmDelete({
				onConfirm: function () {
					Swal.fire({
						icon: 'info',
						title: t('deleteTitle', 'Delete'),
						text: t('deleteNotWired', 'Delete functionality will be connected later.')
					});
				}
			});
		});
	});
})(window, jQuery);
