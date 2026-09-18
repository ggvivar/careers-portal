<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    public string $csrfProtection = 'session';
    public bool $tokenRandomize = false;
    public string $tokenName = 'jng_apply_csrf_token';
    public string $headerName = 'X-JNG-APPLY-CSRF-TOKEN';
    public string $cookieName = 'jng_apply_csrf';
    public int $expires = 7200;
    public bool $regenerate = true;
    public bool $redirect = ENVIRONMENT === 'production';
}
