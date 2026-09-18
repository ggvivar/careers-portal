# JNG Apply Portal v3.8

Applicant-facing CodeIgniter 4 application for the shared JNG Careers
database.

## Included portal updates

- Modern compact interface with smaller typography
- Direct Apply action on job cards
- Applied-state indicator for previously submitted jobs
- Saved Jobs with direct Apply actions
- Salary range from `job_list.salary_range`
- Experience range from `job_list.experience_range`
- Full-width Profile Health card above profile tabs
- Mobile profile sections that collapse independently
- Date of birth collected during registration
- Existing application progress, withdrawal, notifications, and profile
  fields retained

## Database ownership

The Apply Portal does not contain migrations, SQL patches, seeders, or schema
upgrade commands.

The JNG Careers / Recruitment Hub application remains the only database schema
owner. This portal only reads and writes the existing Careers tables.

The latest Careers application already provides:

- `job_list.salary_range`
- `job_list.experience_range`
- applicant profile fields
- saved jobs
- application withdrawal fields

Do not run `php spark migrate` from this application.

## Installation

1. Copy `.env.example` to `.env`.
2. Configure the same Careers database.
3. Configure the working JNG Mailer credentials.
4. Run:

```powershell
composer install
composer dump-autoload
php spark cache:clear
```

Point the web server document root to `public/`.
