<?php

header_remove("X-Powered-By");
header_remove("Content-type");

define('STDOUT', 'php://output');

define('ENDEAVOUR_DIR', $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR);

// Require Endeavour configuration
require 'inc/config.beta.php';

// Require ADOdb
require 'vendor/adodb/adodb-php/adodb.inc.php';

// Database connection
$DB = NewADOConnection('mysqli');
$DB->Connect(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

// Look for 'ready' emails
$lookingForEmails = true;
$processorID = md5(time() . microtime() . rand(0,99));

$i = 0;

while ($lookingForEmails) {

    $i++;
    fwrite(STDOUT, 'running iteration #' . $i . "\n");
    if ($i > 99) {
        // Max 100 emails at a time
        exit;
    }

    $email = $DB->GetRow($DB->Prepare('SELECT * FROM `Emails` WHERE `Status` = 1 ORDER BY `ID` ASC LIMIT 1'));

    if (!$email) {
        $lookingForEmails = false;
        break;
    }

    $emailID = $email['ID'];

    // Update status to processing
    $updateQuery = $DB->Prepare('UPDATE `Emails` SET `Status` = 2, `ProcessedBy` = ?, `Processed` = UTC_TIMESTAMP() WHERE `ID` = ? LIMIT 1');

    $update = $DB->Execute(
        $updateQuery,
        [
            $processorID,
            $emailID,
        ]
    );

    // If update failed, start over
    if (!$update) {
        fwrite(STDOUT, 'Error' . $DB->ErrorMsg());
        exit;
        continue;
    }

    // Check no other process has snuck in and claimed this
    $email = $DB->GetRow($DB->Prepare('SELECT * FROM `Emails` WHERE `ID` = ?'), [$emailID]);
    if ($email['ProcessedBy'] != $processorID) {
        continue;
    }



    // Load PHPMailer vendor library
    require_once 'vendor/phpmailer/phpmailer/PHPMailerAutoload.php';


    // New PHPMailer
    $mail = new \PHPMailer;

    // Mailer Settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = 'tls';

    // Email properties
    $mail->From = 'no-reply@endeavourapp.com';
    $mail->FromName = 'EndeavourApp';

    if (preg_match('/\</', $email['Recipient'])) {
        $recipientParts = explode('<', $email['Recipient']);
        $recipientName = trim($recipientParts[0]);
        $recipientEmail = trim(str_replace('>', '', $recipientParts[1]));
        $mail->addAddress($recipientEmail, $recipientName);
    } else {
        $mail->addAddress($email['Recipient']);
    }

    $mail->addReplyTo('help@endeavourapp.com', 'EndeavourApp Help');
    $mail->addBCC('bcc@endeavourapp.com');

    $replacements = $DB->GetAll($DB->Prepare('SELECT * FROM `EmailReplacements` WHERE `EmailID` = ?'), [$emailID]);

    $template = new EmailTemplate($email['TemplateID']);
    $template->setReplacements($replacements);

    if ($template->hasBCC()) {
        $mail->addBCC($template->getBCC());
    }

    $mail->Subject = $template->getSubject();
    $mail->Body = $template->getBody();
    $mail->AltBody = $template->getAltBody();

    if (!$mail->send()) {
        $DB->Execute($DB->Prepare('INSERT INTO `EmailErrors` (`EmailID`, `Error`, `Date`) VALUES (?, ?, UTC_TIMESTAMP())'), [$emailID, $mail->ErrorInfo]);
    } else {
        $DB->Execute($DB->Prepare('UPDATE `Emails` SET `Status` = 3, `Dispatched` = UTC_TIMESTAMP() WHERE `ID` = ? LIMIT 1'), [$emailID]);
    }

}

class EmailTemplate {

    public $ID;
    private $data;
    private $DB;

    private $replacements = [];

    function __construct($ID)
    {
        $this->DB = $GLOBALS['DB'];
        $this->ID = $ID;

        $this->data = $this->DB->GetRow($this->DB->Prepare("SELECT * FROM `EmailTemplates` WHERE `ID` = ? LIMIT 1"), [$ID]);
    }

    public function setReplacements($replacements)
    {
        $this->replacements = $replacements;
        return $this;
    }

    public function getBody()
    {
        return $this->doReplacements($this->data['HTML']);
    }

    public function getAltBody()
    {
        return $this->doReplacements($this->data['PlainText']);
    }

    public function getSubject()
    {
        return $this->doReplacements($this->data['Subject']);
    }

    public function getBCC()
    {
        return $this->data['BCC'];
    }

    public function hasBCC()
    {
        return array_key_exists('BCC', $this->data) && $this->data['BCC'];
    }

    private function doReplacements($input)
    {
        foreach ($this->getReplacements() as $replacement) {

            $key = $replacement['Key'];
            $value = $replacement['Value'];

            $input = str_replace('<<' . $key . '>>', $value, $input);
            $input = str_replace('<<formatParagraphs:' . $key . '>>', $this->formatParagraphs($value), $input);

        }

        return $input;
    }

    private function getReplacements() {
        return $this->replacements;
    }

    private function formatParagraphs($input) {
        return '<p>' . nl2br($input) . '</p>';
    }

}
