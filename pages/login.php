<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

$isLoggedIn = verifyPermissions(['user', 'admin']);
if ($isLoggedIn) {
    // Redirect to their dashboard
    $redirect = $configManager->getBaseUrl() . 'pages/user/dashboard.php';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

$title = 'Login';
include_once PUBLIC_FILES . '/modules/header.php';

?>

<br><br><br>
<div class="container">
<div class="row justify-content-center">
    <div class="col" style="max-width: 600px">
        <br>
        <hr class="my-4">
        <h4 class="text-center">OSU Employee Login</h4>
        <a class="login" href="auth/index.php?provider=onid" style="text-decoration:none;">
            <button id="onidBtn" class="btn btn-lg btn-warning btn-block text-uppercase" type="submit">
                <i class="fas fa-book mr-2"></i> ONID Login
            </button>
        </a>
        <!-- <hr class="my-4">
        <h4 class="text-center">Google Login</h4>
        <a class="login" href="auth/index.php?provider=onid" style="text-decoration:none;">
            <button id="onidBtn" class="btn btn-lg btn-primary btn-block text-uppercase" type="submit">
                <i class="fab fa-google mr-2"></i> Google Login
            </button>
        </a> -->
        <hr class="my-4">
        <h4 class="text-center">Local Login</h4>
        <a class="login" href="auth/index.php?provider=local" style="text-decoration:none;">
            <button id="localBtn" class="btn btn-lg btn-danger btn-block text-uppercase" type="submit">
                <i class="fas fa-id-badge mr-2"></i> Local Login
            </button>
        </a>
        <hr class="my-4">
    </div>
</div>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
