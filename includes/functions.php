<?php

/**
 * Get posttypes.
 *
 * @return array $post_types 
 */
function bfi_post_type_lists () {

    $post_types = array( 'post', 'page' );

	if( class_exists('WooCommerce') ) {
		$post_types[] = 'product';
	}

    return apply_filters( 'bfie_post_type_lists', $post_types );
}

/**
 * Get BFI plugin settings.
 *
 * @param string $section
 * @param boolean $all
 * @return array settings.
 */
function bfi_get_settings( $section = '', $all = false) {

	$bfi_settings = get_option( 'bfi_settings', true );
	
	if( $all ) {
		return $bfi_settings;
	}
	
	return !empty( $bfi_settings[$section] ) ? $bfi_settings[$section] : '';
}

/**
 * Get per page for post lists.
 *
 * @return string per_page;
 */
function bfi_get_per_page() {

	$bfi_settings = get_option( 'bfi_settings', true );
	$general_settings = !empty( $bfi_settings['general'] ) ? $bfi_settings['general'] : array();

	return !empty($general_settings['bfi_per_page']) ? (int)sanitize_text_field($general_settings['bfi_per_page']) : BFIE_PER_PAGE;
}

/**
 * Function for use to get template file.
 */
function bfi_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	if ( ! $template_path ) {
		$template_path = untrailingslashit( 'bulk-featured-image' );
	}

	if ( ! $default_path ) {
		$default_path = BFIE_PATH . '/templates';
	}

	$template = locate_template( array(
		untrailingslashit( $template_path ) . '/' . $template_name,
		$template_name,
	) );
    
	if ( ! $template ) {
		$template = untrailingslashit( $default_path ) . '/' . $template_name;
	}
	
	$located = $template;

	if ( ! file_exists( $located ) ) {
		return;
	}

	$located = apply_filters( 'bfie_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'bfie_before_get_template', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'bfie_after_get_template', $template_name, $template_path, $located, $args );
}

/**
 * This function used for sanitize text fields.
 *
 * @return string per_page;
 */
function bfi_sanitize_text_field( $fields ) {

	$settings = array();

	if( !empty($fields) && is_array($fields)) {
		foreach ($fields as $key => $field ) {
			if( !empty($field) && is_array($field)) {
				$sanitize_field = bfi_sanitize_text_field($field);
			} else{
				$sanitize_field = sanitize_text_field($field);
			}
			
			$settings[$key] = $sanitize_field;
		}
	}

	return $settings;
}