<?php
/*
 * Plugin Name: BuddyForms Ultimate Member
 * Plugin URI: https://themekraft.com/products/ultimate-member/
 * Description: Extend Ultimate Member Profiles with BuddyForms
 * Version: 1.3.18
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Requires Plugins: buddyforms, ultimate-member
 * Author: ThemeKraft
 * Author URI: https://themekraft.com/buddyforms/
 * License: GPLv2 or later
 * Text Domain: buddyforms-ultimate-member
 * Domain Path: /languages
 * Svn: buddyforms-ultimate-member
 *****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *****************************************************************************
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'buddyforms_addon_plugin_dependencies_slug' ) ) {
	/**
	 * Let the running BuddyForms copy (free or premium) satisfy "Requires Plugins: buddyforms".
	 *
	 * @param string $slug Dependency slug.
	 *
	 * @return string
	 */
	function buddyforms_addon_plugin_dependencies_slug( $slug ) {
		if ( 'buddyforms' === $slug && defined( 'BUDDYFORMS_INSTALL_PATH' ) ) {
			return basename( BUDDYFORMS_INSTALL_PATH );
		}

		return $slug;
	}

	add_filter( 'wp_plugin_dependencies_slug', 'buddyforms_addon_plugin_dependencies_slug' );
}

add_action( 'init', 'buddyforms_ultimate_members_init' );

function buddyforms_ultimate_members_init() {

	// Check if Ultimate Member is activated
	if ( ! class_exists( 'UM' ) ) {
		return;
	}

	load_plugin_textdomain( 'buddyforms-ultimate-member', false, basename( dirname( __FILE__ ) ) . '/languages' );

	define( 'BUDDYFORMS_ULTIMATE_MEMBER_ASSETS', plugins_url( 'assets/', __FILE__ ) );

	// Require all needed files
	require dirname( __FILE__ ) . '/includes/form-builder.php';
	require dirname( __FILE__ ) . '/includes/functions.php';
	require dirname( __FILE__ ) . '/includes/redirect.php';
	require dirname( __FILE__ ) . '/includes/ultimate-member-extension.php';
	require dirname( __FILE__ ) . '/includes/profile-tab-cpublishing.php';
	require dirname( __FILE__ ) . '/includes/profile-tab-moderators.php';
	require dirname( __FILE__ ) . '/includes/ultimate-member-settings.php';

	buddyforms_ultimate_update_new_version_136();
}

/**
 * Update for new version 1.3.6 delete it in the feature
 */
function buddyforms_ultimate_update_new_version_136() {
	$updated_136 = get_option( 'buddyforms_ultimate_member_update_136', false );
	if ( empty( $updated_136 ) ) {
		global $buddyforms;
		$integrate_moderation   = false;
		$integrate_cpublisching = false;
		foreach ( $buddyforms as $form_slug => $buddyform ) {
			if ( isset( $buddyform['ultimate_members_moderation_integration'] ) ) {
				$integrate_moderation = true;
				break;
			}
		}
		foreach ( $buddyforms as $form_slug => $buddyform ) {
			if ( isset( $buddyform['ultimate_members_cpublisching_moderation_integration'] ) ) {
				$integrate_cpublisching = true;
				break;
			}
		}
		$new_option = array();
		if ( $integrate_moderation ) {
			$new_option['moderation_tab'] = 'activate';
		}
		if ( $integrate_cpublisching ) {
			$new_option['collaborative_post_tab'] = 'activate';
		}

		update_option( 'buddyforms_ultimate_settings', $new_option );
		update_option( 'buddyforms_ultimate_member_update_136', true );
	}

}

// Create a helper function for easy SDK access.
function buddyforms_um_fs() {
	global $buddyforms_um_fs;

	if ( ! isset( $buddyforms_um_fs ) ) {
		// Include Freemius SDK.
		if ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php' ) ) {
			// Try to load SDK from parent plugin folder.
			require_once dirname( dirname( __FILE__ ) ) . '/buddyforms/includes/resources/freemius/start.php';
		} elseif ( file_exists( dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php' ) ) {
			// Try to load SDK from premium parent plugin folder.
			require_once dirname( dirname( __FILE__ ) ) . '/buddyforms-premium/includes/resources/freemius/start.php';
		}

		$buddyforms_um_fs = fs_dynamic_init(
			array(
				'id'                  => '961',
				'slug'                => 'buddyforms-ultimate-member',
				'type'                => 'plugin',
				'public_key'          => 'pk_665b8cfafdebbc7171dd9b787e770',
				'is_premium'          => true,
				// If your addon is a serviceware, set this option to false.
				'has_premium_version' => true,
				'has_paid_plans'      => true,
				'trial'               => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
				'parent'              => array(
					'id'         => '391',
					'slug'       => 'buddyforms',
					'public_key' => 'pk_dea3d8c1c831caf06cfea10c7114c',
					'name'       => 'BuddyForms',
				),
				'menu'                => array(
					'slug'       => 'edit.php?post_type=buddyforms',
					'first-path' => 'edit.php?post_type=buddyforms&page=buddyforms_welcome_screen',
					'support'    => false,
				),
				'bundle_license_auto_activation' => true,
			)
		);
	}

	return $buddyforms_um_fs;
}

function buddyforms_um_fs_is_parent_active_and_loaded() {
	// Check if the parent's init SDK method exists.
	return function_exists( 'buddyforms_core_fs' );
}

function buddyforms_um_fs_is_parent_active() {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
	}

	foreach ( $active_plugins as $basename ) {
		if ( 0 === strpos( strtolower( $basename ), 'buddyforms/' ) ||
			 0 === strpos( strtolower( $basename ), 'buddyforms-premium/' )
		) {
			return true;
		}
	}

	return false;
}

function buddyforms_um_fs_init() {
	if ( buddyforms_um_fs_is_parent_active_and_loaded() ) {
		// Init Freemius.
		buddyforms_um_fs();

		// Parent is active, add your init code here.
	} else {
		// Parent is inactive, add your error handling here.
	}
}

if ( buddyforms_um_fs_is_parent_active_and_loaded() ) {
	// If parent already included, init add-on.
	buddyforms_um_fs_init();
} elseif ( buddyforms_um_fs_is_parent_active() ) {
	// Init add-on only after the parent is loaded.
	add_action( 'buddyforms_core_fs_loaded', 'buddyforms_um_fs_init' );
} else {
	// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
	buddyforms_um_fs_init();
}
