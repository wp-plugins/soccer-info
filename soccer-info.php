<?php
/*
Plugin Name: Soccer Info
Plugin URI: http://www.mihalysoft.com/wordpress-plugins/soccer-info/
Description: Soccer Info lets you display ranking tables, fixtures and results of major soccer leagues without any hassles.
Version: 1.3
Requires at least: WordPress 3.2.1
Tested up to: WordPress 3.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author: Szilard Mihaly
Author URI: http://www.mihalysoft.com/
*/


/**
* Loading class for the WordPress plugin Soccer Info
* 
* @author 	Szilard Mihaly
* @package	Soccer Info
* @copyright 	Copyright 2012
*/
if ( !class_exists('SoccerInfo') ) {
	
	class SoccerInfo {
			
		public static $wpsiopt_default = array(
			'si_timezone'			 => '0',
			'si_date_format'		 => 'l, F j, Y',
			'si_time_format'		 => 'H:i',
			'si_date_format_custom'	 => 'l, F j, Y'
		);
		
		public $wpsiopt = array();
		
        public static $pages  = array(
            'soccer-info'
        );

		/**
		 * Constructor
		 *
		 * @param  none
		 * @return void
		 */
		public function __construct() {
			
			define('SOCCER_INFO_VERSION', '1.0');
			define('SOCCER_INFO_PATH', plugin_dir_path(__FILE__));
			define('SOCCER_INFO_BASEPATH', basename(dirname(__FILE__)));
			
			define('SOCCER_INFO', 'soccer-info');  // Text domain & plugin dir
			load_plugin_textdomain(SOCCER_INFO, false, SOCCER_INFO_BASEPATH.'/lang');
			
			$this->wpsiopt = $this->wpsiopt_default;
			
			$this->LoadOptions();
			
			// Widgets
			require_once SOCCER_INFO_PATH.'/soccer-info-widgets.class.php';
			// Add all widgets in the WP process
			add_action('widgets_init', array(&$this, 'soccer_info_register_widgets'));
			
			//backend
			if ( is_admin() ) {
				
                // Specific WP actions coming soon...
                //register_activation_hook(__FILE__, array('SoccerInfo', 'activate'));
                //register_uninstall_hook(__FILE__, array('SoccerInfo', 'uninstall'));
                
                // We need to be administrator to manage SoccerInfo backend
                //SoccerInfo::$access = 'administrator';
                
                // Load the backend controller system
                require_once SOCCER_INFO_PATH.'/soccer-info-admin.class.php';
                
                add_action('init', array('SoccerInfo_Admin', 'add_editor_button'));
                add_action('admin_init', array(&$this, 'plugin_admin_init'));
                //add_action('admin_init', array(&$this, 'plugin_check_upgrade'));
                add_action('admin_menu', array('SoccerInfo_Admin', 'admin_menu'));
                add_action('admin_print_styles', array('SoccerInfo_Admin', 'print_admin_styles'));
                add_action('admin_print_scripts', array('SoccerInfo_Admin', 'print_admin_scripts'));
                //add_action('wp_dashboard_setup', array('SoccerInfo_Admin', 'register_admin_widgets'));
                add_action('admin_print_scripts-widgets.php', array('SoccerInfo_Admin', 'print_admin_scripts_widgets'));
                
                // AJAX library
                //require_once SOCCER_INFO_PATH.'/libs/soccer-info-ajax.php';
                
                // Ajax request to delete a team in player history
               // add_action('wp_ajax_delete_player_history_team', array('SoccerInfo_AJAX', 'delete_player_history_team'));
			}
			else { //front-end
				add_shortcode('soccer-info', array(&$this, 'shortcodes_controller'));
				
				add_action('wp_print_styles', array(&$this, 'print_front_styles'));
			}

			/** Hook for add-points user query */
			add_action( 'wp_ajax_get_soccer_info_teams', array(&$this, 'get_soccer_info_teams') );
			
		}
				
		// Register each widget
		function soccer_info_register_widgets() {
			register_widget('SoccerInfo_Widgets');
		}
        
        /**
         * Admin initializer
         *
         * @param  none
         * @return void
         */
        public function plugin_admin_init() {
			
			add_filter("plugin_action_links_".plugin_basename(__FILE__), array(&$this, 'si_settings_link') );
			
        }
		
		// Add settings link on plugin page
		function si_settings_link($links) { 
			$settings_link = '<a href="options-general.php?page='.SOCCER_INFO.'">Settings</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
        
        /**
         * Plugin upgrade handler
         *
         * @param  none
         * @return void
         */
        public function plugin_check_upgrade() {
            
        }
		
		public function shortcodes_controller($attributes) {
			// Extract data
			extract(shortcode_atts(
				array(
					'id'			=> 1,
					'type'			=> 'table',
					'style'			=> 'general',
					'columns'		=> '',
					'highlight'		=> '',
					'team'			=> '',
					'width'			=> '',
					'limit'			=> 0,
					'title'			=> '',
					'widget'		=> 0
                ),
				$attributes
			));
			
			if ($style == 'general')
				$style = '';
			
			if ( !in_array( $type, array('table', 'fixtures', 'results') ) )
				return '';
			
			if ( !is_int( $id ) ) {
				$league_id = $this->get_league_number_by_id( $id );
			}
			else {
				$league_id = $this->get_league_number_by_name( $id );
			}
			
			if ( $league_id == 0 )
				return '';
			
			$team_id = 0;
			if ($type != 'table') {
				if (!empty($team)) {
					$h = explode('||', $team);
					if (count($h) > 1 && (int)$h[0] > 0) {
						$team_id = (int)$h[0];
						$team = $h[1];
					}
					else {
						$team = '';
					}
				}
			}
			else {
				if (!empty($team)) {
					$h = explode('||', $team);
					if (count($h) > 1)
						$team = $h[1]; 
					else
						$team = '';
				}
			}
			
			if (!empty($highlight)) {
				$h = explode('||', $highlight);
				if (count($h) > 1)
					$highlight = $h[1];
				else
					$highlight = '';
			}
			
			if ( $team_id > 0 ) {
				$feed_url = 'http://widgets.soccerway.com/widget/free/classic/team/'.$team_id;
			}
			else {
				$feed_url1 = 'http://widgets.soccerway.com/widget/free/classic/competition/';
				
				$feed_url = $feed_url1.$league_id.'/#d=350x800&f=table,table_colmp,table_colmw,table_colmd,table_colml,table_colgf,table_colga,results,fixtures&cbackground=FFFFFF&ctext=000000&ctitle=F85F00&cshadow=E8E8E8&cbutton=C0C0C0&cbuttontext=000000&chighlight=FF0000&tbody_family=Tahoma,sans-serif&tbody_size=9&tbody_weight=normal&tbody_style=normal&tbody_decoration=none&tbody_transform=none&ttitle_family=Impact,sans-serif&ttitle_size=13&ttitle_weight=normal&ttitle_style=normal&ttitle_decoration=none&ttitle_transform=none&ttab_family=Tahoma,sans-serif&ttab_size=9&ttab_weight=normal&ttab_style=normal&ttab_decoration=none&ttab_transform=none';
			}
				
				$response = $this->wpsi_remote_get( $feed_url ); //, $cache_args, $http_args);
				
				if ( !is_wp_error( $response ) ) {
					
					$what = $type; //'table';
					$selector = 'div#tabset div#'.$what;
					
					$raw_html = $response['body'];
					
					$filtered_html = '';
					if( !empty($selector) ) {
						$raw_html = $this->wpsi_get_html_by_selector($raw_html, $selector); //, $wpwsopt['output']);
						 if( !is_wp_error( $raw_html ) ) {
							 $filtered_html = $raw_html;
						 } else {
							 $err_str = $raw_html->get_error_message();
						 }
					} elseif( !empty($xpath) ) {
						$raw_html = $this->wpsi_get_html_by_xpath($raw_html, $xpath); //, $wpwsopt['output']);
						 if( !is_wp_error( $raw_html ) ) {
							 $filtered_html = $raw_html;
						 } else {
							 $err_str = $raw_html->get_error_message();
						 }
					} else {
						$filtered_html = $raw_html;
					}
					
					$filtered_html = $this->wpsi_strip_only($filtered_html, '<a>');
					
					
					switch ( $type ) {
						case 'table':
							if ( $widget && empty($columns) )
								$columns = '#,Team,P';
							$filtered_html = $this->wpsi_table($filtered_html, $columns, $highlight, $team, $limit);
						break;
						case 'fixtures':
							$filtered_html = $this->wpsi_fixtures($filtered_html, $highlight, $team, $limit, $team_id);
						break;
						case 'results':
							$filtered_html = $this->wpsi_results($filtered_html, $highlight, $team, $limit, $team_id);
						break;
					}
					
					if ( !empty($width) ) {
						if ( strpos($width, '%') !== false )
							$width = ' style="width:'.$width.';"';
						else
							$width = ' style="width:'.(int)$width.'px;"';
					}
					
					
					$this_wpsiopt_si_table_before = '<div class="si'.$type.(($widget)?' siwidget':'').' '.$style.'">';
					$this_wpsiopt_si_table_after = '</div>';
					
					$c_count = 1;
					$this_wpsiopt_si_before = str_replace('<div', '<div'.$width, $this_wpsiopt_si_table_before, $c_count);
					
					if ( !empty($title) ) {
						$title = htmlspecialchars_decode($title);
						$strip_title = strip_tags($title);
						if ( $strip_title == $title )
							$title = '<h3>'.$title.'</h3>';
					}
					
					$filtered_html = $this_wpsiopt_si_before .$title.$filtered_html. $this_wpsiopt_si_table_after;
					
					return $filtered_html;
				}
				else {
					//return new WP_Error('wpsi_remote_get_failed', $response->get_error_message());
					return 'wpsi_remote_get_failed';
				}
			
		}
		
		/**
		 * Manipulate the results
		 *
		 * @param  none
		 * @return void
		 */
		function wpsi_results($filtered_html, $highlight = '', $team = '', $limit = 0, $team_id = 0) {
			
			$limit = (int)$limit;
			
			$all_columns = array( 'class' => array( 'weekday', 'date', 'team_a', 'result', 'team_b' ) );
			$cols_ok = array( 0, 1, 2, 3, 4);
			
			$filtered_html = preg_replace('#<td[^>]*>#is', '<td>', $filtered_html);
			
			$data = "<?xml version='1.0' ?>\n".$filtered_html;
			
    		$table = new SimpleXmlElement($data);
			
			if ( is_object($table) ) {
				
				$filtered_html = '<table>'."\n";
				
				if ( is_object($table->tbody) ) {
					
					$filtered_html .= '<tbody>'."\n";
					$date_old = '';
					$offset = $this->wpsiopt['si_timezone']*60*60;
					$date_format = $this->getDateFormat();
					$time_format = $this->wpsiopt['si_time_format'];
					$i_limit = 0;
					
					$van_comp = 0;
					if ( $team_id > 0 )
						$van_comp = 1;
					
					foreach ( $table->tbody[0] as $ii => $tr ) {
						$filtered_html_td = '';
						if ($i_limit % 2 == 0)
							$highlight_ok = ' class="even"';
						else
							$highlight_ok = ' class="odd"';
						
						if ( $limit == 0 || $i_limit < $limit ) {
							if ( count($tr->td) > 4 + $van_comp && isset($tr->td[0]->span[0]) ) {
								
								$team_a = $this->correct_team_name( $tr->td[2 + $van_comp] );
								$team_b = $this->correct_team_name( $tr->td[4 + $van_comp] );
								if ( empty($team) || $team == $team_a || $team == $team_b ) { //check for the ONLY team
									
									$span_attr = $tr->td[0]->span[0]->attributes();
									
									$date_new = date_i18n( $date_format, (int)$span_attr['data-value'] + $offset );
									//$time_new = date( $time_format, (int)$span_attr['data-value'] + $offset );
									if ( $date_new != $date_old ) {
										$filtered_html .= '<tr class="date">';
										$filtered_html .= '<td class="date" colspan="'.(3 + $van_comp).'">'.$date_new.'</td>';
										$filtered_html .= '</tr>'."\n";
										$date_old = $date_new;
									}
								
									if ( $highlight == $team_a || $highlight == $team_b ) {
										$highlight_ok = str_replace(' class="', ' class="highlight ', $highlight_ok);
									}
									
									$team_a_bold = '';
									$team_b_bold = '';
									$score = (string)$tr->td[3 + $van_comp];
									$scores = explode(' - ', $score);
									if ( count($scores) == 2 && $scores[0] != $scores[1] ) {
										if ( $scores[0] > $scores[1] )
											$team_a_bold = ' team_bold';
										elseif ( $scores[0] < $scores[1] )
											$team_b_bold = ' team_bold';
									}
									if ($van_comp > 0) {
										$td_2_attr = $tr->td[2]->attributes();
										if (isset($td_2_attr['title']))
											$td_2_attr_title = ' title="'.$td_2_attr['title'].'"';
										else
											$td_2_attr_title = '';
										$filtered_html_td .= '<td class="competition"'.$td_2_attr_title.'>' .$tr->td[2]. '</td>'."\n";
									}
									$filtered_html_td .= '<td class="'.$all_columns['class'][2].$team_a_bold.'">' .$team_a. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][3].'">' .$score. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][4].$team_b_bold.'">' .$team_b. '</td>'."\n";
								}
							}
							if ( !empty($filtered_html_td) ) {
								$filtered_html .= '<tr'.$highlight_ok.'>'."\n";
								$filtered_html .= $filtered_html_td;
								$filtered_html .= '</tr>'."\n";
								
								$i_limit ++;
							}
						}
					}
					$filtered_html .= '</tbody>'."\n";
				}
				
				$filtered_html .= '</table>'."\n";
			}
			
			return $filtered_html;
		}
		
		/**
		 * Manipulate the fixtures
		 *
		 * @param  none
		 * @return void
		 */
		function wpsi_fixtures($filtered_html, $highlight = '', $team = '', $limit = 0, $team_id = 0) {
			
			$limit = (int)$limit;
			
			$all_columns = array( 'class' => array( 'weekday', 'date', 'team_a', 'time', 'team_b' ) );
			$cols_ok = array( 0, 1, 2, 3, 4);
			
			$filtered_html = preg_replace('#<td[^>]*>#is', '<td>', $filtered_html);
			
			$data = "<?xml version='1.0' ?>\n".$filtered_html;
			
    		$table = new SimpleXmlElement($data);
			
			if ( is_object($table) ) {
				
				$filtered_html = '<table>'."\n";
				
				if ( is_object($table->tbody) ) {
					
					$filtered_html .= '<tbody>'."\n";
					$date_old = '';
					$offset = $this->wpsiopt['si_timezone']*60*60;
					$date_format = $this->getDateFormat();
					$time_format = $this->wpsiopt['si_time_format'];
					$i_limit = 0;
					
					$van_comp = 0;
					if ( $team_id > 0 )
						$van_comp = 1;
					
					foreach ( $table->tbody[0] as $ii => $tr ) {
						$filtered_html_td = '';
						if ($i_limit % 2 == 0)
							$highlight_ok = ' class="even"';
						else
							$highlight_ok = ' class="odd"';
						
						if ( $limit == 0 || $i_limit < $limit ) {
							if ( count($tr->td) > 4 + $van_comp && isset($tr->td[0]->span[0]) ) {
								
								$team_a = $this->correct_team_name( $tr->td[2 + $van_comp] );
								$team_b = $this->correct_team_name( $tr->td[4 + $van_comp] );
								if ( empty($team) || $team == $team_a || $team == $team_b ) { //check for the ONLY team
									
									$span_attr = $tr->td[0]->span[0]->attributes();
									
									$date_new = date_i18n( $date_format, (int)$span_attr['data-value'] + $offset );
									$time_new = date_i18n( $time_format, (int)$span_attr['data-value'] + $offset );
									if ( $date_new != $date_old ) {
										$filtered_html .= '<tr class="date">';
										$filtered_html .= '<td class="date" colspan="'.(3 + $van_comp).'">'.$date_new.'</td>';
										$filtered_html .= '</tr>'."\n";
										$date_old = $date_new;
									}
									
									
									if ($van_comp > 0) {
										$td_2_attr = $tr->td[2]->attributes();
										if (isset($td_2_attr['title']))
											$td_2_attr_title = ' title="'.$td_2_attr['title'].'"';
										else
											$td_2_attr_title = '';
										$filtered_html_td .= '<td class="competition"'.$td_2_attr_title.'>' .$tr->td[2]. '</td>'."\n";
									}
									if ( $highlight == $team_a || $highlight == $team_b ) {
										$highlight_ok = str_replace(' class="', ' class="highlight ', $highlight_ok);
									}
									
									$filtered_html_td .= '<td class="'.$all_columns['class'][2 + $van_comp].'">' .$team_a. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][3 + $van_comp].'">' .$time_new. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][4 + $van_comp].'">' .$team_b. '</td>'."\n";
								}
							}
							if ( !empty($filtered_html_td) ) {
								$filtered_html .= '<tr'.$highlight_ok.'>'."\n";
								$filtered_html .= $filtered_html_td;
								$filtered_html .= '</tr>'."\n";
								
								$i_limit ++;
							}
						}
					}
					$filtered_html .= '</tbody>'."\n";
				}
				
				$filtered_html .= '</table>'."\n";
			}
			
			return $filtered_html;
		}
		
		/**
		 * Manipulate the table
		 *
		 * @param  none
		 * @return void
		 */
		function wpsi_table($filtered_html, $columns = '', $highlight = '', $team = '', $limit = 0) {
			$limit = (int)$limit;
			if ( empty($columns) )
				$columns = '#,Team,MP,W,D,L,F,A,G,P';
			else
				$columns = preg_replace('/\s+/', '', $columns);
			
			$all_columns = array( 'name' => array( '#', 'Team', 'MP', 'W', 'D', 'L', 'F', 'A', 'G', 'P' ),
								  'name_translation' => array( __('#', SOCCER_INFO), 
								  							   __('Team', SOCCER_INFO), 
															   __('MP', SOCCER_INFO), 
															   __('W', SOCCER_INFO), 
															   __('D', SOCCER_INFO), 
															   __('L', SOCCER_INFO), 
															   __('F', SOCCER_INFO), 
															   __('A', SOCCER_INFO), 
															   __('G', SOCCER_INFO), 
															   __('P', SOCCER_INFO) ),
								  'class' => array( 'rank', 'team', 'matches_played', 'wins', 'draws', 'losses', 'goals_for', 'goals_against', 'goal_difference', 'points' ),
								  'title' => array( __('Rank', SOCCER_INFO), 
								  					__('Team', SOCCER_INFO), 
													__('Matches played', SOCCER_INFO),
													__('Wins', SOCCER_INFO), 
													__('Draws', SOCCER_INFO), 
													__('Losses', SOCCER_INFO), 
													__('Goals for', SOCCER_INFO), 
													__('Goals against', SOCCER_INFO), 
													__('Goal difference', SOCCER_INFO), 
													__('Points', SOCCER_INFO) ) );
			$cols = explode(',', $columns);
			$c_count = 0;
			foreach ( $cols as $c ) {
				$ii = array_search( $c, $all_columns['name'] );
				if ($ii !== false) {
					$cols_ok[] = $ii;
					$c_count++;
				}
			}
			
			$data = "<?xml version='1.0' ?>\n".$filtered_html;
			
    		$table = new SimpleXmlElement($data);
			
			if ( is_object($table) ) {
				
				$filtered_html = '<table>'."\n";
				
				if ( is_object($table->thead) ) {
					
					$filtered_html .= '<thead><tr>'."\n";
					
					foreach ( $cols_ok as $i => $c ) {
						
						if ( isset($table->thead[0]->tr[0]->th[$c]) ) {
							$th = $table->thead[0]->tr[0]->th[$c];
							//$th_attr = $th->attributes();
							if ( $i == 0 )
								$first_last = ' first';
							elseif ( $i == $c_count - 1 )
								$first_last = ' last';
							else
								$first_last = '';
							$filtered_html .= '<th class="'.$all_columns['class'][$c].$first_last.'" title="'.$all_columns['title'][$c].'">' .$all_columns['name_translation'][$c]. '</th>'."\n";
						}
					}
					$filtered_html .= '</tr></thead>'."\n";
					
					$filtered_html .= '<tbody>'."\n";
					
					$i_limit = 0;
					foreach ( $table->tbody[0] as $ii => $tr ) {
						$filtered_html_td = '';
						
						if ($i_limit % 2 == 0)
							$highlight_ok = ' class="even"';
						else
							$highlight_ok = ' class="odd"';
						
						
						$row_ok = 1;
						if ( $limit == 0 || $i_limit < $limit ) {
							foreach ( $cols_ok as $i => $c ) {
								if ( isset($tr->td[$c]) ) {
									$td = $tr->td[$c];
									//$th_attr = $th->attributes();
									if ( $i == 0 )
										$first_last = ' first';
									elseif ( $i == $c_count - 1 )
										$first_last = ' last';
									else
										$first_last = '';
									if ( $c == 1 ) { //team column
										$td = $this->correct_team_name( $td );
										if ( !empty($team) && $team != $td ) { //check for the ONLY team
											$row_ok = 0;
										}
										if ( $td == $highlight ) {
											$highlight_ok = str_replace(' class="', ' class="highlight ', $highlight_ok);
										}
									}
									$filtered_html_td .= '<td class="'.$all_columns['class'][$c].$first_last.'">' .$td. '</td>'."\n";
								}
							}
							if ( !empty($filtered_html_td) && $row_ok ) {
								$filtered_html .= '<tr'.$highlight_ok.'>'."\n";
								$filtered_html .= $filtered_html_td;
								$filtered_html .= '</tr>'."\n";
								
								$i_limit ++;
							}
						}
					}
					$filtered_html .= '</tbody>'."\n";
				}
				
				$filtered_html .= '</table>'."\n";
			}
			
			return $filtered_html;
		}
		
		
		
		/**
		 * Add the front css
		 *
		 * @param  none
		 * @return void
		 */
		public static function print_front_styles() {
			wp_register_style('soccer-info-front', plugins_url( SOCCER_INFO_BASEPATH.'/css/soccer-info-front.css' ) );
			wp_enqueue_style('soccer-info-front');
		}

		/**
		 * Retrieve the raw response from the HTTP request (or its cached version).
		 * Wrapper function to wp_remote_get()
		 * @param string $url Site URL to retrieve.
		 * @param array $cache_args Optional. Override the defaults.
		 * @param array $http_args Optional. Override the defaults.
		 * @return WP_Error|array The response or WP_Error on failure.
		 */
		function wpsi_remote_get($url, $method = 'GET', $cache_args = array(), $http_args = array()) {
			$default_cache_args = array(
				'cache' => 60,
				'on-error' => 'cache'
			);
			$default_http_args = array(
				'user-agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'
			);
			$cache_args = wp_parse_args( $cache_args, $default_cache_args );
			$http_args = wp_parse_args( $http_args, $default_http_args );
			if(isset($cache_args['headers']) && $cache_args['headers']) {
				$transient = md5($url.serialize($cache_args['headers']));
			} else {
				$transient = md5($url);
			}
		
			if ( false === ( $cache = get_transient($transient) ) || $cache_args['cache'] == 0 ) {
				//$response = wp_remote_request($url, $http_args);
				$response = wp_remote_get($url, array( 'method' => $method, 'timeout' => 30, 'redirection' => 5, 'httpversion' => '1.0', 'blocking' => 'true', 'headers' => $http_args, 'body' => null, 'cookies' => array() ) );
				
				if( !is_wp_error( $response ) ) {
					if($cache_args['cache'] != 0)
						set_transient($transient, $response, $cache_args['cache'] * 60 );
					@$response['headers']['source'] = 'WP_Http';
					return $response;
				} else {
					return new WP_Error('wpsi_remote_get_failed', $response->get_error_message());
				}
			} else {
				$cache = get_transient($transient);
				@$cache['headers']['source'] = 'Cache';
				return $cache;
			}
		}
		
		/**
		 * Strip specified tags
		 * @param string $str
		 * @param string/array $tags
		 * @param bool $strip_content
		 * @return string
		 */
		function wpsi_strip_only($str, $tags, $strip_content = false) {
			$content = '';
			if(!is_array($tags)) {
				$tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
				if(end($tags) == '') array_pop($tags);
			}
			foreach($tags as $tag) {
				if ($strip_content)
					 $content = '(.+</'.$tag.'(>|\s[^>]*>)|)';
				 $str = preg_replace('#</?'.$tag.'(>|\s[^>]*>)'.$content.'#is', '', $str);
			}
			return $str;
		}
		
		/**
		 * Get HTML from a web page using XPath query
		 * @param string $raw_html Raw HTML
		 * @param string $xpath XPath query
		 * @param string $output html or text
		 * @return string
		 */
		function wpsi_get_html_by_xpath($raw_html, $xpath, $output = 'html'){
			// Parsing request using JS_Extractor
			if ( !class_exists('JS_Extractor') )
				require_once 'inc/Extractor/Extractor.php';
			$extractor = new JS_Extractor($raw_html);
			$body = $extractor->query("body")->item(0);
			if (!$result = $body->query($xpath)->item(0)->nodeValue)
				return new WP_Error('wpsi_get_html_by_xpath_failed', "Error parsing xpath: $xpath");
			if($output == 'text')
				return strip_tags($result);
			if($output == 'html')
				return $result;
		}
		
		/**
		 * Get HTML from a web page using selector
		 * @param string $raw_html Raw HTML
		 * @param string $selector Selector
		 * @param string $output html or text
		 * @return string
		 */
		function wpsi_get_html_by_selector($raw_html, $selector, $output = 'html'){
			// Parsing request using phpQuery
			$currcharset = get_bloginfo('charset');
			if ( !class_exists('phpQuery') )
				require_once 'inc/phpQuery-onefile.php';
			$phpquery = phpQuery::newDocumentHTML($raw_html, $currcharset);
			phpQuery::selectDocument($phpquery);
			if($output == 'text')
				return pq($selector)->text();
			if($output == 'html')
				return pq($selector)->html();
			if( empty($output) )
				return new WP_Error('wpsi_get_html_by_selector_failed', "Error parsing selector: $selector");
		}
		
		
		function get_league_number_by_id( $league_id ) {
			$leagues = array_keys($this->competitions);
			
			if ( isset($this->competitions[$leagues[$league_id]]) )
				return $this->competitions[$leagues[$league_id]];
			else
				return 0;
		}
		
		function get_league_number_by_name( $league_name ) {
			$liga = 0;
			$ii = array_search( $league_name, $this->competitions );
			if ( $ii !== false)
				$liga = $ii;
			
			return $liga;
		}
		
		function correct_team_name( $team_name ) {
			$incorrect_teams = array ("Eintracht Fran…",
									  "Borussia M'gla…",
									  "Olympique Mars…",
									  "West Bromwich …",
									  "Wolverhampton …",
									  "Queens Park Ra…",
									  "Szombathelyi H…",
									  "Deportivo La C…");
									  
			$correct_teams = array ("Eintracht Frankfurt",
									"Borussia M'gladbach",
									"Olympique Marseille",
									"West Bromwich Albion",
									"Wolverhampton Wanderers",
									"Queens Park Rangers",
									"Szombathelyi Haladás",
									"Deportivo La Coruña");
			
			$ii = array_search( $team_name, $incorrect_teams );
			if ( $ii !== false && isset($correct_teams[$ii]) )
				return $correct_teams[$ii];
			else
				return $team_name;
		}
		
		public function getLeagueArray() {
			return $this->competitions;
		}
		
		function getTeams($league_id = 1) {
			//http://widgets.soccerway.com/wizard/step2'
			
			$feed_url = 'http://widgets.soccerway.com/a/block_competition_team_control?block_id=page_step2_1_block_widget_parameters_2_block_competition_team_control_2&callback_params=%7B%22data_name%22%3A%20%22team_id%22%2C%20%22group%22%3A%20%22parameters%22%2C%20%22nullable%22%3A%20%22%22%2C%20%22filter%22%3A%20%22%22%7D&action=parentChanged&params=%7B%22parent_value%22%3A%20%22'.$league_id.'%22%7D';
				
			$response = $this->wpsi_remote_get( $feed_url ); //, 'GET', array(), array('content-type' => 'application/json; charset=utf-8') ); //, $cache_args, $http_args);
				
			if ( !is_wp_error( $response ) ) {
				
				$json_html = json_decode($response['body']);
				if ( !empty($json_html) && isset($json_html->{'commands'}[0]->{'parameters'}->{'content'}) ) {
					
					$f = "%<option\ value=\"(.*?)\".*?>(.*?)</option.*?>%is";
					
					preg_match_all($f, $json_html->{'commands'}[0]->{'parameters'}->{'content'}, $matches);
					
					if ( !isset($matches[0][0]) )
						return array();
						
					foreach ($matches[2] as $k => $v)
						$matches[2][$k] = $this->correct_team_name( $matches[2][$k] );
						
					return array( 'value' => $matches[1], 'option' => $matches[2] );
				}
			}
			
			return array();
		}
		
		function get_soccer_info_teams() {
		
			header( "Content-Type: application/json" );			
			
			if ( !isset($_REQUEST['league_id']) || (int)$_REQUEST['league_id'] <= 0 ){
				$response = json_encode( array() );
				echo $response;
				exit;
			}
			
			if (isset($_REQUEST['new_id']) && $_REQUEST['new_id'] == 1)
				$league_id = $this->get_league_number_by_id( (int)$_REQUEST['league_id'] );
			else
				$league_id = (int)$_REQUEST['league_id'];
			
			$teams = $this->getTeams( $league_id );
			
			
			if (isset($_REQUEST['team_id']))
				$team_id = $_REQUEST['team_id'];
			else
				$team_id = 0;
			
			$oo = '';
			if (isset($teams['value'])) {
				$oo_before = '<option value="0||"'.selected($team_id, '0||', false).'>'.__('-- None --', SOCCER_INFO).'</option>';
				$oo_after = '';
				foreach ($teams['value'] as $k => $v) {
					$oo .= '<option value="'.$v.'||'.$teams['option'][$k].'"'.selected($team_id, $v.'||'.$teams['option'][$k], false).'>'.$teams['option'][$k].'</option>';
				}
				$oo = $oo_before.$oo.$oo_after;
			}
			
			$response = json_encode( array('teams' => $oo) );
			echo $response;
			exit;
			
		}
		
		public $competitions = array (
			'NOTHING'							 => 0,	 //0
			'Spanish Primera Division'			 => 7,	 //1
			'English Premier League'			 => 8,	 //2
			'German Bundesliga'					 => 9,	 //3
			'Portuguese Liga'					 => 63,	 //4
			'Italian Serie A'					 => 13,	 //5
			'French Ligue 1'					 => 16,	 //6
			'Dutch Eredivisie'					 => 1,	 //8
			'Belgian Pro League'				 => 24,	 //8
			'Finnish Veikkausliiga'				 => 22,	 //9
			'Hungarian NB I'					 => 67,	 //10
			'Brazilian Serie A'					 => 26,	 //11
			'Argentina - Primera División'		 => 87,	 //12
			'Australia - A-League'				 => 283, //13
			'Austria - Bundesliga'				 => 49,	 //14
			'Belarus - Premier League'			 => 66,	 //15
			'Bulgaria - A PFG'					 => 59,	 //16
			'Canadian Soccer League'			 => 146, //17
			'Czech Republic - Czech Liga'		 => 82,	 //18
			'Denmark - Superliga'				 => 30,	 //19
			'England - Championship'			 => 70,	 //20
			'England - League One'				 => 15,	 //21
			'England - League Two'				 => 32,	 //22
			'France - Ligue 2'					 => 17,	 //23
			'Germany - 2. Bundesliga'			 => 11,	 //24
			'Italy - Serie B'					 => 14,	 //25
			'Japan - J1 League'					 => 109, //26
			'Paraguay - Division Profesional'	 => 157, //27
			'Poland - Ekstraklasa'				 => 119, //28
			'Romania - Liga I'					 => 85,	 //29
			'Russia - Premier League'			 => 121, //30
			'Scotland - Premier League'			 => 43,	 //31
			'Serbia - Super Liga'				 => 440, //32
			'Singapore - S.League'				 => 137, //33
			'Slovakia - Super Liga'				 => 123, //34
			'Spain - Segunda División'			 => 12,	 //35
			'Sweden - Allsvenskan'				 => 28,	 //36
			'Turkey - Süper Lig'				 => 19,	 //37
			'Ukraine - Premier League'			 => 125, //38
			'United States - MLS'				 => 33,	 //39
			'Venezuela - Primera División'		 => 163, //40
			
			'Morocco - GNF 1'					 => 209, //41
			'Bolivia - LFPB'					 => 69,  //42
			'Chile - Primera División'			 => 90,  //43
			'Colombia - Primera A'				 => 91,  //44
			'Costa Rica - Primera División'		 => 315, //45
			'Ecuador - Primera A'				 => 165, //46
			'Mexico - Liga MX'					 => 155, //47
			'Panama - LPF'						 => 525, //48
			'Peru - Primera División'			 => 158, //49
			'Uruguay - Primera División'		 => 162, //50
			'China PR - CSL'					 => 51,  //51
			
		);
	
		/**
		 * Loads the configuration from the database
		 *
		 * @access private
		 * @author Szilard Mihaly
		*/
		function LoadOptions() {
			
			$this->wpsiopt['si_timezone'] = get_option('gmt_offset');
			$this->wpsiopt['si_date_format'] = get_option('date_format');
			$this->wpsiopt['si_time_format'] = SoccerInfo::$wpsiopt_default['si_time_format'];
			$this->wpsiopt['si_date_format_custom'] = $this->wpsiopt['si_date_format'];
			
			
			//Use this only when you are adding a new element
			//delete_option("soccer_info_options");
			
			//First init default values, then overwrite it with stored values so we can add default
			//values with an update which get stored by the next edit.
			$storedoptions = get_option("soccer_info_options");
			if($storedoptions && is_array($storedoptions)) {
				foreach($storedoptions AS $k => $v) {
					$this->wpsiopt[$k] = $v;
				}
			} else update_option("soccer_info_options",$this->wpsiopt); //First time use, store default values
		}
		
		function getDateFormat() {
			if ( $this->wpsiopt['si_date_format'] == 'custom' )
				return $this->wpsiopt['si_date_format_custom'];
			
			return $this->wpsiopt['si_date_format'];
		}
		
	}
	
	$soccer_info = new SoccerInfo();

}


?>