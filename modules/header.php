<?php
/**
 * This header module should be included in all PHP files under the `pages/` directory. It includes all the necessary
 * JavaScript and CSS files and creates the header navigation bar.
 * 
 * Before including the header file, you can specify a `$js` or `$css` variable to add additional JavaScript files
 * and CSS stylesheets to be included when the page loads in the browser. These additional files will be included
 * **after** the default scripts and styles already included in the header.
 */
include_once PUBLIC_FILES . '/modules/button.php';

if (!session_id()) {
  $ok = @session_start();
  if(!$ok){
    session_regenerate_id(true); // replace the Session ID
    session_start(); 
  }
}

$userDao = new DataAccess\UserDao($dbConn, $logger);

$baseUrl = $configManager->getBaseUrl();

$title = isset($title) ? $title : 'OSU';

// JavaScript to include in the page. If you provide a JS reference as an associative array, the keys are the
// atributes of the <script> tag. If it is a string, the string is assumed to be the src.
if (!isset($js)) {
    $js = array();
}
$js = array_merge( 
    array(
        'assets/js/jquery-3.3.1.min.js',
        'assets/js/popper.min.js',
        'assets/js/bootstrap.min.js',
        'assets/js/jquery-ui.js',
        'assets/js/api.js',
        'assets/js/snackbar.js',
        'assets/js/error.js'
    ), $js
);

// CSS to include in the page. If you provide a CSS reference as an associative array, the keys are the
// atributes of the <link> tag. If it is a string, the string is assumed to be the href.
if (!isset($css)) {
    $css = array();
}
$css = array_merge(
    array(
        array(
            'href' => 'https://use.fontawesome.com/releases/v5.7.1/css/all.css',
            'integrity' => 'sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr',
            'crossorigin' => 'anonymous'
        ),
        'assets/css/bootstrap.min.css',
        'assets/css/jquery-ui.css',
        'assets/css/capstone.css',
        'assets/css/snackbar.css',
        array(
            'media' => 'screen and (max-width:768px)', 
            'href' => 'assets/css/capstoneMobile.css'
        ),
    ),
    $css
);

// Setup the buttons to use in the header
$buttons = array();

if (verifyPermissions(['user', 'admin'])) {
    // User is logged in
    $buttons['Dashboard'] = 'pages/user/dashboard.php';
    
    $buttons['Profile'] = 'pages/user/profile.php';
    
    // Admin only
    if (verifyPermissions('admin')) {
        $buttons['Admin'] = 'pages/admin/dashboard.php';
    }

    $buttons['Logout'] = 'pages/logout.php';
} else {
    // User is logged out
    $buttons['Login'] = 'pages/login.php';
}

	
//Inherited from TekBots. Intended to help with image uploads. May slow everything else down.	
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://fonts.googleapis.com/css?family=Roboto:900|Abel|Heebo:700" rel="stylesheet">
    <link href="https://oregonstate.edu/themes/osu/homepage/favicon.ico" rel="icon" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?php echo $baseUrl ?>" />
    <title><?php echo 'SPT | '.$title; ?></title>

    <?php
    // Include the JavaScript files
    foreach ($js as $script) {
        if (!is_array($script)) {
            echo "<script type=\"text/javascript\" src=\"$script\"></script>";
        } else {
            $link = '<script type="text/javascript" ';
            foreach ($script as $attr => $value) {
                $link .= $attr . '="' . $value . '" ';
            }
            $link .= '></script>';
            echo $link;
        }
    }

    // Include the CSS Stylesheets
    foreach ($css as $style) {
        if (!is_array($style)) {
            echo "<link rel=\"stylesheet\" href=\"$style\" />";
        } else {
            $link = '<link rel="stylesheet" ';
            foreach ($style as $attr => $value) {
                $link .= $attr . '="' . $value . '" ';
            }
            $link .= '/>';
            echo $link;
        }
    } 
	?>

</head>
<body>
    <script>
        function stopMasquerade() {
            document.getElementById('masq').disabled = true;

            api.post('/user.php', {action: 'stopMasquerade'}).then(res => {
                location.href = './pages/admin/user.php';
            }).catch(err => {
                snackbar(err.message, 'error');
            }).finally(() => document.getElementById('masq').disabled = false);
        }
    </script>

    <header id="header" class="dark" style="position: sticky;">
        <a class="header-main-link" href="/pages">
            <div class="logo">
                <img class="logo" src="assets/img/osu-logo-orange.png" />
                <h1><span id="projectPrefix"><b>Search Progress Tracker</b></span> </h1>
            </div>
        </a>
        <?php 
            /* For desktop users */
            if(isset($_SESSION['masq']) && $_SESSION['masq']['active'])  {
                $user = $userDao->getUserByID($_SESSION['userID']);

                echo '<div class="d-flex align-items-end justify-content-center navbarBrowser" style="flex-grow: 1">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-user-secret mr-2"></i>
                            You are currently masquerading as '.$user->getFirstName().' '.$user->getLastName().'. <button id="masq" type="button" onclick="stopMasquerade()" class="btn btn-link" style="margin-bottom: -1px">Stop masquerading</button>
                        </div>
                    </div>';
            }
        ?>
		<nav class="navigation">
            <ul>
            <?php 
                foreach ($buttons as $name => $link) {
                    echo createHeaderButton($link, $name);
                }
            ?>
            </ul>
        </nav>
    </header>

    <main class="px-1" style="padding-top: initial;">
    <?php
        /* For mobile device users */
        if(isset($_SESSION['masq']) && $_SESSION['masq']['active']) {
            $user = $userDao->getUserByID($_SESSION['userID']);

            echo '<div class="navbarMobile w-100" style="flex-grow: 1">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-user-secret mr-2"></i>
                        You are currently masquerading as '.$user->getFirstName().' '.$user->getLastName().'. <button id="masq" type="button" onclick="stopMasquerade()" class="btn btn-link" style="margin-bottom: -1px">Stop masquerading</button>
                    </div>
                </div>';
        }
    ?>
