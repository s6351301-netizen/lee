CREATE DATABASE IF NOT EXISTS lee_clan_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lee_clan_demo;
DROP TABLE IF EXISTS LEE_logs, LEE_contacts, LEE_culture, LEE_assets, LEE_manuals, LEE_genealogy, LEE_events, LEE_news, LEE_members;
CREATE TABLE LEE_members (member_id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80),email VARCHAR(160) UNIQUE,password_hash VARCHAR(255),role ENUM('admin','editor','member') DEFAULT 'member',area VARCHAR(80),join_date DATE,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_news (news_id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NULL,title VARCHAR(160),summary VARCHAR(255),content TEXT,status ENUM('draft','published') DEFAULT 'draft',created_at DATETIME,updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_events (event_id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NULL,title VARCHAR(160),description TEXT,photo_url VARCHAR(500),video_url VARCHAR(500),event_date DATE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_genealogy (ancestor_id INT AUTO_INCREMENT PRIMARY KEY,parent_id INT NULL,ancestor_name VARCHAR(80),generation INT,branch_name VARCHAR(80),biography TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_manuals (manual_id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NULL,manual_year YEAR,title VARCHAR(160),file_url VARCHAR(500),upload_date DATE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_assets (asset_id INT AUTO_INCREMENT PRIMARY KEY,asset_name VARCHAR(120),asset_type ENUM('image','video','pdf','link','location'),location VARCHAR(180),asset_value VARCHAR(500),updated_at DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_culture (culture_id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(160),story TEXT,category VARCHAR(80),created_at DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_contacts (contact_id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80),email VARCHAR(160),message TEXT,submitted_at DATETIME,is_read TINYINT(1) DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE LEE_logs (log_id INT AUTO_INCREMENT PRIMARY KEY,member_id INT NULL,action VARCHAR(180),logged_at DATETIME) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO LEE_members (name,email,password_hash,role,area,join_date) VALUES
('李氏管理員','admin@example.org','$2y$10$demo','admin','高雄田寮','2024-01-01'),
('李氏編輯','editor@example.org','$2y$10$demo','editor','臺中大甲','2024-01-02'),
('李氏會員一','m1@example.org','$2y$10$demo','member','外水尾','2024-01-03'),
('李氏會員二','m2@example.org','$2y$10$demo','member','大糠榔','2024-01-04'),
('李氏會員三','m3@example.org','$2y$10$demo','member','嘉義朴子','2024-01-05'),
('李氏會員四','m4@example.org','$2y$10$demo','member','臺南','2024-01-06'),
('李氏會員五','m5@example.org','$2y$10$demo','member','屏東','2024-01-07'),
('李氏會員六','m6@example.org','$2y$10$demo','member','海外','2024-01-08');
INSERT INTO LEE_news (member_id,title,summary,content,status,created_at,updated_at) VALUES
(1,'李武略資料整理','祖先李武略，號殖春，本名李良經。','內容','published',NOW(),NOW()),(1,'田寮五房','高雄田寮李姓族譜線索。','內容','published',NOW(),NOW()),(1,'祭祀公業','祭祀公業李武略資料。','內容','published',NOW(),NOW()),(1,'宗祠大埕','宗祠大埕活動紀錄。','內容','published',NOW(),NOW()),(1,'族譜查詢','族譜電子化查詢。','內容','draft',NOW(),NULL),(1,'文獻典藏','古文書與來源。','內容','published',NOW(),NOW()),(1,'務農開荒','家族務農開荒故事。','內容','published',NOW(),NOW()),(1,'後台完成','PHP MySQL 後台。','內容','draft',NOW(),NULL);
INSERT INTO LEE_events (member_id,title,description,photo_url,video_url,event_date) VALUES (1,'年度大會','照片集',NULL,NULL,'2026-01-01'),(1,'祭祖典禮','影片',NULL,NULL,'2026-02-01'),(1,'文化交流','活動紀錄',NULL,NULL,'2026-03-01'),(1,'族譜整理','查詢校對',NULL,NULL,'2026-04-01'),(1,'田寮訪查','五房資料',NULL,NULL,'2026-05-01'),(1,'宗祠整理','公業資料',NULL,NULL,'2026-06-01'),(1,'開荒故事','務農訪談',NULL,NULL,'2026-07-01');
INSERT INTO LEE_genealogy (parent_id,ancestor_name,generation,branch_name,biography) VALUES (NULL,'李仲秋',1,'崑山派祖','資料'),(1,'李帝盛',2,'臺灣始遷祖','資料'),(2,'李武略',3,'田寮開基祖','號殖春，本名李良經'),(3,'長房',4,'五房','資料'),(3,'二房',4,'五房','資料'),(3,'三房',4,'五房','資料'),(3,'四房',4,'五房','資料'),(3,'五房',4,'五房','資料');
INSERT INTO LEE_manuals (member_id,manual_year,title,file_url,upload_date) VALUES (1,2022,'李武略派下手冊','uploads/a.pdf','2022-01-01'),(1,2023,'年會手冊','uploads/b.pdf','2023-01-01'),(1,2024,'會議紀錄','uploads/c.pdf','2024-01-01'),(1,2025,'重要決議','uploads/d.pdf','2025-01-01'),(1,2026,'族譜報告','uploads/e.pdf','2026-01-01');
INSERT INTO LEE_assets (asset_name,asset_type,location,asset_value,updated_at) VALUES ('祭祀公業李武略','location','高雄田寮','統一編號76645678',NOW()),('祖塔與祠堂','location','宗祠','介紹',NOW()),('祭祀流程','link','祭祀','流程',NOW()),('公業資產','link','公業','管理資訊',NOW()),('首頁圖','image','home','url',NOW());
INSERT INTO LEE_culture (title,story,category,created_at) VALUES ('家族故事','務農開荒','故事',NOW()),('傳統習俗','祭祖','習俗',NOW()),('文獻典藏','族譜','文獻',NOW()),('田寮五房','族譜資料','族譜',NOW()),('李良經','人物資料','祖先',NOW());
INSERT INTO LEE_contacts (name,email,message,submitted_at,is_read) VALUES ('李氏訪客','g1@example.org','留言一',NOW(),0),('李氏訪客二','g2@example.org','留言二',NOW(),1);
INSERT INTO LEE_logs (member_id,action,logged_at) VALUES (1,'建立資料',NOW()),(1,'登入後台',NOW());
