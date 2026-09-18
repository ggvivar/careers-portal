# Deployment

## Apply Portal

The Apply Portal connects to the shared Careers database but cannot create,
alter, migrate, or seed its schema.

Deploy the database changes from the JNG Careers / Recruitment Hub project
first. Then deploy this application.

## XAMPP

```powershell
Copy-Item .env.xampp.example .env
composer install
composer dump-autoload
php spark cache:clear
```

Configure `app.baseURL`, the shared Careers database credentials, and the JNG
Mailer credentials in `.env`.

## Production

Point the web root to `public/`. Use a database account limited to the tables
and operations required by applicants. Keep recruitment administration and
migration privileges with the Careers application only.
