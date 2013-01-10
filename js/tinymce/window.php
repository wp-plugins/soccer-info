<?php

/*
 * This file is part of the SoccerInfo package.
 *
 * (c) Szilard Mihaly <office@mihalysoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$root = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));

if (file_exists($root.'/wp-load.php'))
    require_once($root.'/wp-load.php');
else
    die();

require_once ABSPATH.'/wp-admin/admin.php';

// check for rights
//if ( ! current_user_can('soccer_info')) die();

// Database stuffs
global $wpdb;

// Get leagues
$leagues  = $soccer_info->competitions;

function show_si_options( $type ) {
	$type = '_'.$type;
	?>
				
                <tr>
                    <td><label for="style<?php echo $type; ?>"><?php _e('Style:', SOCCER_INFO) ?></label></td>
                    <td colspan="3">
						<select class="widefat" 
							id="style<?php echo $type; ?>" 
							name="style<?php echo $type; ?>" style="width: 117px">
						<?php
							$styles = array('general'		 => __('Minimal', SOCCER_INFO),
											'blue_light'	 => __('Blue - Light', SOCCER_INFO),
											'blue_dark'		 => __('Blue - Dark', SOCCER_INFO),
								    		'green_light'	 => __('Green - Light', SOCCER_INFO),
								    		'green_dark'	 => __('Green - Dark', SOCCER_INFO),
										    'red_light'		 => __('Red - Light', SOCCER_INFO),
										    'red_dark'		 => __('Red - Dark', SOCCER_INFO));
							foreach($styles as $v => $t) {
								echo '<option value="'.$v.'">'.esc_html($t).'</option>'."\n";
							}
						?> 
						</select>
						<?php _e('Choose a style', SOCCER_INFO) ?>
					</td>
                </tr>
                <tr>
                    <td><label for="limit<?php echo $type; ?>"><?php _e('Limit:', SOCCER_INFO) ?></label></td>
                    <td><input type="text" size="5" value="" name="limit<?php echo $type; ?>" id="limit<?php echo $type; ?>" /></td>
                    <td><label for="width<?php echo $type; ?>"><?php _e('Width:', SOCCER_INFO) ?></label></td>
                    <td><input type="text" size="5" value="" name="width<?php echo $type; ?>" id="width<?php echo $type; ?>" /></td>
                </tr>
                <tr>
                    <td><label for="title<?php echo $type; ?>"><?php _e('Title:', SOCCER_INFO) ?></label></td>
                    <td colspan="3"><input type="text" size="20" value="" name="title<?php echo $type; ?>" id="title<?php echo $type; ?>" /> <?php _e('It shows before the table', SOCCER_INFO) ?></td>
                </tr>
                <tr>
                    <td><label for="highlight<?php echo $type; ?>"><?php _e('Highlight:', SOCCER_INFO) ?></label></td>
                    <td colspan="3">
					<select class="widefat" 
						id="highlight<?php echo $type; ?>" 
						name="highlight<?php echo $type; ?>" style="width: 200px">
					</select> 
					<?php _e('Highlighted team', SOCCER_INFO) ?>
					 <img id='img_highlight<?php echo $type; ?>' class='ajax-loading' src='<?php echo admin_url();?>images/wpspin_light.gif' />
					</td>
                </tr>
                <tr>
                    <td><label for="team<?php echo $type; ?>"><?php _e('Only 1 team:', SOCCER_INFO) ?></label></td>
                    <td colspan="3">
					<select class="widefat" 
						id="team<?php echo $type; ?>" 
						name="team<?php echo $type; ?>" style="width: 200px">
					</select> 
					<?php _e('Show only this team', SOCCER_INFO) ?>
					 <img id='img_team<?php echo $type; ?>' class='ajax-loading' src='<?php echo admin_url();?>images/wpspin_light.gif' />
					</td>
                </tr>
	
	<?php
}

// Get the website url
$site_url = get_option('siteurl');
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php _e('Soccer Info', SOCCER_INFO) ?></title>
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
    <script language="javascript" type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $site_url; ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $site_url; ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo $site_url; ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
    <script language="javascript" type="text/javascript" src="<?php echo plugins_url( SOCCER_INFO_BASEPATH.'/js/tinymce/tinymce.js'); ?>"></script>
    <base target="_self" />
</head>
<body id="link" onLoad="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';document.getElementById('league_id_tables').focus();" style="display: none;">
<form name="SoccerInfo" action="#">
    <div class="tabs">
        <ul>
            <li id="tables_tab" class="current"><span><a href="javascript:mcTabs.displayTab('tables_tab', 'tables_panel');" onMouseOver="return false;"><?php _e('Tables', SOCCER_INFO); ?></a></span></li>
            <li id="fixtures_tab"><span><a href="javascript:mcTabs.displayTab('fixtures_tab', 'fixtures_panel');" onMouseOver="return false;"><?php _e('Fixtures', SOCCER_INFO); ?></a></span></li>
            <li id="results_tab"><span><a href="javascript:mcTabs.displayTab('results_tab', 'results_panel');" onMouseOver="return false;"><?php _e('Results', SOCCER_INFO); ?></a></span></li>
        </ul>
    </div>
    <div class="panel_wrapper" style="height:260px;">
        <!-- tables panel -->
		<?php $type_most = 'tables'; ?>
        <div id="tables_panel" class="panel current"><br />
            <table style="border: 0;" cellpadding="5">
                <tr>
                    <td><label for="league_id<?php echo '_'.$type_most;?>"><?php _e('League:', SOCCER_INFO); ?></label></td>
                    <td colspan="3">
                        <select id="league_id<?php echo '_'.$type_most;?>" name="league_id<?php echo '_'.$type_most;?>" style="width: 240px">
                        <?php
                        if ( $leagues ) {
							$i = 0;
                            foreach($leagues as $league => $ii) {
								if ( $i > 0 )
                                	echo '<option value="'.$i.'" >'.esc_html($league).' (ID = '.$i.')</option>'."\n";
								$i++;
                            }
                        }
                        ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="columns"><?php _e('Columns:', SOCCER_INFO) ?></label></td>
                    <td colspan="3"><input type="text" size="20" value="" name="columns" id="columns" /> <?php _e('You may use: #,Team,MP,W,D,L,F,A,G,P', SOCCER_INFO);?></td>
                </tr>
				
				<?php show_si_options( $type_most ); ?>
				
            </table>
					
	<script type='text/javascript'>
		function get_soccer_info_teams_go(league_id, select_id, team_id, copy_to_id){
			$('#img_'+select_id.substring(1)).show();
			$('#img_'+select_id.substring(1)).css('visibility', 'visible');
			$('#img_'+copy_to_id.substring(1)).show();
			$('#img_'+copy_to_id.substring(1)).css('visibility', 'visible');
			
			this.get_soccer_info_teams_ajax_query = $.ajax({
                url: '<?php echo admin_url('admin-ajax.php');?>',
				data: { 'league_id': league_id, 'new_id': '1', 'team_id': team_id, 'action': 'get_soccer_info_teams' },
				cache: false,
				dataType: 'json',
                success: function(data) {
					if(data.teams == null){
						// no teams found
					}
					else {
						// user found
						$(select_id).html(data.teams);
						$(copy_to_id).html(data.teams);
					}
					
					$('#img_'+select_id.substring(1)).hide();
					$('#img_'+select_id.substring(1)).css('visibility', 'hidden');
					$('#img_'+copy_to_id.substring(1)).hide();
					$('#img_'+copy_to_id.substring(1)).css('visibility', 'hidden');
				}
            });
		}
		
		$(document).ready(function(){
			get_soccer_info_teams_go( $('#league_id<?php echo '_'.$type_most;?>').val(), '#team<?php echo '_'.$type_most;?>', '', '#highlight<?php echo '_'.$type_most;?>' );
			
		});
		$('#league_id<?php echo '_'.$type_most;?>').change(function(){
			get_soccer_info_teams_go( $('#league_id<?php echo '_'.$type_most;?>').val(), '#team<?php echo '_'.$type_most;?>', '', '#highlight<?php echo '_'.$type_most;?>' );
		});

	</script>
	
            <p><?php _e('Display the table for the chosen league.', SOCCER_INFO); ?></p>
        </div>
        <!-- fixtures panel -->
		<?php $type_most = 'fixtures'; ?>
        <div id="fixtures_panel" class="panel"><br />
            <table style="border: 0;" cellpadding="5">
                <tr>
                    <td><label for="league_id<?php echo '_'.$type_most;?>"><?php _e('League:', SOCCER_INFO); ?></label></td>
                    <td colspan="3">
                        <select id="league_id<?php echo '_'.$type_most;?>" name="league_id<?php echo '_'.$type_most;?>" style="width: 240px;">
                        <?php
                        if ( $leagues ) {
							$i = 0;
                            foreach($leagues as $league => $ii) {
								if ( $i > 0 )
                                	echo '<option value="'.$i.'" >'.esc_html($league).' (ID = '.$i.')</option>'."\n";
								$i++;
                            }
                        }
                        ?>
                        </select>
                    </td>
                </tr>
				
				<?php show_si_options( $type_most ); ?>
				
            </table>
					
	<script type='text/javascript'>
		
		$(document).ready(function(){
			get_soccer_info_teams_go( $('#league_id<?php echo '_'.$type_most;?>').val(), '#team<?php echo '_'.$type_most;?>', '', '#highlight<?php echo '_'.$type_most;?>' );
			
		});
		$('#league_id<?php echo '_'.$type_most;?>').change(function(){
			get_soccer_info_teams_go( $('#league_id<?php echo '_'.$type_most;?>').val(), '#team<?php echo '_'.$type_most;?>', '', '#highlight<?php echo '_'.$type_most;?>' );
		});

	</script>
	
            <p><?php _e('Display all fixtures for the chosen league or only those for a selected team.', SOCCER_INFO); ?></p>
        </div>
        <!-- results panel -->
		<?php $type_most = 'results'; ?>
        <div id="results_panel" class="panel"><br />
            <table style="border: 0;" cellpadding="5">
                <tr>
                    <td><label for="league_id<?php echo '_'.$type_most;?>"><?php _e('League:', SOCCER_INFO); ?></label></td>
                    <td colspan="3">
                        <select id="league_id<?php echo '_'.$type_most;?>" name="league_id<?php echo '_'.$type_most;?>" style="width: 240px;">
                        <?php
                        if ( $leagues ) {
							$i = 0;
                            foreach($leagues as $league => $ii) {
								if ( $i > 0 )
                                	echo '<option value="'.$i.'" >'.esc_html($league).' (ID = '.$i.')</option>'."\n";
								$i++;
                            }
                        }
                        ?>
                        </select>
                    </td>
                </tr>
				
				<?php show_si_options( $type_most ); ?>
				
            </table>
					
	<script type='text/javascript'>
		
		$(document).ready(function(){
			get_soccer_info_teams_go( $('#league_id<?php echo '_'.$type_most;?>').val(), '#team<?php echo '_'.$type_most;?>', '', '#highlight<?php echo '_'.$type_most;?>' );
			
		});
		$('#league_id<?php echo '_'.$type_most;?>').change(function(){
			get_soccer_info_teams_go( $('#league_id<?php echo '_'.$type_most;?>').val(), '#team<?php echo '_'.$type_most;?>', '', '#highlight<?php echo '_'.$type_most;?>' );
		});

	</script>
	
            <p><?php _e('Display the last results for the chosen league or only those for a selected team.', SOCCER_INFO); ?></p>
        </div>
    </div>
    <div class="mceActionPanel">
        <div style="float: left">
            <input type="button" id="cancel" name="cancel" value="<?php _e('Cancel', SOCCER_INFO); ?>" onClick="tinyMCEPopup.close();" />
        </div>
        <div style="float: right">
            <input type="submit" id="insert" name="insert" value="<?php _e('Insert', SOCCER_INFO); ?>" onClick="insertSoccerInfo();" />
        </div>
    </div>
</form>
</body>
</html>