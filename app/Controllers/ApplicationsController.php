<?php
namespace App\Controllers;

use App\Repositories\ApplicationRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\JobRepository;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class ApplicationsController extends BaseController
{
    public function index()
    {
        return view('applications/index', [
            'title' => 'My Applications',
            'applications' => (new ApplicationRepository())->listForApplicant(
                $this->applicantId()
            ),
        ]);
    }

    public function create(int $jobPostId)
    {
        $job = (new JobRepository())->findPublic($jobPostId);
        if (! $job) throw PageNotFoundException::forPageNotFound('Job posting not found.');

        if ((new ApplicationRepository())->exists($this->applicantId(), $jobPostId)) {
            return redirect()->to(site_url('applications'))
                ->with('error', 'You have already applied for this position.');
        }

        return view('applications/create', [
            'title' => 'Apply for ' . $job['title'],
            'job' => $job,
        ]);
    }

    public function store(int $jobPostId)
    {
        $job = (new JobRepository())->findPublic($jobPostId);
        if (! $job) throw PageNotFoundException::forPageNotFound('Job posting not found.');

        if (! $this->validate([
            'cover_letter' => 'permit_empty|max_length[5000]',
            'resume' => [
                'label' => 'Resume',
                'rules' => [
                    'uploaded[resume]',
                    'max_size[resume,5120]',
                    'ext_in[resume,pdf,doc,docx]',
                ],
            ],
        ])) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $applicationRepository = new ApplicationRepository();
        $applicationId = null;

        try {
            $applicationId = $applicationRepository->create(
                $this->applicantId(),
                $jobPostId,
                [
                    'source' => 'Apply Portal',
                    'cover_letter' => trim((string) $this->request->getPost('cover_letter')) ?: null,
                ]
            );

            (new DocumentRepository())->store(
                $this->request->getFile('resume'),
                $this->applicantId(),
                'Resume',
                $applicationId
            );
        } catch (Throwable $exception) {
            if ($applicationId) {
                $applicationRepository->deleteOwned($applicationId, $this->applicantId());
            }

            log_message('error', 'Application submission failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->back()->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('applications/' . $applicationId))
            ->with('message', 'Your application has been submitted.');
    }

    public function respond(int $id)
    {
        if (! $this->validate([
            'status_id' => 'required|is_natural_no_zero',
            'applicant_input' => 'permit_empty|max_length[2000]',
        ])) {
            return redirect()->to(site_url('applications/' . $id))
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $result = (new ApplicationRepository())->performApplicantAction(
                $id,
                $this->applicantId(),
                (int) $this->request->getPost('status_id'),
                trim((string) $this->request->getPost('applicant_input')) ?: null
            );
        } catch (Throwable $exception) {
            return redirect()->to(site_url('applications/' . $id))
                ->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('applications/' . $id))
            ->with('message', ($result['action_label'] ?? 'Your response') . ' has been recorded.');
    }

    public function withdraw(int $id)
    {
        if (! $this->validate([
            'withdrawal_reason' => 'permit_empty|max_length[500]',
        ])) {
            return redirect()->to(site_url('applications/' . $id))
                ->with('errors', $this->validator->getErrors());
        }

        try {
            (new ApplicationRepository())->withdrawOwned(
                $id,
                $this->applicantId(),
                trim((string) $this->request->getPost('withdrawal_reason')) ?: null
            );
        } catch (Throwable $exception) {
            return redirect()->to(site_url('applications/' . $id))
                ->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('applications/' . $id))
            ->with('message', 'Your application has been withdrawn.');
    }

    public function show(int $id)
    {
        $application = (new ApplicationRepository())->findOwned(
            $id,
            $this->applicantId()
        );

        if (! $application) {
            throw PageNotFoundException::forPageNotFound('Application not found.');
        }

        return view('applications/show', [
            'title' => 'Application Details',
            'application' => $application,
        ]);
    }
}
