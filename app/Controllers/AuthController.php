<?php

namespace App\Controllers;

use App\Repositories\ApplicantRepository;
use App\Repositories\AuthTokenRepository;
use App\Repositories\PasswordResetRepository;
use App\Services\ApplicantMailer;
use RuntimeException;
use Throwable;

class AuthController extends BaseController
{
    public function index()
    {
        if (session()->get('applicant_id')) {
            return redirect()->to(site_url('dashboard'));
        }

        if (! $this->request->is('post')) {
            return view('auth/login', [
                'mode' => (string) ($this->request->getGet('mode') ?: 'login'),
            ]);
        }

        if (! $this->validate([
            'email'    => 'required|valid_email|max_length[190]',
            'password' => 'required|max_length[255]',
        ])) {
            return redirect()->back()->withInput()
                ->with('mode', 'login')
                ->with('errors', $this->validator->getErrors());
        }

        $repository = new ApplicantRepository();
        $applicant = $repository->findByEmail(
            strtolower(trim((string) $this->request->getPost('email')))
        );

        if (
            ! $applicant
            || ! $repository->isActive($applicant)
            || ! $repository->verifyPassword(
                $applicant,
                (string) $this->request->getPost('password')
            )
        ) {
            return redirect()->back()->withInput()
                ->with('mode', 'login')
                ->with('error', 'Invalid email address or password.');
        }

        $normalized = $repository->normalized($applicant);

        session()->regenerate(true);
        session()->set([
            'applicant_id'    => (int) $normalized['id'],
            'applicant_name'  => $normalized['display_name'],
            'applicant_email' => $normalized['email'],
        ]);

        if ((bool) $this->request->getPost('remember')) {
            $token = (new AuthTokenRepository())->create((int) $normalized['id']);

            if ($token !== null) {
                $this->setRememberCookie($token);
            }
        }

        $redirect = (string) session()->get('redirect_after_login');
        session()->remove('redirect_after_login');

        return redirect()->to($redirect ?: site_url('dashboard'));
    }

    public function register()
    {
        if (session()->get('applicant_id')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/register');
    }

    public function storeRegistration()
    {
        if (! $this->validate([
            'first_name'      => 'required|max_length[100]',
            'middle_name'     => 'permit_empty|max_length[100]',
            'last_name'       => 'required|max_length[100]',
            'birthdate'       => 'required|valid_date[Y-m-d]',
            'email'           => 'required|valid_email|max_length[190]',
            'phone'           => 'permit_empty|max_length[40]',
            'privacy_consent' => 'required|in_list[1]',
        ])) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $birthdate = trim((string) $this->request->getPost('birthdate'));

        if ($birthdate === '' || strtotime($birthdate) === false) {
            return redirect()->back()->withInput()
                ->with('error', 'Enter a valid date of birth.');
        }

        if (strtotime($birthdate) > strtotime(date('Y-m-d'))) {
            return redirect()->back()->withInput()
                ->with('error', 'Date of birth cannot be in the future.');
        }

        $emailAddress = strtolower(trim((string) $this->request->getPost('email')));
        $repository = new ApplicantRepository();

        if ($repository->findByEmail($emailAddress)) {
            return redirect()->to(site_url('login'))
                ->with('mode', 'login')
                ->with(
                    'error',
                    'An applicant account already exists for that email. '
                    . 'Sign in or reset your password.'
                );
        }

        $plainPassword = 'Jn!' . bin2hex(random_bytes(5)) . '9a';
        $name = trim(
            (string) $this->request->getPost('first_name')
            . ' '
            . (string) $this->request->getPost('last_name')
        );
        $applicantId = null;

        try {
            $applicantId = $repository->create([
                'first_name'   => trim((string) $this->request->getPost('first_name')),
                'middle_name'  => trim((string) $this->request->getPost('middle_name')) ?: null,
                'last_name'    => trim((string) $this->request->getPost('last_name')),
                'birthdate'    => $birthdate,
                'email'        => $emailAddress,
                'phone'        => trim((string) $this->request->getPost('phone')) ?: null,
                'country'      => 'Philippines',
                'password'     => password_hash($plainPassword, PASSWORD_DEFAULT),
                'consent'      => 1,
                'consented_at' => date('Y-m-d H:i:s'),
            ]);

            (new ApplicantMailer())->sendWelcome(
                $emailAddress,
                $name,
                $plainPassword
            );
        } catch (Throwable $exception) {
            if ($applicantId !== null) {
                $repository->delete($applicantId);
            }

            log_message('error', 'Applicant registration failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            $message = ENVIRONMENT === 'production'
                ? 'The applicant account could not be created. Please try again.'
                : 'Applicant registration error: ' . $exception->getMessage();

            return redirect()->back()->withInput()->with('error', $message);
        }

        return redirect()->to(site_url('login'))
            ->with('mode', 'login')
            ->with(
                'message',
                'Your account was created. Your temporary password was sent to your email.'
            );
    }

    public function forgot()
    {
        if (! $this->validate([
            'forgot_email' => [
                'label' => 'Email address',
                'rules' => 'required|valid_email|max_length[190]',
            ],
        ])) {
            return redirect()->to(site_url('login') . '?mode=forgot')
                ->withInput()
                ->with('mode', 'forgot')
                ->with('errors', $this->validator->getErrors());
        }

        $emailAddress = strtolower(
            trim((string) $this->request->getPost('forgot_email'))
        );

        $repository = new ApplicantRepository();
        $applicant = $repository->findByEmail($emailAddress);

        if (! $applicant || ! $repository->isActive($applicant)) {
            return $this->forgotSuccessResponse();
        }

        $normalized = $repository->normalized($applicant);
        $applicantId = (int) ($normalized['id'] ?? 0);
        $resetRepository = new PasswordResetRepository();

        try {
            if ($applicantId <= 0) {
                throw new RuntimeException('The applicant ID could not be resolved.');
            }

            $token = $resetRepository->create($applicantId);
            $resetUrl = site_url('reset-password')
                . '?token='
                . rawurlencode($token);

            (new ApplicantMailer())->sendPasswordReset(
                $emailAddress,
                (string) ($normalized['display_name'] ?? $emailAddress),
                $resetUrl
            );
        } catch (Throwable $exception) {
            $resetRepository->invalidateForApplicant($applicantId);

            log_message(
                'error',
                'Password-reset request failed for {email}: {message}',
                ['email' => $emailAddress, 'message' => $exception->getMessage()]
            );

            if (ENVIRONMENT !== 'production') {
                return redirect()->to(site_url('login') . '?mode=forgot')
                    ->with('mode', 'forgot')
                    ->with('error', 'Password-reset error: ' . $exception->getMessage());
            }
        }

        return $this->forgotSuccessResponse();
    }

    public function resetForm()
    {
        $token = trim((string) $this->request->getGet('token'));

        if ($token === '' || ! (new PasswordResetRepository())->valid($token)) {
            return redirect()->to(site_url('login') . '?mode=forgot')
                ->with('mode', 'forgot')
                ->with('error', 'The password-reset link is invalid or has expired.');
        }

        return view('auth/reset_password', ['token' => $token]);
    }

    public function resetPassword()
    {
        if (! $this->validate([
            'token'                 => 'required',
            'password'              => 'required|min_length[10]|max_length[255]',
            'password_confirmation' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $applicantId = (new PasswordResetRepository())->consume(
            (string) $this->request->getPost('token')
        );

        if (! $applicantId) {
            return redirect()->to(site_url('login') . '?mode=forgot')
                ->with('mode', 'forgot')
                ->with('error', 'The password-reset link is invalid or has expired.');
        }

        (new ApplicantRepository())->updatePassword(
            $applicantId,
            password_hash(
                (string) $this->request->getPost('password'),
                PASSWORD_DEFAULT
            )
        );

        (new AuthTokenRepository())->revokeForApplicant($applicantId);

        return redirect()->to(site_url('login'))
            ->with('message', 'Your password has been changed. You may now sign in.');
    }

    public function logout()
    {
        $cookie = (string) ($this->request->getCookie('jng_apply_remember') ?? '');

        if ($cookie !== '') {
            (new AuthTokenRepository())->revoke($cookie);
        }

        setcookie('jng_apply_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $this->request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session()->destroy();

        return redirect()->to(site_url('login'))
            ->with('message', 'You have been signed out.');
    }

    private function forgotSuccessResponse()
    {
        return redirect()->to(site_url('login') . '?mode=forgot')
            ->with('mode', 'forgot')
            ->with(
                'message',
                'If the email is registered, password-reset instructions have been sent.'
            );
    }

    private function setRememberCookie(string $token): void
    {
        $config = config('Careers');

        setcookie('jng_apply_remember', $token, [
            'expires'  => time() + ($config->rememberDays * DAY),
            'path'     => '/',
            'secure'   => $this->request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
