<?php
/**
 * Plugin settings screen.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

namespace ProSolutions\LebanonCommerceToolkit\Admin;

use ProSolutions\LebanonCommerceToolkit\Contracts\Service;
use ProSolutions\LebanonCommerceToolkit\Core\Options;
use ProSolutions\LebanonCommerceToolkit\Domain\Locations\LocationRepository;

/**
 * Registers a focused settings page under WooCommerce.
 */
final class Settings implements Service {
	/**
	 * Options service.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Locations repository.
	 *
	 * @var LocationRepository
	 */
	private $locations;

	/**
	 * Constructor.
	 *
	 * @param Options            $options   Options service.
	 * @param LocationRepository $locations Locations repository.
	 */
	public function __construct( Options $options, LocationRepository $locations ) {
		$this->options   = $options;
		$this->locations = $locations;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 60 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . LCT_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add the WooCommerce submenu.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Lebanon Commerce Toolkit', 'lebanon-commerce-toolkit' ),
			__( 'Lebanon Toolkit', 'lebanon-commerce-toolkit' ),
			'manage_woocommerce',
			'lebanon-commerce-toolkit',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the single settings array.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'lct_settings_group',
			Options::OPTION_KEY,
			array(
				'type'              => 'array',
				'default'           => Options::defaults(),
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * Sanitize every setting at the trust boundary.
	 *
	 * @param mixed $input Untrusted settings payload.
	 * @return array<string,string>
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = Options::defaults();
		$output   = $defaults;

		$checkboxes = array(
			'enable_locations',
			'require_district',
			'enable_phone',
			'enable_secondary_currency',
			'show_secondary_product',
			'show_secondary_cart',
			'delete_data_on_uninstall',
		);

		foreach ( $checkboxes as $key ) {
			$output[ $key ] = ! empty( $input[ $key ] ) ? 'yes' : 'no';
		}

		$output['phone_validation'] = isset( $input['phone_validation'] ) && 'off' === $input['phone_validation'] ? 'off' : 'relaxed';

		$currency_code = isset( $input['secondary_currency_code'] ) ? strtoupper( sanitize_key( $input['secondary_currency_code'] ) ) : 'LBP';
		$output['secondary_currency_code'] = substr( $currency_code, 0, 6 );
		$currency_symbol = isset( $input['secondary_currency_symbol'] )
			? sanitize_text_field( $input['secondary_currency_symbol'] )
			: 'LBP';
		$output['secondary_currency_symbol'] = function_exists( 'mb_substr' )
			? mb_substr( $currency_symbol, 0, 12 )
			: substr( $currency_symbol, 0, 12 );

		$exchange_rate = isset( $input['exchange_rate'] ) ? $this->sanitize_decimal( $input['exchange_rate'] ) : '';
		$output['exchange_rate'] = '' !== $exchange_rate && (float) $exchange_rate > 0 ? $exchange_rate : '';

		$rounding = isset( $input['rounding_increment'] ) ? $this->sanitize_decimal( $input['rounding_increment'] ) : '1000';
		$output['rounding_increment'] = '' !== $rounding && (float) $rounding >= 0 ? $rounding : '0';

		return $output;
	}

	/**
	 * Load admin-only styling.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'woocommerce_page_lebanon-commerce-toolkit' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'lct-admin',
			LCT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LCT_VERSION
		);
	}

	/**
	 * Add a direct settings link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public function add_action_links( array $links ) {
		$url = admin_url( 'admin.php?page=lebanon-commerce-toolkit' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'lebanon-commerce-toolkit' ) . '</a>' );
		return $links;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings           = $this->options->all();
		$governorate_count  = count( $this->locations->governorate_options( 'en_US' ) );
		$district_count     = count( $this->locations->flattened_district_options( 'en_US' ) );
		$shipping_zones_url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
		?>
		<div class="wrap lct-admin-wrap">
			<div class="lct-admin-hero">
				<div>
					<p class="lct-eyebrow"><?php esc_html_e( 'Pro-Solutions.net open-source solution', 'lebanon-commerce-toolkit' ); ?></p>
					<h1><?php esc_html_e( 'Lebanon Commerce Toolkit', 'lebanon-commerce-toolkit' ); ?></h1>
					<p><?php esc_html_e( 'Localize WooCommerce checkout and delivery for Lebanese stores without locking your site to a theme or external service.', 'lebanon-commerce-toolkit' ); ?></p>
				</div>
				<div class="lct-admin-stats" aria-label="<?php esc_attr_e( 'Dataset statistics', 'lebanon-commerce-toolkit' ); ?>">
					<strong><?php echo esc_html( $governorate_count ); ?></strong><span><?php esc_html_e( 'Governorates', 'lebanon-commerce-toolkit' ); ?></span>
					<strong><?php echo esc_html( $district_count ); ?></strong><span><?php esc_html_e( 'Districts', 'lebanon-commerce-toolkit' ); ?></span>
				</div>
			</div>

			<?php settings_errors(); ?>

			<form action="options.php" method="post">
				<?php settings_fields( 'lct_settings_group' ); ?>

				<div class="lct-settings-grid">
					<section class="lct-card">
						<h2><?php esc_html_e( 'Lebanese checkout locations', 'lebanon-commerce-toolkit' ); ?></h2>
						<p><?php esc_html_e( 'Adds governorate and district selection for Lebanon while keeping City / Area available for neighborhood-level delivery details.', 'lebanon-commerce-toolkit' ); ?></p>
						<?php $this->checkbox( 'enable_locations', __( 'Enable Lebanese checkout fields', 'lebanon-commerce-toolkit' ), $settings ); ?>
						<?php $this->checkbox( 'require_district', __( 'Require a district when Lebanon is selected', 'lebanon-commerce-toolkit' ), $settings ); ?>
					</section>

					<section class="lct-card">
						<h2><?php esc_html_e( 'Lebanese phone handling', 'lebanon-commerce-toolkit' ); ?></h2>
						<p><?php esc_html_e( 'Normalizes common local formats to +961 while preserving explicit international numbers.', 'lebanon-commerce-toolkit' ); ?></p>
						<?php $this->checkbox( 'enable_phone', __( 'Enable phone normalization', 'lebanon-commerce-toolkit' ), $settings ); ?>
						<label class="lct-field-label" for="lct-phone-validation"><?php esc_html_e( 'Validation mode', 'lebanon-commerce-toolkit' ); ?></label>
						<select id="lct-phone-validation" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[phone_validation]">
							<option value="relaxed" <?php selected( $settings['phone_validation'], 'relaxed' ); ?>><?php esc_html_e( 'Relaxed structural validation', 'lebanon-commerce-toolkit' ); ?></option>
							<option value="off" <?php selected( $settings['phone_validation'], 'off' ); ?>><?php esc_html_e( 'Normalize only; do not reject', 'lebanon-commerce-toolkit' ); ?></option>
						</select>
					</section>

					<section class="lct-card lct-card-wide">
						<h2><?php esc_html_e( 'Secondary currency display', 'lebanon-commerce-toolkit' ); ?></h2>
						<p><?php esc_html_e( 'Displays an informational secondary amount. It never changes the store currency, payment amount, taxes, or order totals.', 'lebanon-commerce-toolkit' ); ?></p>
						<?php $this->checkbox( 'enable_secondary_currency', __( 'Enable secondary currency display', 'lebanon-commerce-toolkit' ), $settings ); ?>
						<div class="lct-inline-fields">
							<div>
								<label class="lct-field-label" for="lct-currency-code"><?php esc_html_e( 'Code', 'lebanon-commerce-toolkit' ); ?></label>
								<input id="lct-currency-code" type="text" maxlength="6" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[secondary_currency_code]" value="<?php echo esc_attr( $settings['secondary_currency_code'] ); ?>">
							</div>
							<div>
								<label class="lct-field-label" for="lct-currency-symbol"><?php esc_html_e( 'Display symbol', 'lebanon-commerce-toolkit' ); ?></label>
								<input id="lct-currency-symbol" type="text" maxlength="12" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[secondary_currency_symbol]" value="<?php echo esc_attr( $settings['secondary_currency_symbol'] ); ?>">
							</div>
							<div>
								<label class="lct-field-label" for="lct-exchange-rate"><?php esc_html_e( 'Manual rate for 1 store-currency unit', 'lebanon-commerce-toolkit' ); ?></label>
								<input id="lct-exchange-rate" type="number" min="0" step="0.000001" inputmode="decimal" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[exchange_rate]" value="<?php echo esc_attr( $settings['exchange_rate'] ); ?>" placeholder="89500">
							</div>
							<div>
								<label class="lct-field-label" for="lct-rounding"><?php esc_html_e( 'Round to nearest', 'lebanon-commerce-toolkit' ); ?></label>
								<input id="lct-rounding" type="number" min="0" step="1" inputmode="numeric" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[rounding_increment]" value="<?php echo esc_attr( $settings['rounding_increment'] ); ?>">
							</div>
						</div>
						<div class="lct-checkbox-row">
							<?php $this->checkbox( 'show_secondary_product', __( 'Show on product and shop prices', 'lebanon-commerce-toolkit' ), $settings ); ?>
							<?php $this->checkbox( 'show_secondary_cart', __( 'Show in classic cart line prices', 'lebanon-commerce-toolkit' ), $settings ); ?>
						</div>
						<p class="description"><?php esc_html_e( 'No automatic exchange-rate source is bundled. The merchant remains responsible for the configured informational rate.', 'lebanon-commerce-toolkit' ); ?></p>
					</section>

					<section class="lct-card">
						<h2><?php esc_html_e( 'District shipping', 'lebanon-commerce-toolkit' ); ?></h2>
						<p><?php esc_html_e( 'Configure Lebanon District Delivery inside each WooCommerce shipping zone. Rates can target a district, a governorate fallback, or all Lebanon.', 'lebanon-commerce-toolkit' ); ?></p>
						<p><a class="button" href="<?php echo esc_url( $shipping_zones_url ); ?>"><?php esc_html_e( 'Open shipping zones', 'lebanon-commerce-toolkit' ); ?></a></p>
						<code>mount-lebanon:metn=4.00</code><br>
						<code>@mount-lebanon=5.00</code><br>
						<code>*=7.00</code>
					</section>

					<section class="lct-card">
						<h2><?php esc_html_e( 'Developer tools', 'lebanon-commerce-toolkit' ); ?></h2>
						<p><code>[lct_location_selector]</code></p>
						<p><code>[lct_secondary_price product_id="123"]</code></p>
						<p><code>/wp-json/lct/v1/locations</code></p>
						<p><?php esc_html_e( 'Equivalent dynamic Gutenberg blocks are also registered.', 'lebanon-commerce-toolkit' ); ?></p>
					</section>

					<section class="lct-card lct-card-wide">
						<h2><?php esc_html_e( 'Data removal', 'lebanon-commerce-toolkit' ); ?></h2>
						<?php $this->checkbox( 'delete_data_on_uninstall', __( 'Delete plugin settings when the plugin is uninstalled', 'lebanon-commerce-toolkit' ), $settings ); ?>
						<p class="description"><?php esc_html_e( 'Order and customer address metadata is retained to preserve historical commerce records.', 'lebanon-commerce-toolkit' ); ?></p>
					</section>
				</div>

				<?php submit_button( __( 'Save Lebanon Toolkit settings', 'lebanon-commerce-toolkit' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param string              $key      Setting key.
	 * @param string              $label    Label.
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	private function checkbox( $key, $label, array $settings ) {
		?>
		<label class="lct-checkbox">
			<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="yes" <?php checked( isset( $settings[ $key ] ) ? $settings[ $key ] : 'no', 'yes' ); ?>>
			<span><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}

	/**
	 * Sanitize a decimal without relying on WooCommerce bootstrap order.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function sanitize_decimal( $value ) {
		$value = str_replace( ',', '.', sanitize_text_field( wp_unslash( (string) $value ) ) );
		return is_numeric( $value ) ? (string) (float) $value : '';
	}
}
