<?php
/**
 * Display and manage checkboxes - functions
 *
 * @package yp-agreement-checkboxes
 *
 * phpcs:disable Squiz.PHP.CommentedOutCode.Found
 */

/**
 * Escape output
 *
 * @param array $post_output POST array.
 *
 * @return array Returns escaped POST.
 */
function yp_chckboxes_fn_protectsql( $post_output ) {
	$post_output = preg_replace( "/'/", '`', $post_output );
	$post_output = preg_replace( '/"/', '&#34;', $post_output );
	$post_output = preg_replace( '/(\<script)(.*?)(script>)/si', '', $post_output );
	$post_output = wp_strip_all_tags( $post_output );
	$post_output = str_replace( array( '"', '>', '<', '\\' ), '', $post_output );

	return $post_output;
}

/**
 * Validate checkboxes' output
 *
 * @param array $validation_errors WP errors.
 *
 * @return array Returns error if required checkboxes were not ticked.
 */
function yp_chckboxes_fn_validate_checkboxes( $validation_errors ) {

	$agreement_checkboxes = get_option( 'options_yp_checkboxes_agreement_checkboxes' );
	$list                 = preg_split( '/\r\n|\r|\n/', $agreement_checkboxes );
	$trimmed_array        = array_values( array_filter( $list ) );

	$ile   = count( $trimmed_array );
	$error = false;

	$nonce_sav = ! empty( $_POST['save-checkboxes-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['save-checkboxes-nonce'] ) ) : '';
	$nonce_reg = ! empty( $_POST['registration-checkboxes-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['registration-checkboxes-nonce'] ) ) : '';

	$array = ! empty( $_POST['yp__agreement_chbx'] ) && ( wp_verify_nonce( $nonce_reg, 'registration-checkboxes-db' ) || wp_verify_nonce( $nonce_sav, 'save-checkboxes-db' ) ) ? yp_chckboxes_fn_sanitize_array( wp_unslash( $_POST['yp__agreement_chbx'] ) ) : array( '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	for ( $x = 0; $x < $ile; $x++ ) {
		$parts = explode( '|', $trimmed_array[ $x ] );
		preg_match( '#\[(.*?)\]#', $parts[0], $match );
		$name = $match[1];

		if ( strpos( $parts[0], '*' ) !== false ) {
			if ( ! in_array( $name, $array, true ) ) {
				$error = true;
			}
		}
	}
	if ( $error ) {
		return $validation_errors->add( 'yp__agreement_' . $x . '_error', esc_html__( 'You must agree to all terms', 'yp-agreement-checkboxes' ) );
	}
}

/**
 * Validate and escape checkboxes' output
 *
 * @param string $post_output WP errors.
 *
 * @return string Returns re-formatted string to be used in the backend.
 */
function yp_chckboxes_fn_protectsql_remove_scripts( $post_output ) {

	$post_output = preg_replace( '/(\<script)(.*?)(script>)/si', '', $post_output );

	$list          = preg_split( '/\r\n|\r|\n/', $post_output );
	$trimmed_array = array_values( array_filter( $list ) );
	$output        = '';

	$ile = count( $trimmed_array );
	for ( $x = 0, $y = 1; $x < $ile; $x++, $y++ ) {

		$parts = explode( '|', $trimmed_array[ $x ] );
		preg_match( '#\[(.*?)\]#', $parts[0], $match );
		$name = $match[1];
		$name = sanitize_title( $name );
		// $name = preg_replace( array( '/\s/', '/[^a-zA-Z_]/' ), array( '_', '' ), $name );

		$name = ( '' === $name ) ? 'checkbox-' . $y : $name;

		$req = '';
		if ( false !== strpos( $parts[0], '*' ) ) {
			$req = '* ';
		}
		$output .= '[' . $name . '] ' . $req . '| ' . preg_replace( '/\s+/', ' ', $parts[1] ) . "\r\n";
	}
	return $output;
}

/**
 * Sanitize_array
 *
 * @param array $array Array to be sanitized.
 *
 * @return array Returns cleaned array.
 */
function yp_chckboxes_fn_sanitize_array( $array ) {
	foreach ( (array) $array as $k => $v ) {
		if ( is_array( $v ) ) {
			$array[ $k ] = yp_chckboxes_fn_sanitize_array( $v );
		} else {
			$array[ $k ] = wp_kses_post( $v );
		}
	}

	return $array;
}

/**
 * Validate checkboxes' output
 *
 * @param array $array Checkboxes.
 * @param array $register Used while registering new user.
 * @param array $user_id Used while updating user's details.
 * @param array $visualize If in visualize mode then remove 'required' from the form.
 *
 * @return string Prints checkboxes on the front.
 */
function yp_chckboxes_fn_get_checkboxes( $array, $register = null, $user_id = null, $visualize = null ) {

	$is_checked    = '';
	$is_disabled   = '';
	$output        = '';
	$list          = preg_split( '/\r\n|\r|\n/', $array );
	$trimmed_array = array_values( array_filter( $list ) );

	if ( $user_id ) {

		$data                 = get_user_meta( $user_id, 'yp__chckbx_agreements', true );
		$nonce                = ! empty( $_POST['save-checkboxes-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['save-checkboxes-nonce'] ) ) : '';
		$yp__agreements_array = ! empty( $_POST['yp__agreement_chbx'] ) && wp_verify_nonce( $nonce, 'save-checkboxes-db' ) ? yp_chckboxes_fn_sanitize_array( wp_unslash( $_POST['yp__agreement_chbx'] ) ) : json_decode( $data );
	}

	if ( $register ) {

		$nonce          = ! empty( $_POST['registration-checkboxes-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['registration-checkboxes-nonce'] ) ) : '';
		$yp__post_array = ! empty( $_POST['yp__agreement_chbx'] ) && wp_verify_nonce( $nonce, 'registration-checkboxes-db' ) ? yp_chckboxes_fn_sanitize_array( wp_unslash( $_POST['yp__agreement_chbx'] ) ) : array();
	}

	$ile = count( $trimmed_array );
	for ( $x = 0; $x < $ile; $x++ ) {

		$parts = explode( '|', trim( $trimmed_array[ $x ] ) );
		preg_match( '#\[(.*?)\]#', $parts[0], $match );
		$name = $match[1];
		$req  = ''; // required.
		$ast  = '';

		if ( false !== strpos( $parts[0], '*' ) ) {

			if ( null !== $visualize ) {
				$req = '';
			} else {
				$req = ' required'; // ' required'.
			}

			$ast = '<strong class="checkbox__req">*</strong>';
		}

		if ( $user_id && $yp__agreements_array ) {
			$is_checked = in_array( $name, $yp__agreements_array, true ) ? ' checked' : '';
		}

		if ( $register && $yp__post_array ) {
			$is_checked = in_array( $name, $yp__post_array, true ) ? ' checked' : '';
		}

		$output .= '<p>
		<input type="checkbox" name="yp__agreement_chbx[]" id="yp__agreement_chbx_' . $x . '" value="' . $name . '"' . $is_checked . $req . '>
		<label for="yp__agreement_chbx_' . $x . '">' . preg_replace( array( '/<br \/>/', '/<p><\/p>/', '/<\/p>/' ), '', $parts[1] ) . ' ' . $ast . '</label></p>';
		$output .= "\r\n";
	}

	return $output;
}
