<?php
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
class Mail {
	protected $config;
	protected $log;
	protected $owner;

	public function __construct( Configuration $config, SystemLog $log, Owner $owner ) {
		$this->config = $config;
		$this->log    = $log;
		$this->owner  = $owner;
	}

	public function sendEmailToSiteOwner( $siteId, $title, $contents ) {
		if ( $this->config->mail->enabled ) {
			$details = $this->owner->getOwnerBySiteId( $siteId );

			if ( $details == false ) {
				$this->log->warning(
					"A problem occurred processing an email for {$siteId}. Could be either the site did not finish configuration, or the primary admin was deleted."
				);

				return false;
			}

			$mail = new PHPMailer( true );
			try {
				$mail->Host       = $this->config->mail->SMTP;
				$mail->SMTPAuth   = $this->config->mail->auth;
				$mail->Username   = $this->config->mail->user;
				$mail->Password   = $this->config->mail->password;
				$mail->SMTPSecure = $this->config->mail->useSSL;
				$mail->Port       = (int) $this->config->mail->Port;

				$mail->setFrom( $this->config->mail->fromAddress, $this->config->mail->fromName );
				$mail->addAddress( $details['user_email'], $details['user_nicename'] );
				if ( ! empty( $this->config->mail->cc ) ) {
					$mail->addCC( $this->config->mail->cc );
				}

				$mail->isHTML( true );
				$mail->Subject = $title;
				$mail->Body    = "<html><body>{$contents}</body></html>";

				if( ! $this->config->mail->useSSL ) {
					$mail->SMTPOptions = [
						'ssl' => [
							'verify_peer'       => false,
							'verify_peer_name'  => false,
							'allow_self_signed' => true
						]
					];
				}

				$mail->isSMTP();
				$mail->send();
			} catch ( Exception $e ) {
				$this->log->error( 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo );
				return false;
			}

			return true;
		} else {
			return false;
		}
	}
}
