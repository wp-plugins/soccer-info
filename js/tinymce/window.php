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
                    <td colspan="3"><input type="text" size="20" value="" name="highlight<?php echo $type; ?>" id="highlight<?php echo $type; ?>" /> <?php _e('Highlighted team', SOCCER_INFO) ?></td>
                </tr>
                <tr>
                    <td><label for="team<?php echo $type; ?>"><?php _e('Only 1 team:', SOCCER_INFO) ?></label></td>
                    <td colspan="3"><input type="text" size="20" value="" name="team<?php echo $type; ?>" id="team<?php echo $type; ?>" /> <?php _e('Show only this team', SOCCER_INFO) ?></td>
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
    <div class="panel_wrapper" style="height:230px;">
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