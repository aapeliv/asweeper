<html>
	<?php
	// © 2012 - 2013 Aapeli Vuorinen
	// visit my website at http://aapeliv.com/ (if it is still up!)
	
	// This program is free software: you can redistribute it and/or modify
    // it under the terms of the GNU General Public License as published by
    // the Free Software Foundation, either version 3 of the License, or
    // (at your option) any later version.

    // This program is distributed in the hope that it will be useful,
    // but WITHOUT ANY WARRANTY; without even the implied warranty of
    // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    // GNU General Public License for more details.

    // You should have received a copy of the GNU General Public License
    // along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	session_start();
	// VARIABLES
	// $_SESSION['stage'];
	// ask - ask for info
	// input - invalid input
	// started - initialize field
	// playing - game in progress
	// lost - game ended, failed
	// won - game ended, won
	
	// output
	$output = "";
	
	// FUNCTIONS
	// outputs a field of a given size and number of mines into a variable
	function generate_field ($size_y, $size_x, $no_mines) {
		$newfield = array();
		$newfield['data'] = array();
		$newfield['field'] = array();
		for ($a = 0; $a < $size_x; $a++) {
			$newfield['field'][$a] = array();
			for ($b = 0; $b < $size_y; $b++) {
				$newfield['field'][$a][$b] = array();
				$newfield['field'][$a][$b]['value'] = 0;
				$newfield['field'][$a][$b]['show'] = 0;
				$newfield['field'][$a][$b]['flag'] = false;
			}
		}
		$newfield['data']['x'] = $size_x;
		$newfield['data']['y'] = $size_y;
		$newfield['data']['mines'] = $no_mines;
		$newfiled['data']['flags'] = 0;
		$newfield['data']['visible'] = 0;
		$newfield['data']['max_visible'] = (($size_y * $size_x) - $no_mines);
		$newfield['data']['mine_pressed']['x'] = -1;
		$newfield['data']['mine_pressed']['y'] = -1;
		for ($i = 0; $i < $no_mines; $i++) {
			$x = rand(0, $size_x - 1);
			$y = rand(0, $size_y - 1);
			if ($newfield['field'][$x][$y]['value'] != -1) {
				$newfield['field'][$x][$y]['value'] = -1;
			} else {
				$i--;
			}
		}
		for ($a = 0; $a < $size_x; $a++) {
			for ($b = 0; $b < $size_y; $b++) {
				if ($newfield['field'][$a][$b]['value'] != -1) {
					$mines = 0;
					for ($c = -1; $c <= 1; $c++) {
						for ($d = -1; $d <= 1; $d++) {
							if ($a + $c >= 0 && $b + $d >= 0 && $a + $c < $size_x && $b + $d < $size_y) {
								if ($newfield['field'][$a + $c][$b + $d]['value'] == -1) {
									$mines++;
								}
							}
						}
					}
					$newfield['field'][$a][$b]['value'] = $mines;
				}
			}
		}
		return $newfield;
	}
	// outputs the given field as a string
	function draw_field_to_string ($field) {
		$size_x = $field['data']['x'];
		$size_y = $field['data']['y'];
		$s = '<table>';
		for ($a = 0; $a < $size_x; $a++) {
			$s .= '<tr>';
			for ($b = 0; $b < $size_y; $b++) {
				$s .= '<td>';
				$image = "empty";
				$link = true;
				if ($field['field'][$a][$b]['flag'] && $field['field'][$a][$b]['value'] == -1 && $field['field'][$a][$b]['show'] != 0) {
					$image = "-2";
				} else if ($field['field'][$a][$b]['flag']) {
					$image = "flag";
				} else if ($field['field'][$a][$b]['show'] != 0) {
					$link = false;
					$image = $field['field'][$a][$b]['value'];
				}
				if ($a == $field['data']['mine_pressed']['x'] && $b == $field['data']['mine_pressed']['y']) {
					$link = false;
					$image = "-3";
				}
				if ($link)
					$s .= '<a href="#" onmousedown="handlePress(event,\'' . $a . '|' . $b . '\')">';
				$s .= '<img src="images/' . $image . '.png" style="border: 0px" />';
				if ($link)
					$s .= '</a>';
				$s .= '</td>';
			}
			$s .= '</tr>';
		}
		$s .= '</table>';
		return $s;
	}
	// makes all squares visible
	function show_all ($raw_field) {
		$field = $raw_field;
		for ($a = 0; $a < $field['data']['x']; $a++) {
			for ($b = 0; $b < $field['data']['y']; $b++) {
				$field['field'][$a][$b]['show'] = 1;
			}
		}
		return $field;
	}
	// handles the input from a link e.g. handle_left_press ($field, '5|5'); will handle the (6, 6) square in $field
	function handle_left_press ($raw_field, $raw_square) {
		$field = $raw_field;
		$square = explode('|', $raw_square);
		$a = $square[0];
		$b = $square[1];
		if (!$field['field'][$a][$b]['flag']) {
			$field['field'][$a][$b]['show'] = 1;
			if ($field['field'][$a][$b]['value'] == 0) {
				for ($c = -1; $c <= 1; $c++) {
					for ($d = -1; $d <= 1; $d++) {
						if ($a + $c >= 0 && $b + $d >= 0 && $a + $c < $field['data']['x'] && $b + $d < $field['data']['y']) {
							if ($field['field'][$a + $c][$b + $d]['show'] == 0) {
								$field = handle_left_press ($field, ($a + $c) . '|' . ($b + $d));
							}
						}
					}
				}
			}
		}
		if ($field['field'][$square[0]][$square[1]]['value'] == -1) {
			$field['data']['mine_pressed']['x'] = $square[0];
			$field['data']['mine_pressed']['y'] = $square[1];
			end_game(false);
		}
		$field['data']['visible']++;
		if ($field['data']['visible'] == $field['data']['max_visible'] && $field['field'][$square[0]][$square[1]]['value'] != -1) {
			end_game(true);
		}
		return $field;
	}
	// handles the right press i.e. adds a flag
	function handle_right_press ($raw_field, $raw_square) {
		$field = $raw_field;
		$square = explode('|', $raw_square);
		$a = $square[0];
		$b = $square[1];
		if ($field['field'][$a][$b]['flag']) {
			$field['data']['flags']--;
			$field['field'][$a][$b]['flag'] = false;
		} else {
			$field['data']['flags']++;
			$field['field'][$a][$b]['flag'] = true;
		}
		return $field;
	}
	// end of game prosessing
	function end_game ($win) {
		if ($win)
			$_SESSION['stage'] = 'won';
		else
			$_SESSION['stage'] = 'lost';
	}
	// GAME LOGIC
	if (isset($_GET['aapeli'])) {
		end_game(true);
	}
	if (!isset($_SESSION['stage']) || $_SESSION['stage'] == 'ask' || isset($_GET['newgame'])) {
		$_SESSION['stage'] = 'started';
		// field options
		$output .= '<a href="' . $_SERVER['PHP_SELF'] . '?field_x=9&field_y=9&field_mines=10">Beginner: 9 x 9 field with 10 mines</a><br />';
		$output .= '<a href="' . $_SERVER['PHP_SELF'] . '?field_x=16&field_y=16&field_mines=40">Intermediate: 16 x 16 field with 40 mines</a><br />';
		$output .= '<a href="' . $_SERVER['PHP_SELF'] . '?field_x=30&field_y=16&field_mines=99">Expert: 30 x 16 field with 99 mines</a><br />';
		
		// create field input options
		$output .= '<form method="GET" action="' . $_SERVER['PHP_SELF'] . '">';
		$output .= 'Field width:<br />';
		$output .= '<input type="text" name="field_x" /><br />';
		$output .= 'Field height:<br />';
		$output .= '<input type="text" name="field_y" /><br />';
		$output .= 'Number of mines:<br />';
		$output .= '<input type="text" name="field_mines" /><br />';
		$output .= '<input type="submit" value="Start" /><br />';
		$output .= '</form>';
	} else if ($_SESSION['stage'] == 'started') {
		if (($_GET['field_x'] * $_GET['field_y']) * 9/10 <= $_GET['field_mines']) {
			$_SESSION['stage'] = 'input';
			$output .= 'The number of mines is too high, for a field ' . $_GET['field_x'] . ' squares wide and ' . $_GET['field_y'] . ' squares high, you can not have more than ' . floor($_GET['field_x'] * $_GET['field_x'] * 9/10) . ' mines.';
		} else {
			$_SESSION['field'] = generate_field ($_GET['field_x'], $_GET['field_y'], $_GET['field_mines']);
			$_SESSION['stage'] = 'playing';
		}
	}
	if (isset($_GET['s']) && $_GET['s'] != '') {
		if ($_GET['b'] == 'l') {
			$_SESSION['field'] = handle_left_press ($_SESSION['field'], $_GET['s']);
		} else if ($_GET['b'] == 'r') {
			$_SESSION['field'] = handle_right_press ($_SESSION['field'], $_GET['s']);
		}
	}
	if ($_SESSION['stage'] == 'playing') {
		$flags_left = $_SESSION['field']['data']['mines'] - $_SESSION['field']['data']['flags'];
		$output .= '<h1 class="mines"><img src="images/-1.png" style="border: 0px" /> x ' . ($flags_left >= 0 ? $flags_left : 0) . '</h1>';
		$output .= draw_field_to_string ($_SESSION['field']);
	}
	if ($_SESSION['stage'] == 'won') {
		$output .= '<h1>You WON!</h1>';
		$_SESSION['field'] = show_all ($_SESSION['field']);
		$output .= draw_field_to_string ($_SESSION['field']);
	}
	if ($_SESSION['stage'] == 'lost') {
		$output .= '<h1>You lost :(</h1>';
		$_SESSION['field'] = show_all ($_SESSION['field']);
		$output .= draw_field_to_string ($_SESSION['field']);
	}
	?>
	<head>
		<title>Aapeli'sweeper</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
		<link rel="shortcut icon" href="favicon.ico" />
		<style>
		.mines {
			font-family: "Calibri","sans-serif";
			font-size: 30px;
		}
		.title {
			font-family: Lucida Console; 
			font-size: 20px;
		}
		.title_bold {
			font-family: Lucida Console; 
			font-size: 35px;
			font-weight: bold;
		}
		body {
			font-family: Arial; 
		}
		.copy_title {
			font-family: "Calibri","sans-serif";
			font-size: 11pt;
		}
		.copy_body {
			color: gray;
			font-family: "Constantia","serif";
			font-size: 9pt;
			line-height: 90%;
		}
		</style>
		<script>
		
		
		oncontextmenu = function() {return false;};
		document.onmousedown = disable_right_click;
		document.captureEvents ( Event.MOUSEDOWN );

		function disable_right_click (e)
		{
			if (e.which == 3) {
				return(false);
			}
		}
			
		function handlePress(e, square) {	
			var evt = e || window.event;
			var button = 'l';
			if (evt.which) { 
				if (evt.which==3) button='r';
				if (evt.which==2) button='m';
			}
			else if (evt.button) {
				if (evt.button==2) button='r';
				if (evt.button==4) button='m';
			}
			location.href = "?s=" + square + "&b=" + button;
		}
		</script>
	</head>
	<body oncontextmenu="return false;">
		<center>
			<p class="title">&lt;?php <b class="title_bold">Aapeli'sweeper</b> ?&gt;</p>
			<?php echo $output; ?>
		</center>
		<a href="?newgame">New game</a>
		<center>
			<p class="copy_title">&#169; 2012 - 2013 Aapeli&#8482;</p>
			<p class="copy_body">This program is free software: you can redistribute it and/or modify<br />it under the terms of the GNU General Public License as published by<br />the Free Software Foundation, either version 3 of the License, or<br />(at your option) any later version.<br /><br />This program is distributed in the hope that it will be useful,<br />but WITHOUT ANY WARRANTY; without even the implied warranty of<br />    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the<br />GNU General Public License for more details.<br /><br />You should have received a copy of the GNU General Public License<br />along with this program.  If not, see &lt;http://www.gnu.org/licenses/&gt;.</p>
		</center>
	</body>
</html>