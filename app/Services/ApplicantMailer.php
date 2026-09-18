<?php

namespace App\Services;

use App\Libraries\CareersMailer;
use RuntimeException;
use Throwable;

class ApplicantMailer
{
    private CareersMailer $mailer;

    public function __construct(?CareersMailer $mailer = null)
    {
        $this->mailer = $mailer ?? new CareersMailer();
    }

    /** @return array<string, mixed> */
    public function sendWelcome(
        string $email,
        string $applicantName,
        string $temporaryPassword
    ): array {
        try {
            $message = view('emails/applicant_welcome', [
                'applicantName'     => $applicantName,
                'temporaryPassword' => $temporaryPassword,
                'loginUrl'          => site_url('login'),
            ]);

            if (trim($message) === '') {
                throw new RuntimeException(
                    'The applicant welcome email template returned an empty message.'
                );
            }

            return $this->mailer->send(
                $email,
                'Welcome to the JNG Recruitment Hub',
                $message,
                'html'
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Applicant welcome email failed for {email}: {message}',
                ['email' => $email, 'message' => $exception->getMessage()]
            );

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function sendPasswordReset(
        string $email,
        string $applicantName,
        string $resetUrl
    ): array {
        try {
            $careersConfig = config('Careers');
            $resetMinutes = max(10, (int) ($careersConfig->resetMinutes ?? 60));

            $message = view('emails/applicant_reset_password', [
                'applicantName' => $applicantName,
                'resetUrl'      => $resetUrl,
                'expiresIn'     => $resetMinutes . ' minutes',
            ]);

            if (trim($message) === '') {
                throw new RuntimeException(
                    'The password-reset email template returned an empty message.'
                );
            }

            return $this->mailer->send(
                $email,
                'Reset your JNG Applicant Portal password',
                $message,
                'html'
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Password-reset email failed for {email}: {message}',
                ['email' => $email, 'message' => $exception->getMessage()]
            );

            throw $exception;
        }
    }
}
