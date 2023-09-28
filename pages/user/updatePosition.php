<?php

include_once "../../bootstrap.php";

if(!isset($_SESSION)) {
    @session_start();
}

// Make sure the user is logged in and allowed to be on this page
include_once PUBLIC_FILES . '/lib/authorize.php';
allowIf(verifyPermissions(['admin', 'user']), 'It looks like you\'re not signed in. Please sign in before updating positions.', true);
allowIf(!is_null($_REQUEST['id']), 'It looks like your request failed to specify a position to update.', true);
allowIf(checkRoleForPosition('Search Chair', $_REQUEST['id']), 'It looks like you\'re not the Search Chair for that position. Please speak to the Search Chair about the changes you believe need to be made.', true);

$title = 'Update Position';
include_once PUBLIC_FILES."/modules/header.php";

use DataAccess\CandidateDao;
use DataAccess\CandidateFileDao;
use DataAccess\PositionDao;
use DataAccess\QualificationDao;
use DataAccess\QualificationForRoundDao;
use DataAccess\RoundDao;
use DataAccess\RoleDao;
use DataAccess\UserDao;

$positionDao = new PositionDao($dbConn, $logger);
$qualificationDao = new QualificationDao($dbConn, $logger);
$roundDao = new RoundDao($dbConn, $logger);
$roleDao = new RoleDao($dbConn, $logger);
$userDao = new UserDao($dbConn, $logger);
$candidateDao = new CandidateDao($dbConn, $logger);
$candidateFileDao = new CandidateFileDao($dbConn, $logger);
$qualForRoundDao = new QualificationForRoundDao($dbConn, $logger);

$position = $positionDao->getPosition($_REQUEST['id']);

allowIf($position, 'It looks like you tried to edit a position that doesn\'t exist.', true);

$qualifications = $qualificationDao->getQualificationsForPosition($position->getID());
$rounds = $roundDao->getAllRoundsByPositionId($_REQUEST['id']);
$roundNum = range( 1, count($rounds) ); 
$roles = $roleDao->getAllRoles();
$members = $roleDao->getAllPositionMembers($_REQUEST['id']);
$allUsers = $userDao->getAllUsers();
$candidates = $candidateDao->getCandidatesByPositionId($_REQUEST['id']);

// Prevent unverified users from accessing page with file uploads
allowIf($position->getStatus() != 'Requested' || verifyPermissions('admin'), "It looks like this position hasn't been approved yet. Please request approval from the site admins to edit this position.", true);

include_once PUBLIC_FILES."/modules/breadcrumb.php";
renderBreadcrumb(["./pages/user/dashboard.php"=>"Dashboard"], $title);

$candidateTemplate = <<<HTML
    <div class="container border bg-light m-1 p-2">
        <!-- Header -->
        <div class="row m-1">
            <div class="col-2"></div>
            <div class="col"><h4 class="text-center w-100 pt-1" id="candidateName{{num}}">{{firstname}} {{lastname}}</h4></div>
            <div class="col-2 pr-1"><button data-toggle='collapse' data-target='#candidate{{num}}' type='button' class='btn btn-outline-dark float-right'><i class='fas fa-chevron-down'></i></button></div>
        </div>

        <!-- Body -->
        <div id="candidate{{num}}" class='collapse pt-1'>
            <!-- Seperate out file upload input events -->
            <div oninput="setActive(this, true)">
                <!-- Name -->
                <div class="row m-1">
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateFirstName{{num}}" class="input-group-text">First Name</label></div>
                        <input class="form-control" id="candidateFirstName{{num}}" name="candidateFirstName{{num}}" value="{{firstname}}">
                    </div>
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateLastName{{num}}" class="input-group-text">Last Name</label></div>
                        <input class="form-control" id="candidateLastName{{num}}" name="candidateLastName{{num}}" value="{{lastname}}">
                    </div>
                </div>
                <!-- Contact Info -->
                <div class="row m-1">
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateEmail{{num}}" class="input-group-text">Email</label></div>
                        <input class="form-control" id="candidateEmail{{num}}" name="candidateEmail{{num}}" value="{{email}}">
                    </div>
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidatePhone{{num}}" class="input-group-text">Phone</label></div>
                        <input class="form-control" id="candidatePhone{{num}}" name="candidatePhone{{num}}" value="{{phone}}">
                    </div>
                </div>
                <!-- Other -->
                <div class="row m-1">
                    <!-- Location -->
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateLocation{{num}}" class="input-group-text">Location</label></div>
                        <input class="form-control" id="candidateLocation{{num}}" name="candidateLocation{{num}}" value="{{location}}">
                    </div>
                    <!-- Applied Date -->
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateAppliedDate{{num}}" class="input-group-text">Date Applied</label></div>
                        <input type="date" class="form-control" id="candidateAppliedDate{{num}}" name="candidateAppliedDate{{num}}" value="{{appliedDate}}">
                    </div>
                </div>
                <!-- Buttons -->
                <div class="row m-1">
                    <div class="w-100 mt-1">
                        <button class="btn btn-primary float-right" type="button" onclick="updateCandidate(this, {{num}}, '{{id}}')" disabled data-update>Saved</button>
                        <button class="btn btn-danger float-right mx-2" type="button" onclick="deleteCandidate(this, {{num}}, '{{id}}')"><i class="fas fa-trash mr-2"></i>Delete</button>
                    </div>
                </div>
            </div>
            
            <!-- Candidate File Section -->
            <div class="row"> <!-- Force this section to be below the button --> </div>
            <div class="row m-1" oninput="this.getElementsByTagName('button')[0].disabled = false;">
                <!-- File Purpose -->
                <div class="col input-group p-1">
                    <div class="input-group-prepend"><label for="candidateFilePurpose{{num}}" class="input-group-text">New File Purpose</label></div>
                    <input class="form-control" id="candidateFilePurpose{{num}}" name="candidateFilePurpose{{num}}" value="{{filename}}" placeholder="Resume, CV, etc.">
                </div>
                <!-- File Upload -->
                <div class="col p-1">
                    <div class="custom-file">
                        <label id="candidateUploadFileName{{num}}" for="candidateUploadFileInput{{num}}" class="custom-file-label">Upload New Candidate File</label>
                        <input type="file" id="candidateUploadFileInput{{num}}" class="custom-file-input" name="candidateUploadFileInput{{num}}" onchange="updateUploadFileName(this)" accept=".jpeg,.jpg,.png,.bmp,.JPG,.JPEG,.PNG,.BMP,.heic,.HEIC,.pdf,.PDF,.docx,.DOCX">
                    </div>
                </div>
                <div class="col-auto p-1">
                    <button type="button" class="btn btn-primary" onclick="uploadCandidateFile(this, {{num}}, '{{id}}');" disabled>Upload File</button>
                </div>
            </div>
            <!-- Uploaded Files Section -->
            <div class="row m-1">
                <div id="candidateFilesDiv{{num}}" class="col py-1 px-3">
                    {{candidateFiles}}
                </div>
            </div>
        </div>
    </div>

HTML;

$qualificationTemplate = <<<HTML
    <div id="qualification{{id}}" class="container border bg-light m-1 p-2">
        <!-- Header -->
        <div class="row m-1">
            <div class="col-2"></div>
            <div class="col-8"><h4 class="text-center pt-1" id="qualificationName{{num}}" style="height: 30px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">{{description}}</h4></div>
            <div class="col-2 pr-1"><button data-toggle='collapse' data-target='#qualification{{num}}' type='button' class='btn btn-outline-dark float-right'><i class='fas fa-chevron-down'></i></button></div>
        </div>

        <!-- Body -->
        <div id="qualification{{num}}" class='collapse pt-1' oninput="setActive(this, true)">
            <!-- Description -->
            <div class="input-group p-1">
                <div class="input-group-prepend"><label for="qualDescription{{num}}" class="input-group-text">Description</label></div>
                <textarea class="form-control" id="qualDescription{{num}}" name="qualDescription{{num}}">{{description}}</textarea>
            </div>
            <!-- Screening Criteria -->
            <div class="input-group p-1">
                <div class="input-group-prepend"><label for="screeningCriteria{{num}}" class="input-group-text">Screening Criteria</label></div>
                <textarea class="form-control" id="screeningCriteria{{num}}" name="screeningCriteria{{num}}">{{screeningCriteria}}</textarea>
            </div>
            <!-- Strength Indicators -->
            <div class="input-group p-1">
                <div class="input-group-prepend"><label for="strengthIndicators{{num}}" class="input-group-text">Strength Indicators</label></div>
                <textarea class="form-control" id="strengthIndicators{{num}}" name="strengthIndicators{{num}}">{{strengthIndicators}}</textarea>
            </div>

            <!-- Dropdowns -->
            <div class="row m-1">
                <!-- Level -->
                <div class="col-md input-group p-1">
                    <div class="input-group-prepend"><label for="level{{num}}" class="input-group-text">Level</label></div>
                    <select class="custom-select" id="level{{num}}" name="level{{num}}">
                        <option {{levelPreferred}}>Preferred</option>
                        <option {{levelMinimum}}>Minimum</option>
                    </select>
                </div>
                <!-- Priority -->
                <div class="col-md input-group p-1">
                    <div class="input-group-prepend"><label for="priority{{num}}" class="input-group-text">Priority</label></div>
                    <select class="custom-select" id="priority{{num}}" name="priority{{num}}">
                        <option {{priorityLow}}>Low</option>
                        <option {{priorityMed}}>Medium</option>
                        <option {{priorityHigh}}>High</option>
                    </select>
                </div>
                <!-- Transferable -->
                <div class="col-md input-group p-1">
                    <div class="input-group-prepend"><label for="transferable{{num}}" class="input-group-text">Transferable?</label></div>
                    <select class="custom-select" id="transferable{{num}}" name="transferable{{num}}">
                        <option {{transferableFalse}}>No</option>
                        <option {{transferableTrue}}>Yes</option>
                    </select>
                </div>
            </div>

            <!-- Update button -->
            <div class="row mt-1">
                <div class="col">
                    <button class="btn btn-primary float-right" type="button" onclick="updateQualification(this, {{num}}, '{{id}}')" disabled data-update>Saved</button>
                    <button class="btn btn-danger float-right mx-2" type="button" onclick="deleteQualification(this, '{{id}}')"><i class="fas fa-trash mr-2"></i>Delete</button>
                </div>
            </div>
        </div>
    </div>
HTML;

$roundTemplate = <<<HTML
    <div class="container border bg-light m-1 p-2" id="round{{num}}">
        <!-- Header -->
        <div class="row m-1">
            <div class="col-2"></div>
            <div class="col"><h4 class="text-center w-100 pt-1" style="height: 30px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">{{num}}: <span id="roundName{{num}}">{{name}}</span></h4></div>
            <div class="col-2 pr-1"><button data-toggle='collapse' data-target='#roundCollapse{{num}}' type='button' class='btn btn-outline-dark float-right'><i class='fas fa-chevron-down'></i></button></div>
        </div>

        <!-- Body -->
        <div id="roundCollapse{{num}}" class='collapse pt-1' oninput="setActive(this, true)">
            <!-- Name -->
            <div class="input-group p-1">
                <div class="input-group-prepend"><label for="rndName{{num}}" class="input-group-text">Name</label></div>
                <input class="form-control" id="rndName{{num}}" name="rndName{{num}}" value="{{name}}">
            </div>
            <!-- Interview Questions Link -->
            <BR>
            <div class="alert alert-info d-flex align-items-center justify-content-center py-1 mb-1 mx-2">
                <i class="fas fa-info-circle"></i>
                <p class="ml-2 mb-0">Please insert a link to the external interview question page <b>OR</b> upload a file of the interview question.</p>
            </div>
            <div class="row m-1">
                
                <div class="col-md input-group p-1">
                    <div class="input-group-prepend"><label for="rndLink{{num}}" class="input-group-text">Interview Questions Link</label></div>
                    <input class="form-control" id="rndLink{{num}}" name="rndLink{{num}}" value="{{link}}">
                </div>
                <p class="col-md-1 text-center w-100 my-auto">OR</p>
                <div class="col-md input-group p-1">
                    <div class="custom-file">
                        <div class="input-group-prepend"><label for="rndFile{{num}}" class="input-group-text">Interview Questions File Upload</label></div>
                        <input class="custom-file-input" type="file" id="rndFile{{num}}" name="rndFile{{num}}" accept=".jpeg,.jpg,.png,.bmp,.JPG,.JPEG,.PNG,.BMP,.heic,.HEIC,.pdf,.PDF,.docx,.DOCX">
                    </div>
                    <!-- Interview Questions Files -->
                    <div id="interviewQuestionsFilesDiv" class="col py-1 px-3"></div>
                </div>
            </div>

            <div class="w-100 mt-1">
                <button class="btn btn-primary float-right" type="button" onclick="updateRound(this, {{num}}, '{{id}}')" disabled data-update>Saved</button>
                <button class="btn btn-danger float-right mx-2" type="button" onclick="deleteRound(this, {{num}}, '{{id}}')"><i class="fas fa-trash mr-2"></i>Delete</button>
            </div>
        </div>
    </div>
HTML;

$memberTemplate = <<<HTML
    <div id="member{{id}}" class="container border bg-light m-1 p-2">
        <div class="row m-1" {{oninput}}>
            <!-- Name -->
            <div class="col input-group p-1">
                <div class="input-group-prepend"><label for="member{{num}}" class="input-group-text">Name</label></div>
                <select class="custom-select selectUser" id="member{{num}}" name="member{{num}}" {{disableUserSelect}} onchange="checkAddUser(this);">
                    {{userOptions}}
                </select>
            </div>
            <!-- Role -->
            <div class="col input-group p-1">
                <div class="input-group-prepend"><label for="role{{num}}" class="input-group-text">Role</label></div>
                <select class="custom-select" id="role{{num}}" name="role{{num}}">
                    {{roleOptions}}
                </select>
            </div>
            <div class="col-auto p-1">
                {{jsButton}}
            </div>
        </div>
    </div>
HTML;
?>

<div class="container py-4">
    <h2 class="text-center">Update Position</h2>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        This form allows you, as the Search Chair, to modify this position. You will need to click 
        the green <kbd class="bg-success">Start Interviewing</kbd> button before your committee can begin submitting feedback.
    </div> 

    <!-- General Position -->
    <div class="row border border-dark rounded my-3 p-2" oninput="setActive(this, true)">
        <h3 class="text-center w-100">General Position Information</h3>

        <!-- Position Title -->
        <div class="input-group p-1">
            <div class="input-group-prepend"><label for="title" class="input-group-text">Position Title</label></div>
            <input class="form-control" id="title" name="title" value="<?php echo $position->getTitle() ?>" >
        </div>
        <!-- Posting Link -->
        <div class="input-group p-1">
            <div class="input-group-prepend"><label for="postingLink" class="input-group-text">Link to Internal Posting Site</label></div>
            <input class="form-control" id="postingLink" name="postingLink" value="<?php echo $position->getPostingLink() ?>" >
        </div>
        <!-- Email Address -->
        <div class="input-group p-1">
            <div class="input-group-prepend"><label for="email" class="input-group-text">Committee Email Address</label></div>
            <input class="form-control" id="email" name="email" value="<?php echo $position->getCommitteeEmail() ?>" >
        </div>
        
        <!-- Buttons -->
        <div class="w-100 mt-1">
            <button id="updatePositionBtn" class="btn btn-primary float-right" type="button" disabled data-update>Saved</button>
            <?php
                if($position->getStatus() == 'Open')
                    echo '<button id="startInterviewingBtn" class="btn btn-outline-success float-right mx-2" type="button">Start Interviewing</button>';
            ?>
        </div>
    </div>

    <!-- Candidates -->
    <div class="row border border-dark rounded my-3 p-2">
        <div class="col-sm-2 d-block"><!-- Used to keep title centered --></div>
        <h3 class="col text-center w-100 pt-2">Candidates</h3>
        <div class="col-sm-2">
            <button type="button" id="addCandidate" class="btn btn-outline-primary float-right my-2">Add Candidate</button>
        </div>

        <?php
            foreach($candidates as $index=>$c) {
                $files = $candidateFileDao->getAllFilesForCandidate($c->getID());

                $output = $candidateTemplate;

                $candidateFileHTML = '';
                foreach($files as $f) {
                    $candidateFileHTML .= '
                        <div class="row" id="candidateFile'.$f->getID().'">
                            <div class="col input-group p-1">
                                <div class="input-group-prepend"><label for="candidateFilePurpose'.$f->getID().'" class="input-group-text">File Purpose</label></div>
                                <input id="candidateFilePurpose'.$f->getID().'" type="text" class="form-control" value="'.$f->getPurpose().'" onchange="updateCandidateFile(this, \''.$f->getID().'\')">
                            </div>
                            <div class="col input-group p-1">
                                <div class="input-group-prepend"><label for="candidateFileName'.$f->getID().'" class="input-group-text">File Name</label></div>
                                <p id="candidateFileName'.$f->getID().'" type="text" class="form-control" style="background-color: #e9ecef;" ><a class="text-primary" target="_blank" href="./uploads/candidate/'.$f->getFileName().'">'.$f->getFileName().'</a></p>
                            </div>
                            <div class="col-auto p-1">
                                <button class="btn btn-danger mx-auto" type="button" onclick="removeCandidateFile(this, \''.$f->getID().'\');" id="delete'.$f->getID().'">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>';
                }
                $output = str_replace("{{candidateFiles}}", $candidateFileHTML ?? "", $output);

                $output = str_replace("{{num}}", $index + 1, $output);
                $output = str_replace("{{id}}", $c->getID(), $output);
                $output = str_replace("{{firstname}}", $c->getFirstName() ?? "", $output);
                $output = str_replace("{{lastname}}", $c->getLastName() ?? "", $output);
                $output = str_replace("{{email}}", $c->getEmail() ?? "", $output);
                $output = str_replace("{{phone}}", $c->getPhoneNumber() ?? "", $output);
                $output = str_replace("{{location}}", $c->getLocation() ?? "", $output);
                $output = str_replace("{{appliedDate}}", $c->getDateApplied()?->format('Y-m-d') ?? "", $output);

                $output = preg_replace("/[{]{2}[a-zA-z]*[}]{2}/", "", $output); // Remove any unused replacements

                echo $output;
            }
        
        ?>

        <template id="candidateForm">
            <?php 
                $output = $candidateTemplate;

                $output = preg_replace("/[{]{2}(?!id[}]{2})(?!num[}]{2})[a-zA-z]*[}]{2}/", "", $output); // Remove any other replacements that aren't {{id}} or {{num}}

                echo $output;
            ?>
        </template>
    </div>

    <!-- Qualifications -->
    <div class="row border border-dark rounded my-3 p-2">
        <div class="col-sm-2 d-block"><!-- Used to keep title centered --></div>
        <h3 class="col text-center w-100 pt-2">Qualifications</h3>
        <div class="col-sm-2">
            <button type="button" id="addQual" class="btn btn-outline-primary float-right my-2">Add Qualification</button>
        </div>
        
        <?php
            // Add all currently-existing qualifications using the HTML template
            foreach($qualifications as $index=>$qualification) {
                $output = $qualificationTemplate;

                // Fill in template
                $output = str_replace("{{num}}", $index + 1, $output);
                $output = str_replace("{{id}}", $qualification->getID(), $output);
                $output = str_replace("{{description}}", $qualification->getDescription() ?? "", $output);
                $output = str_replace("{{screeningCriteria}}", $qualification->getScreeningCriteria() ?? "", $output);
                $output = str_replace("{{strengthIndicators}}", $qualification->getStrengthIndicators() ?? "", $output);
                
                // $output = str_replace("{{roundNum}}", $roundNum[$index], $output); // What was this? -Nate
                
                // Mark current options as selected
                switch($qualification->getLevel()) {
                    case 'Minimum':
                        $output = str_replace("{{levelMinimum}}", "selected", $output);
                        break;
                    case 'Preferred':
                    default:
                        $output = str_replace("{{levelPreferred}}", "selected", $output);
                }
                switch($qualification->getPriority()) {
                    case 'High':
                        $output = str_replace("{{priorityHigh}}", "selected", $output);
                        break;
                    case 'Medium':
                        $output = str_replace("{{priorityMed}}", "selected", $output);
                        break;
                    case 'Low':
                    default:
                        $output = str_replace("{{priorityLow}}", "selected", $output);
                }
                switch($qualification->getTransferable()) {
                    case 1:
                        $output = str_replace("{{transferableTrue}}", "selected", $output);
                        break;
                    case 0:
                    default:
                        $output = str_replace("{{transferableFalse}}", "selected", $output);
                }

                $output = preg_replace("/[{]{2}[a-zA-z]*[}]{2}/", "", $output); // Remove any unused replacements

                echo $output;
            }
        ?>
        
        <template id="qualForm">
            <?php
                // Update HTML template for JS to create new qualifications

                $output = $qualificationTemplate;
                $output = str_replace("{{levelPreferred}}", "selected", $output);
                $output = str_replace("{{priorityLow}}", "selected", $output);
                $output = str_replace("{{transferableFalse}}", "selected", $output);

                // Fill in options for linked rounds
                $roundOptions = "";
                foreach($rounds as $index=>$r) {
                    $roundOptions .= "<option value='".$r->getID()."'>". $index+1 ."</option>\n";
                }
                $output = str_replace("{{roundOptions}}", $roundOptions, $output);

                $output = preg_replace("/[{]{2}(?!id[}]{2})(?!num[}]{2})[a-zA-z]*[}]{2}/", "", $output); // Remove any other replacements that aren't {{id}} or {{num}}

                echo $output; 
            ?>
        </template>
    </div>

    <!-- Rounds -->
    <div class="row border border-dark rounded my-3 p-2">
        <div class="col-sm-2 d-block"><!-- Used to keep title centered --></div>
        <h3 class="col text-center w-100 pt-2">Rounds</h3>
        <div class="col-sm-2">
            <button type="button" id="addRound" class="btn btn-outline-primary float-right my-2">Add Round</button>
        </div>

        <?php
            foreach($rounds as $index=>$round) {
                $output = $roundTemplate;
                $output = str_replace("{{num}}", $index + 1, $output);
                $output = str_replace("{{id}}", $round->getID(), $output);
                $output = str_replace("{{name}}", $round->getName() ?? "", $output);
                $output = str_replace("{{link}}", $round->getInterviewQuestionLink() ?? "", $output);

                $output = preg_replace("/[{]{2}[a-zA-z]*[}]{2}/", "", $output); // Remove any unused replacements

                echo $output;
            }
        ?>
        
        <template id="roundForm">
            <?php
                //Update HTML template for JS to create new rounds

                $output = $roundTemplate;
                $output = preg_replace("/[{]{2}(?!id[}]{2})(?!num[}]{2})[a-zA-z]*[}]{2}/", "", $output); // Remove any other replacements that aren't {{id}} or {{num}}

                echo $output; 
            ?>
        </template>
    </div>

    <!-- Qualification Evaluation Rounds -->
    <div class="row border border-dark rounded my-3 p-2">
        <h3 class="col text-center w-100 pt-2">Qualification Evaluation Rounds</h3>
        <div class="w-100"></div>
        <div id="qualForRoundInput" class="container p-2 bg-light" oninput="setActive(this, true)">
            <table class="w-100">
                <tr class="border border-dark">
                    <th class="p-2">Qualification</th>
                    <?php
                        foreach($rounds as $round) {
                            echo "<th id='qualForRound_RoundName".$round->getID()."' class='p-2 text-center'>".$round->getName()."</th>";
                        }
                    ?>
                </tr>
                <?php
                    foreach($qualifications as $qualification) {
                        echo "<tr class='border border-dark text-strong' data-qualid='".$qualification->getID()."'>
                                <td id='qualForRound_QualName".$qualification->getID()."' class='p-2'>".$qualification->getDescription()."</td>";
                        foreach($rounds as $round) {
                            echo "<td class='qualForRoundCheck p-2 text-center' id='withRound".$round->getID()."'>
                                    <input type='checkbox' value='".$qualification->getID().",".$round->getID()."'".
                                        ($qualForRoundDao->getQualForRound($qualification->getID(), $round->getID()) ?
                                            ' checked' : '').">
                                </td>";
                                
                        }
                        echo "</tr>";
                    }
                ?>
            </table>
            <!-- Save button -->
            <div class="row p-1">
                <div class="col">
                    <button type="button" class="btn btn-primary float-right" onclick="updateQualForRound(this)" disabled data-update>Saved</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Board Members -->
    <div class="row border border-dark rounded my-3 p-2">
        <div class="col-sm-2 d-block"><!-- Used to keep title centered --></div>
        <h3 class="col text-center w-100 pt-2">Search Committee</h3>
        <div class="col-sm-2">
            <button type="button" id="addMember" class="btn btn-outline-primary float-right my-2">Add Member</button>
        </div>

        <!-- May be good for clarity, but need to find way to seperate it out -->
        <!-- <div class="alert alert-info container mt-2">
            <i class="fas fa-info-circle"></i>
            If you cannot find a member of your search committee in the 
            <kbd style="background: rgb(12, 84, 96);">Name</kbd> dropdown, select the 
            <kbd style="background: rgb(12, 84, 96);">** Add User**</kbd> option.
        </div> -->

        <?php
            // Add all currently-existing members using the HTML template
            foreach($members as $index=>$member) {
                $output = $memberTemplate;
                $output = str_replace("{{jsButton}}", "<button class='btn btn-primary float-right' onclick='updateMember(this, {{num}}, {{id}})' disabled data-update>Saved</button>", $output);
                $output = str_replace("{{num}}", $index + 1, $output);
                $output = str_replace("{{id}}", $member->getID(), $output);
                $output = str_replace("{{disableUserSelect}}", "disabled", $output);
                $output = str_replace("{{oninput}}", "oninput='setActive(this, true)'", $output);

                $userOptions = "";
                foreach($allUsers as $user) {
                    $userOptions .= "<option value='".$user->getID()."' ".($user->getID() == $member->getUser()->getID() ? 'selected' : '').">".$user->getFirstName()." ".$user->getLastName()."</option>\n";
                }
                $output = str_replace("{{userOptions}}", $userOptions, $output);
                
                $roleOptions = "";
                foreach($roles as $role) {
                    $roleOptions .= "<option value='".$role->getID()."' ".($role->getID() == $member->getRole()->getID() ? 'selected' : '').">".$role->getName()."</option>\n";
                }
                $output = str_replace("{{roleOptions}}", $roleOptions, $output);
                

                $output = preg_replace("/[{]{2}[a-zA-z]*[}]{2}/", "", $output); // Remove any unused replacements

                echo $output;
            }
        ?>

        <template id="committeeForm">
            <?php
                // Update HTML for JS to create new committee members

                $output = $memberTemplate;

                $output = str_replace("{{jsButton}}", "<button class='btn btn-primary float-right' onclick='saveMember(this, {{num}}, \"{{id}}\")'>Save Member</button>", $output);

                $userOptions = "<option selected>--</option>";
                $userOptions .= "<option>** Add User **</option>";
                foreach($allUsers as $user) {
                    $userOptions .= "<option value='".$user->getID()."'>".$user->getFirstName()." ".$user->getLastName()."</option>\n";
                }
                $output = str_replace("{{userOptions}}", $userOptions, $output);

                $roleOptions = "";
                foreach($roles as $role) {
                    $roleOptions .= "<option value='".$role->getID()."' ".($role->getName() == 'Member' ? 'selected' : '').">".$role->getName()."</option>\n";
                }

                $output = str_replace("{{roleOptions}}", $roleOptions, $output);
                
                $output = preg_replace("/[{]{2}(?!id[}]{2})(?!num[}]{2})[a-zA-z]*[}]{2}/", "", $output); // Remove any other replacements that aren't {{id}} or {{num}}
                
                echo $output;
            ?>
        </template>
    </div>
</div>

<!-- New User Modal Trigger -->
<button id="triggerNewUserModal" type="button" class="d-none btn btn-primary" data-toggle="modal" data-target="#newUserModal">Add New User</button>
<!-- New User Modal -->
<div class="modal fade" id="newUserModal" tabindex="-1" role="dialog" aria-labelledby="newUserModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title w-100 text-center" id="newUserModal">Add A New User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body row m-1">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    This form allows you to create a new user account in case a member of your committee has not yet 
                    logged into this site. <b>This method only supports ONID accounts</b> for security reasons. If a 
                    committee member does not have an ONID, they will need to create an account before you can add them
                    to this position.
                </div>
                <div class="input-group p-1 col-sm">
                    <div class="input-group-prepend"><label for="newFirstName" class="input-group-text">First Name</label></div>
                    <input id="newFirstName" class="form-control">
                </div>
                <div class="input-group p-1 col-sm">
                    <div class="input-group-prepend"><label for="newLastName" class="input-group-text">Last Name</label></div>
                    <input id="newLastName" class="form-control">
                </div>
                <div class="input-group p-1 col-sm">
                    <div class="input-group-prepend"><label for="newONID" class="input-group-text">ONID</label></div>
                    <input id="newONID" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal" id="closeNewUserModal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addNewUser(this)">Add User</button>
            </div>
        </div>
    </div>
</div>

<script>
    const POSITION_ID = (new URL(window.location.href)).searchParams.get('id');

    // Function for making buttons active/unactive based on input
    function setActive(element, value) {
        let button = element.querySelector('button[data-update]');

        if(value) {
            button.innerText = 'Update';
            button.disabled = false;
        } else {
            button.innerText = 'Saved';
            button.disabled = true;
        }
    }

    function addNewItem(insertElmt, templateElmt, id) {
        const serializer = new XMLSerializer();

        let numOfItems = insertElmt.getElementsByClassName('container').length;

        let clone = templateElmt.content.cloneNode(true);

        let cloneText = serializer.serializeToString(clone);;
        cloneText = cloneText.replaceAll('{{num}}', (numOfItems + 1));
        cloneText = cloneText.replaceAll('{{id}}', (id));

        // Get location to insert
        var elements = insertElmt.children;
        elements.item(3).insertAdjacentHTML('beforebegin', cloneText);

        return numOfItems + 1;
    }

    document.getElementById('addQual').addEventListener('click', e => {
        let data = {
            action: 'createQualification',
            positionID: POSITION_ID
        }

        e.target.disabled = true;

        api.post('/qualification.php', data).then(res => {
            snackbar(res.message, 'success');

            let template = document.getElementById('qualForm');
            let insert = e.target.parentNode.parentNode;
            num = addNewItem(insert, template, res.content);
            document.getElementById('qualification'+num).parentNode.id = 'qualification' + res.content;
            document.getElementById('qualification'+num).classList.add('show');
            document.getElementById('qualificationName'+num).innerText = 'New Qualification';

            // Add option to select any newly-created rounds in this qualification
            let container = document.getElementById('qualForRoundInput');
            let rows = container.getElementsByTagName('tr');
            let topRow = rows[0];
            let newHTML = `<tr class="border border-dark" data-qualid="${res.content}">
                                <td id='qualForRound_QualName${res.content}' class='p-2'></td>`;
            for(let i = 1; i < topRow.childElementCount; i++) {
                newHTML += `<td class="qualForRoundCheck p-2 text-center">
                                <input type="checkbox" value="${res.content},${topRow.children[i].id.replace('qualForRound_RoundName', '')}">
                            </td>`;
            } // Add column for each round
            newHTML += '</tr>';
            container.getElementsByTagName('tbody')[0].insertAdjacentHTML('beforeend', newHTML);
            
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => e.target.disabled = false);

    });
    document.getElementById('addRound').addEventListener('click', e => {
        let data = {
            action: 'createRound',
            positionID: POSITION_ID
        };

        e.target.disabled = true;

        api.post('/round.php', data).then(res => {
            snackbar(res.message, 'success');

            let template = document.getElementById('roundForm');
            let insert = e.target.parentNode.parentNode;
            
            let num = addNewItem(insert, template, res.content);
            document.getElementById('roundCollapse'+num).classList.add('show');
            document.getElementById('roundName'+num).innerText = 'New Round';

            // Add option to select this round for each qualification
            let rows = document.getElementById('qualForRoundInput').getElementsByTagName('tr');
            rows[0].insertAdjacentHTML('beforeend', `<th id="qualForRound_RoundName${res.content}" class="p-2 text-center">New Round</th>`);
            for(let i = 1; i < rows.length; i++) {
                rows[i].insertAdjacentHTML('beforeend', `
                    <td class='qualForRoundCheck p-2 text-center' id='withRound${res.content}'>
                        <input type='checkbox' value='${rows[i].dataset.qualid},${res.content}'>
                    </td>`);
            }
            
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => e.target.disabled = false);
    });
    document.getElementById('addCandidate').addEventListener('click', e => {
        let data = {
            action: 'createCandidate',
            positionID: POSITION_ID
        };

        e.target.disabled = true;

        api.post('/candidate.php', data).then(res => {
            snackbar(res.message, 'success');

            let template = document.getElementById('candidateForm');
            let insert = e.target.parentNode.parentNode;
            num = addNewItem(insert, template, res.content);
            document.getElementById('candidate'+num).classList.add('show');
            document.getElementById('candidateName'+num).innerText = 'New Candidate';
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => e.target.disabled = false);
    });
    document.getElementById('addMember').addEventListener('click', e => {
        let template = document.getElementById('committeeForm');
        let insert = event.target.parentNode.parentNode;
        addNewItem(insert, template, null);
    });

    document.getElementById('updatePositionBtn').addEventListener('click', e => {
        let data = {
            action: 'updatePosition',
            id: POSITION_ID,
            title: document.getElementById('title').value,
            postingLink: document.getElementById('postingLink').value,
            email: document.getElementById('email').value
        };

        e.target.disabled = true;

        api.post('/position.php', data).then(res => {
            setActive(e.target.parentNode, false);
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            e.target.disabled = false;
        });
    });

    // Suppress unneeded error if button doesn't exist
    if(document.getElementById('startInterviewingBtn')) {
        document.getElementById('startInterviewingBtn').addEventListener('click', e => {
            if(!confirm('Are you sure you want to change the position\'s status to "Interviewing"? This will restrict your options for modifying this position and allow committee members to begin submitting feedback for candidates.'))
                return false;
    
            let data = {
                action: 'startInterviewing',
                id: POSITION_ID
            };
    
            e.target.disabled = true;
    
            api.post('/position.php', data).then(res => {
                e.target.remove();
                snackbar(res.message, 'success');
            }).catch(err => {
                snackbar(err.message, 'error');
            }).finally(() => e.target.disabled = false);
        });
    }

    function updateQualification(thisVal, num, qualificationID) {
        let data = {
            action: 'updateQualification',
            id: qualificationID,
            description: document.getElementById('qualDescription'+num).value,
            screeningCriteria: document.getElementById('screeningCriteria'+num).value,
            strengthIndicators: document.getElementById('strengthIndicators'+num).value,
            level: document.getElementById('level'+num).value,
            transferable: document.getElementById('transferable'+num).value,
            priority: document.getElementById('priority'+num).value
        };

        thisVal.disabled = true;

        api.post('/qualification.php', data).then(res => {
            document.getElementById('qualificationName'+num).innerText = data.description;
            document.getElementById('qualForRound_QualName'+data.id).innerText = data.description;
            setActive(document.getElementById('qualification'+num), false);
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    function deleteQualification(thisVal, qualificationID) {
        if(!confirm("Are you sure you want to delete this qualification? This action is irreversible and will also delete all feedback linked to this qualification!"))
            return;

        let data = {
            action: 'deleteQualification',
            id: qualificationID
        };

        thisVal.disabled = true;

        api.post('/qualification.php', data).then(res => {
            document.querySelector('[data-qualid]').remove();
            document.getElementById('qualification'+data.id).remove();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }
    
    async function updateRound(thisVal, num, roundID) {
        // get link here
        var link;
        var isFile = false;
        var rndLinkValue = document.getElementById('rndLink'+num).value;
        var rndFileValue = document.getElementById('rndFile'+num).value;
        // console.log("--rndLink", rndLinkValue);
        // console.log("--rndFile", rndFileValue);
        // if the input box and file upload are both filled return false
        if (rndLinkValue !== '' && rndFileValue !== '') {
            alert('Both inputs cannot be used. Please select only one.');
            return false;
        } else if (rndLinkValue !== '') {
            link = rndLinkValue;
        } else if (rndFileValue !== '') {
            link = await uploadInterviewQuestionsFile(thisVal, num, roundID);
            isFile = true
        }

        let data = {
            action: 'updateRound',
            id: roundID,
            name: document.getElementById('rndName'+num).value,
            link: link
        };
        console.log(data)
        thisVal.disabled = true;

        api.post('/round.php', data).then(res => {
            if (isFile) {
                document.getElementById('rndLink'+num).value = link;
            }
            document.getElementById('roundName'+num).innerText = data.name;
            document.getElementById('qualForRound_RoundName'+data.id).innerText = data.name;
            setActive(document.getElementById('roundCollapse'+num), false);
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    function deleteRound(thisVal, num, roundID) {
        if(!confirm("Are you sure you want to delete this round? This action is irreversible and will also delete all feedback linked to this round!"))
            return;

        let data = {
            action: 'deleteRound',
            id: roundID
        };

        thisVal.disabled = true;

        api.post('/round.php', data).then(res => {
            let rows = document.getElementById('qualForRoundInput').getElementsByTagName('tr');
            document.querySelector(`#qualForRoundInput #qualForRound_RoundName${roundID}`).remove();
            for(let i = 1; i < rows.length; i++) {
                document.querySelector(`[data-qualid="${rows[i].dataset.qualid}"] #withRound${roundID}`).remove();
            }
            document.getElementById('round'+num).remove();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    function updateQualForRound(thisVal) {
        if(!confirm("Are you sure you wish to update Qualification Evaluation Rounds? Unlinking any qualifications and rounds will delete all reviews for that qualification in that round!"))
            return false;
        
        let elements = document.querySelectorAll('.qualForRoundCheck > input');
        let qualForRounds = [];
        for(let i = 0; i < elements.length; i++) {
            console.log(elements[i].value);
            let value = elements[i].value.split(',');
            let qualForRound = {
                qual: value[0],
                round: value[1],
                value: !!elements[i].checked
            }
            qualForRounds.push(qualForRound);
        }

        let data = {
            action: 'updateQualForRound',
            data: qualForRounds
        }

        thisVal.disabled = true;

        api.post('/qualificationForRound.php', data).then(res => {
            snackbar(res.message, 'success');
            setActive(document.getElementById('qualForRoundInput'), false);
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    // call if file is method in updateRound
    function uploadInterviewQuestionsFile(thisVal, num, roundID) {
        return new Promise((resolve, reject) => {
            var isValidFile = false;
            var file_data = $('#rndFile'+num).prop('files')[0]
            var form_data = new FormData();
            var dbFileName = "";
            form_data.append('file', file_data);
            form_data.append('action', 'uploadInterviewQuestionsFile');

            thisVal.disabled = true;

            $.ajax({
                url: './ajax/Handler.php',
                type: 'POST',
                dataType: 'json',
                /* What we are expecting back */
                contentType: false,
                processData: false,
                data: form_data,
                success: function(result) {
                    console.log("-- AJAX Result", result)
                    thisVal.disabled = false;
                    if (result["successful"] == 1) {
                        dbFileName = result["path"];
                        resolve(document.getElementsByTagName('base')[0].href + 'uploads/interviewQuestions/' + dbFileName);
                    } else if (result["successful"] == 0) {
                        // snackbar(result["string"], 'error');
                        reject(result["string"]);
                    }
                    // if (isValidFile) {
                    //     console.log("File successfully uploaded");
                    // }
                },
                error: function(result) {
                    thisVal.disabled = false;
                    isValidFile = false;
                    // snackbar(result["string"], 'error');
                    reject(result["string"]);
                }
            });
        });
	}

    function updateCandidate(thisVal, num, candidateID) {
        let data = {
            action: 'updateCandidate',
            id: candidateID,
            firstname: document.getElementById('candidateFirstName'+num).value,
            lastname: document.getElementById('candidateLastName'+num).value,
            email: document.getElementById('candidateEmail'+num).value,
            phone: document.getElementById('candidatePhone'+num).value,
            location: document.getElementById('candidateLocation'+num).value,
            dateApplied: document.getElementById('candidateAppliedDate'+num).value
        };

        thisVal.disabled = true;

        api.post('/candidate.php', data).then(res => {
            document.getElementById('candidateName'+num).innerText = data.firstname + ' ' + data.lastname;
            setActive(document.getElementById('candidate'+num), false);
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });

    }

    function deleteCandidate(thisVal, num, candidateID) {
        if(!confirm("Are you sure you want to delete this candidate? This action is irreversible and will also delete all feedback linked to this candidate!"))
            return;
        if(!confirm("If the candidate withdrew their application, you need to update their status on the Candidate Review Summary page, not delete them! Are you still sure you want to delete them?"))
            return;

        let data = {
            action: 'deleteCandidate',
            id: candidateID
        };

        thisVal.disabled = true;

        api.post('/candidate.php', data).then(res => {
            document.getElementById('candidate'+num).parentNode.remove();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    function saveMember(thisVal, num, memberID) {
        let data = {
            action: 'addRoleForPosition',
            positionID: POSITION_ID,
            userID: document.getElementById('member'+num).value,
            roleID: document.getElementById('role'+num).value
        }

        // Don't send request for nonexistent user
        if(data.name == '--')
            return;

        thisVal.disabled = true;

        api.post('/role.php', data).then(res => {
            snackbar(res.message, 'success');

            thisVal.parentNode.parentNode.getElementsByTagName('select')[0].disabled = true;
            thisVal.parentNode.parentNode.setAttribute('oninput', `setActive(this, true)`);
            thisVal.dataset.update = true;
            thisVal.setAttribute('onclick', `updateMember(this, ${num}, ${res.content})`);
            thisVal.innerHTML = 'Saved';
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    function updateMember(thisVal, num, memberID) {
        let data = {
            action: 'updateRoleForPosition',
            positionID: POSITION_ID,
            roleForPositionID: memberID,
            userID: document.getElementById('member'+num).value,
            roleID: document.getElementById('role'+num).value
        }

        // Don't send request for nonexistent user
        if(data.name == '--')
            return;

        thisVal.disabled = true;

        api.post('/role.php', data).then(res => {
            snackbar(res.message, 'success');
            setActive(thisVal.parentNode.parentNode, false);
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        });
    }

    function updateUploadFileName(thisVal) {
        thisVal.parentNode.firstElementChild.innerText = thisVal.files[0].name;
    }

	function uploadCandidateFile(thisVal, num, candidateID) {
        var isValidFile = false;
		var file_data = $('#candidateUploadFileInput'+num).prop('files')[0]
		var form_data = new FormData();
        var dbFileName = "";
		form_data.append('file', file_data);
		form_data.append('action', 'uploadCandidateFile');

        thisVal.disabled = true;

		$.ajax({
			url: './ajax/Handler.php',
			type: 'POST',
			dataType: 'json',
			/* What we are expecting back */
			contentType: false,
			processData: false,
			data: form_data,
			success: function(result) {
                console.log("-- AJAX Result", result)
				if (result["successful"] == 1) {
					isValidFile = true;
					dbFileName = result["path"];
				} else if (result["successful"] == 0) {
					isValidFile = false;
                    snackbar(result["string"], 'error');
				}
                if (isValidFile) {
                    var purpose = $('#candidateFilePurpose'+num).val();
                    saveCandidateFileToDb(thisVal, num, dbFileName, candidateID, purpose);
                    console.log("File successfully uploaded");
                }
			},
			error: function(result) {
                thisVal.disabled = false;
				isValidFile = false;
                snackbar(result["string"], 'error');
			}
		});
	}

    function saveCandidateFileToDb(thisVal, num, dbFileName, candidateID, purpose) {
        let data = {
            action: 'createCandidateFile',
            candidateID: candidateID,
            purpose: purpose,
            filename: dbFileName
        }

        api.post('/candidateFile.php', data).then(res => {
            document.getElementById('candidateFilesDiv'+num).insertAdjacentHTML('beforeend', `
                <div class="row" id="candidateFile${res.content}">
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateFilePurpose${res.content}" class="input-group-text">File Purpose</label></div>
                        <input id="candidateFilePurpose${res.content}" type="text" class="form-control" value="${purpose}" onchange="updateCandidateFile(this, \'${res.content}\')">
                    </div>
                    <div class="col input-group p-1">
                        <div class="input-group-prepend"><label for="candidateFileName${res.content}" class="input-group-text">File Name</label></div>
                        <p id="candidateFileName${res.content}" type="text" class="form-control" style="background-color: #e9ecef;" ><a class="text-primary" target="_blank" href="./uploads/candidate/${dbFileName}">${dbFileName}</a></p>
                    </div>
                    <div class="col-auto p-1">
                        <button class="btn btn-danger mx-auto" type="button" onclick="removeCandidateFile(this, \'${res.content}\');" id="delete'.${res.content}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`);
            document.getElementById('candidateUploadFileInput'+num).value = null;
            document.getElementById('candidateUploadFileName'+num).innerText = 'Upload New Candidate File';
            document.getElementById('candidateFilePurpose'+num).value = null;
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
            thisVal.disabled = false;
        })/* .finally(() => $('#fileFeedback'+num).html('')) */;
    }

    function updateCandidateFile(thisVal, fileID) {
        let data = {
            action: 'updateCandidateFile',
            fileID: fileID,
            purpose: document.getElementById('candidateFilePurpose' + fileID).value
        }

        thisVal.disabled = true;

        api.post('/candidateFile.php', data).then(res => {
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function removeCandidateFile(thisVal, fileID) {
        let data = {
            action: 'removeCandidateFile',
            fileID: fileID,
        }

        thisVal.disabled = true;

        api.post('/candidateFile.php', data).then(res => {
            document.getElementById('candidateFile'+fileID).remove();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

    function checkAddUser(thisVal) {
        if(thisVal.value == '** Add User **') {
            thisVal.children[0].selected = true;
            document.getElementById('triggerNewUserModal').click();
        }
    }

    function addNewUser(thisVal) {
        let onid = document.getElementById('newONID').value;
        let fName = document.getElementById('newFirstName').value;
        let lName = document.getElementById('newLastName').value;
        if(!confirm(`Please verify that the committee member's ONID is ${onid}`))
            return;

        let data = {
            action: 'addUser',
            onid: onid,
            firstName: fName,
            lastName: lName
        }

        thisVal.disabled = true;

        api.post('/user.php', data).then(res => {
            let selects = document.getElementsByClassName('selectUser');
            for(let i = 0; i < selects.length; i++) {
                let newOption = document.createElement('option');
                newOption.value = res.content;
                newOption.innerText = data.firstName + ' ' + data.lastName;
                selects[i].insertBefore(newOption, selects[i].children[2]);
            }
            document.getElementById('closeNewUserModal').click();
            snackbar(res.message, 'success');
        }).catch(err => {
            snackbar(err.message, 'error');
        }).finally(() => thisVal.disabled = false);
    }

</script>

<?php include_once PUBLIC_FILES."/modules/footer.php" ?>