<?php
/**
 * Job Filter
 *
 * @package    wp-job-board-pro
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Pro_Job_Filter extends WP_Job_Board_Pro_Abstract_Filter {
	
	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'archive' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'taxonomy' ) );
	}

	public static function get_fields() {
		return apply_filters( 'wp-job-board-pro-default-job_listing-filter-fields', array(
			'title'	=> array(
				'label' => __( 'Search Keywords', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_input'),
				'placeholder' => __( 'e.g. web design', 'wp-job-board-pro' ),
				'for_post_type' => 'job_listing',
			),
			'category' => array(
				'label' => __( 'Category', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
				'taxonomy' => 'job_listing_category',
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'center-location' => array(
				'label' => __( 'Location', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_input_location'),
				'placeholder' => __( 'All Location', 'wp-job-board-pro' ),
				'show_distance' => true,
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'location' => array(
				'label' => __( 'Location list', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_taxonomy_hierarchical_check_list'),
				'taxonomy' => 'job_listing_location',
				'placeholder' => __( 'All Locations', 'wp-job-board-pro' ),
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'type' => array(
				'label' => __( 'Job Type', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_taxonomy_hierarchical_radio_list'),
				'taxonomy' => 'job_listing_type',
				'placeholder' => __( 'All Types', 'wp-job-board-pro' ),
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'salary' => array(
				'label' => __( 'Salary', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_job_salary'),
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'date-posted' => array(
				'label' => __( 'Date Posted', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_input_date_posted'),
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'tag' => array(
				'label' => __( 'Job Tag', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_taxonomy_check_list'),
				'taxonomy' => 'job_listing_tag',
				'toggle' => true,
				'for_post_type' => 'job_listing',
			),
			'featured' => array(
				'label' => __( 'Featured', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_checkbox'),
				'for_post_type' => 'job_listing',
			),
			'urgent' => array(
				'label' => __( 'Urgent', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_checkbox'),
				'for_post_type' => 'job_listing',
			),
			'author' => array(
				'label' => __( 'Employer', 'wp-job-board-pro' ),
				'field_call_back' => array( 'WP_Job_Board_Pro_Abstract_Filter', 'filter_field_employers'),
				'for_post_type' => 'job_listing',
			),
		));
	}
	
	public static function archive($query) {
		$suppress_filters = ! empty( $query->query_vars['suppress_filters'] ) ? $query->query_vars['suppress_filters'] : '';

		if ( ! is_post_type_archive( 'job_listing' ) || ! $query->is_main_query() || is_admin() || $query->query_vars['post_type'] != 'job_listing' || $suppress_filters ) {
			return;
		}

		$limit = wp_job_board_pro_get_option('number_jobs_per_page', 10);
		$query_vars = &$query->query_vars;
		$query_vars['posts_per_page'] = $limit;
		$query->query_vars = $query_vars;
		
		return self::filter_query( $query );
	}

	public static function taxonomy($query) {
		$is_correct_taxonomy = false;
		if ( is_tax( 'job_listing_type' ) || is_tax( 'job_listing_tag' ) || is_tax( 'job_listing_location' ) || is_tax( 'job_listing_category' ) || apply_filters( 'wp-job-board-pro-job-query-taxonomy', false ) ) {
			$is_correct_taxonomy = true;
		}

		if ( ! $is_correct_taxonomy  || ! $query->is_main_query() || is_admin() ) {
			return;
		}

		$limit = wp_job_board_pro_get_option('number_jobs_per_page', 10);
		$query_vars = $query->query_vars;
		$query_vars['posts_per_page'] = $limit;
		$query->query_vars = $query_vars;

		return self::filter_query( $query );
	}


	public static function filter_query( $query = null, $params = array() ) {
		global $wpdb, $wp_query;

		if ( empty( $query ) ) {
			$query = $wp_query;
		}

		if ( empty( $params ) ) {
			$params = $_GET;
		}
		
		// Filter params
		$params = apply_filters( 'wp_job_board_pro_job_filter_params', $params );

		// Initialize variables
		$query_vars = $query->query_vars;
		$query_vars = self::get_query_var_filter($query_vars, $params);
		$query->query_vars = $query_vars;

		// Meta query
		$meta_query = self::get_meta_filter($params);
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}

		// Tax query
		$tax_query = self::get_tax_filter($params);
		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
		
		return apply_filters('wp-job-board-pro-job_listing-filter-query', $query, $params);
	}

	public static function get_query_var_filter($query_vars, $params) {
		$ids = null;
		$query_vars = self::orderby($query_vars, $params);

		// Property title
		if ( ! empty( $params['filter-title'] ) ) {
			global $wp_job_board_pro_job_keyword;
			$wp_job_board_pro_job_keyword = sanitize_text_field( wp_unslash($params['filter-title']) );
			$query_vars['s'] = sanitize_text_field( wp_unslash($params['filter-title']) );
			add_filter( 'posts_search', array( __CLASS__, 'get_jobs_keyword_search' ) );
		}

		$distance_ids = self::filter_by_distance($params, 'job_listing');
		if ( !empty($distance_ids) ) {
			$ids = self::build_post_ids( $ids, $distance_ids );
		}
    	
    	
		if ( ! empty( $params['filter-author'] ) ) {
			if ( is_array($params['filter-author']) ) {
				$query_vars['author__in'] = array_map( 'sanitize_title', wp_unslash( $params['filter-author'] ) );
			} else {
				$query_vars['author'] = sanitize_text_field( wp_unslash($params['filter-author']) );
			}
		}

		// Post IDs
		if ( is_array( $ids ) && count( $ids ) > 0 ) {
			$query_vars['post__in'] = $ids;
		}

		//date posted
		$date_query = self::filter_by_date_posted($params);
		if ( !empty($date_query) ) {
			$query_vars['date_query'] = $date_query;
		}

		return $query_vars;
	}

	public static function get_meta_filter($params) {
		$meta_query = array();
		// salary
    	if ( ! empty( $params['filter-salary_type'] ) ) {
			$meta_query[] = array(
				'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'salary_type',
				'value'     => sanitize_text_field( wp_unslash($params['filter-salary_type']) ),
				'compare'   => '==',
			);
		}
		
    	if ( isset( $params['filter-salary-from'] ) && isset( $params['filter-salary-to'] ) ) {
    		$price_meta_query = array( 'relation' => 'OR' );
			if ( isset($params['filter-salary-from']) && intval($params['filter-salary-from']) >= 0 && isset($params['filter-salary-to']) && intval($params['filter-salary-to']) > 0) {

				$salary_from = WP_Job_Board_Pro_Price::convert_current_currency_to_default($params['filter-salary-from']);
				$salary_to = WP_Job_Board_Pro_Price::convert_current_currency_to_default($params['filter-salary-to']);

				$price_meta_query[] = array(
		           	'key' => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'salary',
		           	'value' => array( sanitize_text_field($salary_from), sanitize_text_field($salary_to) ),
		           	'compare'   => 'BETWEEN',
					'type'      => 'NUMERIC',
		       	);

		       	$price_meta_query[] = array(
		           	'key' => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'max_salary',
		           	'value' => array( sanitize_text_field($salary_from), sanitize_text_field($salary_to) ),
		           	'compare'   => 'BETWEEN',
					'type'      => 'NUMERIC',
		       	);
			}
			$meta_query[] = $price_meta_query;
    	}

    	// meta flexible
    	$meta_obj = WP_Job_Board_Pro_Job_Listing_Meta::get_instance(0);
		$meta_keys = ['experience', 'gender', 'industry', 'qualification', 'career_level'];
		foreach ( $meta_keys as $meta_key ) {
			if ( ! empty( $params['filter-'.$meta_key] ) ) {
				$field = $meta_obj->get_meta_field($meta_key);
				
				$value = $params['filter-'.$meta_key];
				if ( is_array($value) ) {
					$multi_meta = array( 'relation' => 'OR' );
					foreach ($value as $val) {
						if ( !empty($field['type']) && in_array($field['type'], array('pw_multiselect', 'multicheck')) ) {
							$f_val = '"'.$val.'"';
							$compare = 'LIKE';
						} else {
							$f_val = $val;
							$compare = '=';
						}
						$multi_meta[] = array(
							'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . $meta_key,
							'value'     => $f_val,
							'compare'   => $compare,
						);
					}
					$meta_query[] = $multi_meta;
				} else {
					if ( !empty($field['type']) && in_array($field['type'], array('pw_multiselect', 'multicheck')) ) {
						$f_val = '"'.$value.'"';
						$compare = 'LIKE';
					} else {
						$f_val = $value;
						$compare = '=';
					}

					$meta_query[] = array(
						'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . $meta_key,
						'value'     => $f_val,
						'compare'   => $compare,
					);
				}
			}
		}
		
		if ( ! empty( $params['filter-featured'] ) ) {
			$meta_query[] = array(
				'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'featured',
				'value'     => 'on',
				'compare'   => '==',
			);
		}

		if ( ! empty( $params['filter-urgent'] ) ) {
			$meta_query[] = array(
				'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'urgent',
				'value'     => 'on',
				'compare'   => '==',
			);
		}

		// custom fields
		$filter_fields = self::get_fields();
		$meta_query = self::filter_custom_field_meta($meta_query, $params, $filter_fields);

		return $meta_query;
	}

	public static function get_tax_filter($params) {
		$tax_query = array();
		if ( ! empty( $params['filter-category'] ) ) {
			if ( is_array($params['filter-category']) ) {
				$field = is_numeric( $params['filter-category'][0] ) ? 'term_id' : 'slug';
				$values = array_filter( array_map( 'sanitize_title', wp_unslash( $params['filter-category'] ) ) );

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_category',
					'field'     => $field,
					'terms'     => array_values($values),
					'compare'   => 'IN',
				);
			} else {
				$field = is_numeric( $params['filter-category'] ) ? 'term_id' : 'slug';

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_category',
					'field'     => $field,
					'terms'     => sanitize_text_field( wp_unslash($params['filter-category']) ),
					'compare'   => '==',
				);
			}
		}

		if ( ! empty( $params['filter-type'] ) ) {
			if ( is_array($params['filter-type']) ) {
				$field = is_numeric( $params['filter-type'][0] ) ? 'term_id' : 'slug';
				$values = array_filter( array_map( 'sanitize_title', wp_unslash( $params['filter-type'] ) ) );

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_type',
					'field'     => $field,
					'terms'     => array_values($values),
					'compare'   => 'IN',
				);
			} else {
				$field = is_numeric( $params['filter-type'] ) ? 'term_id' : 'slug';

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_type',
					'field'     => $field,
					'terms'     => sanitize_text_field( wp_unslash($params['filter-type']) ),
					'compare'   => '==',
				);
			}
		}

		if ( ! empty( $params['filter-location'] ) ) {
			if ( is_array($params['filter-location']) ) {
				$field = is_numeric( $params['filter-location'][0] ) ? 'term_id' : 'slug';
				$values = array_filter( array_map( 'sanitize_title', wp_unslash( $params['filter-location'] ) ) );

				$location_type = wp_job_board_pro_get_option('location_multiple_fields', 'yes');
			    if ( $location_type === 'no' ) {
					$tax_query[] = array(
						'taxonomy'  => 'job_listing_location',
						'field'     => $field,
						'terms'     => array_values($values),
						'compare'   => 'IN',
					);
				} else {
					$location_tax_query = array('relation' => 'AND');
					foreach ($values as $key => $value) {
						$location_tax_query[] = array(
							'taxonomy'  => 'job_listing_location',
							'field'     => $field,
							'terms'     => $value,
							'compare'   => '==',
						);
					}
					$tax_query[] = $location_tax_query;
				}
			} else {
				$field = is_numeric( $params['filter-location'] ) ? 'term_id' : 'slug';

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_location',
					'field'     => $field,
					'terms'     => sanitize_text_field( wp_unslash($params['filter-location']) ),
					'compare'   => '==',
				);
			}
		}

		if ( ! empty( $params['filter-tag'] ) ) {
			if ( is_array($params['filter-tag']) ) {
				$field = is_numeric( $params['filter-tag'] ) ? 'term_id' : 'slug';
				$values = array_filter( array_map( 'sanitize_title', wp_unslash( $params['filter-tag'] ) ) );

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_tag',
					'field'     => $field,
					'terms'     => array_values($values),
					'compare'   => 'IN',
				);
			} else {
				$field = is_numeric( $params['filter-tag'] ) ? 'term_id' : 'slug';

				$tax_query[] = array(
					'taxonomy'  => 'job_listing_tag',
					'field'     => $field,
					'terms'     => sanitize_text_field( wp_unslash($params['filter-tag']) ),
					'compare'   => '==',
				);
			}
		}

		return $tax_query;
	}

	public static function get_jobs_keyword_search( $search ) {
		global $wpdb, $wp_job_board_pro_job_keyword;

		// Searchable Meta Keys: set to empty to search all meta keys.
		$searchable_meta_keys = array(
			WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX.'address',
		);

		$searchable_meta_keys = apply_filters( 'wp_job_board_pro_searchable_meta_keys', $searchable_meta_keys );

		// Set Search DB Conditions.
		$conditions = array();

		// Search Post Meta.
		if ( apply_filters( 'wp_job_board_pro_search_post_meta', true ) ) {

			// Only selected meta keys.
			if ( $searchable_meta_keys ) {
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( '" . implode( "','", array_map( 'esc_sql', $searchable_meta_keys ) ) . "' ) AND meta_value LIKE '%" . esc_sql( $wp_job_board_pro_job_keyword ) . "%' )";
			} else {
				// No meta keys defined, search all post meta value.
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $wp_job_board_pro_job_keyword ) . "%' )";
			}
		}

		// Search taxonomy.
		$conditions[] = "{$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE t.name LIKE '%" . esc_sql( $wp_job_board_pro_job_keyword ) . "%' )";
		
		$conditions = apply_filters( 'wp_job_board_pro_search_conditions', $conditions, $wp_job_board_pro_job_keyword );
		if ( empty( $conditions ) ) {
			return $search;
		}

		$conditions_str = implode( ' OR ', $conditions );

		if ( ! empty( $search ) ) {
			$search = preg_replace( '/^ AND /', '', $search );
			$search = " AND ( {$search} OR ( {$conditions_str} ) )";
		} else {
			$search = " AND ( {$conditions_str} )";
		}
		remove_filter( 'posts_search', array( __CLASS__, 'get_jobs_keyword_search' ) );
		return $search;
	}

	public static function display_filter_value($key, $value, $filters) {
		$meta_obj = WP_Job_Board_Pro_Job_Listing_Meta::get_instance(0);
		$url = urldecode(WP_Job_Board_Pro_Mixes::get_full_current_url());
		
		if ( is_array($value) ) {
			$value = array_filter( array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
		} else {
			$value = sanitize_text_field( wp_unslash($value) );
		}
		
		switch ($key) {
			case 'filter-category':
				self::render_filter_tax($key, $value, 'job_listing_category', $url);
				break;
			case 'filter-location':
				self::render_filter_tax($key, $value, 'job_listing_location', $url);
				break;
			case 'filter-type':
				self::render_filter_tax($key, $value, 'job_listing_type', $url);
				break;
			case 'filter-tag':
				self::render_filter_tax($key, $value, 'job_listing_tag', $url);
				break;
			case 'filter-salary-from':
				$salary = WP_Job_Board_Pro_Price::format_price($value, true, true);
				$rm_url = self::remove_url_var($key . '=' . $value, $url);
				self::render_filter_result_item( $salary, $rm_url );
				break;
			case 'filter-salary-to':
				$salary = WP_Job_Board_Pro_Price::format_price($value, true, true);
				$rm_url = self::remove_url_var($key . '=' . $value, $url);
				self::render_filter_result_item( $salary, $rm_url );
				break;
			case 'filter-salary_type':
				$types = WP_Job_Board_Pro_Mixes::get_default_salary_types();
				$title = $value;
				if ( in_array($value, $types) ) {
					$title = $types[$value];
				}
				$rm_url = self::remove_url_var( $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-date-posted':
				$options = self::date_posted_options();
				foreach ($options as $option) {
					if ( !empty($option['value']) && $option['value'] == $value ) {
						$title = $option['text'];
						$rm_url = self::remove_url_var( $key . '=' . $value, $url);
						self::render_filter_result_item( $title, $rm_url );
						break;
					}
				}
				break;
			case 'filter-distance':
				if ( !empty($filters['filter-center-location']) ) {
					$distance_type = apply_filters( 'wp_job_board_pro_filter_distance_type', 'miles' );
					$title = $value.' '.$distance_type;
					$rm_url = self::remove_url_var( $key . '=' . $value, $url);
					self::render_filter_result_item( $title, $rm_url );
				}
				break;
			case 'filter-featured':
				$title = esc_html__('Featured', 'wp-job-board-pro');
				$rm_url = self::remove_url_var($key . $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-urgent':
				$title = esc_html__('Urgent', 'wp-job-board-pro');
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-author':
				if ( !empty($value) ) {
					if ( is_array($value) ) {
						foreach ($value as $val) {
							$employer_id = WP_Job_Board_Pro_User::get_employer_by_user_id($val);
							if ( $employer_id ) {
								$title = get_the_title($employer_id);
							} else {
								$user_info = get_userdata($val);
								if ( is_object($user_info) ) {
									$title = $user_info->display_name;
								} else {
									$title = $val;
								}
							}
							$rm_url = self::remove_url_var(  $key . '=' . $val, $url);
							self::render_filter_result_item( $title, $rm_url );
						}
					} else {
						$employer_id = WP_Job_Board_Pro_User::get_employer_by_user_id($value);
						if ( $employer_id ) {
							$title = get_the_title($employer_id);
						} else {
							$user_info = get_userdata($value);
							if ( is_object($user_info) ) {
								$title = $user_info->display_name;
							} else {
								$title = $value;
							}
						}
						$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
						self::render_filter_result_item( $title, $rm_url );
					}
				}
				
				break;
			case 'filter-orderby':
				$orderby_options = apply_filters( 'wp-job-board-pro-jobs-orderby', array(
					'menu_order' => esc_html__('Default', 'wp-job-board-pro'),
					'newest' => esc_html__('Newest', 'wp-job-board-pro'),
					'oldest' => esc_html__('Oldest', 'wp-job-board-pro'),
					'random' => esc_html__('Random', 'wp-job-board-pro'),
				));
				$title = $value;
				if ( !empty($orderby_options[$value]) ) {
					$title = $orderby_options[$value];
				}
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-experience':
			case 'filter-gender':
			case 'filter-industry':
			case 'filter-qualification':
			case 'filter-career_level':
				$label_key = str_replace('filter-', '', $key);
				$field = $meta_obj->get_meta_field($label_key);
				$options = !empty($field['options']) ? $field['options'] : array();
				// echo "<pre>".print_r($value,1)."</pre>";
				if ( is_array($value) ) {
					foreach ($value as $val) {
						$rm_url = self::remove_url_var( $key . '[]=' . $val, $url);
						$val_title = $val;
						if ( !empty($options[$val]) ) {
							$val_title = $options[$val];
						}
						self::render_filter_result_item( $val_title, $rm_url);
					}
				} else {
					$rm_url = self::remove_url_var( $key . '=' . $value, $url);
					$val_title = $value;
					if ( !empty($options[$value]) ) {
						$val_title = $options[$value];
					}
					self::render_filter_result_item( $val_title, $rm_url);
				}
				break;
			default:
				if ( is_array($value) ) {
					foreach ($value as $val) {
						$rm_url = self::remove_url_var( $key . '[]=' . $val, $url);
						self::render_filter_result_item( $val, $rm_url);
					}
				} else {
					$rm_url = self::remove_url_var( $key . '=' . $value, $url);
					self::render_filter_result_item( $value, $rm_url);
				}
				
				break;
		}
	}


	public static function display_filter_value_simple($key, $value, $filters) {
		$meta_obj = WP_Job_Board_Pro_Job_Listing_Meta::get_instance(0);
		if ( is_array($value) ) {
			$value = array_filter( array_map( 'sanitize_text_field', wp_unslash( $value ) ) );
		} else {
			$value = sanitize_text_field( wp_unslash($value) );
		}
		switch ($key) {
			case 'filter-category':
				self::render_filter_tax_simple($key, $value, 'job_listing_category', esc_html__('Category', 'wp-job-board-pro'));
				break;
			case 'filter-location':
				self::render_filter_tax_simple($key, $value, 'job_listing_location', esc_html__('Location', 'wp-job-board-pro'));
				break;
			case 'filter-type':
				self::render_filter_tax_simple($key, $value, 'job_listing_type', esc_html__('Type', 'wp-job-board-pro'));
				break;
			case 'filter-tag':
				self::render_filter_tax_simple($key, $value, 'job_listing_tag', esc_html__('Tag', 'wp-job-board-pro'));
				break;
			case 'filter-salary':
				if ( isset($value[0]) && isset($value[1]) ) {
					$from = WP_Job_Board_Pro_Price::format_price($value[0], true, true);
					$to = WP_Job_Board_Pro_Price::format_price($value[1], true, true);
					
					self::render_filter_result_item_simple( $from.' - '.$to, esc_html__('Salary', 'wp-job-board-pro') );
				}
				break;
			case 'filter-salary-from':
				$salary = WP_Job_Board_Pro_Price::format_price($value, true, true);
					
				self::render_filter_result_item_simple( $salary, esc_html__('Salary Min', 'wp-job-board-pro') );
				break;
			case 'filter-salary-to':
				$salary = WP_Job_Board_Pro_Price::format_price($value, true, true);
				
				self::render_filter_result_item_simple( $salary, esc_html__('Salary Max', 'wp-job-board-pro') );
				
				break;
			case 'filter-salary_type':
				$types = WP_Job_Board_Pro_Mixes::get_default_salary_types();
				$title = $value;
				if ( in_array($value, $types) ) {
					$title = $types[$value];
				}
				self::render_filter_result_item_simple( $title, esc_html__('Salary Type', 'wp-job-board-pro') );
				break;
			case 'filter-date-posted':
				$options = self::date_posted_options();
				foreach ($options as $option) {
					if ( !empty($option['value']) && $option['value'] == $value ) {
						$title = $option['text'];
						self::render_filter_result_item_simple( $title, esc_html__('Posted Date', 'wp-job-board-pro') );
						break;
					}
				}
				break;
			case 'filter-center-location':
				
				self::render_filter_result_item_simple( $value, esc_html__('Location', 'wp-job-board-pro') );
			
				break;
			case 'filter-distance':
				if ( !empty($filters['filter-center-location']) ) {
					$distance_type = apply_filters( 'wp_job_board_pro_filter_distance_type', 'miles' );
					$title = $value.' '.$distance_type;
					self::render_filter_result_item_simple( $title, esc_html__('Distance', 'wp-job-board-pro') );
				}
				break;
			case 'filter-featured':
				$title = esc_html__('Yes', 'wp-job-board-pro');
				self::render_filter_result_item_simple( $title, esc_html__('Featured', 'wp-job-board-pro') );
				break;
			case 'filter-urgent':
				$title = esc_html__('Yes', 'wp-job-board-pro');
				self::render_filter_result_item_simple( $title, esc_html__('Urgent', 'wp-job-board-pro') );
				break;
			case 'filter-author':
				if ( !empty($value) ) {
					if ( is_array($value) ) {
						foreach ($value as $val) {
							$employer_id = WP_Job_Board_Pro_User::get_employer_by_user_id($val);
							if ( $employer_id ) {
								$title = get_the_title($employer_id);
							} else {
								$user_info = get_userdata($val);
								if ( is_object($user_info) ) {
									$title = $user_info->display_name;
								} else {
									$title = $val;
								}
							}
							self::render_filter_result_item_simple( $title, esc_html__('Author', 'wp-job-board-pro') );
						}
					} else {
						$employer_id = WP_Job_Board_Pro_User::get_employer_by_user_id($value);
						if ( $employer_id ) {
							$title = get_the_title($employer_id);
						} else {
							$user_info = get_userdata($value);
							if ( is_object($user_info) ) {
								$title = $user_info->display_name;
							} else {
								$title = $value;
							}
						}
						self::render_filter_result_item_simple( $title, esc_html__('Author', 'wp-job-board-pro') );
					}
				}
				break;
			case 'filter-orderby':
				$orderby_options = apply_filters( 'wp-job-board-pro-jobs-orderby', array(
					'menu_order' => esc_html__('Default', 'wp-job-board-pro'),
					'newest' => esc_html__('Newest', 'wp-job-board-pro'),
					'oldest' => esc_html__('Oldest', 'wp-job-board-pro'),
					'random' => esc_html__('Random', 'wp-job-board-pro'),
				));
				$title = $value;
				if ( !empty($orderby_options[$value]) ) {
					$title = $orderby_options[$value];
				}
				self::render_filter_result_item_simple( $title, esc_html__('Orderby', 'wp-job-board-pro') );
				break;
			case 'filter-experience':
			case 'filter-gender':
			case 'filter-industry':
			case 'filter-qualification':
			case 'filter-career_level':
				$label_key = str_replace('filter-', '', $key);
				$field = $meta_obj->get_meta_field($label_key);
				$options = !empty($field['options']) ? $field['options'] : array();
				if ( !empty($options[$value]) ) {
					$value = $options[$value];
				}

				$label = $meta_obj->get_post_meta_title($label_key);
				if ( is_array($value) ) {
					foreach ($value as $val) {
						self::render_filter_result_item_simple( $val, $label);
					}
				} else {
					self::render_filter_result_item_simple( $value, $label);
				}
				break;
			default:
				
				$label_key = str_replace('filter-', '', $key);
				$label_key = str_replace('-', ' ', $label_key);

				$prefix = '';
				if (preg_match("/-to/i", $key)) {
					$prefix = esc_html__('to', 'wp-job-board-pro');

					$label_key = str_replace('-to', '', $label_key);
				} elseif (preg_match("/-from/i", $key)) {
					$prefix = esc_html__('from', 'wp-job-board-pro');

					$label_key = str_replace('-from', '', $label_key);
				}

				$label = $meta_obj->get_post_meta_title($label_key);
				if ( empty($label) ) {
					$label = $label_key;
				}
				if ( $prefix ) {
					$label .= ' '.$prefix;
				}
				
				if ( is_array($value) ) {
					foreach ($value as $val) {
						self::render_filter_result_item_simple( $val, $label);
					}
				} else {
					self::render_filter_result_item_simple( $value, $label);
				}
				
				break;
		}
	}
}

WP_Job_Board_Pro_Job_Filter::init();