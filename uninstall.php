<?php
/**
 * Uninstall Bulk Featured Image
 *
 * Uninstalling Bulk Featured Image deletes plugin options.
 *
 * @package Bulk Featured Image
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$bfi_settings = get_option( 'bfi_settings', true );

$section = 'uninstall';

$uninstall_settings = !empty( $bfi_settings[$section] ) ? $bfi_settings[$section] : '';

$bfi_uninstall = !empty( $uninstall_settings['bfi_uninstall'] ) ? $uninstall_settings['bfi_uninstall'] : '';

if( !empty($bfi_uninstall) && $bfi_uninstall == '1' ){
    
    delete_option( 'bfi_settings' );
}
