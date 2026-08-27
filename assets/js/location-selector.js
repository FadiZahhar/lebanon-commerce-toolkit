(function () {
	'use strict';

	function fillDistricts(container) {
		var governorate = container.querySelector('[data-lct-governorate]');
		var district = container.querySelector('[data-lct-district]');

		if (!governorate || !district || !window.lctLocationData) {
			return;
		}

		var selected = district.getAttribute('data-selected') || district.value;
		var options = window.lctLocationData.districts[governorate.value] || [];

		district.innerHTML = '';
		var placeholder = document.createElement('option');
		placeholder.value = '';
		placeholder.textContent = window.lctLocationData.placeholder;
		district.appendChild(placeholder);

		options.forEach(function (item) {
			var option = document.createElement('option');
			option.value = item.value;
			option.textContent = item.label;
			option.selected = selected === item.value;
			district.appendChild(option);
		});

		district.disabled = !governorate.value;
		district.removeAttribute('data-selected');
	}

	function initialize(container) {
		fillDistricts(container);
		var governorate = container.querySelector('[data-lct-governorate]');
		if (governorate) {
			governorate.addEventListener('change', function () {
				fillDistricts(container);
			});
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-lct-location-selector]').forEach(initialize);
	});
}());
