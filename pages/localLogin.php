<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    session_start();
}

$isLoggedIn = verifyPermissions(['user', 'admin']);
if ($isLoggedIn) {
    // Redirect to their dashboard
    $redirect = $configManager->getBaseUrl() . 'pages/userDashboard.php';
    echo "<script>window.location.replace('$redirect');</script>";
    die();
}

$title = 'Login';
include_once PUBLIC_FILES . '/modules/header.php';

?>

<section>
    <form action="./auth/localAttempt.php" method="POST">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card shadow-2-strong bg-light" style="border-radius: 1rem;">
                        <div class="card-body p-5 text-center">
                            <h3 class="mb-5">Local Sign in</h3>
                            <div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="localEmail">Email</label></div>
                                <input type="email" name="localEmail" id="localEmail" class="form-control form-control-lg" />
                            </div>
                            <div class="mb-4 input-group">
                                <div class="input-group-prepend"><label class="input-group-text" for="localPassword">Password</label></div>
                                <input type="password" name="localPassword" id="localPassword" class="form-control form-control-lg" />
                            </div>
                            <a class="forgot-password float-right mb-2" href="pages/localForgotPassword.php">Forgot Password?</a>
                            <button class="btn btn-primary btn-lg btn-block" type="submit">Login</button>
                            <hr class="my-4">
                            <a class="text-primary" href="pages/localNewUser.php">Create Account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
