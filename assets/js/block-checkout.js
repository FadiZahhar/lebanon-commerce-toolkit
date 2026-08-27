(function () {
	'use strict';

	if (!window.lctLocationData) {
		return;
	}

	var fieldId = window.lctLocationData.fieldId;
	var lastValue = null;
	var updateTimer = null;
	var scanFrame = null;
	var unsubscribe = null;

	function blocksCheckoutApi() {
		return window.wc && window.wc.blocksCheckout
			? window.wc.blocksCheckout
			: null;
	}

	function send(value) {
		var api = blocksCheckoutApi();
		var normalized = typeof value === 'string' ? value : '';

		if (
			normalized === lastValue ||
			!api ||
			typeof api.extensionCartUpdate !== 'function'
		) {
			return;
		}

		lastValue = normalized;
		api.extensionCartUpdate({
			namespace: window.lctLocationData.namespace,
			data: { district: normalized },
			overwriteDirtyCustomerData: false
		}).catch(function (error) {
			if (lastValue === normalized) {
				lastValue = null;
			}

			if (
				window.wc &&
				window.wc.wcBlocksData &&
				typeof window.wc.wcBlocksData.processErrorResponse === 'function'
			) {
				window.wc.wcBlocksData.processErrorResponse(error);
			}
		});
	}

	function scheduleUpdate(value) {
		window.clearTimeout(updateTimer);
		updateTimer = window.setTimeout(function () {
			send(value);
		}, 180);
	}

	function getAddressValue(address, key) {
		if (!address || typeof address !== 'object') {
			return '';
		}

		if (Object.prototype.hasOwnProperty.call(address, key)) {
			return address[key] || '';
		}

		if (
			address.additionalFields &&
			Object.prototype.hasOwnProperty.call(address.additionalFields, key)
		) {
			return address.additionalFields[key] || '';
		}

		if (
			address.additional_fields &&
			Object.prototype.hasOwnProperty.call(address.additional_fields, key)
		) {
			return address.additional_fields[key] || '';
		}

		return '';
	}

	function getCartStoreSelector() {
		if (
			!window.wp ||
			!window.wp.data ||
			typeof window.wp.data.select !== 'function' ||
			!window.wc ||
			!window.wc.wcBlocksData ||
			!window.wc.wcBlocksData.cartStore
		) {
			return null;
		}

		try {
			return window.wp.data.select(window.wc.wcBlocksData.cartStore);
		} catch (error) {
			return null;
		}
	}

	function readDistrictFromCartStore() {
		var selector = getCartStoreSelector();

		if (!selector || typeof selector.getCustomerData !== 'function') {
			return null;
		}

		var customerData = selector.getCustomerData();
		if (!customerData || typeof customerData !== 'object') {
			return null;
		}

		var shipping = customerData.shippingAddress || customerData.shipping_address || {};
		var billing = customerData.billingAddress || customerData.billing_address || {};
		var needsShipping = typeof selector.getNeedsShipping === 'function'
			? Boolean(selector.getNeedsShipping())
			: true;
		var activeAddress = needsShipping && (shipping.country || getAddressValue(shipping, fieldId))
			? shipping
			: billing;
		var country = activeAddress.country || '';

		return country === window.lctLocationData.country
			? String(getAddressValue(activeAddress, fieldId) || '')
			: '';
	}

	function syncFromCartStore() {
		var value = readDistrictFromCartStore();

		if (value === null) {
			return false;
		}

		scheduleUpdate(value);
		return true;
	}

	function isDistrictField(element) {
		if (!element || !element.matches || !element.matches('select, input')) {
			return false;
		}

		var signature = [
			element.name || '',
			element.id || '',
			element.getAttribute('data-field-key') || '',
			element.getAttribute('aria-label') || ''
		].join(' ').toLowerCase();

		return signature.indexOf(fieldId.toLowerCase()) !== -1 ||
			signature.indexOf('lebanon-commerce-toolkit-district') !== -1;
	}

	function fieldGroup(element) {
		var signature = [
			element.name || '',
			element.id || '',
			element.getAttribute('data-field-key') || '',
			element.closest('[class*="shipping"], [id*="shipping"]') ? 'shipping-container' : '',
			element.closest('[class*="billing"], [id*="billing"]') ? 'billing-container' : ''
		].join(' ').toLowerCase();

		if (signature.indexOf('shipping') !== -1) {
			return 'shipping';
		}

		if (signature.indexOf('billing') !== -1) {
			return 'billing';
		}

		return 'unknown';
	}

	function readDistrictFromDom() {
		var values = {
			shipping: '',
			billing: '',
			unknown: ''
		};

		document.querySelectorAll('select, input').forEach(function (field) {
			if (!isDistrictField(field)) {
				return;
			}

			var group = fieldGroup(field);
			if (field.value || !values[group]) {
				values[group] = field.value || '';
			}
		});

		return values.shipping || values.billing || values.unknown;
	}

	function detectExistingField() {
		scanFrame = null;

		if (!syncFromCartStore()) {
			scheduleUpdate(readDistrictFromDom());
		}
	}

	function scheduleScan() {
		if (scanFrame !== null) {
			return;
		}

		scanFrame = window.requestAnimationFrame(detectExistingField);
	}

	function subscribeToCartStore() {
		if (
			unsubscribe ||
			!window.wp ||
			!window.wp.data ||
			typeof window.wp.data.subscribe !== 'function' ||
			!getCartStoreSelector()
		) {
			return;
		}

		unsubscribe = window.wp.data.subscribe(function () {
			syncFromCartStore();
		});
	}

	document.addEventListener('change', function (event) {
		if (isDistrictField(event.target)) {
			if (!syncFromCartStore()) {
				scheduleUpdate(event.target.value || '');
			}
		}
	});

	document.addEventListener('DOMContentLoaded', function () {
		subscribeToCartStore();
		scheduleScan();
	});

	new MutationObserver(function () {
		subscribeToCartStore();
		scheduleScan();
	}).observe(document.documentElement, {
		childList: true,
		subtree: true
	});
}());
