<?php
/**
 * Front end: the blocked scripts, the banner script and its styles, only while the banner is on.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	if ( ! no_thanks_active() ) {
		return;
	}

	$options = no_thanks_options();
	$dir     = plugin_dir_path( __DIR__ ) . 'dist/';

	// A few hundred bytes: inlined rather than one more request
	wp_register_style( 'no-thanks', false, [], NO_THANKS_VERSION );
	wp_add_inline_style( 'no-thanks', file_get_contents( $dir . 'no-thanks.css' ) );
	wp_enqueue_style( 'no-thanks' );

	wp_enqueue_script( 'no-thanks', plugins_url( 'dist/no-thanks.js', __DIR__ ), [], NO_THANKS_VERSION, [ 'strategy' => 'defer' ] );

	$config = apply_filters( 'no_thanks_config', [
		// New trackers or a new message ask the visitors again
		'revision' => substr( md5( $options['message'] . $options['head'] . $options['body'] ), 0, 8 ),
		'texts'    => [
			'label'        => __( 'Cookies and trackers', 'no-thanks' ),
			'message'      => $options['message'],
			'dnt'          => $options['dnt'],
			'refuse'       => $options['refuse'],
			'accept'       => $options['accept'],
			'acceptAnyway' => $options['accept_anyway'],
			'accepted'     => $options['accepted'],
			'refused'      => $options['refused'],
			'allowed'      => $options['allowed'],
			'notAllowed'   => $options['not_allowed'],
		],
	] );

	wp_add_inline_script( 'no-thanks', 'window.noThanksConfig = ' . wp_json_encode( $config ) . ';', 'before' );
} );

/**
 * Prints blocked HTML: inert in a <template> until the visitor agrees.
 */
function no_thanks_print( string $html ): void {
	if ( '' !== trim( $html ) ) {
		echo "<template data-no-thanks>\n" . $html . "\n</template>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- scripts saved by an admin with unfiltered_html
	}
}

add_action( 'wp_head', fn() => no_thanks_print( no_thanks_options()['head'] ), 99 );
add_action( 'wp_footer', fn() => no_thanks_print( no_thanks_options()['body'] ), 5 );
