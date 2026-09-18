<?php

namespace App\Controllers;

use App\Repositories\ApplicationRepository;
use App\Repositories\JobRepository;
use App\Repositories\SavedJobRepository;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class JobsController extends BaseController
{
    public function index()
    {
        $page = max(1, (int) $this->request->getGet('page'));
        $result = (new JobRepository())->paginate([
            'q'        => $this->request->getGet('q'),
            'location' => $this->request->getGet('location'),
            'type'     => $this->request->getGet('type'),
        ], $page, 12);

        $applicantId = (int) session()->get('applicant_id');
        $savedIds = $applicantId > 0
            ? (new SavedJobRepository())->idsForApplicant($applicantId)
            : [];
        $appliedIds = $applicantId > 0
            ? (new ApplicationRepository())->jobPostIdsForApplicant($applicantId)
            : [];

        return view('jobs/index', [
            'title'  => 'Open Positions',
            'result' => $result,
            'savedJobIds' => $savedIds,
            'appliedJobIds' => $appliedIds,
            'filters' => [
                'q'        => (string) $this->request->getGet('q'),
                'location' => (string) $this->request->getGet('location'),
                'type'     => (string) $this->request->getGet('type'),
            ],
        ]);
    }

    public function show(int $id)
    {
        $job = (new JobRepository())->findPublic($id);
        if (! $job) {
            throw PageNotFoundException::forPageNotFound('Job posting not found.');
        }

        $applicantId = (int) session()->get('applicant_id');

        return view('jobs/show', [
            'title'          => $job['title'],
            'job'            => $job,
            'alreadyApplied' => $applicantId > 0
                && (new ApplicationRepository())->exists($applicantId, $id),
            'saved'          => $applicantId > 0
                && (new SavedJobRepository())->exists($applicantId, $id),
        ]);
    }

    public function saved()
    {
        $applicantId = $this->applicantId();

        return view('jobs/saved', [
            'title'         => 'Saved Jobs',
            'jobs'          => (new SavedJobRepository())->listForApplicant($applicantId),
            'appliedJobIds' => (new ApplicationRepository())->jobPostIdsForApplicant($applicantId),
        ]);
    }

    public function save(int $id)
    {
        if (! (new JobRepository())->findPublic($id)) {
            throw PageNotFoundException::forPageNotFound('Job posting not found.');
        }

        try {
            (new SavedJobRepository())->save($this->applicantId(), $id);
        } catch (Throwable $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('message', 'Job saved to your list.');
    }

    public function unsave(int $id)
    {
        try {
            (new SavedJobRepository())->remove($this->applicantId(), $id);
        } catch (Throwable $exception) {
            return redirect()->back()->with('error', $exception->getMessage());
        }

        return redirect()->back()->with('message', 'Job removed from your saved list.');
    }
}
