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

<div class="alert alert-info container mt-4">
    <i class="fas fa-info-circle"></i>
    Welcome to the Search Progress Tracker! You can use this tool to track your thoughts about each candidate you review and share
    those thoughts with the other members of your search committee. If you're the Search Chair for a new position, you 
    can <a href="./pages/user/createPosition.php">add the position to our system</a> to utilize our tool during your
    search. To edit an example position to better understand this tool's capabilities, click 
    <button type='button' class='btn btn-link' style='margin-top: -8px' data-toggle='modal' data-target='#exampleModal'>here</button>.
</div>
<br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col">
            <h2 class="my-auto" style="text-align: center">Search Membership</h2>
        </div>
    </div>
    <?php
        /* Have to count manually since completed positions are only shown to search chair */
        $positionsDisplayed = 0;

        if(count($positions)) {
            foreach($positions as $position) {
                $status = $position->getStatus();

                if($status == "Completed" && !checkRoleForPosition('Search Chair', $position->getID()))
                    continue;

                $positionsDisplayed++;

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
                    echo "<button type='button' class='btn btn-outline-info' data-toggle='modal' data-target='#exportModal' onclick='updateExportModalData(\"".$position->getID()."\")'>Export Data</button>";
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
        }
        
        if($positionsDisplayed == 0) {
            echo "
            <div class='row py-3' style='border: 1px solid black'>
                <h4 class='col text-center'>You are not on the committee for any positions.</h4>
            </div>";
        }
    ?>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title w-100 text-center">Export Position</h5>
        </div>
        <div class="modal-body">
            <p style="white-space: normal;">
                To export all position data for compliance with laws around saving hiring notes, click 
                <kbd>Email All Data</kbd>. This will send you an email with all information about each candidate, all 
                information saved about each qualification and round, and all committee members' notes throughout the
                search process.
                <br><br>
                To export data for the HR Disposition Worksheet, click <kbd>Save Disposition Sheet</kbd>. This will 
                download a spreadsheet with the necessary data to copy and save on the HR worksheet.
            </p>
        </div>
        <div class="modal-footer">
            <button id="exportEmail" type="button" class="btn btn-primary" onclick="emailPosition(this, null)">Email All Data</button>
            <button id="exportDisp" type="button" class="btn btn-primary" onclick="exportPositionDisposition(this, null)">Save Disposition Sheet</button>
            <button id="closeExportModal" type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        </div>
    </div>
  </div>
</div>

<!-- Example Position Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title w-100 text-center">Duplicate Example Position</h5>
        </div>
        <div class="modal-body">
            <p style="white-space: normal;">
                This will create a new position based off an example template. You will be able to modify qualifications,
                rounds, and candidates; complete the feedback process; and otherwise run through the procedures this tool
                supports. After you've evaluated the tool, you can delete the example and add a new position to our system
                to run your search. Your example position will be automatically deleted two weeks after you duplicate it,
                if you have not already deleted it yourself.
            </p>
        </div>
        <div class="modal-footer">
            <button id="closeExampleModal" type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            <button id="duplicateExample" type="button" class="btn btn-primary" onclick="copyExample()">Duplicate Example</button>
        </div>
    </div>
  </div>
</div>

<script>
    function copyExample() {
        let data = {
            action: 'getExample'
        };

        snackbar('Duplicating Example', 'info');

        api.post('/position.php', data).then(res => {
            snackbar(res.message, 'success');
            window.location.href = 'pages/user/updatePosition.php?id=' + res.content;
        }).catch(err => {
            snackbar(err.message, 'error');
        });
    }

    function updateExportModalData(id) {
        document.getElementById('exportEmail').setAttribute('onclick', `emailPosition(this, '${id}')`);
        document.getElementById('exportDisp').setAttribute('onclick', `exportPositionDisposition(this, '${id}')`);
    }

    function emailPosition(thisVal, id) {
        let data = {
            action: 'emailPosition',
            id: id
        }

        thisVal.disabled = true;

        snackbar('Generating Export Email', 'info');

        api.post('/position.php', data).then(res => {
            snackbar(res.message, 'success');
            document.getElementById('closeExportModal').click();
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function exportPositionDisposition(thisVal, id) {
        let data = {
            action: 'exportPositionDisposition',
            id: id
        }

        thisVal.disabled = true;

        snackbar('Generating Export Email', 'info');

        api.post('/position.php', data).then(res => {
            snackbar(res.message, 'success');
            document.getElementById('closeExportModal').click();
            window.open(res.content);
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
