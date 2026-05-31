-- =============================================================================
-- ProEstate — Skema e Plotë e Bazës së të Dhënave
-- MySQL 8.0+ | utf8mb4 | 3NF (Forma e Tretë Normale)
-- Importo: mysql -u root -p < proesta.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `proesta`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `proesta`;

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- TABELAT
-- =============================================================================

-- Perdoruesit e sistemit
CREATE TABLE IF NOT EXISTS `users` (
  `id`                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `email`                VARCHAR(255)     NOT NULL,
  `password`             VARCHAR(255)     NOT NULL COMMENT 'bcrypt hash',
  `first_name`           VARCHAR(100)     NOT NULL,
  `last_name`            VARCHAR(100)     NOT NULL,
  `phone`                VARCHAR(25)      DEFAULT NULL,
  `gender`               ENUM('female','male','other','unspecified') NOT NULL DEFAULT 'unspecified',
  `role`                 ENUM('admin','agent','owner','client') NOT NULL DEFAULT 'client',
  `avatar`               VARCHAR(255)     DEFAULT NULL,
  `bio`                  TEXT             DEFAULT NULL,
  `agency_name`          VARCHAR(200)     DEFAULT NULL,
  `license_number`       VARCHAR(100)     DEFAULT NULL,
  `city`                 VARCHAR(100)     DEFAULT NULL,
  `is_active`            TINYINT(1)       NOT NULL DEFAULT 1,
  `email_verified`       TINYINT(1)       NOT NULL DEFAULT 0,
  `verification_token`   VARCHAR(64)      DEFAULT NULL,
  `reset_token`          VARCHAR(64)      DEFAULT NULL,
  `reset_token_expires`  DATETIME         DEFAULT NULL,
  `last_login`           DATETIME         DEFAULT NULL,
  `created_at`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pronat
CREATE TABLE IF NOT EXISTS `properties` (
  `id`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `title`        VARCHAR(300)     NOT NULL,
  `description`  TEXT             NOT NULL,
  `type`         ENUM('apartment','house','villa','commercial','office','land','garage') NOT NULL,
  `status`       ENUM('for_sale','for_rent','sold','rented') NOT NULL DEFAULT 'for_sale',
  `price`        DECIMAL(12,2)    NOT NULL,
  `price_period` ENUM('total','monthly','yearly') NOT NULL DEFAULT 'total',
  `area`         DECIMAL(10,2)    DEFAULT NULL COMMENT 'm2',
  `rooms`        TINYINT UNSIGNED DEFAULT 0,
  `bathrooms`    TINYINT UNSIGNED DEFAULT 0,
  `floor`        SMALLINT         DEFAULT NULL,
  `total_floors` SMALLINT         DEFAULT NULL,
  `year_built`   SMALLINT         DEFAULT NULL,
  `address`      VARCHAR(500)     NOT NULL,
  `city`         VARCHAR(100)     NOT NULL,
  `neighborhood` VARCHAR(200)     DEFAULT NULL,
  `latitude`     DECIMAL(10,8)    DEFAULT NULL,
  `longitude`    DECIMAL(11,8)    DEFAULT NULL,
  `owner_id`     INT UNSIGNED     NOT NULL,
  `agent_id`     INT UNSIGNED     DEFAULT NULL,
  `is_featured`  TINYINT(1)       NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)       NOT NULL DEFAULT 1,
  `approval_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `is_verified`  TINYINT(1)       NOT NULL DEFAULT 1,
  `approved_at`  DATETIME         DEFAULT NULL,
  `approved_by`  INT UNSIGNED     DEFAULT NULL,
  `views`        INT UNSIGNED     NOT NULL DEFAULT 0,
  `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_city` (`city`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_price` (`price`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_active` (`is_active`),
  KEY `idx_approval` (`approval_status`),
  KEY `idx_verified` (`is_verified`),
  KEY `idx_owner` (`owner_id`),
  KEY `idx_agent` (`agent_id`),
  FULLTEXT KEY `ft_search` (`title`, `description`, `address`, `city`, `neighborhood`),
  CONSTRAINT `fk_prop_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prop_agent` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prop_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Imazhet e pronave
CREATE TABLE IF NOT EXISTS `property_images` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id`   INT UNSIGNED NOT NULL,
  `filename`      VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `is_primary`    TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`    SMALLINT     NOT NULL DEFAULT 0,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prop` (`property_id`),
  CONSTRAINT `fk_img_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dokumentet e pronave
CREATE TABLE IF NOT EXISTS `property_documents` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `property_id`   INT UNSIGNED  NOT NULL,
  `filename`      VARCHAR(255)  NOT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `file_type`     VARCHAR(100)  DEFAULT NULL,
  `file_size`     INT UNSIGNED  DEFAULT NULL,
  `uploaded_by`   INT UNSIGNED  NOT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_doc_prop` (`property_id`),
  CONSTRAINT `fk_doc_prop`   FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploaded_by`)  REFERENCES `users`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Karakteristikat e pronave
CREATE TABLE IF NOT EXISTS `property_features` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `feature`     VARCHAR(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_feat_prop` (`property_id`),
  CONSTRAINT `fk_feat_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Takimet
CREATE TABLE IF NOT EXISTS `appointments` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id`    INT UNSIGNED NOT NULL,
  `client_id`      INT UNSIGNED NOT NULL,
  `agent_id`       INT UNSIGNED DEFAULT NULL,
  `scheduled_date` DATE         NOT NULL,
  `scheduled_time` TIME         NOT NULL,
  `status`         ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `notes`          TEXT         DEFAULT NULL COMMENT 'Shënime të agjentit',
  `client_notes`   TEXT         DEFAULT NULL COMMENT 'Shënime të klientit',
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appt_prop`   (`property_id`),
  KEY `idx_appt_client` (`client_id`),
  KEY `idx_appt_agent`  (`agent_id`),
  KEY `idx_appt_date`   (`scheduled_date`),
  CONSTRAINT `fk_appt_prop`   FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_client` FOREIGN KEY (`client_id`)   REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appt_agent`  FOREIGN KEY (`agent_id`)    REFERENCES `users`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesazhet ndërmjet perdoruesve
CREATE TABLE IF NOT EXISTS `messages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id`   INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `property_id` INT UNSIGNED DEFAULT NULL,
  `subject`     VARCHAR(300) DEFAULT NULL,
  `content`     TEXT         NOT NULL,
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_receiver` (`receiver_id`),
  KEY `idx_msg_sender`   (`sender_id`),
  CONSTRAINT `fk_msg_sender`   FOREIGN KEY (`sender_id`)   REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_prop`     FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prona të ruajtura (Favorites)
CREATE TABLE IF NOT EXISTS `favorites` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `property_id` INT UNSIGNED NOT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav` (`user_id`, `property_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`)     REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_prop` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vlerësimet e agjentëve
CREATE TABLE IF NOT EXISTS `reviews` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reviewer_id` INT UNSIGNED NOT NULL,
  `agent_id`    INT UNSIGNED NOT NULL,
  `rating`      TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment`     TEXT         DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rev_agent`    (`agent_id`),
  KEY `idx_rev_reviewer` (`reviewer_id`),
  CONSTRAINT `fk_rev_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_agent`    FOREIGN KEY (`agent_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log aktivitetesh
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `user_agent`  VARCHAR(500) DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user`   (`user_id`),
  KEY `idx_log_action` (`action`),
  KEY `idx_log_date`   (`created_at`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Radhë e email-eve
-- Tentativat e login-it per bllokim sigurie
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`        VARCHAR(255) NOT NULL,
  `ip_address`   VARCHAR(45)  NOT NULL,
  `success`      TINYINT(1)   NOT NULL DEFAULT 0,
  `user_agent`   VARCHAR(255) DEFAULT NULL,
  `attempted_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_guard` (`email`, `ip_address`, `success`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `to_email`   VARCHAR(255) NOT NULL,
  `to_name`    VARCHAR(200) DEFAULT NULL,
  `subject`    VARCHAR(300) NOT NULL,
  `body`       TEXT         NOT NULL,
  `status`     ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at`    DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eq_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- TË DHËNA FILLESTARE (SEED DATA)
-- Hash bcrypt demonstrativ me cost=12 per llogarite seed
-- =============================================================================

INSERT INTO `users`
  (email, password, first_name, last_name, phone, gender, role, bio, agency_name, license_number, city, is_active, email_verified)
VALUES
('admin@proestate.al',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Admin','ProEstate','+355691000001','unspecified','admin',
 'Administratori i sistemit ProEstate.',NULL,NULL,'Tiranë',1,1),

('joey.koci@proestate.al',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Joey','Koçi','+355692000002','male','agent',
 'Agjent imobiliar me 8 vjet eksperiencë në tregun e Tiranës. I specializuar në prona banesore luksoze dhe komerciale. Kam mbyllur mbi 200 transaksione të suksesshme.',
 'ProEstate Realty','AGJ-2016-0042','Tiranë',1,1),

('arben.hoxha@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Arben','Hoxha','+355693000003','male','agent',
 'Ekspert i tregut imobiliar në Durrës dhe Shqipërinë bregdetare. 5 vjet eksperiencë, i specializuar në prona pushimi dhe investime.',
 'Elite Properties','AGJ-2019-0118','Durrës',1,1),

('marinela.cela@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Marinela','Çela','+355694000004','female','agent',
 'Agjente e fokusuar në prona luksoze dhe investime afatgjata. Vlerësoj transparencën dhe komunikimin e shpejtë me klientët.',
 'Luxury Homes Albania','AGJ-2018-0077','Tiranë',1,1),

('genti.muca@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Genti','Muca','+355695000005','male','owner',
 'Pronar i disa apartamenteve dhe lokaleve komerciale në Tiranë.',
 NULL,NULL,'Tiranë',1,1),

('blerina.gjoka@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Blerina','Gjoka','+355696000006','female','owner',
 'Pronare e vilës dhe trualleve në periferi të Tiranës.',
 NULL,NULL,'Tiranë',1,1),

('klajdi.prifti@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Klajdi','Prifti','+355697000007','male','client',
 NULL,NULL,NULL,'Tiranë',1,1),

('arta.shehu@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Arta','Shehu','+355698000008','female','client',
 NULL,NULL,NULL,'Tiranë',1,1),

('endri.veli@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Endri','Veli','+355699000009','male','client',
 NULL,NULL,NULL,'Durrës',1,1),

('ermira.basha@gmail.com',
 '$2y$12$WxTHvRYsczX9oD3J1uuJFOeCJMLPPMqEOKS0HFE9f3H4OsTjjDhKe',
 'Ermira','Basha','+355699100010','female','client',
 NULL,NULL,NULL,'Shkodër',1,1);

-- Vendos hash-in demonstrativ per llogarite demo.
UPDATE `users` SET `password` = '$2y$12$/x3XRIwyuT55IxoP0.UOaemif7ysKc9stAfmuFlGH2QVKUX6GOcUe';

INSERT INTO `properties`
  (title, description, type, status, price, price_period, area, rooms, bathrooms, floor, total_floors, year_built, address, city, neighborhood, owner_id, agent_id, is_featured, is_active)
VALUES
('Apartament Modern 2+1 në Bllok',
 'Apartament i ri dhe modern me 2 dhoma gjumi, sallon i madh me buze-dritare panoramike, kuzhinë e plotë me pajisje, banjë e re. Ndodhet në zemër të Bllokut, njëra nga zonat më prestigjioze të Tiranës. Ndërtesë e re 2021, parking nëntokësor i siguruar, ashensor, sistem alarmi 24h. Ballkon 12m² me pamje të qytetit. Materiale cilësie të lartë, dysheme parket, dritare PVC të dyfishtë.',
 'apartment','for_sale',185000.00,'total',95.00,3,1,5,8,2021,
 'Rruga Sami Frashëri, Nr.24','Tiranë','Blloku',5,2,1,1),

('Apartament 1+1 me Qira në Kombinat',
 'Apartament i mobiluar plotësisht, i përshtatshmë për çift ose student. Kuzhinë me pajisje (frigorifer, lavatriçe, mikrovalë). Sallon me TV dhe kolltuk, dhomë gjumi me shtrat dyfish. Afër transportit publik (autobuzit), shkollave dhe supermarketeve. Ndërtesë e mirëmbajtur, portieri aktiv.',
 'apartment','for_rent',350.00,'monthly',55.00,2,1,3,6,2015,
 'Rruga Myslym Shyri, Nr.12','Tiranë','Kombinat',5,2,0,1),

('Vilë Luksoze me Pishinë në Sauk',
 'Vilë e mrekullueshme me 4 dhoma gjumi, 4 banjo, dhomë pritjeje, dhomë ngrënieje, kuzhinë e madhe e hapur, depo dhe garazh i dyfishtë. Kopshti 800m² me pishinë private 8x4m, terrasa të mëdha, sistem ujitjeje automatik. Smart Home: ndriçimi, ngrohja dhe siguriija kontrollohen nga celular. Sistem alarmi + kamera 24h. Ideale për familje ose investim premium.',
 'villa','for_sale',850000.00,'total',420.00,5,4,0,3,2019,
 'Rruga Elbasanit, Sauk','Tiranë','Sauk',6,3,1,1),

('Apartament 3+1 i Ri në Don Bosko',
 'Apartament i sapo ri­ndërtuar tota­lisht, kuzhinë e re me paisje Bosch, banjo e re me ngrohtës uji, dysheme laminat e re, dritare të reja PVC. 3 dhoma gjumi, sallon 25m², ballkon 10m². Afër shkollës 9-vjeçare, farmacisë dhe qendrës tregtare Univers. Gjithçka e re, gati për banim të menjëhershëm.',
 'apartment','for_sale',120000.00,'total',110.00,4,2,2,7,2010,
 'Rruga Don Bosko, Nr.45','Tiranë','Don Bosko',5,2,0,1),

('Zyrë Moderne Open-Space 200m² në Qendër',
 'Hapësirë zyre premium në bulevardin kryesor të Tiranës. 200m² hapësirë e hapur (open-space), e ndarshme sipas nevojave. 2 salla konferencash (8 dhe 4 persona), 2 banjo, kuzhinë stafi. AC central VRF, internet fiber 1 Gbps, sistem telefonie IP. Parking 5 vendet për stafin. Adresë prestigjioze për biznesin tuaj.',
 'office','for_rent',2500.00,'monthly',200.00,0,2,3,10,2020,
 'Bulevardi Dëshmorët e Kombit, Nr.3','Tiranë','Qendër',6,3,1,1),

('Truall 500m² Ndërtimi në Vore',
 'Truall i rrafshët 500m² me dokumentacion të rregullt (AMTP), i regjistruar në Hipotekë. Leje ndërtimi e mundshme (zona e banimit individual, koeficient 0.6). Infrastruktura e lidhur: ujë, rrymë, kanalizim. Akses i lehtë nga autostrada Tiranë-Durrës.',
 'land','for_sale',75000.00,'total',500.00,0,0,0,0,NULL,
 'Autostrada Tiranë-Durrës, Km 20','Vorë',NULL,6,NULL,0,1),

('Apartament 2+1 Afër Detit, Durrës',
 'Apartament i bukur 100 metra nga bregu i detit. Pamje deti nga ballkoni i madh 15m². Mobiluar plotësisht me mobilje moderne. 2 dhoma gjumi, sallon me divanet, kuzhinë e hapur, banjo e re. Ideale si banesë verore ose investim me yield të lartë (qiradhënie turistike). Pishinë e përbashkët e ndërtesës.',
 'apartment','for_sale',95000.00,'total',80.00,3,1,4,6,2018,
 'Rruga Taulantia, Nr.8','Durrës','Plazh',5,3,1,1),

('Dyqan Komercial 80m² Rrugë Kryesore',
 'Dyqan komercial 80m² në rrugën me kalim më të lartë këmbësorësh në Tiranë. I përshtatshëm për çdo aktivitet tregtar: restorant, farmaci, dyqan ushqimor, kafe etj. Vitrinë e madhe 8 metra, tavan 3.5m, dritë direkte. Sistemim i brendshëm i mirë, sistemi i ujit dhe drymës funksionale.',
 'commercial','for_rent',1200.00,'monthly',80.00,0,1,0,4,2005,
 'Rruga e Kavajës, Nr.67','Tiranë','Kavaja Road',6,2,0,1),

('Studio e Re 45m² në Unazën e Re',
 'Studio moderne e re, ndërtesë 2022. 45m² e shfrytëzueshme me dizajn smart-space: krevat murphy i integruar, kuzhinë e hapur moderne, banjo me dush italiane. Ashensor, ngrohtje qendrore, sistem interphone. Parkingje të disponueshme me pagesë. Ideale për student, profesionist apo si investim.',
 'apartment','for_rent',280.00,'monthly',45.00,1,1,7,12,2022,
 'Unaza e Re, Nr.15','Tiranë','Unaza e Re',5,3,0,1),

('Apartament 4+1 Luksoz 180m² Myslym Shyri',
 'Apartament i jashtëzakonshëm luksoz në ndërtesën prestigjioze të vitit 2023. 180m² me 4 dhoma gjumi, 3 banjo (2 ensuite), sallon panoramik 45m², kuzhinë e hapur Snaidero, depo 12m². Tarracë private 30m² me pamje 360°. Smart Home Loxone, ngrohje dyshemeje, AC Mitsubishi VRF, dritare Schüco. Materiale të nivelit më të lartë: mermeri Calacatta, parket dubi 22cm.',
 'apartment','for_sale',350000.00,'total',180.00,5,3,8,10,2023,
 'Rruga Myslym Shyri, Nr.5','Tiranë','Blloku',6,3,1,1),

('Shtëpi 2-kateshe 150m² në Shkodër',
 'Shtëpi e bukur 2-kateshe në lagje të qetë dhe të sigurtë. Kati i parë: sallon, kuzhinë, banjo, depo. Kati i dytë: 4 dhoma gjumi, 2 banjo. Oborr 200m², parkingje 2 makina. Ripunime të reja brendshme 2020: pikturim, dysheme, banjo. Afër shkollës, xhamisë dhe tregut.',
 'house','for_sale',65000.00,'total',150.00,4,2,0,2,2008,
 'Rruga Gjuhadol, Nr.22','Shkodër','Qendër',5,NULL,0,1),

('Apartament 1+1 Afër Plazhit, Vlorë',
 'Apartament kompakt i mobiluar, 50m², 100 metra nga plazhi i Radhimës. Ballkon me pamje deti 8m². Ideale si rezidencë verore ose për qiradhënie sezonale me kthim të mirë. Dokumentacion i rregullt, AMTP. Ndërtesa ka gjenerator rezervë.',
 'apartment','for_sale',55000.00,'total',50.00,2,1,2,5,2016,
 'Rruga Flamurit, Nr.18','Vlorë','Plazh',6,NULL,0,1),

('Penthouse 3+1 Panoramik Tiranë',
 'Penthouse ekskluzip në katin e fundit të ndërtesës më të re në Tiranë (2024). 200m² sipërfaqe totale + tarracë rrethore 60m² me pamje 360° të gjithë qytetit. 3 dhoma gjumi, 3 banjo, sallon-kuzhinë e hapur 70m². Finime italiane Minotti, sistem domotiike KNX, ngrohje dyshemeje. Garazh dyfish i sigurt. Mundësi financimi.',
 'apartment','for_sale',520000.00,'total',200.00,4,3,12,12,2024,
 'Rruga Abdyl Frashëri, Nr.1','Tiranë','Blloku',6,3,1,1),

('Lokal Komercial 150m² në Tiranë të Re',
 'Lokal komercial i madh 150m² në katin e parë të ndërtesës rezidenciale. Tre fasada me vitrina të mëdha. Tavan 4m, sistemim i ri i brendshëm. I lidhur me parking të ndërtesës (10 vendet). I përshtatshëm për restorant, bankë, farmaci, klinikë. Qiradhënie afatgjatë me kontratë 3+2 vjet.',
 'commercial','for_rent',2000.00,'monthly',150.00,0,2,0,5,2022,
 'Rruga Dritan Hoxha, Nr.9','Tiranë','Tiranë e Re',5,2,0,1),

('Vilë 300m² me Kopsht, Farkë',
 'Vilë moderne 3-kateshe në komunën e Farkës, periferi elegante e Tiranës. 300m² + kopsht 1200m². Kati i parë: sallon i madh, kuzhinë profesionale, banjo dhe studio. Kati i dytë: 4 dhoma gjumi, 3 banjo. Kati i tretë: terrasa + studio private. Pishinë 10x5m, sistem uji automatik, park privat. Garazh 3 makina.',
 'villa','for_sale',650000.00,'total',300.00,5,4,0,3,2020,
 'Rruga Farkë-Laknas, Nr.3','Farkë',NULL,6,3,1,1);

-- Karakteristikat e pronave
INSERT INTO `property_features` (property_id, feature) VALUES
(1,'Parking nëntokësor'),(1,'Ashensor'),(1,'Sistem alarmi 24h'),(1,'Kondicioner'),(1,'Internet fiber'),(1,'Ballkon 12m²'),(1,'Dysheme parket'),(1,'Dritare PVC të dyfishtë'),
(2,'I mobiluar plotësisht'),(2,'Makinë larëse'),(2,'Internet'),(2,'Kondicioner'),(2,'Frigorifer'),
(3,'Pishinë private'),(3,'Kopsht 800m²'),(3,'Garazh i dyfishtë'),(3,'Smart Home'),(3,'Siguri 24h + kamera'),(3,'Terrasa të mëdha'),(3,'Pamje panoramike'),
(4,'Ri-ndërtuar tërësisht'),(4,'Dysheme laminat'),(4,'Dritare PVC'),(4,'Ballkon 10m²'),(4,'Kuzhinë me pajisje Bosch'),
(5,'Hapësirë open-space'),(5,'2 salla konferencash'),(5,'AC central VRF'),(5,'Internet fiber 1Gbps'),(5,'Parking 5 vende'),(5,'Telefoni IP'),
(7,'Pamje deti'),(7,'I mobiluar plotësisht'),(7,'Pishinë e përbashkët'),(7,'Ballkon 15m²'),
(10,'Tarracë private 30m²'),(10,'Smart Home Loxone'),(10,'Ngrohje dyshemeje'),(10,'AC VRF Mitsubishi'),(10,'Mermer Calacatta'),(10,'Garazh dyfish'),
(13,'Tarracë rrethore 60m²'),(13,'Pamje 360°'),(13,'Smart Home KNX'),(13,'Ngrohje dyshemeje'),(13,'Finime Minotti'),(13,'Garazh dyfish'),
(15,'Pishinë 10x5m'),(15,'Kopsht 1200m²'),(15,'Garazh 3 makina'),(15,'Studio private'),(15,'Sistem ujitjeje automatik');

-- Takimet
INSERT INTO `appointments` (property_id, client_id, agent_id, scheduled_date, scheduled_time, status, notes, client_notes) VALUES
(1, 7, 2, '2026-05-20', '10:00:00', 'confirmed', 'Klienti është shumë i interesuar. Ka buxhet. Vizitë e dytë.', 'Dua të shoh gjendjen e çatisë dhe bodrumit.'),
(3, 8, 3, '2026-05-22', '14:00:00', 'pending', 'Vizitë e parë. Klienti kërkon informacion financimi.', 'Kemi dy fëmijë, dua të shoh kopshtin dhe pishinën.'),
(7, 9, 3, '2026-05-23', '11:00:00', 'confirmed', 'Klienti interesohet si investim. Kërkon ndihmë me hipotekën.', NULL),
(10, 7, 3, '2026-05-25', '15:30:00', 'pending', NULL, 'Do doja të inspektoja sistemin e ngrohjes dhe dritaret.'),
(5, 8, 3, '2026-05-19', '09:00:00', 'completed', 'Vizitë e kryer. Klienti ka nevojë për kohë vendimi.', NULL),
(13, 10, 3,'2026-05-28', '16:00:00', 'pending', NULL, 'Interested in buying for investment. Budget up to 550k.'),
(1, 10, 2, '2026-05-15', '11:00:00', 'completed', 'Klient shumë i interesuar.', NULL);

-- Mesazhet
INSERT INTO `messages` (sender_id, receiver_id, property_id, subject, content) VALUES
(7, 2, 1, 'Pyetje rreth apartamentit në Bllok',
 'Përshëndetje, jam i interesuar për apartamentin 2+1 në Bllok. A është ende në dispozicion? Doja të dija nëse mund të negociojmë çmimin dhe kur mund ta vizitojmë.'),
(2, 7, 1, 'Re: Pyetje rreth apartamentit në Bllok',
 'Përshëndetje Klajdi! Po, apartamenti është ende në dispozicion. Çmimi është pak negociabil. Mund të takohemi të mërkurën në mesditë apo të enjten pasdite për vizitë. Na tregoni kur jeni i lirë.'),
(8, 3, 3, 'Interes për vilën në Sauk',
 'Mirëdita, vilen e keni shumë të bukur. Jemi familje me 2 fëmijë dhe jemi shumë të interesuar. Doja të dija nëse ka mundësi pagese me kredi hipotekore dhe nëse çmimi është fundor.'),
(3, 8, 3, 'Re: Interes për vilën në Sauk',
 'Mirëdita Arta! Shumë gëzim që jeni të interesuar. Po, prona pranon financim hipotekor. Mund të organizojmë vizitë sa herë të dëshironi. Çmimi ka pak hapësirë negocimi për blerje të shpejtë.'),
(9, 3, 7, 'Apartamenti në Durrës — pyetje investimi',
 'Pershendetje Arben, jam i interesuar në apartamentin e Durrësit si investim afatgjatë. Sa është yield-i mesatar i qirasë verore në atë zonë?'),
(7, 4, 10, 'Apartament 4+1 — vizitë',
 'Mirëdita znj. Çela! Jam shumë i interesuar për penthousin. A mund të organizojmë vizitë javën tjetër?');

-- Prona të preferuara
INSERT INTO `favorites` (user_id, property_id) VALUES
(7,1),(7,3),(7,10),(7,13),
(8,3),(8,5),(8,7),(8,15),
(9,1),(9,4),(9,7),
(10,13),(10,15);

-- Vlerësimet
INSERT INTO `reviews` (reviewer_id, agent_id, rating, comment) VALUES
(7, 2, 5, 'Joey është agjent jashtëzakonisht profesional. Na ndihmoi të gjenim shtëpinë e ëndrrave në kohë rekord. Komunikim i shkëlqyer, i sinqertë dhe gjithmonë i disponueshëm.'),
(8, 3, 4, 'Arben ka njohuri shumë të mira për tregun. Komunikim i shpejtë dhe i qartë. Rekomandoj pa hezitim.'),
(9, 3, 5, 'Shërbim i shkëlqyer, i përditësuar me çmimet e tregut. Na rrëzgoi shumë mirë mundësitë e investimit. Plotësisht profesional!'),
(7, 3, 4, 'Profesional dhe i sinqertë. Na tregoi disa prona të mira dhe nuk na imponoi asgjë. Kënaqësi pune.'),
(10, 4, 5, 'Marinela është shumë e kujdesshme dhe dinjitoze. E di çfarë duan klientët. E rekomandoj për prona luksoze.');

-- =============================================================================
-- TABELA PAYMENTS (PayPal)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `appointment_id`    INT UNSIGNED  DEFAULT NULL,
  `user_id`           INT UNSIGNED  NOT NULL,
  `property_id`       INT UNSIGNED  NOT NULL,
  `paypal_order_id`   VARCHAR(100)  NOT NULL,
  `paypal_capture_id` VARCHAR(100)  NOT NULL,
  `payer_email`       VARCHAR(255)  DEFAULT NULL,
  `payer_name`        VARCHAR(200)  DEFAULT NULL,
  `amount`            DECIMAL(10,2) NOT NULL,
  `currency`          VARCHAR(10)   NOT NULL DEFAULT 'EUR',
  `status`            ENUM('pending','completed','refunded','failed') NOT NULL DEFAULT 'pending',
  `paid_at`           DATETIME      DEFAULT NULL,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_capture` (`paypal_capture_id`),
  KEY `idx_pay_user`     (`user_id`),
  KEY `idx_pay_prop`     (`property_id`),
  KEY `idx_pay_status`   (`status`),
  CONSTRAINT `fk_pay_user`  FOREIGN KEY (`user_id`)        REFERENCES `users`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_prop`  FOREIGN KEY (`property_id`)    REFERENCES `properties`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_appt`  FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
