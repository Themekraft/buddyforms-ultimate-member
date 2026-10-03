<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//
// Load all needed css and js
// @todo: this should not be global.
//
function bf_um_front_js_css_loader( $fount ) {
	return true;
}

add_filter( 'buddyforms_front_js_css_loader', 'bf_um_front_js_css_loader', 10, 1 );

//
// Helper function to get the parent slug for the profil navigation
// Coppy of function rewrite_rules /ultimate-member/core/um-rewrite.php line 31
// @todo: crearte a pull request to fire the rewrire function if set to not load automaticaly...?!

function bf_ultimate_member_parent_tab( $member_form ) {

	// By default use the form slug for the tab
	$parent_tab_name = $member_form['slug'];

	// Check if form is a sub tab of a parent.
	if ( isset( $member_form['ultimate_members_profiles_parent_tab'] ) ) {
		$parent_tab = $member_form['ultimate_members_profiles_parent_tab'];
	}

	// If the form is a sub form of a parent tab get the attached page slug as parent.
	if ( isset( $member_form['attached_page'] ) && isset( $parent_tab ) ) {
		$attached_page   = $member_form['attached_page'];
		$parent_tab_page = get_post( $attached_page, 'OBJECT' );
		$parent_tab_name = $parent_tab_page->post_name;
	}

	return str_replace( '-', '', $parent_tab_name );
}

//
// Prevent Ultimate Member from flush rewrite roles bei every page load.
// @todo: Rewrite roles should get flushed by every option save form Ultimate Member
//
// add_filter( 'um_rewrite_flush_rewrite_rules', 'buddyforms_um_rewrite_flush_rewrite_rules' );
function buddyforms_um_rewrite_flush_rewrite_rules() {
	return true;
}


add_action( 'buddyforms_process_submission_end', 'buddyforms_um_add_new_submissions_to_the_um_activity_component' );
function buddyforms_um_add_new_submissions_to_the_um_activity_component( $args ) {
	global $buddyforms;

	$post_id   = $args['post_id'];
	$form_slug = $args['form_slug'];

	if ( ! defined( 'um_activity_url' ) ) {
		return;
	}

	// Check if form is a sub tab of a parent.
	if ( ! isset( $buddyforms[ $form_slug ]['ultimate_members_social_activity'] ) ) {
		return;
	}

	$post_status = get_post_status( $post_id );

	if ( $post_status != 'publish' ) {
		return;
	}

	$post = get_post( $post_id );

	$buddyforms_um_activity = get_post_meta( $post_id, 'buddyforms_um_activity', true );
	if ( $buddyforms_um_activity == 'published' ) {
		return;
	}

	$user_id = $post->post_author;

	um_fetch_user( $user_id );
	$author_name    = um_user( 'display_name' );
	$author_profile = um_user_profile_url();

	if ( has_post_thumbnail( $post_id ) ) {
		$image      = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'large' );
		$post_image = '<span class="post-image"><img src="' . $image[0] . '" alt="" title="" class="um-activity-featured-img" /></span>';
	} else {
		$post_image = '';
	}

	if ( $post->post_content ) {
		$post_excerpt = '<span class="post-excerpt">' . wp_trim_words( $post->post_content, $num_words = 25, $more = null ) . '</span>';
	} else {
		$post_excerpt = '';
	}

	UM()->Activity_API()->api()->save(
		array(
			'template'       => 'new-post',
			'wall_id'        => $user_id,
			'related_id'     => $post_id,
			'author'         => $user_id,
			'author_name'    => $author_name,
			'author_profile' => $author_profile,
			'post_title'     => '<span class="post-title">' . $post->post_title . '</span>',
			'post_url'       => get_permalink( $post_id ),
			'post_excerpt'   => $post_excerpt,
			'post_image'     => $post_image,
		)
	);

	update_post_meta( $post_id, 'buddyforms_um_activity', 'published' );

}

add_filter( 'buddyforms_ask_to_become_an_author_url', 'buddyforms_cpublishing_ask_to_become_an_author_url' );

function buddyforms_cpublishing_ask_to_become_an_author_url( $url, $user_id ) {
	um_fetch_user( $user_id );

	return um_user_profile_url();
}

function buddyforms_moderation_create_submission_link( $default_link, $form_slug, $args ) {
	if ( empty( $form_slug ) ) {
		return $default_link;
	}
	$is_ultimate_members_profiles_integration = buddyforms_get_form_option( $form_slug, 'ultimate_members_profiles_integration' );
	if ( empty( $is_ultimate_members_profiles_integration ) ) {
		return $default_link;
	}
	$user_id = get_current_user_id();
	if ( empty( $user_id ) ) {
		return $default_link;
	}

	$attached_page     = buddyforms_get_form_option( $form_slug, 'attached_page' );
	$attached_page_url = get_permalink( $attached_page );

	$attached_page_url = add_query_arg( 'subnav', 'form-' . $form_slug, $attached_page_url );
	$attached_page_url = add_query_arg( 'bf_um_action', 'create', $attached_page_url );

	return $attached_page_url;
}

add_filter( 'buddyforms_create_submission_link', 'buddyforms_moderation_create_submission_link', 10, 3 );


add_filter( 'buddyforms_next_posts_link', 'buddyforms_um_next_posts_link', 10, 2 );
function buddyforms_um_next_posts_link( $link, $form_slug ) {
	global $buddyforms;

	$parent_tab = bf_ultimate_member_parent_tab( $buddyforms[ $form_slug ] );

	if ( ! isset( $_GET['profiletab'] ) ) {
		return $link;
	}

	if ( $_GET['profiletab'] !== $parent_tab || empty( $link ) ) {
		return $link;
	}

	$page = ! empty( $_GET['bf_um_page'] ) ? ( (int) $_GET['bf_um_page'] + 1 ) : 2;
	$link = '<a href="' . add_query_arg( 'bf_um_page', $page ) . '">&larr;' . __( 'Previos Entries ', 'buddyforms-ultimate-member' ) . '</a>';

	return $link;
}

add_filter( 'buddyforms_previos_posts_link', 'buddyforms_um_previos_posts_link', 10, 2 );
function buddyforms_um_previos_posts_link( $link, $form_slug ) {
	global $buddyforms;

	$parent_tab = bf_ultimate_member_parent_tab( $buddyforms[ $form_slug ] );

	if ( ! isset( $_GET['profiletab'] ) ) {
		return $link;
	}

	if ( $_GET['profiletab'] !== $parent_tab || empty( $link ) ) {
		return $link;
	}

	$page = (int) $_GET['bf_um_page'] - 1;
	$link = '<a href="' . add_query_arg( 'bf_um_page', $page ) . '">' . __( 'Next Entries', 'buddyforms-ultimate-member' ) . '&rarr;</a>';

	return $link;
}
