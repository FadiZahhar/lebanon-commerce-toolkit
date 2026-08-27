(function (blocks, components, element, i18n, blockEditor) {
	'use strict';

	if (!blocks || !components || !element || !i18n || !blockEditor) {
		return;
	}

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;
	var TextControl = components.TextControl;
	var Placeholder = components.Placeholder;

	blocks.registerBlockType('lebanon-commerce-toolkit/location-selector', {
		title: __('Lebanon Location Selector', 'lebanon-commerce-toolkit'),
		description: __('Governorate, district, and optional city/area fields.', 'lebanon-commerce-toolkit'),
		icon: 'location-alt',
		category: 'widgets',
		attributes: {
			showCity: { type: 'boolean', default: true },
			required: { type: 'boolean', default: false }
		},
		edit: function (props) {
			return el(
				element.Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Selector settings', 'lebanon-commerce-toolkit') },
						el(ToggleControl, {
							label: __('Show City / Area', 'lebanon-commerce-toolkit'),
							checked: props.attributes.showCity,
							onChange: function (value) { props.setAttributes({ showCity: value }); }
						}),
						el(ToggleControl, {
							label: __('Require fields', 'lebanon-commerce-toolkit'),
							checked: props.attributes.required,
							onChange: function (value) { props.setAttributes({ required: value }); }
						})
					)
				),
				el(
					Placeholder,
					{ icon: 'location-alt', label: __('Lebanon Location Selector', 'lebanon-commerce-toolkit') },
					__('The interactive selector is rendered on the front end.', 'lebanon-commerce-toolkit')
				)
			);
		},
		save: function () { return null; }
	});

	blocks.registerBlockType('lebanon-commerce-toolkit/secondary-price', {
		title: __('Lebanon Secondary Price', 'lebanon-commerce-toolkit'),
		description: __('Shows the configured informational secondary currency amount.', 'lebanon-commerce-toolkit'),
		icon: 'money-alt',
		category: 'widgets',
		attributes: {
			productId: { type: 'number', default: 0 },
			amount: { type: 'string', default: '' }
		},
		edit: function (props) {
			return el(
				element.Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Price source', 'lebanon-commerce-toolkit') },
						el(TextControl, {
							label: __('Product ID (0 = current product)', 'lebanon-commerce-toolkit'),
							type: 'number',
							value: props.attributes.productId || 0,
							onChange: function (value) { props.setAttributes({ productId: parseInt(value || 0, 10) }); }
						}),
						el(TextControl, {
							label: __('Explicit base amount (optional)', 'lebanon-commerce-toolkit'),
							value: props.attributes.amount || '',
							onChange: function (value) { props.setAttributes({ amount: value }); }
						})
					)
				),
				el(
					Placeholder,
					{ icon: 'money-alt', label: __('Lebanon Secondary Price', 'lebanon-commerce-toolkit') },
					__('The configured secondary price is rendered on the front end.', 'lebanon-commerce-toolkit')
				)
			);
		},
		save: function () { return null; }
	});
}(window.wp && window.wp.blocks, window.wp && window.wp.components, window.wp && window.wp.element, window.wp && window.wp.i18n, window.wp && window.wp.blockEditor));
