<?php
/**
 * Settings › No Thanks: what the banner names and the scripts it holds back.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	$hook = add_options_page( 'No Thanks', 'No Thanks', 'manage_options', 'no-thanks', 'no_thanks_settings_page' );

	add_action( 'admin_enqueue_scripts', function ( $current ) use ( $hook ) {
		if ( $current !== $hook ) {
			return;
		}
		$editor = wp_enqueue_code_editor( [ 'type' => 'text/html' ] );
		if ( false !== $editor ) {
			wp_add_inline_script( 'code-editor', sprintf(
				'jQuery(() => document.querySelectorAll(".no-thanks-code").forEach(el => wp.codeEditor.initialize(el, %s)));',
				wp_json_encode( $editor )
			) );
			wp_add_inline_style( 'code-editor', '.no-thanks-code + .CodeMirror { height: 10em; }' );
		}
	} );
} );

add_filter( 'plugin_action_links_no-thanks/no-thanks.php', function ( $links ) {
	array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=no-thanks' ) ), __( 'Settings', 'no-thanks' ) ) );
	return $links;
} );

add_action( 'admin_init', function () {
	register_setting( 'no_thanks', 'no_thanks', [
		'type'              => 'array',
		'sanitize_callback' => 'no_thanks_sanitize',
	] );
} );

/**
 * Keeps the scripts as pasted, but only from a user allowed to post raw HTML.
 */
function no_thanks_sanitize( $input ): array {
	$old   = no_thanks_options();
	$input = is_array( $input ) ? $input : [];
	$raw   = current_user_can( 'unfiltered_html' );

	return [
		'services' => sanitize_text_field( $input['services'] ?? '' ),
		'purpose'  => sanitize_text_field( $input['purpose'] ?? '' ),
		'head'     => $raw ? trim( $input['head'] ?? '' ) : $old['head'],
		'body'     => $raw ? trim( $input['body'] ?? '' ) : $old['body'],
		'page'     => absint( $input['page'] ?? 0 ),
	];
}

function no_thanks_settings_page(): void {
	$o   = no_thanks_options();
	$raw = current_user_can( 'unfiltered_html' );
	$purposes = [
		__( 'measure its audience', 'no-thanks' ),
		__( 'show you advertising', 'no-thanks' ),
		__( 'measure its advertising campaigns', 'no-thanks' ),
	];
	?>
	<div class="wrap">
		<h1>No Thanks</h1>
		<p><?php esc_html_e( 'A consent banner for the trackers this site uses. Leave the scripts empty and nothing is loaded.', 'no-thanks' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'no_thanks' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="no-thanks-services"><?php esc_html_e( 'Services', 'no-thanks' ); ?></label></th>
					<td>
						<input type="text" id="no-thanks-services" class="regular-text" name="no_thanks[services]" value="<?php echo esc_attr( $o['services'] ); ?>" placeholder="Google Analytics">
						<p class="description"><?php esc_html_e( 'Named in the banner, e.g. Google Analytics, Meta Pixel.', 'no-thanks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="no-thanks-purpose"><?php esc_html_e( 'Purpose', 'no-thanks' ); ?></label></th>
					<td>
						<input type="text" id="no-thanks-purpose" class="regular-text" name="no_thanks[purpose]" value="<?php echo esc_attr( $o['purpose'] ); ?>" placeholder="<?php echo esc_attr( $purposes[0] ); ?>" list="no-thanks-purposes">
						<datalist id="no-thanks-purposes">
							<?php foreach ( $purposes as $purpose ) : ?>
								<option value="<?php echo esc_attr( $purpose ); ?>"></option>
							<?php endforeach; ?>
						</datalist>
						<p class="description">
							<?php
							/* translators: %s: an example purpose */
							printf( esc_html__( 'Completes “This site would like to … with these services”, e.g. %s.', 'no-thanks' ), esc_html( $purposes[0] ) );
							?>
						</p>
					</td>
				</tr>
				<?php foreach ( [ 'head' => __( 'Scripts in the head', 'no-thanks' ), 'body' => __( 'Scripts at the end of the page', 'no-thanks' ) ] as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="no-thanks-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<textarea id="no-thanks-<?php echo esc_attr( $key ); ?>" class="large-text code no-thanks-code" rows="6" name="no_thanks[<?php echo esc_attr( $key ); ?>]" <?php disabled( ! $raw ); ?>><?php echo esc_textarea( $o[ $key ] ); ?></textarea>
							<?php if ( 'head' === $key ) : ?>
								<p class="description"><?php esc_html_e( 'Paste the code as the service gives it. It only runs once the visitor agrees.', 'no-thanks' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="no-thanks-page"><?php esc_html_e( '“Learn more” page', 'no-thanks' ); ?></label></th>
					<td>
						<?php
						wp_dropdown_pages( [
							'name'              => 'no_thanks[page]',
							'id'                => 'no-thanks-page',
							'selected'          => (int) $o['page'],
							'show_option_none'  => __( 'Privacy policy page (Settings › Privacy)', 'no-thanks' ),
							'option_none_value' => 0,
						] );
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Footer link', 'no-thanks' ); ?></th>
					<td>
						<p class="description">
							<?php
							/* translators: %s: the link address */
							printf( esc_html__( 'Any link to %s reopens the banner, so visitors can change their mind: add one to the footer menu.', 'no-thanks' ), '<code>#no-thanks</code>' );
							?>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
