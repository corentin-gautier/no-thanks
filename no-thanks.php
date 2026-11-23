<?php
/**
 * Plugin Name: No Thanks
 * Description: An ultra-light consent banner: no dependency, no cookie, respects Do Not Track and Global Privacy Control.
 * Version: 0.1.3
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Corentin Gautier
 * License: GPL-3.0-or-later
 * Text Domain: no-thanks
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'NO_THANKS_VERSION', '0.1.3' );

require __DIR__ . '/lib/settings.php';
require __DIR__ . '/lib/front.php';
require __DIR__ . '/lib/privacy.php';

add_action( 'init', function () {
	load_plugin_textdomain( 'no-thanks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

/**
 * Default texts, in the site language. The message links to the privacy policy page when there is one.
 *
 * @return array<string, string>
 */
function no_thanks_defaults(): array {
	$privacy = get_privacy_policy_url();
	$message = __( 'This site would like to measure its audience. Is that OK?', 'no-thanks' );
	if ( $privacy ) {
		$message .= sprintf( ' <a href="%s">%s</a>', esc_url( $privacy ), __( 'Learn more', 'no-thanks' ) );
	}

	return [
		'label'         => __( 'Cookies and trackers', 'no-thanks' ),
		'message'       => $message,
		'dnt'           => __( 'Your browser asks not to be tracked: noted, no tracker is on.', 'no-thanks' ),
		'refuse'        => __( 'No thanks', 'no-thanks' ),
		'accept'        => __( 'OK', 'no-thanks' ),
		'accept_anyway' => __( 'Allow anyway', 'no-thanks' ),
		/* translators: {date} is replaced, keep it as is. */
		'accepted'      => __( 'You agreed on {date}.', 'no-thanks' ),
		/* translators: {date} is replaced, keep it as is. */
		'refused'       => __( 'You refused on {date}.', 'no-thanks' ),
		/* translators: {date} is replaced, keep it as is. */
		'chosen'        => __( 'Your choices of {date}.', 'no-thanks' ),
		'choose'        => __( 'Choose', 'no-thanks' ),
		'save'          => __( 'Save', 'no-thanks' ),
		'link'          => __( 'Cookies', 'no-thanks' ),
		'allowed'       => __( 'Cookies allowed', 'no-thanks' ),
		'some'          => __( 'Some cookies allowed', 'no-thanks' ),
		'not_allowed'   => __( 'Cookies not allowed', 'no-thanks' ),
	];
}

/**
 * The purposes: id, label, description and the scripts each one holds back. Settings saved before
 * purposes existed (`head`, `body`) become one « Audience measurement » purpose.
 *
 * @return list<array{id: string, label: string, description: string, head: string, body: string}>
 */
function no_thanks_purposes( array $saved ): array {
	$purposes = $saved['purposes'] ?? null;
	if ( null === $purposes ) {
		$purposes = [ [ 'id' => 'audience', 'label' => '', 'head' => $saved['head'] ?? '', 'body' => $saved['body'] ?? '' ] ];
	}
	return array_map( fn( $p ) => [
		'id'          => (string) ( $p['id'] ?? 'audience' ),
		'label'       => (string) ( $p['label'] ?? '' ) ?: __( 'Audience measurement', 'no-thanks' ),
		'description' => (string) ( $p['description'] ?? '' ),
		'head'        => (string) ( $p['head'] ?? '' ),
		'body'        => (string) ( $p['body'] ?? '' ),
	], (array) $purposes );
}

/**
 * Plugin options: the texts (an empty one falls back to its default) and the purposes with their scripts.
 *
 * @return array<string, mixed>
 */
function no_thanks_options(): array {
	$saved   = (array) get_option( 'no_thanks', [] );
	$options = [ 'purposes' => no_thanks_purposes( $saved ) ];
	foreach ( no_thanks_defaults() as $key => $default ) {
		$options[ $key ] = ( $saved[ $key ] ?? '' ) ?: $default;
	}
	return $options;
}

/**
 * Whether the banner is on: some script waits for consent, or several purposes are set
 * (their blocked content may be in the pages, as `<template data-no-thanks="id">`).
 */
function no_thanks_active(): bool {
	$purposes = no_thanks_options()['purposes'];
	$scripts  = implode( '', array_map( fn( $p ) => $p['head'] . $p['body'], $purposes ) );
	return '' !== trim( $scripts ) || count( $purposes ) > 1;
}

/**
 * A link that reopens the banner, for a footer. Empty while the banner is off.
 *
 * @param string $label Link text, the one set in Settings › No Thanks by default.
 */
function no_thanks_link( string $label = '' ): string {
	if ( ! no_thanks_active() ) {
		return '';
	}
	return sprintf( '<a href="#no-thanks">%s</a>', esc_html( $label ?: no_thanks_options()['link'] ) );
}
