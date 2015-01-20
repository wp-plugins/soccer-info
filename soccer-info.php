<?php
/*
Plugin Name: Soccer Info
Plugin URI: http://www.mihalysoft.com/wordpress-plugins/soccer-info/
Description: Soccer Info lets you display ranking tables, fixtures and results of major soccer leagues without any hassles.
Version: 1.8.1
Requires at least: WordPress 3.3
Tested up to: WordPress 4.1
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
* @copyright 	Copyright 2013-2015
*/
if ( !class_exists('SoccerInfo') ) {
	
	class SoccerInfo {
			
		public static $wpsiopt_default = array(
			'si_timezone'			 => '0',
			'si_date_format'		 => 'l, F j, Y',
			'si_time_format'		 => 'H:i',
			'si_date_format_custom'	 => 'l, F j, Y',
			'si_donated'			 => false			//added as of 1.7.1 version
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
			
			define('SOCCER_INFO_VERSION', '1.8.1');
			define('SOCCER_INFO_PATH', plugin_dir_path(__FILE__));
			define('SOCCER_INFO_BASEPATH', basename(dirname(__FILE__)));
			
			define('SOCCER_INFO', 'soccer-info');  // Text domain & plugin dir
			load_plugin_textdomain(SOCCER_INFO, false, SOCCER_INFO_BASEPATH.'/lang');
			
			$this->wpsiopt = SoccerInfo::$wpsiopt_default; //$this->wpsiopt_default;
			
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
			   
				add_action('admin_notices', array(&$this, 'si_admin_notices'));
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
			   
			//plugin row links
			add_filter('plugin_row_meta', array(&$this, 'si_donate_link'), 10, 2);
			
        }
		
		// Add settings link on plugin page
		function si_settings_link($links) { 
			$settings_link = '<a href="'.admin_url('options-general.php?page='.SOCCER_INFO).'">'.__('Settings', SOCCER_INFO).'</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
		
		function si_donate_link($links, $file) {
			if ($file == plugin_basename(__FILE__)) {
				$links[] = '<a href="'.admin_url('options-general.php?page='.SOCCER_INFO).'">'.__('Settings', SOCCER_INFO).'</a>';
				$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4V94PVSJNFZMA" target="_blank">'.__('Donate', SOCCER_INFO).'</a>';
			}
			return $links;
		}
		
		// Add donation notification to the admin panel
		function si_admin_notices() {
			// Check user capability
			if ( current_user_can('manage_options') ) {
				if ( !isset($this->wpsiopt['si_donated']) || $this->wpsiopt['si_donated'] != SOCCER_INFO_VERSION ) {
					echo '<div class="error fade"><p><b>'.sprintf(__('Soccer Info %s for WordPress', SOCCER_INFO), SOCCER_INFO_VERSION).'</b>: '.__('Please donate to keep this plugin FREE. If you find this plugin useful, please consider making a small donation to help contribute to my time invested and to further development. Thanks for your kind support!',SOCCER_INFO).' | <a href="'.admin_url('options-general.php?page='.SOCCER_INFO).'">'.__('Settings', SOCCER_INFO).'</a></p>';
					echo '
								<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="clear:both;">
								<input type="hidden" name="cmd" value="_s-xclick" />
								<input type="hidden" name="hosted_button_id" value="4V94PVSJNFZMA" />
								<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
								<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
								</form>';
					echo '</div>';
				}
			}
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
			
			if ( 1 == 0 && $team_id > 0 ) {
				$feed_url = 'http://widgets.soccerway.com/widget/free/classic/team/'.$team_id;
			}
			elseif ( ($league_id > -9 && $league_id < 0) || $league_id == -30 ) {
				/**
					if ( $seasons_data[$season_id][$bookie] == '&ttFK=42&country=11' ) {
						//836165 - BL Qualification
						$bl_el_all = array('836870', '836871', '836872', '836873', '836874', '836875', '836876', '836877');
					}
					else {
						//836161 - EL Qualification
						$bl_el_all = array('836878', '836879', '836880', '836881', '836882', '836883', '836884', '836885', '836886', '836887', '836888', '836889');
					}
					foreach ( $bl_el_all as $bl_el ) {
						$feed_url_all[] = 'http://football-data.enetpulse.com/standings.php?round='.$my_schedule['week_no'].'&standing=false'.$seasons_data[$season_id][$bookie].'&oFK='.$bl_el;
					}
				/**/
				$oFK = 836870 - 1 - $league_id;
				if ( $league_id == -30 )
					$oFK = 838266;
				$feed_url = 'http://football-data.enetpulse.com/standings.php?standing=false'.'&ttFK=42&country=11'.'&oFK='.$oFK; //'&oFK=836870';
			}
			elseif ( ($league_id > -21 && $league_id < -8) || $league_id == -31 ) {
				$oFK = 836878 - 9 - $league_id;
				if ( $league_id == -31 )
					$oFK = 838267;
				$feed_url = 'http://football-data.enetpulse.com/standings.php?standing=false'.'&ttFK=73&country=11'.'&oFK='.$oFK; //'&oFK=836878';
			}
			elseif ( $league_id > -30 && $league_id < -20 ) {
				$oFK = array(-21 => 834918, -22 => 834921, -23 => 834922, -24 => 834923, -25 => 834924, -26 => 834925, -27 => 834926, -28 => 834927, -29 => 834928);
				$feed_url = 'http://football-data.enetpulse.com/standings.php?standing=false'.'&ttFK=50&country=all&round=8389'.'&oFK='.$oFK[$league_id]; //'&oFK=834918';
			}
			else {
				$feed_url1 = 'http://widgets.soccerway.com/widget/free/classic/competition/';
				
				$feed_url = $feed_url1.$league_id.'/#d=350x800&f=table,table_colmp,table_colmw,table_colmd,table_colml,table_colgf,table_colga,results,fixtures&cbackground=FFFFFF&ctext=000000&ctitle=F85F00&cshadow=E8E8E8&cbutton=C0C0C0&cbuttontext=000000&chighlight=FF0000&tbody_family=Tahoma,sans-serif&tbody_size=9&tbody_weight=normal&tbody_style=normal&tbody_decoration=none&tbody_transform=none&ttitle_family=Impact,sans-serif&ttitle_size=13&ttitle_weight=normal&ttitle_style=normal&ttitle_decoration=none&ttitle_transform=none&ttab_family=Tahoma,sans-serif&ttab_size=9&ttab_weight=normal&ttab_style=normal&ttab_decoration=none&ttab_transform=none';
			}
			
				//$feed_url = "http://termalfurdo.ro";
				
				$response = $this->wpsi_remote_get( $feed_url ); //, $cache_args, $http_args);
				
				//var_export($feed_url);
				
				//var_export($response);
				
				if ( !is_wp_error( $response ) && isset($response['body']) && !empty($response['body']) ) {
					
					$what = $type; //'table';
					$selector = 'div#tabset div#'.$what;
					$filter_links = '<a>';
					
					$enetpulse = false;
					
					if ( $league_id < 0 ) {
						$enetpulse = $league_id;
						//var_export($response);
						$filter_links = '<a><span><img>';
						if ( $type == 'table' )
							$selector = 'table.BackgroundTableHeader';
						else
							$selector = 'div#LeagueMatches table';
					}
					
					$raw_html = str_ireplace(' id="{country}"', '', $response['body']);
					
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
					
					$filtered_html = $this->wpsi_strip_only($filtered_html, $filter_links); //'<a>');
					
					if ( $league_id < 0 ) {
						$filtered_html = '<table>'.str_replace('nowrap', '', $filtered_html).'</table>';
					}
					
					switch ( $type ) {
						case 'table':
							if ( $widget && empty($columns) )
								$columns = '#,Team,P';
							$filtered_html = $this->wpsi_table($filtered_html, $columns, $highlight, $team, $limit, $enetpulse);
						break;
						case 'fixtures':
							$filtered_html = $this->wpsi_fixtures($filtered_html, $highlight, $team, $limit, $team_id, $enetpulse);
						break;
						case 'results':
							$filtered_html = $this->wpsi_results($filtered_html, $highlight, $team, $limit, $team_id, $enetpulse);
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
		function wpsi_results($filtered_html, $highlight = '', $team = '', $limit = 0, $team_id = 0, $enetpulse = false) {
			
			$limit = (int)$limit;
			
			$all_columns = array( 'class' => array( 'weekday', 'date', 'team_a', 'result', 'team_b' ) );
			$cols_ok = array( 0, 1, 2, 3, 4);
			
			if ( $enetpulse !== false ) {
				$filtered_html = str_replace(' colspan="2"', '></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="3"', '></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="4"', '></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="5"', '></td><td></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="6"', '></td><td></td><td></td><td></td><td></td><td', $filtered_html);
				
				$filtered_html = str_replace(" colspan='2'", '></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='3'", '></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='4'", '></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='5'", '></td><td></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='6'", '></td><td></td><td></td><td></td><td></td><td', $filtered_html);
			
				$filtered_html = str_ireplace(' id="{country}"', '', $filtered_html);
			}
			
			$filtered_html = preg_replace('#<td[^>]*>#is', '<td>', $filtered_html);
			
			$data = "<?xml version='1.0' ?>\n".$filtered_html;
			
			if ( !empty($filtered_html) ) {
				try {
					$table = new SimpleXmlElement($data);
				} catch (Exception $e) {}
			}
			
			if ( isset($table) && is_object($table) ) {
				
				$filtered_html = '<table>'."\n";
				
				if ( is_object($table->tbody) ) {
					
					$filtered_html .= '<tbody>'."\n";
					$date_old = '';
					$offset = $this->wpsiopt['si_timezone']*60*60;
					$date_format = $this->getDateFormat();
					$time_format = $this->wpsiopt['si_time_format'];
					$i_limit = 0;
					
					$van_comp = 0;
					/**
					if ( $team_id > 0 )
						$van_comp = 1;
					/**/
					
					if ( $enetpulse != false )
						$table_tbody_0 = $table;
					else
						$table_tbody_0 = $table->tbody[0];
					
					foreach ( $table_tbody_0 as $ii => $tr ) {
						$filtered_html_td = '';
						if ($i_limit % 2 == 0)
							$highlight_ok = ' class="even"';
						else
							$highlight_ok = ' class="odd"';
						
						if ( $limit == 0 || $i_limit < $limit ) {
							
							if ( (count($tr->td) > 4 + $van_comp) && $enetpulse != false && !empty($tr->td[count($tr->td) - 1]) && strpos($tr->td[count($tr->td) - 1], 'Standings') === false  ) {
								$new_date_format = str_replace(',', '', trim($tr->td[count($tr->td) - 1]));
								$new_date_formats = explode(' ', $new_date_format);
								if ( count($new_date_formats) > 2 ) {
										if ( $new_date_formats[2] < 100 )
											$new_date_formats[2] = 2000 + $new_date_formats[2];
										
										$last_td_date = strtotime($new_date_formats[0].' '.$new_date_formats[1].' '.$new_date_formats[2]);
										
										$date_new = date_i18n( $date_format, (int)$last_td_date + $offset );
										//$time_new = date( $time_format, (int)$span_attr['data-value'] + $offset );
										if ( $date_new != $date_old ) {
											$filtered_html_date = '';
											$filtered_html_date .= '<tr class="date">';
											$filtered_html_date .= '<td class="date" colspan="'.(3 + $van_comp).'">'.$date_new.'</td>';
											$filtered_html_date .= '</tr>'."\n";
										}
								}
							}
									
							if ( count($tr->td) > 4 + $van_comp && ( isset($tr->td[0]->span[0]) || ( $enetpulse != false && $tr->td[0] == 'FT' ) ) ) {
								
								if ( $enetpulse != false ) {
									$team_a = $this->correct_team_name( $tr->td[1 + $van_comp] );
									$team_b = $this->correct_team_name( $tr->td[3 + $van_comp] );
								}
								else {
									$team_a = $this->correct_team_name( $tr->td[2 + $van_comp] );
									$team_b = $this->correct_team_name( $tr->td[4 + $van_comp] );
								}
								if ( empty($team) || $team == $team_a || $team == $team_b ) { //check for the ONLY team
									
									if ( $enetpulse === false ) {
										$span_attr = $tr->td[0]->span[0]->attributes();
										
										$date_new = date_i18n( $date_format, (int)$span_attr['data-value'] + $offset );
										//$time_new = date( $time_format, (int)$span_attr['data-value'] + $offset );
										if ( $date_new != $date_old ) {
											$filtered_html .= '<tr class="date">';
											$filtered_html .= '<td class="date" colspan="'.(3 + $van_comp).'">'.$date_new.'</td>';
											$filtered_html .= '</tr>'."\n";
											$date_old = $date_new;
										}
									}
								
									if ( $highlight == $team_a || $highlight == $team_b ) {
										$highlight_ok = str_replace(' class="', ' class="highlight ', $highlight_ok);
									}
									
									$team_a_bold = '';
									$team_b_bold = '';
									if ( $enetpulse != false ) {
										$score = (string)$tr->td[2 + $van_comp];
									}
									else {
										$score = (string)$tr->td[3 + $van_comp];
									}
									$scores = explode(' - ', trim($score));
									if ( count($scores) == 2 && $scores[0] != $scores[1] ) {
										if ( $scores[0] > $scores[1] )
											$team_a_bold = ' team_bold';
										elseif ( $scores[0] < $scores[1] )
											$team_b_bold = ' team_bold';
									}
									if ($van_comp > 0) {
										if ( $enetpulse != false ) {
											$td_2_attr = $tr->td[1]->attributes();
										}
										else {
											$td_2_attr = $tr->td[2]->attributes();
										}
										if (isset($td_2_attr['title']))
											$td_2_attr_title = ' title="'.$td_2_attr['title'].'"';
										else
											$td_2_attr_title = '';
										
										if ( $enetpulse != false ) {
											$filtered_html_td .= '<td class="competition"'.$td_2_attr_title.'>' .$tr->td[1]. '</td>'."\n";
										}
										else {
											$filtered_html_td .= '<td class="competition"'.$td_2_attr_title.'>' .$tr->td[2]. '</td>'."\n";
										}
									}
									$filtered_html_td .= '<td class="'.$all_columns['class'][2].$team_a_bold.'">' .$team_a. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][3].'">' .$score. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][4].$team_b_bold.'">' .$team_b. '</td>'."\n";
								}
							}
							if ( !empty($filtered_html_td) ) {
								if ( $enetpulse != false && $date_new != $date_old ) {
									$filtered_html .= $filtered_html_date;
									$date_old = $date_new;
								}
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
		function wpsi_fixtures($filtered_html, $highlight = '', $team = '', $limit = 0, $team_id = 0, $enetpulse = false) {
			
			$limit = (int)$limit;
			
			$all_columns = array( 'class' => array( 'weekday', 'date', 'team_a', 'time', 'team_b' ) );
			$cols_ok = array( 0, 1, 2, 3, 4);
			
			if ( $enetpulse !== false ) {
				$filtered_html = str_replace(' colspan="2"', '></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="3"', '></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="4"', '></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="5"', '></td><td></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(' colspan="6"', '></td><td></td><td></td><td></td><td></td><td', $filtered_html);
				
				$filtered_html = str_replace(" colspan='2'", '></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='3'", '></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='4'", '></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='5'", '></td><td></td><td></td><td></td><td', $filtered_html);
				$filtered_html = str_replace(" colspan='6'", '></td><td></td><td></td><td></td><td></td><td', $filtered_html);
			
				$filtered_html = str_ireplace(' id="{country}"', '', $filtered_html);
			}
			
			//echo $filtered_html;
			
			$filtered_html = preg_replace('#<td[^>]*>#is', '<td>', $filtered_html);
			
			$data = "<?xml version='1.0' ?>\n".$filtered_html;
			
			if ( !empty($filtered_html) ) {
				try {
		    		$table = new SimpleXmlElement($data);
				} catch (Exception $e) {}
			}
			
			if ( isset($table) && is_object($table) ) {
				
				$filtered_html = '<table>'."\n";
				
				if ( is_object($table->tbody) ) {
					
					$filtered_html .= '<tbody>'."\n";
					$date_old = '';
					$offset = $this->wpsiopt['si_timezone']*60*60;
					$date_format = $this->getDateFormat();
					$time_format = $this->wpsiopt['si_time_format'];
					$i_limit = 0;
					
					$van_comp = 0;
					/**
					if ( $team_id > 0 )
						$van_comp = 1;
					/**/
					
					if ( $enetpulse != false )
						$table_tbody_0 = $table;
					else
						$table_tbody_0 = $table->tbody[0];
					
					foreach ( $table_tbody_0 as $ii => $tr ) {
						$filtered_html_td = '';
						if ($i_limit % 2 == 0)
							$highlight_ok = ' class="even"';
						else
							$highlight_ok = ' class="odd"';
						
						if ( $limit == 0 || $i_limit < $limit ) {
							
							if ( (count($tr->td) > 4 + $van_comp) && $enetpulse != false && !empty($tr->td[count($tr->td) - 1]) && strpos($tr->td[count($tr->td) - 1], 'Standings') === false  ) {
								$new_date_format = str_replace(',', '', trim($tr->td[count($tr->td) - 1]));
								$new_date_formats = explode(' ', $new_date_format);
								if ( count($new_date_formats) > 2 ) {
										if ( $new_date_formats[2] < 100 )
											$new_date_formats[2] = 2000 + $new_date_formats[2];
										
										$last_td_date = strtotime($new_date_formats[0].' '.$new_date_formats[1].' '.$new_date_formats[2]);
										
										$last_new_date_formats = $new_date_formats;
										
										$date_new = date_i18n( $date_format, (int)$last_td_date + $offset );
										//$time_new = date( $time_format, (int)$span_attr['data-value'] + $offset );
										if ( $date_new != $date_old ) {
											$filtered_html_date = '';
											$filtered_html_date .= '<tr class="date">';
											$filtered_html_date .= '<td class="date" colspan="'.(3 + $van_comp).'">'.$date_new.'</td>';
											$filtered_html_date .= '</tr>'."\n";
										}
								}
							}
									
							if ( count($tr->td) > 4 + $van_comp && ( isset($tr->td[0]->span[0]) || ( $enetpulse != false && strpos($tr->td[0], ':') !== false ) ) ) {
								
								if ( $enetpulse != false ) {
									$team_a = $this->correct_team_name( $tr->td[1 + $van_comp] ); 
									$team_b = $this->correct_team_name( $tr->td[3 + $van_comp] );
								}
								else {
									$team_a = $this->correct_team_name( $tr->td[2 + $van_comp] ); 
									$team_b = $this->correct_team_name( $tr->td[4 + $van_comp] );
								}
								if ( empty($team) || $team == $team_a || $team == $team_b ) { //check for the ONLY team
									
									if ( $enetpulse === false ) {
										$span_attr = $tr->td[0]->span[0]->attributes();
										
										$date_new = date_i18n( $date_format, (int)$span_attr['data-value'] + $offset );
										$time_new = date_i18n( $time_format, (int)$span_attr['data-value'] + $offset );
										if ( $date_new != $date_old ) {
											$filtered_html .= '<tr class="date">';
											$filtered_html .= '<td class="date" colspan="'.(3 + $van_comp).'">'.$date_new.'</td>';
											$filtered_html .= '</tr>'."\n";
											$date_old = $date_new;
										}
									}
									else {
										$time_new = '';
										if ( isset( $last_new_date_formats ) ) {
											$last_td_time = strtotime($last_new_date_formats[0].' '.$last_new_date_formats[1].' '.$last_new_date_formats[2].' '.$tr->td[0].':00');
											$time_new = date_i18n( $time_format, (int)$last_td_time + $offset );
										}
									}
									
									
									if ($van_comp > 0) {
										if ( $enetpulse != false ) {
											$td_2_attr = $tr->td[1]->attributes();
										}
										else {
											$td_2_attr = $tr->td[2]->attributes();
										}
										if (isset($td_2_attr['title']))
											$td_2_attr_title = ' title="'.$td_2_attr['title'].'"';
										else
											$td_2_attr_title = '';
										if ( $enetpulse != false ) {
											$filtered_html_td .= '<td class="competition"'.$td_2_attr_title.'>' .$tr->td[1]. '</td>'."\n";
										}
										else {
											$filtered_html_td .= '<td class="competition"'.$td_2_attr_title.'>' .$tr->td[2]. '</td>'."\n";
										}
									}
									if ( $highlight == $team_a || $highlight == $team_b ) {
										$highlight_ok = str_replace(' class="', ' class="highlight ', $highlight_ok);
									}
									
									$filtered_html_td .= '<td class="'.$all_columns['class'][2 + $van_comp].'">' .$team_a. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][3 + $van_comp].'">' .$time_new. '</td>'."\n";
									$filtered_html_td .= '<td class="'.$all_columns['class'][4 + $van_comp].'">' .$team_b. '</td>'."\n";
									
									if ( $enetpulse != false && !empty($team) && $team != $team_a && $team != $team_b ) { //check for the ONLY team
										$filtered_html_td = '';
									}
								}
							}
							if ( !empty($filtered_html_td) ) {
								if ( $enetpulse != false && $date_new != $date_old ) {
									$filtered_html .= $filtered_html_date;
									$date_old = $date_new;
								}
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
		function wpsi_table($filtered_html, $columns = '', $highlight = '', $team = '', $limit = 0, $enetpulse = false) {
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
			
			if ( $enetpulse != false ) {
				$filtered_html = str_replace(array('Rank', '>P</', 'Gf-Ga', '>+/-</', 'Point'), array('#', '>MP</', 'F</td><td>A', '>G</', 'P'), $filtered_html);
				
				$filtered_html = str_ireplace(' id="{country}"', '', $filtered_html);
				
				$filtered_html = preg_replace('/(\d+)\-(\d+)/i', '$1</td><td>$2', $filtered_html);
			}
			
			$data = "<?xml version='1.0' ?>\n".$filtered_html;
			
			//var_dump($data);
			//echo $filtered_html;
			
			if ( !empty($filtered_html) ) {
				try {
		    		$table = new SimpleXmlElement($data);
				} catch (Exception $e) {}
			}
			
			if ( isset($table) && is_object($table) ) {
				
				$filtered_html = '<table>'."\n";
				
				if ( is_object($table->thead) || ( $enetpulse != false && is_object($table->tr) ) ) {
					
					$the_head = false;
					if ( isset($table->thead[0]) ) {
						
						$the_head = true;
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
					}
					
					if ( $enetpulse != false )
						$table_tbody_0 = $table;
					else
						$table_tbody_0 = $table->tbody[0];
					
					$i_limit = 0;
					foreach ( $table_tbody_0 as $ii => $tr ) {
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
										if ( !empty($team) && $team != $td && $the_head ) { //check for the ONLY team
											$row_ok = 0;
										}
										if ( $td == $highlight ) {
											$highlight_ok = str_replace(' class="', ' class="highlight ', $highlight_ok);
										}
									}
									if ( !$the_head ) {
										$filtered_html_td .= '<th class="'.$all_columns['class'][$c].$first_last.'" title="'.$all_columns['title'][$c].'">' .$all_columns['name_translation'][$c]. '</th>'."\n";
									}
									else {
										$filtered_html_td .= '<td class="'.$all_columns['class'][$c].$first_last.'">' .$td. '</td>'."\n";
									}
								}
							}
							if ( !empty($filtered_html_td) && $row_ok ) {
								if ( !$the_head ) {
									$filtered_html .= '<thead><tr>'."\n";
									$filtered_html .= $filtered_html_td;
									$filtered_html .= '</thead></tr>'."\n";
									
									$filtered_html .= '<tbody>'."\n";
									
									$the_head = true;
									
									$i_limit --;
								}
								else {
									$filtered_html .= '<tr'.$highlight_ok.'>'."\n";
									$filtered_html .= $filtered_html_td;
									$filtered_html .= '</tr>'."\n";
								}
								
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
				//'user-agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'
				'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64)'
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
				$response = wp_remote_get($url, array( 'method' => $method, 'timeout' => 60, 'redirection' => 5, 'httpversion' => '1.1', /*'blocking' => 'true',*/ 'headers' => $http_args, 'body' => null, 'cookies' => array() ) );
				
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
									  "Deportivo La C…",
									  "Arles",
									  "Volendam",
									  "Nyíregyháza Sp…",
									  "Dunaújváros-Pá…",
									  "Atlético de Ko…");
									  
			$correct_teams = array ("Eintracht Frankfurt",
									"Borussia M'gladbach",
									"Olympique Marseille",
									"West Bromwich Albion",
									"Wolverhampton Wanderers",
									"Queens Park Rangers",
									"Szombathelyi Haladás",
									"Deportivo La Coruña",
									"Arles-Avignon",
									"FC Volendam",
									"Nyíregyháza Spartacus FC",
									"Dunaújváros FC",
									"Atlético de Kolkata");
			
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
			if ( ($league_id > -9 && $league_id < 0) || $league_id == -30 ) {
				$oFK = 836870 - 1 - $league_id;
				if ( $league_id == -30 )
					$oFK = 838266;
				$feed_url = 'http://football-data.enetpulse.com/standings.php?standing=false'.'&ttFK=42&country=11'.'&oFK='.$oFK; //'&oFK=836870';
			}
			elseif ( ($league_id > -21 && $league_id < -8) || $league_id == -31 ) {
				$oFK = 836878 - 9 - $league_id;
				if ( $league_id == -31 )
					$oFK = 838267;
				$feed_url = 'http://football-data.enetpulse.com/standings.php?standing=false'.'&ttFK=73&country=11'.'&oFK='.$oFK; //'&oFK=836878';
			}
			elseif ( $league_id > -30 && $league_id < -20 ) {
				$oFK = array(-21 => 834918, -22 => 834921, -23 => 834922, -24 => 834923, -25 => 834924, -26 => 834925, -27 => 834926, -28 => 834927, -29 => 834928);
				$feed_url = 'http://football-data.enetpulse.com/standings.php?standing=false'.'&ttFK=50&country=all&round=8389'.'&oFK='.$oFK[$league_id]; //'&oFK=834918';
			}
			else {
				//http://widgets.soccerway.com/wizard/step2'
				$feed_url = 'http://widgets.soccerway.com/a/block_competition_team_control?block_id=page_step2_1_block_widget_parameters_2_block_competition_team_control_2&callback_params=%7B%22data_name%22%3A%20%22team_id%22%2C%20%22group%22%3A%20%22parameters%22%2C%20%22nullable%22%3A%20%22%22%2C%20%22filter%22%3A%20%22%22%7D&action=parentChanged&params=%7B%22parent_value%22%3A%20%22'.$league_id.'%22%7D';
			}
				
			$response = $this->wpsi_remote_get( $feed_url ); //, 'GET', array(), array('content-type' => 'application/json; charset=utf-8') ); //, $cache_args, $http_args);
				
			if ( !is_wp_error( $response ) ) {
				
				if ( $league_id > 0 ) {
					$json_html = json_decode($response['body']);
					if ( !empty($json_html) && isset($json_html->{'commands'}[0]->{'parameters'}->{'content'}) ) {
						
						$f = "%<option\ value=\"(.*?)\".*?>(.*?)</option.*?>%is";
						
						preg_match_all($f, $json_html->{'commands'}[0]->{'parameters'}->{'content'}, $matches);
						
						if ( !isset($matches[0][0]) )
							return array();
							
						foreach ($matches[2] as $k => $v)
							$matches[2][$k] = $this->correct_team_name( $matches[2][$k] );
						
						//var_export($matches[1]);
						//var_export($matches[2]);
							
						return array( 'value' => $matches[1], 'option' => $matches[2] );
					}
				}
				else {
					//echo $league_id."\n".$feed_url."\n\n";
					//var_export($response);
					
					$what = 'table';
					$enetpulse = $league_id;
					$filter_links = '<a><span><img>';
					$selector = 'table.BackgroundTableHeader';
					
					$raw_html = str_ireplace(' id="{country}"', '', $response['body']);
					
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
					
					$filtered_html = $this->wpsi_strip_only($filtered_html, $filter_links); //'<a>');
					
					$filtered_html = '<table>'.str_replace('nowrap', '', $filtered_html).'</table>';
					
					//echo $filtered_html;
					
					$data = "<?xml version='1.0' ?>\n".$filtered_html;
					
					if ( !empty($filtered_html) ) {
						try {
							$table = new SimpleXmlElement($data);
						} catch (Exception $e) { return array(); }
					}
					
					if ( isset($table) && is_object($table) ) {
						
						if ( is_object($table->tr) ) {
							
							$the_head = false;
							$table_tbody_0 = $table;
							
							$limit = 0;
							$i_limit = 0;
							foreach ( $table_tbody_0 as $ii => $tr ) {
								$filtered_html_td = '';
								
								$row_ok = 1;
								if ( $i_limit > 0 && isset($tr->td[1]) ) {
										$td = $tr->td[1];
										$td = $this->correct_team_name( $td );
										
										//$filtered_html_td .= $td."\n";
										$filtered_html .= $filtered_html_td;
										
										$matches[1][] = 1;
										$matches[2][] = $td;
								}
								$i_limit ++;
							}
							if ( isset($matches[1]) && count($matches[1]) > 0 ) {
								return array( 'value' => $matches[1], 'option' => $matches[2] );
							}
						}
					}
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
			'NOTHING'							 => 0,			 //0
			'Spanish Primera Division'			 => 7,			 //1
			'English Premier League'			 => 8,			 //2
			'German Bundesliga'					 => 9,			 //3
			'Portuguese Liga'					 => 63,			 //4
			'Italian Serie A'					 => 13,			 //5
			'French Ligue 1'					 => 16,			 //6
			'Dutch Eredivisie'					 => 1,			 //8
			'Belgian Pro League'				 => 24,			 //8
			'Finnish Veikkausliiga'				 => 22,			 //9
			'Hungarian NB I'					 => 67,			 //10
			'Brazilian Serie A'					 => 26,			 //11
			'Argentina - Primera División'		 => 87,			 //12
			'Australia - A-League'				 => 283,		 //13
			'Austria - Bundesliga'				 => 49,			 //14
			'Belarus - Premier League'			 => 66,			 //15
			'Bulgaria - A PFG'					 => 59,			 //16
			'Canadian Soccer League'			 => 146,		 //17
			'Czech Republic - Czech Liga'		 => 82,			 //18
			'Denmark - Superliga'				 => 30,			 //19
			'England - Championship'			 => 70,			 //20
			'England - League One'				 => 15,			 //21
			'England - League Two'				 => 32,			 //22
			'France - Ligue 2'					 => 17,			 //23
			'Germany - 2. Bundesliga'			 => 11,			 //24
			'Italy - Serie B'					 => 14,			 //25
			'Japan - J1 League'					 => 109,		 //26
			'Paraguay - Division Profesional'	 => 157,		 //27
			'Poland - Ekstraklasa'				 => 119,		 //28
			'Romania - Liga I'					 => 85,			 //29
			'Russia - Premier League'			 => 121,		 //30
			'Scotland - Premier League'			 => 43,			 //31
			'Serbia - Super Liga'				 => 440,		 //32
			'Singapore - S.League'				 => 137,		 //33
			'Slovakia - Super Liga'				 => 123,		 //34
			'Spain - Segunda División'			 => 12,			 //35
			'Sweden - Allsvenskan'				 => 28,			 //36
			'Turkey - Süper Lig'				 => 19,			 //37
			'Ukraine - Premier League'			 => 125,		 //38
			'United States - MLS'				 => 33,			 //39
			'Venezuela - Primera División'		 => 163,		 //40
			
			'Morocco - GNF 1'					 => 209,		 //41
			'Bolivia - LFPB'					 => 69,			 //42
			'Chile - Primera División'			 => 90,			 //43
			'Colombia - Primera A'				 => 91,			 //44
			'Costa Rica - Primera División'		 => 315,		 //45
			'Ecuador - Primera A'				 => 165,		 //46
			'Mexico - Liga MX'					 => 155,		 //47
			'Panama - LPF'						 => 525,		 //48
			'Peru - Primera División'			 => 158,		 //49
			'Uruguay - Primera División'		 => 162,		 //50
			'China PR - CSL'					 => 51,			 //51
			
			/**/
			
			'Afghanistan - Afghan Premier League'	 => 1093,	 //52
			'Albania - Superliga'					 => 48,		 //53
			'Albania - 1st Division'				 => 578,	 //54
			'Albania - 2nd Division'				 => 672,	 //55
			'Algeria - Ligue 1'						 => 205,	 //56
			'Algeria - Ligue 2'						 => 207,	 //57
			'American Samoa - Division 1'			 => 885,	 //58
			'Andorra - 1a Divisió'					 => 139,	 //59
			'Andorra - 2a Divisió'					 => 491,	 //60
			'Angola - Girabola'						 => 493,	 //61
			'Antigua and Barbuda - Premier Division' => 583,	 //62
			'Argentina - Primera División -'		 => 87,		 //63
			'Argentina - Prim B Nacional'			 => 88,		 //64
			'Argentina - Prim B Metro'				 => 471,	 //65
			'Argentina - Argentino A'				 => 454,	 //66
			'Argentina - Prim C Metro'				 => 472,	 //67
			'Argentina - Argentino B'				 => 501,	 //68
			'Argentina - Prim D Metro'				 => 481,	 //69
			'Armenia - Premier League'				 => 143,	 //70
			'Armenia - First League'				 => 542,	 //71
			'Aruba - Division di Honor'				 => 589,	 //72
			'Australia - A-League -'				 => 283,	 //73
			'Australia - Capital Territory'			 => 624,	 //74
			'Australia - New South Wales'			 => 606,	 //75
			'Australia - Northern'					 => 626,	 //76
			'Australia - Brisbane'					 => 721,	 //77
			'Australia - Northern NSW'				 => 625,	 //78
			'Australia - NSL'						 => 42,		 //79
			'Australia - Queensland'				 => 608,	 //80
			'Australia - South Australian'			 => 607,	 //81
			'Australia - T-League (Victory League)'	 => 1111,	 //82
			'Australia - Tasmania'					 => 611,	 //83
			'Australia - Victoria'					 => 318,	 //84
			'Australia - Western Australia'			 => 609,	 //85
			'Australia - National Youth League'		 => 1080,	 //86
			'Austria - Bundesliga -'					 => 49,		 //87
			'Austria - 1. Liga'						 => 50,		 //88
			'Austria - Regionalliga'				 => 553,	 //89
			'Austria - Landesliga'					 => 628,	 //90
			'Austria - Jugendliga U18'				 => 1089,	 //91
			'Azerbaijan - Premyer Liqa'				 => 106,	 //92
			'Azerbaijan - Birinci Dasta'			 => 581,	 //93
			'Bahamas - BFA Senior League'			 => 587,	 //94
			'Bahrain - Premier League'				 => 238,	 //95
			'Bangladesh - Premier League'			 => 537,	 //96
			'Barbados - Premier League'				 => 524,	 //97
			'Belarus - Premier League -'				 => 66,		 //98
			'Belarus - 1. Division'					 => 263,	 //99
			'Belarus - 2. Division'					 => 804,	 //100
			'Belgium - Pro League'					 => 24,		 //101
			'Belgium - Second Division'				 => 52,		 //102
			'Belgium - Third Division'				 => 133,	 //103
			'Belgium - Promotion'					 => 572,	 //104
			'Belgium - Provincial'					 => 833,	 //105
			'Belize - Premier League'				 => 691,	 //106
			'Benin - Championnat National'			 => 870,	 //107
			'Bermuda - Premier League'				 => 538,	 //108
			'Bhutan - National League'				 => 1104,	 //109
			'Bhutan - A-Division'					 => 908,	 //110
			'Bolivia - LFPB -'						 => 69,		 //111
			'Bolivia - Nacional B'					 => 1082,	 //112
			'Bosnia-Herzegovina - Premier Liga'		 => 64,		 //113
			'Bosnia-Herzegovina - 1st League'		 => 144,	 //114
			'Botswana - Premier League'				 => 855,	 //115
			'Brazil - Serie A'						 => 26,		 //116
			'Brazil - Serie B'						 => 89,		 //117
			'Brazil - Serie C'						 => 321,	 //118
			'Brazil - Serie D'						 => 736,	 //119
			'Brazil - Copa do Nordeste'				 => 817,	 //120
			'Brazil - Paulista A1'					 => 239,	 //121
			
			'Brazil - Paulista A2'					 => 593,	 //122
			'Brazil - Paulista A3'					 => 699,	 //123
			'Brazil - Paulista Série B'				 => 921,	 //124
			'Brazil - Carioca 1'					 => 240,	 //125
			'Brazil - Carioca 2'					 => 595,	 //126
			'Brazil - Gaucho 1'						 => 388,	 //127
			'Brazil - Gaucho 2'						 => 600,	 //128
			'Brazil - Mineiro 1'					 => 387,	 //129
			'Brazil - Mineiro 2'					 => 594,	 //130
			'Brazil - Baiano 1'						 => 394,	 //131
			'Brazil - Baiano 2'						 => 596,	 //132
			'Brazil - Paranaense 1'					 => 386,	 //133
			'Brazil - Paranaense 2'					 => 727,	 //134
			'Brazil - Pernambucano 1'				 => 392,	 //135
			'Brazil - Pernambucano 2'				 => 934,	 //136
			'Brazil - Catarinense 1'				 => 390,	 //137
			'Brazil - Catarinense 2'				 => 955,	 //138
			'Brazil - Goiano 1'						 => 389,	 //139
			'Brazil - Goiano 2'						 => 922,	 //140
			'Brazil - Cearense 1'					 => 395,	 //141
			'Brazil - Cearense 2'					 => 778,	 //142
			'Brazil - Paraense'						 => 396,	 //143
			'Brazil - Brasiliense'					 => 393,	 //144
			'Brazil - Paraibano'					 => 399,	 //145
			'Brazil - Alagoano'						 => 398,	 //146
			'Brazil - Potiguar'						 => 397,	 //147
			'Brazil - Sergipano'					 => 402,	 //148
			'Brazil - Amazonense'					 => 405,	 //149
			'Brazil - Matogrossense'				 => 391,	 //150
			'Brazil - Sul-Matogrossense'			 => 401,	 //151
			'Brazil - Capixaba'						 => 408,	 //152
			'Brazil - Maranhense'					 => 403,	 //153
			'Brazil - Piauiense'					 => 400,	 //154
			'Brazil - Acreano'						 => 409,	 //155
			'Brazil - Rondoniense'					 => 406,	 //156
			'Brazil - Tocantinense'					 => 407,	 //157
			'Brazil - Amapaense'					 => 410,	 //158
			'Brazil - Roraimense'					 => 411,	 //159
			'British Virgin Islands - BVIFA Football League'		 => 907,	 //160
			'Brunei Darussalam - Super League'		 => 912,	 //161
			'Bulgaria - A PFG -'						 => 59,		 //162
			'Bulgaria - B PFG'						 => 60,		 //163
			'Bulgaria - V AFG'						 => 664,	 //164
			'Bulgaria - Elite U19'					 => 1078,	 //165
			'Burkina Faso - 1ère Division'			 => 878,	 //166
			'Burundi - Ligue A'						 => 914,	 //167
			'Cambodia - C-League'					 => 797,	 //168
			'Cameroon - Elite ONE'					 => 266,	 //169
			'Canada - Canadian Soccer League'		 => 146,	 //170
			'Canada - PCSL'							 => 147,	 //171
			'Cape Verde Islands - Campeonato Nacional'				 => 894,	 //172
			'Cayman Islands - CIFA Premier League'	 => 906,	 //173
			'Chad - LFN'							 => 903,	 //174
			'Chile - Primera División -'				 => 90,		 //175
			'Chile - Primera B'						 => 438,	 //176
			'Chile - Segunda División'				 => 1020,	 //177
			'Chile - Tercera A'						 => 779,	 //178
			'China PR - CSL -'						 => 51,		 //179
			'China PR - China League One'			 => 148,	 //180
			'Chinese Taipei - Inter City league'	 => 383,	 //181
			'Chinese Taipei - Entrerprise Football League'			 => 998,	 //182
			'Colombia - Primera A -'					 => 91,		 //183
			'Colombia - Primera B'					 => 448,	 //184
			'Congo - Ligue 1'						 => 928,	 //185
			'Congo DR - Super Ligue'				 => 780,	 //186
			'Cook Islands - Round Cup'				 => 887,	 //187
			'Costa Rica - Primera División -'			 => 315,	 //188
			'Costa Rica - Liga de Ascenso'			 => 752,	 //189
			'Côte d\'Ivoire - Ligue 1'				 => 530,	 //190
			'Croatia - 1. HNL'						 => 61,		 //191
			'Croatia - 2. HNL'						 => 62,		 //192
			'Croatia - 3. HNL'						 => 687,	 //193
			'Croatia - 1. HNL Juniori'				 => 1094,	 //194
			'Cuba - Primera Division'				 => 567,	 //195
			'Curaçao - Curaçao Sekshon Pagá'		 => 585,	 //196
			'Cyprus - 1. Division'					 => 75,		 //197
			'Cyprus - 2. Division B1'				 => 486,	 //198
			'Cyprus - 2. Division B2'				 => 1156,	 //199
			'Cyprus - 3. Division'					 => 663,	 //200
			'Czech Republic - Czech Liga -'			 => 82,		 //201
			'Czech Republic - 2. liga'				 => 83,		 //202
			'Czech Republic - 3. liga'				 => 84,		 //203
			'Czech Republic - 4. liga'				 => 633,	 //204
			'Czech Republic - Juniorská liga'		 => 1065,	 //205
			'Czech Republic - 1. Liga U19'			 => 1071,	 //206
			'Denmark - Superliga -'					 => 30,		 //207
			'Denmark - 1st Division'				 => 39,		 //208
			'Denmark - 2nd Division'				 => 40,		 //209
			'Denmark - Denmark Series'				 => 632,	 //210
			'Denmark - Reserve League'				 => 1120,	 //211
			'Denmark - U19 Ligaen'					 => 1072,	 //212
			'Djibouti - Division 1'					 => 1003,	 //213
			'Dominica - Premier League'				 => 1134,	 //214
			'Dominican Republic - Liga Mayor'		 => 547,	 //215
			'Ecuador - Primera A -'					 => 165,	 //216
			'Ecuador - Primera B'					 => 447,	 //217
			'Egypt - Premier League'				 => 206,	 //218
			'Egypt - Second Divison'				 => 666,	 //219
			'El Salvador - Primera Division'		 => 378,	 //220
			'England - Premier League'				 => 8,		 //221
			'England - Championship -'				 => 70,		 //222
			'England - League One -'					 => 15,		 //223
			'England - League Two -'					 => 32,		 //224
			'England - Conference National'			 => 71,		 //225
			'England - Conference N / S'			 => 302,	 //226
			'England - Non League Premier'			 => 306,	 //227
			'England - Non League Div One'			 => 308,	 //228
			'England - U21 Premier League'			 => 1058,	 //229
			'England - Premier Academy League'		 => 950,	 //230
			'England - Premier Reserve League'		 => 949,	 //231
			'Estonia - Meistriliiga'				 => 111,	 //232
			'Estonia - Esiliiga A'					 => 112,	 //233
			'Estonia - Esiliiga B'					 => 1108,	 //234
			'Estonia - II Liiga'					 => 783,	 //235
			'Ethiopia - Premier League'				 => 880,	 //236
			'Faroe Islands - Meistaradeildin'		 => 81,		 //237
			'Faroe Islands - 1. Deild'				 => 384,	 //238
			'Faroe Islands - 2. Deild'				 => 792,	 //239
			'Fiji - National Football League'		 => 591,	 //240
			'Finland - Veikkausliiga'				 => 22,		 //241
			'Finland - Ykkönen'						 => 35,		 //242
			'Finland - Kakkonen'					 => 41,		 //243
			'France - Ligue 1'						 => 16,		 //244
			'France - Ligue 2 -'						 => 17,		 //245
			'France - National'						 => 57,		 //246
			'France - CFA'							 => 354,	 //247
			'France - CFA 2'						 => 557,	 //248
			'France - Championnat National U-19'	 => 951,	 //249
			'French Guiana - Division d\'Honneur'	 => 849,	 //250
			'Gabon - Championnat D1'				 => 888,	 //251
			'Gambia - GFA League'					 => 809,	 //252
			'Georgia - Umaglesi Liga'				 => 166,	 //253
			'Georgia - Pirveli Liga'				 => 761,	 //254
			'Georgia - Meore Liga'					 => 1100,	 //255
			'Georgia - Reserve League'				 => 1118,	 //256
			'Germany - Bundesliga'					 => 9,		 //257
			'Germany - 2. Bundesliga -'				 => 11,		 //258
			'Germany - 3. Liga'						 => 622,	 //259
			'Germany - Regionalliga'				 => 55,		 //260
			'Germany - Oberliga'					 => 366,	 //261
			'Germany - U-19 Bundesliga'				 => 945,	 //262
			'Ghana - Premier League'				 => 487,	 //263
			'Gibraltar - Premier Division'			 => 1141,	 //264
			'Greece - Super League'					 => 107,	 //265
			'Greece - Football League'				 => 108,	 //266
			'Greece - Football League 2'			 => 140,	 //267
			'Greece - Delta Ethniki'				 => 989,	 //268
			'Greece - Super League K20'				 => 1074,	 //269
			'Grenada - Premier Division'			 => 584,	 //270
			'Guadeloupe - Division d\'Honneur'		 => 735,	 //271
			'Guam - Division One'					 => 676,	 //272
			'Guatemala - Liga Nacional'				 => 320,	 //273
			'Guatemala - Primera Division'			 => 746,	 //274
			'Guyana - GFF Super League'				 => 777,	 //275
			'Haiti - Championnat National'			 => 523,	 //276
			'Honduras - Liga Nacional'				 => 463,	 //277
			'Hong Kong - HKFA 1st Division'			 => 113,	 //278
			'Hong Kong - HKFA 2nd Division'			 => 657,	 //279
			'Hungary - NB I'						 => 67,		 //280
			'Hungary - NB II'						 => 68,		 //281
			'Hungary - NB III'						 => 656,	 //282
			'Hungary - U19 League'					 => 1124,	 //283
			'Iceland - Úrvalsdeild'					 => 31,		 //284
			'Iceland - 1. Deild'					 => 38,		 //285
			'Iceland - 2. Deild'					 => 544,	 //286
			'Iceland - 3. Deild'					 => 1119,	 //287
			'India - I-League'						 => 150,	 //288
			'India - I-League 2nd Division'			 => 534,	 //289
			'Indonesia - IPL'						 => 1001,	 //290
			'Indonesia - ISL'						 => 629,	 //291
			'Indonesia - Divisi Utama (ISL)'		 => 151,	 //292
			'Iran - Persian Gulf Cup'				 => 76,		 //293
			'Iran - Azadegan League'				 => 602,	 //294
			'Iraq - Iraqi League'					 => 518,	 //295
			'Ireland Republic - Premier Division'	 => 34,		 //296
			'Ireland Republic - First Division'		 => 77,		 //297
			'Ireland Republic - A Championship'		 => 627,	 //298
			'Israel - Ligat ha\'Al'					 => 117,	 //299
			'Israel - Liga Leumit'					 => 141,	 //300
			'Israel - Liga Artzit'					 => 142,	 //301
			'Italy - Serie A'						 => 13,		 //302
			'Italy - Serie B -'						 => 14,		 //303
			'Italy - Lega Pro 1'					 => 53,		 //304
			'Italy - Lega Pro 2'					 => 358,	 //305
			'Italy - Serie D'						 => 659,	 //306
			'Italy - Campionato Nazionale Primavera' => 952,	 //307
			'Italy - Dante Berretti'				 => 1092,	 //308
			'Jamaica - Premier League'				 => 477,	 //309
			'Japan - J1 League -'						 => 109,	 //310
			'Japan - J2 League'						 => 110,	 //311
			'Japan - Japan Football League'			 => 540,	 //312
			'Jordan - League'						 => 218,	 //313
			'Kazakhstan - Premier League'			 => 79,		 //314
			'Kazakhstan - 1. Division'				 => 279,	 //315
			'Kenya - Premier League'				 => 715,	 //316
			'Korea Republic - K League Classic'		 => 136,	 //317
			'Korea Republic - K League Challenge'	 => 1110,	 //318
			'Korea Republic - National League'		 => 616,	 //319
			'Korea Republic - Challengers League'	 => 618,	 //320
			'Kosovo - Superliga'					 => 1152,	 //321
			'Kuwait - Premier League'				 => 237,	 //322
			'Kuwait - Division 1'					 => 670,	 //323
			'Kyrgyzstan - Top Liga'					 => 795,	 //324
			'Laos - Lao League'						 => 1131,	 //325
			'Latvia - Virsliga'						 => 116,	 //326
			'Latvia - 1. Liga'						 => 265,	 //327
			'Lebanon - Premier League'				 => 217,	 //328
			'Lesotho - Lesotho Premier League'		 => 879,	 //329
			'Liberia - LFA National League'			 => 1130,	 //330
			'Libya - Premier League'				 => 236,	 //331
			'Lithuania - A Lyga'					 => 118,	 //332
			'Lithuania - 1 Lyga'					 => 258,	 //333
			'Luxembourg - National Division'		 => 134,	 //334
			'Luxembourg - Promotion d\'Honneur'		 => 492,	 //335
			'Luxembourg - 1. Division'				 => 661,	 //336
			'Macao - Primeira Divisão'				 => 694,	 //337
			'Macedonia FYR - First League'			 => 65,		 //338
			'Macedonia FYR - Second League'			 => 361,	 //339
			'Madagascar - Ligue des Champions'		 => 911,	 //340
			'Malawi - Super League'					 => 899,	 //341
			'Malaysia - Super League'				 => 153,	 //342
			'Malaysia - Premier League'				 => 154,	 //343
			'Malaysia - FAM League'					 => 1007,	 //344
			'Maldives - Dhivehi League'				 => 675,	 //345
			'Mali - Première Division'				 => 881,	 //346
			'Malta - Premier League'				 => 152,	 //347
			'Malta - First Division'				 => 473,	 //348
			'Malta - Second Division'				 => 660,	 //349
			'Martinique - Division d\'Honneur'		 => 848,	 //350
			'Mauritania - Premier League'			 => 592,	 //351
			'Mauritius - Mauritian League'			 => 926,	 //352
			'Mexico - Liga MX -'						 => 155,	 //353
			'Mexico - Ascenso MX'					 => 156,	 //354
			'Mexico - Segunda División'				 => 1153,	 //355
			'Moldova - Divizia Națională'			 => 80,		 //356
			'Moldova - Divizia A'					 => 561,	 //357
			'Moldova - Divizia B'					 => 856,	 //358
			'Mongolia - Niislel League'				 => 956,	 //359
			'Montenegro - First League'				 => 445,	 //360
			'Montenegro - Second League'			 => 566,	 //361
			'Morocco - GNF 1 -'						 => 209,	 //362
			'Morocco - GNF 2'						 => 474,	 //363
			'Mozambique - Moçambola'				 => 810,	 //364
			'Myanmar - National League'				 => 791,	 //365
			'Namibia - Premier League'				 => 603,	 //366
			'Nepal - National League'				 => 1012,	 //367
			'Nepal - A Division'					 => 787,	 //368
			'Netherlands - Eredivisie'				 => 1,		 //369
			'Netherlands - Eerste Divisie'			 => 5,		 //370
			'Netherlands - Topklasse'				 => 826,	 //371
			'Netherlands - Hoofdklasse'				 => 303,	 //372
			'Netherlands - Eerste Klasse'			 => 658,	 //373
			'Netherlands - Play-offs 1/2'			 => 286,	 //374
			'Netherlands - Play-offs 3/4'			 => 1031,	 //375
			'Netherlands - Play-offs 4/5'			 => 1033,	 //376
			'Netherlands - Beloften'				 => 1066,	 //377
			'Netherlands - Eredivisie U19'			 => 1075,	 //378
			'New Caledonia - Super Ligue'			 => 788,	 //379
			'New Zealand - Premiership'				 => 73,		 //380
			'Nicaragua - Primera Division'			 => 351,	 //381
			'Niger - Ligue 1'						 => 896,	 //382
			'Nigeria - NPFL'						 => 296,	 //383
			'Northern Ireland - Premiership'		 => 78,		 //384
			'Northern Ireland - Championship 1'		 => 310,	 //385
			'Northern Ireland - Championship 2'		 => 316,	 //386
			'Norway - Eliteserien'					 => 29,		 //387
			'Norway - 1. Division'					 => 36,		 //388
			'Norway - 2. Divisjon'					 => 503,	 //389
			'Norway - 3. Divisjon'					 => 1005,	 //390
			'Oman - Elite League'					 => 377,	 //391
			'Pakistan - Premier League'				 => 520,	 //392
			'Pakistan - 2nd Division'				 => 997,	 //393
			'Palestine - West Bank League'			 => 853,	 //394
			'Panama - LPF -'							 => 525,	 //395
			'Papua New Guinea - National Soccer League'				 => 889,	 //396
			'Paraguay - Division Profesional -'		 => 157,	 //397
			'Paraguay - Division Intermedia'		 => 546,	 //398
			'Peru - Primera División -'				 => 158,	 //399
			'Peru - Segunda División'				 => 439,	 //400
			'Philippines - UFL'						 => 916,	 //401
			'Poland - Ekstraklasa -'					 => 119,	 //402
			'Poland - I Liga'						 => 120,	 //403
			'Poland - II Liga'						 => 558,	 //404
			'Poland - III Liga'						 => 647,	 //405
			'Poland - Młoda Ekstraklasa'			 => 1085,	 //406
			'Portugal - Primeira Liga'				 => 63,		 //407
			'Portugal - Liga de Honra'				 => 100,	 //408
			'Portugal - Campeonato Nacional'		 => 101,	 //409
			'Portugal - III Divisão'				 => 651,	 //410
			'Portugal - Júniores U19'				 => 1076,	 //411
			'Puerto Rico - LNFPR First Division'	 => 731,	 //412
			'Qatar - Stars League'					 => 215,	 //413
			'Qatar - League 2'						 => 697,	 //414
			'Reunion - D1 Promotionelle'			 => 789,	 //415
			'Romania - Liga I -'						 => 85,		 //416
			'Romania - Liga II'						 => 159,	 //417
			'Romania - Liga III'					 => 565,	 //418
			'Russia - Premier League -'				 => 121,	 //419
			'Russia - FNL'							 => 122,	 //420
			'Russia - 2. Division'					 => 267,	 //421
			'Russia - LFL'							 => 805,	 //422
			'Russia - U21 Premier League'			 => 1087,	 //423
			'Rwanda - National Soccer League'		 => 781,	 //424
			'Samoa - National League'				 => 890,	 //425
			'San Marino - Campionato'				 => 160,	 //426
			'São Tomé e Príncipe - Campeonato Nacional'				 => 936,	 //427
			'Saudi Arabia - Pro League'				 => 216,	 //428
			'Saudi Arabia - Division 1'				 => 573,	 //429
			'Saudi Arabia - Division 2'				 => 669,	 //430
			'Saudi Arabia - Youth League'			 => 1101,	 //431
			'Scotland - Premiership'				 => 43,		 //432
			'Scotland - Championship'				 => 45,		 //433
			'Scotland - League One'					 => 46,		 //434
			'Scotland - League Two'					 => 47,		 //435
			'Scotland - Scottish Football League'	 => 1157,	 //436
			'Scotland - Highland League'			 => 301,	 //437
			'Scotland - East of Scotland'			 => 347,	 //438
			'Scotland - SPL U20'					 => 1073,	 //439
			'Senegal - Ligue 1'						 => 909,	 //440
			'Serbia - Super Liga -'					 => 440,	 //441
			'Serbia - Prva Liga'					 => 441,	 //442
			'Serbia - Srpska Liga'					 => 442,	 //443
			'Serbia and Montenegro - Prva Liga'		 => 92,		 //444
			'Serbia and Montenegro - Druga Liga'	 => 161,	 //445
			'Serbia and Montenegro - Treca Liga'	 => 317,	 //446
			'Seychelles - Division One'				 => 910,	 //447
			'Sierra Leone - Premier League'			 => 920,	 //448
			'Singapore - S.League -'					 => 137,	 //449
			'Slovakia - Super Liga -'					 => 123,	 //450
			'Slovakia - 2. liga'					 => 124,	 //451
			'Slovakia - 3. liga'					 => 644,	 //452
			'Slovenia - 1. SNL'						 => 86,		 //453
			'Slovenia - 2. SNL'						 => 299,	 //454
			'Slovenia - 3. SNL'						 => 630,	 //455
			'Solomon Islands - S-League'			 => 892,	 //456
			'South Africa - PSL'					 => 214,	 //457
			'South Africa - 1st Division'			 => 526,	 //458
			'Spain - Primera División'				 => 7,		 //459
			'Spain - Segunda División -'				 => 12,		 //460
			'Spain - Segunda B'						 => 98,		 //461
			'Spain - Tercera Division'				 => 569,	 //462
			'Sri Lanka - Champions League'			 => 901,	 //463
			'St. Kitts and Nevis - Premier Division' => 528,	 //464
			'Sudan - Sudani Premier League'			 => 601,	 //465
			'Suriname - Hoofdklasse'				 => 469,	 //466
			'Suriname - Eerste Klasse'				 => 605,	 //467
			'Swaziland - MTN Premier League'		 => 213,	 //468
			'Sweden - Allsvenskan -'					 => 28,		 //469
			'Sweden - Superettan'					 => 37,		 //470
			'Sweden - Division 1'					 => 427,	 //471
			'Sweden - Division 2'					 => 502,	 //472
			'Sweden - U21 League'					 => 1064,	 //473
			'Sweden - U19 League'					 => 1016,	 //474
			'Switzerland - Super League'			 => 27,		 //475
			'Switzerland - Challenge League'		 => 99,		 //476
			'Switzerland - 1. Liga Promotion'		 => 1043,	 //477
			'Switzerland - 1. Liga Classic'			 => 554,	 //478
			'Switzerland - 2. Liga Interregional'	 => 648,	 //479
			'Switzerland - U18 League'				 => 1096,	 //480
			'Syria - Premier League'				 => 212,	 //481
			'Tahiti - Super Ligue Mana'				 => 767,	 //482
			'Tajikistan - Vysshaya Liga'			 => 796,	 //483
			'Tanzania - Ligi kuu Bara'				 => 857,	 //484
			'Thailand - Thai Premier League'		 => 519,	 //485
			'Thailand - Thai Division 1'			 => 782,	 //486
			'Togo - Championnat National'			 => 712,	 //487
			'Trinidad and Tobago - T &amp; T Pro League'			 => 465,	 //488
			'Tunisia - Ligue 1'						 => 210,	 //489
			'Tunisia - Ligue 2'						 => 649,	 //490
			'Turkey - Süper Lig -'					 => 19,		 //491
			'Turkey - 1. Lig'						 => 97,		 //492
			'Turkey - 2. Lig'						 => 562,	 //493
			'Turkey - 3. Lig'						 => 654,	 //494
			'Turkey - A2 Ligi (Reserve)'			 => 990,	 //495
			'Turkey - Elit Akademi Ligi'			 => 1059,	 //496
			'Turkey - Akademi Ligleri'				 => 995,	 //497
			'Turkmenistan - Ýokary Liga'			 => 800,	 //498
			'Turks and Caicos Islands - Football League'			 => 893,	 //499
			'Tuvalu - A-Division'					 => 1013,	 //500
			'Uganda - FUFA Super League'			 => 1105,	 //501
			'Uganda - Super League'					 => 871,	 //502
			'Ukraine - Premier League -'				 => 125,	 //503
			'Ukraine - Persha Liga'					 => 233,	 //504
			'Ukraine - Druha Liga'					 => 559,	 //505
			'Ukraine - U21 League'					 => 1083,	 //506
			'Ukraine - U19 League'					 => 1077,	 //507
			'United Arab Emirates - Arabian Gulf League'			 => 344,	 //508
			'United Arab Emirates - Division 1 - Group A'			 => 574,	 //509
			'United Arab Emirates - Division 1 - Group B'			 => 827,	 //510
			'United Arab Emirates - Reserve League'	 => 1090,	 //511
			'United States - MLS -'					 => 33,		 //512
			'United States - NASL'					 => 917,	 //513
			'United States - USL Pro'				 => 918,	 //514
			'United States - PDL'					 => 522,	 //515
			'United States - USL Pro / MLS Reserve'	 => 1133,	 //516
			'United States - USSF Division 2'		 => 145,	 //517
			'United States - USL 2'					 => 521,	 //518
			'Uruguay - Primera División -'			 => 162,	 //519
			'Uruguay - Segunda División'			 => 532,	 //520
			'Uzbekistan - PFL'						 => 494,	 //521
			'Uzbekistan - 1st Division'				 => 614,	 //522
			'Vanuatu - Premia Divisen'				 => 891,	 //523
			'Vanuatu - National Super League'		 => 1142,	 //524
			'Venezuela - Primera División -'			 => 163,	 //525
			'Venezuela - Segunda División'			 => 507,	 //526
			'Vietnam - V-League'					 => 234,	 //527
			'Vietnam - First Division'				 => 598,	 //528
			'Wales - Premier League'				 => 74,		 //529
			'Wales - Feeder Leagues'				 => 309,	 //530
			'Yemen - Yemeni League'					 => 235,	 //531
			'Zambia - Super League'					 => 495,	 //532
			'Zimbabwe - Premier Soccer League'		 => 496,	 //533
			
			//new leagues, added on - 2014-03-31
			'Algeria - U21 League 1'				 => 1246,	 //534
			'Algeria - U21 League 2'				 => 1247,	 //535
			'Argentina - Reserve League'			 => 1255,	 //536
			'Australia - National Premier League'	 => 1231,	 //537
			'Australia - Victoria Division One'		 => 1318,	 //538
			'Australia - Brisbane Reserves Premier League'			 => 1332,	 //539
			'Australia - South Australia Reserves Premier League'	 => 1334,	 //540
			'Australia - NPL Youth League'			 => 1333,	 //541
			'Azerbaijan - Reserve League'			 => 1187,	 //542
			'Azerbaijan - U19 League'				 => 1236,	 //543
			'Bahrain - Second Division'				 => 1196,	 //544
			'Belarus - Reserve League'				 => 1226,	 //545
			'Belgium - Reserve Pro League'			 => 1188,	 //546
			'Canada - Reserve League'				 => 1237,	 //547
			'Chinese Taipei - U18 League'			 => 1252,	 //548
			'Cyprus - U21 League'					 => 1240,	 //549
			'Denmark - U21 Ligaen'					 => 1316,	 //550
			'Denmark - U17 Ligaen'					 => 1189,	 //551
			'Egypt - Division One'					 => 1267,	 //552
			'England - Central League'				 => 1192,	 //553
			'England - Professional Development League 2'			 => 1191,	 //554
			'Estonia - U19 League'					 => 1343,	 //555
			'Finland - U19 League'					 => 1328,	 //556
			'Guinea - Ligue 1'						 => 1265,	 //557
			'Hong Kong - Reserve Division'			 => 1190,	 //558
			'Hungary - U21 League'					 => 1161,	 //559
			'Hungary - U18 League'					 => 1162,	 //560
			'Iceland - U19 League'					 => 1227,	 //561
			'India - Calcutta Premier Division'		 => 1232,	 //562
			'India - U19 League'					 => 1346,	 //563
			'Indonesia - Play-offs 1/2 (ISL)'		 => 1169,	 //564
			'Israel - U19 Elite Division'			 => 1193,	 //565
			'Japan - J3 League'						 => 1272,	 //566
			'Jordan - 1st Division'					 => 1195,	 //567
			'Lithuania - Reserve League'			 => 1222,	 //568
			'Malta - Gozo First Division'			 => 1264,	 //569
			'Mexico - U20 League'					 => 1221,	 //570
			'Mexico - U17 League'					 => 1257,	 //571
			'New Zealand - ASB Youth League'		 => 1321,	 //572
			'Northern Ireland - NIFL Reserve League' => 1327,	 //573
			'Panama - Liga Nacional de Ascenso'		 => 1259,	 //574
			'Poland - Central Youth League'			 => 1197,	 //575
			'Portugal - Júniores U17'				 => 1251,	 //576
			'Scotland - SPFL Reserve League'		 => 1225,	 //577
			'Singapore - Reserve Prime League'		 => 1317,	 //578
			'Slovakia - U19 League'					 => 1220,	 //579
			'Somalia - First Division'				 => 1177,	 //580
			'Spain - U18 League'					 => 1258,	 //581
			'Thailand - Thai Division 2 League'		 => 1307,	 //582
			'Turkey - U19 Elit Ligi'				 => 1168,	 //583
			'United Arab Emirates - U19 League'		 => 1235,	 //584
			'Uruguay - Segunda Amateur'				 => 1180,	 //585
			'Venezuela - U20 League'				 => 1260,	 //586
			'Vietnam - Second Division'				 => 1344,	 //587
			'Vietnam - U19 Championship'			 => 1326,	 //588
			
			//new leagues, added on - 2014-10-02
			'Bonaire - Bonaire League'				 => 1355,	 //589
			'England - Professional U18 Development League 2'	 => 1387,	 //590
			'England - U21 Premier League Division 2' => 1405,	 //591
			'England - Youth Alliance'				 => 1385,	 //592
			'Iceland - 4. Deild'					 => 1363,	 //593
			'India - Indian Super League'			 => 1417,	 //594
			'Scotland - Development League'			 => 1415,	 //595
			'Scotland - Development League 2'		 => 1413,	 //596
			'Serbia - Play-offs 1/2'				 => 1373,	 //597
			'Turkey - U21 Süper Lig'				 => 1403,	 //598
			'United Arab Emirates - U21 League'		 => 1421,	 //599
			'United Arab Emirates - U18 League'		 => 1419,	 //600
			'Vietnam - Play-offs 1/2'				 => 1409,	 //601
			'Vietnam - U21 Championship'			 => 1411,	 //602
			
			'UEFA Champions League Group A'			 => -1,		 //603
			'UEFA Champions League Group B'			 => -2,		 //604
			'UEFA Champions League Group C'			 => -3,		 //605
			'UEFA Champions League Group D'			 => -4,		 //606
			'UEFA Champions League Group E'			 => -5,		 //607
			'UEFA Champions League Group F'			 => -6,		 //608
			'UEFA Champions League Group G'			 => -7,		 //609
			'UEFA Champions League Group H'			 => -8,		 //610
			
			'UEFA Europa League Group A'			 => -9,		 //611
			'UEFA Europa League Group B'			 => -10,	 //612
			'UEFA Europa League Group C'			 => -11,	 //613
			'UEFA Europa League Group D'			 => -12,	 //614
			'UEFA Europa League Group E'			 => -13,	 //615
			'UEFA Europa League Group F'			 => -14,	 //616
			'UEFA Europa League Group G'			 => -15,	 //617
			'UEFA Europa League Group H'			 => -16,	 //618
			'UEFA Europa League Group I'			 => -17,	 //619
			'UEFA Europa League Group J'			 => -18,	 //620
			'UEFA Europa League Group K'			 => -19,	 //621
			'UEFA Europa League Group L'			 => -20,	 //622
			
			//new leagues, added on - 2014-10-06
			'Euro 2016 Qualification Group A'		 => -21,	 //623
			'Euro 2016 Qualification Group B'		 => -22,	 //624
			'Euro 2016 Qualification Group C'		 => -23,	 //625
			'Euro 2016 Qualification Group D'		 => -24,	 //626
			'Euro 2016 Qualification Group E'		 => -25,	 //627
			'Euro 2016 Qualification Group F'		 => -26,	 //628
			'Euro 2016 Qualification Group G'		 => -27,	 //629
			'Euro 2016 Qualification Group H'		 => -28,	 //630
			'Euro 2016 Qualification Group I'		 => -29,	 //631
			
			//new leagues, added on - 2015-01-20
			'UEFA Champions League Final Stages'	 => -30,	 //632
			
			'UEFA Europa League Final Stages'		 => -31,	 //633
			
			/**/
			
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
			$this->wpsiopt['si_donated'] = SoccerInfo::$wpsiopt_default['si_donated'];
			
			
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