<?php
namespace Email;

use Util\Security;

class HiringMailer extends Mailer {
    /**
     * Constructs a new instance of a mailer specifically for SPT-related emails.
     *
     * @param string $from the from address for emails
     * @param string $bounceAddress the email address to direct notices about emails that bounced to
     * @param string|null $subjectTag an optional subject tag to prefix the provided subject tag with
     * @param \Util\Logger|null $logger an optional logger to capture error messages from the mail() function
     */
    public function __construct($from, $bounceAddress, $subjectTag = null, $logger = null) {
        parent::__construct($from, $bounceAddress, $subjectTag, $logger);
    }

    /**
     * Sends an email to the specified user to tell them that they have been added to a search committee.
     * 
     * @param \Model\User $user The user to send the email to
     * @param \Model\Message $message The message template to fill out and send the user
     * @param \Model\Position $position The position to fill out the message template with
     * @param string $role The user's role on the search committee
     * 
     * @return bool Whether the email successfully sent
     */
    public function sendAddedToCommitteeEmail($user, $message, $position, $role) {
        $replacements = Array();
        
        $replacements['name'] = Security::HtmlEntitiesEncode($user->getFirstName() . " " . $user->getLastName());
        $replacements['role'] = $role;
        $replacements['position'] = $position->getTitle();
        $replacements['positionID'] = $position->getID();
        
        return $this->sendTemplateEmail($user->getEmail(), $message, $replacements);
    }

    /**
     * Sends an email to the specified user with information for (re)setting their password.
     * 
     * @param \Model\User $user The user to send the email to
     * @param \Model\Message $message The message template to fill out and send the user
     * @param string $link The link to the password reset page
     * @param string $code The code for (re)setting their password
     * 
     * @return bool Whether the email successfully sent
     */
    public function sendLocalPasswordEmail($user, $message, $link, $code) {
        $replacements = Array();
        
        $replacements['name'] = Security::HtmlEntitiesEncode($user->getFirstName() . " " . $user->getLastName());
        $replacements['link'] = $link;
        $replacements['resetCode'] = $code;
        
        return $this->sendTemplateEmail($user->getEmail(), $message, $replacements);
    }

    /**
     * Sends an email to the specified user to inform them that their position has been approved.
     * 
     * @param \Model\User $user The user to send the email to
     * @param \Model\Message $message The message template to fill out and send the user
     * @param string $link The link to the edit position page
     * 
     * @return bool Whether the email successfully sent
     */
    public function sendPositionApprovedEmail($user, $message, $link) {
        $replacements = Array();
        
        $replacements['name'] = Security::HtmlEntitiesEncode($user->getFirstName() . " " . $user->getLastName());
        $replacements['link'] = $link;

        return $this->sendTemplateEmail($user->getEmail(), $message, $replacements);
    }
    
    /**
     * Sends an email to the specified member to ask them to submit their feedback for a given candidate for a given 
     * position.
     * 
     * @param \Model\User $member The member to send the email to
     * @param \Model\Message $message The message template to fill out and send the user
     * @param string $searchChairName The name of the search chair for the position
     * @param string $candidateName The name of the candidate the feedback must be completed for
     * @param string $positionName The name of the position the feedback must be completed for
     * 
     * @return bool Whether the email successfully sent
     */
    public function sendFeedbackReminderEmail($member, $message, $searchChairName, $candidateName, $positionName) {
        $replacements = Array();
        
        $replacements['name'] = Security::HtmlEntitiesEncode($member->getFirstName() . " " . $member->getLastName());
        $replacements['searchChair'] = Security::HtmlEntitiesEncode($searchChairName);
        $replacements['candidate'] = Security::HtmlEntitiesEncode($candidateName);
        $replacements['positionName'] = Security::HtmlEntitiesEncode($positionName);

        return $this->sendTemplateEmail($member->getEmail(), $message, $replacements);
    }
    
    /**
     * Sends an email to the SPT admin informing them that a new position has been created.
     * 
     * @param \Model\Message $message The message template to fill out and send the user
     * @param string $searchChairName The name of the person creating the new position
     * @param string $positionName The name of the position being created
     * @param \Util\ConfigManager $configManager The config manager for finding the admin email address
     * 
     * @return bool Whether the email successfully sent
     */
    public function sendPositionCreatedEmail($message, $searchChairName, $positionName, $configManager) {
        $replacements = Array();
        
        $replacements['searchChair'] = Security::HtmlEntitiesEncode($searchChairName);
        $replacements['positionName'] = Security::HtmlEntitiesEncode($positionName);

        return $this->sendTemplateEmail($configManager->getAdminEmail(), $message, $replacements);
    }

    /**
     * 
     * @param string $address The email address to send the message to
     * @param \Model\Message $message The message to send
     * @param string=>string $replacements keys represent the template names 
     *        (eg "{{user}}"); values represent the value to replace the template name with
     * 
     * @return bool Whether the email successfully sent
     */
    public function sendTemplateEmail($address, $message, $replacements) {
        $subject = $message->fillTemplateSubject($replacements);
        $body = $message->fillTemplateBody($replacements);

        return $this->sendEmail($address, $subject, $body, true);
    }
}