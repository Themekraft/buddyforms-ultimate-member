<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'init', 'buddyforms_cpublishing_ultimate_member_integration', 9999 );

function buddyforms_cpublishing_ultimate_member_integration() {

	$ultimate_members_settings = get_option( 'buddyforms_ultimate_settings' );

	$integrate = ( ! empty( $ultimate_members_settings ) && ! empty( $ultimate_members_settings['collaborative_post_tab'] ) && $ultimate_members_settings['collaborative_post_tab'] === 'activate' );

	if ( $integrate ) {
		add_filter( 'um_profile_tabs', 'buddyforms_cpublishing_um_add_tab', 9999 );
	}

}


// You could set the default privacy for custom tab and disable to change the tab privacy settings in admin menu.
function buddyforms_cpublishing_um_add_tab( $tabs ) {
	$ultimate_members_settings = get_option( 'buddyforms_ultimate_settings' );

	$integrate = ( ! empty( $ultimate_members_settings ) && ! empty( $ultimate_members_settings['collaborative_post_tab'] ) && $ultimate_members_settings['collaborative_post_tab'] === 'activate' );

	UM()->options()->options['profile_tab_buddyforms_cpublishing'] = $integrate;

	if ( ! $integrate ) {
		return $tabs;
	}

	$tab_name = apply_filters( 'buddyforms_ultimate_member_collaborative_tab_title', __( 'Collaborative Posts', 'buddyforms-ultimate-member' ) );
	if ( ! empty( $ultimate_members_settings['collaborative_post_tab_name'] ) ) {
		$tab_name = $ultimate_members_settings['collaborative_post_tab_name'];
	}

	$args = array(
		'name' => $tab_name,
		'icon' => 'um-faicon-gift',
	);

	$tabs['buddyforms_cpublishing'] = $args;

	return $tabs;
}

/**
 * Check an ability to view tab
 *
 * @param $tabs
 *
 * @return mixed
 */
function buddyforms_cpublishing_um_add_tab_visibility( $tabs ) {
	if ( empty( $tabs['buddyforms_cpublishing'] ) ) {
		return $tabs;
	}

	$user_id = um_profile_id();

	if ( ! user_can( $user_id, '{here some capability which you need to check}' ) ) {
		unset( $tabs['buddyforms_cpublishing'] );
	}

	return $tabs;
}

// add_filter( 'um_user_profile_tabs', 'buddyforms_cpublishing_um_add_tab_visibility', 2000, 1 );

// Action
function um_profile_content_buddyforms_cpublishing_default( $args ) {
	if ( ! empty( $GLOBALS['BuddyFormsCPublishing'] ) ) {
		echo do_shortcode( '[buddyforms_list_editor_posts]' );
	}
}

add_action( 'um_profile_content_buddyforms_cpublishing_default', 'um_profile_content_buddyforms_cpublishing_default' );
