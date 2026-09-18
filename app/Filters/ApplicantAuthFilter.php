<?php
namespace App\Filters;

use App\Repositories\ApplicantRepository;
use App\Repositories\AuthTokenRepository;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApplicantAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('applicant_id')) {
            return null;
        }

        $cookie = (string) ($request->getCookie('jng_apply_remember') ?? '');
        if ($cookie !== '') {
            $id = (new AuthTokenRepository())->resolve($cookie);
            if ($id) {
                $repository = new ApplicantRepository();
                $applicant = $repository->find($id);

                if ($applicant && $repository->isActive($applicant)) {
                    session()->regenerate(true);
                    session()->set([
                        'applicant_id' => $id,
                        'applicant_name' => $repository->displayName($applicant),
                        'applicant_email' => $repository->email($applicant),
                    ]);
                    return null;
                }
            }
        }

        session()->set('redirect_after_login', current_url());

        return redirect()
            ->to(site_url('login'))
            ->with('error', 'Please sign in to continue.');
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        return null;
    }
}
