<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add a custom tabs to the profile
add_filter( 'um_profile_tabs', 'bf_profile_tabs', 2000 );
add_filter( 'um_user_profile_tabs', 'bf_profile_tabs', 2000 );

function bf_profile_tabs( $tabs ) {
	global $buddyforms, $bf_um_tabs, $bf_um_form_slug;

	// run through all forms and check if they should get integrated
	if ( isset( $buddyforms ) && is_array( $buddyforms ) ) {
		foreach ( $buddyforms as $form_slug => $form ) {
			if ( isset( $form['ultimate_members_profiles_integration'] ) ) {

				$show = false;
				if ( $form['um_profile_visibility'] == 'logged_in_user' && is_user_logged_in() ) {
					$show = true;
				}

				if ( $form['um_profile_visibility'] == 'private' && is_user_logged_in() && um_is_user_himself() ) {
					$show = true;
				}

				if ( $form['um_profile_visibility'] == 'any' ) {
					$show = true;
				}

				if ( ! $show ) {
					continue;
				}

				// Set the Tap slug
				$parent_tab_slug = bf_ultimate_member_parent_tab( $form );

				// Set the Tab name
				$parent_tab_name = $form['name'];
				if ( ! empty( $form['um_profile_menu_label'] ) ) {
					$parent_tab_name = $form['um_profile_menu_label'];
				}

				$icon = 'um-faicon-pencil';
				if ( ! empty( $form['um_profile_menu_icon'] ) ) {
					$icon = $form['um_profile_menu_icon'];
				}

				// Check if the form has a parent tap and use the parent tab name instad the from name
				if ( isset( $form['ultimate_members_profiles_integration'] ) && isset( $form['ultimate_members_profiles_parent_tab'] ) ) {
					$attached_page   = $form['attached_page'];
					$parent_tab_page = get_post( $attached_page, 'OBJECT' );
					$parent_tab_name = $parent_tab_page->post_title;
				}

				// Check if this form is grouped under a Parent Tap and only create the nav item once
				if ( ! isset( $tabs[ $parent_tab_slug ] ) ) {
					$tabs[ $parent_tab_slug ] = array(
						'name'   => $parent_tab_name,
						'icon'   => $icon,
						'custom' => true,
						// 'default_privacy'   => 3
					);
					$tabs[ $parent_tab_slug ]['subnav_default'] = 'posts-' . $form_slug;
					$bf_um_tabs                                 = $tabs;

					add_action( 'um_profile_content_' . $parent_tab_slug . '_default', 'bf_profile_tabs_content', 1, 10 );

				}

				$tab_name = ! empty( $form['singular_name'] ) ? $form['singular_name'] : $form['name'];

				// Add the Subtabs to the Ultimate Member Menue
				$tab_view_name = __( 'View ', 'buddyforms-ultimate-member' ) . $tab_name;
				$tabs[ $parent_tab_slug ]['subnav'][ 'posts-' . $form_slug ] = apply_filters( 'bf_ultimate_member_view_tab_name', $tab_view_name, $form_slug );

				// Add the Subtab for the create only if diplayd profil is from loged in user.
				if ( um_is_user_himself() ) {
					// Check if the user has the needed rights
					$current_user_id         = um_user( 'ID' );
					$current_user_can_create = bf_user_can( $current_user_id, 'buddyforms_' . $form_slug . '_create', array(), $form_slug );
					if ( $current_user_can_create ) {
						$tab_form_name = __( 'Create ', 'buddyforms-ultimate-member' ) . $tab_name;
						$tabs[ $parent_tab_slug ]['subnav'][ 'form-' . $form_slug ] = apply_filters( 'bf_ultimate_member_form_tab_name', $tab_form_name, $form_slug );
					}
				}

				// Hook the content into the coret tabs
				add_action( 'um_profile_content_' . $parent_tab_slug . '_posts-' . $form_slug, 'bf_profile_tabs_content' );
				add_action( 'um_profile_content_' . $parent_tab_slug . '_form-' . $form_slug, 'bf_profile_tabs_content' );

			}
		}
	}

	$bf_um_tabs = $tabs;

	return $tabs;
}

//
// Display the Tab Content
//
function bf_profile_tabs_content( $subnav_defalt ) {
	global $bf_um_tabs;

	if ( ! isset( $_GET['profiletab'] ) ) {
		return;
	};
	$parent_tab = sanitize_text_field( wp_unslash( $_GET['profiletab'] ) );

	$subnav_slug_default = $bf_um_tabs[ $parent_tab ]['subnav_default'];

	$subnav_slug = isset( $_GET['subnav'] ) ? sanitize_text_field( wp_unslash( $_GET['subnav'] ) ) : $subnav_slug_default;

	$form_slug = strstr( $subnav_slug, '-' );
	$form_slug = substr( $form_slug, 1 );

	$profiletab_type = explode( '-', $subnav_slug );
	$profiletab_type = $profiletab_type[0];

	// Check if the ultimate member view is a form view and add the coret content
	if ( isset( $_GET['profiletab'] ) && sanitize_text_field( wp_unslash( $_GET['profiletab'] ) ) == $parent_tab ) {

		$action = ( isset( $_GET['bf_um_action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['bf_um_action'] ) ) : 'create';
		if ( isset( $subnav_slug ) && $profiletab_type == 'posts' ) {

			$paged = 1;
			if ( isset( $_GET['bf_um_page'] ) && ! empty( $_GET['bf_um_page'] ) ) {
				$paged = (int) $_GET['bf_um_page'];
			}

			$um_user_id = um_profile_id();

			// Display the posts
			echo do_shortcode( '[buddyforms_the_loop paged=' . $paged . ' user_logged_in_only="false" form_slug="' . $form_slug . '" author="' . $um_user_id . '"]' );

		} elseif ( isset( $_GET['subnav'] ) && $profiletab_type == 'form' ) {

			// Create the arguments aray for the form to get displayed
			$args = array(
				'form_slug' => $form_slug,
			);
			if ( $action !== 'create' ) {
				// Add the post id if post edit
				if ( isset( $_GET['bf_post_id'] ) ) {
					$args['post_id'] = sanitize_text_field( wp_unslash( $_GET['bf_post_id'] ) );
				}

				// Add the revisionsid if needed
				if ( isset( $_GET['bf_rev_id'] ) ) {
					$args['revision_id'] = sanitize_text_field( wp_unslash( $_GET['bf_rev_id'] ) );
				}
			}

			wp_enqueue_style( 'buddyforms-ultimate-member-css', BUDDYFORMS_ULTIMATE_MEMBER_ASSETS . 'css/buddyforms-ultimate-member.css' );

			buddyforms_create_edit_form( $args );
		}
	}
}

/**
 * Clear the URL parameter to differentiate when the form is load to create or edit an entry
 *
 * @param $subnav_link
 * @param $id_s
 * @param $subtab
 *
 * @return mixed
 * @since 1.3.4 Change the url for the subnav link to differentiate the create from edit
 */
function bf_um_profile_subnav_link( $subnav_link, $id_s, $subtab ) {
	$id_array     = explode( '-', $id_s );
	$tab_type     = $id_array[0];
	$bf_um_action = get_query_var( 'bf_um_action', $subnav_link );
	if ( $tab_type === 'form' && ( ! empty( $bf_um_action ) && $bf_um_action !== 'edit' ) ) {
		$subnav_link = add_query_arg( 'bf_um_action', 'create', $subnav_link );
		$subnav_link = remove_query_arg( 'bf_post_id', $subnav_link );
	}

	return $subnav_link;
}

add_filter( 'um_user_profile_subnav_link', 'bf_um_profile_subnav_link', 2000, 3 );

