<?php
/**
 * Settings › No Thanks: the scripts the banner holds back and every text it shows.
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
			wp_add_inline_style( 'code-editor', '.no-thanks-code + .CodeMirror { height: 7em; }' );
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
 * Keeps the scripts as pasted, but only from a user allowed to post raw HTML. Messages keep bold, italic and links.
 * A purpose block left without name or scripts is dropped; a new one gets an id from its name, kept afterwards.
 */
function no_thanks_sanitize( $input ): array {
	$old    = no_thanks_options();
	$input  = is_array( $input ) ? $input : [];
	$raw    = current_user_can( 'unfiltered_html' );
	$inline = [
		'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
		'strong' => [],
		'b'      => [],
		'em'     => [],
		'i'      => [],
		'br'     => [],
	];

	$clean = [ 'purposes' => [] ];
	$before = array_column( $old['purposes'], null, 'id' );
	$ids    = [];
	foreach ( (array) ( $input['purposes'] ?? [] ) as $p ) {
		$id    = sanitize_key( $p['id'] ?? '' );
		$label = sanitize_text_field( $p['label'] ?? '' );
		$keep  = $before[ $id ] ?? [ 'head' => '', 'body' => '' ];
		$head  = $raw ? trim( $p['head'] ?? '' ) : $keep['head'];
		$body  = $raw ? trim( $p['body'] ?? '' ) : $keep['body'];
		if ( '' === $label . $head . $body ) {
			continue;
		}
		$base = $id ?: ( sanitize_title( $label ) ?: 'purpose' );
		$id   = $base;
		for ( $n = 2; in_array( $id, $ids, true ); $n++ ) {
			$id = "$base-$n";
		}
		$ids[]               = $id;
		$clean['purposes'][] = [
			'id'          => $id,
			'label'       => $label,
			'description' => sanitize_text_field( $p['description'] ?? '' ),
			'head'        => $head,
			'body'        => $body,
		];
	}
	foreach ( [ 'message', 'dnt' ] as $key ) {
		// The editor wraps paragraphs: the banner holds one line, breaks become <br>
		$html          = preg_replace( '#</p>\s*<p[^>]*>#i', '<br>', $input[ $key ] ?? '' );
		$clean[ $key ] = trim( wp_kses( $html, $inline ) );
	}
	foreach ( [ 'label', 'refuse', 'accept', 'accept_anyway', 'accepted', 'refused', 'chosen', 'choose', 'save', 'link', 'allowed', 'some', 'not_allowed' ] as $key ) {
		$clean[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
	}
	return $clean;
}

/**
 * A small visual editor (bold, italic, link) for a banner message.
 */
function no_thanks_editor( string $key, string $value ): void {
	wp_editor( $value, "no-thanks-$key", [
		'textarea_name' => "no_thanks[$key]",
		'textarea_rows' => 3,
		'media_buttons' => false,
		'teeny'         => true,
		'quicktags'     => false,
		'tinymce'       => [
			'toolbar1'      => 'bold,italic,link,unlink',
			'toolbar2'      => '',
			'statusbar'     => false,
			'height'        => 90,
			'forced_root_block' => 'p',
		],
	] );
}

function no_thanks_settings_page(): void {
	$o   = no_thanks_options();
	$raw = current_user_can( 'unfiltered_html' );
	$row = function ( string $key, string $label, string $help = '' ) use ( $o ): void {
		?>
		<tr>
			<th scope="row"><label for="no-thanks-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="text" id="no-thanks-<?php echo esc_attr( $key ); ?>" class="regular-text" name="no_thanks[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $o[ $key ] ); ?>">
				<?php if ( $help ) : ?>
					<p class="description"><?php echo esc_html( $help ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	};
	?>
	<style>
		.no-thanks-purpose { max-width: 960px; margin: 0 0 16px; padding: 4px 16px; border: 1px solid #c3c4c7; border-radius: 4px; background: #fff; }
		.no-thanks-purpose.is-new { border-style: dashed; background: #f6f7f7; }
		.no-thanks-purpose h3 { display: flex; justify-content: space-between; }
		.no-thanks-purpose h3 code { font-weight: 400; }
	</style>
	<div class="wrap">
		<h1>No Thanks</h1>
		<p><?php esc_html_e( 'A consent banner for the trackers this site uses. Leave the scripts empty and nothing is loaded.', 'no-thanks' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'no_thanks' ); ?>

			<h2><?php esc_html_e( 'Trackers', 'no-thanks' ); ?></h2>
			<p><?php esc_html_e( 'One block per purpose. With one purpose the banner asks once; with several, visitors can also choose purpose by purpose. Fill in the empty block to add a purpose, empty a block to remove it.', 'no-thanks' ); ?></p>
			<?php
			// The saved purposes, then an empty one to add
			$blocks = array_merge( $o['purposes'], [ [ 'id' => '', 'label' => '', 'description' => '', 'head' => '', 'body' => '' ] ] );
			foreach ( $blocks as $i => $p ) :
				$name = "no_thanks[purposes][$i]";
				$slug = "no-thanks-$i";
				?>
				<div class="no-thanks-purpose<?php echo '' === $p['id'] ? ' is-new' : ''; ?>">
					<h3>
						<?php echo esc_html( '' === $p['id'] ? __( 'New purpose', 'no-thanks' ) : $p['label'] ); ?>
						<?php if ( '' !== $p['id'] ) : ?>
							<code><?php echo esc_html( $p['id'] ); ?></code>
						<?php endif; ?>
					</h3>
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $p['id'] ); ?>">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $slug ); ?>-label"><?php esc_html_e( 'Name', 'no-thanks' ); ?></label></th>
							<td><input type="text" id="<?php echo esc_attr( $slug ); ?>-label" class="regular-text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $p['label'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Videos', 'no-thanks' ); ?>"></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $slug ); ?>-description"><?php esc_html_e( 'Description', 'no-thanks' ); ?></label></th>
							<td>
								<input type="text" id="<?php echo esc_attr( $slug ); ?>-description" class="large-text" name="<?php echo esc_attr( $name ); ?>[description]" value="<?php echo esc_attr( $p['description'] ); ?>">
								<p class="description"><?php esc_html_e( 'Shown under its switch: the services and what they do.', 'no-thanks' ); ?></p>
							</td>
						</tr>
						<?php foreach ( [ 'head' => __( 'Scripts in the head', 'no-thanks' ), 'body' => __( 'Scripts at the end of the page', 'no-thanks' ) ] as $key => $label ) : ?>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( "$slug-$key" ); ?>"><?php echo esc_html( $label ); ?></label></th>
								<td>
									<textarea id="<?php echo esc_attr( "$slug-$key" ); ?>" class="large-text code no-thanks-code" rows="4" name="<?php echo esc_attr( $name . "[$key]" ); ?>" <?php disabled( ! $raw ); ?>><?php echo esc_textarea( $p[ $key ] ); ?></textarea>
									<?php if ( 'head' === $key ) : ?>
										<p class="description"><?php esc_html_e( 'Paste the code as the service gives it. It only runs once the visitor agrees.', 'no-thanks' ); ?></p>
									<?php elseif ( '' !== $p['id'] ) : ?>
										<p class="description">
											<?php
											/* translators: %s: HTML code example. */
											printf( esc_html__( 'Elsewhere in the site, wrap code in %s to tie it to this purpose.', 'no-thanks' ), '<code>' . esc_html( '<template data-no-thanks="' . $p['id'] . '">' ) . '</code>' );
											?>
										</p>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			<?php endforeach; ?>

			<h2><?php esc_html_e( 'Banner', 'no-thanks' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php $row( 'label', __( 'Title', 'no-thanks' ) ); ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Message', 'no-thanks' ); ?></th>
					<td>
						<?php no_thanks_editor( 'message', $o['message'] ); ?>
						<p class="description"><?php esc_html_e( 'Name the services and what they are for. Changing it asks every visitor again.', 'no-thanks' ); ?></p>
					</td>
				</tr>
				<?php
				$row( 'refuse', __( 'Refuse button', 'no-thanks' ) );
				$row( 'accept', __( 'Accept button', 'no-thanks' ) );
				$row( 'choose', __( 'Link to choose by purpose', 'no-thanks' ), __( 'Only with several purposes, like the texts below about choices.', 'no-thanks' ) );
				$row( 'save', __( 'Button to save the choices', 'no-thanks' ) );
				?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Message when the browser asks not to be tracked', 'no-thanks' ); ?></th>
					<td>
						<?php no_thanks_editor( 'dnt', $o['dnt'] ); ?>
						<p class="description"><?php esc_html_e( 'Such visitors get no tracker: this message shows a few seconds on their first visit, then from the footer link.', 'no-thanks' ); ?></p>
					</td>
				</tr>
				<?php
				$row( 'accept_anyway', __( 'Button to allow anyway', 'no-thanks' ) );
				$row( 'accepted', __( 'Reminder after accepting', 'no-thanks' ), __( 'Shown when the banner is reopened; {date} is replaced by the date of the choice.', 'no-thanks' ) );
				$row( 'refused', __( 'Reminder after refusing', 'no-thanks' ) );
				$row( 'chosen', __( 'Reminder after choosing by purpose', 'no-thanks' ) );
				$row( 'link', __( 'Footer link', 'no-thanks' ), __( 'Added to the footer by the theme. Any other link to #no-thanks also reopens the banner.', 'no-thanks' ) );
				$row( 'allowed', __( 'Corner button when allowed', 'no-thanks' ), __( 'Once the banner is closed, a cookie button in the corner shows the state and reopens it; this text shows on hover.', 'no-thanks' ) );
				$row( 'some', __( 'Corner button when some are allowed', 'no-thanks' ) );
				$row( 'not_allowed', __( 'Corner button when not allowed', 'no-thanks' ) );
				?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
