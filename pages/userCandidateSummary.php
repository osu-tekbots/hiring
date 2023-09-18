<?php
include_once '../bootstrap.php';

use DataAccess\CandidateDao;
use DataAccess\CandidateFileDao;
use DataAccess\CandidateRoundNoteDao;
use DataAccess\FeedbackDao;
use DataAccess\FeedbackFileDao;
use DataAccess\FeedbackForQualDao;
use DataAccess\PositionDao;
use DataAccess\QualificationDao;
use DataAccess\QualificationStatusDao;
use DataAccess\QualificationForRoundDao;
use DataAccess\RoundDao;
use DataAccess\UserDao;

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['admin', 'user']), 'It looks like you\'re not signed in. Please sign in before submitting feedback.', true);
allowIf(!is_null($_REQUEST['id']), 'It looks like your request failed to specify a candidate to submit feedback for.', true);

$title = 'Candidate Review Summary';
include_once PUBLIC_FILES . '/modules/header.php';

$qualificationStatusDao = new QualificationStatusDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$candidateFileDao = new CandidateFileDao($dbConn, $logger);
$candidateRoundNoteDao = new CandidateRoundNoteDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$qualificationDao = new QualificationDao($dbConn, $logger);
$positionDao = new PositionDao($dbConn, $logger);
$qfrDao = new QualificationForRoundDao($dbConn, $logger);
$ffqDao = new FeedbackForQualDao($dbConn, $logger);
$feedbackDao = new FeedbackDao($dbConn, $logger);
$feedbackFileDao = new FeedbackFileDao($dbConn, $logger);
$userDao = new UserDao($dbConn, $logger);

$candidateID = $_REQUEST['id'];
$candidate = $candidateDao->getCandidateById($candidateID);

allowIf($candidate, 'It looks like you tried to review the summary for a candidate that doesn\'t exist.', true);

$position = $positionDao->getPosition($candidate->getPositionID());
$rounds = $roundDao->getAllRoundsByPositionId($position->getID());
$files = $candidateFileDao->getAllFilesForCandidate($candidateID);
$users = $userDao->getUsersForPosition($position->getID());

allowIf(checkRoleForPosition('Any', $position?->getID()), 'It looks like you\'re not on the committee for that position. Please speak to the committee\'s search chair if you believe you should be added.', true); // Implicitly verifies that position exists
// Prevent unverified users from accessing the whole site
allowIf($position->getStatus() == 'Interviewing' || $position->getStatus() == 'Closed' || verifyPermissions('admin'), "It looks like this position hasn't started interviewing yet. Please ask the committee's search chair to update the position's status.", true);

include_once PUBLIC_FILES."/modules/breadcrumb.php";
renderBreadcrumb(["./pages/userDashboard.php"=>"Dashboard", ("./pages/userPosition.php?id=".$position->getID())=>"View Position"], $title);

?>

<br><br><br>
<div class="container" style="border: 2px solid black">
<div class="row py-3" style="border-bottom: 2px solid black">
        <div class="col-6">
            <h2 class="my-auto" style="text-align: center;"><?php echo $candidate->getFirstName().' '.$candidate->getLastName() ?></h2>
        </div>
        <div class="col-4">
            <h6>Email: <button id="emailCandidate" class="btn btn-link" type="button" data-toggle="modal" data-target="#emailModal"><?php echo $candidate->getEmail() ?></button></h6>
            <h6>Phone: <a href="tel:<?php echo $candidate->getPhoneNumber() ?>"><?php echo $candidate->getPhoneNumber() ?></a></h6>
            <!-- <h6>Location: <a target="_blank" href="https://www.google.com/maps?q=<?php echo $candidate->getLocation() ?>"><?php echo $candidate->getLocation() ?></a></h6> -->
            <?php
                if(count($files)) {
                    echo "<h6>Files: ";
                    foreach($files as $index=>$file) {
                        echo "<a target='_blank' href='./uploads/candidate/".$file->getFileName()."'>".$file->getPurpose()."</a>".($index < count($files) - 1 ? ", " : "");
                    }
                    echo "</h6>";
                }
            ?>
        </div>
        <div class="col-sm-2">
            <?php
                if(checkRoleForPosition('Search Chair', $position?->getID()))
                    echo '<button class="btn btn-outline-danger float-right" type="button" data-toggle="modal" data-target="#statusModal">Update Status</button>';
            ?>
        </div>
    </div>

    <?php
    foreach($rounds as $index=>$round) {
        // $feedback = $feedbackDao->getFeedbackForUser($_SESSION['userID'], $candidateID, $round->getID()); // Just for reference
        $qualifications = $qfrDao->getAllQualificationsForRound($round->getID());

        // Output general round info
        $output = "
            <div class='row py-3'>
                <div class='col-sm py-1 my-auto' style='vertial-align: middle'>
                    <h4>".($index+1).": ".$round->getDescription()."</h4>
                </div>
                <div class='col-sm-2 py-1 my-auto'>
                    <button data-toggle='collapse' data-target='#round". $index+1 ."' type='button' class='btn btn-outline-dark float-right'><i class='fas fa-chevron-down'></i></button>
                </div>
            
                <div id='round". $index+1 ."' class='collapse container mt-2".(isset($_REQUEST['round'])&&$_REQUEST['round']==$round->getID() ? ' show' : '')."'>";
        if($round->getInterviewQuestionLink()) {
            $output .= "
                    <div class='row d-block p-2 mb-3 mx-1 rounded' style='background: #ccc;'>
                        <p>Interview Questions: <a target='_blank' href='".$round->getInterviewQuestionLink()."'>".$round->getInterviewQuestionLink()."</a></p>
                    </div>";
        }

        // Start output for qualification evaluation table
        $output .= "
            <div class='row px-3' style='overflow: scroll'>
                <h6 class='ml-2'>Group Evaluations</h6>
                <table style='border: 1px solid black; min-width: calc(100% - 4px); margin-left: 2px;'>
                    <tr class='border border-dark'>
                        <th class='p-1'>Qualification</th>";
        foreach($users as $user) {
            $output .= "<th class='p-1 text-center'>".$user->getFirstName()." ".$user->getLastName()."</th>\n";
        }
        $output .= "</tr>";

        // Output each qualification row in table
        foreach($qualifications as $qualification) {
            $output .= "<tr class='border border-dark'>";
    
            $output .= "<td class='p-1'>
                <button type='button' data-toggle='modal' data-target='#qualModal".$qualification->getID()."' class='btn btn-link text-primary text-left py-2".($qualification->getLevel() == 'Minimum' ? " font-weight-bold" : "")."'>
                    ".$qualification->getDescription()."
                </button></td>";
            foreach($users as $user) {
                $rating = $ffqDao->getQualStatusName($user->getID(), $candidate->getID(), $round->getID(), $qualification->getID());
                $output .= "<td class='p-1 text-center".($rating == 'Does Not Meet' ? ' text-danger' : '')."'>".($rating ? $rating : '--')."</td>";
            }
                           
            $output .= "</tr>";
        }

        // Output notes row in table
        $output .= "<tr class='bg-light'>
                        <td class='p-1'>Notes</td>";
        foreach($users as $user) {
            $feedback = $feedbackDao->getFeedbackForUser($user->getID(), $candidateID, $round->getID());
            if($feedback) {
                $feedbackFiles = $feedbackFileDao->getAllFilesForFeedback($feedback->getID());
                $output .= "<td class='p-1'>".$feedback->getNotes();
                foreach($feedbackFiles as $feedbackFile) {
                    $output .= "<br><a target='_blank' href='uploads/feedback/".$feedbackFile->getFileName()."'>".$feedbackFile->getFileName()."</a>";
                }
                $output .= "</td>\n";
            } else {
                $output .= "<td class='p-1'></td>";
            }
        }
        $output .= "</tr>";

        // End output for the qualification evaluation table
        $output .= "</table>
            </div>";
        
        // Group Notes
        $roundNote = $candidateRoundNoteDao->getCandidateNotesForRound($candidate->getID(), $round->getID());
        if($roundNote === false) {
            $roundNote = ' ';
        } else {
            $roundNote = $roundNote->getNotes(); // Avoid issues with string vs object below
        }
        $output .= "
            <div class='row p-2 mb-3 rounded'>
                <div class='col mt-2'>
                    <h6>Group Notes</h6>";
        if(checkRoleForPosition('Search Chair', $position?->getID())) {
            $output .= "<textarea class='form-control' onchange='groupNotes(this, \"".$round->getID()."\")'>$roundNote</textarea>";
        } else {
            $output .= "<p class='form-control' style='height:auto'>$roundNote</p>";
        }
        $output .= "
                </div>
            </div>";
        
        // User notes & files
        $feedback = $feedbackDao->getFeedbackForUser($_SESSION['userID'], $candidateID, $round->getID());
        if($feedback !== false) {
            $feedbackFiles = $feedbackFileDao->getAllFilesForFeedback($feedback->getID());
            $output .= "
                <div class='row p-2 mb-3 rounded'>
                    <div class='col-sm-8 mt-2'>
                        <h6>My Notes</h6>
                        <p class='form-control' style='height:auto'>".$feedback->getNotes()." </p>
                    </div>
                    <div class='col-sm-4 mt-2'>
                        <h6>My Uploads</h6>
                        <ul id='feedbackFiles".$feedback->getID()."'>";
            if($feedbackFiles) {
                foreach($feedbackFiles as $feedbackFile) {
                    $output .= "<li><a target='_blank' href='uploads/feedback/".$feedbackFile->getFileName()."'>".$feedbackFile->getFileName()."</a></li>\n";
                }
            }
            $output .= "</ul>
                    </div>
                </div>";
        }
            
        $output .= "</div></div>"; // End of this round

        if($index < count($rounds) - 1) {
            $output .= "<hr style='border-color: black; margin: 0px'>";
        } // Add line to seperate rounds

        echo $output;
    }

    ?>    
</div>

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title w-100 text-center" id="exampleModalLongTitle">Update Candidate Status</h5>
        </div>
        <div class="modal-body">
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Responsible Party Description</h6></div>
                <input id="responsiblePartyDesc" class="form-control" placeholder="Why you're the right person to decide the candidate's status" value="<?php echo $candidate->getCandidateStatus()?->getResponsiblePartyDescription() ?>">
            </div>
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Disposition</h6></div>
                <select id="disposition" class="custom-select">
                    <option <?php echo ($candidate->getCandidateStatus()?->getName() ? "" : "selected") ?> >--</option>
                    <?php 
                        $candidateStatusOptions = $candidateDao->getActiveCandidateStatusOptions();
                        foreach($candidateStatusOptions as $cso) {
                            echo "<option value='".$cso->getID().($candidate->getCandidateStatus()?->getName() == $cso->getName() ? "' selected" : "'").">".$cso->getName()."</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Disposition Reason</h6></div>
                <input id="reason" class="form-control" value="<?php echo $candidate->getCandidateStatus()?->getSpecificDispositionReason() ?>">
            </div>
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Notification Method</h6></div>
                <input id="notificationMethod" class="form-control" placeholder="How the candidate was informed of the decision" value="<?php echo $candidate->getCandidateStatus()?->getHowNotified() ?>">
            </div>
            <div class="input-group my-2">
                <div class="input-group-prepend"><h6 class="input-group-text">Comments</h6></div>
                <textarea id="comments" class="form-control"><?php echo $candidate->getCandidateStatus()?->getComments() ?></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button id="closeStatusModal" type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel </button>
            <button type="button" class="btn btn-danger" onclick="candidateStatus(this)">Update</button>
        </div>
    </div>
  </div>
</div>

<?php include_once PUBLIC_FILES . '/modules/qualificationModal.php'; ?>

<?php include_once PUBLIC_FILES . '/modules/emailModal.php'; ?>

<script>
    const CANDIDATE_ID = (new URL(window.location.href)).searchParams.get('id');

    function candidateStatus(thisVal) {
        data = {
            action: 'createOrUpdateStatus',
            candidateID: CANDIDATE_ID,
            status: document.getElementById('disposition').value,
            responsiblePartyDesc: document.getElementById('responsiblePartyDesc').value,
            disposition: document.getElementById('disposition').value,
            reason: document.getElementById('reason').value,
            notificationMethod: document.getElementById('notificationMethod').value,
            comments: document.getElementById('comments').value
        }

        thisVal.disabled = true;

        api.post('/candidate.php', data).then(res => {
            document.getElementById('closeStatusModal').click();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function groupNotes(thisVal, roundID) {
        data = {
            action: 'createOrUpdateRoundNotes',
            candidateID: CANDIDATE_ID,
            roundID: roundID,
            notes: thisVal.value
        }

        thisVal.disabled = true;

        api.post('/candidate.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
