<?php
include_once '../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['user', 'admin']), 'index.php');

$title = 'Dashboard';
include_once PUBLIC_FILES . '/modules/header.php';

include_once PUBLIC_FILES . '/modules/breadcrumb.php';
renderBreadcrumb(NULL, $title);

use DataAccess\PositionDao;

$positionDao = new PositionDao($dbConn, $logger);

$positions = $positionDao->getPositionsForUser($_SESSION['userID']);

?>

<br><br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col-sm-4 d-block"><!-- Used to keep title centered --></div>
        <div class="col">
            <h2 class="my-auto" style="text-align: center">Your Positions</h2>
        </div>
        <div class="col-sm-4">
            <a href="userCreatePosition.php" class="btn btn-outline-primary float-right">Add Position</a>
        </div>
    </div>
    <?php
        if(count($positions)) {
            foreach($positions as $position) {
                $status = $position->getStatus();
                $statusColor = ($status == "Open" || $status == "Interviewing" ? "text-success" : 
                    ($status == "Requested" ? "text-warning" : ""));
                echo "
                <div class='row py-3' style='border: 1px solid black'>
                    <div class='col-md-5 my-auto'>
                        <h4>".$position->getTitle()."</h4>
                    </div>
                    <div class='col-5 my-auto'>
                        <h4 class='$statusColor'>$status</h4>
                    </div>
                    <div class='col my-auto'>";
                if($position->getStatus() != 'Requested' || verifyPermissions('admin'))
                    echo "<a href='userPosition.php?id=".$position->getID()."' class='btn btn-primary float-right'>View</a>";
                if(checkRoleForPosition('Search Chair', $position->getID()) && $position->getStatus() != 'Requested' || verifyPermissions('admin'))
                    echo "<a href='userUpdatePosition.php?id=".$position->getID()."' class='btn btn-outline-warning float-right mx-2'>Edit</a>";
                echo "</div>
                </div>";
            }
        } else {
            echo "
            <div class='row py-3' style='border: 1px solid black'>
                <h4 class='col text-center'>You are not on the committee for any positions.</h4>
            </div>";
        }
    ?>
</div>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
