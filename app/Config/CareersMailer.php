<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CareersMailer extends BaseConfig
{
    public string $endpoint;
    public string $authHeader;
    public string $authorization;
    public string $authPrefix;
    public float $timeout;

    public function __construct()
    {
        parent::__construct();

        $this->endpoint = trim((string) env(
            'careersMailer.endpoint',
            'https://jn-mailer-staging.joy-nostalg.com/send/careers'
        ));

        $this->authHeader = trim((string) env(
            'careersMailer.authHeader',
            'Authorization'
        ));

        $this->authorization = trim((string) env(
            'careersMailer.authorization',
            ''
        ));

        $this->authPrefix = trim((string) env(
            'careersMailer.authPrefix',
            ''
        ));

        $this->timeout = (float) env(
            'careersMailer.timeout',
            30
        );
    }

    public function authorizationValue(): string
    {
        if ($this->authPrefix === '') {
            return $this->authorization;
        }

        return trim(
            $this->authPrefix . ' ' . $this->authorization
        );
    }
}