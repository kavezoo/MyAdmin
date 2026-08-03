<?php
/**
 * Flash → Simple Notify helper (JeffAdmin5 mintájára).
 * Több üzenet is megjelenhet egyszerre (toast).
 *
 * @var \App\View\AppView $this
 */
?>
function flashMessage(title, text, status) {
	if (typeof Notify === 'undefined') {
		if (window.console && console.warn) {
			console.warn('Simple Notify is not loaded.', title, text);
		}
		return;
	}
	new Notify({
		status: status || 'info',
		title: title || '',
		text: text || '',
		effect: 'slide',
		speed: 500,
		customClass: '',
		customIcon: '',
		showIcon: true,
		showCloseButton: true,
		autoclose: true,
		autotimeout: 5000,
		notificationsGap: null,
		notificationsPadding: null,
		position: 'bottom left',
		type: 'outline',
		customWrapper: ''
	});
}
