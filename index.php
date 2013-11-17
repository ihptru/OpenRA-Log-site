<?PHP

# Copyright 2012-2014 OpenRA Community
#
# This file is part of orabot, which is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

include_once "settings.php";

date_default_timezone_set('GMT');

$directory = "openra/";

######## functions ########
function color_strings($string)
{
    $line_array = explode(' ', $string);
    $time = explode('T', $line_array[0]);
    $time = $time[1];
    $new_string = "";
    for ($i=1;$i<=count($line_array);$i++)
    {
	$new_string = $new_string." ".$line_array[$i];
    }
    
    $content = "";
    $content .= "<td valign='top'>[".$time."]</td>";
    $content .= "<td valign='top'>";
    if ($line_array[1] == "***" and $line_array[5] == "joined")
    {
	//join
	$name = $line_array[2];
	$new_string = "";
	for ($i=4;$i<=count($line_array);$i++)
	{
	    $new_string = $new_string." ".$line_array[$i];
	}
	$content .= "<span class='b'>*** ".htmlspecialchars($name).htmlspecialchars($new_string)."</span>";
    }
    elseif ($line_array[1] == "***" and ( $line_array[5] == "quit" or $line_array[5] == "left" ))
    {
	// quit or part
	$name = $line_array[2];
	$new_string = "";
	for ($i=4;$i<=count($line_array);$i++)
	{
	    $new_string = $new_string." ".$line_array[$i];
	}
	$content .= "<span class='a'>*** ".htmlspecialchars($name).htmlspecialchars($new_string)."</span>";
    }
    elseif ($line_array[1][0] == "<")
    {
	// just a text
	$name = explode("<", $new_string);
	$name = explode(">", $name[1]);
	$name = $name[0];
	$new_string = "";
	for ($i=2;$i<=count($line_array);$i++)
	{
	    $new_string = $new_string." ".$line_array[$i];
	}
	if (isset($_POST['searchtext']))
        {
	    $pattern = "/".$_POST['searchtext']."/i";
	    preg_match($pattern, $new_string, $matches);
	    if ($matches)
	    {
		foreach ($matches as $item)
		{
		    $replacement = array("<span class='s'>".htmlspecialchars($item)."</span>");
		    $new_string = preg_replace(array("/".$item."/i"), $replacement, $new_string);
		}
	    }
        }
	else
	    $new_string = preg_replace("/(https?:\/\/[^ ]*)/i", "<a href='$1' target=_blank>$1</a>", htmlspecialchars($new_string));
	$content .= "<span class='d'> &lt;<span class='e'>".htmlspecialchars($name)."</span>&gt;".$new_string."</span>";
    }
    elseif ($line_array[1] == "***" and $line_array[2] == "NOTICE")
    {
	$name = substr($line_array[6],0,-1);
	$channel = $line_array[4];
	$new_string = "";
	for ($i=7;$i<=count($line_array);$i++)
	{
	    $new_string = $new_string." ".$line_array[$i];
	}
	$content .= "<span class='c'> <span class='e'>-".htmlspecialchars($name)."/".htmlspecialchars($channel)."-</span>".$new_string."</span>";
    }
    else
    {
	// nick, modes, kicks, topics
	$content .= "<span class='c'>".htmlspecialchars($new_string)."</span>";
    }

    $content .= "</td>";
    return $content;
}

function get_default_layout($directory)
{
    $year = end(scandir($directory));
    $month = end(scandir($directory . $year));
    $day = end(scandir($directory.$year."/".$month));
    return array($year,$month,$day);
}

function selected($form, $current)
{
    if ($form == $current)
    {
	return "true";
    }
    else
    {
	return "false";
    }
}

function searchtext()
{
    if (isset($_POST['searchtext']))
    {
	$text = $_POST['searchtext'];
	if (strlen($text) < 3)
	{	
	    echo "<script type='text/javascript'>alert('At least 3 characters required')
	    window.location.href = '/'</script>";
	    return False;
	}
	$execute = shell_exec('grep -ir "^.*T.* .*'.htmlspecialchars($text).'.*$" '.WEBSITE_PATH.'openra/ | grep -v "^.*T.* \*.*$" | grep -v grep | sort -r');
	$searcharray = explode("\n", $execute);
	$trash = array_pop($searcharray);
	if (!$searcharray)
	    echo "<script type='text/javascript'>alert('Nothing found!')</script>";
	return $searcharray;
    }
    return False;
}
$content = searchtext();
if ($content)
{
    echo "<html><head>
    <link rel='stylesheet' type='text/css' href='style.css'>
    <title>IRC Logs of #openra</title></head>
    <body>
    <p id='main'><a href='/'><<< Back</a></p>
    <table id='main'>";
    echo "<tr><td valgn='top'><b>&nbsp;&nbsp;Results: </b></td><td><b>".count($content)."</b></td></tr>";
    foreach ($content as $value)
    {
	$file_ts = explode(":", $value);
	$file_ts = $file_ts[1];
	$file_ts = explode("T", $file_ts);
	$file_ts = $file_ts[0];
	echo "<tr><td valign='top' width='80px'>".$file_ts."</td>".color_strings($value)."</tr>";
    }
    echo "</table></body></html>";
}
else
{
// set up $year, $month and $day vars
if (isset($_GET["year"]) and isset($_GET["month"]) and isset($_GET["day"]))
{
    $year = $_GET["year"];
    $month = $_GET["month"];
    $day = $_GET["day"];
    if (!file_exists(WEBSITE_PATH.$directory.$year."/".$month."/".$day))
    {
	// no such file: get default layout
	list($year,$month,$day) = get_default_layout($directory);
    }
}
else
{
    // no GET request found: use default layout
    list($year,$month,$day) = get_default_layout($directory);
}

$path = $directory.$year."/".$month."/".$day;
$lines = file($path);

$info_message = "IRC Logs of #openra<br>".date("F j, Y", mktime(0, 0, 0, $month, $day, $year));

echo "<html><head>
    <link rel='stylesheet' type='text/css' href='style.css'>
    <title>IRC Logs of #openra</title></head>
    <body>
    <table id='main' style='height:200px;'>
    <tr>
	<td width=350px;>
	    <a href='http://open-ra.org' target='_blank'><img src='soviet-logo.png' style='border:0px'></a><h2>".$info_message."</h2>
	</td>
	<td style='padding-left:50px;padding-right:20px'>
	    <form method=POST action=''>

	    <input style='margin-top:-10px;' type='text' size=50px name='searchtext'>
	    <p style='margin-top:-30px;margin-left:315px;'><input type='submit' value='Search in Logs'></p>
	    <p style='float:right'><a href='stats/index.html'>stats</a></p>
	    </form>
	    <div id='main' style='margin-top:50px;'>";
		$post_year = $year;
		$post_month = $month;
		$post_day = $day;
		if (isset($_POST['year']) and !isset($_POST['month']))
		{
		    $post_year = $_POST['year'];
		    $post_month = scandir($directory.$post_year);
		    $post_month = $post_month[2];
		    $post_day = scandir($directory.$post_year."/".$post_month);
		    $post_day = $post_day[2];
		}
		if (isset($_POST['month']) and isset($_POST['year']))
		{
		    $post_year = $_POST['year'];
		    $post_month = $_POST['month'];
		    $post_day = scandir($directory.$post_year."/".$post_month);
		    $post_day = $post_day[2];
		}
		echo "<form method=GET action='' name='layout'>
		<select name='year' id='year' onChange='post_to_url_year();' title='year'>
		
		</select>
		<select name='month' id='month' onChange='post_to_url_month();' title='month'>
		
		</select>
		<select name='day' id='day' title='day'>
		
		</select> 
		
		<input type='submit' value='Update' name='update'>
		</form>
		
		<script type='text/javascript'>
		    function fill_data()
		    {
			document.layout.year.options.length=0";
			$list_dir = scandir($directory);
			foreach ($list_dir as $value)
			{
			    if ($value != "." and $value != "..")
				echo "\ndocument.layout.year.options[document.layout.year.options.length] = new Option('".$value."','".$value."',false,".selected($value, $post_year).")";
			}
		    echo "
			document.layout.month.options.length=0";
			$list_dir = scandir($directory.$post_year);
			foreach ($list_dir as $value)
			{
			    if ($value != "." and $value != "..")
				echo "\ndocument.layout.month.options[document.layout.month.options.length] = new Option('".$value."','".$value."',false,".selected($value, $post_month).")";
			}
		    echo "
			document.layout.day.options.length=0";
			$list_dir = scandir($directory.$post_year."/".$post_month);
			foreach ($list_dir as $value)
			{
			    if ($value != "." and $value != "..")
				echo "\ndocument.layout.day.options[document.layout.day.options.length] = new Option('".$value."','".$value."',false,".selected($value, $post_day).")";
			}
		    echo "
		    }
		    fill_data()
		</script>
	    </div>
	    
	    <script type='text/javascript'>
		function post_to_url_year()
		{
		    method = 'post';

		    var form = document.createElement('form');
		    form.setAttribute('method', method);
		    form.setAttribute('action', '');
		    
		    var params = {
			'year' : document.getElementById('year').options[document.getElementById('year').selectedIndex].value
		    }
		    for ( var key in params )
		    {
			var hiddenField = document.createElement('input');
			hiddenField.setAttribute('type', 'hidden');
			hiddenField.setAttribute('name', key);
			hiddenField.setAttribute('value', params[key]);
			
			form.appendChild(hiddenField);
		    }

		    document.body.appendChild(form);
		    form.submit();
		}
		function post_to_url_month()
		{
		    method = 'post';

		    var form = document.createElement('form');
		    form.setAttribute('method', method);
		    form.setAttribute('action', '');
		    
		    var params = {
			'year' : document.getElementById('year').options[document.getElementById('year').selectedIndex].value,
			'month' : document.getElementById('month').options[document.getElementById('month').selectedIndex].value
		    }
		    for ( var key in params )
		    {
			var hiddenField = document.createElement('input');
			hiddenField.setAttribute('type', 'hidden');
			hiddenField.setAttribute('name', key);
			hiddenField.setAttribute('value', params[key]);
			
			form.appendChild(hiddenField);
		    }

		    document.body.appendChild(form);
		    form.submit();
		}
	    </script>
";

// get list of days from $year and $month
$days_current_month = date('t', mktime(0, 0, 0, $month, 1, $year)); 

for ($i=1;$i<=$days_current_month;$i++)
{
    if (strlen($i) == 1)
	$res = "0".$i;
    else
	$res = $i;
    if ($day == $res)
	$class = " style='color:#0000FF; background-color:#FFFF00;'";
    else
	$class = "";
    if (file_exists(WEBSITE_PATH.$directory.$year."/".$month."/".$res))
    {
	echo "<a href='index.php?year=".$year."&month=".$month."&day=".$res."' title='".date("l", mktime(0, 0, 0, $month, $res, $year))."' ".$class.">".$res."</a>  ";
    }
    else
    {
	echo $res. "  ";
    }
}
echo"</td>
    </tr>
</table>
";

echo "<table id='main'>";
// output logs lines
foreach ($lines as $line_num => $line)
{
    echo "<tr>".color_strings($line) . "</tr>";
}

echo "</table>";

$TS = strtotime($year."-".$month."-".$day);

if($TS !== false)
{
    // 'previous day' link
    $prev = false;
    $prev_day_year = date('Y', strtotime('-1 day', $TS));
    $prev_day_month = date('m', strtotime('-1 day', $TS));
    $prev_day_day = date('d', strtotime('-1 day', $TS));
    if (file_exists(WEBSITE_PATH.$directory.$prev_day_year."/".$prev_day_month."/".$prev_day_day))
    {
	echo "<br><p id='main'><a href='index.php?year=".$prev_day_year."&month=".$prev_day_month."&day=".$prev_day_day."'>&lt;&lt;&lt; Previous Day</a>";
	$prev = true;
    }
    // 'next day' link
    $next_day_year = date('Y', strtotime('+1 day', $TS));
    $next_day_month = date('m', strtotime('+1 day', $TS));
    $next_day_day = date('d', strtotime('+1 day', $TS));
    if (file_exists(WEBSITE_PATH.$directory.$next_day_year."/".$next_day_month."/".$next_day_day))
    {
	if ($prev)
	    echo " | <a href='index.php?year=".$next_day_year."&month=".$next_day_month."&day=".$next_day_day."'>Next Day &gt;&gt;&gt;</a><p>";
	else
	    echo "<br><p id='main'><a href='index.php?year=".$next_day_year."&month=".$next_day_month."&day=".$next_day_day."'>Next Day &gt;&gt;&gt;</a><p>";
    }
    else
    {
	if ($prev)
	    echo "</p>";
    }
}

echo "</body></html>";
}

echo '
<!-- Start of StatCounter Code for Default Guide -->
<script type="text/javascript">
var sc_project=7550490; 
var sc_invisible=1; 
var sc_security="27dc495c"; 
</script>
<script type="text/javascript"
src="http://www.statcounter.com/counter/counter.js"></script>
<noscript><div class="statcounter"><a title="vBulletin
statistic" href="http://statcounter.com/vbulletin/"
target="_blank"><img class="statcounter"
src="http://c.statcounter.com/7550490/0/27dc495c/1/"
alt="vBulletin statistic"></a></div></noscript>
<!-- End of StatCounter Code for Default Guide -->
';
?>
