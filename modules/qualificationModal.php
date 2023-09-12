<?php
    /**
     * Generates modals with all qualification information for each qualification associated with the current position.
     * 
     * @global Model\Position $position The position to generate qualification modals for
     * @global DataAccess\QualificationDao $qualificationDao The DAO for accessing qualifications
     * 
     * Generates modals that can be activated by button elements with the 
     *  `data-toggle='modal' data-target='#qualModal{{qualificationID}}'` 
     *  attributes
     */

    $qualifications = $qualificationDao->getQualificationsForPosition($position->getID());
    foreach($qualifications as $index=>$qualification) {
        $qualID = $qualification->getID();
        $description = $qualification->getDescription();
        $screeningCriteria = $qualification->getScreeningCriteria();
        $strengthIndicators = $qualification->getStrengthIndicators();
        $level = $qualification->getLevel();
        $priority = $qualification->getPriority();
        $transferable = $qualification->getTransferable() ? 'Yes' : 'No';
        echo "
            <div class='modal fade' id='qualModal$qualID' tabindex='-1' role='dialog'>
                <div class='modal-dialog modal-dialog-centered modal-lg' role='document'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title'>Qualification Details</h5>
                            <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>
                        <div class='modal-body'>
                            <!-- Description -->
                            <div class='input-group p-1'>
                                <div class='input-group-prepend'><p for='qualDescription$index' class='input-group-text'>Description</p></div>
                                <p class='form-control' id='qualDescription$index' style='height: auto'>$description</p>
                            </div>
                            <!-- Screening Criteria -->
                            <div class='input-group p-1'>
                                <div class='input-group-prepend'><p for='screeningCriteria$index' class='input-group-text'>Screening Criteria</p></div>
                                <p class='form-control' id='screeningCriteria$index' style='height: auto'>$screeningCriteria</p>
                            </div>
                            <!-- Strength Indicators -->
                            <div class='input-group p-1'>
                                <div class='input-group-prepend'><p for='strengthIndicators$index' class='input-group-text'>Strength Indicators</p></div>
                                <p class='form-control' id='strengthIndicators$index' style='height: auto'>$strengthIndicators</p>
                            </div>
    
                            <!-- Dropdowns -->
                            <div class='row m-1'>
                                <!-- Level -->
                                <div class='col-md input-group p-1'>
                                    <div class='input-group-prepend'><p for='level$index' class='input-group-text'>Level</p></div>
                                    <p class='form-control' id='level$index'>$level</p>
                                </div>
                                <!-- Priority -->
                                <div class='col-md input-group p-1'>
                                    <div class='input-group-prepend'><p for='priority$index' class='input-group-text'>Priority</p></div>
                                    <p class='form-control' id='priority$index'>$priority</p>
                                </div>
                                <!-- Transferable -->
                                <div class='col-md input-group p-1'>
                                    <div class='input-group-prepend'><label for='transferable$index' class='input-group-text'>Transferable?</label></div>
                                    <p class='form-control' id='transferable$index'>$transferable</p>
                                </div>
                            </div>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-primary' data-dismiss='modal'>Done</button>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
?>