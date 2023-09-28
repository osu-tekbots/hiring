<?php
include_once '../../bootstrap.php';

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

<div class="alert alert-info container mt-2">
    <i class="fas fa-info-circle"></i>
    Welcome to the Hiring tool! You can use this tool to track your thoughts about each candidate you review and share
    those thoughts with the other members of your search committee. If you're the Search Chair for a new position, you 
    can <a href="./pages/user/createPosition.php">add the position to our system</a> to utilize our tool during your
    search.
</div>
<br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">Search Membership</h2>
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
                    <div class='col-md-4 my-auto'>
                        <h4>".$position->getTitle()."</h4>
                    </div>
                    <div class='col-4 my-auto'>
                        <h4 class='$statusColor'>$status</h4>
                    </div>
                    <div class='col-2 my-auto'>";
                if(checkRoleForPosition('Search Chair', $position->getID())) {
                    echo "<button type='button' class='btn btn-outline-info' onclick='exportPosition(this, \"".$position->getID()."\")'>Export Data</button>";
                }
                echo "</div>
                    <div class='col-2 my-auto'>";
                if($position->getStatus() != 'Requested' || verifyPermissions('admin'))
                    echo "<a href='user/viewPosition.php?id=".$position->getID()."' class='btn btn-primary float-right'>View</a>";
                if(checkRoleForPosition('Search Chair', $position->getID()) && $position->getStatus() != 'Requested' || verifyPermissions('admin'))
                    echo "<a href='user/updatePosition.php?id=".$position->getID()."' class='btn btn-outline-warning float-right mx-2'>Edit</a>";
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

<script>
    function exportPosition(thisVal, id) {
        let data = {
            action: 'exportPosition',
            id: id
        }

        thisVal.disabled = true;

        snackbar('Generating Export Email', 'info');

        api.post('/position.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
