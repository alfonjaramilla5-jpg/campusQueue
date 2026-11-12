# CampusQ MVP

CampusQ is a small Laravel and React application for campus queues. This first release focuses on five flows: department applications and approval, service creation, public ticketing and tracking, staff call/complete actions, and live queue status.

## Local setup

1. Configure `.env` for your database and mail-free local development.
2. Run `php artisan migrate`.
3. Run `php artisan campusqueue:make-admin` and follow the prompts to create the first administrator.
4. Start the app with `composer run dev`.
5. Sign in as the administrator, approve a department application, and use that department owner account to add services.
6. Open the public home page to join a service queue. The ticket link updates while its page is open.

Departments apply at `/departments/register` with their department email and password. Applications cannot accept tickets until approved. After approval, sign in using that department email; the queue desk is at `/dashboard`. Administrators use the same dashboard to review applications.

Ticket numbers are generated per service and reset each day. The tracking URL contains a random secret token, so share it only with the ticket holder.

Windows, notifications, reports, staff account management, and separate API hosting are intentionally outside this MVP.
