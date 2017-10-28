<?PHP

# Copyright 2012-2015 OpenRA Community
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

/**
* functions
*/
function color_strings($string)
{
	$line_array = explode(' ', $string);

	if (count($line_array) < 2)
		return "";

	$time = explode('T', $line_array[0]);

	if (count($time) < 2)
		return "";

	$new_string = "";
	for ($i=1; $i<count($line_array); $i++)
	{
		$new_string = $new_string." ".$line_array[$i];
	}
	
	$content = "";
	if (isset($_GET['search']))
	{
		$time_date = explode(':', $time[0]);
		$time_date_t = explode('-', $time_date[1]);
		$time_year = $time_date_t[0];
		$time_month = $time_date_t[1];
		$time_day = $time_date_t[2];
		$time_t = $time[1];
		$content .= "<td valign='top'>[<a href='/?year=".$time_year."&month=".$time_month."&day=".$time_day."#".$time_t."'>".$time_t."</a>]</td>";
	}
	else
	{
		$time = $time[1];
		$content .= "<td valign='top'><a name='".$time."'></a>[".$time."]</td>";
	}
	$content .= "<td valign='top'>";
	if ($line_array[1] == "***" and $line_array[5] == "joined")
	{
		//join
		$name = $line_array[2];
		$new_string = "";
		for ($i=4; $i<count($line_array); $i++)
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
		for ($i=4; $i<count($line_array); $i++)
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
		for ($i=2; $i<count($line_array); $i++)
		{
			$new_string = $new_string." ".htmlspecialchars($line_array[$i]);
		}
		
		if (isset($_GET['search']))
		{
			$logic_and = strpos(strtolower($_GET['search']), " && ");

			$search_items_color = array();

			if ($logic_and)
			{
				$search_items_color = explode(" && ", strtolower($_GET['search']));
			}
			else {
				$search_items_color = explode(" and ", strtolower($_GET['search']));
			}

			foreach ($search_items_color as $search_key_color)
			{
				$pattern = "/".$search_key_color."/i";
				preg_match($pattern, $new_string, $matches);
				if ($matches)
				{
					$replacement = array("<span class='s'>".htmlspecialchars($search_key_color)."</span>");
					$new_string = preg_replace(array("/".$search_key_color."/i"), $replacement, $new_string);
				}
			}

		}
		else
			$new_string = preg_replace("/(https?:\/\/[^ ]*)/i", "<a href='$1' target=_blank>$1</a>", $new_string);
		$content .= "<span class='d'> &lt;<span class='e'>".htmlspecialchars($name)."</span>&gt;".$new_string."</span>";
	}
	elseif ($line_array[1] == "***" and $line_array[2] == "NOTICE")
	{
		$name = substr($line_array[6],0,-1);
		$channel = $line_array[4];
		$new_string = "";
		for ($i=7; $i<count($line_array); $i++)
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
		return "true";
	else
		return "false";
}

function searchtext()
{
	if (isset($_GET['search']))
	{
		$text = $_GET['search'];
		if (strlen($text) < 3)
		{	
			echo "<script type='text/javascript'>alert('At least 3 characters required')
				window.location.href = '/'</script>";
			return False;
		}


		$logic_and = strpos(strtolower($text), " && ");

		$search_items = array();

		if ($logic_and)
		{
			$search_items = explode(" && ", strtolower($text));
		}
		else {
			$search_items = explode(" and ", strtolower($text));
		}

		$to_grep = 'grep -ir "^.*T.* .*'.htmlspecialchars($search_items[0]).'.*$" '.WEBSITE_PATH.'openra/ ';

		if (count($search_items) > 1)
		{
			$first_grep_shift = array_shift($search_items);
			foreach ($search_items as $search_key)
			{
				$to_grep .= '| grep -i "^.*T.* .*'.htmlspecialchars($search_key).'.*$" ';
			}
		}

		$execute = shell_exec($to_grep . ' | grep -v "^.*T.* \*.*$" | grep -v grep | sort -r');
		$searcharray = explode("\n", $execute);
		$trash = array_pop($searcharray);
		if (!$searcharray)
			echo "<script type='text/javascript'>alert('Nothing found!')</script>";
		return $searcharray;
	}
	return False;
}
/**
* functions over
*/

$content = searchtext();
if ($content)
{
	echo "<html><head>
		<meta charset='utf-8'>
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
			<a href='http://openra.net' target='_blank'><img src='soviet-logo.png' style='border:0px'></a><h2>".$info_message."</h2>
		</td>
		<td style='padding-left:50px;padding-right:20px'>
			<form method=GET action=''>

			<input class='search-field' type='text' size=50px name='search'>
			<p style='margin-top:-30px;margin-left:315px;'><input type='submit' value='Search in Logs'></p>
			<p style='float:right'><a href='stats/index.html'>stats</a><br /><a href='http://ingame.logs.openra.net/'>in-game logs</a></p>
			<p class='operator-info'>Add the operator AND to perform a more precise search.</p>
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
	echo "</td>
		</tr>
		</table>
	";

	echo "<table id='main'>";
	// output logs lines
	foreach ($lines as $line_num => $line)
	{
		echo "<tr>".color_strings($line) . "</tr>";

		if ((int)$line_num %3000 == 0 && (int)$line_num != 0)
		{
			echo "<tr><td></td><td>";
			echo '
			<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<!-- logs.openra.net and ingame.logs -->
			<ins class="adsbygoogle"
			     style="display:inline-block;width:728px;height:90px"
			     data-ad-client="ca-pub-1502331739186135"
			     data-ad-slot="6956233403"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
			';
			echo "</td></tr>";
		}

	}

	echo "</table>";

	echo '
		<div class="google-adsense-block">
			<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
			<!-- logs.openra.net and ingame.logs -->
			<ins class="adsbygoogle"
			     style="display:inline-block;width:728px;height:90px"
			     data-ad-client="ca-pub-1502331739186135"
			     data-ad-slot="6956233403"></ins>
			<script>
			(adsbygoogle = window.adsbygoogle || []).push({});
			</script>
		</div>
	';

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
?>

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function (d, w, c) {
        (w[c] = w[c] || []).push(function() {
            try {
                w.yaCounter33786919 = new Ya.Metrika({
                    id:33786919,
                    clickmap:true,
                    trackLinks:true,
                    accurateTrackBounce:true,
                    webvisor:true
                });
            } catch(e) { }
        });

        var n = d.getElementsByTagName("script")[0],
            s = d.createElement("script"),
            f = function () { n.parentNode.insertBefore(s, n); };
        s.type = "text/javascript";
        s.async = true;
        s.src = "https://mc.yandex.ru/metrika/watch.js";

        if (w.opera == "[object Opera]") {
            d.addEventListener("DOMContentLoaded", f, false);
        } else { f(); }
    })(document, window, "yandex_metrika_callbacks");
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/33786919" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->