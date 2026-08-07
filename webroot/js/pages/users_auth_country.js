/**
 * Auth country Select2 (register / complete-profile / profile edit):
 * searchable + flag icons.
 *
 * Profile / complete-profile: AJAX-refill #club-id (never navigate — no leave dialog).
 * Register (no club select): optional page reload with ?country_id=.
 *
 * Important: never leave #club-id disabled on submit — browsers omit disabled fields
 * (that caused “Please select your club.” after AJAX refresh).
 */
(function ($) {
	'use strict';

	function flagOptionFactory(cfg) {
		return function (opt) {
			if (!opt.id) {
				return opt.text;
			}
			var $wrap = $('<span class="select2-flag-option"></span>');
			var iso = cfg.flags && cfg.flags[opt.id] ? String(cfg.flags[opt.id]) : '';
			if (iso && cfg.flagBase) {
				$wrap.append(
					$('<img>', {
						'class': 'select2-flag',
						src: cfg.flagBase + iso + '.png',
						alt: '',
						width: 20,
						height: 20,
						loading: 'lazy'
					})
				);
			}
			$wrap.append(document.createTextNode(opt.text));
			return $wrap;
		};
	}

	function resolveClubsUrl($country, cfg) {
		return String(
			$country.data('clubs-url')
			|| cfg.clubsUrl
			|| ''
		).trim();
	}

	function resolveIncludeClubId($country, cfg) {
		var fromData = parseInt($country.data('include-club-id'), 10);
		if (!isNaN(fromData) && fromData > 0) {
			return fromData;
		}
		return parseInt(cfg.includeClubId, 10) || 0;
	}

	function destroyClubSelect2($club) {
		if (!$.fn.select2) {
			return;
		}
		if ($club.data('select2') || $club.hasClass('select2-hidden-accessible')) {
			try {
				$club.select2('destroy');
			} catch (err) { /* ignore */ }
		}
	}

	function initClubSelect2($club, placeholder) {
		if (!$.fn.select2) {
			return;
		}
		$club.select2({
			theme: 'bootstrap-5',
			width: '100%',
			minimumResultsForSearch: 0,
			dropdownParent: $(document.body),
			placeholder: placeholder || undefined,
			allowClear: false
		});
	}

	function setClubOptions($club, clubs, cfg) {
		var placeholder = cfg.clubPlaceholder || $club.data('placeholder') || '';
		var empty = !clubs || ($.isArray(clubs) && clubs.length === 0) || $.isEmptyObject(clubs);

		destroyClubSelect2($club);

		$club.empty();
		$club.append(new Option(placeholder || '', '', true, true));
		if (!empty) {
			$.each(clubs, function (id, name) {
				if (id === '' || id === null || typeof id === 'undefined') {
					return;
				}
				$club.append(new Option(String(name), String(id), false, false));
			});
		}

		// Never disable: disabled <select> is omitted from POST → “Please select your club.”
		$club.prop('disabled', false);
		$club.prop('required', !empty);
		$club.val('');

		initClubSelect2($club, placeholder);
	}

	function updateClubHints(countryId, empty) {
		var $needCountry = $('.js-club-need-country');
		var $emptyWarn = $('.js-club-empty-warning');
		if (!countryId) {
			$needCountry.removeClass('d-none');
			$emptyWarn.addClass('d-none');
			return;
		}
		$needCountry.addClass('d-none');
		if (empty) {
			$emptyWarn.removeClass('d-none');
		} else {
			$emptyWarn.addClass('d-none');
		}
	}

	function loadClubsForCountry(countryId, clubsUrl, includeClubId, cfg) {
		var $club = $('#club-id');
		if (!$club.length || !clubsUrl) {
			return;
		}
		var params = { country_id: countryId || '' };
		if (includeClubId > 0) {
			params.include_club_id = includeClubId;
		}
		$.ajax({
			url: clubsUrl,
			method: 'GET',
			dataType: 'json',
			cache: false,
			data: params
		}).done(function (data) {
			var clubs = (data && data.clubs) ? data.clubs : {};
			var empty = !!(data && data.empty);
			setClubOptions($club, clubs, cfg);
			updateClubHints(countryId, empty || !countryId);
		}).fail(function () {
			setClubOptions($club, {}, cfg);
			updateClubHints(countryId, !!countryId);
			var App = window.MyAdmin || {};
			if (App.alertError) {
				App.alertError(cfg.clubsLoadFailed || 'Failed to load clubs.');
			}
		});
	}

	function ensureClubFieldSubmittable() {
		var $club = $('#club-id');
		if (!$club.length) {
			return;
		}
		$club.prop('disabled', false);
		// Sync Select2 → native <select> before serialize/submit
		if ($club.hasClass('select2-hidden-accessible')) {
			var selected = $club.select2('data');
			if (selected && selected.length && selected[0].id !== undefined && selected[0].id !== null) {
				$club.val(String(selected[0].id));
			}
		}
	}

	$(function () {
		var cfg = window.UsersAuthCountry || window.UsersRegister || {};
		var $country = $('#country-id');
		if (!$country.length) {
			return;
		}

		var $club = $('#club-id');
		var hasClubSelect = $club.length > 0;

		if ($.fn.select2) {
			var format = flagOptionFactory(cfg);
			$country.select2({
				theme: 'bootstrap-5',
				width: '100%',
				minimumResultsForSearch: 0,
				dropdownParent: $(document.body),
				placeholder: $country.data('placeholder') || '',
				templateResult: format,
				templateSelection: format,
				language: {
					noResults: function () {
						return cfg.noResults || 'No results found.';
					},
					searching: function () {
						return cfg.searchPlaceholder || 'Search...';
					}
				}
			});

			$country.on('select2:open', function () {
				var $search = $('.select2-container--open .select2-search__field');
				if ($search.length) {
					$search.trigger('focus');
				}
			});

			var $language = $('#language-id');
			if ($language.length) {
				$language.select2({
					theme: 'bootstrap-5',
					width: '100%',
					minimumResultsForSearch: 8,
					dropdownParent: $(document.body),
					placeholder: $language.data('placeholder') || '',
					allowClear: true
				});
			}
		}

		$country.off('change.usersAuthCountry').on('change.usersAuthCountry', function (e) {
			if (hasClubSelect) {
				if (e && typeof e.preventDefault === 'function') {
					e.preventDefault();
				}
				var id = String($(this).val() || '');
				var clubsUrl = resolveClubsUrl($country, cfg) || '/clubs-for-country';
				if (!id) {
					setClubOptions($club, {}, cfg);
					updateClubHints(0, false);
					return;
				}
				loadClubsForCountry(id, clubsUrl, resolveIncludeClubId($country, cfg), cfg);
				return;
			}

			var idOnly = String($(this).val() || '');
			if (!idOnly) {
				return;
			}
			var base = cfg.reloadUrl
				|| $country.data('reload-url')
				|| cfg.registerUrl
				|| $country.data('register-url')
				|| '';
			if (!base) {
				return;
			}
			window.location.href = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'country_id=' + encodeURIComponent(idOnly);
		});

		$('#form-horizontal, #complete-profile-form, #profile-form').on('submit.usersAuthClub', function () {
			ensureClubFieldSubmittable();
		});
	});
})(jQuery);
