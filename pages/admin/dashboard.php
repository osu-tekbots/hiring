<?php
include_once '../../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions('admin'), '../index.php');

$title = 'Dashboard';
$css = array(array('defer' => 'true', 'href' => 'assets/css/sb-admin.min.css'), 'assets/css/admin.css');
include_once PUBLIC_FILES . '/modules/header.php';

echo '<div id="wrapper">';
$active = 'Positions';
include_once PUBLIC_FILES . '/modules/adminSidebar.php';

use DataAccess\PositionDao;
use DataAccess\RoleDao;

$positionDao = new PositionDao($dbConn, $logger);
$roleDao = new RoleDao($dbConn, $logger);

function echoPositions($positions, $roleDao) {
    if(count($positions)) {
        foreach($positions as $position) {
            $members = $roleDao->getAllPositionMembers($position->getID());
            $searchChair = array("");
            if($members && count($members)) {
                $searchChair = array_filter($members, fn($elmt) => $elmt->getRole()->getName() == "Search Chair");
                $searchChair = array_map(fn($elmt) => ($elmt->getUser()->getFirstName() . " " . $elmt->getUser()->getLastName()), $searchChair);
            }

            $status = $position->getStatus();
            $statusColor = ($status == "Open" || $status == "Interviewing" ? "text-success" : 
                ($status == "Requested" ? "" : "text-danger"));
                
            echo "
            <div class='row py-3' style='border: 1px solid black'>
                <div class='col-md-4 my-auto'>
                    <h4>".$position->getTitle()."</h4>
                </div>
                <div class='col-3 my-auto'>
                    <h4 id='status".$position->getID()."' class='$statusColor'>$status</h4>
                    <h5>".implode(", ", $searchChair)."</h5>
                </div>
                <div class='col-2 my-auto'>
                    <button type='button' class='btn btn-outline-info' onclick='exportPosition(this, \"".$position->getID()."\")'>Export Data</button>
                </div>
                <div class='col-3 my-auto'>
                    <a href='pages/user/viewPosition.php?id=".$position->getID()."' class='btn btn-primary float-right'>View</a>
                    <a href='pages/user/updatePosition.php?id=".$position->getID()."' class='btn btn-outline-warning float-right mx-2'>Edit</a>";
            if($position->getStatus() == 'Requested')
                echo "<button type='button' id='approve".$position->getID()."' onclick='approvePosition(\"".$position->getID()."\")' class='btn btn-outline-success float-right'>Approve</a>";
            if($position->getIsExample())
                echo "<button type='button' id='purge".$position->getID()."' onclick='purgePosition(\"".$position->getID()."\")' class='btn btn-outline-danger float-right'>Purge</a>";
            echo "</div>
            </div>";
        }
    } else {
        echo "
        <div class='row py-3' style='border: 1px solid black'>
            <h4 class='col text-center'>No positions.</h4>
        </div>";
    }
}
?>

<div id="content-wrapper">
<br><br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">Unapproved Positions</h2>
        </div>
    </div>
    <?php echoPositions($positionDao->getUnapprovedPositions(), $roleDao); ?>
</div>
<br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">All Positions</h2>
        </div>
    </div>
    <?php echoPositions($positionDao->getApprovedPositions(), $roleDao); ?>
</div>
<br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">Example Positions</h2>
        </div>
    </div>
    <?php echoPositions($positionDao->getExamplePositions(), $roleDao); ?>
</div>

</div> <!-- Close wrapper for main contents -->
</div> <!-- Close wrapper for sidebar & main contents -->

<script>
    function approvePosition(id) {
        data = {
            action: 'approvePosition',
            id: id
        }
        
        document.getElementById('approve'+id).disabled = true;

        api.post('/position.php', data).then(res => {
            document.getElementById('approve'+id).remove();
            document.getElementById('status'+id).classList.add('text-success');
            document.getElementById('status'+id).textContent = 'Open';
            snackbar(res.message, 'success');
        }).catch(err => {
            document.getElementById('approve'+id).disabled = false;
            snackbar(err.message, 'error');
        })
    }

    function purgePosition(id) {
        data = {
            action: 'deleteExamplePosition',
            id: id
        }
        
        document.getElementById('purge'+id).disabled = true;

        api.post('/position.php', data).then(res => {
            document.getElementById('purge'+id).parentNode.parentNode.remove();
            snackbar(res.message, 'success');
        }).catch(err => {
            document.getElementById('purge'+id).disabled = false;
            snackbar(err.message, 'error');
        })
    }

    function exportPosition(thisVal, id) {
        let data = {
            action: 'emailPosition',
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
