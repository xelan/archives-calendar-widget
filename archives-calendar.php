<?php
/*
Plugin Name: Archives Calendar Widget
Plugin URI: http://labs.alek.be/
Description: Display archives as a calendar.
Version: 0.4.7
Author: Aleksei Polechin (alek´)
Author URI: http://alek.be
License: GPLv3

/***** LICENSE *****

	Archives Calendar Widget for Wordpress
	Copyright (C) 2013 Aleksei Polechin (http://alek.be)

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
	
****/

require('arw-options.php');

// LOCALISATION
add_action('init', 'archivesCalendar_init');
function archivesCalendar_init()
{
	load_plugin_textdomain('arwloc', false, dirname(plugin_basename(__FILE__)).'/languages');
}

// ACTIVATION
function archivesCalendar_activation($network_wide)
{
	global $wpdb;
	if (isMU())
	{
		// isMU verifyes id the site is in Multisite mode
		// check if it is a network activation - if so, run the activation function for each blog id
		if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1))
		{
			$old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids =  $wpdb->get_results("SELECT blog_id FROM $wpdb->blogs");
			foreach ($blogids as $blogid)
			{
				$blog_id = $blogid->blog_id;
				switch_to_blog($blog_id);
				_archivesCalendar_activate();
			}
			switch_to_blog($old_blog);
			return;
		}   
	} 
	_archivesCalendar_activate();
}
function _archivesCalendar_activate()
{
	global $wpdb;	
	if(!get_option( 'archivesCalendar' ))
		$options = array(
			"css" => 1,
			"theme" => "default",
			"jquery" => 1,
			"js" => 1,
			"show_settings" => 1,
			"shortcode" => 0,
			"javascript" => "jQuery(document).ready(function($){\n\t$('.calendar-archives').archivesCW();\n});"
		);
	else
		$options = get_option( 'archivesCalendar' );
		
	if(isMU())
	{
		update_blog_option($wpdb -> blogid, 'archivesCalendar', $options);
		add_blog_option($wpdb -> blogid, 'archivesCalendar', $options);
	}
	else
	{
		update_option('archivesCalendar', $options);
		add_option('archivesCalendar', $options);
	}
}
register_activation_hook(__FILE__, 'archivesCalendar_activation');

function archivesCalendar_new_blog($blog_id)
{
	global $wpdb;
	if (is_plugin_active_for_network('archives-calendar/archives-calendar.php'))
	{
		$old_blog = $wpdb->blogid;
		switch_to_blog($blog_id);
		_archivesCalendar_activate();
		switch_to_blog($old_blog);
	}
}
add_action( 'wpmu_new_blog', 'archivesCalendar_new_blog', 10, 6); // in case of creation of a new site in WPMU


// UNINSTALL 
function archivesCalendar_uninstall()
{
	global $wpdb;
	if (isMU())
	{
		$old_blog = $wpdb->blogid;
		$blogids =  $wpdb->get_results("SELECT blog_id FROM $wpdb->blogs");
		foreach ($blogids as $blogid)
		{
			$blog_id = $blogid->blog_id;
			switch_to_blog($blog_id);
			_archivesCalendar_uninstall();
		}
		switch_to_blog($old_blog);
		return;
	} 
	_archivesCalendar_uninstall();
}
function _archivesCalendar_uninstall()
{
	global $wpdb;
	if (isMU())
		delete_blog_option($wpdb->blogid, 'archivesCalendar');
	else
		delete_option('archivesCalendar');
}
register_uninstall_hook(__FILE__, 'archivesCalendar_uninstall');

// ADD Settings link in Plugins page when the plugin is activated
if(!function_exists('plugin_settings_link'))
{
	function plugin_settings_link($links)
	{
		$settings_link = '<a href="options-general.php?page=archives_calendar">'.__( 'Settings' ).'</a>'; 
		array_unshift($links, $settings_link); 
		return $links; 
	}
}
$acplugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$acplugin", 'plugin_settings_link' );


$archivesCalendar_options = get_option('archivesCalendar');

function archivesCalendar_jquery_plugin()
{
	global $archivesCalendar_options;
	
	if($archivesCalendar_options['jquery'] == 1)
		wp_register_script( 'archivesCW', plugins_url(). '/archives-calendar-widget/jquery.archivesCW.min.js', array('jquery') );
	else
		wp_register_script( 'archivesCW', plugins_url(). '/archives-calendar-widget/jquery.archivesCW.min.js' );
	
	wp_enqueue_script( 'archivesCW');
}

function archivesCalendar_js()
{
	global $archivesCalendar_options;
	echo '<script type="text/javascript">'."\n\t".$archivesCalendar_options['javascript']."\n".'</script>';
}

function archives_calendar_styles()
{
	$archivesCalendar_options = get_option('archivesCalendar');
	wp_register_style( 'archives-cal-css', plugins_url('themes/'.$archivesCalendar_options['theme'].'.css', __FILE__));
	wp_enqueue_style('archives-cal-css');
}

if($archivesCalendar_options['css'] == 1)
	add_action('wp_enqueue_scripts', 'archives_calendar_styles');
if($archivesCalendar_options['js'] == 1)
	add_action('wp_head', 'archivesCalendar_js');
// in all cases the jQuery plugin must be included
add_action( 'wp_enqueue_scripts', 'archivesCalendar_jquery_plugin' );



/***** WIDGET CLASS *****/
class Archives_Calendar extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
	 		'archives_calendar',
			'Archives Calendar',
			array( 'description' => __( 'Show archives as calendar', 'arwloc' ), )
		);
	}
	public function widget( $args, $instance )
	{
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		$instance['function'] = 'no';
		echo archive_calendar($instance);
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['prev_text'] = $new_instance['prev_text'];
		$instance['next_text'] = $new_instance['next_text'];
		$instance['post_count'] = ($new_instance['post_count']) ? $new_instance['post_count'] : 0;
		$instance['month_view'] = $new_instance['month_view'];
		$instance['categories'] = $new_instance['categories'];
		$instance['post_type'] = $new_instance['post_type'];
		return $instance;
	}

	public function form( $instance )
	{
		wp_enqueue_script( 'accordion' );
		wp_enqueue_style( 'media-views' );
		$defaults = array(
			'title' > __( 'Archives' ),
			'next_text' => '>',
			'prev_text' => '<',
			'post_count' => 1,
			'month_view' => 0,
			'post_type' => null,
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$prev = $instance['prev_text'];
		$next = $instance['next_text'];
		$count = $instance['post_count'];
		$month_view = $instance['month_view'];
		$cats = $instance['categories'];
		$post_type = $instance['post_type'];

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<div style="float: left; width: 49%">
				<label for="<?php echo $this->get_field_id( 'prev_text' ); ?>"><?php _e( 'Previous'); ?>:</label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'prev_text' ); ?>" name="<?php echo $this->get_field_name( 'prev_text' ); ?>" type="text" value="<?php echo esc_attr( $prev ); ?>" />
			</div>
			<div style="float: right; width: 49%">
				<label for="<?php echo $this->get_field_id( 'next_text' ); ?>"><?php _e( 'Next' ); ?>:</label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'next_text' ); ?>" name="<?php echo $this->get_field_name( 'next_text' ); ?>" type="text" value="<?php echo esc_attr( $next ); ?>" />
			</div>
			<div class="clear"></div>
		</p>
		<p>
			<input class="selectit" <?php if($count) echo "checked";?> id="<?php echo $this->get_field_id( 'post_count' ); ?>" name="<?php echo $this->get_field_name( 'post_count' ); ?>" type="checkbox" value="1" />
			<label for="<?php echo $this->get_field_id( 'post_count' ); ?>"><?php _e( 'Show number of posts', 'arwloc' ); ?></label> 
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'month_view' ); ?>"><?php _e( 'Show' ).': '; ?></label> 
			<select name="<?php echo $this->get_field_name( 'month_view' ); ?>" >
				<option <?php selected( 1, $month_view ); ?> value="1">
					<?php _e( 'Months' ); ?>
				</option>
				<option <?php selected( 0, $month_view ); ?> value="0">
					<?php _e( 'Years' ); ?>
				</option>
			</select>
		</p>
		<p>
			<div class="accordion-container" style="border: 1px solid #ddd; overflow: hidden" tabindex="-1">
				<?php
					$elemid = $this->get_field_id( 'cats' );
				?>
				<script>
				jQuery(function($){

					$('#<?php echo $elemid; ?> input[type=checkbox].all').on('change', function(){
						$(this).attr('checked', true);
						$("#<?php echo $elemid; ?> input[type=checkbox]:not(.all)").attr('checked', false);
					});

					$('#<?php echo $elemid; ?> input[type=checkbox]:not(.all)').on('change', function(){
						$("#<?php echo $elemid; ?> input[type=checkbox].all").attr('checked', false);
					});
				})
				</script>
				<div class="control-section accordion-section acw-cats">
					<div class="accordion-section-title" tabindex="0">
						<strong><?php _e('Categories');?></strong>
					</div>
					<div class="accordion-section-content" id="<?php echo $elemid; ?>" style="background-color: #FDFDFD">
						<label class="selectit"><input class="all" type="checkbox" id="acw_all_cats" <?php if(!count($cats)) echo 'checked';?> > <?php _e('All'); ?></label>
						<hr>
						<ul id="categorychecklist" class="categorychecklist form-no-clear">
						<?php 
							$acw_walker = new acw_Walker_Category_Checklist( array('field_id' => $this->get_field_id( 'categories' ), 'field_name' => $this->get_field_name( 'categories' ) ) );
							$args = array(
								'descendants_and_self'  => 0,
								'selected_cats'         => $cats,
								'popular_cats'          => false,
								'walker'                => $acw_walker,
								'taxonomy'              => 'category',
								'checked_ontop'         => true,
							);
							wp_category_checklist( 0, 0, $cats, false, $acw_walker, false);
						?>
						</ul>
					</div>
				</div>
				<div class="control-section accordion-section acw-post_type">
					<div class="accordion-section-title" tabindex="0">
						<strong>Post type</strong>
					</div>
					<div class="accordion-section-content" id="<?php echo $this->get_field_id( 'post_type' );; ?>">
						<p>
							<label>post_type:</label>
							<input class="widefat" id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" type="text" value="<?php echo esc_attr( $post_type ); ?>" />
							<p class="description" style="font-size: 12px;">Enter post_type separated by a comma ",". If not empty, only the specified types will be shown, don't forget to enter "post" if needed.<br>Example: post, event, film</p>
						<p>
					</div>
				</div>
			</div>
		</p>
		<?php 
	}
}
add_action( 'widgets_init', create_function( '', 'register_widget( "Archives_Calendar" );' ) );

/***** WIDGET CONSTRUCTION FUNCTION *****/
/* can be called directly archive_calendar($args) */
function archive_calendar($args = array())
{
	global $wpdb;

	$defaults = array(
		'next_text' => '>',
		'prev_text' => '<',
		'post_count' => true,
		'month_view' => false,
		'categories' => null,
		'post_type' => null
	);
	$args = wp_parse_args( (array) $args, $defaults );

	if($args['month_view'] == false)
		$cal = archives_year_view($args);
	else
		$cal = archives_month_view($args);

	if($function == "no")
		return $cal;
	echo $cal;
}

function acw_post_types($post_types){
	if($post_types == '')
		return "'post'";

	$post_types = str_replace(' ', '', $post_types);
	$post_types = explode(',', $post_types);
	$types = "";
	for($i = 0; $i < count($post_types); $i++)
	{
		$types .= "'".$post_types[$i]."'";
		$types .= ($i < count($post_types) -1) ? ", " : "";
	}
	return $types;
}

/* displays years */
function archives_year_view($args)
{
	global $wpdb, $wp_locale, $post;
	extract($args);

	$post_type = acw_post_types($post_type);

	$mnames[0] = '';

	for($i=1; $i<13; $i++)
	{
		$monthdate = '1970-'. sprintf('%02d', $i) .'-01';		
		$mnames[$i] = $wp_locale->get_month_abbrev( $wp_locale->get_month(intval($i)) );
	}

	$cats = "";
	if($categories)
		for($i=0; $i < count($categories); $i++)
			$cats .= ($i < count($categories)-1) ? $categories[$i].', ' : $categories[$i];

	$sql = "SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month
		FROM $wpdb->posts wpposts ";

	if(count($categories))
	{
		$sql .= "JOIN $wpdb->term_relationships tr ON wpposts.ID = tr.object_id 
				JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id ";
		$sql .= "AND tt.term_id IN(".$cats.") ";
		$sql .= "AND tt.taxonomy = 'category')"." ";
	}

	$sql .= "WHERE post_type IN ($post_type)
			AND post_status IN ('publish')
			AND post_password=''
			ORDER BY year DESC, month DESC";

	$results = $wpdb->get_results($sql);

	$years = array();
	foreach ($results as $date)
	{
		if($post_count)
		{			
			$sql = "SELECT COUNT(ID) AS count
					FROM $wpdb->posts wpposts ";

			if(count($categories))
			{
			$sql .= "JOIN $wpdb->term_relationships tr 
					ON ( wpposts.ID = tr.object_id )
					JOIN $wpdb->term_taxonomy tt ON 
					( tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.term_taxonomy_id IN($cats) ) ";
			}
			$sql .= "WHERE post_type IN ($post_type)
					AND post_status IN ('publish')
					AND post_password=''
					AND YEAR(post_date) = $date->year
					AND MONTH(post_date) = $date->month";

			$postcount = $wpdb->get_results($sql);
			$count = $postcount[0]->count;
		}
		else 
			$count = 0;
		$years[$date->year][$date->month] = $count;
	}

	$totalyears = count($years);

	if($totalyears==0)
	{
		$totalyears = 1;
		$years[date('Y')] = array();
	}

	$yearNb = array();
	foreach ($years as $year => $months)
		$yearNb[] = $year;
	
	if(is_archive())
	{
		$archiveYear = date('Y', strtotime($post->post_date)); // year to be visible
		
		if( !array_key_exists ( $archiveYear , $years ) )
			$archiveYear = $yearNb[0];
	}
	else
		$archiveYear = $yearNb[0]; // if no current year -> show the more recent

	$nextyear = ($totalyears > 1) ? '<a href="#" class="next-year"><span>'.$next_text.'</span></a>' : '';
	$prevyear = ($totalyears > 1) ? '<a href="#" class="prev-year"><span>'.$prev_text.'</span></a>' : '';
	
	$cal = "\n<!-- Archives Calendar Widget by Aleksei Polechin - alek´ - http://alek.be -->\n";
	$cal.= '<div class="calendar-archives" id="arc-'.$title.'-'.mt_rand(10,100).'">';
	$cal.= '<div class="cal-nav">'.$prevyear.'<div class="year-nav">';
		$cal .=  '<a href="'.get_year_link($archiveYear).'" class="year-title">'.$archiveYear.'</a>';
		$cal .= '<div class="year-select">';
		$i=0;
		foreach( $yearNb as $year )
		{
			$current = ($archiveYear == $year) ? " current" : "";
			$cal.= '<a href="'.get_year_link($year).'" class="year '.$year.$current.'" rel="'.$i.'" >'.$year.'</a>';
			$i++;
		}
		$cal.= '</div>';
		if ($totalyears > 1)
			$cal.= '<div class="arrow-down" title="'.__("Select archives year", "arwloc").'"><span>&#x25bc;</span></div>';
	$cal.= '</div>'.$nextyear.'</div>';
	$cal.= '<div class="archives-years">';

	$i=0;

	foreach ($years as $year => $months)
	{
		$lastyear = ($i == $totalyears-1 ) ? " last" : "";
		$current = ($archiveYear == $year) ? " current" : "";

		$cal .= '<div class="year '.$year.$lastyear.$current.'" rel="'.$i.'">';
		for ( $month = 1; $month <= 12; $month++ )
		{
			$last = ( $month%4 == 0 ) ? ' last' : '';
			if($post_count)
			{
				if(isset($months[$month])) $count = $months[$month];
				else $count = 0;
				$posts_text = ($count == 1) ? __('Post', 'arwloc') : __('Posts', 'arwloc');

				$postcount = '<span class="postcount"><span class="count-number">'.$count.'</span> <span class="count-text">'.$posts_text.'</span></span>';
			}
			else
				$postcount = "";
			if(isset($months[$month]))
				$cal .= '<div class="month'.$last.'"><a href="'.get_month_link($year, $month).'"><span class="month-name">'.$mnames[$month].'</span>'.$postcount.'</a></div>';
			else
				$cal .= '<div class="month'.$last.' empty"><span class="month-name">'.$mnames[$month].'</span>'.$postcount.'</div>';
		}
		$cal .= "</div>\n";
		$i++;
	}
	$cal .= "</div></div>";

	return $cal;
}

/* displays months */
function archives_month_view($args)
{
	global $wpdb, $wp_locale, $post;
	extract($args);

	$post_type = acw_post_types($post_type);

	$cats = "";
	if($categories)
		for($i=0; $i < count($categories); $i++)
			$cats .= ($i < count($categories)-1) ? $categories[$i].', ' : $categories[$i];


	$sql = "SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month
		FROM $wpdb->posts wpposts ";

	if(count($categories))
	{
		$sql .= "JOIN $wpdb->term_relationships tr ON wpposts.ID = tr.object_id 
				JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id ";
		$sql .= "AND tt.term_id IN(".$cats.") ";
		$sql .= "AND tt.taxonomy = 'category')"." ";
	}

	$sql .= "WHERE post_type IN ($post_type)
			AND post_status IN ('publish')
			AND post_password=''
			ORDER BY year DESC, month DESC";



	// Select all months where are posts
	$months = $wpdb->get_results($sql);
	$totalmonths = count($months);

	if(is_archive())
	{

		$archiveYear = date('Y', strtotime($post->post_date));// year to be visible
		$archiveMonth = date('m', strtotime($post->post_date)); // year to be visible

		$found = 0;
		foreach($months as $struct)
		{
			if ($struct->year < $archiveYear);
			else if($struct->year == $archiveYear)
			{
				if($struct->month == $archiveMonth)
					$found = 1;
			}
		}
		if(!$found)
		{
			$archiveYear = $months[0]->year;
			$archiveMonth = $months[0]->month;
		}
	}
	else
	{
		$archiveYear = $months[0]->year; // if no current year -> show the more recent
		$archiveMonth = $months[0]->month; // year to be visible
	}

	if(!$totalmonths)
	{
		$totalmonths = 1;
		$months[0] = new StdClass();
		$months[0]->year = (is_archive()) ? $archiveYear : date('Y');
		$months[0]->month =  (is_archive()) ? $archiveMonth : date('m');
	}

	$nextmonth = ($totalmonths > 1) ? '<a href="#" class="next-year"><span>'.$next_text.'</span></a>' : '';
	$prevmonth = ($totalmonths > 1) ? '<a href="#" class="prev-year"><span>'.$prev_text.'</span></a>' : '';
	
	$cal = "\n<!-- Archives Calendar Widget by Aleksei Polechin - alek´ - http://alek.be -->\n";
	$cal.= '<div class="calendar-archives" id="arc-'.$title.'-'.mt_rand(10,100).'">';
	$cal.= '<div class="cal-nav months">'.$prevmonth.'<div class="year-nav months">';
		$cal .=  '<a href="'.get_month_link( intval($archiveYear), intval($archiveMonth) ).'" class="year-title">'.$wp_locale->get_month(intval($archiveMonth)).' '.$archiveYear.'</a>';
		$cal .= '<div class="year-select">';
		$i=0;
		foreach( $months as $month )
		{
			$current = ($archiveYear == $month->year && $archiveMonth == $month->month) ? " current" : "";
			$cal.= '<a href="'.get_month_link( intval($month->year), intval($month->month) ).'" class="year '.$month->year.' '.$month->month.$current.'" rel="'.$i.'" >'.$wp_locale->get_month(intval($month->month)).' '.$month->year.'</a>';
			$i++;
		}
		$cal.= '</div>';
		if ($totalmonths > 1) $cal.= '<div class="arrow-down" title="'.__("Select archives year", "arwloc").'"><span>&#x25bc;</span></div>';
	$cal.= '</div>'.$nextmonth.'</div>';

	// Display week days names
	$week_begins = intval(get_option('start_of_week'));
	for ($wdcount=0; $wdcount<=6; $wdcount++ )
	{
		$myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
	}
	$i=1;
	$cal .= '<div class="week-row">';
	foreach ( $myweek as $wd )
	{
		$day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
		$wd = esc_attr($wd);
		$last = ($i%7 == 0) ? " last" : "";
		$cal .= '<span class="day weekday'.$last.'">'.$day_name.'</span>';
		$i++;
	}

	$cal.= '</div><div class="archives-years">';

	// for each month
	for($i = 0; $i < $totalmonths; $i++)
	{
		$lastyear = ($i == $totalmonths-1 ) ? " last" : "";
		$current = ($archiveYear == $months[$i]->year && $archiveMonth == $months[$i]->month) ? " current" : "";

		// select days with posts
		$sql = "SELECT DAY(post_date) AS day
			FROM $wpdb->posts wpposts ";
		if(count($categories))
		{
			$sql .= "JOIN $wpdb->term_relationships tr ON wpposts.ID = tr.object_id 
					JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id ";
			$sql .= "AND tt.term_id IN(".$cats.") ";
			$sql .= "AND tt.taxonomy = 'category')"." ";
		}
		$sql .= "WHERE post_type IN ($post_type)
				AND post_status IN ('publish') 
				AND YEAR(post_date) = ".$months[$i]->year."
				AND MONTH(post_date) = ".$months[$i]->month."
				AND post_password=''
				GROUP BY day";

		$days = $wpdb->get_results( $sql, ARRAY_N);

		$dayswithposts = array();
		for($j = 0; $j< count($days); $j++)
		{
			$dayswithposts[] = $days[$j][0];
		}

		$cal .= '<div class="year '.$months[$i]->month.' '.$months[$i]->year.$lastyear.$current.'" rel="'.$i.'">';
		// 1st of month date
		$firstofmonth = $months[$i]->year.'-'. intval($months[$i]->month) .'-01';
		// first weekday of the month
		$firstweekday = date('w', strtotime("$firstofmonth"));

		$cal .= '<div class="week-row">';

		$k = 0; // total grid days counter
		$j = $week_begins;

		while( $j != $firstweekday )
		{
			$k++;
			$last = ($k%7 == 0) ? " last" : "";
			$cal .= '<span class="day noday'.$last.'">&nbsp;</span>';
			$j++;
			if($j == 7)
				$j = 0;
		}

		$monthdays = month_days($months[$i]->year, $months[$i]->month);

		for($j = 1; $j <= $monthdays; $j++)
		{
			$k++;
			$last = ($k%7 == 0) ? " last" : "";

			if(in_array ( $j , $dayswithposts ) )
				$cal .= '<span class="month day'.$last.'"><a href="'.get_day_link( $months[$i]->year, $months[$i]->month, $j ).'">'.$j.'</a></span>';
			else
				$cal .= '<span class="month day'.$last.' empty">'.$j.'</span>';

			if($k%7 == 0)
				$cal .= "</div>\n<div class=\"week-row\">\n";
		}

		while( $k < 42)
		{
			$k++;
			$last = ($k%7 == 0) ? " last" : "";
			$cal .= '<span class="day noday'.$last.'">&nbsp;</span>';	
			if($k%7 == 0)
				$cal .= "</div>\n<div class=\"week-row\">\n";		
		}
			$cal .= "</div>\n";
		$cal .= "</div>\n";
	}

	$cal .= "</div></div>";	

	return $cal;
}


/***** SHORTCODE *****/
if($archivesCalendar_options['shortcode'])
{
	add_filter( 'widget_text', 'shortcode_unautop');
	add_filter('widget_text', 'do_shortcode');
}

function archivesCalendar_shortcode( $atts )
{
	extract( shortcode_atts( array(
		'next_text' => '>',
		'prev_text' => '<',
		'post_count' => true,
		'month_view' => false
	), $atts ) );

	$post_count = ($post_count == "true") ? true : false;
	$month_view = ($month_view == "true") ? true : false;
	$defaults = array(
		'next_text' => $next_text,
		'prev_text' => $prev_text,
		'post_count' => $post_count,
		'month_view' => $month_view,
		'function' => 'no',
	);
	return archive_calendar($defaults);
}
add_shortcode( 'arcalendar', 'archivesCalendar_shortcode' );

/***** Days in months *****/
function month_days($year, $month)
{
    switch(intval($month))
	{
		case 4: case 6: case 9: case 11: //april, june, september, november
			return 30; // have 30 days
		case 2: //february
			if( $year%400==0 || ( $year%100 != 00 && $year%4==0 ) ) // intercalary year check
				return 29; // have 29 days or
		return 28; // 28 days

		default:// other months
			return 31; // have 31 days
	}
}

function ac_checked($option, $value = 1)
{
	$options = get_option('archivesCalendar');
	if($options[$option] == $value)
		echo 'checked="checked"';
}

/***** Function to check if multisite *****/
if (!function_exists('isMU'))
{
	function isMU()
	{
		if (function_exists('is_multisite') && is_multisite())
			return true;
		return false;
	}
}


/***** Walker for categories checkboxes *****/
class acw_Walker_Category_Checklist extends Walker
{
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

	var $conf;
   	function __construct($conf)
   	{
        $this->conf = $conf;
    }
	
	function start_lvl( &$output, $depth = 0, $args = array() )
	{
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children' style='margin-left: 18px;'>\n";
	}

	function end_lvl( &$output, $depth = 0, $args = array() )
	{
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 )
	{
		extract($args);
		$conf = $this->conf;

		if ( empty($taxonomy) )
			$taxonomy = 'category';

		if ( $taxonomy == 'category' )
			$name = 'post_category';
		else
			$name = 'tax_input['.$taxonomy.']';

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';

		/** This filter is documented in wp-includes/category-template.php */
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" 
		type="checkbox" 
		id="'.$conf['field_id'].'-'.$cat->slug.'" 
		name="'.$conf['field_name'].'['.$cat->term_id.']"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters( 'the_category', $category->name ) ) . '</label>';
	}

	function end_el( &$output, $category, $depth = 0, $args = array() )
	{
		$output .= "</li>\n";
	}
}