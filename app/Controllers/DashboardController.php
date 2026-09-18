<?php

namespace App\Controllers;

use App\Repositories\ApplicantRepository;
use App\Repositories\ApplicationRepository;
use App\Repositories\SavedJobRepository;

class DashboardController extends BaseController
{
    public function index()
    {
        $applicantId = $this->applicantId();
        $applicantRepository = new ApplicantRepository();

        return view('dashboard/index', [
            'title' => 'Applicant Dashboard',
            'applicant' => $applicantRepository->normalized(
                $applicantRepository->find($applicantId) ?? []
            ),
            'applications' => (new ApplicationRepository())->listForApplicant($applicantId),
            'savedJobs' => (new SavedJobRepository())->listForApplicant($applicantId, 4),
        ]);
    }
}
