<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2016, Phoronix Media
	Copyright (C) 2013 - 2016, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if(!is_file('phoromatic-export-viewer-config.php'))
{
	echo '<p>You must first configure the <em>phoromatic-export-viewer-config.php.config</em> file and rename it to <em>phoromatic-export-viewer-config.php</em> within this directory.</p>';
	return;
}
require('phoromatic-export-viewer-config.php');

if(!is_file(PATH_TO_PHORONIX_TEST_SUITE . 'pts-core/pts-core.php'))
{
	echo '<p>You must first set the <em>PATH_TO_PHORONIX_TEST_SUITE</em> define within the <em>phoromatic-export-viewer-config.php</em> file.</p>';
	return;
}

if(!is_file(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-index.json'))
{
	echo '<p>You must first set the <em>PATH_TO_EXPORTED_PHOROMATIC_DATA</em> define within the <em>phoromatic-export-viewer-config.php</em> file. No <em>export-index.json</em> found.</p>';
	return;
}


define('PHOROMATIC_EXPORT_VIEWER', true);
define('PTS_MODE', 'LIB');
define('PTS_AUTO_LOAD_OBJECTS', true);
require(PATH_TO_PHORONIX_TEST_SUITE . 'pts-core/pts-core.php');
pts_define_directories();

set_time_limit(0);
ini_set('memory_limit','2048M');
error_reporting(E_ALL);

$export_index_json = file_get_contents(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-index.json');
$export_index_json = json_decode($export_index_json, true);

if(!isset($export_index_json['phoromatic']) || empty($export_index_json['phoromatic']))
{
	echo '<p>Error decoding the Phoromatic export JSON file.</p>';
	return;
}

if(strpos($_SERVER['REQUEST_URI'], '?') === false && isset($_SERVER['QUERY_STRING']))
{
	$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
}
$URI = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
if(($uc = strpos($URI, '&')) !== false)
{
	$URI = substr($URI, 0, $uc);
}
$PATH = explode('/', $URI);
$REQUESTED = str_replace('.', null, array_shift($PATH));

if(empty($REQUESTED) || !isset($export_index_json['phoromatic'][$REQUESTED]))
{
	$keys = array_keys($export_index_json['phoromatic']);
	$REQUESTED = array_shift($keys);
	$title = PHOROMATIC_VIEWER_TITLE;
	$meta_desc = 'Phoronix Test Suite\'s open-source Phoromatic result viewer for automated performance benchmark results.';
}
else
{
	$title = $export_index_json['phoromatic'][$REQUESTED]['title'];
	$meta_desc = substr($export_index_json['phoromatic'][$REQUESTED]['description'], 0, (strpos($export_index_json['phoromatic'][$REQUESTED]['description'], '. ') + 1));
}

$tracker = &$export_index_json['phoromatic'][$REQUESTED];
$length = count($tracker['triggers']);

?>
<!DOCTYPE html>
<html>
<head>
<title>Phoronix Test Suite Phoromatic - Benchmark Viewer - <?php echo $title; ?></title>
<link href="phoromatic-export-viewer.css" rel="stylesheet" type="text/css" />
<meta name="keywords" content="Linux benchmarks, open-source benchmarks, benchmark viewer, Phoronix Test Suite, Phoromatic, Phoromatic viewer" />
<meta name="Description" content="<?php echo $meta_desc; ?>" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href='https://fonts.googleapis.com/css?family=Quicksand:700' rel='stylesheet' type='text/css'/>
<link href='https://fonts.googleapis.com/css?family=Raleway' rel='stylesheet' type='text/css'/>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<div id="top_list">
<ul>
<li><?php echo PHOROMATIC_VIEWER_TITLE ?></li>
<?php

foreach($export_index_json['phoromatic'] as &$schedule)
{
	if($schedule['id'] === $REQUESTED)
	{
		echo '<li id="alt"><a href="?' . $schedule['id'] . '">' . $schedule['title'] . '</a></li>';
	}
	else
	{
		echo '<li><a href="?' . $schedule['id'] . '">' . $schedule['title'] . '</a></li>';
	}
}

?>
</ul>
</div>
<hr />
<h1><?php echo $tracker['title'] ?></h1>
<p id="phoromatic_descriptor"><?php echo $tracker['description'] ?></p>
<div id="config_option_line">
<form action="<?php $_SERVER['REQUEST_URI']; ?>" name="update_result_view" method="post">
Show Results For The Past <select name="view_results_limit" id="view_results_limit">
<?php

foreach(array(14 => 'Two Weeks', 21 => 'Three Weeks', 30 => 'One Month',  60 => 'Two Months', 90 => 'Three Months', 120 => 'Four Months', 180 => 'Six Months', 270 => 'Nine Months', 365 => 'One Year') as $days => $st)
{
	if($days > $length)
	{
		break;
	}

	echo '<option value="' . $days . '" ' . (isset($_REQUEST['view_results_limit']) && $_REQUEST['view_results_limit'] == $days ? 'selected="selected"' : null) . ' >' . $st . '</option>';
}
echo '<option value="' . count($tracker['triggers']) . '">All Results</option>';
?>
</select> Days.<br /><br />

<input type="checkbox" name="normalize_results" value="1" <?php echo (isset($_REQUEST['normalize_results']) && $_REQUEST['normalize_results'] == 1 ? 'checked="checked"' : null); ?> /> Normalize Results?

<input type="checkbox" name="clear_unchanged_results" value="1" <?php echo (isset($_REQUEST['clear_unchanged_results']) && $_REQUEST['clear_unchanged_results'] == 1 ? 'checked="checked"' : null); ?> /> Clear Unchanged Results?

<input type="checkbox" name="clear_noisy_results" value="1" <?php echo (isset($_REQUEST['clear_noisy_results']) && $_REQUEST['clear_noisy_results'] == 1 ? 'checked="checked"' : null); ?> /> Clear Noisy Results?

<input type="checkbox" name="system_table" value="1" <?php echo (isset($_REQUEST['system_table']) && $_REQUEST['system_table'] == 1 ? 'checked="checked"' : null); ?> /> Show System Information Table?

<input type="checkbox" name="regression_detector" value="1" <?php echo (isset($_REQUEST['regression_detector']) && $_REQUEST['regression_detector'] == 1 ? 'checked="checked"' : null); ?> /> Attempt To Results Of Interest?

<br /><br /><input type="submit" value="Refresh Results">

</form>
</div>
<blockquote>
<?php if(isset($welcome_msg) && !empty($welcome_msg)) { echo '<p>' . str_replace(PHP_EOL, '<br />', $welcome_msg) . '</p><hr />'; } ?>
<p>This service is powered by the <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a>'s built-in <a href="http://www.phoromatic.com/">Phoromatic</a> test orchestration and centralized performance management software. The tests are hosted by <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a>. The public code is <a href="http://github.com/phoronix-test-suite/phoronix-test-suite/">hosted on GitHub</a>.</p>
<p><a href="http://www.phoronix-test-suite.com/"><img src="images/pts.png" /></a> &nbsp; &nbsp; &nbsp; <a href="http://www.phoromatic.com/"><img src="images/phoromatic.png" /></a> &nbsp; &nbsp; &nbsp; <a href="http://openbenchmarking.org/"><img src="images/ob.png" /></a></p></blockquote>

<?php

ini_set('memory_limit', '4G');
if(isset($_REQUEST['view_results_since']) && ($st = strtotime($_REQUEST['view_results_since'])) != false)
{
	$cut_duration = ceil((time() - $st) / 86400);
}
else if(isset($_REQUEST['view_results_limit']) && is_numeric($_REQUEST['view_results_limit']) && $_REQUEST['view_results_limit'] > 7)
{
	$cut_duration = $_REQUEST['view_results_limit'];
}
else
{
	$cut_duration = 30;
}

$result_files = array();
$triggers = array_splice($tracker['triggers'], 0, $cut_duration);

foreach($triggers as $trigger)
{
	$results_for_trigger = glob(PATH_TO_EXPORTED_PHOROMATIC_DATA . '/' . $REQUESTED . '/' . $trigger . '/*/composite.xml');

	if($results_for_trigger == false)
		continue;

	foreach($results_for_trigger as $composite_xml)
	{
		// Add to result file
		$system_name = basename(dirname($composite_xml)) . ': ' . $trigger;
		array_push($result_files, new pts_result_merge_select($composite_xml, null, $system_name));
	}
}

$attributes = array();
$result_file = new pts_result_file(null, true);
$result_file->merge($result_files);
$extra_attributes = array('reverse_result_buffer' => true, 'force_simple_keys' => true, 'force_line_graph_compact' => true, 'force_tracking_line_graph' => true);

if(isset($_REQUEST['normalize_results']) && $_REQUEST['normalize_results'])
{
	$extra_attributes['normalize_result_buffer'] = true;
}
if(isset($_REQUEST['clear_unchanged_results']) && $_REQUEST['clear_unchanged_results'])
{
	$extra_attributes['clear_unchanged_results'] = true;
}
if(isset($_REQUEST['clear_noisy_results']) && $_REQUEST['clear_noisy_results'])
{
	$extra_attributes['clear_noisy_results'] = true;
}

$intent = null;
//$table = new pts_ResultFileTable($result_file, $intent);
//echo '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

if(isset($_REQUEST['regression_detector']))
{
	$has_flagged_results = false;
	foreach($result_file->get_result_objects() as $i => $result_object)
	{
		if(!$has_flagged_results)
		{
			echo '<hr /><h2>Flagged Results</h2>';
			echo '<p>Displayed are results for each system of each scheduled test where there is a measurable change when comparing the most recent result to the previous result for that system for that test.</p>';
			$has_flagged_results = true;
		}
		$poi = $result_object->points_of_possible_interest();

		if(!empty($poi))
		{
			echo '<h4>' . $result_object->test_profile->get_title() . '</h4><p>';
			foreach($poi as $text)
			{
				echo '<a href="#r-' . $i . '">' . $text . '</a><br />';
			}
			echo '</p>';
		}
	}
}

echo '<div id="pts_results_area">';
foreach($result_file->get_result_objects((isset($_REQUEST['show_only_changed_results']) ? 'ONLY_CHANGED_RESULTS' : -1), true) as $i => $result_object)
{
	if(stripos($result_object->get_arguments_description(), 'frame time') !== false)
		continue;
	$res = pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);

	if($res == false)
	{
		continue;
	}

	echo '<h2><a name="r-' . $i . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
	//echo '<h3>' . $result_object->get_arguments_description() . '</h3>';
	echo '<p class="result_object">';
	echo $res;
	echo '</p>';
	unset($result_object);
	flush();
}
echo '</div>';

if(isset($_REQUEST['system_table']) && $_REQUEST['system_table'])
{
	$table = new pts_ResultFileSystemsTable($result_file);
	echo '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
}

?>

<p id="footer"><em><?php echo pts_core::program_title(true); ?></em><br />Phoronix Test Suite, Phoromatic, and OpenBenchmarking.org are copyright &copy; 2004 - <?php echo date('Y'); ?> by Phoronix Media.<br />The Phoronix Test Suite / Phoromatic is open-source under the GNU GPL.<br />For more information, visit <a href="http://www.phoronix-test-suite.com/">Phoronix-Test-Suite.com</a> or contact <a href="http://www.phoronix-media.com/">Phoronix Media</a>.</p>
</body>
</html>
