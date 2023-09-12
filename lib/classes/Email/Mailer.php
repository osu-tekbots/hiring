<?php
namespace Email;

use Util\Security;

/**
 * Handles automated sending of emails based on events received by the server. The mailer will send emails using the
 * same 'From' address.
 */
class Mailer {
    
    /** @var string */
    private $from;

    /** @var string */
    private $subjectTag;

    /** @var \Util\Logger */
    protected $logger;

    /**
     * Creates a new mailer to send emails.
     *
     * @param string $from the from address for the email
     * @param string|null $subjectTag an optional tag to prefix the email subject with
     * @param \Util\Logger|null $logger an optional logger to capture error messages from the mail() function
     */
    public function __construct($from, $subjectTag = null, $logger = null) {
        $this->from = $from;
        $this->subjectTag = $subjectTag;
        $this->logger = $logger;
    }

    /**
     * Sends the provided email.
     *
     * @param string|string[] $to the email address or addresses to send the message to
     * @param string $subject the subject of the email
     * @param string $message the email content to send
     * @param boolean $html indicates whether the message content is HTML or plain text
     * @param string $cc the email address or addresses to carbon-copy on the email
     * @return boolean true on success, false otherwise
     */
    public function sendEmail($to, $subject, $message, $html = false, $cc = null) {
        if ($this->subjectTag != null) {
            $subject = '[' . $this->subjectTag . '] ' . $subject;
        }

        $from = $this->from;

        $headers = array();

        if ($html) {
            $message = "
            <html>
            <head> 
                <title>$subject</title>
            </head>
            <body>
                $message
            </body>
            </html>
            ";

            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html;charset=UTF-8';
        }

        $headers[] = "From: $from";

        if(!is_null($cc)) {
            if (\is_array($cc)) {
                $cc = \implode(',', $cc);
            }
            if(\is_string($cc)) {
                $headers[] = 'Cc: '.$cc;
            }
        }

        $headersStr = \implode("\r\n", $headers);
        $headersStr .= "\r\n";
        
        if (\is_array($to)) {
            $to = \implode(',', $to);
        }

        $accepted = mail($to, $subject, $message, $headersStr);
        if (!$accepted) {
            $lastError = error_get_last();
            if ($this->logger != null) {
                $this->logger->error("Failed to send email to $to from $from: " . $lastError['message']);
                $this->logger->error($message);
            }
            return false;
        }

        return true;
    }

    /**
     * Sends the specified email.
     *
     * @param \Model\User $user the email address or addresses to send the message to
     * @param \Model\Message $message model from database for template, body
     * @return boolean true on success, false otherwise
     */
    public function sendMODEL_NAMEEmail($user, $message) {
        $replacements = Array();
		
		$replacements['email'] = Security::HtmlEntitiesEncode($user->getEmail());
		// FILL REPLACEMENTS
		
		$subject = $message->fillTemplateSubject($replacements);
		$body = $message->fillTemplateBody($replacements);

        return $this->sendEmail($replacements['email'], $subject, $body, true);
    }
}
