(function ($) {
	'use strict';

	if (!$ || !window.lctLocationData) {
		return;
	}

	function updateAddress(type) {
		var $country = $('#' + type + '_country');
		var $state = $('#' + type + '_state');
		var $district = $('#' + type + '_lct_district');
		var $row = $district.closest('.form-row');

		if (!$district.length) {
			return;
		}

		var isLebanon = $country.val() === window.lctLocationData.country;
		var isRequired = isLebanon && Boolean(window.lctLocationData.requireField);
		$row.toggleClass('lct-field--hidden', !isLebanon);
		$row.toggleClass('validate-required', isRequired);
		$district.prop('disabled', !isLebanon);
		$district.prop('required', isRequired).attr('aria-required', isRequired ? 'true' : 'false');

		if (!isLebanon) {
			$district.val('').trigger('change.select2');
			return;
		}

		var current = $district.val();
		var options = window.lctLocationData.districts[$state.val()] || [];
		$district.empty().append($('<option>', {
			value: '',
			text: window.lctLocationData.placeholder
		}));

		options.forEach(function (item) {
			$district.append($('<option>', {
				value: item.value,
				text: item.label,
				selected: item.value === current
			}));
		});

		if (!options.some(function (item) { return item.value === current; })) {
			$district.val('');
		}

		$district.trigger('change.select2');
	}

	function updateAll() {
		updateAddress('billing');
		updateAddress('shipping');
	}

	$(document.body).on('country_to_state_changed updated_checkout', updateAll);
	$(document).on('change', '#billing_country, #billing_state, #shipping_country, #shipping_state', updateAll);
	$(document).on('change', '#billing_lct_district, #shipping_lct_district', function () {
		if ($('form.checkout').length) {
			$(document.body).trigger('update_checkout');
		}
	});

	$(updateAll);
}(window.jQuery));
