<?php
namespace Email;

use Util\Security;

class HiringMailer extends Mailer {
    /**
     * Constructs a new instance of a mailer specifically for hiring website-related emails
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
		
		$subject = $message->fillTemplateSubject($replacements);
		$body = $message->fillTemplateBody($replacements);

        return $this->sendEmail($user->getEmail(), $subject, $body, true);
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
		
		$subject = $message->fillTemplateSubject($replacements);
		$body = $message->fillTemplateBody($replacements);

        return $this->sendEmail($user->getEmail(), $subject, $body, true);
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
		
		$subject = $message->fillTemplateSubject($replacements);
		$body = $message->fillTemplateBody($replacements);

        return $this->sendEmail($user->getEmail(), $subject, $body, true);
    }
}