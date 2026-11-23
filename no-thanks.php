<?php
/**
 * Plugin Name: No Thanks
 * Description: An ultra-light consent banner: no dependency, no cookie, respects Do Not Track and Global Privacy Control.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Corentin Gautier
 * License: GPL-3.0-or-later
 * Text Domain: no-thanks
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'NO_THANKS_VERSION', '0.1.0' );

require __DIR__ . '/lib/settings.php';
require __DIR__ . '/lib/front.php';

add_action( 'init', function () {
	load_plugin_textdomain( 'no-thanks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

/**
 * Plugin options, with defaults.
 *
 * @return array{services: string, purpose: string, head: string, body: string, page: int}
 */
function no_thanks_options(): array {
	return wp_parse_args( get_option( 'no_thanks', [] ), [
		'services' => '',
		'purpose'  => '',
		'head'     => '',
		'body'     => '',
		'page'     => 0,
	] );
}

/**
 * Whether the banner is on: at least one script waits for consent.
 */
function no_thanks_active(): bool {
	$options = no_thanks_options();
	return '' !== trim( $options['head'] . $options['body'] );
}

/**
 * A link that reopens the banner, for a footer. Empty while the banner is off.
 *
 * @param string $label Link text, "Cookies" by default.
 */
function no_thanks_link( string $label = '' ): string {
	if ( ! no_thanks_active() ) {
		return '';
	}
	return sprintf( '<a href="#no-thanks">%s</a>', esc_html( $label ?: __( 'Cookies', 'no-thanks' ) ) );
}
