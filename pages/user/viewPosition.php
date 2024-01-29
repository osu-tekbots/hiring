<?php
include_once '../../bootstrap.php';

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['admin', 'user']), 'It looks like you\'re not logged in. Please log in before viewing positions.', true);
allowIf(!is_null($_REQUEST['id']), 'It looks like your request failed to specify a position to pull data for.', true);
allowIf(checkRoleForPosition('Any', $_REQUEST['id']), 'It looks like you\'re not on the committee for that position. Please speak to the committee\'s search chair if you believe you should be added.', true); // Implicitly verifies that position exists
allowIf(!checkRoleForPosition('Inactive', $_REQUEST['id']) || verifyPermissions('admin'), 'It looks like you\'re no longer on the committee for that position. Please speak to the committee\'s search chair if you believe you should still have access.', true); // Admins are always true for first comparison


use DataAccess\CandidateDao;
use DataAccess\CandidateRoundNoteDao;
use DataAccess\PositionDao;
use DataAccess\RoundDao;
use DataAccess\FeedbackDao;
use DataAccess\QualificationForRoundDao;
use DataAccess\FeedbackForQualDao;

define('SORT_METHOD', isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'name');   // Default to sorting by name; have to pick something to avoid undefined errors

$positionDao = new PositionDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$candidateRoundNoteDao = new CandidateRoundNoteDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$feedbackDao = new FeedbackDao($dbConn, $logger);
$qualForRoundDao = new QualificationForRoundDao($dbConn, $logger);
$feedbackForQualDao = new FeedbackForQualDao($dbConn, $logger);

$position = $positionDao->getPosition($_REQUEST['id']);

allowIf($position, 'It looks like you tried to view a position that doesn\'t exist.', true);

$candidates = $candidateDao->getCandidatesByPositionId($_REQUEST['id'], SORT_METHOD);

// Prevent unverified users from accessing the whole site
allowIf($position->getStatus() != 'Requested' || verifyPermissions('admin'), "It looks like this position hasn't been approved yet. Please request approval from the site admins to view this position.", true);
allowIf($position->getStatus() != "Completed" || checkRoleForPosition('Search Chair', $_REQUEST['id']), "It looks like that position is no longer interviewing. Please speak to the committee's search chair if you believe it should be reopened.", true);

$title = 'View Position';
include_once PUBLIC_FILES . '/modules/header.php';

include_once PUBLIC_FILES."/modules/breadcrumb.php";
renderBreadcrumb(["./pages/user/dashboard.php"=>"Dashboard"], $position->getTitle());

/**
 * Determines the last round that the committee has completed for the given candidate. 
 * Used to determine the status to display.
 * 
 * @param string $candidateID The candidate to check the status of
 * 
 * @return Model\Round|bool The last round that the committee completed a review for, or `false` if 
 *  they haven't finished any yet
 */
function determineLastFinishedRound($roundDao, $candidateRoundNoteDao, $candidateID) {
    $lastRound = false;
    $rounds = $roundDao->getAllRoundsByPositionId($_REQUEST['id']);

    foreach($rounds as $round) {
        $decision = $candidateRoundNoteDao->getCandidateNotesForRound($candidateID, $round->getID());
        if(!$decision | ($decision && !$decision->getDecision())) continue;

        $lastRound = $round;
    }

    return $lastRound;
}

/**
 * Determines the first round that the committee has not completed for the given candidate. 
 * Used to determine the review button text to display.
 * 
 * @param string $candidateID The candidate to check the status of
 * 
 * @return Model\Round|bool The first round that the committee has not completed a review for, or 
 *  `false` if they have finished all rounds already
 */
function determineNextRound($roundDao, $candidateRoundNoteDao, $candidateID) {
    $nextRound = false;
    $rounds = $roundDao->getAllRoundsByPositionId($_REQUEST['id']);

    foreach($rounds as $round) {
        $decision = $candidateRoundNoteDao->getCandidateNotesForRound($candidateID, $round->getID());
        if($decision && $decision->getDecision()) continue;
        
        $nextRound = $round;
        break;
    }

    return $nextRound;
}
?>

<br><br><br>
<div class="container" style="border: 2px solid black">
    <div class="row py-3" style="border: 1px solid black">
        <div class="col-3">
            <div class="form-check d-inline-block">
                <button class="btn btn-outline-info" onclick="swapSort();">Sort By <?php 
                    echo SORT_METHOD == 'appDate' ? 'Name' : 'Date Applied';
                ?></button>
            </div>
        </div>
        <div class="col">
            <h2 class="my-auto w-100 text-center">Candidates</h2>
        </div>
        <div class="col-3">
            <?php
                if($position->getStatus() == 'Open' && checkRoleForPosition('Search Chair', $_REQUEST['id'])) {
                    echo "<button id='startInterviewingBtn' type='button' class='btn btn-outline-success float-right mb-2'>Start Interviewing</button>";
                }
            ?>
            <div class="form-check d-inline-block">
                <input type="checkbox" class="form-check-input" id="hideDisqualified">
                <label class="form-check-label" for="hideDisqualified">Hide Disqualified Candidates</label>
            </div>
        </div>
    </div>
    <?php
        if(count($candidates)) {
            foreach($candidates as $candidate) {
                $status = $candidate->getCandidateStatus()?->getName();
                $lastRound = false;
                $lastRoundQuery = '';
                $disqualified = (isset($status) && $status != "Hired"); // Set initially here before $status is overwritten
                $finalDecision = isset($status);
                $statusColor = ($status == null ? "" : ($status == "Hired" ? "text-success" : "text-danger")); // Set here to all 'in progress' statuses are black
                
                if($status == null) {
                    $lastRound = determineLastFinishedRound($roundDao, $candidateRoundNoteDao, $candidate->getID());
                    if($lastRound === false) {
                        $status = "No Rounds Completed";
                    } else {
                        $status = "Completed ".$lastRound->getName();
                        $lastRoundQuery = '&round='.$lastRound->getID();
                    }
                }

                $nextRoundBtnStyle = "btn-primary";
                $nextRound = determineNextRound($roundDao, $candidateRoundNoteDao, $candidate->getID());
                $nextRoundQuery = '';
                if($nextRound === false) {
                    $nextRound = "Update Reviews";
                    $nextRoundBtnStyle = "btn-warning";
                } else {
                    $nextRoundQuery = "&round=".$nextRound->getID();
                    $nextRound = "Complete ".$nextRound->getName();
                }

                $lastRoundStatus = "";
                if($lastRound !== false)
                    $lastRoundStatus = $candidateRoundNoteDao->getCandidateNotesForRound($candidate->getID(), $lastRound->getID())?->getDecision();
                $disqualified = $disqualified || ($lastRoundStatus == "Disqualified"); // Update if the candidate has been marked "Disqualified" but Final Disposition is not yet set
                
                // NOTE: Below, date '-0001-11-30' seems random, but is the format result for the timestamp '0000-00-00 00:00:00' in the database
                $output = "
                <div class='row py-3 candidate' style='border: 1px solid black' ".($disqualified ? "data-disqual" : "").">
                    <div class='col-sm-2 my-auto' style='vertial-align: middle'>
                        <h4>".$candidate->getFirstName()." ".$candidate->getLastName()."</h4>
                    </div>
                    <div class='col-sm my-auto'>
                        <h5 class='$statusColor'>$status</h5>
                    </div>
                    <div class='col-sm-2 my-auto'>
                        <p>".($candidate->getDateApplied()?->format('Y-m-d') && $candidate->getDateApplied()?->format('Y-m-d') != '-0001-11-30' ? 'Applied: '.$candidate->getDateApplied() ->format('m-d-Y') : '')."</p>
                    </div>
                    <div class='col-sm-2 my-auto'>";
                if(!$finalDecision && ($position->getStatus() == 'Interviewing' || $position->getStatus() == 'Completed'))
                    $output .= "<a href='user/reviewCandidate.php?id=".$candidate->getID()."$nextRoundQuery' class='btn $nextRoundBtnStyle float-right'>$nextRound</a>";

                $output .= "
                </div>
                    <div class='col-sm-2 my-auto'>";

                if($position->getStatus() == 'Interviewing' || $position->getStatus() == 'Completed') {
                    $output .= "<a href='user/viewCandidateSummary.php?id=".$candidate->getID()."$nextRoundQuery' class='btn btn-outline-primary float-right'>View All Reviews</a>";
                }

                $output .= "</div>
                </div>";

                echo $output;
            }
        } else {
            echo "
            <div class='row py-3' style='border: 1px solid black'>
                <h4 class='col text-center'>There are no candidates for this position.</h4>
            </div>";
        }
    ?>
</div>

<script>
    const POSITION_ID = (new URL(window.location.href)).searchParams.get('id');

    document.getElementById('startInterviewingBtn')?.addEventListener('click', e => {
        if(!confirm('Are you sure you want to change the position\'s status to "Interviewing"? This will restrict your options for modifying this position and allow committee members to begin submitting feedback for candidates.'))
            return false;

        let data = {
            action: 'startInterviewing',
            id: POSITION_ID
        };

        e.target.disabled = true;

        api.post('/position.php', data).then(res => {
            snackbar(res.message, 'success');
            location.reload();
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => e.target.disabled = false);
    });

    document.getElementById('hideDisqualified').addEventListener('input', e => {
        let disqualified = document.querySelectorAll('.candidate[data-disqual]');

        if(e.target.checked) {
            // Hide disqualified candidates
            for(let i = 0; i < disqualified.length; i++) {
                disqualified[i].classList.add('d-none');
            }
        } else {
            // Show disqualified candidates
            for(let i = 0; i < disqualified.length; i++) {
                disqualified[i].classList.remove('d-none');
            }
        }
    });

    function swapSort() {
        const currentMethod = (new URL(window.location.href)).searchParams.get('sort');
        const newMethod = (currentMethod == 'appDate') ? 'name' : 'appDate';

        const basePath = location.protocol + '//' + location.host + location.pathname;
        
        const path = basePath + '?' + 'id=' + POSITION_ID + '&sort=' + newMethod;

        window.location.replace(path); 
    }
</script>

<?php
include_once PUBLIC_FILES . '/modules/footer.php';
?>
