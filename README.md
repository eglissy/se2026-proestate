# ProEstate — Platformë Web për Menaxhimin e Pronave të Paluajtshme dhe Qirave

## Përshkrimi
ProEstate është një platformë web për menaxhimin e pronave të paluajtshme dhe qirave. Sistemi u mundëson agjentëve imobiliarë, pronarëve dhe klientëve të menaxhojnë prona, të kërkojnë prona me filtra, të rezervojnë takime dhe të komunikojnë në mënyrë më të strukturuar.

## Project Management

Menaxhimi i projektit është realizuar me GitHub Projects, Issues, Milestones dhe Pull Requests.

- Repository: https://github.com/eglissy/se2026-proestate
- Project Board: https://github.com/users/eglissy/projects/2
- Dokumentacioni: `/docs`
- Kodi burimor: `/src`

## Anëtarët e ekipit
- Eglis Haderaj — Team Lead
- Eriseld Memia — Developer / UI
- Harilla Bica — Developer / Testing

## Teknologjitë
- Frontend: HTML5, CSS3, JavaScript, jQuery, AJAX
- Backend: PHP 8+
- Database: MySQL 8+
- Server lokal: XAMPP / Laragon
- Version Control: Git + GitHub
- Project Management: GitHub Projects

## Funksionalitetet kryesore
- Regjistrim dhe login përdoruesish
- Role përdoruesish: Admin, Agent, Owner, Client
- Menaxhim pronash CRUD
- Upload fotosh dhe dokumentesh
- Kërkim i avancuar me filtra
- Rezervim takimesh
- Njoftime me email
- Panel administratori
- Favorites, reviews dhe messaging si funksionalitete shtesë

## Struktura e projektit
```text
se2026-proestate/
├── README.md
├── .gitignore
├── .github/
│   └── ISSUE_TEMPLATE/
│       ├── feature.md
│       └── bug.md
├── docs/
│   ├── SRS.md
│   ├── SDD.md
│   ├── db-schema.md
│   ├── test-report.md
│   ├── deployment.md
│   ├── user-manual.md
│   ├── github-issues-plan.md
│   ├── sprint-1-review.md
│   ├── sprint-1-retro.md
│   ├── sprint-2-review.md
│   ├── sprint-2-retro.md
│   ├── sprint-3-review.md
│   ├── sprint-3-retro.md
│   ├── sprint-4-review.md
│   ├── sprint-4-retro.md
│   └── wireframes/
├── src/
│   ├── .env.example
│   ├── .htaccess
│   ├── README.md
│   ├── about.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── forgot-password.php
│   ├── reset-password.php
│   ├── verify-email.php
│   ├── resend-verification.php
│   ├── properties.php
│   ├── property.php
│   ├── agents.php
│   ├── agent.php
│   ├── contact.php
│   ├── payment-success.php
│   ├── setup.php
│   ├── admin/
│   │   ├── index.php
│   │   ├── login.php
│   │   ├── properties.php
│   │   └── users.php
│   ├── api/
│   │   ├── admin-actions.php
│   │   ├── chatbot.php
│   │   ├── favorites.php
│   │   ├── paypal-create-order.php
│   │   ├── paypal-capture-order.php
│   │   └── upload.php
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css
│   │   ├── js/
│   │   │   └── main.js
│   │   └── images/
│   │       ├── default-avatar.svg
│   │       ├── favicon.svg
│   │       └── property-placeholder.svg
│   ├── config/
│   │   └── config.php
│   ├── dashboard/
│   │   ├── index.php
│   │   ├── add-property.php
│   │   ├── edit-property.php
│   │   ├── my-properties.php
│   │   ├── appointments.php
│   │   ├── favorites.php
│   │   ├── messages.php
│   │   ├── payments.php
│   │   ├── profile.php
│   │   └── sidebar.php
│   ├── database/
│   │   └── proesta.sql
│   ├── includes/
│   │   ├── auth.php
│   │   ├── db.php
│   │   ├── email.php
│   │   ├── functions.php
│   │   └── security.php
│   ├── templates/
│   │   ├── header.php
│   │   └── footer.php
│   └── uploads/
│       ├── .htaccess
│       ├── avatars/
│       ├── documents/
│       └── properties/
└── tests/
```

## Workflow
Çdo punë fillon si GitHub Issue. Për çdo issue krijohet branch i veçantë, hapet Pull Request, bëhet review nga një anëtar tjetër dhe pastaj bëhet merge në `main`.

## Branch naming
- `feature/[nr-issue]-[pershkrim]`
- `bugfix/[nr-issue]-[pershkrim]`
- `docs/[pershkrim]`
- `devops/[pershkrim]`

## Commit format
Përdoret formati Conventional Commits:
```text
type(scope): description
```



## Final Submission

Ky repository përmban dorëzimin final të projektit ProEstate për lëndën Inxhinieri Softi.

Përmbajtja kryesore:
- Kodi burimor i aplikacionit ndodhet në `/src`.
- Dokumentacioni i projektit ndodhet në `/docs`.
- Menaxhimi i projektit është realizuar me GitHub Projects.
- Detyrat janë ndarë në GitHub Issues dhe Milestones sipas 4 sprinteve.
- Ndryshimet kryesore janë menaxhuar me Pull Requests.

Linke kryesore:
- Repository: https://github.com/eglissy/se2026-proestate
- Project Board: https://github.com/users/eglissy/projects/2

Shembuj:
```text
feat(auth): add user registration form
fix(auth): resolve login redirect issue
docs: add SRS document
test(properties): add property CRUD test cases
```
