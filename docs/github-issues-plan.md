# GitHub Issues Plan — ProEstate

## Issue #1: Setup repository + README

**Description:** Krijo repo publike, README dhe .gitignore për ProEstate.

**Acceptance Criteria:**
- Repo ekziston; README ka emrin, ekipin, përshkrimin; .gitignore është shtuar.

**Labels:** devops  
**Priority:** High  
**Effort:** 1  
**Type:** DevOps  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 1

## Issue #2: Krijo GitHub Project Board

**Description:** Krijo board Kanban me kolonat e kërkuara.

**Acceptance Criteria:**
- Kolonat Backlog, Sprint, In Progress, In Review, Done ekzistojnë; custom fields janë shtuar.

**Labels:** devops  
**Priority:** High  
**Effort:** 2  
**Type:** DevOps  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 1

## Issue #3: Konfiguro labels dhe milestones

**Description:** Shto labels dhe milestones për sprintet.

**Acceptance Criteria:**
- Labels feature, bug, docs, testing, ui/ux, devops, blocked ekzistojnë; Sprint 1-4 janë milestones.

**Labels:** devops  
**Priority:** High  
**Effort:** 2  
**Type:** DevOps  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 1

## Issue #4: Shkruaj SRS v1

**Description:** Përgatit dokumentin e kërkesave për ProEstate.

**Acceptance Criteria:**
- SRS.md në /docs; ka kërkesa funksionale; ka kërkesa jo-funksionale; ka use cases.

**Labels:** docs  
**Priority:** High  
**Effort:** 5  
**Type:** Docs  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 1

## Issue #5: Krijo wireframes lo-fi

**Description:** Përgatit wireframes për faqet kryesore.

**Acceptance Criteria:**
- Wireframes për login, register, properties, property details, dashboard; ruhen në /docs/wireframes.

**Labels:** docs, ui/ux  
**Priority:** Medium  
**Effort:** 3  
**Type:** Docs  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 1

## Issue #6: Krijo strukturën bazë të folderave

**Description:** Krijo strukturën src, docs, tests dhe .github.

**Acceptance Criteria:**
- Folderat ekzistojnë; issue templates ekzistojnë; struktura përputhet me udhëzuesin.

**Labels:** devops  
**Priority:** High  
**Effort:** 2  
**Type:** DevOps  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 1

## Issue #7: Përgatit template për feature issue

**Description:** Shto feature.md në .github/ISSUE_TEMPLATE.

**Acceptance Criteria:**
- Template ka description, acceptance criteria, shënime teknike dhe testim.

**Labels:** docs  
**Priority:** Medium  
**Effort:** 1  
**Type:** Docs  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 1

## Issue #8: Përgatit template për bug issue

**Description:** Shto bug.md në .github/ISSUE_TEMPLATE.

**Acceptance Criteria:**
- Template ka përshkrim, hapa riprodhimi, sjellje të pritur dhe screenshots.

**Labels:** docs  
**Priority:** Medium  
**Effort:** 1  
**Type:** Docs  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 1

## Issue #9: Shkruaj SDD v1

**Description:** Përgatit dokumentin e design-it të softuerit.

**Acceptance Criteria:**
- SDD.md ka arkitekturën, modulet, sigurinë, strukturën e folderave dhe rrjedhat kryesore.

**Labels:** docs  
**Priority:** High  
**Effort:** 5  
**Type:** Docs  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 2

## Issue #10: Përgatit DB schema

**Description:** Dokumento tabelat kryesore të databazës.

**Acceptance Criteria:**
- db-schema.md ka users, properties, images, documents, appointments, reviews, messages, favorites.

**Labels:** docs  
**Priority:** High  
**Effort:** 3  
**Type:** Docs  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 2

## Issue #11: Implemento formën e regjistrimit

**Description:** Krijo frontend dhe backend për regjistrim.

**Acceptance Criteria:**
- Forma validohet; email unik; password hash; mesazh suksesi.

**Labels:** feature  
**Priority:** High  
**Effort:** 5  
**Type:** Feature  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 2

## Issue #12: Implemento login dhe logout

**Description:** Krijo hyrje/dalje me session.

**Acceptance Criteria:**
- Login punon; logout mbyll session; gabimet shfaqen qartë.

**Labels:** feature  
**Priority:** High  
**Effort:** 3  
**Type:** Feature  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 2

## Issue #13: Implemento role-based access

**Description:** Kufizo faqet sipas roleve.

**Acceptance Criteria:**
- Admin, agent, owner, client kanë akses të ndarë; faqet e ndaluara bllokohen.

**Labels:** feature  
**Priority:** High  
**Effort:** 5  
**Type:** Feature  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 2

## Issue #14: Implemento CRUD për prona

**Description:** Shto, lexo, ndrysho dhe fshi prona.

**Acceptance Criteria:**
- Prona krijohet; editohet; fshihet/arkivohet; përdoruesi sheh vetëm pronat e veta.

**Labels:** feature  
**Priority:** High  
**Effort:** 8  
**Type:** Feature  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 2

## Issue #15: Krijo dashboard bazë

**Description:** Krijo dashboard për përdoruesit e loguar.

**Acceptance Criteria:**
- Dashboard shfaq emrin, rolin, statistika bazë dhe navigim.

**Labels:** feature, ui/ux  
**Priority:** Medium  
**Effort:** 3  
**Type:** Feature  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 2

## Issue #16: Implemento upload fotosh

**Description:** Shto upload për fotot e pronës.

**Acceptance Criteria:**
- Pranohen jpg/png; max 5 foto; madhësia kontrollohet; foto ruhet në DB.

**Labels:** feature  
**Priority:** High  
**Effort:** 5  
**Type:** Feature  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 3

## Issue #17: Implemento upload dokumentesh

**Description:** Shto upload për PDF të pronës.

**Acceptance Criteria:**
- Pranohen vetëm PDF; max 3 dokumente; madhësia kontrollohet.

**Labels:** feature  
**Priority:** Medium  
**Effort:** 3  
**Type:** Feature  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 3

## Issue #18: Implemento kërkimin me filtra

**Description:** Shto kërkim sipas qytetit, çmimit, tipit, dhomave dhe sipërfaqes.

**Acceptance Criteria:**
- Filtrat punojnë të kombinuar; rezultatet janë të sakta; ka pagination.

**Labels:** feature  
**Priority:** High  
**Effort:** 8  
**Type:** Feature  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 3

## Issue #19: Implemento detajet e pronës

**Description:** Krijo faqen e plotë të pronës.

**Acceptance Criteria:**
- Shfaq foto, çmim, lokacion, përshkrim, agjent dhe buton rezervimi.

**Labels:** feature, ui/ux  
**Priority:** High  
**Effort:** 5  
**Type:** Feature  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 3

## Issue #20: Implemento rezervimin e takimeve

**Description:** Klienti rezervon takim për një pronë.

**Acceptance Criteria:**
- Datë/orë zgjidhet; overlap kontrollohet; statusi ruhet; mesazh suksesi.

**Labels:** feature  
**Priority:** High  
**Effort:** 8  
**Type:** Feature  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 3

## Issue #21: Shkruaj test report

**Description:** Përgatit planin dhe raportin e testimit.

**Acceptance Criteria:**
- test-report.md ka test cases, rezultate dhe bugs të gjetura.

**Labels:** testing, docs  
**Priority:** High  
**Effort:** 3  
**Type:** Test  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 3

## Issue #22: Rregullo responsive design

**Description:** Përmirëso UI për mobile dhe tablet.

**Acceptance Criteria:**
- Faqet kryesore punojnë në 375px, 768px dhe desktop.

**Labels:** ui/ux  
**Priority:** Medium  
**Effort:** 3  
**Type:** Feature  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 3

## Issue #23: Implemento favorites

**Description:** Klienti ruan prona të preferuara.

**Acceptance Criteria:**
- Add/remove favorite punon; lista e favorites shfaqet në profil.

**Labels:** feature  
**Priority:** Medium  
**Effort:** 3  
**Type:** Feature  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 4

## Issue #24: Implemento reviews bazë

**Description:** Klienti lë vlerësim për agjentin.

**Acceptance Criteria:**
- Rating 1-5; koment opsional; review shfaqet në profil.

**Labels:** feature  
**Priority:** Medium  
**Effort:** 5  
**Type:** Feature  
**Assignee:** Eriseld Memia  
**Sprint:** Sprint 4

## Issue #25: Implemento admin panel

**Description:** Admin menaxhon përdoruesit dhe pronat.

**Acceptance Criteria:**
- Admin sheh users; ndryshon status; shikon prona; sheh statistika.

**Labels:** feature  
**Priority:** High  
**Effort:** 8  
**Type:** Feature  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 4

## Issue #26: Përgatit deployment guide

**Description:** Shkruaj udhëzuesin e instalimit/deployment.

**Acceptance Criteria:**
- deployment.md ka kërkesa, hapa instalimi, DB setup dhe probleme të zakonshme.

**Labels:** docs, devops  
**Priority:** High  
**Effort:** 3  
**Type:** Docs  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 4

## Issue #27: Përgatit user manual

**Description:** Shkruaj manualin e përdoruesit.

**Acceptance Criteria:**
- user-manual.md shpjegon regjistrim, login, shtim prone, kërkim, rezervim dhe admin panel.

**Labels:** docs  
**Priority:** High  
**Effort:** 3  
**Type:** Docs  
**Assignee:** Harilla Bica  
**Sprint:** Sprint 4

## Issue #28: Final polish dhe bug fixes

**Description:** Rregullo gabimet finale para prezantimit.

**Acceptance Criteria:**
- Nuk ka error kryesor; UI është konsistent; demo funksionon.

**Labels:** bug, ui/ux  
**Priority:** High  
**Effort:** 5  
**Type:** Bug  
**Assignee:** Eglis Haderaj  
**Sprint:** Sprint 4
