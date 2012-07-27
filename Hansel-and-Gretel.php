<? 
/*
Plugin Name: Hansel & Gretel
Plugin URI: http://rodaine.com/wordpress/hansel-and-gretel
Description: Adds fine-tuned breadcrumb generation to your Wordpress Site, including custom structure, markup, and microdata.
Author: Chris Roche & Taylor Gorman [Clark Nikdel Powell]
Author URI: http://clarknikdelpowell.com/
Version: 0.0.1
License: GPL2

Copyright 2012  Clark Nikdel Powell  (email : wordpress@clarknikdelpowell.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once plugin_dir_path(__FILE__).'HAG_Options.php';
require_once plugin_dir_path(__FILE__).'HAG_Utils.php';

final class HAG_Breadcrumbs {
	
	//////////////////////////////////////WORDPRESS PLUGIN ADMINISTRATIVE FUNCTIONS
	
	public static function activate() {
		add_option(HAG_Options::option_name, array(), '', 'yes');
	}
	
	public static function uninstall() {
		delete_option(HAG_Options::option_name);
	}
	
	public static function initialize() {
		//!TODO: Create Admin menu item
	}
	
	private static function debug_info(array $options, $comment = false) {
		global $post;
		
		$output = array();
		$output[] = $comment ? '<!--' : '<pre>';

		$output[] = '######################## HaG DEBUG INFO ########################';
		
		$output[] = sprintf('404: %b', is_404());
		$output[] = sprintf('Search: %b', is_search());
		$output[] = sprintf('Archive: %b', is_archive());
		$output[] = sprintf('Custom Taxonomy Archive: %b', is_tax());
		$output[] = sprintf('Category Archive: %b', is_category());
		$output[] = sprintf('Tag Archive: %b', is_tag());
		$output[] = sprintf('Author Archive: %b', is_author());
		$output[] = sprintf('Date Archive: %b', is_date());
		$output[] = sprintf('Year Archive: %b', is_year());
		$output[] = sprintf('Month Archive: %b', is_month());
		$output[] = sprintf('Day Archive: %b', is_day());
		$output[] = sprintf('Custom Post-Type Archive: %b', is_post_type_archive());
		$output[] = sprintf('Paged: %b', is_paged());
		$output[] = sprintf('Singular Page: %b', is_singular());
		$output[] = sprintf('Single Post Page: %b', is_single());
		$output[] = sprintf('Attachment Post: %b', is_attachment());
		$output[] = sprintf('Static Page: %b', is_page());
		$output[] = sprintf('Custom Static Page: %b', is_page_template());
		$output[] = sprintf('Site Front Page: %b', is_front_page());
		$output[] = sprintf('Posts Home Page: %b', is_home());
		$output[] = sprintf('Comments Popup Page: %b', is_comments_popup());

		$output[] = '######################## QUERY ############################';
		ob_start();
		var_dump(get_queried_object());
		$output[] = $comment ? ob_get_clean() : htmlentities(ob_get_clean());

		$output[] = '######################## $POST ########################';
		ob_start();
		var_dump($post);
		$output[] = $comment ? ob_get_clean() : htmlentities(ob_get_clean());
		
		$output[] = '######################## $OPTIONS ########################';
		ob_start();
		var_dump($options);
		$output[] = $comment ? ob_get_clean() : htmlentities(ob_get_clean());
		
		$output[] = $comment ? '-->' : '</pre>';
		
		echo implode(PHP_EOL, $output);
	}
	
	private static function get_wrapper(array $options, $open_tag = true) {
		$wrapper_element = HAG_Utils::sanitize_element($options['wrapper_element']);
		
		if (empty($wrapper_element)) return '';
		if (!$open_tag) return sprintf('</%s>', $wrapper_element);
		
		$wrapper_class   = HAG_Utils::sanitize_class($options['wrapper_class']);
		$wrapper_id      = HAG_Utils::sanitize_id($options['wrapper_id']);
	
		$wrapper = array();
		$wrapper[] = sprintf('<%s', $wrapper_element);
		
		if (!empty($wrapper_id)) 
			$wrapper[] = sprintf('id="%s"', $wrapper_id);
		
		if (!empty($wrapper_class)) 
			$wrapper[] = sprintf('class="%s"', $wrapper_class);
		
		if ($options['microdata']) 
			$wrapper[] = 'itemprop="breadcrumb"';
		
		$wrapper[] = '>';
		
		return implode(' ', $wrapper);
	}

	private static function get_prefix(array $options) {
		return empty($options['prefix'])
			? ''
			: $options['prefix'];
	}
	
	private static function get_suffix(array $options) {
		return empty($options['suffix'])
			? ''
			: $options['suffix'];
	}
		
	private static function get_crumb_wrapper(array $options, $open_tag, $is_home = false, $is_last = false) {
		$crumb_element = HAG_Utils::sanitize_element($options['crumb_element']);
				
		if (empty($crumb_element)) return '';
		if (!$open_tag) return sprintf('</%s>', $crumb_element);
		
		$crumb_class = HAG_Utils::get_crumb_class($options);
		$crumb_id = HAG_Utils::get_crumb_id($options);
		
		$wrapper = array();
		$wrapper[] = sprintf('<%s', $crumb_element);
		
		if (!empty($crumb_id))
			$wrapper[] = sprintf('id="%s"', $crumb_id);
		
		if (!empty($crumb_class)) 
			$wrapper[] = sprintf('class="%s"', $crumb_class);
		
		$wrapper[] = '>';
		
		return implode(' ', $wrapper);
	}
	
	private static function get_crumb_link(array $options, $url, $open_tag = true, $is_home = false, $is_last = false) {
		$crumb_link = HAG_Utils::get_crumb_link($options, $is_home, $is_last);
		$url = trim($url);
		
		if (!$crumb_link || empty($url)) return '';
		if (!$open_tag) return '</a>';
		
		$crumb_element = HAG_Utils::sanitize_element($options['crumb_element']);
		$crumb_class = HAG_Utils::get_crumb_class($options);
		$crumb_id = HAG_Utils::get_crumb_id($options);
		
		$link = array();
		$link[] = sprintf('<a href="%s"', $url);
		
		if (empty($crumb_element) && !empty($crumb_id))
			$link[] = sprintf('id="%s"', $crumb_id);
			
		if (empty($crumb_element) && !empty($crumb_class))
			$link[] = sprintf('class="%s"', $crumb_class);
		
		$link[] = '>';
		
		return implode(' ', $link);
	}

	private static function get_crumb(array $options, $label, $url = '', $is_home = false, $is_last = false) {
		$output = array();
		$output[] = self::get_crumb_wrapper($options, true, $is_home, $is_last);
		$output[] = self::get_crumb_link($options, $url, true, $is_home, $is_last);
		$output[] = trim($label);
		$output[] = self::get_crumb_link($options, $url, false);
		$output[] = self::get_crumb_wrapper($options, false);
		return implode('', $output);
	}
	
	private static function get_home_crumbs(array $options) {
		global $post;
		$crumbs = array();
		if (!$options['home_show']) return $crumbs;

		$fp = is_front_page();
		$bh = is_home();
		$cfp = HAG_Utils::has_front_page();
		$cbh = HAG_Utils::has_blog_home();
	
		//probably a contradiction
		if ($fp && $bh && $cbh) { 
			$blog = HAG_Utils::get_blog_home();
			return array(self::get_crumb(
				$options,
				$blog->post_title,
				get_permalink($blog->ID),
				true,
				true
			));
		}

		//use custom front page or fall back to the settings
		if ($cfp) { 
			$front = HAG_Utils::get_front_page();
			$crumbs[] = self::get_crumb(
				$options,
				$front->post_title,
				site_url(),
				true,
				$fp
			);
			
		} else {
			$crumbs[] = self::get_crumb(
				$options,
				$options['home_label'],
				site_url(),
				true,
				$fp || ($bh && !$cbh)
			);	
		}

		//break out if we aren't looking deeper
		if ($fp || !$cbh) return $crumbs;
		if (!$bh && !is_null($post) && 'post' !== $post->post_type) return $crumbs;

		//load in the custom blog page crumb
		$blog = HAG_Utils::get_blog_home();
		$crumbs[] = self::get_crumb(
			$options,
			$blog->post_title,
			get_permalink($blog->ID),
			false,
			$bh
		);
		
		return $crumbs;
	}
	
	private static function get_last_crumb(array $options) {
		global $post;
	}
	
	private static function get_crumbs($options) {

		if (is_404()) 
			return self::get_404_crumbs($options);
		
		if (is_front_page() || is_home())
			return self::get_home_crumbs($options);
			
		if (is_search())
			return self::get_search_crumbs($options);
			
		if (is_date())
			return self::get_date_archive_crumbs($options);
		
		if (is_category() || is_tag() || is_tax())
			return self::get_taxonomy_crumbs($options);	
			
		if (is_author())
			return self::get_author_crumbs($options);
			
		if (is_post_type_archive())
			return self::get_post_type_crumbs($options);
		
		if (is_comments_popup())
			return self::get_comment_popup_crumbs($options);
		
		if (is_singular())
			return self::get_singular_crumbs($options);
		
		return array();
	}
	
	private static function get_404_crumbs($options) {
	
		$crumbs = self::get_home_crumbs($options);
			
		if ($options['last_show'])	
			$crumbs[] = self::get_crumb(
				$options,
				$options['404_label'],
				'',
				false,
				true
			);
			
		return $crumbs;
	}
	
	private static function get_search_crumbs($options) {
		$crumbs = self::get_home_crumbs($options);
		if (!$options['last_show']) return $crumbs;

		$crumbs[] = self::get_crumb(
			$options,
			$options['search_label'],
			'',
			false,
			!$options['search_query']
		);
		if (!$options['search_query']) return $crumbs;
	
		$crumbs[] = self::get_crumb(
			$options,
			get_search_query(),
			'',
			false,
			true
		);

		return $crumbs;
	}
	
	private static function get_date_archive_crumbs($options) {
		$crumbs = self::get_home_crumbs($options);
		$date = new DateTime(get_the_date());
		$last_show = $options['last_show'];
		
		if (is_year() && !$last_show) return $crumbs;
		
		$crumbs[] = self::get_crumb(
			$options,
			$date->format('Y'),
			get_year_link($date->format('Y')),
			false,
			is_year()
		);
		
		if (is_year() || (is_month() && !$last_show)) return $crumbs;
		
		$crumbs[] = self::get_crumb(
			$options,
			$date->format('F'),
			get_month_link($date->format('Y'), $date->format('n')),
			false,
			is_month()
		);
		
		if (is_month() || (is_day() && !$last_show)) return $crumbs;
		
		$crumbs[] = self::get_crumb(
			$options,
			$date->format('jS'),
			get_day_link($date->format('Y'), $date->format('n'), $date->format('j')),
			false,
			is_day()
		);
		
		return $crumbs;
	}

	private static function get_author_crumbs($options) {
		$crumbs = self::get_home_crumbs($options);
		if (!$options['last_show']) return $crumbs;
		
		$author = get_user_by('id', get_query_var('author'));
		if (is_null($author) || is_wp_error($author)) return $crumbs;
		
		$crumbs[] = self::get_crumb(
			$options,
			$author->display_name,
			get_author_posts_url($author->ID),
			false,
			true
		);
		
		return $crumbs;
	}
	
	private static function get_post_type_crumbs($options) {
		$crumbs = self::get_home_crumbs($options);
		if (!$options['last_show']) return $crumbs;
			
		$pt = get_queried_object();
		if (is_null($pt) || is_wp_error($pt) || !$pt->has_archive) return $crumbs;
		
		$crumbs[] = self::get_crumb(
			$options,
			$pt->label,
			get_post_type_archive_link($pt->name),
			false,
			true
		);

		return $crumbs;
	}
	
	private static function get_taxonomy_crumbs($options) {
		
		$crumbs = self::get_home_crumbs($options);
		
		$term = get_queried_object();
		if (is_null($term) || is_wp_error($term)) return $crumbs;
		
		$tax = get_taxonomy($term->taxonomy);
		if (is_null($tax) || is_wp_error($tax)) return $crumbs;
		
		$pt = get_post_type_object($tax->object_type[0]);
		
		if (1 === count($tax->object_type) 
			&& !is_null($pt) 
			&& !is_wp_error($pt) 
			&& $pt->has_archive) {
			$crumbs[] = self::get_crumb(
				$options,
				$pt->label,
				get_post_type_archive_link($pt->name),
				false,
				false
			);
		}
		
		$rev_crumbs = array();
		
		if ($options['last_show']) {
			$rev_crumbs[] = self::get_crumb(
				$options,
				$term->name,
				get_term_link($term),
				false,
				true
			);
		}
		
		if ($tax->hierarchical && $options['taxonomy_ancestors_show']) {
			$term = get_term($term->parent, $term->taxonomy);
			while (!is_wp_error($term)) {
				$rev_crumbs[] = self::get_crumb(
					$options,
					$term->name,
					get_term_link($term),
					false,
					false
				);
				$term = get_term($term->parent, $term->taxonomy);
			}
		}
		
		return array_merge($crumbs, array_reverse($rev_crumbs));
	}
	
	private static function get_comment_popup_crumbs($options) {
		return self::get_singular_crumbs($options);
	}
	
	private static function get_singular_crumbs($options) {
		global $post;
		$crumbs = self::get_home_crumbs($options);
		
		$pt = get_post_type_object($post->post_type);
		if (!is_null($pt) && !is_wp_error($pt) && $pt->has_archive) {
			$crumbs[] = self::get_crumb(
				$options,
				$pt->label,
				get_post_type_archive_link($pt->name),
				false,
				false
			);
		}

		$rev_crumbs = array();
		if ($options['last_show']) {
			$rev_crumbs[] = self::get_crumb(
				$options,
				$post->post_title,
				get_permalink($post->ID),
				false,
				true
			);
		}
		
		if (!$options['taxonomy_ancestors_show']) return array_merge($crumbs, $rev_crumbs);
		
		if (is_post_type_hierarchical($pt->name)) {
			foreach($post->ancestors as $aID) {
				$ancestor = get_post($aID);
				$rev_crumbs[] = self::get_crumb(
					$options,
					$ancestor->post_title,
					get_permalink($ancestor->ID),
					false,
					false
				);
			}
		} else {
		
			$tax_names = get_object_taxonomies($post);
			$taxes = get_object_taxonomies($post, OBJECT);
			$term_args = array('orderby' => 'count', 'order' => 'DESC');			
			$term = null;

			//get preferred taxonomy term if it exists
			if (in_array($options['taxonomy_preferred'], $tax_names)) {
				$tax_name = $options['taxonomy_preferred'];
				$tax = array_key_exists($tax_name, $taxes) ? $taxes[$tax_name] : null;
				if (!is_null($tax)) $terms = wp_get_object_terms($post->ID, $tax_name, $term_args);
				if (count($terms) > 0) $term = $terms[0];
			}
			
			//get hierarchical taxonomy term if it exists
			if (is_null($term)) {
				$hier_taxes = array();
				foreach($taxes as $t) if ($t->hierarchical) $hier_taxes[] = $t->name;
				$hier_terms = wp_get_object_terms($post->ID, $hier_taxes, $term_args);
				if (!is_wp_error($hier_terms) && count($hier_terms) > 0) $term = $hier_terms[0];
			}
			
			if (is_null($term)) {
				$unhier_taxes = array();
				foreach($taxes as $t) if (!$t->hierarchical) $unhier_taxes[] = $t->name;
				$unhier_terms = wp_get_object_terms($post->ID, $unhier_taxes, $term_args);
				if (!is_wp_error($unhier_terms) && count($unhier_terms) > 0) $term = $unhier_terms[0];
			}
			
			if (!is_null($term)) {
				do {
					$rev_crumbs[] = self::get_crumb(
						$options,
						$term->name,
						get_term_link($term),
						false,
						false
					);
					$term = get_term($term->parent, $term->taxonomy);
				} while (!is_wp_error($term));
			}
			
		}
		
		return array_merge($crumbs, array_reverse($rev_crumbs));
	}
	
	public static function display(array $options = null) {
		global $post;
		$post_type = is_null($post)
			? ''
			: $post->post_type;
		
		/***************************** LOAD AND RESOLVE OPTIONS FOR THE BREADCRUMBS */
		if (!is_array($options)) $options = array();
		$options = HAG_Options::get_options($options, $post_type);
		
		
		/*************************************** PRINT DEBUG INFORMATION IF DESIRED */
		if ($options['debug_show']) self::debug_info($options, $options['debug_comment']);
		
		
		/************************************* OBTAIN CRUMBS AND EXIT IF NONE FOUND */
		$crumbs = self::get_crumbs($options);
		if (0 === count($crumbs)) return;
		
		/********************************************* BUILD OUTPUT BASED ON OPTIONS*/
		$output = array();
		$output[] = self::get_wrapper($options);
		$output[] = self::get_prefix($options);
		$output[] = implode(
			sprintf(' %s ', $options['separator']), 
			$crumbs
		);
		$output[] = self::get_suffix($options);
		$output[] = self::get_wrapper($options, false);
		
		echo implode('', $output);
	}
	
}

register_activation_hook(__FILE__, array('HAG_Breadcrumbs', 'activate'));
register_uninstall_hook(__FILE__, array('HAG_Breadcrumbs', 'uninstall'));

HAG_Breadcrumbs::initialize();

function HAG_Breadcrumbs(array $options = null) {
	HAG_Breadcrumbs::display($options);
}
