<?php

/*
 * This file is part of the SoccerInfo package.
 *
 * (c) Szilard Mihaly <office@mihalysoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( ! class_exists('SoccerInfo_Admin')) {
    
    /**
     * Manage the rendering in the back-end.
     *
     * @category   Admin
     * @package    SoccerInfo
     * @author     Szilard Mihaly
     * @copyright  (c) 2012 Mihaly Soft
     */
    class SoccerInfo_Admin {

        /**
         * Constructor
         *
         * @param  none
         * @return void
         */
        public function __construct() {}

        /**
         * Add the admin styles
         *
         * @param  none
         * @return void
         */
        public static function print_admin_styles()
        {
            // Execute this only when we are on a SoccerInfo page
            if (isset($_GET['page']))
            {
                // We quit if the current page isn't one of SoccerInfo
                if ( ! in_array(trim($_GET['page']), SoccerInfo::$pages ))
                    return;

                wp_register_style('soccer-info-backend', plugins_url( SOCCER_INFO_BASEPATH.'/css/soccer-info-admin.css' ));
                wp_enqueue_style('soccer-info-backend');
            }
        }
		
	
		function HtmlPrintBoxHeader($id, $title, $right = false) {
			?>
				<div id="<?php echo $id; ?>" class="postbox">
					<h3 class="hndle"><span><?php echo $title ?></span></h3>
					<div class="inside">
			<?php
		}
		
		function HtmlPrintBoxFooter( $right = false) {
				?>
					</div>
				</div>
				<?php
		}
	
		/**
		 * Returns a link pointing back to the plugin page in WordPress
		 * 
		 * @return string The full url
		 */
		function GetBackLink() {
			global $wp_version;
			$url = '';
			//admin_url was added in WP 2.6.0
			if(function_exists("admin_url")) $url = admin_url("options-general.php?page=" .  SOCCER_INFO_BASEPATH);
			else $url = $_SERVER['PHP_SELF'] . "?page=" .  SOCCER_INFO_BASEPATH;
			
			//Some browser cache the page... great! So lets add some no caching params depending on the WP and plugin version
			$url.='&si_wpv=' . $wp_version . '&si_pv=' . SOCCER_INFO_VERSION;
			
			return $url;
		}

        /**
         * Backend pages handler
         *
         * @param  none
         * @return string
         */
        public function admin_page() {
			global $wp_version, $soccer_info;
				
            // JS must be enabled to use properly SoccerInfo...
            _e('<noscript>Javascript must be enabled, thank you.</noscript>', SOCCER_INFO);
            
            // Initialize libraries
            //$ctl = new SoccerInfo_Admin;
			
			$wpsiopt = get_option("soccer_info_options");
		
			?>
			
			<div class="wrap" id="si_div">
				<form method="post" action="<?php echo $this->GetBackLink() ?>">
				<h2><?php printf(__('Soccer Info %s for WordPress', SOCCER_INFO), SOCCER_INFO_VERSION); ?> </h2>
				
				<?php
				if ( isset($_POST['si_update']) && !empty($_POST['si_update']) ) { //Pressed Button: Update Config
					check_admin_referer('soccer_info');
					
					if (isset($_POST['si_timezone']) && ( $_POST['si_timezone'] == 0 || !empty($_POST['si_timezone']) ))
						$wpsiopt['si_timezone'] = $_POST['si_timezone'];
					
					if (isset($_POST['si_date_format']) && !empty($_POST['si_date_format']))
						$wpsiopt['si_date_format'] = $_POST['si_date_format'];
					
					if (isset($_POST['si_time_format']) && !empty($_POST['si_time_format']))
						$wpsiopt['si_time_format'] = $_POST['si_time_format'];
					
					if (isset($_POST['si_date_format_custom']) && !empty($_POST['si_date_format_custom']))
						$wpsiopt['si_date_format_custom'] = $_POST['si_date_format_custom'];
					
					update_option("soccer_info_options", $wpsiopt);
					
					?>
					<div class="updated">
						<p><?php _e('Settings Updated', SOCCER_INFO);?></p>
					</div>
					<?php
					
				}
				
				if (isset($_POST['si_reset_config'])) {
					check_admin_referer('soccer_info');
					
					delete_option("soccer_info_options");
					
					$soccer_info->wpsiopt = SoccerInfo::$wpsiopt_default;
					$soccer_info->LoadOptions();
					
					$wpsiopt = get_option("soccer_info_options");
					
					?>
					<div class="updated">
						<p><?php _e('Settings Updated to the default values', SOCCER_INFO);?></p>
					</div>
					<?php
				}
				
				?>
					
				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<div class="inner-sidebar">
						<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
							
							<?php $this->HtmlPrintBoxHeader('si_pnres',__('About this Plugin:',SOCCER_INFO),true); ?>
								<?php _e('Soccer Info lets you display ranking tables, fixtures and results of major soccer leagues without any hassles.',SOCCER_INFO); ?>
								<?php
									$translator_name = __('translator_name', SOCCER_INFO);
									if ( $translator_name != 'translator_name'  ) {
										echo '<br />'.__('Translated by:', SOCCER_INFO).'<br />';
										$translator_url = __('translator_url', SOCCER_INFO);
										if ( $translator_url != 'translator_url' )
											echo '<a class="si_button si_pluginSupport" href="'.$translator_url.'">';
										
										echo $translator_name;
										
										if ( $translator_url != 'translator_url' )
											echo '</a>';
									}
								?>
									
							<?php $this->HtmlPrintBoxFooter(true); ?>
							
							<?php $this->HtmlPrintBoxHeader('si_faq',__('Frequently Asked Questions:',SOCCER_INFO),true); ?>
								<?php _e("
<h4>I've just activated Soccer Info, what do I need to do now?</h4>
<p>
You are now able to add the shortcodes that displays the tables (fixtures or results) to any of your posts or pages.<br /> 
For example: <strong>[soccer-info id='1' type='table' /]</strong><br />
That means you want to display the raking <em>`table`</em> (type) of the soccer league with the <em>id=1</em> (Spanish Primera Division)<br />
You will find the whole list of the soccer leagues supported by the plugin on the <strong>`Settings` > `Soccer Info`</strong> page.
</p>
<h4>I don't know how to use shortcodes, what should I do then?</h4>
<p>
For your confort, we added a function to the post/page editor that automatically generates and inserts the shortcodes for you. You will be prompted to select the league (and some other info) and then just click `Insert`<br />
It sounds easy. Isn't is?
</p>
<h4>Does this plugin have a widget?</h4>
<p>
Yes, and it's easy to use.
</p>
<h4>I have a another question. Where can I ask that?</h4>
<p>
For more information, check out the plugin's website: <a href='http://www.mihalysoft.com/wordpress-plugins/soccer-info/' target='_blank'>Soccer Info</a>
</p>",SOCCER_INFO); ?>
							<?php $this->HtmlPrintBoxFooter(true); ?>
							
							<?php $this->HtmlPrintBoxHeader('si_league_list',__('Supported Leagues:',SOCCER_INFO),true); ?>
								<?php
									$i_l = 0;
									foreach($soccer_info->competitions as $league => $ii) {
										if ( $i_l > 0 )
											echo esc_html($league).' <span class="alignright">ID = '.$i_l.'</span><br />'."\n";
										$i_l++;
									}
								?>
							<?php $this->HtmlPrintBoxFooter(true); ?>
							
							
						</div>
					</div>
					
					<div class="has-sidebar si-padded" >
					
						<div id="post-body-content" class="has-sidebar-content">
						
								<div class="meta-box-sortabless">
							
						<!-- Basic Options -->
						<?php $this->HtmlPrintBoxHeader('si_options',__('Options', SOCCER_INFO)); ?>
	
							<!-- <p><?php _e('Description...', SOCCER_INFO) ?></p> -->
							
							<table class="form-table" style="clear:none;">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<label for="si_timezone">
											<?php _e('Timezone', SOCCER_INFO) ?>
										</label>
									</th>
									<td>
										<select id="si_timezone" name="si_timezone">
											<option value="-12"<?php if ($wpsiopt['si_timezone'] == -12) echo ' selected="selected"' ?>>UTC -12</option>
											<?php
											for ($i = -11; $i < 14; $i ++) {
												if ($i.'.5' == $wpsiopt['si_timezone'])
													$selected_5 = ' selected="selected"';
												else
													$selected_5 = '';
												if($i < 0)echo '<option value="'.$i.'.5"'.$selected_5.'>UTC '.$i.':30</option>';
												
												if($i == 0) {
													if ('-0.5' == $wpsiopt['si_timezone'])
														$selected_0_5 = ' selected="selected"';
													else
														$selected_0_5 = '';
													echo '<option value="-0.5"'.$selected_0_5.'>UTC -0:30</option>';
												}
												
												if ($i == $wpsiopt['si_timezone'])
													$selected = ' selected="selected"';
												else
													$selected = '';
												echo '<option value="'.$i.'"'.$selected.'>UTC '.(($i>=0)?'+':'').$i.'</option>';
												
												if($i >= 0)echo '<option value="'.$i.'.5"'.$selected_5.'>UTC +'.$i.':30</option>';
												
												if(in_array($i, array(5, 8, 12, 13))) {
													if ($i.'.75' == $wpsiopt['si_timezone'])
														$selected_75 = ' selected="selected"';
													else
														$selected_75 = '';
													echo '<option value="'.$i.'.75"'.$selected_75.'>UTC +'.$i.':45</option>';
												}
											}
											?>
											<option value="14"<?php if ($wpsiopt['si_timezone'] == 14) echo ' selected="selected"' ?>>UTC +14</option>
										</select>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="si_date_format">
											<?php _e('Date Format', SOCCER_INFO) ?>
										</label>
									</th>
									<td>
										<fieldset>
											<legend class="screen-reader-text"><span><?php _e('Date Format', SOCCER_INFO) ?></span></legend>
											<?php
											$o_dates = array( __('l, F j, Y', SOCCER_INFO), 
															  __('F j, Y', SOCCER_INFO), 
															  __('Y/m/d', SOCCER_INFO), 
															  __('m/d/Y', SOCCER_INFO), 
															  __('d/m/Y', SOCCER_INFO) );
											foreach ($o_dates as $o_d) {
												if ($o_d == $wpsiopt['si_date_format']) {
													$checked = ' checked="checked"';
												}
												else
													$checked = '';
												echo '<label title="'.$o_d.'"><input type="radio" name="si_date_format" value="'.$o_d.'"'.$checked.'> <span>'.date_i18n($o_d).'</span></label><br />';
											}
											?>
											<label><input type="radio" name="si_date_format" id="si_date_format_custom_radio" value="custom"<?php if ($wpsiopt['si_date_format'] == 'custom') echo ' checked="checked"'; ?> /> <?php _e('Custom:', SOCCER_INFO);?> </label><input type="text" name="si_date_format_custom" value="<?php echo $wpsiopt['si_date_format_custom'];?>" class="small-text" /> <span class="example"><?php echo date_i18n($wpsiopt['si_date_format_custom']);?></span>  <img class='ajax-loading' src='<?php echo admin_url();?>images/wpspin_light.gif' />
											<p><a href="http://codex.wordpress.org/Formatting_Date_and_Time" target="_blank"><?php _e('Documentation on date and time formatting.', SOCCER_INFO); ?></a></p>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<label for="si_date_format">
											<?php _e('Time Format', SOCCER_INFO) ?>
										</label>
									</th>
									<td>
										<fieldset>
											<legend class="screen-reader-text"><span><?php _e('Time Format', SOCCER_INFO); ?></span></legend>
											<?php
											$o_times = array( __('g:i a', SOCCER_INFO), 
															  __('g:i A', SOCCER_INFO), 
															  __('H:i', SOCCER_INFO) );
											foreach ($o_times as $o_t) {
												if ($o_t == $wpsiopt['si_time_format'])
													$checked = ' checked="checked"';
												else
													$checked = '';
												echo '<label title="'.$o_t.'"><input type="radio" name="si_time_format" value="'.$o_t.'"'.$checked.'> <span>'.date_i18n($o_t).'</span></label><br />';
											}
											?>
										</fieldset>
									</td>
								</tr>
							</tbody>
							</table>
							
						<?php $this->HtmlPrintBoxFooter(); ?>
						
						</div> <!-- meta-box-sortabless -->
						</div> <!-- has-sidebar-content -->
						
						<p class="submit">
								<?php wp_nonce_field('soccer_info') ?>
								<input type="submit" name="si_update" value="<?php _e('Update options', SOCCER_INFO); ?>" class="button-primary" />
								<input type="submit" onclick='return confirm("<?php _e('Do you really want to reset your configuration?', SOCCER_INFO); ?>");' class="si_warning" name="si_reset_config" value="<?php _e('Reset options', SOCCER_INFO); ?>" />
						</p>
					</div> <!-- has-sidebar si-padded -->
					
				</div> <!-- metabox-holder has-right-sidebar -->
				
				</form>
			</div> <!-- wrap -->
			<?php
            
            // Page Footer
           // echo $this->admin_footer();
        }

        /**
         * Add's new global menu, if $href is false menu is added
         * but registred as submenuable
         *
         * @return void
         */
        protected static function add_root_menu($name, $id, $href = FALSE)
        {
            global $wp_admin_bar;
            if ( ! is_super_admin() || ! is_admin_bar_showing())
              return;

            $wp_admin_bar->add_menu(array(
                'id'    => $id,
                'title' => $name,
                'href'  => $href
            ));
        }

        /**
         * Add's new submenu where additinal $meta specifies class,
         * id, target or onclick parameters
         *
         * @return void
         */
        protected static function add_sub_menu($name, $link, $parent, $id, $meta = FALSE)
        {
            global $wp_admin_bar;
            if ( ! is_super_admin() || ! is_admin_bar_showing())
                return;
            
            $wp_admin_bar->add_menu(array(
                'parent' => $parent,
                'title'  => $name,
                'href'   => $link,
                'meta'   => $meta,
                'id'     => $id
            ));
        }
        
        /**
         * Add the admin scripts
         *
         * @param  none
         * @return void
         */
        public static function print_admin_scripts()
        {
            // Execute this only when we are on a SoccerInfo page
            if (isset($_GET['page']))
            {
                // We quit if the current page isn't one of SoccerInfo
                if ( ! in_array(trim($_GET['page']), SoccerInfo::$pages ))
                    return;
                
                // Make sure to use the latest version of jQuery...
                //wp_deregister_script('jquery');
                //wp_register_script('jquery', ('http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js'), FALSE, NULL, TRUE);
                //wp_enqueue_script('jquery');

                wp_register_script('soccer-info', plugins_url( SOCCER_INFO_BASEPATH.'/js/admin.js'), array('jquery') );
                wp_enqueue_script('soccer-info');
                //wp_register_script('soccer-info-mask', plugins_url( SOCCER_INFO_BASEPATH.'/js/jquery.maskedinput.js'), array('jquery') );
                //wp_enqueue_script('soccer-info-mask');
            }
        }
        public static function print_admin_scripts_widgets() {
			
			wp_register_script('soccer-info-widgets', plugins_url( SOCCER_INFO_BASEPATH.'/js/admin-widgets.js'), array('jquery') );
			wp_enqueue_script('soccer-info-widgets');
			
		}
        
        /**
         * Admin menu generation
         *
         * @param  none
         * @return void
         */
        public static function admin_menu() {
			
            $instance = new SoccerInfo_Admin;
            $parent   = 'soccer_info_overview';
			
			
		
			if (function_exists('add_options_page')) {
				add_options_page(__('Soccer Info', SOCCER_INFO), __('Soccer Info', SOCCER_INFO), 'manage_options', SOCCER_INFO_BASEPATH, array($instance,'admin_page'));
			}
        }

        /**
         * Add TinyMCE Button
         *
         * @param  none
         * @return void
         */
        public static function add_editor_button()
        {
            // Don't bother doing this stuff if the current user lacks permissions
            if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages')) return;

            // Check for SoccerInfo capability
            //if ( ! current_user_can('soccer_info')) return;

            // Add only in Rich Editor mode
            if (get_user_option('rich_editing') == 'true')
            {
                add_filter('mce_external_plugins', array('SoccerInfo_Admin', 'add_editor_plugin'));
                add_filter('mce_buttons', array('SoccerInfo_Admin', 'register_editor_button'));
            }
        }
        
        /**
         * Add TinyMCE plugin
         *
         * @param  array $plugin_array
         * @return array
         */
        public function add_editor_plugin($plugin_array)
        {
            $plugin_array['SoccerInfo'] = plugins_url( SOCCER_INFO_BASEPATH.'/js/tinymce/editor_plugin.js');
            return $plugin_array;
        }
        
        /**
         * Register TinyMCE button
         *
         * @param  array $buttons
         * @return array
         */
        public function register_editor_button($buttons)
        {
            array_push($buttons, 'separator', 'SoccerInfo');
            return $buttons;
        }
    }
}