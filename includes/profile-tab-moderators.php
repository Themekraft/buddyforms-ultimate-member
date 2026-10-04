<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'init', 'buddyforms_moderators_ultimate_member_integration', 9999 );

function buddyforms_moderators_ultimate_member_integration() {

	$ultimate_members_settings = get_option( 'buddyforms_ultimate_settings' );

	$integrate = ( ! empty( $ultimate_members_settings ) && ! empty( $ultimate_members_settings['moderation_tab'] ) && $ultimate_members_settings['moderation_tab'] === 'activate' );
	if ( $integrate ) {
		add_filter( 'um_profile_tabs', 'buddyforms_moderators_um_add_tab', 9999 );
	}

}


// You could set the default privacy for custom tab and disable to change the tab privacy settings in admin menu.
function buddyforms_moderators_um_add_tab( $tabs ) {
	$ultimate_members_settings = get_option( 'buddyforms_ultimate_settings' );

	$integrate = ( ! empty( $ultimate_members_settings ) && ! empty( $ultimate_members_settings['moderation_tab'] ) && $ultimate_members_settings['moderation_tab'] === 'activate' );

	UM()->options()->options['profile_tab_buddyforms_moderation'] = $integrate;

	if ( ! $integrate ) {
		return $tabs;
	}

	$tab_name = apply_filters( 'buddyforms_ultimate_member_moderation_tab_title', __( 'Moderate Posts', 'buddyforms-ultimate-member' ) );
	if ( ! empty( $ultimate_members_settings['moderation_tab_name'] ) ) {
		$tab_name = $ultimate_members_settings['moderation_tab_name'];
	}

	$args = array(
		'name'   => $tab_name,
		'icon'   => 'um-faicon-magic',
		'custom' => true,
	);

	$tabs['buddyforms_moderation'] = $args;

	return $tabs;
}

/**
 * Check an ability to view tab
 *
 * @param $tabs
 *
 * @return mixed
 */
function buddyforms_moderators_um_add_tab_visibility( $tabs ) {
	if ( empty( $tabs['buddyforms_moderation'] ) ) {
		return $tabs;
	}

	$user_id = um_profile_id();

	if ( ! user_can( $user_id, '{here some capability which you need to check}' ) ) {
		unset( $tabs['buddyforms_moderation'] );
	}

	return $tabs;
}

// add_filter( 'um_user_profile_tabs', 'buddyforms_moderators_um_add_tab_visibility', 2000, 1 );

// Action
function um_profile_content_buddyforms_moderators_um_add_tab_default( $args ) {
	if ( defined( 'BUDDYFORMS_MODERATION_ASSETS' ) ) {
		echo do_shortcode( '[buddyforms_list_posts_to_moderate]' );
	}
}

add_action( 'um_profile_content_buddyforms_moderation_default', 'um_profile_content_buddyforms_moderators_um_add_tab_default' );
