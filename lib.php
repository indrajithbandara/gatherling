<?php
require_once 'bootstrap.php';

$HC = "#DDDDDD";
$R1 = "#EEEEEE";
$R2 = "#FFFFFF";
$CC = $R1;

function is_assoc($array) {
  return (bool)count(array_filter(array_keys($array), 'is_string'));
}

/** Gets the correct name, relative to the gatherling root dir, for a file in the theme.
 *  Allows for overrides, falls back to default/
 */
function theme_file($name) {
  global $CONFIG;
  $theme_dir = "styles/{$CONFIG['style']}/";
  $default_dir = "styles/default/";
  if (file_exists($theme_dir . $name)) {
    return $theme_dir . $name;
  }
  return $default_dir . $name;
}

function print_header($title, $js = null, $extra_head_content = "") {
  global $CONFIG;
  echo "<html><head><meta http-equiv=\"X-UA-Compatible\" content=\"IE=8\" />";
  echo "<title>{$CONFIG['site_name']} | Gatherling | {$title}</title>";
  echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"". theme_file("css/stylesheet.css") . "\" />";
  echo "<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.4.1/jquery.min.js\"></script>\n";
  if ($js) {
    echo "<script type=\"text/javascript\">";
    echo $js;
    echo "</script>";
  }
  echo $extra_head_content;
  echo <<<EOT
  </head>
  <body>
    <div id="maincontainer" class="container_12">
      <div id="headerimage" class="grid_12">
EOT;
  echo image_tag("header.png");
  echo <<<EOT
      </div>
      <div id="mainmenu_submenu" class="grid_12">
        <ul>
          <li><a href="http://pdcmagic.com/">Home</a></li>
          <li><a href="http://forums.pdcmagic.com/">Forums</a></li>
          <li><a href="http://pdcmagic.com/articles/">Articles</a></li>
          <li><a href="series.php">Events</a></li>
          <li class="current">
            <a href="index.php">
            Gatherling
            </a>
          </li>
          <li><a href="ratings.php">Ratings</a></li>
          <li class="last"><a href="http://community.wizards.com/pauperonline/wiki/">Wiki</a></li>
        </ul>
      </div>
EOT;

  $player = Player::getSessionPlayer();

  $super = false;
  $host = false;
  $steward = false;

  if ($player != NULL) {
    $host = $player->isHost();
    $super = $player->isSuper();
    $steward = count($player->stewardsSeries()) > 0;
  }

  $tabs = 5;
  if ($super || $steward) {
    $tabs += 1;
  }
  if ($host) {
    $tabs += 1;
  }
  if ($super) {
    $tabs += 1;
  }

  echo <<<EOT
<div id="submenu" class="grid_12 tabs_$tabs">
  <ul>
    <li><a href="profile.php">Profile</a></li>
    <li><a href="player.php">Player CP</a></li>
    <li><a href="eventreport.php">Metagame</a></li>
    <li><a href="decksearch.php">Decks</a></li>
EOT;
  if ($host || $super) {
    echo "<li><a href=\"event.php\">Host CP</a></li>\n";
  }

  if ($steward || $super) {
    echo "<li><a href=\"seriescp.php\">Series CP</a></li>\n";
  }

  if ($super) {
    echo "<li><a href=\"admincp.php\">Admin CP</a></li>\n";
  }

  if ($player == NULL) {
    echo "<li class=\"last\"><a href=\"login.php\">Login</a></li>\n";
  } else {
    echo "<li class=\"last\"><a href=\"logout.php\">Logout [{$player->name}]</a></li>\n";
  }

  echo "</ul> </div>\n";
}

function print_footer() {
  echo "<div class=\"grid_10 prefix_1 suffix_1\"> <div id=\"gatherling_footer\" class=\"box\">";
  version_tagline();
  echo "</div> </div>";
  echo "<div class=\"clear\"></div>\n";
  include_once('util/tracking.php');
}

function headerColor() {
  global $HC, $CC, $R1, $R2;
  $CC = $R2;
  return $HC;
}

function rowColor() {
  global $CC, $R1, $R2;
  if(strcmp($CC, $R1) == 0) {$CC = $R2;}
  else {$CC = $R1;}
  return $CC;
}

function linkToLogin() {
  echo "<center>\n";
  echo "Please <a href=\"login.php\">Click Here</a> to log in.\n";
  echo "</center>\n";
}

function printCardLink($card) {
  echo "<a href=\"http://www.deckbox.org/mtg/{$card}\"";
  echo " target=\"_blank\">{$card}</a>";
}

function image_tag($filename, $extra_attr = NULL) {
  global $CONFIG;
  $tag = "<img ";
  if (is_array($extra_attr)) {
    foreach ($extra_attr as $key => $value) {
      $tag .= "{$key}=\"{$value}\" ";
    }
  }
  $tag .= "src=\"" . $CONFIG['base_url'] . theme_file("images/{$filename}") . "\" />";
  return $tag;
}

function noHost() {
  echo "<center>\n";
  echo "Only hosts and admins may access that page.</center>\n";
}

function medalImgStr($medal) {
  return image_tag("$medal.png", array("style" => "border-width: 0px"));
}

function seasonDropMenu($season, $useall = 0) {
    $db = Database::getConnection();
    $query = "SELECT MAX(season) AS m FROM events";
    $result = $db->query($query) or die($db->error);
    $maxarr = $result->fetch_assoc();
    $max = $maxarr['m'];
    $title = ($useall == 0) ? "- Season - " : "All";
    $result->close();
    numDropMenu("season", $title, max(10, $max + 1), $season);
}

function formatDropMenu($format, $useAll = 0, $form_name = 'format') {
    $db = Database::getConnection();
    $query = "SELECT name FROM formats ORDER BY priority desc, name";
    $result = $db->query($query) or die($db->error);
    echo "<select name=\"{$form_name}\">";
    $title = ($useAll == 0) ? "- Format -" : "All";
    echo "<option value=\"\">$title</option>";
    while($thisFormat = $result->fetch_assoc()) {
        $name = $thisFormat['name'];
        $selStr = (strcmp($name, $format) == 0) ? "selected" : "";
        echo "<option value=\"$name\" $selStr>$name</option>";
    }
    echo "</select>";
    $result->close();
}

function dropMenu($name, $options, $selected = NULL) {
  echo "<select name=\"{$name}\">";
  foreach ($options as $option) {
    $setxt = "";
    if (!is_null($selected) && $selected == $option) {
      $setxt = " selected";
    }
    echo "<option value=\"{$option}\"{$setxt}>{$option}</option>";
  }
  echo "</select>";
}


function numDropMenu($field, $title, $max, $def, $min = 0, $special="") {
    if(strcmp($def, "") == 0) {$def = -1;}
    echo "<select name=\"$field\">";
    echo "<option value=\"\">$title</option>";
    if(strcmp($special, "") != 0) {
        $sel = ($def == 128) ? "selected" : "";
        echo "<option value=\"128\" $sel>$special</option>";
    }
    for($n = $min; $n <= $max; $n++) {
        $selStr = ($n == $def) ? "selected" : "";
        echo "<option value=\"$n\" $selStr>$n</option>";
    }
    echo "</select>";
}

function timeDropMenu($hour, $minutes = 0) {
  if(strcmp($hour, "") == 0) {$hour = -1;}
  echo "<select name=\"hour\">";
  echo "<option value=\"\">- Hour -</option>";
  for($h = 0; $h < 24; $h++) {
    for ($m = 0; $m < 60; $m += 30) {
      $hstring = $h;
      if ($m == 0) {
        $mstring = ":00";
      } else {
        $mstring = ":$m";
      }
      if ($h == 0) {
        $hstring = "12";
      }
      $apstring = " AM";
      if ($h >= 12) {
        $hstring = $h != 12 ? $h - 12 : $h;
        $apstring = " PM";
      }
      if($h == 0 && $m == 0) {
        $hstring = "Midnight";
        $mstring = "";
        $apstring = "";
      } elseif ($h == 12 && $m == 0) {
        $hstring = "Noon";
        $mstring = "";
        $apstring = "";
      }
      $selStr = ($hour == $h) && ($minutes == $m) ? "selected" : "";
      echo "<option value=\"$h:$m\" $selStr>$hstring$mstring$apstring</option>";
    }
  }
  echo "</select>";
}

function minutes($mins) {
  return $mins * 60;
}

function db_query_single() {
  $params = func_get_args();
  $query = array_shift($params);
  $paramspec = array_shift($params);
  $db = Database::getConnection();
  $stmt = $db->prepare($query);
  $stmt or die($db->error);
  if (count($params) == 1) {
    list($one) = $params;
    $stmt->bind_param($paramspec, $one);
  } else if (count($params) == 2) {
    list($one, $two) = $params;
    $stmt->bind_param($paramspec, $one, $two);
  } else if (count($params) == 3) {
    list($one, $two, $three) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three);
  } else if (count($params) == 4) {
    list($one, $two, $three, $four) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four);
  } else if (count($params) == 5) {
    list($one, $two, $three, $four, $five) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five);
  } else if (count($params) == 6) {
    list($one, $two, $three, $four, $five, $six) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six);
  } else if (count($params) == 7) {
    list($one, $two, $three, $four, $five, $six, $seven) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven);
  } else if (count($params) == 8) {
    list($one, $two, $three, $four, $five, $six, $seven, $eight) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven, $eight);
  } else if (count($params) == 9) {
    list($one, $two, $three, $four, $five, $six, $seven, $eight, $nine) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven, $eight, $nine);
  } else if (count($params) == 10) {
    list($one, $two, $three, $four, $five, $six, $seven, $eight, $nine, $ten) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven, $eight, $nine, $ten);
  }

  $stmt->execute() or die($stmt->error);
  $stmt->bind_result($result);

  $stmt->fetch();
  $stmt->close();
  return $result;
}

function db_query() {
  $params = func_get_args();
  $query = array_shift($params);
  $paramspec = array_shift($params);
  $db = Database::getConnection();
  $stmt = $db->prepare($query);
  $stmt or die($db->error);
  if (count($params) == 1) {
    list($one) = $params;
    $stmt->bind_param($paramspec, $one);
  } else if (count($params) == 2) {
    list($one, $two) = $params;
    $stmt->bind_param($paramspec, $one, $two);
  } else if (count($params) == 3) {
    list($one, $two, $three) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three);
  } else if (count($params) == 4) {
    list($one, $two, $three, $four) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four);
  } else if (count($params) == 5) {
    list($one, $two, $three, $four, $five) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five);
  } else if (count($params) == 6) {
    list($one, $two, $three, $four, $five, $six) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six);
  } else if (count($params) == 7) {
    list($one, $two, $three, $four, $five, $six, $seven) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven);
  } else if (count($params) == 8) {
    list($one, $two, $three, $four, $five, $six, $seven, $eight) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven, $eight);
  } else if (count($params) == 9) {
    list($one, $two, $three, $four, $five, $six, $seven, $eight, $nine) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven, $eight, $nine);
  } else if (count($params) == 10) {
    list($one, $two, $three, $four, $five, $six, $seven, $eight, $nine, $ten) = $params;
    $stmt->bind_param($paramspec, $one, $two, $three, $four, $five, $six, $seven, $eight, $nine, $ten);
  }

  $stmt->execute() or die($stmt->error);
  $stmt->close();
  return true;
}

function json_headers() {
  header('Content-type: application/json');
  header('Cache-Control: no-cache');
  header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
}

function distance_of_time_in_words($from_time,$to_time = 0, $include_seconds = false) {
  $dm = $distance_in_minutes = abs(($from_time - $to_time))/60;
  $ds = $distance_in_seconds = abs(($from_time - $to_time));

  switch ($distance_in_minutes) {
    case $dm > 0 && $dm < 1:
    if($include_seconds == false) {
      if ($dm == 0) {
        return 'less than a minute';
      } else {
        return '1 minute';
      }
    } else {
      switch ($distance_of_seconds) {
        case $ds > 0 && $ds < 4:
          return 'less than 5 seconds';
          break;
        case $ds > 5 && $ds < 9:
          return 'less than 10 seconds';
          break;
        case $ds > 10 && $ds < 19:
          return 'less than 20 seconds';
          break;
        case $ds > 20 && $ds < 39:
          return 'half a minute';
          break;
        case $ds > 40 && $ds < 59:
          return 'less than a minute';
          break;
        default:
          return '1 minute';
        break;
      }
    }
    break;
    case $dm > 2 && $dm < 44:
      return round($dm) . ' minutes';
      break;
    case $dm > 45 && $dm < 89:
      return 'about 1 hour';
      break;
    case $dm > 90 && $dm < 1439:
      return 'about ' . round($dm / 60.0) . ' hours';
      break;
    case $dm > 1440 && $dm < 2879:
      return '1 day';
      break;
    case $dm > 2880 && $dm < 43199:
      return round($dm / 1440) . ' days';
      break;
    case $dm > 43200 && $dm < 86399:
      return 'about 1 month';
      break;
    case $dm > 86400 && $dm < 525599:
      return round($dm / 43200) . ' months';
      break;
    case $dm > 525600 && $dm < 1051199:
      return 'about 1 year';
      break;
    default:
      return 'over ' . round($dm / 525600) . ' years';
    break;
  }
}

function not_allowed($reason) {
  echo "<span class=\"notallowed\" title=\"{$reason}\">&#x26A0;</span>";
}

function version_tagline() {
  print "Gatherling version 3.2.2 (\"Well I guess that is completely inconspicuous.\")";
}

function redirect($page) {
  header("Location: {$CONFIG['base_url']}{$page}");
  exit(0);
}

