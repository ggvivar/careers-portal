<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\FileHandler;
use RuntimeException;

class Session extends BaseConfig
{
    public string $driver = FileHandler::class;
    public string $cookieName = 'jng_apply_session';
    public int $expiration = 7200;
    public string $savePath = WRITEPATH . 'session';
    public bool $matchIP = false;
    public int $timeToUpdate = 300;
    public bool $regenerateDestroy = false;
    public ?string $DBGroup = null;
    public int $lockRetryInterval = 100000;
    public int $lockMaxRetries = 300;

    public function __construct()
    {
        parent::__construct();

        // Older packages incorrectly placed the literal value
        // "WRITEPATH/session" in .env. Environment values do not evaluate
        // PHP constants, so normalize that legacy value automatically.
        $configuredPath = trim($this->savePath);

        if (
            $configuredPath === ''
            || str_starts_with(str_replace('\\', '/', $configuredPath), 'WRITEPATH/')
        ) {
            $configuredPath = WRITEPATH . 'session';
        }

        $this->savePath = rtrim($configuredPath, '\\/');

        if (
            ! is_dir($this->savePath)
            && ! mkdir($this->savePath, 0775, true)
            && ! is_dir($this->savePath)
        ) {
            throw new RuntimeException(
                'Unable to create the session directory: ' . $this->savePath
            );
        }
    }
}
