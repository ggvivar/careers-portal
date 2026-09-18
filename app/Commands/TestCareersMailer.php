<?php

namespace App\Commands;

use App\Libraries\CareersMailer;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

class TestCareersMailer extends BaseCommand
{
    protected $group = 'Careers';

    protected $name = 'careers:mail-test';

    protected $description =
        'Sends a test email through the JNG Careers Mailer API.';

    protected $usage =
        'careers:mail-test recipient@example.com';

    protected $arguments = [
        'email' => 'Recipient email address.',
    ];

    public function run(array $params)
    {
        $email = trim((string) ($params[0] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            CLI::error(
                'Provide a valid recipient email address.'
            );

            return EXIT_ERROR;
        }

        $message = <<<HTML
<!doctype html>
<html lang="en">
<body style="font-family:Arial,sans-serif">
    <h1>JNG Apply Portal email test</h1>
    <p>The external Careers Mailer API is working.</p>
    <p>Sent at: {$this->currentDate()}</p>
</body>
</html>
HTML;

        try {
            $result = (new CareersMailer())->send(
                $email,
                'JNG Apply Portal email test',
                $message,
                'html'
            );

            CLI::write(
                'Email request completed.',
                'green'
            );

            CLI::write(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                )
            );

            return EXIT_SUCCESS;
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());

            return EXIT_ERROR;
        }
    }

    private function currentDate(): string
    {
        return date('Y-m-d H:i:s');
    }
}