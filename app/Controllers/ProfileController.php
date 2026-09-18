<?php
namespace App\Controllers;

use App\Repositories\ApplicantRepository;
use App\Repositories\CommonDefaultRepository;
use App\Repositories\AuthTokenRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\ProfileRepository;
use App\Services\ProfileHealthService;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;

class ProfileController extends BaseController
{
    public function index()
    {
        $applicantRepository = new ApplicantRepository();
        $profileRepository = new ProfileRepository();
        $documentRepository = new DocumentRepository();
        $commonDefaults = new CommonDefaultRepository();

        $applicant = $applicantRepository->normalized(
            $applicantRepository->find($this->applicantId()) ?? []
        );
        $education = $profileRepository->education($this->applicantId());
        $employment = $profileRepository->employment($this->applicantId());
        $documents = $documentRepository->listForApplicant($this->applicantId());
        $hiredApplication = $profileRepository->hiredApplication($this->applicantId());

        if ($hiredApplication) {
            $profileRepository->ensureEmployeeDetailsForApplicant($this->applicantId());
        }

        return view('profile/index', [
            'title' => 'My Profile',
            'applicant' => $applicant,
            'education' => $education,
            'employment' => $employment,
            'documents' => $documents,
            'employeeDetails' => $profileRepository->employeeDetails($this->applicantId()),
            'isHired' => $hiredApplication !== null,
            'hiredApplication' => $hiredApplication,
            'genderOptions' => $commonDefaults->options('Gender'),
            'civilStatusOptions' => $commonDefaults->options('Civil Status'),
            'relationshipOptions' => $commonDefaults->options('Relationship'),
            'profileHealth' => (new ProfileHealthService())->evaluate(
                $applicant,
                $education,
                $employment,
                $documents
            ),
        ]);
    }

    public function update()
    {
        if (! $this->validate([
            'first_name' => 'required|max_length[100]',
            'middle_name' => 'permit_empty|max_length[100]',
            'last_name' => 'required|max_length[100]',
            'suffix' => 'permit_empty|max_length[20]',
            'birthdate' => 'permit_empty|valid_date[Y-m-d]',
            'gender' => 'permit_empty|max_length[20]',
            'nationality' => 'permit_empty|max_length[100]',
            'email' => 'required|valid_email|max_length[190]',
            'phone' => 'permit_empty|max_length[40]',
            'city' => 'permit_empty|max_length[120]',
            'province' => 'permit_empty|max_length[120]',
            'country' => 'permit_empty|max_length[120]',
            'zip_code' => 'permit_empty|max_length[20]',
            'linkedin_url' => 'permit_empty|valid_url_strict|max_length[500]',
            'portfolio_url' => 'permit_empty|valid_url_strict|max_length[500]',
            'religion' => 'permit_empty|max_length[100]',
            'civil_status' => 'permit_empty|max_length[100]',
            'current_address' => 'permit_empty|max_length[1000]',
            'permanent_address' => 'permit_empty|max_length[1000]',
            'company_relative_name' => 'permit_empty|max_length[190]',
            'company_relative_relation' => 'permit_empty|max_length[100]',
            'company_relative_relation_other' => 'permit_empty|max_length[100]',
        ])) {
            return redirect()->to(site_url('profile'))->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $commonDefaults = new CommonDefaultRepository();
        $civilStatus = trim((string) $this->request->getPost('civil_status'));
        $gender = trim((string) $this->request->getPost('gender'));
        $hasCompanyRelative = (bool) $this->request->getPost('has_company_relative');
        $relativeName = trim((string) $this->request->getPost('company_relative_name'));
        $relativeRelation = trim((string) $this->request->getPost('company_relative_relation'));
        $relativeRelationOther = trim((string) $this->request->getPost('company_relative_relation_other'));
        $isOtherRelation = in_array(strtolower($relativeRelation), ['other', 'others'], true);

        if (! $commonDefaults->contains('Civil Status', $civilStatus)) {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Select a valid civil status from Common Defaults.');
        }

        if ($gender !== '' && ! $commonDefaults->contains('Gender', $gender)) {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Select a valid gender from Common Defaults.');
        }

        if ($hasCompanyRelative && $relativeName === '') {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Enter the name of your relative working in the company.');
        }

        if ($hasCompanyRelative && $relativeRelation === '') {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Select your relationship to the company employee.');
        }

        if ($hasCompanyRelative && ! $commonDefaults->contains('Relationship', $relativeRelation)) {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Select a valid relationship from Common Defaults.');
        }

        if ($hasCompanyRelative && $isOtherRelation && $relativeRelationOther === '') {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Specify the relationship when Other is selected.');
        }

        $birthdate = $this->request->getPost('birthdate') ?: null;
        if ($birthdate && strtotime((string) $birthdate) > time()) {
            return redirect()->to(site_url('profile') . '#personal')->withInput()
                ->with('error', 'Date of birth cannot be in the future.');
        }

        $repository = new ApplicantRepository();
        $existing = $repository->findByEmail((string) $this->request->getPost('email'));
        $normalized = $existing ? $repository->normalized($existing) : null;

        if ($normalized && (int) $normalized['id'] !== $this->applicantId()) {
            return redirect()->to(site_url('profile'))->withInput()
                ->with('error', 'That email address is already used by another applicant account.');
        }

        $repository->updateProfile($this->applicantId(), [
            'first_name' => trim((string) $this->request->getPost('first_name')),
            'middle_name' => trim((string) $this->request->getPost('middle_name')) ?: null,
            'last_name' => trim((string) $this->request->getPost('last_name')),
            'suffix' => trim((string) $this->request->getPost('suffix')) ?: null,
            'birthdate' => $birthdate,
            'gender' => $gender ?: null,
            'nationality' => trim((string) $this->request->getPost('nationality')) ?: null,
            'email' => strtolower(trim((string) $this->request->getPost('email'))),
            'phone' => trim((string) $this->request->getPost('phone')) ?: null,
            'city' => trim((string) $this->request->getPost('city')) ?: null,
            'province' => trim((string) $this->request->getPost('province')) ?: null,
            'country' => trim((string) $this->request->getPost('country')) ?: 'Philippines',
            'zip_code' => trim((string) $this->request->getPost('zip_code')) ?: null,
            'linkedin_url' => trim((string) $this->request->getPost('linkedin_url')) ?: null,
            'portfolio_url' => trim((string) $this->request->getPost('portfolio_url')) ?: null,
            'religion' => trim((string) $this->request->getPost('religion')) ?: null,
            'civil_status' => $civilStatus ?: null,
            'current_address' => trim((string) $this->request->getPost('current_address')) ?: null,
            'permanent_address' => trim((string) $this->request->getPost('permanent_address')) ?: null,
            'covid_vaccinated' => (int) ((bool) $this->request->getPost('covid_vaccinated')),
            'has_company_relative' => (int) $hasCompanyRelative,
            'company_relative_name' => $hasCompanyRelative ? $relativeName : null,
            'company_relative_relation' => $hasCompanyRelative ? $relativeRelation : null,
            'company_relative_relation_other' => ($hasCompanyRelative && $isOtherRelation) ? $relativeRelationOther : null,
        ]);

        session()->set([
            'applicant_name' => trim(
                (string) $this->request->getPost('first_name')
                . ' '
                . (string) $this->request->getPost('last_name')
            ),
            'applicant_email' => strtolower(trim((string) $this->request->getPost('email'))),
        ]);

        return redirect()->to(site_url('profile'))
            ->with('message', 'Your profile has been updated.');
    }

    public function updateEmployeeDetails()
    {
        if (! $this->validate([
            'sss_no' => 'permit_empty|max_length[50]',
            'tin_no' => 'permit_empty|max_length[50]',
            'philhealth_no' => 'permit_empty|max_length[50]',
            'pagibig_no' => 'permit_empty|max_length[50]',
        ])) {
            return redirect()->to(site_url('profile') . '#employee-details')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            (new ProfileRepository())->saveApplicantGovernmentIds($this->applicantId(), [
                'sss_no' => $this->request->getPost('sss_no'),
                'tin_no' => $this->request->getPost('tin_no'),
                'philhealth_no' => $this->request->getPost('philhealth_no'),
                'pagibig_no' => $this->request->getPost('pagibig_no'),
            ]);
        } catch (Throwable $exception) {
            return redirect()->to(site_url('profile') . '#employee-details')
                ->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('profile') . '#employee-details')
            ->with('message', 'Employee government numbers updated.');
    }

    public function updatePassword()
    {
        if (! $this->validate([
            'current_password'         => 'required|max_length[255]',
            'new_password'             => 'required|min_length[10]|max_length[255]',
            'new_password_confirmation' => 'required|matches[new_password]',
        ])) {
            return redirect()->to(site_url('profile') . '#security')
                ->with('errors', $this->validator->getErrors());
        }

        $repository = new ApplicantRepository();
        $applicant = $repository->find($this->applicantId());

        if (
            ! $applicant
            || ! $repository->verifyPassword(
                $applicant,
                (string) $this->request->getPost('current_password')
            )
        ) {
            return redirect()->to(site_url('profile') . '#security')
                ->with('error', 'The current password is incorrect.');
        }

        $repository->updatePassword(
            $this->applicantId(),
            password_hash(
                (string) $this->request->getPost('new_password'),
                PASSWORD_DEFAULT
            )
        );

        (new AuthTokenRepository())->revokeForApplicant($this->applicantId());

        return redirect()->to(site_url('profile') . '#security')
            ->with('message', 'Your password has been changed.');
    }

    public function storeEducation()
    {
        if (! $this->validate([
            'school_name' => 'required|max_length[150]',
            'degree' => 'permit_empty|max_length[150]',
            'field_of_study' => 'permit_empty|max_length[150]',
            'start_year' => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[2100]',
            'end_year' => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[2100]',
            'honors' => 'permit_empty|max_length[150]',
        ])) {
            return redirect()->to(site_url('profile') . '#education')->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $startYear = trim((string) $this->request->getPost('start_year'));
        $endYear = trim((string) $this->request->getPost('end_year'));
        if ($startYear !== '' && $endYear !== '' && (int) $endYear < (int) $startYear) {
            return redirect()->to(site_url('profile') . '#education')->withInput()
                ->with('error', 'Education end year cannot be earlier than the start year.');
        }

        (new ProfileRepository())->addEducation($this->applicantId(), [
            'school_name' => trim((string) $this->request->getPost('school_name')),
            'degree' => trim((string) $this->request->getPost('degree')) ?: null,
            'field_of_study' => trim((string) $this->request->getPost('field_of_study')) ?: null,
            'start_year' => $startYear ?: null,
            'end_year' => $endYear ?: null,
            'honors' => trim((string) $this->request->getPost('honors')) ?: null,
        ]);

        return redirect()->to(site_url('profile') . '#education')
            ->with('message', 'Education record added.');
    }

    public function deleteEducation(int $id)
    {
        (new ProfileRepository())->deleteEducation($id, $this->applicantId());
        return redirect()->to(site_url('profile') . '#education')
            ->with('message', 'Education record removed.');
    }

    public function storeEmployment()
    {
        if (! $this->validate([
            'company_name' => 'required|max_length[150]',
            'company_address' => 'permit_empty|max_length[255]',
            'job_title' => 'permit_empty|max_length[150]',
            'department' => 'permit_empty|max_length[150]',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'responsibilities' => 'permit_empty|max_length[5000]',
            'salary' => 'permit_empty|decimal',
            'reason_for_leaving' => 'permit_empty|max_length[255]',
        ])) {
            return redirect()->to(site_url('profile') . '#employment')->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $currentlyWorking = (bool) $this->request->getPost('currently_working');
        $startDate = $this->request->getPost('start_date') ?: null;
        $endDate = $currentlyWorking ? null : ($this->request->getPost('end_date') ?: null);

        if ($startDate && $endDate && strtotime((string) $endDate) < strtotime((string) $startDate)) {
            return redirect()->to(site_url('profile') . '#employment')->withInput()
                ->with('error', 'Employment end date cannot be earlier than the start date.');
        }

        (new ProfileRepository())->addEmployment($this->applicantId(), [
            'company_name' => trim((string) $this->request->getPost('company_name')),
            'company_address' => trim((string) $this->request->getPost('company_address')) ?: null,
            'job_title' => trim((string) $this->request->getPost('job_title')) ?: null,
            'department' => trim((string) $this->request->getPost('department')) ?: null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'currently_working' => (int) $currentlyWorking,
            'responsibilities' => trim((string) $this->request->getPost('responsibilities')) ?: null,
            'salary' => trim((string) $this->request->getPost('salary')) ?: null,
            'reason_for_leaving' => $currentlyWorking
                ? null
                : (trim((string) $this->request->getPost('reason_for_leaving')) ?: null),
        ]);

        return redirect()->to(site_url('profile') . '#employment')
            ->with('message', 'Employment record added.');
    }

    public function deleteEmployment(int $id)
    {
        (new ProfileRepository())->deleteEmployment($id, $this->applicantId());
        return redirect()->to(site_url('profile') . '#employment')
            ->with('message', 'Employment record removed.');
    }

    public function storeDocument()
    {
        if (! $this->validate([
            'document_type' => 'required|max_length[100]',
            'document' => [
                'label' => 'Document',
                'rules' => [
                    'uploaded[document]',
                    'max_size[document,5120]',
                    'ext_in[document,pdf,doc,docx,jpg,jpeg,png]',
                ],
            ],
        ])) {
            return redirect()->to(site_url('profile') . '#documents')
                ->with('errors', $this->validator->getErrors());
        }

        try {
            (new DocumentRepository())->store(
                $this->request->getFile('document'),
                $this->applicantId(),
                trim((string) $this->request->getPost('document_type'))
            );
        } catch (Throwable $exception) {
            return redirect()->to(site_url('profile') . '#documents')
                ->with('error', $exception->getMessage());
        }

        return redirect()->to(site_url('profile') . '#documents')
            ->with('message', 'Document uploaded.');
    }

    public function downloadDocument(int $id)
    {
        $repository = new DocumentRepository();
        $document = $repository->findOwned($id, $this->applicantId());

        if (! $document) throw PageNotFoundException::forPageNotFound('Document not found.');

        $path = $repository->absolutePath($document);
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('Stored document file not found.');
        }

        return $this->response->download($path, null)
            ->setFileName((string) ($document['original_name'] ?: basename($path)));
    }

    public function deleteDocument(int $id)
    {
        (new DocumentRepository())->deleteOwned($id, $this->applicantId());
        return redirect()->to(site_url('profile') . '#documents')
            ->with('message', 'Document removed.');
    }
}
