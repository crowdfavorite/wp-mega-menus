<?php
/**
 * Plugin Name: CF Mega Menus
 * Plugin URI:  http://presspermit.com
 * Description: Advanced yet accessible content permissions. Give users or groups type-specific roles. Enable or block access for specific posts or terms.
 * Author:      Crowd Favorite
 * Author URI:  http://crowdfavorite.com
 * Version:     1.0.1
 */

define('CF_MEGA_MENUS_DIR', trailingslashit(dirname(__FILE__)));

class CF_Mega_Menus {
	
	static function add_hooks() {
		add_action('init', 'CF_Mega_Menus::register_mega_menu_post_type');
		
		// Used during page build
		add_filter('walker_nav_menu_start_el', 'CF_Mega_Menus::display_mega_menu_contents', 100, 4);
		
		// Used during admin
		add_filter('wp_edit_nav_menu_walker', 'CF_Mega_Menus::edit_nav_menu_walker');
		add_action('wp_update_nav_menu_item', 'CF_Mega_Menus::save_mega_menu_setting', 10, 2);
		
		// The following is used to add build support where Carrington Build is installed
		add_filter('cfct-build-enabled-post-types', 'CF_Mega_Menus::add_carrington_build_support');
	}
	
	static function register_mega_menu_post_type() {
		register_post_type('cf_mega_menu', array(
			'labels' => array(
				'name' => __('Mega Menus'),
				'singular_name' => __('Mega Menu'),
				'menu_name' => __('Mega Menus'),
				'all_items' => __('All Mega Menus'),
				'add_new_item' => __('Add New Mega Menu'),
				'edit_item' => __('Edit Mega Menu'),
				'new_item' => __('New Mega Menu'),
				'view_item' => __('View Mega Menu'),
				'search_items' => __('Search Mega Menus'),
				'not_found' => __('No mega menus found'),
				'not_found_in_trash' => __('No mega menus found in trash'),
			),
			'description' => __('A utility post type used to control contents within mega menu dropdowns'),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 30,
			'hierarchical' => true,
			'supports' => array(
				'title',
				'editor',
			)
		));
	}
	
	static function display_mega_menu_contents($output, $item, $depth, $args) {
		$item = (array) $item;
		$args = (array) $args;
		if (empty($args['hide_mega_menu']) && $depth == 0 && empty($item['has_children'])) {
			$mega_menu_id = get_post_meta($item['ID'], '_cf_mega_menu_id', true);
			if (!empty($mega_menu_id) && ($mega_menu = get_post($mega_menu_id)) && !is_wp_error($mega_menu)) {
				// We have a mega menu to display.
				$wrapper_classes = apply_filters('cf-mega-menu-classes', array('cf-mega-menu'), $item, $depth, $args);
				global $post;
				$old_post = $post;
				$post = $mega_menu;
				setup_postdata($mega_menu);
				ob_start();
				the_content();
				$contents = ob_get_clean();
				wp_reset_postdata();
				if (!empty($contents)) {
					$output .= '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . "\">\n";
					$output .= $contents;
					$output .= "</div>\n";
				}
				$post = $old_post;
				setup_postdata($post);
			}
		}
		return $output;
	}
	
	static function edit_nav_menu_walker($walker_classname) {
		include_once(CF_MEGA_MENUS_DIR.'lib/class.cf-mega-menu-edit-walker.php');
		return 'CF_Mega_Menu_Walker_Nav_Menu_Edit';
	}
	
	static function save_mega_menu_setting($menu_id, $menu_item_id) {
		$mega_menu_id = false;
		if (!empty($_REQUEST['menu-item-mega-menu']) && !empty($_REQUEST['menu-item-mega-menu'][$menu_item_id])) {
			$mega_menu_id = intval($_REQUEST['menu-item-mega-menu'][$menu_item_id]);
		}
		if (!empty($mega_menu_id)) {
			update_post_meta($menu_item_id, '_cf_mega_menu_id', $mega_menu_id);
		}
		else {
			delete_post_meta($menu_item_id, '_cf_mega_menu_id');
		}
	}
	
	static function add_carrington_build_support($post_types) {
		return array_merge($post_types, array('cf_mega_menu'));
	}
	
}
CF_Mega_Menus::add_hooks();
