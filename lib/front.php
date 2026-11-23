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

	// The build with purposes only when there is a choice to make between them
	$build = count( $options['purposes'] ) > 1 ? 'no-thanks-purposes' : 'no-thanks';

	// About 1 KB gzipped: inlined rather than one more request
	wp_register_style( 'no-thanks', false, [], NO_THANKS_VERSION );
	wp_add_inline_style( 'no-thanks', file_get_contents( $dir . $build . '.css' ) );
	wp_enqueue_style( 'no-thanks' );

	wp_enqueue_script( 'no-thanks', plugins_url( "dist/$build.js", __DIR__ ), [], NO_THANKS_VERSION, [ 'strategy' => 'defer' ] );

	$config = apply_filters( 'no_thanks_config', [
		// New trackers, purposes or message ask the visitors again
		'revision' => substr( md5( $options['message'] . wp_json_encode( $options['purposes'] ) ), 0, 8 ),
		'purposes' => array_map( fn( $p ) => [
			'id'          => $p['id'],
			'label'       => $p['label'],
			'description' => $p['description'],
		], $options['purposes'] ),
		'texts'    => [
			'label'        => $options['label'],
			'message'      => $options['message'],
			'dnt'          => $options['dnt'],
			'refuse'       => $options['refuse'],
			'accept'       => $options['accept'],
			'acceptAnyway' => $options['accept_anyway'],
			'accepted'     => $options['accepted'],
			'refused'      => $options['refused'],
			'chosen'       => $options['chosen'],
			'choose'       => $options['choose'],
			'save'         => $options['save'],
			'allowed'      => $options['allowed'],
			'some'         => $options['some'],
			'notAllowed'   => $options['not_allowed'],
		],
	] );

	wp_add_inline_script( 'no-thanks', 'window.noThanksConfig = ' . wp_json_encode( $config ) . ';', 'before' );
} );

/**
 * Prints each purpose's blocked HTML for a spot (`head` or `body`): inert in a <template> until the visitor agrees to it.
 */
function no_thanks_print( string $spot ): void {
	foreach ( no_thanks_options()['purposes'] as $p ) {
		if ( '' !== trim( $p[ $spot ] ) ) {
			printf( "<template data-no-thanks=\"%s\">\n%s\n</template>\n", esc_attr( $p['id'] ), $p[ $spot ] ); // phpcs:ignore WordPress.Security.EscapeOutput -- scripts saved by an admin with unfiltered_html
		}
	}
}

add_action( 'wp_head', fn() => no_thanks_print( 'head' ), 99 );
add_action( 'wp_footer', fn() => no_thanks_print( 'body' ), 5 );
