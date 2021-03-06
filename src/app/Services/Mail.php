<?php declare(strict_types=1);
/**
 * WordPress development container generator.
 *
 * @package twpg-wordpress-generator
 * @author BRE Digital
 * @license GPL-3.0
 */

namespace TWPG\Services;

use TWPG\Models\Owner;
use TWPG\Services\Configuration;
use TWPG\Services\SystemLog;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Handles the dispatch of emails sent from the internal system itself.
 */
class Mail
{
	protected $config;
	protected $log;
	protected $owner;

	public function __construct(Configuration $config, SystemLog $log, Owner $owner)
	{
		$this->config = $config;
		$this->log    = $log;
		$this->owner  = $owner;
	}

	public function sendEmailToSiteOwner(int $siteId, string $title, string $contents):bool
	{
		if ($this->config->mail->enabled) {
			$details = $this->owner->getOwnerBySiteId($siteId);

			if (empty($details)) {
				$this->log->warning(
					"A problem occurred processing an email for {$siteId}. Could be either the site did not finish configuration, or the primary admin was deleted."
				);

				return false;
			}

			$mail = new PHPMailer(true);
			try {
				$mail->Host       = $this->config->mail->SMTP;
				$mail->SMTPAuth   = $this->config->mail->auth;
				$mail->Username   = $this->config->mail->user;
				$mail->Password   = $this->config->mail->password;
				$mail->Port       = $this->config->mail->Port;

				$mail->setFrom($this->config->mail->fromAddress, $this->config->mail->fromName);
				$mail->addAddress($details['user_email'], $details['user_nicename']);
				if (! empty($this->config->mail->cc)) {
					$mail->addCC($this->config->mail->cc);
				}

				$mail->isHTML(true);
				$mail->Subject = $title;
				$mail->Body    = "<html><body>{$contents}</body></html>";

				if (! $this->config->mail->useSSL) {
					$mail->SMTPOptions = [
						'ssl' => [
							'verify_peer'       => false,
							'verify_peer_name'  => false,
							'allow_self_signed' => true
						]
					];
				}

				if ($this->config->general->debug) {
					$cob = $this->log;
					$mail->SMTPDebug = 2;
					$mail->Debugoutput = function ($str, $level) use ($cob) {
						$cob->debug("Email  (L{$level}) : {$str}");
					};
				}

				$mail->isSMTP();
				$mail->send();
			} catch (Exception $e) {
				$this->log->error('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
				return false;
			}

			return true;
		} else {
			return false;
		}
	}
}
