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
	$page    = $options['page'] ? get_permalink( $options['page'] ) : get_privacy_policy_url();

	// A few hundred bytes: inlined rather than one more request
	wp_register_style( 'no-thanks', false, [], NO_THANKS_VERSION );
	wp_add_inline_style( 'no-thanks', file_get_contents( $dir . 'no-thanks.css' ) );
	wp_enqueue_style( 'no-thanks' );

	wp_enqueue_script( 'no-thanks', plugins_url( 'dist/no-thanks.js', __DIR__ ), [], NO_THANKS_VERSION, [ 'strategy' => 'defer' ] );

	$config = apply_filters( 'no_thanks_config', [
		'services'    => $options['services'] ?: __( 'a measurement tool', 'no-thanks' ),
		'purpose'     => $options['purpose'] ?: __( 'measure its audience', 'no-thanks' ),
		'privacyUrl'  => $page ?: '',
		// A new tool or script asks the visitors again
		'revision'    => substr( md5( $options['services'] . $options['head'] . $options['body'] ), 0, 8 ),
		'texts'       => [
			'label'        => __( 'Cookies and trackers', 'no-thanks' ),
			/* translators: {purpose} and {services} are replaced, keep them as is. */
			'message'      => __( 'This site would like to {purpose} with {services}. Is that OK?', 'no-thanks' ),
			/* translators: {services} is replaced, keep it as is. */
			'dnt'          => __( 'Your browser asks not to be tracked: {services} stays off.', 'no-thanks' ),
			/* translators: {choice} and {date} are replaced, keep them as is. */
			'state'        => __( 'Your choice: {choice}, on {date}.', 'no-thanks' ),
			'accepted'     => __( 'accepted', 'no-thanks' ),
			'refused'      => __( 'refused', 'no-thanks' ),
			'refuse'       => __( 'No thanks', 'no-thanks' ),
			'accept'       => __( 'OK', 'no-thanks' ),
			'acceptAnyway' => __( 'Allow anyway', 'no-thanks' ),
			'more'         => __( 'Learn more', 'no-thanks' ),
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
