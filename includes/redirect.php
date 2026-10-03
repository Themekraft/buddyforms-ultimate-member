<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect the user to their respective profile page
 *
 * @package BuddyForms
 * @since 0.3 beta
 */
function bf_ultimate_member_redirect_to_profile() {
	global $post;

	if ( ! isset( $post->ID ) || ! is_user_logged_in() ) {
		return false;
	}

	$link = bf_ultimate_member_get_redirect_link( $post->ID );

	if ( ! empty( $link ) ) :
		wp_safe_redirect( $link );
		exit;
	endif;
}

add_action( 'template_redirect', 'bf_ultimate_member_redirect_to_profile', 999 );

/**
 * Get the redirect link
 *
 * @package BuddyForms
 * @since 0.3 beta
 */
function bf_ultimate_member_get_redirect_link( $id = false ) {
	global $buddyforms, $wp_query, $current_user;

	if ( ! $id ) {
		return false;
	}

	if ( ! isset( $wp_query->query_vars['bf_form_slug'] ) ) {
		return false;
	}

	$form_slug = $wp_query->query_vars['bf_form_slug'];

	if ( ! isset( $buddyforms[ $form_slug ] ) ) {
		return false;
	}

	$parent_tab = bf_ultimate_member_parent_tab( $buddyforms[ $form_slug ] );

	$link = '';
	if ( isset( $buddyforms ) && is_array( $buddyforms ) && isset( $parent_tab ) ) {

		if ( isset( $buddyforms[ $form_slug ]['attached_page'] ) ) {
			$attached_page_id = $buddyforms[ $form_slug ]['attached_page'];
		}

		// Only create a new url if the profile inetgration is enabled and the corect attached page displayed.
		if ( isset( $buddyforms[ $form_slug ]['ultimate_members_profiles_integration'] ) && isset( $attached_page_id ) && $attached_page_id == $id ) {

			$um_options = get_option( 'um_options' );

			$current_user = wp_get_current_user();
			$userdata     = get_userdata( $current_user->ID );

			switch ( $um_options['permalink_base'] ) {
				case 'user_login':
					$user_permalink = $userdata->user_login;
					break;
				case 'user_id':
					$user_permalink = $userdata->ID;
					break;
				default:
					$user_permalink = $userdata->user_login;
			}

			$link = get_the_permalink( $um_options['core_user'] ) . $userdata->user_nicename . '?profiletab=' . $parent_tab;

			// Check the bf_action action query var and create the Ultimate member url for the correct action
			if ( isset( $wp_query->query_vars['bf_action'] ) ) {
				if ( $wp_query->query_vars['bf_action'] == 'create' ) {
					$link = get_the_permalink( $um_options['core_user'] ) . $user_permalink . '?profiletab=' . $parent_tab . '&subnav=form-' . $form_slug;
				}
				if ( $wp_query->query_vars['bf_action'] == 'edit' ) {
					$link = get_the_permalink( $um_options['core_user'] ) . $user_permalink . '?profiletab=' . $parent_tab . '&subnav=form-' . $form_slug . '&bf_post_id=' . $wp_query->query_vars['bf_post_id'];
				}
				if ( $wp_query->query_vars['bf_action'] == 'revision' ) {
					$link = get_the_permalink( $um_options['core_user'] ) . $user_permalink . '?profiletab=' . $parent_tab . '&subnav=form-' . $form_slug . '&bf_post_id=' . $wp_query->query_vars['bf_post_id'] . '&bf_rev_id=' . $wp_query->query_vars['bf_rev_id'];
				}
				if ( $wp_query->query_vars['bf_action'] == 'view' ) {
					$link = get_the_permalink( $um_options['core_user'] ) . $user_permalink . '?profiletab=' . $parent_tab . '&subnav=posts-' . $form_slug;
				}
			}
		}
	}

	return apply_filters( 'bf_ultimate_member_get_redirect_link', $link );
}

/**
 * Link router function
 *
 * @package BuddyForms
 * @since 0.3 beta
 * @uses    bp_get_option()
 * @uses    is_page()
 * @uses    bp_loggedin_user_domain()
 */
function bf_ultimate_member_page_link_router( $link, $id ) {
	if ( ! is_user_logged_in() || is_admin() ) {
		return $link;
	}

	$new_link = bf_ultimate_member_get_redirect_link( $id );

	if ( ! empty( $new_link ) ) {
		$link = $new_link;
	}

	return apply_filters( 'bf_ultimate_member_page_link_router', $link );
}

add_filter( 'page_link', 'bf_ultimate_member_page_link_router', 10, 2 );

//
// Link Router for the Loop
//
function bf_ultimate_member_page_link_router_edit( $link, $id ) {
	global $buddyforms, $current_user;

	$form_slug  = get_post_meta( $id, '_bf_form_slug', true );
	$um_options = get_option( 'um_options' );

	$form_settings = isset( $buddyforms[ $form_slug ] ) ? $buddyforms[ $form_slug ] : false;

	if ( empty( $form_settings ) ) {
		return $link;
	}

	if ( empty( $form_settings['ultimate_members_profiles_integration'] ) || ( ! empty( $form_settings['ultimate_members_profiles_integration'] ) && $form_settings['ultimate_members_profiles_integration'][0] !== 'integrate' ) ) {
		return $link;
	}

	$parent_tab = bf_ultimate_member_parent_tab( $buddyforms[ $form_slug ] );

	$current_user = wp_get_current_user();
	$userdata     = get_userdata( $current_user->ID );

	$link_href = get_the_permalink( $um_options['core_user'] ) . $userdata->user_nicename . '?profiletab=' . $parent_tab . '&bf_um_action=edit&subnav=form-' . $form_slug . '&bf_post_id=' . $id;

	return '<a title="Edit" id="' . $id . '" class="bf_edit_post" href="' . $link_href . '">' . __( 'Edit', 'buddyforms-ultimate-member' ) . '</a>';
}

add_filter( 'buddyforms_loop_edit_post_link', 'bf_ultimate_member_page_link_router_edit', 20, 2 );
