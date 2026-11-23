<?php
/**
 * Suggested text for the privacy policy (Settings › Privacy › Policy Guide), with the services found in the scripts.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Known services, spotted in the pasted scripts by a piece of their code.
 *
 * @return array<string, array{0: string, 1: string}> Needle => [ name and provider, privacy policy URL ].
 */
function no_thanks_known_services(): array {
	return [
		'googletagmanager.com' => [ 'Google Analytics / Google Tag Manager (Google)', 'https://policies.google.com/privacy' ],
		'google-analytics.com' => [ 'Google Analytics (Google)', 'https://policies.google.com/privacy' ],
		'matomo'               => [ 'Matomo', 'https://matomo.org/privacy-policy/' ],
		'connect.facebook.net' => [ 'Meta Pixel (Meta)', 'https://www.facebook.com/privacy/policy/' ],
		'hotjar'               => [ 'Hotjar', 'https://www.hotjar.com/legal/policies/privacy/' ],
		'clarity.ms'           => [ 'Microsoft Clarity (Microsoft)', 'https://privacy.microsoft.com/privacystatement' ],
		'snap.licdn.com'       => [ 'LinkedIn Insight Tag (LinkedIn)', 'https://www.linkedin.com/legal/privacy-policy' ],
		'youtube.com'          => [ 'YouTube (Google)', 'https://policies.google.com/privacy' ],
		'plausible.io'         => [ 'Plausible', 'https://plausible.io/privacy' ],
	];
}

/**
 * The suggested text: how consent works here, then one line per service to complete.
 */
function no_thanks_privacy_text(): string {
	$o       = no_thanks_options();
	$scripts = $o['head'] . $o['body'];
	$items   = [];
	foreach ( no_thanks_known_services() as $needle => [ $name, $url ] ) {
		if ( false !== stripos( $scripts, $needle ) && ! isset( $items[ $name ] ) ) {
			$items[ $name ] = sprintf(
				/* translators: 1: service name, 2: link to its privacy policy. */
				__( '%1$s: [what it is used for]. Its cookies are kept [how long]. Its privacy policy: %2$s', 'no-thanks' ),
				esc_html( $name ),
				sprintf( '<a href="%1$s">%1$s</a>', esc_url( $url ) )
			);
		}
	}
	if ( ! $items ) {
		$items[] = esc_html__( '[Service] ([provider]): [what it is used for]. Its cookies are kept [how long]. Its privacy policy: [link]', 'no-thanks' );
	}

	$p = fn( string $text ) => '<p>' . $text . '</p>';
	return '<h2>' . esc_html__( 'Cookies and trackers', 'no-thanks' ) . '</h2>'
		. '<p class="privacy-policy-tutorial">' . esc_html__( 'No Thanks only lets the scripts set in its settings run once the visitor agrees. Complete the list of services: what each one is for and how long its cookies last.', 'no-thanks' ) . '</p>'
		. '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'no-thanks' ) . ' </strong>'
		. esc_html__( 'This site only uses trackers (cookies and similar technologies) with your consent: none is set before you answer, and refusing is as easy as accepting.', 'no-thanks' ) . '</p>'
		. $p( esc_html__( 'Your answer is kept in your browser for 6 months (in its local storage, not in a cookie); we then ask again. If your browser asks not to be tracked (Do Not Track or Global Privacy Control), we take it as a refusal.', 'no-thanks' ) )
		. $p( esc_html__( 'You can change your mind at any time with the cookie button in the corner of each page. When you withdraw your consent, the trackers’ cookies are deleted.', 'no-thanks' ) )
		. $p( esc_html__( 'Services that may set trackers on this site:', 'no-thanks' ) )
		. '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
}

add_action( 'admin_init', function () {
	if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		wp_add_privacy_policy_content( 'No Thanks', wp_kses_post( no_thanks_privacy_text() ) );
	}
} );
