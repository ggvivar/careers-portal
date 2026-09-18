<?php

namespace App\Libraries;

use Config\CareersMailer as CareersMailerConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use RuntimeException;
use Throwable;

class CareersMailer
{
    private Client $client;

    private CareersMailerConfig $config;

    public function __construct(
        ?Client $client = null,
        ?CareersMailerConfig $config = null
    ) {
        $this->client = $client ?? new Client();

        $resolvedConfig = $config ?? config('CareersMailer');

        if (! $resolvedConfig instanceof CareersMailerConfig) {
            throw new RuntimeException(
                'CareersMailer configuration could not be loaded.'
            );
        }

        $this->config = $resolvedConfig;
    }

    /**
     * @param string|list<string> $to
     * @param list<string|array{
     *     path: string,
     *     filename?: string,
     *     content_type?: string
     * }> $attachments
     *
     * @return array<string, mixed>
     */
    public function send(
        string|array $to,
        string $subject,
        string $message,
        string $messageType = 'html',
        array $attachments = []
    ): array {
        $recipients = $this->normalizeRecipients($to);

        if ($recipients === '') {
            throw new RuntimeException(
                'At least one recipient is required.'
            );
        }

        if ($this->config->endpoint === '') {
            throw new RuntimeException(
                'The Careers Mailer endpoint is not configured.'
            );
        }

        if ($this->config->authorization === '') {
            throw new RuntimeException(
                'The Careers Mailer authorization key is empty.'
            );
        }

        if (! in_array($messageType, ['html', 'text'], true)) {
            throw new RuntimeException(
                'Message type must be html or text.'
            );
        }

        $multipart = [
            [
                'name'     => 'to',
                'contents' => $recipients,
            ],
            [
                'name'     => 'subject',
                'contents' => $subject,
            ],
            [
                'name'     => 'message',
                'contents' => $message,
            ],
            [
                'name'     => 'message_type',
                'contents' => $messageType,
            ],
        ];

        $openedFiles = [];

        try {
            foreach ($attachments as $attachment) {
                $file = $this->normalizeAttachment($attachment);

                $handle = Utils::tryFopen(
                    $file['path'],
                    'r'
                );

                $openedFiles[] = $handle;

                $multipart[] = [
                    'name'     => 'attachments[]',
                    'contents' => $handle,
                    'filename' => $file['filename'],
                    'headers'  => [
                        'Content-Type' => $file['content_type'],
                    ],
                ];
            }

            $headers = [
                'Accept' => 'application/json',
                $this->config->authHeader =>
                    $this->config->authorizationValue(),
            ];

            log_message(
                'info',
                'Sending Careers email to {recipients} using {endpoint}.',
                [
                    'recipients' => $recipients,
                    'endpoint'   => $this->config->endpoint,
                ]
            );

            $response = $this->client->request(
                'POST',
                $this->config->endpoint,
                [
                    'headers'         => $headers,
                    'multipart'       => $multipart,
                    'timeout'         => $this->config->timeout,
                    'connect_timeout' => 10,
                    'http_errors'     => false,
                ]
            );

            $statusCode = $response->getStatusCode();
            $body = trim((string) $response->getBody());

            log_message(
                'info',
                'Careers Mailer response. Status: {status}; Body: {body}',
                [
                    'status' => $statusCode,
                    'body'   => $body !== ''
                        ? $body
                        : '[empty response]',
                ]
            );

            $decoded = json_decode($body, true);

            if (! is_array($decoded)) {
                $decoded = [
                    'success' => $statusCode >= 200
                        && $statusCode < 300,
                    'message' => $body,
                ];
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new RuntimeException(
                    sprintf(
                        'Mailer API returned HTTP %d: %s',
                        $statusCode,
                        $decoded['message']
                            ?? $body
                            ?: 'No response message'
                    )
                );
            }

            if (
                array_key_exists('success', $decoded)
                && ! filter_var(
                    $decoded['success'],
                    FILTER_VALIDATE_BOOL
                )
            ) {
                throw new RuntimeException(
                    (string) (
                        $decoded['message']
                        ?? 'The mailer reported a failed send.'
                    )
                );
            }

            return $decoded;
        } catch (GuzzleException $exception) {
            log_message(
                'error',
                'Careers Mailer connection error: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to connect to the JNG Mailer API: '
                . $exception->getMessage(),
                0,
                $exception
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Careers Mailer send error: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            throw $exception;
        } finally {
            foreach ($openedFiles as $handle) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        }
    }

    /**
     * @param string|list<string> $recipients
     */
    private function normalizeRecipients(
        string|array $recipients
    ): string {
        if (is_string($recipients)) {
            $recipients = explode(',', $recipients);
        }

        $validRecipients = [];

        foreach ($recipients as $recipient) {
            $email = strtolower(
                trim((string) $recipient)
            );

            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException(
                    "Invalid recipient email: {$email}"
                );
            }

            $validRecipients[] = $email;
        }

        return implode(
            ',',
            array_values(array_unique($validRecipients))
        );
    }

    /**
     * @param string|array{
     *     path: string,
     *     filename?: string,
     *     content_type?: string
     * } $attachment
     *
     * @return array{
     *     path: string,
     *     filename: string,
     *     content_type: string
     * }
     */
    private function normalizeAttachment(
        string|array $attachment
    ): array {
        $path = is_string($attachment)
            ? $attachment
            : trim((string) ($attachment['path'] ?? ''));

        if (
            $path === ''
            || ! is_file($path)
            || ! is_readable($path)
        ) {
            throw new RuntimeException(
                "Attachment is missing or unreadable: {$path}"
            );
        }

        $filename = is_array($attachment)
            ? trim((string) (
                $attachment['filename']
                ?? basename($path)
            ))
            : basename($path);

        $detectedType = function_exists('mime_content_type')
            ? mime_content_type($path)
            : false;

        $contentType = is_array($attachment)
            ? trim((string) (
                $attachment['content_type']
                ?? $detectedType
                ?: 'application/octet-stream'
            ))
            : (
                $detectedType
                ?: 'application/octet-stream'
            );

        return [
            'path'         => $path,
            'filename'     => $filename,
            'content_type' => $contentType,
        ];
    }
}