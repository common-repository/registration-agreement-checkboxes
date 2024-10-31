<?php
/**
 * Display and manage checkboxes
 *
 * @package yp-agreement-checkboxes
 *
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', array( 'GBYP_Registration_Checkboxes', 'init' ) );

/**
 * WP and WOO registration agreement checkboxes.
 */
class GBYP_Registration_Checkboxes {

	/**
	 * Array holding all tags allowed
	 *
	 * @var $kses
	 */
	private static $kses = array(
		'input'  => array(
			'type'     => array(),
			'name'     => array(),
			'value'    => array(),
			'checked'  => array(),
			'id'       => array(),
			'required' => array(),
		),
		'label'  => array(
			'for' => array(),
		),
		'p'      => array(
			'class' => array(),
		),
		'strong' => array(
			'class' => array(),
		),
		'em'     => array(),
		'br'     => array(),
		'ol'     => array(),
		'ul'     => array(),
		'li'     => array(),
		'a'      => array(
			'target' => array(),
			'href'   => array(),
		),
		'div'    => array(
			'class' => array(),
		),
	);

	/**
	 * Array holding all tags allowed for the YP Checkboxes notices
	 *
	 * @var $kses_checked
	 */
	private static $kses_checked = array(
		'checked' => array(),
	);

	/**
	 * Activate hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_settings_tabs_yp_registration_checkboxes', array( get_class(), 'visualize' ), 15 );
		add_action( 'wp_enqueue_scripts', array( get_class(), 'theme_enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( get_class(), 'admin_enqueue_styles' ) );
		add_action( 'woocommerce_settings_tabs_yp_registration_checkboxes', array( get_class(), 'settings_tab' ) );
		add_action( 'admin_notices', array( get_class(), 'yp_chcbx__error_notice' ) );
		add_action( 'woocommerce_register_form', array( get_class(), 'display' ) );
		add_action( 'woocommerce_settings_tabs_yp_checkboxes_double_pass', array( get_class(), 'double_pass_tab' ) );
		add_action( 'woocommerce_update_options_yp_checkboxes_double_pass', array( get_class(), 'update_dbl_pass_settings' ) );
		add_action( 'woocommerce_register_post', array( get_class(), 'yp_chcbx__validate_register_fields' ), 10, 3 );
		add_action( 'woocommerce_update_options_yp_registration_checkboxes', array( get_class(), 'update_settings' ) );

		// Display two tabs in WooCommerce -> settings.
		add_filter( 'woocommerce_settings_tabs_array', array( get_class(), 'add_settings_tab' ), 50 );

		// Check if the checkboxes are to be displayed on the front page.
		$display_checkboxes   = wp_kses_post( get_option( 'options_yp_checkboxes_yes_no' ) );

		// Setup these only if option Display on Front is checked.
		if ( $display_checkboxes ) {
			add_action( 'woocommerce_edit_account_form', array( get_class(), 'show_yp_checkboxes_on_front' ), 10 );
			add_action( 'woocommerce_register_post', array( get_class(), 'yp_chcbx__validate_checkboxes_registration' ), 10, 3 );
			add_action( 'woocommerce_save_account_details_errors', array( get_class(), 'yp_chcbx__validate_checkboxes_account' ) );
			add_action( 'woocommerce_save_account_details', array( get_class(), 'yp_chcbx__checkboxes' ), 12, 1 );
			add_action( 'woocommerce_created_customer', array( get_class(), 'yp_chcbx__checkboxes' ), 12, 1 );
		}
	}

	/**
	 * Check woocommerce settings in order for YP plugin to run correctly
	 */
	public static function yp_chcbx__error_notice() {

		// Check if WooCommerce plugin is activated and prompt a warning message if not.
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
			<?php
				vprintf(
					'<p><b>%s</b> %s</p>',
					array(
						esc_html__( 'Registration Checkboxes', 'yp-agreement-checkboxes' ),
						esc_html__( 'is dedicated to extend WooCommerce functionality so it requires that plugin to be activated.', 'yp-agreement-checkboxes' ),
					)
				);
			?>
			</div>
			<?php
		}

		// Disable auto-generated password.
		if ( isset( $_GET['disable-auto-pass'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_GET['disable-auto-pass'] ) );
			if ( ! wp_verify_nonce( $nonce, 'yp-agreement-disable-auto-pass-nonce' ) ) {

				// This nonce is not valid.
				die( esc_html__( 'Security check - validation error', 'yp-agreement-checkboxes' ) );
			} else {

				// The nonce is valid.
				// Update the Woocommerce option.
				update_option( 'woocommerce_registration_generate_password', 'no', false );

				if ( isset( $_GET['woo'] ) ) {
					?>
					<div class="notice-success notice is-dismissible">
						<p><?php esc_html_e( 'The double password feature has been enabled and is now active.', 'yp-agreement-checkboxes' ); ?></p>
					</div>
					<?php
				}
			}
		}

		// Check current page.
		$screen = get_current_screen();

		// Find Woocommerce password option.
		$is_valid = get_option( 'woocommerce_registration_generate_password' );

		// Find if double password feature was enabled.
		$is_selected = get_option( 'options_yp_checkboxes_double_pass' );

		// Display warning if the woocommerce auto generate password is enabled.
		if ( 'woocommerce_page_wc-settings' === $screen->id && ! empty( $_GET['tab'] ) && 'yp_checkboxes_double_pass' === $_GET['tab'] ) {

			if ( $is_selected && 'yes' === $is_valid ) {

				$url = add_query_arg(
					array(
						'page'              => 'wc-settings',
						'tab'               => 'yp_checkboxes_double_pass',
						'woo'               => '1',
						'disable-auto-pass' => wp_create_nonce( 'yp-agreement-disable-auto-pass-nonce' ),
					),
					admin_url( 'admin.php' )
				);
				?>
			<div class="error notice is-dismissible">
				<?php

				vprintf(
					'<p>%s<br />%s <a href="%s">%s</a></p>',
					array(
						esc_html__( 'Woocommerce\'s "Automatically generate customer password" must be disabled in order for this very plugin to work properly.', 'yp-agreement-checkboxes' ),
						esc_html__( 'Would you like to disable this option?', 'yp-agreement-checkboxes' ),
						esc_url( $url ),
						esc_html__( 'Yes, disable auto-password generator', 'yp-agreement-checkboxes' ),
					)
				);
				?>
			</div>
				<?php
			}
		}
	}

	/**
	 * Saves Checkboxes options to the DB.
	 */
	public static function update_dbl_pass_settings() {

		$yes_no = ! empty( $_POST['yp_checkboxes_double_pass'] ) ? sanitize_text_field( wp_unslash( $_POST['yp_checkboxes_double_pass'] ) ) : '';
		$nonce  = ! empty( $_POST['yp_double_password_nonce_value'] ) ? sanitize_text_field( wp_unslash( $_POST['yp_double_password_nonce_value'] ) ) : '';

		if ( isset( $_POST['yp_checkboxes_save_data'] ) && ! empty( $_POST['yp_double_password_nonce_value'] ) && wp_verify_nonce( $nonce, 'yp_double_password_nonce' ) ) {
			update_option( 'options_yp_checkboxes_double_pass', $yes_no, true );
		}
	}

	/**
	 * Saves Checkboxes options to the DB.
	 */
	public static function update_settings() {

		$yes_no = ! empty( $_POST['yp_checkboxes_yes_no'] ) ? sanitize_text_field( wp_unslash( $_POST['yp_checkboxes_yes_no'] ) ) : '';
		$nonce  = ! empty( $_POST['yp_checkboxes_nonce_value'] ) ? sanitize_text_field( wp_unslash( $_POST['yp_checkboxes_nonce_value'] ) ) : '';

		if ( isset( $_POST['yp_checkboxes_save_data'] )
			&& isset( $yes_no )
			&& isset( $_POST['yp_checkboxes_copy'] )
			&& isset( $_POST['yp_checkboxes_agreement_checkboxes'] )
			&& ! empty( $_POST['yp_checkboxes_nonce_value'] )
			&& wp_verify_nonce( $nonce, 'yp_checkboxes_nonce' ) ) {

			update_option( 'options_yp_checkboxes_yes_no', $yes_no, true );
			update_option( 'options_yp_checkboxes_copy', wp_kses_post( wp_unslash( $_POST['yp_checkboxes_copy'] ) ), true );
			update_option( 'options_yp_checkboxes_agreement_checkboxes', yp_chckboxes_fn_protectsql_remove_scripts( wp_kses_post( wp_unslash( $_POST['yp_checkboxes_agreement_checkboxes'] ) ) ), true );
		}
	}

	/**
	 * Add 'Agreement checkboxes' tab.
	 *
	 * @param string $settings_tabs Tab name.
	 *
	 * @return array Returns updated number of Woocommerce tabs.
	 */
	public static function add_settings_tab( $settings_tabs ) {
		$settings_tabs['yp_registration_checkboxes'] = esc_html__( 'Agreement checkboxes', 'yp-agreement-checkboxes' );
		$settings_tabs['yp_checkboxes_double_pass']  = esc_html__( 'Double password', 'yp-agreement-checkboxes' );
		return $settings_tabs;
	}

	/**
	 * Print out the double password form.
	 */
	public static function double_pass_tab() {

		$get_yes_no_val = get_option( 'options_yp_checkboxes_double_pass' ) === '1' ? ' checked' : '';

		echo '<div id="gbyp_checkboxes_plugin"><h2>' . esc_html__( 'Double password feature', 'yp-agreement-checkboxes' ) . '</h2>
		<em>' . esc_html__( 'Turn on/off the double password validation', 'yp-agreement-checkboxes' ) . '</em>';

		echo '<p class="onoff">
		<input type="checkbox" name="yp_checkboxes_double_pass" id="yp_checkboxes_double_pass" value="1"' . wp_kses( $get_yes_no_val, self::$kses_checked ) . '>
		<label for="yp_checkboxes_double_pass" data-no="' . esc_attr__( 'No', 'yp-agreement-checkboxes' ) . '" data-yes="' . esc_attr__( 'Yes', 'yp-agreement-checkboxes' ) . '"></label>
		</p>

		<input type="hidden" name="yp_checkboxes_save_data" id="yp_checkboxes_save_data" value="ok">
		</div>';

		wp_nonce_field( 'yp_double_password_nonce', 'yp_double_password_nonce_value' );
	}

	/**
	 * Print out the checkboxes form.
	 */
	public static function settings_tab() {

		$agreement_checkboxes_copy = stripslashes( get_option( 'options_yp_checkboxes_copy' ) );
		$agreement_checkboxes      = stripslashes( get_option( 'options_yp_checkboxes_agreement_checkboxes' ) );

		echo '<div id="gbyp_checkboxes_plugin"><h2>' . esc_html__( 'Checkboxes copy', 'yp-agreement-checkboxes' ) . '</h2>
		<em>' . esc_html__( 'Enter a description of the Agreement checkboxes here (optional)', 'yp-agreement-checkboxes' ) . '</em>';

		$settings_a = array(
			'textarea_rows' => 3,
			'wpautop'       => true,
			'media_buttons' => false,
			'tinymce'       => false,
			'quicktags'     => true,
		);
		wp_editor( $agreement_checkboxes_copy, 'yp_checkboxes_copy', $settings_a );

		echo '<h2>' . esc_html__( 'Edit checkboxes', 'yp-agreement-checkboxes' ) . '</h2>';
		echo '
		<p>
		' . wp_kses(
			__(
				'<div class="yp_checkboxes_legend">
				<p>Every entry is to be stored in a separate line.<br />Format the checkboxes by following this pattern:
				<br><em>[checkbox_name] * |  Description, regular HTML is allowed here</em> where:</p>
				<p class="yp_legend"><strong>[</strong> Opening square bracket<br />
				<strong>checkbox_name</strong> Checkbox name - only letter and underscores ( _ ); no spaces, numbers, special characters - just plain text<br />
				<strong>]</strong> Closing square bracket<br />
				<strong class="yp_star">*</strong> If the checkbox is mandatory to be checked enter asterisk right after closing bracket<br />
				<strong>|</strong> The pipe character to separate checkbox name from checkbox content<br />
				<strong>checkbox copy</strong> - regular HTML is allowed here. Do NOT copy text from other visual editors as they tend to attach hidden elements along with the text</p></div>',
				'yp-agreement-checkboxes'
			),
			self::$kses
		) . '
		</p>
		';

		$settings_b = array(
			'textarea_rows' => 8,
			'wpautop'       => true,
			'media_buttons' => false,
			'tinymce'       => false,
			'quicktags'     => true,
		);
		wp_editor( $agreement_checkboxes, 'yp_checkboxes_agreement_checkboxes', $settings_b );
		$get_yes_no_val = get_option( 'options_yp_checkboxes_yes_no' ) === '1' ? ' checked' : '';

		echo '<h2>' . esc_html__( 'Display on the Front?', 'yp-agreement-checkboxes' ) . '</h2>
		<em>' . esc_html__( 'Mark YES if you are sure you want to publish the checkboxes on the front page', 'yp-agreement-checkboxes' ) . '</em>';
		echo '<p class="onoff">
		<input type="checkbox" name="yp_checkboxes_yes_no" id="yp_checkboxes_yes_no" value="1"' . wp_kses( $get_yes_no_val, self::$kses_checked ) . '>
		<label for="yp_checkboxes_yes_no" data-no="' . esc_attr__( 'No', 'yp-agreement-checkboxes' ) . '" data-yes="' . esc_attr__( 'Yes', 'yp-agreement-checkboxes' ) . '"></label>
		</p>

		<input type="hidden" name="yp_checkboxes_save_data" id="yp_checkboxes_save_data" value="ok">
		';

		wp_nonce_field( 'yp_checkboxes_nonce', 'yp_checkboxes_nonce_value' );
		echo '</div> <!-- Checkboxes tab ends -->';
	}

	/**
	 * Display checkbox set on the front page, in the User's settings (User is already logged in).
	 */
	public static function show_yp_checkboxes_on_front() {

		$current_user_id      = get_current_user_id();
		$agreement_checkboxes = get_option( 'options_yp_checkboxes_agreement_checkboxes' );
		$box_css              = is_account_page() ? ' inside' : ' logreg';
		$display_checkboxes   = wp_kses_post( get_option( 'options_yp_checkboxes_yes_no' ) );

		if ( $display_checkboxes ) {
			echo '<div class="gbyp_checkboxes_div_wrapper' . esc_attr( $box_css ) . '">' . wp_kses( yp_chckboxes_fn_get_checkboxes( $agreement_checkboxes, null, $current_user_id ), self::$kses ) . '</div>';
			wp_nonce_field( 'save-checkboxes-db', 'save-checkboxes-nonce' );
		}
	}

	/**
	 * Check if required checkboxes were ticked
	 *
	 * @param string $username Logged in user.
	 * @param string $email User's email.
	 * @param array  $validation_errors Error array.
	 *
	 * @return array Returns updated array with all errors.
	 */
	public static function yp_chcbx__validate_checkboxes_registration( $username, $email, $validation_errors ) {
		return yp_chckboxes_fn_validate_checkboxes( $validation_errors );
	}

	/**
	 * Check if required checkboxes were ticked
	 *
	 * @param array $validation_errors Error array.
	 *
	 * @return array Returns updated array with all errors.
	 */
	public static function yp_chcbx__validate_checkboxes_account( $validation_errors ) {
		return yp_chckboxes_fn_validate_checkboxes( $validation_errors );
	}

	/**
	 * Compile selected checkboxes into JSON file and save it to the DB
	 *
	 * @param int $user_id User ID.
	 */
	public static function yp_chcbx__checkboxes( $user_id ) {

		$nonce_sav = ! empty( $_POST['save-checkboxes-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['save-checkboxes-nonce'] ) ) : '';
		$nonce_reg = ! empty( $_POST['registration-checkboxes-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['registration-checkboxes-nonce'] ) ) : '';

		if ( ! empty( $_POST['yp__agreement_chbx'] ) && ( wp_verify_nonce( $nonce_reg, 'registration-checkboxes-db' ) || wp_verify_nonce( $nonce_sav, 'save-checkboxes-db' ) ) ) {
			update_user_meta( $user_id, 'yp__chckbx_agreements', wp_json_encode( yp_chckboxes_fn_sanitize_array( wp_unslash( $_POST['yp__agreement_chbx'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	}

	/**
	 * YP__ register fields Validation
	 *
	 * @param string $username Logged in user.
	 * @param string $email User's email.
	 * @param array  $validation_errors Error array.
	 *
	 * @return array Returns updated array with all errors.
	 */
	public static function yp_chcbx__validate_register_fields( $username, $email, $validation_errors ) {

		// Bail early if woocommerce "automatically generate password" is enabled.
		$is_valid = get_option( 'woocommerce_registration_generate_password' );
		if ( 'yes' === $is_valid ) {
			return;
		}

		// OR else.
		// Check if the password was re-typed.
		$nonce = ! empty( $_GET['save-retyped-pass-nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['save-retyped-pass-nonce'] ) ) : '';
		if ( empty( $_POST['yp__retype_password'] ) && wp_verify_nonce( $nonce, 'retyped-password-db' ) ) {
			$validation_errors->add( 'yp__retype_password_error', esc_html__( 'You must re-type your password', 'yp-agreement-checkboxes' ) );
		}

		// Check if the passwords do match.
		if ( ( isset( $_POST['yp__retype_password'] ) && isset( $_POST['password'] ) ) && ( $_POST['yp__retype_password'] !== $_POST['password'] ) ) {
			$validation_errors->add( 'yp__retype_password_error', esc_html__( 'Passwords provided don\'t match', 'yp-agreement-checkboxes' ) );
		}

		return $validation_errors;
	}

	/**
	 * Display how the checkboxes will look like (the backend).
	 */
	public static function visualize() {

		$agreement_checkboxes = wp_kses_post( get_option( 'options_yp_checkboxes_agreement_checkboxes' ) );
		$checkboxes_copy      = wp_kses_post( get_option( 'options_yp_checkboxes_copy' ) );

		echo '<div class="yp_agreement_visualize"><h2>', esc_html__( 'Visualization of the checkboxes', 'yp-agreement-checkboxes' ), ':</h2>';
		echo '<span class="vis_copy">' . nl2br( wp_kses( $checkboxes_copy, self::$kses ) ) . '</span>';
		echo wp_kses( yp_chckboxes_fn_get_checkboxes( $agreement_checkboxes, null, null, 'ok' ), self::$kses );
		echo '</div>';
	}

	/**
	 * Check if the re-type field exists and display it if it does.
	 */
	public static function get_retype_password() {

		$is_valid        = get_option( 'woocommerce_registration_generate_password' );
		$double_password = wp_kses_post( get_option( 'options_yp_checkboxes_double_pass' ) );
		if ( ! $double_password || 'yes' === $is_valid ) {
			return;
		}
		?>
			<p class="woocommerce form form-row yp__password_field">
			<label for="yp__retype_password"><?php esc_html_e( 'Re-type password', 'yp-agreement-checkboxes' ); ?>&nbsp;<span class="">*</span></label>
				<input
					type="password"
					id="yp__retype_password"
					name="yp__retype_password"
					class="woocommerce-Input woocommerce-Input--text input-text"
					placeholder="<?php esc_html_e( 'Re-type password', 'yp-agreement-checkboxes' ); ?>"
					required
				/>
			</p>
		<?php
		wp_nonce_field( 'retyped-password-db', 'save-retyped-pass-nonce' );
	}

	/**
	 * Check if checkboxes exist and display them if they do - inside the registration box.
	 */
	public static function get_checkboxes() {

		$checkboxes_div       = '';
		$agreement_checkboxes = wp_kses_post( get_option( 'options_yp_checkboxes_agreement_checkboxes' ) );
		$display_checkboxes   = wp_kses_post( get_option( 'options_yp_checkboxes_yes_no' ) );

		if ( ! $agreement_checkboxes || ! $display_checkboxes ) {
			return;
		}

		if ( $agreement_checkboxes && $display_checkboxes ) {

			$checkboxes_copy = wp_kses_post( get_option( 'options_yp_checkboxes_copy' ) );

			if ( $checkboxes_copy ) {
				$checkboxes_div .= '<div class="yp_chbx_copy">' . nl2br( $checkboxes_copy ) . '</div>';
			}
			$checkboxes_div .= wp_kses( yp_chckboxes_fn_get_checkboxes( $agreement_checkboxes, $register = '1' ), self::$kses );
			$checkboxes_div .= wp_nonce_field( 'registration-checkboxes-db', 'registration-checkboxes-nonce', true, false );
		}

		return $checkboxes_div;
	}

	/**
	 * Display plugin's combined content.
	 */
	public static function display() {

		if ( self::get_checkboxes() || self::get_retype_password() ) {
			?>
		<div class="gbyp_checkboxes_div_wrapper">
			<?php echo self::get_retype_password(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo self::get_checkboxes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
			<?php
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function theme_enqueue_styles() {
		wp_enqueue_style( 'yp-chckbx-front', esc_url( plugin_dir_url( REGISTER_AGREEMENT_CHBXS__MAIN_FILE ) . 'assets/style/yp_chbx_front.css' ), array(), REGISTER_AGREEMENT_CHBXS__MAIN_FILE );
	}

	/**
	 * Enqueue scripts and styles - admin area.
	 */
	public static function admin_enqueue_styles() {
		wp_enqueue_style( 'yp-chckbx-admin', esc_url( plugin_dir_url( REGISTER_AGREEMENT_CHBXS__MAIN_FILE ) . 'assets/style/yp_chbx_admin.css' ), array(), REGISTER_AGREEMENT_CHBXS__MAIN_FILE );
	}
}
