<?php

/*
 * This file is part of the SoccerInfo package.
 *
 * (c) Szilard Mihaly <office@mihalysoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( ! class_exists('SoccerInfo_Widgets')) {
    
    /**
     * Manage the SoccerInfo widgets.
     *
     * @category   Widgets
     * @package    Soccer Info
     * @author     Szilard Mihaly
     * @copyright  (c) 2012 Mihaly Soft
     */
    class SoccerInfo_Widgets extends WP_Widget {
		
		public $leagues = array();

        /**
         * Constructor
         *
         * @param  none
         * @return void
         */
        public function __construct() {
            parent::WP_Widget(
                'soccer_info_widget',
                'Soccer Info',
                array('description' => __("Display a league's Ranking Table, Next Fixtures and Latest Results", SOCCER_INFO) )
            );
			
			global $soccer_info;
			$this->leagues = $soccer_info->getLeagueArray();
        }

        /**
         * Widget method
         *
         * @param  mixed $args
         * @param  mixed $instance
         * @return void
         */
        public function widget($args, $instance) {

            // Extract arguments
            extract($args);
			
			$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
			$type = $instance['type'];
			$league_id = $instance['league_id'];
			$columns = strip_tags($instance['columns']);
			$hide_style = $instance['hide_style'];
			$limit = (int)$instance['limit'];
			$width = strip_tags($instance['width']);
			$highlight = strip_tags($instance['highlight']);
			$team = strip_tags($instance['team']);
			
			if ($type == 'table')
				$columns = " columns='".$columns."'";
			else
				$columns = '';
			
			if ( !empty($width) )
				$width = " width='".$width."'";
			
			if ( !empty($limit) && (int)$limit > 0 )
				$limit = " limit='".$limit."'";
			else
				$limit = '';
			
			if ( !empty($highlight) )
				$highlight = " highlight='".$highlight."'";
			
			if ( !empty($team) )
				$team = " team='".$team."'";
				
			$content = '';
			
			$content .= do_shortcode("[soccer-info widget='1' id='".$league_id."' type='".$type."'".$columns.$limit.$width.$highlight.$team." /]");
			
			if(!empty($content)) {
				if ( !$hide_style ) {
					echo $before_widget;
					if ( !empty( $title ) ) echo $before_title . $title . $after_title;
					echo $content;
					echo $after_widget;
				}
				else echo $content;
			}
        }

        /**
         * Update method
         *
         * @param  mixed $new_instance
         * @param  mixed $old_instance
         * @return void
         */
        public function update($new_instance, $old_instance) {
            $instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['type'] = $new_instance['type'];
			$instance['league_id'] = $new_instance['league_id'];
			$instance['columns'] = strip_tags($new_instance['columns']);
			$instance['hide_style'] = isset($new_instance['hide_style']);
			$instance['limit'] = (int)$new_instance['limit'];
			$instance['width'] = strip_tags($new_instance['width']);
			$instance['highlight'] = strip_tags($new_instance['highlight']);
			$instance['team'] = strip_tags($new_instance['team']);
			
            return $instance;
        }

        /**
         * Form method
         *
         * @param  mixed $instance
         * @return void
         */
        public function form($instance) {
			
			// Get leagues
			$leagues  = $this->getLeagues();
			
			$instance = wp_parse_args((array) $instance, 
				array(
					'title'			 => '',
					'type'			 => 'table',
					'league_id'		 => '',
					'columns'		 => '#,Team,P',
					'limit'			 => '',
					'width'			 => '',
					'highlight'		 => '',
					'team'			 => ''
				)
			);
			$title = strip_tags($instance['title']);
			$type = $instance['type'];
			$league_id = $instance['league_id'];
			$columns = strip_tags($instance['columns']);
			$hide_style = $instance['hide_style'];
			$limit = $instance['limit'];
			$width = strip_tags($instance['width']);
			$highlight = strip_tags($instance['highlight']);
			$team = strip_tags($instance['team']);
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', SOCCER_INFO); ?></label>
				<input class="widefat" 
					id="<?php echo $this->get_field_id('title'); ?>" 
					name="<?php echo $this->get_field_name('title'); ?>" 
					type="text" value="<?php echo esc_attr($title); ?>" />
			</p>
			<p><label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Show:', SOCCER_INFO); ?></label>
				<select class="widefat" 
					id="<?php echo $this->get_field_id('type'); ?>" 
					name="<?php echo $this->get_field_name('type'); ?>" >
				<?php
					$types = array('table'		 => __('Table', SOCCER_INFO), 
								   'fixtures'	 => __('Next Fixtures', SOCCER_INFO), 
								   'results'	 => __('Latest Results', SOCCER_INFO));
					foreach($types as $v => $t) {
						echo '<option value="'.$v.'"'.selected($instance['type'], $v).'>'.esc_html($t).'</option>'."\n";
					}
				?> 
				</select>
			</p>    
			<p><label for="<?php echo $this->get_field_id('league_id'); ?>"><?php _e('Select League:', SOCCER_INFO); ?></label>
				<select class="widefat" 
					id="<?php echo $this->get_field_id('league_id'); ?>" 
					name="<?php echo $this->get_field_name('league_id'); ?>" >
				<?php
					$i = 0;
					foreach($leagues as $league => $ii) {
						if ( $i > 0 )
							echo '<option value="'.$i.'"'.selected($instance['league_id'], $i).'>'.esc_html($league).' (ID = '.$i.')</option>'."\n";
						$i++;
					}
				?> 
				</select>
			</p>
			<p><label for="<?php echo $this->get_field_id('columns'); ?>"><?php _e('Columns (#,Team,MP,W,D,L,F,A,G,P):', SOCCER_INFO); ?></label>
				<input class="widefat" 
					id="<?php echo $this->get_field_id('columns'); ?>" 
					name="<?php echo $this->get_field_name('columns'); ?>" 
					type="text" value="<?php echo esc_attr($columns); ?>" />
			</p>
			<p><label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Limit:', SOCCER_INFO); ?></label>
				<input
					size="5" 
					id="<?php echo $this->get_field_id('limit'); ?>" 
					name="<?php echo $this->get_field_name('limit'); ?>" 
					type="text" value="<?php echo (int)$instance['limit']; ?>" /> &nbsp;
				<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width:', SOCCER_INFO); ?></label>
				<input 
					size="5"
					id="<?php echo $this->get_field_id('width'); ?>" 
					name="<?php echo $this->get_field_name('width'); ?>" 
					type="text" value="<?php echo esc_attr($instance['width']); ?>" />
			</p>
			<p><label for="<?php echo $this->get_field_id('highlight'); ?>"><?php _e('Highlighted team:', SOCCER_INFO); ?></label>
				<input class="widefat" 
					id="<?php echo $this->get_field_id('highlight'); ?>" 
					name="<?php echo $this->get_field_name('highlight'); ?>" 
					type="text" value="<?php echo esc_attr($highlight); ?>" />
			</p>
			<p><label for="<?php echo $this->get_field_id('team'); ?>"><?php _e('Show only this team:', SOCCER_INFO); ?></label>
				<input class="widefat" 
					id="<?php echo $this->get_field_id('team'); ?>" 
					name="<?php echo $this->get_field_name('team'); ?>" 
					type="text" value="<?php echo esc_attr($team); ?>" />
			</p>
			<p>
				<input 
					id="<?php echo $this->get_field_id('hide_style'); ?>" 
					name="<?php echo $this->get_field_name('hide_style'); ?>" 
					type="checkbox" <?php checked($instance['hide_style']); ?> />&nbsp;
				<label for="<?php echo $this->get_field_id('hide_style'); ?>">
				<?php _e('Hide widget style.', SOCCER_INFO); ?>
				</label>
			</p>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					if ( $("#<?php echo $this->get_field_id('type'); ?>").val() != 'table' )
						$("#<?php echo $this->get_field_id('columns'); ?>").parent().hide();
					else
						$("#<?php echo $this->get_field_id('columns'); ?>").parent().show();
						
					$("#<?php echo $this->get_field_id('type'); ?>").change(function(){
						if ( $(this).val() != 'table' )
							$("#<?php echo $this->get_field_id('columns'); ?>").parent().hide();
						else
							$("#<?php echo $this->get_field_id('columns'); ?>").parent().show();
					});
				});
			</script>
			
			<?php
		}
		
		function getLeagues() {
			
			return $this->leagues;
		}
    }
}