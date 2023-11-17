<?php
namespace Email;

use Util\Security;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader to automatically include PHPMailer classes
require PUBLIC_FILES.'/vendor/autoload.php';

/**
 * Handles automated sending of emails based on events received by the server. The mailer will send emails using the
 * same 'From' address.
 */
class NewMailer {
    
    /** @var string */
    private $from;

    /** @var string */
    private $bounceAddress;

    /** @var string */
    private $subjectTag;

    /** @var PHPMailer */
    private $mail;

    /** @var \Util\Logger */
    protected $logger;

    /**
     * Creates a new mailer to send emails.
     *
     * @param string $from the from address for the email
     * @param string|null $subjectTag an optional tag to prefix the email subject with
     * @param \Util\Logger|null $logger an optional logger to capture error messages from the mail() function
     */
    public function __construct($from, $bounceAddress, $subjectTag = null, $logger = null) {
        $this->from = $from;
        $this->bounceAddress = $bounceAddress;
        $this->subjectTag = $subjectTag;
        $this->logger = $logger;
        //Create an instance; passing `true` enables exceptions
        $this->mail = new PHPMailer(true);
        $this->mail->setFrom($from);
        $this->mail->Sender = $this->bounceAddress; // Set known-good address for bounces - in case a user enters a bad "from" address
    }

    /**
     * Sends the provided email.
     * 
     * @todo Add ability to embed files: https://github.com/PHPMailer/PHPMailer/blob/master/examples/send_multiple_file_upload.phps
     *
     * @param string|string[] $to the email address or addresses to send the message to
     * @param string $subject the subject of the email
     * @param string $message the email content to send
     * @param boolean $html indicates whether the message content is HTML or plain text
     * @param string $cc the email address or addresses to carbon-copy on the email
     * 
     * @return boolean true on success, false otherwise
     */
    public function sendEmail($to, $subject, $message, $html = false, $cc = null, $attachments = null) {
        try {
            if ($this->subjectTag != null) {
                $subject = '[' . $this->subjectTag . '] ' . $subject;
            }

            $from = $this->from;

            $headers = array();

            if(!is_null($cc)) {
                if (\is_array($cc)) {
                    foreach($cc as $email) {
                        $this->mail->addCC($cc);
                    }
                } else if(\is_string($cc)) {
                    $this->mail->addCC($cc);
                } else {
                    $this->logger->error("Invalid argument 'cc' to Email\NewMailer: " . var_export($cc, true));
                    return false;
                }
            }
            
            if (\is_array($to)) {
                foreach($to as $email) {
                    $this->mail->addAddress($email);
                }
            } else if(\is_string($to)) {
                $this->mail->addAddress($to);
            } else {
                $this->logger->error("Invalid argument 'to' to Email\NewMailer: " . var_export($to, true));
                return false;
            }

            if($attachments != NULL) {
                if(\is_array($attachments)) {
                    foreach($attachments as $attachment) {
                        if(\is_array($attachment)) {
                            $this->mail->addAttachment($attachment['address'], $attachment['name']);
                        } else if(\is_string($attachment)) {
                            $this->mail->addAttachment($attachment);
                        } else {
                            $this->logger->error("Invalid argument 'attachments' to Email\NewMailer: " . var_export($attachments, true));
                            return false;
                        }
                    }
                } else {
                    $this->logger->error("Invalid argument 'attachments' to Email\NewMailer: " . var_export($attachments, true));
                    return false;
                }
            }

            $this->mail->isHTML($html);

            $this->mail->Subject = $subject;
            $this->mail->Body = $message;
            $this->mail->AltBody = strip_tags($subject); // Alternate email body for non-HTML mail clients

            $ok = $this->mail->send();

            if(!$ok) {
                throw new Exception("mail->send() returned an issue: {$mail->ErrorInfo}");
            }

            return true;
        } catch (\Exception $e) {
            if ($this->logger != null) {
                $this->logger->error("Failed to send email using PHPMailer: " . var_export($this->mail, true));
                $this->logger->error($e);
            }
            return false;
        }
    }
}
