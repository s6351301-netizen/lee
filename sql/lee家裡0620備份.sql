-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-06-20 23:38:18
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `lee`
--

DELIMITER $$
--
-- 程序
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sync_account_members` (IN `p_id` INT, IN `p_name` VARCHAR(50), IN `p_new_member` VARCHAR(50), IN `p_status` VARCHAR(20))   BEGIN
    -- 安全機制：發生任何 SQL 錯誤就整筆取消（Rollback），保證資料不會改一半
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    -- 開始事務
    START TRANSACTION;

    -- 更新 account 表 (status 欄位是 varchar)
    UPDATE account
    SET name = p_name,
        new_member = p_new_member,
        status = p_status,
        updated_at = NOW()
    WHERE id = p_id;

    -- 更新 members 表 (status 欄位是 tinyint，MySQL 會自動轉換)
    UPDATE members
    SET name = p_name,
        new_member = p_new_member,
        status = CAST(p_status AS SIGNED),
        update_time = NOW()
    WHERE id = p_id;

    -- 確認無誤，提交存檔
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- 資料表結構 `account`
--

CREATE TABLE `account` (
  `id` int(11) NOT NULL COMMENT '流水號',
  `new_member` varchar(50) DEFAULT NULL COMMENT '現在會員號',
  `old_member` varchar(50) NOT NULL COMMENT '前會員號',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `gender` varchar(10) NOT NULL COMMENT '性別',
  `email` varchar(255) NOT NULL COMMENT '電子信箱',
  `password` varchar(255) NOT NULL COMMENT '密碼',
  `role` enum('admin','user','clan') DEFAULT 'user' COMMENT '權限(管理者/派下員/宗親)',
  `join_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '註冊日期',
  `status` varchar(20) DEFAULT '1' COMMENT '帳號停用或使用中(1:使用中, 0:停用)',
  `discontinued_date` date DEFAULT NULL COMMENT '停用日期',
  `remarks` varchar(255) DEFAULT NULL COMMENT '備註',
  `menu_id` varchar(10) NOT NULL COMMENT '選單項目 ID：對應網頁中 clanMenuData 陣列的 id（例如：3 代表 1-1、7.1 代表 2-3 json 選單）。',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT '權限建立時間',
  `updated_at` datetime DEFAULT NULL COMMENT '權限更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帳號資料表';

--
-- 傾印資料表的資料 `account`
--

INSERT INTO `account` (`id`, `new_member`, `old_member`, `name`, `gender`, `email`, `password`, `role`, `join_date`, `status`, `discontinued_date`, `remarks`, `menu_id`, `created_at`, `updated_at`) VALUES
(1, '1', 'OLD001', '李明輝', '男', 'user1@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-04-12 02:23:14', '1', NULL, '常參與春季祭祖', '', '2026-06-08 18:28:50', '2026-06-14 10:55:36'),
(2, '2', 'OLD002', '李美麗', '女', 'may@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-11-05 06:15:22', '1', NULL, '民國112年除籍，家屬已辦理過戶', '', '2026-06-08 18:28:50', '2026-06-17 20:02:27'),
(3, '3', 'OLD003', '李大山', '男', 'user3@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2027-02-18 01:45:11', '1', NULL, '目前失聯中，書面信件遭退件', '', '2026-06-08 18:28:50', NULL),
(4, '4', 'OLD004', '李志強', '男', 'user4@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-08-22 08:30:00', '1', NULL, '現任地方宗親會理事', '', '2026-06-08 18:28:50', NULL),
(5, '5', 'OLD005', '李秋香', '女', 'user5@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-05-19 03:12:45', '1', NULL, '聯絡電話有更新', '', '2026-06-08 18:28:50', NULL),
(6, '6', 'OLD006', '李文雄', '男', 'user6@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2027-06-01 00:20:33', '0', NULL, '派下權已由長子繼承', '', '2026-06-08 18:28:50', '2026-06-17 20:26:17'),
(7, '7', 'OLD007', '李美玲', '女', 'user7@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-03-14 07:25:19', '0', '2026-10-22', '出境國外未歸，狀態待確認', '', '2026-06-08 18:28:50', '2026-06-17 20:29:46'),
(8, '8', 'OLD008', '李建國', '男', 'user8@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-07-07 04:00:54', '1', NULL, '每年固定出席大會', '', '2026-06-08 18:28:50', NULL),
(9, '9', 'OLD009', '李淑珍', '女', 'user9@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2027-01-30 09:41:02', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(10, '10', 'OLD010', '李志明', '男', 'user10@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-09-11 05:14:15', '1', NULL, '除籍資料已備查', '', '2026-06-08 18:28:50', NULL),
(11, '11', 'OLD011', '李秀英', '女', 'user11@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-02-28 02:05:33', '1', '2027-01-15', '需補件親屬關係證明', '', '2026-06-08 18:28:50', NULL),
(12, '12', 'OLD012', '李建華', '男', 'user12@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-12-25 06:55:00', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(13, '13', 'OLD013', '李家豪', '男', 'user13@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-03-15 03:24:41', '1', NULL, '戶籍有變動，尚在查證中', '', '2026-06-08 18:28:50', NULL),
(14, '14', 'OLD014', '李美麗', '女', 'user14@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-06-18 08:18:18', '1', '2026-11-30', '主要通訊聯絡人', '', '2026-06-08 18:28:50', NULL),
(15, '15', 'OLD015', '李文傑', '男', 'user15@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-10-10 01:09:09', '0', NULL, '生前已聲明拋棄衍生權利', '', '2026-06-08 18:28:50', NULL),
(16, '16', 'OLD016', '李佩芬', '女', 'user16@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-05-04 07:40:22', '0', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(17, '17', 'OLD017', '李建智', '男', 'user17@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-01-15 05:50:55', '1', '2026-08-14', '無', '', '2026-06-08 18:28:50', NULL),
(18, '18', 'OLD018', '李淑惠', '女', 'user18@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-07-29 09:33:12', '0', NULL, '民國114年歿', '', '2026-06-08 18:28:50', NULL),
(19, '19', 'OLD019', '李志遠', '男', 'user19@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-08-12 03:11:11', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(20, '20', 'OLD020', '李家銘', '男', 'user20@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-11-11 02:22:44', '1', NULL, '青年代表成員', '', '2026-06-08 18:28:50', NULL),
(21, '21', 'OLD021', '李麗君', '女', 'user21@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-04-05 06:50:23', '1', '2027-03-09', '目前地址查無此人', '', '2026-06-08 18:28:50', NULL),
(22, '22', 'OLD022', '李志誠', '男', 'user22@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-02-22 08:06:07', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(23, '23', 'OLD023', '李秀蘭', '女', 'user23@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-09-09 00:45:30', '1', NULL, '已故資深會員', '', '2026-06-08 18:28:50', NULL),
(24, '24', 'OLD024', '李建勳', '男', 'user24@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-12-14 04:12:12', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(25, '25', 'OLD025', '李美華', '女', 'user25@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-07-07 07:15:15', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(26, '26', 'OLD026', '李志明', '男', 'user26@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-05-25 02:33:21', '0', NULL, '後代已移居海外未辦繼承', '', '2026-06-08 18:28:50', NULL),
(27, '27', 'OLD027', '李英傑', '男', 'user27@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-02-10 06:14:14', '1', '2026-12-01', '無', '', '2026-06-08 18:28:50', NULL),
(28, '28', 'OLD028', '李淑芬', '女', 'user28@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-04-19 03:40:50', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(29, '29', 'OLD029', '李大同', '男', 'user29@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-10-31 08:55:03', '1', NULL, '失蹤多年，待法院宣告', '', '2026-06-08 18:28:50', NULL),
(30, '30', 'OLD030', '李麗婷', '女', 'user30@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-08-08 01:22:11', '1', NULL, '青年代表成員', '', '2026-06-08 18:28:50', NULL),
(31, '31', 'OLD031', '李志明', '男', 'user31@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-01-01 05:00:00', '1', NULL, '近期更換手機號碼', '', '2026-06-08 18:28:50', NULL),
(32, '32', 'OLD032', '李阿鑾', '女', 'user32@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-03-23 07:15:42', '1', NULL, '派下權由其次子辦理繼承中', '', '2026-06-08 18:28:50', NULL),
(33, '33', 'OLD033', '李建宏', '男', 'user33@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-07-14 03:10:09', '1', '2027-05-20', '大會重要幹部', '', '2026-06-08 18:28:50', NULL),
(34, '34', 'OLD034', '李美玲', '女', 'user34@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-11-20 09:45:31', '1', NULL, '通知信件查無此人', '', '2026-06-08 18:28:50', NULL),
(35, '35', 'OLD035', '李國華', '男', 'user35@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-09-05 06:20:18', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(36, '36', 'OLD036', '李金木', '男', 'user36@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-06-30 02:05:59', '0', NULL, '民國113年歿，無直系血親繼承', '', '2026-06-08 18:28:50', NULL),
(37, '37', 'OLD037', '李淑芬', '女', 'user37@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-02-15 00:30:22', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(38, '李志豪', 'OLD038', '38', '男', 'user38@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-05-01 08:40:00', '1', '2026-11-15', '定期繳納宗親會費', '', '2026-06-08 18:28:50', '2026-06-14 08:34:17'),
(39, '李寶珠', 'OLD039', '39', '女', 'user39@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-03-28 04:15:37', '1', NULL, '已除籍，證明文件備查', '', '2026-06-08 18:28:50', '2026-06-14 08:39:18'),
(40, '40', 'OLD040', '李水源', '男', 'user40@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-12-12 05:14:15', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(41, '41', 'OLD041', '李家豪', '男', 'user41@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-09-18 07:22:41', '1', NULL, '出國工作，家屬代為聯絡', '', '2026-06-08 18:28:50', NULL),
(42, '42', 'OLD042', '李佩君', '女', 'user42@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-06-15 03:11:50', '1', NULL, '青年委員會代表', '', '2026-06-08 18:28:50', NULL),
(43, '43', 'OLD043', '李俊傑', '男', 'user43@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-04-20 02:02:03', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(44, '44', 'OLD044', '李火旺', '男', 'user44@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-01-08 06:35:12', '0', NULL, '民國111年壽終正寢', '', '2026-06-08 18:28:50', NULL),
(45, '45', 'OLD045', '李雅婷', '女', 'user45@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-02-14 08:50:22', '1', NULL, '通訊地址有變更', '', '2026-06-08 18:28:50', NULL),
(46, '46', 'OLD046', '李文德', '男', 'user46@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-08-30 01:18:27', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(47, '47', 'OLD047', '李美華', '女', 'user47@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-10-05 05:40:55', '1', NULL, '失聯會員，正由鄰里長協助尋找', '', '2026-06-08 18:28:50', NULL),
(48, '48', 'OLD048', '李志強', '男', 'user48@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2027-05-20 09:12:04', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(49, '49', 'OLD049', '李春枝', '女', 'user49@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-03-03 03:25:36', '1', NULL, '已故資深會員', '', '2026-06-08 18:28:50', NULL),
(50, '50', 'OLD050', '李家明', '男', 'user50@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-07-22 07:55:40', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(51, '51', 'OLD051', '李雅如', '女', 'user51@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2027-01-19 06:14:12', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(52, '52', 'OLD052', '李天送', '男', 'user52@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-11-30 02:20:30', '0', NULL, '已由代書辦理繼承登錄', '', '2026-06-08 18:28:50', NULL),
(53, '53', 'OLD053', '李建宏', '男', 'user53@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-05-14 00:44:19', '1', NULL, '戶籍地查無此人', '', '2026-06-08 18:28:50', NULL),
(54, '54', 'OLD054', '李秀英', '女', 'user54@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2027-04-01 08:33:05', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(55, '55', 'OLD055', '李志遠', '男', 'user55@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-02-27 04:12:58', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(56, '56', 'OLD056', '李阿土', '男', 'user56@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-09-25 07:00:44', '1', NULL, '民國110年過世', '', '2026-06-08 18:28:50', NULL),
(57, '57', 'OLD057', '李美玲', '女', 'user57@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2027-08-08 03:22:33', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(58, '58', 'OLD058', '李冠宇', '男', 'user58@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-06-11 05:50:21', '1', NULL, '無', '', '2026-06-08 18:28:50', NULL),
(59, '59', 'OLD059', '李淑惠', '女', 'user59@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2026-12-05 02:05:14', '1', NULL, '通訊處更動未申報', '', '2026-06-08 18:28:50', NULL),
(60, '60', 'OLD060', '李大同', '男', 'user60@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'clan', '2027-03-03 08:41:00', '1', NULL, '每年固定委託代表出席', '', '2026-06-08 18:28:50', NULL),
(61, NULL, '', '李', '', 'may2@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'user', '2026-06-02 14:19:40', '1', NULL, NULL, '', '2026-06-08 18:28:50', NULL),
(62, '62', '', '李梅蘭', '女', 's63@gmail.com', '$2y$10$j6dxP3mDJVDEQ13vXKRXn.32M.1kUTgaTqnM3HwgJhAaOStt0rDkS', 'admin', '2026-06-02 08:40:52', '1', '2026-06-02', '驗證碼5866', '', '2026-06-08 18:28:50', NULL),
(63, '63', '', '李以柔', '女', 'angel@gmail.com', '$2y$10$swpEzPKprZwJEMfYyTN0Yu0L/ZqgXmkxOiDHuUas8lMF1MxgMOwua', 'admin', '2026-06-02 13:40:45', '1', '0000-00-00', '管理者', '', '2026-06-08 18:28:50', NULL),
(64, '64', '', '李雪梅', '女', 'angel2@gmail.com', '$2y$10$Tc67v8AWFqFR2.KWlusINeKs.9soNVEi7EVus1V6euN0bt5HaDs66', 'user', '2026-06-02 13:47:21', '1', '0000-00-00', '', '', '2026-06-08 18:28:50', NULL);

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `account_members_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `account_members_view` (
`account_id` int(11)
,`account_new_member` varchar(50)
,`account_old_member` varchar(50)
,`account_name` varchar(50)
,`account_gender` varchar(10)
,`account_email` varchar(255)
,`password` varchar(255)
,`role` enum('admin','user','clan')
,`join_date` timestamp
,`account_status` varchar(20)
,`discontinued_date` date
,`account_remarks` varchar(255)
,`menu_id` varchar(10)
,`created_at` datetime
,`updated_at` datetime
,`member_id` int(11)
,`receive_date` date
,`member_old_member` varchar(50)
,`member_new_member` varchar(50)
,`generation` int(11)
,`emperor_shizu` int(11)
,`number_of_houses` int(11)
,`member_name` varchar(50)
,`member_gender` varchar(10)
,`id_card_num` varchar(20)
,`birthday` date
,`placeOfBirth` varchar(100)
,`education` varchar(100)
,`experience` text
,`address` varchar(255)
,`zip_code` text
,`mobile_phone` varchar(20)
,`home_phone` varchar(20)
,`company_phone` varchar(20)
,`member_email` varchar(100)
,`introducer` varchar(50)
,`SendSubordinates` text
,`living_status` enum('存','亡','未知')
,`member_status` tinyint(1)
,`member_remarks` text
,`update_time` datetime
,`last_updater` varchar(50)
);

-- --------------------------------------------------------

--
-- 資料表結構 `bike_logs`
--

CREATE TABLE `bike_logs` (
  `id` bigint(20) NOT NULL COMMENT '流水號(主鍵)',
  `station_id` varchar(20) NOT NULL COMMENT '對應 youbike 的站點編號',
  `available_bikes` smallint(6) NOT NULL COMMENT '可借車輛數(sbi)',
  `empty_spaces` smallint(6) NOT NULL COMMENT '可停空位數(bemp)',
  `is_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '服務狀態(1:正常, 0:暫停)',
  `official_update_time` datetime NOT NULL COMMENT '官方最後更新時間(mday)',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '系統執行抓取的時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='YouBike即時車位動態紀錄表';

-- --------------------------------------------------------

--
-- 資料表結構 `dev_tracking`
--

CREATE TABLE `dev_tracking` (
  `id` int(11) NOT NULL,
  `creator_member_id` varchar(50) DEFAULT NULL COMMENT '登入會員編號',
  `project_name_zh` text DEFAULT NULL COMMENT '名稱(中)',
  `project_name_en` text DEFAULT NULL COMMENT '檔名(中/英)',
  `status` enum('待辦事項','進行中','測試中','已完成') DEFAULT '進行中' COMMENT '開發狀態',
  `dev_start_at` datetime DEFAULT NULL COMMENT '開發-起',
  `dev_end_at` datetime DEFAULT NULL COMMENT '開發-迄',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `course_name_zh` text DEFAULT NULL COMMENT '課程名稱',
  `teacher_name` text DEFAULT NULL COMMENT '指導老師',
  `skill_category` text DEFAULT NULL COMMENT '技能領域',
  `technology_name` text DEFAULT NULL COMMENT '技術名稱',
  `skill_practiced` text DEFAULT NULL COMMENT '練習技能點',
  `ai_url` text DEFAULT NULL COMMENT 'AI開發網址',
  `references` text DEFAULT NULL COMMENT '參考文獻',
  `dev_note` text DEFAULT NULL COMMENT '開發筆記',
  `dev_note1` text DEFAULT NULL COMMENT '開發筆記一',
  `dev_note2` text DEFAULT NULL COMMENT '開發筆記二',
  `dev_note3` text DEFAULT NULL COMMENT '開發筆記三',
  `dev_note4` text DEFAULT NULL COMMENT '開發筆記四',
  `dev_note5` text DEFAULT NULL COMMENT '開發筆記五',
  `dev_note6` text DEFAULT NULL COMMENT '開發筆記六',
  `dev_note7` text DEFAULT NULL COMMENT '開發筆記七',
  `dev_note8` text DEFAULT NULL COMMENT '開發筆記八',
  `dev_note9` text DEFAULT NULL COMMENT '開發筆記九',
  `dev_note10` text DEFAULT NULL COMMENT '開發筆記十'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `dev_tracking`
--

INSERT INTO `dev_tracking` (`id`, `creator_member_id`, `project_name_zh`, `project_name_en`, `status`, `dev_start_at`, `dev_end_at`, `created_at`, `updated_at`, `course_name_zh`, `teacher_name`, `skill_category`, `technology_name`, `skill_practiced`, `ai_url`, `references`, `dev_note`, `dev_note1`, `dev_note2`, `dev_note3`, `dev_note4`, `dev_note5`, `dev_note6`, `dev_note7`, `dev_note8`, `dev_note9`, `dev_note10`) VALUES
(3, '2', '開發作品的開發進度追蹤', 'test_view.php', '進行中', '2026-06-18 14:51:00', '2026-06-18 08:56:00', '2026-06-18 06:56:26', '2026-06-18 06:56:26', '前端', '鄭光凱_前端', 'HTML_CSS_JQ_JS_Bootstrap_AJAX', 'AJAX', '紀錄開發歷程重點0618老師教PHP與AJAX去撈資料', '0617-0618：寫PHP開發工期與進度表含資料庫設計\r\nhttps://gemini.google.com/app/c9473057ad83b50b?hl=zh-TW', '暫無', '暫無', '', '', '', '', '', '', '', '', '', ''),
(4, '2', '開發作品的開發進度追蹤２', 'http://localhost/lee/backend/dev_tracking.php', '進行中', '2026-06-18 15:24:00', '2026-06-22 18:29:00', NULL, NULL, '後端', '劉勤永_後端PHP', 'php_ajax_js', 'AJAX2', '0618前端老師將後端老師的php語法重新做簡易教學2-2', 'http://localhost/lee/backend/dev_tracking.php\r\n祭祀公業網站資料庫功能探討2\r\nhttps://gemini.google.com/app/11f543c6a3969bb4?hl=zh-TW\r\n大甲\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '無...教學2', '吳...教學\r\nhttps://www.youtube.com/embed/fS-3o4Tz5cI', '大甲1-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲2-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲3-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲4-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲5-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲6-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲7-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲8-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲9-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54', '大甲10-2\r\nhttps://www.youtube.com/watch?v=cJ7-F58Cp54'),
(7, '2', '', '', '待辦事項', '2026-06-19 08:23:00', '0000-00-00 00:00:00', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');

-- --------------------------------------------------------

--
-- 資料表結構 `family`
--

CREATE TABLE `family` (
  `id` int(11) NOT NULL COMMENT '流水號',
  `new_member` varchar(50) DEFAULT NULL COMMENT '現在會員號',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `father` varchar(50) DEFAULT NULL COMMENT '父',
  `mother` varchar(50) DEFAULT NULL COMMENT '母',
  `adoptiveFather` varchar(50) DEFAULT NULL COMMENT '養父',
  `fosterMother` varchar(50) DEFAULT NULL COMMENT '養母',
  `spouse` varchar(50) DEFAULT NULL COMMENT '配偶',
  `brothers` text DEFAULT NULL COMMENT '手足-兄/弟',
  `sisters` text DEFAULT NULL COMMENT '手足-姊/妹',
  `FamilySituation` text DEFAULT NULL COMMENT '家庭情況說明',
  `son1` text DEFAULT NULL COMMENT '長子(複數/長文字)',
  `son2` text DEFAULT NULL COMMENT '次子(複數/長文字)',
  `son3` text DEFAULT NULL COMMENT '三子(複數/長文字)',
  `son4` text DEFAULT NULL COMMENT '四子(複數/長文字)',
  `son5` text DEFAULT NULL COMMENT '五子(複數/長文字)',
  `son6` text DEFAULT NULL COMMENT '六子(複數/長文字)',
  `son7` text DEFAULT NULL COMMENT '七子(複數/長文字)',
  `son8` text DEFAULT NULL COMMENT '八子(複數/長文字)',
  `son9` text DEFAULT NULL COMMENT '九子(複數/長文字)',
  `daughter1` text DEFAULT NULL COMMENT '長女(複數/長文字)',
  `daughter2` text DEFAULT NULL COMMENT '次女(複數/長文字)',
  `daughter3` text DEFAULT NULL COMMENT '三女(複數/長文字)',
  `daughter4` text DEFAULT NULL COMMENT '四女(複數/長文字)',
  `daughter5` text DEFAULT NULL COMMENT '五女(複數/長文字)',
  `daughter6` text DEFAULT NULL COMMENT '六女(複數/長文字)',
  `daughter7` text DEFAULT NULL COMMENT '七女(複數/長文字)',
  `daughter8` text DEFAULT NULL COMMENT '八女(複數/長文字)',
  `daughter9` text DEFAULT NULL COMMENT '九女(複數/長文字)',
  `remarks` text DEFAULT NULL COMMENT '備註'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `family`
--

INSERT INTO `family` (`id`, `new_member`, `name`, `gender`, `father`, `mother`, `adoptiveFather`, `fosterMother`, `spouse`, `brothers`, `sisters`, `FamilySituation`, `son1`, `son2`, `son3`, `son4`, `son5`, `son6`, `son7`, `son8`, `son9`, `daughter1`, `daughter2`, `daughter3`, `daughter4`, `daughter5`, `daughter6`, `daughter7`, `daughter8`, `daughter9`, `remarks`) VALUES
(1, '1', '李明輝', '男', '李大同', '陳小華', NULL, NULL, '王美玲', '李明強', '李秀英', '育有一子一女', '李小明', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '李美美', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '常參與春季祭祖'),
(2, '2', '李美麗', '女', '李美麗', '林春枝', NULL, NULL, '陳建宏', NULL, '張美華', '小家庭結構', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '陳小妞', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '電話有更新'),
(3, '3', '李大山', '男', '李大山', '謝秋月', '劉大木', '曾小草', NULL, '王二山', NULL, '收養關係，與父母同住', '王小山', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '因收養加入'),
(4, '4', '李志強', '男', '李志強', '林淑芬', NULL, NULL, '黃淑惠', '陳志明', '陳麗娟', '家庭和樂', '陳冠宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '陳雅婷', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(5, '5', '李秋香', '女', '李秋香', '張金鳳', NULL, NULL, '蔡明哲', NULL, '林秋月', '育有一子', '蔡小寶', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '資深會員'),
(6, '6', '李文雄', '男', '李文雄', '吳美珠', NULL, NULL, NULL, '黃文德', '黃文娟', '單身', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '已遷居國外'),
(7, '7', '李美玲', '女', '李美玲', '曾麗華', NULL, NULL, '張志明', '郭建宏', NULL, '育有一女', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '張小珍', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(8, '8', '李建國', '男', '李建國', '洪阿嬌', NULL, NULL, '邱秀琴', NULL, '曾美惠', '育有一子一女', '曾大宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '曾小萱', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '代表出席者'),
(9, '9', '李淑珍', '女', '李淑珍', '林秀英', NULL, NULL, '許信宏', '賴建良', '賴淑美', '育有二子', '許大寶', '許小寶', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(10, '10', '李志明', '男', '李志明', '徐月娥', NULL, NULL, '連美雪', '蔡志清', '蔡佳玲', '三代同堂，四子二女', '蔡家豪', '蔡冠廷', '蔡冠志', '蔡冠傑', NULL, NULL, NULL, NULL, NULL, '蔡佩珊', '蔡佩君', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '核心派下員'),
(11, '11', '李秀英', '女', '李秀英', '陳秋霞', NULL, NULL, NULL, '葉秀雄', NULL, '單親家庭', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '林美美', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '需補件證明'),
(12, '12', '李建華', '男', '李建華', '王滿', NULL, NULL, '謝麗華', '許建銘', '許秀琴', '育有一子二女', '許博宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '許宇婷', '許宇萱', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(13, '13', '李家豪', '男', '李家豪', '林寶珠', NULL, NULL, NULL, '蘇家慶', '蘇美蓮', '未婚', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(14, '14', '李美麗', '女', '李美麗', '郭秀美', NULL, NULL, '曾文欽', NULL, '莊美玲', '家庭穩定', '曾小明', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '曾小美', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '聯絡人'),
(15, '15', '李文傑', '男', '李文傑', '謝玉蘭', NULL, NULL, '李美子', '何文欽', '何淑惠', '育有八子一女', '何大明', '何二男', '何三男', '何四男', '何五男', '何六男', '何七男', '何八男', NULL, '何女兒', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '已聲明放棄'),
(16, '16', '李佩芬', '女', '李佩芬', '吳麗華', NULL, NULL, '洪建忠', '謝世傑', '謝佩玲', '育有一女', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '洪小芬', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(17, '17', '李建智', '男', '李建智', '陳滿妹', NULL, NULL, '劉淑貞', '彭建強', NULL, '育有一子一女', '彭冠傑', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '彭宇婷', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(18, '18', '李淑惠', '女', '李淑惠', '張阿妹', NULL, NULL, '吳明達', '呂建忠', '呂淑玲', '家庭結構健全', '吳冠宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '吳佩蓉', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(19, '19', '李志遠', '男', '李志遠', '林玉春', NULL, NULL, '邱美慧', NULL, '宋志玲', '育有一女', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '宋小美', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(20, '20', '李家銘', '男', '李家銘', '劉美玲', NULL, NULL, NULL, '張家豪', '張家妤', '未婚與父母同住', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '青年代表'),
(21, '21', '李麗君', '女', '李麗君', '黃秀珍', NULL, NULL, NULL, '盧建名', '盧麗華', '單親家庭', '陳大寶', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '地址待確認'),
(22, '22', '李志誠', '男', '李志誠', '何金英', NULL, NULL, '蕭淑美', '戴志強', '戴秀美', '育有一子一女', '戴冠宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '戴雅如', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(23, '23', '李秀蘭', '女', '李秀蘭', '林阿桃', NULL, NULL, '施明輝', NULL, '汪秀美', '子女皆已成年', '施家豪', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '施佩君', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(24, '24', '李建勳', '男', '李建勳', '陳玉葉', NULL, NULL, '詹美珠', '江建德', '江麗君', '育有一子', '江小宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(25, '25', '李美華', '女', '李美華', '吳春花', NULL, NULL, NULL, NULL, '薛美玲', '未婚', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(26, '26', '李志明', '男', '李志明', '林秀鑾', NULL, NULL, '白麗雪', '余志強', '余志玲', '舉家外遷', '余大山', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '余小莉', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(27, '27', '李英傑', '男', '李英傑', '王麗華', NULL, NULL, '張美娟', '馬英才', NULL, '育有一女', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '馬小婷', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(28, '28', '李淑芬', '女', '李淑芬', '許阿秀', NULL, NULL, '曾建華', '白志明', '白淑美', '小家庭結構', '曾宇軒', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '曾宇婷', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(29, '29', '李大同', '男', '李大同', '林月里', NULL, NULL, '陳秀琴', '方大安', '方美玉', '三代同堂', '方冠中', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '方信雅', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(30, '30', '李麗婷', '女', '李麗婷', '郭美滿', '陳大山', '林阿蓮', NULL, '鐘志豪', '鐘麗娟', '養子女關係', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '養子女繼承相關'),
(31, '31', '李志明', '男', '李志明', '陳淑娟', NULL, NULL, '林佳慧', '林志強', '林小妹', '育有三子一女', '林大維', '林二維', '林三維', NULL, NULL, NULL, NULL, NULL, NULL, '林淑婷', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '近期更換手機號碼'),
(32, '32', '李阿鑾', '女', '李阿鑾', '陳林阿綢', NULL, NULL, NULL, NULL, '陳美秀', '派下權辦理中', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '派下權由其次子辦理繼承中'),
(33, '33', '李建宏', '男', '李建宏', '吳美珠', NULL, NULL, '黃麗秋', '黃建勳', '黃文娟', '大會重要幹部', '黃長男', '黃次男', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '大會重要幹部'),
(34, '34', '李美玲', '女', '李美玲', '吳佩芬', NULL, NULL, NULL, NULL, '吳雅婷', '通知信件遭退', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '通知信件查無此人'),
(35, '35', '李國華', '男', '李國華', '洪阿嬌', NULL, NULL, '邱秀琴', '曾建良', '曾美惠', '典型家庭', '曾大宇', '曾二宇', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(36, '36', '李金木', '男', '李金木', '林玉春', NULL, NULL, NULL, NULL, '朱志玲', '無直系血親', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '民國113年歿，無直系血親繼承'),
(37, '37', '李淑芬', '女', '李淑芬', '謝玉蘭', NULL, NULL, NULL, NULL, '劉淑惠', '一般結構', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(38, '38', '李志豪', '男', '李志豪', '林阿梅', NULL, NULL, '謝淑芬', '鄭志明', '鄭麗娟', '定期繳納會費', '鄭長男', '鄭次男', '鄭三男', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '定期繳納宗親會費'),
(39, '39', '李寶珠', '女', '李寶珠', '林寶珠', NULL, NULL, NULL, NULL, '楊美蓮', '已除籍', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '已除籍，證明文件備查'),
(40, '40', '李水源', '男', '李水源', '吳麗華', NULL, NULL, NULL, '謝世傑', '謝佩玲', '無特殊備註', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(41, '41', '李家豪', '男', '李家豪', '曾麗華', NULL, NULL, NULL, '郭建宏', '郭美玲', '出國工作', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '出國工作，家屬代為聯絡'),
(42, '42', '李佩君', '女', '李佩君', '王滿', NULL, NULL, NULL, '徐建銘', '徐秀琴', '青年代表', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '徐一女', '徐二女', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '青年委員會代表'),
(43, '43', '李俊傑', '男', '李俊傑', '林月里', NULL, NULL, NULL, '周大安', '周美玉', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(44, '44', '李火旺', '男', '李火旺', '林阿蓮', NULL, NULL, NULL, NULL, NULL, '壽終正寢', '高長男', '高次男', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '民國111年壽終正寢'),
(45, '45', '李雅婷', '女', '李雅婷', '徐月娥', NULL, NULL, NULL, '蔡志清', '蔡佳玲', '地址變更', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '通訊地址有變更'),
(46, '46', '李文德', '男', '李文德', '謝玉蘭', NULL, NULL, NULL, '梁文欽', '梁淑惠', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(47, '47', '李美華', '女', '李美華', '林寶珠', NULL, NULL, NULL, '蘇家慶', '蘇美蓮', '失聯會員', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '失聯會員，正由鄰里長協助尋找'),
(48, '48', '李志強', '男', '李志強', '洪阿嬌', NULL, NULL, NULL, '丁建良', '丁淑珍', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(49, '49', '李春枝', '女', '李春枝', '郭秀美', NULL, NULL, NULL, NULL, '沈美玲', '資深會員', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '已故資深會員'),
(50, '50', '李家明', '男', '李家明', '王滿', NULL, NULL, NULL, '魏建銘', '魏秀琴', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(51, '51', '李雅如', '女', '李雅如', '陳玉葉', NULL, NULL, NULL, '江建德', '江麗君', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(52, '52', '李天送', '男', '李天送', '陳滿妹', NULL, NULL, NULL, '潘建強', NULL, '代書辦理中', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '已由代書辦理繼承登錄'),
(53, '53', '李建宏', '男', '李建宏', '蕭洪阿嬌', NULL, NULL, NULL, '蕭建良', '蕭美惠', '查無此人', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '戶籍地查無此人'),
(54, '54', '李秀英', '女', '李秀英', '田曾金蓮', NULL, NULL, NULL, NULL, '田麗華', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(55, '55', '李志遠', '男', '李志遠', '趙郭阿妹', NULL, NULL, NULL, '趙志明', '趙淑玲', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(56, '56', '李阿土', '男', '李阿土', '金林秀鑾', NULL, NULL, NULL, '金志強', '金志玲', '已過世', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '民國110年過世'),
(57, '57', '李美玲', '女', '李美玲', '彭王麗華', NULL, NULL, NULL, '彭英才', NULL, '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(58, '58', '李冠宇', '男', '李冠宇', '黃淑芬', NULL, NULL, NULL, '石志強', '石麗娟', '無', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '無'),
(59, '59', '李淑惠', '女', '李淑惠', '林秀鑾', NULL, NULL, NULL, NULL, '姚志玲', '更動未報', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '通訊處更動未申報'),
(60, '60', '李大同', '男', '李大同', '林月里', NULL, NULL, NULL, '連大安', '連美玉', '委託出席', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '每年固定委託代表出席');

-- --------------------------------------------------------

--
-- 資料表結構 `files`
--

CREATE TABLE `files` (
  `file_id` int(11) NOT NULL COMMENT '檔案編號',
  `file_name` varchar(255) NOT NULL COMMENT '檔案名稱',
  `file_path` varchar(1000) NOT NULL COMMENT '檔案路徑',
  `file_url` text DEFAULT NULL COMMENT '檔案網址(下載或存取的URL)',
  `file_type` varchar(100) DEFAULT NULL COMMENT '檔案類型 (image/png, pdf)',
  `file_size` bigint(20) DEFAULT NULL COMMENT '檔案大小 (bytes)',
  `upload_date` datetime DEFAULT current_timestamp() COMMENT '上傳日期',
  `uploaded_id` varchar(100) DEFAULT NULL COMMENT '上傳者ID',
  `uploaded_name` text DEFAULT NULL COMMENT '上傳者姓名',
  `description` text DEFAULT NULL COMMENT '檔案描述',
  `status` enum('active','deleted','archived') DEFAULT 'active' COMMENT '狀態 (是否啟用、刪除、封存)',
  `reference_id` int(11) DEFAULT NULL COMMENT '參考編號 (關聯到其他資料表，如文章ID)',
  `version` int(11) DEFAULT 1 COMMENT '版本 (檔案版本控制)',
  `public` tinyint(1) DEFAULT 0 COMMENT '是否公開 (0=私有, 1=公開)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='檔案上傳紀錄表';

--
-- 傾印資料表的資料 `files`
--

INSERT INTO `files` (`file_id`, `file_name`, `file_path`, `file_url`, `file_type`, `file_size`, `upload_date`, `uploaded_id`, `uploaded_name`, `description`, `status`, `reference_id`, `version`, `public`) VALUES
(1, '螢幕擷取畫面 2026-05-06 184319.png', 'D:/web02/icon/20260606_062124_6a23a044a4376.png', '/icon/20260606_062124_6a23a044a4376.png', 'image/png', 631411, '2026-06-06 12:21:24', '李志遠', '李志遠', NULL, 'active', 158, 1, 0),
(2, '螢幕擷取畫面 2026-04-01 161838.png', 'D:/web02/icon/20260606_063345_6a23a32977fdd.png', '/icon/20260606_063345_6a23a32977fdd.png', 'image/png', 107801, '2026-06-06 12:33:45', '李佩芬', '李佩芬', NULL, 'active', 159, 1, 0),
(3, '螢幕擷取畫面 2026-03-31 194509.png', 'D:/web02/icon/20260606_072039_6a23ae279f4aa.png', '/icon/20260606_072039_6a23ae279f4aa.png', 'image/png', 78431, '2026-06-06 13:20:39', '26', '李志明', NULL, 'active', 160, 1, 0),
(4, '螢幕擷取畫面 2026-04-05 083229.png', 'D:/web02/icon/20260606_073129_6a23b0b18bd11.png', '/icon/20260606_073129_6a23b0b18bd11.png', 'image/png', 378015, '2026-06-06 13:31:29', '36', '李金木', NULL, 'active', 161, 1, 0),
(5, '划手機必背PHP使用的SQL通用與法.pdf', 'D:/web02/icon/20260606_075439_6a23b61f511b3.pdf', '/icon/20260606_075439_6a23b61f511b3.pdf', 'application/pdf', 65302, '2026-06-06 13:54:39', '9', '李淑珍', NULL, 'active', 162, 1, 0),
(6, 'members (3).sql', 'D:/web02/icon/20260606_075439_6a23b61f5185a.sql', '/icon/20260606_075439_6a23b61f5185a.sql', 'application/octet-stream', 11770, '2026-06-06 13:54:39', '9', '李淑珍', NULL, 'active', 162, 1, 0),
(7, 'lee (1).sql', 'D:/web02/icon/20260606_075439_6a23b61f520c6.sql', '/icon/20260606_075439_6a23b61f520c6.sql', 'application/octet-stream', 113408, '2026-06-06 13:54:39', '9', '李淑珍', NULL, 'active', 162, 1, 0),
(8, 'login (1).php', 'D:/web02/icon/20260606_075439_6a23b61f52970.php', '/icon/20260606_075439_6a23b61f52970.php', 'application/octet-stream', 3821, '2026-06-06 13:54:39', '9', '李淑珍', NULL, 'active', 162, 1, 0),
(9, 'logo.png', 'D:/web02/icon/20260606_075511_6a23b63f17f62.png', '/icon/20260606_075511_6a23b63f17f62.png', 'image/png', 21089, '2026-06-06 13:55:11', '9', '李淑珍', NULL, 'active', 162, 1, 0),
(16, '111訴835附表.pdf', 'D:/web02/icon/20260606_095704_6a23d2d0edea0_111訴835附表.pdf', '/icon/20260606_095704_6a23d2d0edea0_111訴835附表.pdf', 'application/pdf', 117645, '2026-06-06 15:57:04', NULL, NULL, NULL, 'active', 0, 1, 0),
(18, '7-11百元[博弘雲端]1113 AI SecureNext 資安無界.png', 'D:/web02/icon/20260606_100919_6a23d5afa8603_7-11百元[博弘雲端]1113 AI SecureNext 資安無界.png', '/icon/20260606_100919_6a23d5afa8603_7-11百元[博弘雲端]1113 AI SecureNext 資安無界.png', 'image/png', 321102, '2026-06-06 16:09:19', NULL, NULL, NULL, 'active', 0, 1, 0),
(23, '家族祈願圖片.png', 'D:/web02/icon/20260606_120318_6a23f066d482a_家族祈願圖片.png', '/icon/20260606_120318_6a23f066d482a_家族祈願圖片.png', 'image/png', 1568417, '2026-06-06 18:03:18', NULL, NULL, NULL, 'active', 0, 1, 0),
(24, '家族祈願圖片.png', 'D:/web02/icon/20260606_121653_6a23f3951a6ee_家族祈願圖片.png', '/icon/20260606_121653_6a23f3951a6ee_家族祈願圖片.png', 'image/png', 1568417, '2026-06-06 18:16:53', NULL, NULL, NULL, 'active', 0, 1, 0),
(25, '家族祈願圖片.png', 'D:/web02/icon/20260606_121738_6a23f3c271392_家族祈願圖片.png', '/icon/20260606_121738_6a23f3c271392_家族祈願圖片.png', 'image/png', 1568417, '2026-06-06 18:17:38', NULL, NULL, NULL, 'active', 0, 1, 0),
(26, '划手機必背PHP使用的SQL通用與法.pdf', 'D:/web02/icon/20260606_121807_6a23f3dfc970c_划手機必背PHP使用的SQL通用與法.pdf', '/icon/20260606_121807_6a23f3dfc970c_划手機必背PHP使用的SQL通用與法.pdf', 'application/pdf', 65302, '2026-06-06 18:18:07', NULL, NULL, NULL, 'active', 0, 1, 0),
(27, '家裡127_0_0_1 (1)完整0604備份.sql', 'D:/web02/icon/20260606_121807_6a23f3dfc9eca_家裡127_0_0_1 (1)完整0604備份.sql', '/icon/20260606_121807_6a23f3dfc9eca_家裡127_0_0_1 (1)完整0604備份.sql', 'application/octet-stream', 444057, '2026-06-06 18:18:07', NULL, NULL, NULL, 'active', 0, 1, 0),
(28, '篩選結果 (1).xlsx', 'D:/web02/icon/20260606_121807_6a23f3dfca531_篩選結果 (1).xlsx', '/icon/20260606_121807_6a23f3dfca531_篩選結果 (1).xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 263483, '2026-06-06 18:18:07', NULL, NULL, NULL, 'active', 0, 1, 0),
(29, '115年學員訓練材料表(全端網頁開發與AI應用班)(職前班).pdf', 'D:/web02/icon/20260606_121848_6a23f408a75ce_115年學員訓練材料表(全端網頁開發與AI應用班)(職前班).pdf', '/icon/20260606_121848_6a23f408a75ce_115年學員訓練材料表(全端網頁開發與AI應用班)(職前班).pdf', 'application/pdf', 92901, '2026-06-06 18:18:48', NULL, NULL, NULL, 'active', 0, 1, 0),
(30, '1150505祭祀公業法人臺中市張文通公派下全員系統表-公告版_家族9成女性子孫都拋棄.pdf', 'D:/web02/icon/20260606_121848_6a23f408a7ca6_1150505祭祀公業法人臺中市張文通公派下全員系統表-公告版_家族9成女性子孫都拋棄.pdf', '/icon/20260606_121848_6a23f408a7ca6_1150505祭祀公業法人臺中市張文通公派下全員系統表-公告版_家族9成女性子孫都拋棄.pdf', 'application/pdf', 465665, '2026-06-06 18:18:48', NULL, NULL, NULL, 'active', 0, 1, 0),
(31, '臺中市 115年03月單一年齡人口數_20260404090742.png', 'D:/web02/icon/20260606_121937_6a23f439395a8_臺中市 115年03月單一年齡人口數_20260404090742.png', '/icon/20260606_121937_6a23f439395a8_臺中市 115年03月單一年齡人口數_20260404090742.png', 'image/png', 135409, '2026-06-06 18:19:37', NULL, NULL, NULL, 'active', 0, 1, 0),
(36, '臺中市 115年03月單一年齡人口數_20260404090742.png', 'D:/web02/icon/20260606_124847_6a23fb0f1e77e_臺中市 115年03月單一年齡人口數_20260404090742.png', '/icon/20260606_124847_6a23fb0f1e77e_臺中市 115年03月單一年齡人口數_20260404090742.png', 'image/png', 135409, '2026-06-06 18:48:47', NULL, NULL, NULL, 'active', 0, 1, 0),
(41, '臺中市 115年03月單一年齡人口數_20260404090742.png', 'D:/web02/icon/20260606_125046_6a23fb8619a27_臺中市 115年03月單一年齡人口數_20260404090742.png', '/icon/20260606_125046_6a23fb8619a27_臺中市 115年03月單一年齡人口數_20260404090742.png', 'image/png', 135409, '2026-06-06 18:50:46', NULL, NULL, NULL, 'active', 0, 1, 0),
(47, '螢幕擷取畫面 2026-05-18 084123.png', 'D:/web02/icon/20260606_130139_6a23fe13d4b13_螢幕擷取畫面 2026-05-18 084123.png', '/icon/20260606_130139_6a23fe13d4b13_螢幕擷取畫面 2026-05-18 084123.png', 'image/png', 6047, '2026-06-06 19:01:39', NULL, NULL, NULL, 'active', 0, 1, 0),
(51, '臺中市 115年03月單一年齡人口數_20260404090742.png', 'D:/web02/icon/20260606_130153_6a23fe21304d6_臺中市 115年03月單一年齡人口數_20260404090742.png', '/icon/20260606_130153_6a23fe21304d6_臺中市 115年03月單一年齡人口數_20260404090742.png', 'image/png', 135409, '2026-06-06 19:01:53', NULL, NULL, NULL, 'active', 0, 1, 0),
(52, '螢幕擷取畫面 2026-06-02 215544.png', 'D:/web02/icon/螢幕擷取畫面 2026-06-02 215544.png', '/icon/螢幕擷取畫面 2026-06-02 215544.png', 'image/png', 2847, '2026-06-06 19:13:04', NULL, NULL, NULL, 'active', 0, 1, 0),
(53, '螢幕擷取畫面 2026-06-02 212521.png', 'D:/web02/icon/螢幕擷取畫面 2026-06-02 212521.png', '/icon/螢幕擷取畫面 2026-06-02 212521.png', 'image/png', 8319, '2026-06-06 19:13:13', NULL, NULL, NULL, 'active', 0, 1, 0),
(54, '螢幕擷取畫面 2026-06-02 211334.png', 'D:/web02/icon/20260606_215229_6a247a7db5605_螢幕擷取畫面 2026-06-02 211334.png', '/icon/20260606_215229_6a247a7db5605_螢幕擷取畫面 2026-06-02 211334.png', 'image/png', 22739, '2026-06-07 03:52:29', NULL, NULL, NULL, 'active', 0, 1, 0),
(55, '115年學員訓練材料表(全端網頁開發與AI應用班)(職前班).pdf', 'D:/web02/icon/20260606_215252_6a247a9402e6b_115年學員訓練材料表(全端網頁開發與AI應用班)(職前班).pdf', '/icon/20260606_215252_6a247a9402e6b_115年學員訓練材料表(全端網頁開發與AI應用班)(職前班).pdf', 'application/pdf', 92901, '2026-06-07 03:52:52', '25', '李美華', NULL, 'active', 163, 1, 0),
(56, '1150505祭祀公業法人臺中市張文通公派下全員系統表-公告版_家族9成女性子孫都拋棄.pdf', 'D:/web02/icon/20260606_215252_6a247a9403c00_1150505祭祀公業法人臺中市張文通公派下全員系統表-公告版_家族9成女性子孫都拋棄.pdf', '/icon/20260606_215252_6a247a9403c00_1150505祭祀公業法人臺中市張文通公派下全員系統表-公告版_家族9成女性子孫都拋棄.pdf', 'application/pdf', 465665, '2026-06-07 03:52:52', '25', '李美華', NULL, 'active', 163, 1, 0),
(57, '義和里定位.xlsx', 'D:/web02/icon/20260606_215252_6a247a94041d4_義和里定位.xlsx', '/icon/20260606_215252_6a247a94041d4_義和里定位.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 67252155, '2026-06-07 03:52:52', '25', '李美華', NULL, 'active', 163, 1, 0),
(58, '臺中市 115年03月單一年齡人口數_20260404090742.png', 'D:/web02/icon/20260606_215252_6a247a940478f_臺中市 115年03月單一年齡人口數_20260404090742.png', '/icon/20260606_215252_6a247a940478f_臺中市 115年03月單一年齡人口數_20260404090742.png', 'image/png', 135409, '2026-06-07 03:52:52', NULL, NULL, NULL, 'active', 0, 1, 0),
(59, '家族祈願圖片.png', 'D:/web02/icon/20260606_220157_6a247cb5ef515_家族祈願圖片.png', '/icon/20260606_220157_6a247cb5ef515_家族祈願圖片.png', 'image/png', 1568417, '2026-06-07 04:01:57', NULL, NULL, NULL, 'active', 0, 1, 0),
(60, '龍蝦1.png', 'D:/web02/icon/20260607_145607_6a256a6706cb5_龍蝦1.png', '/icon/20260607_145607_6a256a6706cb5_龍蝦1.png', 'image/png', 694056, '2026-06-07 20:56:07', '5', '李秋香', NULL, 'active', 166, 1, 0),
(61, '家族祈願圖片.png', 'D:/web02/icon/20260607_145708_6a256aa44fbb4_家族祈願圖片.png', '/icon/20260607_145708_6a256aa44fbb4_家族祈願圖片.png', 'image/png', 1568417, '2026-06-07 20:57:08', '57', '李美玲', NULL, 'active', 167, 1, 0),
(62, '家族祈願圖片.jpg', 'D:/web02/icon/20260607_145708_6a256aa450328_家族祈願圖片.jpg', '/icon/20260607_145708_6a256aa450328_家族祈願圖片.jpg', 'image/jpeg', 2300104, '2026-06-07 20:57:08', '57', '李美玲', NULL, 'active', 167, 1, 0),
(63, '划手機必背PHP使用的SQL通用與法.pdf', 'D:/web02/icon/20260607_145708_6a256aa450a12_划手機必背PHP使用的SQL通用與法.pdf', '/icon/20260607_145708_6a256aa450a12_划手機必背PHP使用的SQL通用與法.pdf', 'application/pdf', 65302, '2026-06-07 20:57:08', '57', '李美玲', NULL, 'active', 167, 1, 0),
(64, '家裡127_0_0_1 (1)完整0604備份.sql', 'D:/web02/icon/20260607_145708_6a256aa451040_家裡127_0_0_1 (1)完整0604備份.sql', '/icon/20260607_145708_6a256aa451040_家裡127_0_0_1 (1)完整0604備份.sql', 'application/octet-stream', 444057, '2026-06-07 20:57:08', NULL, NULL, NULL, 'active', 0, 1, 0),
(65, 'logo-轉檔前格式-為-png.png', 'D:/web02/icon/20260607_145708_6a256aa451884_logo-轉檔前格式-為-png.png', '/icon/20260607_145708_6a256aa451884_logo-轉檔前格式-為-png.png', 'image/png', 29281, '2026-06-07 20:57:08', '57', '李美玲', NULL, 'active', 167, 1, 0),
(66, '李氏宗親會網站_系統架構書.txt', 'D:/web02/icon/20260607_145708_6a256aa452101_李氏宗親會網站_系統架構書.txt', '/icon/20260607_145708_6a256aa452101_李氏宗親會網站_系統架構書.txt', 'text/plain', 60519, '2026-06-07 20:57:08', '57', '李美玲', NULL, 'active', 167, 1, 0),
(67, '家族祈願圖片.jpg', 'D:/web02/icon/20260608_025210_6a26123a8394b_家族祈願圖片.jpg', '/icon/20260608_025210_6a26123a8394b_家族祈願圖片.jpg', 'image/jpeg', 2300104, '2026-06-08 08:52:10', '15', '李文傑', NULL, 'active', 166, 1, 0),
(68, 'members (3).sql', 'D:/web02/icon/20260608_025210_6a26123a83f99_members (3).sql', '/icon/20260608_025210_6a26123a83f99_members (3).sql', 'application/octet-stream', 11770, '2026-06-08 08:52:10', NULL, NULL, NULL, 'active', 0, 1, 0),
(69, 'Dajia29geli.html', 'D:/web02/icon/20260608_025245_6a26125de166d_Dajia29geli.html', '/icon/20260608_025245_6a26125de166d_Dajia29geli.html', 'text/html', 70157, '2026-06-08 08:52:45', '15', '李文傑', NULL, 'active', 166, 1, 0),
(70, 'build_tseng_site.py', 'D:/web02/icon/20260608_031634_6a2617f26d108_build_tseng_site.py', '/icon/20260608_031634_6a2617f26d108_build_tseng_site.py', 'application/octet-stream', 45582, '2026-06-08 09:16:34', '35', '李國華', NULL, 'active', 167, 1, 0),
(71, '龍蝦1.png', 'D:/web02/icon/20260608_031702_6a26180e919fb_龍蝦1.png', '/icon/20260608_031702_6a26180e919fb_龍蝦1.png', 'image/png', 694056, '2026-06-08 09:17:02', '35', '李國華', NULL, 'active', 167, 1, 0),
(72, '家族祈願圖片.png', 'D:/web02/icon/20260608_031702_6a26180e9214a_家族祈願圖片.png', '/icon/20260608_031702_6a26180e9214a_家族祈願圖片.png', 'image/png', 1568417, '2026-06-08 09:17:02', '35', '李國華', NULL, 'active', 167, 1, 0),
(73, '家族祈願圖片.jpg', 'D:/web02/icon/20260608_031702_6a26180e928ed_家族祈願圖片.jpg', '/icon/20260608_031702_6a26180e928ed_家族祈願圖片.jpg', 'image/jpeg', 2300104, '2026-06-08 09:17:02', '35', '李國華', NULL, 'active', 167, 1, 0),
(74, '划手機必背PHP使用的SQL通用與法.pdf', 'D:/web02/icon/20260608_031702_6a26180e92e4a_划手機必背PHP使用的SQL通用與法.pdf', '/icon/20260608_031702_6a26180e92e4a_划手機必背PHP使用的SQL通用與法.pdf', 'application/pdf', 65302, '2026-06-08 09:17:02', '35', '李國華', NULL, 'active', 167, 1, 0),
(75, 'logo-轉檔前格式-為-png.webp', 'D:/web02/icon/20260608_032529_6a261a096ef8c_logo-轉檔前格式-為-png.webp', '/icon/20260608_032529_6a261a096ef8c_logo-轉檔前格式-為-png.webp', 'image/webp', 4686, '2026-06-08 09:25:29', '25', '李美華', NULL, 'active', 168, 1, 0),
(76, '龍蝦1.png', 'D:/web02/icon/20260608_032613_6a261a35e9afa_龍蝦1.png', '/icon/20260608_032613_6a261a35e9afa_龍蝦1.png', 'image/png', 694056, '2026-06-08 09:26:13', '29', '李大同', NULL, 'active', 169, 1, 0),
(77, '龍蝦1.png', 'D:/web02/icon/20260608_071601_6a265011c461c_龍蝦1.png', '/icon/20260608_071601_6a265011c461c_龍蝦1.png', 'image/png', 694056, '2026-06-08 13:16:01', '10', '李文山', NULL, 'active', 166, 1, 0),
(78, '大頭照_男01.webp', 'D:/web02/icon/20260611_151646_6a2ab53eab73b_大頭照_男01.webp', '/icon/20260611_151646_6a2ab53eab73b_大頭照_男01.webp', 'image/webp', 49080, '2026-06-11 21:16:46', '5', '李秋香', NULL, 'active', 167, 1, 0),
(79, '會員入會申請表_李寶珠.xls', 'D:/web02/icon/20260611_151713_6a2ab559e92de_會員入會申請表_李寶珠.xls', '/icon/20260611_151713_6a2ab559e92de_會員入會申請表_李寶珠.xls', 'application/vnd.ms-excel', 386115, '2026-06-11 21:17:13', '5', '李秋香', NULL, 'active', 167, 1, 0),
(80, '會員入會申請表_李寶珠 (1).doc', 'D:/web02/icon/20260611_151713_6a2ab559e9b26_會員入會申請表_李寶珠 (1).doc', '/icon/20260611_151713_6a2ab559e9b26_會員入會申請表_李寶珠 (1).doc', 'application/msword', 385592, '2026-06-11 21:17:13', NULL, NULL, NULL, 'active', 0, 1, 0),
(81, '2026_AI_Navigator.pptx', 'D:/web02/icon/20260611_151713_6a2ab559ea080_2026_AI_Navigator.pptx', '/icon/20260611_151713_6a2ab559ea080_2026_AI_Navigator.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 15372357, '2026-06-11 21:17:13', '5', '李秋香', NULL, 'active', 167, 1, 0),
(82, '祭祀公業李武略派下現員名冊.zip', 'D:/web02/icon/20260612_101046_6a2bbf06689fc_祭祀公業李武略派下現員名冊.zip', '/icon/20260612_101046_6a2bbf06689fc_祭祀公業李武略派下現員名冊.zip', 'application/x-zip-compressed', 16928489, '2026-06-12 16:10:46', '50', '李家明', NULL, 'active', 168, 1, 0),
(83, '2026_AI_發展版圖與實用工具.pptx', 'D:/web02/icon/20260612_101057_6a2bbf11158e2_2026_AI_發展版圖與實用工具.pptx', '/icon/20260612_101057_6a2bbf11158e2_2026_AI_發展版圖與實用工具.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 15372357, '2026-06-12 16:10:57', '50', '李家明', NULL, 'active', 168, 1, 0),
(84, '2026_AI_發展版圖與實用工具.mp4', 'D:/web02/icon/20260612_101115_6a2bbf23aadf3_2026_AI_發展版圖與實用工具.mp4', '/icon/20260612_101115_6a2bbf23aadf3_2026_AI_發展版圖與實用工具.mp4', 'video/mp4', 52266421, '2026-06-12 16:11:15', '50', '李家明', NULL, 'active', 168, 1, 0),
(85, '2026_AI_發展版圖與實用工具.mp4', 'D:/web02/icon/20260612_113856_6a2bd3b0b6e60_2026_AI_發展版圖與實用工具.mp4', '/icon/20260612_113856_6a2bd3b0b6e60_2026_AI_發展版圖與實用工具.mp4', 'video/mp4', 52266421, '2026-06-12 17:38:56', '5', '李秋香', NULL, 'active', 169, 1, 0),
(86, '祭祖是否出席統計資料_明信片.jpg', 'D:/web02/icon/20260613_000528_6a2c82a86fcd8_祭祖是否出席統計資料_明信片.jpg', '/icon/20260613_000528_6a2c82a86fcd8_祭祖是否出席統計資料_明信片.jpg', 'image/jpeg', 92471, '2026-06-13 06:05:28', '18', '李淑惠', NULL, 'active', 169, 1, 0),
(87, 'vid_6a2fd162f2bd1.webm', 'uploads/vid_6a2fd162f2bd1.webm', 'http://127.0.0.1//lee/backend/uploads/vid_6a2fd162f2bd1.webm', 'video/webm', 7738176, '2026-06-15 18:18:10', '李美麗', '2', '攝影鏡頭正常\r\n', 'active', 0, 1, 1),
(88, 'vid_6a2fd2a6a162b.webm', 'uploads/vid_6a2fd2a6a162b.webm', 'http://127.0.0.1//lee/backend/uploads/vid_6a2fd2a6a162b.webm', 'video/webm', 15885284, '2026-06-15 18:23:34', '李美麗', '2', '寄件者：2， 編號：李美麗， 第30世祖 第5代 第1大房。\r\n-----------------------------------\r\n', 'active', 0, 1, 0),
(89, 'vid_6a2fd836c5e7b.webm', 'uploads/vid_6a2fd836c5e7b.webm', 'http://127.0.0.1//lee/backend/uploads/vid_6a2fd836c5e7b.webm', 'video/webm', 13995054, '2026-06-15 18:47:18', '李美麗', '2', '寄件者：2， 編號：李美麗， 第30世祖 第5代 第1大房。\r\n-----------------------------------\r\n', 'active', 0, 1, 1),
(90, 'vid_6a2fd8ea842dc.webm', 'uploads/vid_6a2fd8ea842dc.webm', 'http://127.0.0.1//lee/backend/uploads/vid_6a2fd8ea842dc.webm', 'video/webm', 33283897, '2026-06-15 18:50:18', '李美麗', '2', '寄件者：2， 編號：李美麗， 第30世祖 第5代 第1大房。\r\n-----------------------------------\r\n', 'active', 0, 1, 1),
(91, '127_0_0_1 (1).sql', 'D:/web02/icon/20260619_015801_6a348609e46c7_127_0_0_1 (1).sql', '/icon/20260619_015801_6a348609e46c7_127_0_0_1 (1).sql', 'application/octet-stream', 300625, '2026-06-19 07:58:01', NULL, NULL, NULL, 'active', 0, 1, 0);

-- --------------------------------------------------------

--
-- 資料表結構 `makeawish`
--

CREATE TABLE `makeawish` (
  `id` int(11) NOT NULL,
  `generation` int(11) NOT NULL COMMENT '世代(大甲算起)',
  `emperor_shizu` int(11) NOT NULL COMMENT '世祖(台灣算起)',
  `number_of_houses` int(11) NOT NULL COMMENT '房數',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `family_members` varchar(100) DEFAULT NULL COMMENT '家庭成員',
  `message_of_blessing` text DEFAULT NULL COMMENT '祝福的話',
  `login_time` datetime NOT NULL COMMENT '登錄時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `makeawish`
--

INSERT INTO `makeawish` (`id`, `generation`, `emperor_shizu`, `number_of_houses`, `name`, `family_members`, `message_of_blessing`, `login_time`) VALUES
(1, 7, 29, 3, '李明輝', '妻、長子', '祈願祖先庇佑，子孫平安，事業興隆，家庭幸福美滿。', '1975-02-13 14:22:45'),
(2, 11, 29, 1, '李美麗', '個人', '感謝祖德，願我心境安寧，生活平安，事業順遂，囧，ありがとう。', '1975-09-18 08:41:12'),
(3, 3, 27, 2, '李大山', '個人', '感謝祖德，願我心想事成，*_^眨眨眼，ありがとう，Stay strong! Orz', '1990-03-15 21:05:33'),
(4, 9, 27, 2, '李志強', '個人', '感謝祖先，願我能在事業上有所突破，生活安穩。', '1976-02-14 11:18:59'),
(5, 9, 29, 3, '李秋香', '個人', '感謝祖先庇佑，偶要繼續努力，事業順利，囧，ありがとう，Stay cool! Orz ~~~', '1976-11-09 17:34:02'),
(6, 6, 29, 3, '李文雄', '個人', '祈求祖先庇佑，讓我在學業上能有所突破，考試順利，學業精進。祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在學業或生活中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光，讓祖德在我身上延續。', '1978-01-19 05:12:26'),
(7, 7, 30, 2, '李美玲', '妻、長子', '祈願祖先庇佑，子孫平安，事業興隆，家庭幸福美滿，Orz，ありがとう。', '1978-04-09 23:50:14'),
(8, 8, 27, 1, '李建國', '個人', '感謝祖先庇佑，學業突破，～..～一隻豬在笑，ありがとう，Orz', '1977-06-30 13:08:41'),
(9, 7, 30, 1, '李淑珍', '妻、長子', '祈願祖先庇佑，子孫平安，事業興隆，家庭幸福美滿。', '1977-11-08 09:27:18'),
(10, 3, 29, 3, '李志明', '妻、長子', '感謝祖德，願子孫平安，事業興隆。長子正值成長階段，我希望他能在祖德庇佑下，培養仁心與智慧，懂得尊重他人、珍惜家庭。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子的成長。祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿。', '1979-05-22 16:15:50'),
(11, 6, 30, 2, '李秀英', '個人', '祈願祖先庇佑，考試順利，囧，ありがとう，사랑합니다，Orz。', '1979-10-05 02:49:31'),
(12, 11, 28, 3, '李建華', '妻二子', '祈求祖先保佑家庭和樂子女勤學代代延續祖宗精神幸福永續', '1980-03-17 19:22:07'),
(13, 10, 27, 1, '李家豪', '妻、幼子', '感謝祖先，願幼子聰明伶俐，健康快樂，幸福一生，囧，Stay strong!', '1981-10-31 07:04:55'),
(14, 5, 30, 1, '李美麗', '個人', '祈願祖先庇佑，事業順遂，m(S)m超人，ありがとう，Stay strong! Orz', '1981-08-24 15:33:12'),
(15, 11, 28, 1, '李文傑', '妻二子', '祈求祖先保佑家庭和樂子女勤學代代延續祖宗精神不斷繁榮昌盛', '1983-05-09 12:11:43'),
(16, 8, 29, 1, '李佩芬', '個人', '感謝祖先庇佑，願我努力學習，心境安寧，生活幸福，囧，Thank you always!', '1983-12-22 22:58:04'),
(17, 4, 30, 2, '李建智', '妻、三子', '祈願祖先庇佑，子孫勤儉持家，代代昌盛，幸福永續。', '1982-08-19 04:26:37'),
(18, 4, 29, 1, '李淑惠', '妻、三子', '祈願祖先庇佑，子孫勤儉持家，代代昌盛。', '1981-05-16 18:09:21'),
(19, 2, 30, 3, '李志遠', '妻、長女', '祈願祖先庇佑，女兒快樂成長，_微笑，囧，ありがとう，Peace forever! Orz', '1980-12-05 10:44:16'),
(20, 10, 30, 3, '李家銘', '妻、幼子', '感謝祖先，願幼子聰明伶俐，健康快樂。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子的成長.祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿。願幼子在祖德庇佑下，能在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖德在他身上延續。', '1985-02-18 14:52:33'),
(21, 8, 27, 3, '李麗君', '個人', '感謝祖先庇佑，偶要在學業上突破，囧，ありがとう，Stay strong! Orz ~~~', '1986-01-26 20:17:08'),
(22, 5, 30, 3, '李志誠', '個人', '祈求祖先庇佑，讓我在事業上能有所突破，生活安穩。祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或生活中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '1985-11-04 06:39:51'),
(23, 10, 28, 2, '李秀蘭', '個人', '感謝祖先的庇佑，讓我能在困境中保持堅毅。人生道路並非一帆風順，但祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光，讓祖德在我身上延續，並傳承給後代。', '1987-03-25 11:23:14'),
(24, 6, 30, 3, '李建勳', '個人', '祈願祖先庇佑，考試順利，><#慘了，ありがとう，사랑합니다，Orz', '2009-07-07 16:05:42'),
(25, 4, 30, 1, '李美華', '個人', '感謝祖德，願我心境安寧，身體康健，能為家族盡一份心力。', '1986-09-09 22:12:29'),
(26, 4, 27, 2, '李志明', '個人', '感謝祖德庇佑，願我事業穩步前行，生活安定，心境平和，ありがとう。', '1986-12-16 03:48:11'),
(27, 2, 30, 1, '李英傑', '妻、長女', '祈願祖先庇佑，女兒快樂成長，擁有智慧仁心勇氣，幸福一生，Orz。', '1987-08-05 09:56:03'),
(28, 2, 29, 1, '李淑芬', '個人', '祈願祖先庇佑，心境平和，生活幸福。', '1984-04-09 15:19:37'),
(29, 8, 28, 1, '李大同', '個人', '感謝祖先庇佑，願我能在困境中保持堅毅，心境安寧。', '1984-10-31 18:42:50'),
(30, 3, 27, 2, '李麗婷', '個人', '感謝祖德，偶要心想事成，勇敢前行，囧，ありがとう，Stay strong! Orz o', '1984-05-21 07:31:06'),
(31, 3, 28, 3, '李志明', '妻、長子', '感謝祖德，願子孫平安，事業興隆。', '1989-11-03 21:14:25'),
(32, 3, 29, 1, '李阿鑾', '妻、長子', '感謝祖德，願子孫平安，事業興隆，家庭幸福。', '1990-08-20 13:50:18'),
(33, 4, 30, 1, '李建宏', '妻、三子', '祈求祖先庇佑，讓我們一家人能在平凡的生活中找到幸福與安定。三個孩子正值成長階段，我希望他們能在祖德庇佑下，培養仁心與智慧，懂得尊重他人、珍惜家庭。願妻子健康長壽，孩子們勤學不輟，代代承繼祖先的精神，讓家族在未來的世代中持續繁榮昌盛。', '1991-04-01 02:08:44'),
(34, 3, 29, 2, '李美玲', '妻、長子', '感謝祖德，願子孫平安，事業興隆，家庭幸福，囧，사랑합니다。', '1992-01-10 10:27:53'),
(35, 6, 30, 1, '李國華', '個人', '感謝祖先庇佑，願我能在學業上持續精進，不僅僅是為了個人榮耀，更是為了家族的延續與光耀。祖先的智慧與勤勞是我們的根基，我希望能以謙遜之心承繼這份精神，並在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖先的德澤在我身上延續，並傳承給後代子孫，代代不息。', '1992-06-15 17:39:11'),
(36, 11, 29, 3, '李金木', '個人', '感謝祖德，心境安寧，f_騷騷頭，ありがとう，Stay cool! Orz', '1992-09-04 23:04:36'),
(37, 5, 27, 2, '李淑芬', '妻、二女', '祈求祖先庇佑，女兒幸福快樂，家庭和樂融融。', '1995-02-11 08:15:22'),
(38, 5, 28, 3, '李志豪', '妻、二女', '祈求祖先庇佑，女兒幸福快樂，家庭和樂融融，사랑합니다。', '1995-09-30 14:48:09'),
(39, 7, 28, 1, '李寶珠', '個人', '祈求祖先庇佑，心境平和，囧，ありがとう，사랑합니다，Orz。', '1996-03-07 12:53:41'),
(40, 11, 28, 3, '李水源', '妻、二子', '祈求祖先保佑，家人幸福，子女勤學，Orz，사랑합니다，囧，Keep going!', '2020-06-18 19:11:30'),
(41, 2, 28, 3, '李家豪', '妻、三女', '祈願祖先庇佑，讓三位女兒能在成長的道路上，擁有智慧、仁心與勇氣。願她們在學業上有所成就，在生活中懂得珍惜與感恩。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子們的成長。祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿。', '1994-01-14 04:36:57'),
(42, 5, 28, 1, '李佩君', '妻、二女', '祈求祖先庇佑，女兒幸福快樂，家庭和樂融融，Orz，Peace forever!', '1991-06-14 22:02:15'),
(43, 2, 30, 2, '李俊傑', '妻長女', '祈願祖先庇佑女兒快樂成長擁有智慧仁心勇氣幸福一生', '1993-07-02 11:25:48'),
(44, 9, 29, 1, '李火旺', '個人', '感謝祖先庇佑，願我生活平安幸福，事業順利，心境安寧。', '1994-04-06 15:44:03'),
(45, 11, 29, 2, '李雅婷', '個人', '感謝祖德，偶要心境安寧，生活平安，囧，ありがとう，Stay cool! Orz _', '1993-11-03 09:19:36'),
(46, 9, 28, 3, '李文德', '個人', '感謝祖先，願我事業順利，生活安穩，心境常保平和。', '1993-12-03 17:51:24'),
(47, 8, 29, 3, '李美華', '個人', '感謝祖先庇佑，偶要努力拼命學習，囧，(_)!，ありがとう，Stay strong! Orz', '1985-11-14 20:07:49'),
(48, 11, 28, 3, '李志強', '個人', '感謝祖先的庇佑，讓我能在困境中保持堅毅。人生道路並非一帆風順，但祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光，讓祖德在我身上延續，並傳承給後代。', '1996-11-20 03:33:12'),
(49, 6, 28, 3, '李春枝', '妻、二子', '祈求祖先庇佑，子孫勤學，代代昌盛，家族永續繁榮，Orz，Stay strong!', '1997-02-26 13:14:55'),
(50, 7, 28, 2, '李家明', '個人', '祈求祖先庇佑，心境平和，3親一個，ありがとう，Orz', '1988-10-05 21:40:28'),
(51, 8, 28, 2, '李雅如', '個人', '感謝祖先庇佑，願我能在困境中保持堅毅，心境安寧。人生道路並非一帆風順，但祖先的精神提醒我：勤勞、誠信、仁愛是立身', '2007-11-09 07:22:11'),
(52, 8, 29, 3, '李天送', '個人', '感謝祖先庇佑，願我在學業上努力不懈，取得成果，Thank you ancestors.', '2007-10-24 16:58:34'),
(53, 8, 28, 2, '李建宏', '個人', '感謝祖先庇佑，願我在學業與事業上皆能持續精進，不負祖德厚望。', '2008-01-16 11:05:19'),
(54, 9, 27, 3, '李秀英', '個人', '感謝祖先，願我能在事業上有所突破，生活安穩，囧，Keep going!', '2008-05-05 23:17:42'),
(55, 10, 29, 2, '李志遠', '個人', '感謝祖先，願我智慧處事，@_@頭昏眼花，ありがとう，Stay blessed! Orz', '2012-10-11 14:43:06'),
(56, 10, 30, 2, '李阿土', '妻、幼子', '感謝祖先，願幼子聰明伶俐，健康成長。', '2011-08-05 05:29:51'),
(57, 10, 27, 3, '李美玲', '妻、幼子', '感謝祖先，願幼子聰明伶俐，健康快樂，幸福一生。', '2011-11-26 18:12:37'),
(58, 10, 29, 1, '李冠宇', '個人', '感謝祖先，偶要以仁心待人，智慧處事，囧，ありがとう，Stay blessed! Orz (T_T)', '2013-07-14 09:36:14'),
(59, 5, 30, 3, '李淑惠', '個人', '感謝祖先庇佑，讓我在生命的旅程中能夠保持堅毅與智慧。祖先的勤勞與仁心是我們的根基，我願以此為榜樣，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光，讓祖德在我身上延續，並傳承給後代子孫，代代不息。', '2012-03-08 22:50:23'),
(60, 2, 28, 1, '李大同', '妻、三女', '祈求祖先庇佑，女兒幸福快樂，家庭和樂，ありがとう。', '2010-04-10 13:19:48'),
(61, 7, 29, 3, '李明輝', '妻、三子', '祈願祖先庇佑，子孫勤儉持家，代代昌盛，幸福永續，Orz，Stay blessed!', '2011-03-24 20:04:15'),
(62, 11, 29, 1, '李美麗', '妻、二子', '祈求祖先庇佑，子孫勤學，>_<想哭，囧，ありがとう，사랑합니다，Orz', '2016-08-08 07:41:56'),
(63, 3, 27, 2, '李大山', '個人', '感謝祖先庇佑，願我能在事業上有所突破，不僅僅是為了個人利益，更是為了家族的榮耀。祖先的勤勞與智慧是我們的根基，我希望能以此為榜樣，努力不懈，追求卓越。願祖先庇佑我能在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖德在我身上延續，並傳承給後代，代代不息。', '2013-02-24 15:28:33'),
(64, 9, 27, 2, '李志強', '個人', '祈求祖先庇佑，讓我在學業上能有所突破，考試順利，學業精進。祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在學業或生活中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '2011-10-05 11:14:09'),
(65, 9, 29, 3, '李秋香', '個人', '感謝祖德庇佑，願我事業順利，生活安定，心境平和，사랑합니다，囧。', '2014-01-14 18:57:22'),
(66, 6, 29, 3, '李文雄', '個人', '感謝祖德，願我心想事成，能在困境中保持堅毅。', '2012-04-28 23:35:46'),
(67, 7, 30, 2, '李美玲', '個人', '感謝祖德，願我心想事成，能在困境中保持堅毅，勇敢前行。', '2012-06-11 04:08:13'),
(68, 8, 27, 1, '李建國', '妻、三女', '祈求祖先庇佑，女兒幸福快樂，家庭和樂。', '2014-05-05 16:22:50'),
(69, 7, 30, 1, '李淑珍', '個人', '感謝祖德庇佑，偶要努力工作，生活安定，囧，ありがとう，Stay blessed! Orz (^^)', '1994-10-29 10:49:17'),
(70, 3, 29, 3, '李志明', '妻、幼子', '感謝祖先，願幼子聰明伶俐，健康快樂。', '1997-11-11 21:13:38'),
(71, 6, 30, 2, '李秀英', '個人', '感謝祖先庇佑讓我能在學業上努力並獲得成果不負祖德厚望 Thank you ancestors', '2004-09-05 14:32:05'),
(72, 11, 28, 3, '李建華', '妻、長子', '感謝祖德，願子孫平安，囧，ありがとう，Stay blessed! Orz ~~~', '2005-05-24 08:55:42'),
(73, 10, 27, 1, '李家豪', '妻、二女', '祈求祖先庇佑，女兒快樂成長，家庭幸福。', '2002-12-14 17:18:29'),
(74, 5, 30, 1, '李美麗', '妻、三女', '祈求祖先庇佑，女兒幸福快樂，x~x糟糕--裝死，囧，ありがとう，Orz', '1982-04-13 23:04:11'),
(75, 11, 28, 1, '李文傑', '妻、長子', '感謝祖德，願子孫平安，事業興隆。長子正值成長階段，我希望他能在祖德庇佑下，培養仁心與智慧，懂得尊重他人、珍惜家庭。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子的成長.祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿，並在社會上有所貢獻。', '2003-08-04 11:47:36'),
(76, 8, 29, 1, '李佩芬', '個人', '祈願祖先庇佑，讓我在生活中能保持心境平和，無論遇到何種挑戰，都能以堅毅之志克服。祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '2002-09-11 05:21:58'),
(77, 4, 30, 2, '李建智', '妻、三女', '祈求祖先庇佑，女兒幸福快樂，囧，ありがとう，사랑합니다，Orz。', '1989-06-19 13:39:14'),
(78, 4, 29, 1, '李淑惠', '個人', '感謝祖先，願我能以仁心待人，智慧處事，延續祖德。', '2000-01-25 20:52:47'),
(79, 2, 30, 3, '李志遠', '個人', '感謝祖德庇佑，願我事業順利，--_--;，ありがとう，Stay blessed! Orz', '1999-07-23 09:14:33'),
(80, 10, 30, 3, '李家銘', '個人', '感謝祖先，願我能以仁心待人，智慧處事，延續祖德。', '2001-06-07 16:28:06'),
(81, 8, 27, 3, '李麗君', '個人', '祈願祖先庇佑，事業順遂，囧，ありがとう，사랑합니다，Orz，Keep going!', '2004-04-18 22:43:51'),
(82, 5, 30, 3, '李志誠', '妻、長子', '感謝祖德，子孫平安，_臉紅，ありがとう', '1998-01-31 03:07:25'),
(83, 10, 28, 2, '李秀蘭', '妻、幼子', '祈願祖先庇佑，讓幼子能在成長的道路上，擁有智慧、仁心與勇氣。願他在學業上有所成就，在生活中懂得珍惜與感恩。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子的成長。祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿，並在社會上有所貢獻，成為祖先的驕傲。願幼子在祖德庇佑下，能在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖德在他身上延續，代代不息。', '2001-12-12 11:59:18'),
(84, 6, 30, 3, '李建勳', '妻、三女', '祈求祖先庇佑，女兒幸福快樂，家庭和樂，Orz，사랑합니다。', '2003-11-11 15:16:44'),
(85, 4, 30, 1, '李美華', '妻、二子', '祈求祖先庇佑，子孫勤學，代代昌盛，囧，ありがとう，사랑합니다，Orz。', '2001-09-15 18:34:02'),
(86, 4, 27, 2, '李志明', '個人', '祈願祖先庇佑，考試順利，學業精進，為家族增光。', '2002-03-01 07:45:29'),
(87, 2, 30, 1, '李英傑', '個人', '祈願祖先庇佑，考試順利，學業精進，為家族增光。', '2002-02-26 21:12:53'),
(88, 2, 29, 1, '李淑芬', '妻、長子', '祈願祖先庇佑，子孫平安，><？聽不懂，囧，ありがとう，Orz', '1995-05-29 14:27:10'),
(89, 8, 28, 1, '李大同', '個人', '感謝祖德，願我心想事成，能在困境中保持堅毅，囧，勇敢前行。', '2002-07-20 04:38:16'),
(90, 3, 27, 2, '李麗婷', '個人', '感謝祖德庇佑讓我在事業上能夠穩步前行生活安定心境平和', '2005-01-27 11:09:45'),
(91, 3, 28, 3, '李志明', '妻、三子', '祈願祖先庇佑，子孫勤儉持家，囧，ありがとう，사랑합니다，Orz。', '2000-08-11 23:51:32'),
(92, 3, 29, 1, '李阿鑾', '個人', '祈願祖先庇佑，事業順遂，生活安穩，心境平和，Orz，ありがとう。', '2004-12-14 16:14:27'),
(93, 4, 30, 1, '李建宏', '個人', '感謝祖德庇佑讓我在事業上穩步前行生活安定心境平和ありがとうご先祖様', '2005-12-18 08:33:50'),
(94, 3, 29, 2, '李美玲', '個人', '感謝祖德，願我心境安寧，生活平安，事業順遂。', '2006-01-22 20:46:13'),
(95, 6, 30, 1, '李國華', '個人', '感謝祖先庇佑，偶要繼續拼命學習，心境安寧，囧，ありがとう，Stay strong! Orz _', '2006-03-12 13:18:04'),
(96, 11, 29, 3, '李金木', '個人', '感謝祖德，願我心境安寧，生活平安，事業順遂。', '2006-05-22 05:57:41'),
(97, 5, 27, 2, '李淑芬', '個人', '感謝祖先，願我能以仁心待人，智慧處事，延續祖德，囧，사랑합니다。', '2019-11-17 17:24:19'),
(98, 5, 28, 3, '李志豪', '個人', '感謝祖德，願我事業蒸蒸日上，生活平安。', '2020-07-20 11:42:35'),
(99, 7, 28, 1, '李寶珠', '妻、三子', '祈願祖先庇佑，子孫昌盛，T_T哭哭啦><，ありがとう，사랑합니다，Orz', '2021-12-25 22:08:12'),
(100, 11, 28, 3, '李水源', '個人', '祈求祖先庇佑，讓我在事業上能有所突破，生活安穩.祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或生活中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '2018-05-22 09:35:56'),
(101, 2, 28, 3, '李家豪', '妻、二子', '祈求祖先庇佑，讓我們一家人能在平凡的生活中找到幸福與安定。兩個孩子正值成長階段，我希望他們能在祖德庇佑下，培養仁心與智慧，懂得尊重他人、珍惜家庭。願妻子健康長壽，孩子們勤學不輟，代代承繼祖先的精神，讓家族在未來的世代中持續繁榮昌盛，並在社會上有所貢獻，成為祖先的驕傲。', '2018-11-30 14:19:43'),
(102, 5, 28, 1, '李佩君', '妻、二女', '祈求祖先庇佑，女兒幸福快樂，囧，ありがとう，사랑합니다，Orz，Peace forever!', '2010-12-01 23:51:07'),
(103, 2, 30, 2, '李俊傑', '個人', '感謝祖先的庇佑，讓我能在困境中保持堅毅。人生道路並非一帆風順，但祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '2017-06-19 04:13:28'),
(104, 9, 29, 1, '李火旺', '個人', '祈求祖先庇佑，心境平和，生活幸福，夢想成真，Orz，ありがとう。', '2017-03-22 16:47:52'),
(105, 11, 29, 2, '李雅婷', '個人', '感謝祖先庇佑，願我能在困境中保持堅毅，心境安寧。人生道路並非一帆風順，但祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '2017-05-18 11:29:14'),
(106, 9, 28, 3, '李文德', '妻、長女', '祈願祖先庇佑，女兒快樂成長，擁有智慧仁心勇氣，幸福一生。', '2018-08-29 20:05:41'),
(107, 8, 29, 3, '李美華', '妻、長女', '祈願祖先庇佑，女兒快樂成長，未來能有智慧與仁心。', '2017-11-14 07:33:16'),
(108, 11, 28, 3, '李志強', '妻、長女', '祈願祖先庇佑，女兒快樂成長，幸福一生，囧，ありがとう，사랑합니다，Orz。', '2018-02-04 15:18:59'),
(109, 6, 28, 3, '李春枝', '個人', '祈求祖先庇佑，心境平和，生活幸福，夢想成真。', '2019-04-14 21:54:23'),
(110, 7, 28, 2, '李家明', '妻、二子', '祈求祖先保佑，家庭和樂幸福，(_)A，사랑합니다，囧，Keep going! Orz', '2007-04-02 10:12:47'),
(111, 8, 28, 2, '李雅如', '妻、三子', '祈求祖先庇佑，讓我們一家人能在平凡的生活中找到幸福與安定。三個孩子正值成長階段，我希望他們能在祖德庇佑下，培養仁心與智慧，懂得尊重他人、珍惜家庭。願妻子健康長壽，孩子們勤學不輟，代代承繼祖先的精神，讓家族在未來的世代中持續繁榮昌盛，並在社會上有所貢獻，成為祖先的驕傲。', '2017-10-01 13:40:35'),
(112, 8, 29, 3, '李天送', '個人', '感謝祖先庇佑，願我能在事業上有所突破，不僅僅是為了個人利益，更是為了家族的榮耀。祖先的勤勞與智慧是我們的根基，我希望能以此為榜樣，努力不懈，追求卓越。願祖先庇佑我能在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖德在我身上延續，並傳承給後代。', '2016-12-25 04:22:19'),
(113, 8, 28, 2, '李建宏', '妻、幼子', '感謝祖先，願幼子聰明伶俐，囧，ありがとう，Stay strong! Orz o', '2017-09-12 23:15:58'),
(114, 9, 27, 3, '李秀英', '妻、二子', '祈求祖先保佑，家庭和樂幸福，子女勤學，代代延續祖宗精神。', '2016-01-04 16:51:04'),
(115, 10, 29, 2, '李志遠', '妻、二子', '祈求祖先保佑，家庭和樂，子女勤學，代代延續祖宗精神。', '2015-03-02 08:37:12'),
(116, 10, 30, 2, '李阿土', '個人', '感謝祖先，事業突破，v(-)v和平：勝利，ありがとう，Stay strong! Orz', '2015-11-22 11:04:49'),
(117, 10, 27, 3, '李美玲', '妻、三女', '祈願祖先庇佑，讓三位女兒能在成長的道路上，擁有智慧、仁心與勇氣。願她們在學業上有所成就，在生活中懂得珍惜與感恩。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子們的成長。祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿，並在社會上有所貢獻，成為祖先的驕傲。', '2020-08-14 22:26:33'),
(118, 10, 29, 1, '李冠宇', '個人', '感謝祖先庇佑，願我生活平安幸福，事業順利，囧，ありがとうご先祖様。', '2021-05-11 14:58:15'),
(119, 5, 30, 3, '李淑惠', '個人', '祈求祖先庇佑，心境平和，生活幸福。', '2021-06-14 05:19:42'),
(120, 2, 28, 1, '李大同', '妻、長子', '祈願祖先庇佑，子孫平安，家庭幸福，囧，ありがとう，사랑합니다，Orz。', '2025-04-30 17:43:08'),
(121, 5, 28, 3, '李志豪', '個人', '感謝祖先庇佑，願我能在學業上持續精進，不僅僅是為了個人榮耀，更是為了家族的延續與光耀。祖先的智慧與勤勞是我們的根基，我希望能以謙遜之心承繼這份精神，並在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖先的德澤在我身上延續。', '2021-12-14 20:12:51'),
(122, 7, 28, 1, '李寶珠', '妻、三子', '祈求祖先庇佑，女兒幸福快樂，=..=凸一隻豬比中指，囧，ありがとう，Orz', '2003-02-17 09:36:24'),
(123, 11, 28, 3, '李水源', '個人', '感謝祖先庇佑讓我能在學業上持續努力並獲得成果不負祖德厚望', '2021-10-15 23:55:17'),
(124, 2, 28, 3, '李家豪', '妻、二子', '祈願祖先庇佑，考試順利，學業精進，為家族增光，Orz，ありがとう。', '2022-07-19 13:14:46'),
(125, 5, 28, 1, '李佩君', '妻、二女', '感謝祖先，偶要在事業上突破，囧，ありがとう，Stay strong! Orz (^^)', '2022-02-28 07:49:03'),
(126, 2, 30, 2, '李俊傑', '個人', '感謝祖先庇佑，願我在學業上有所突破，為家族增光。', '2022-10-23 16:22:35'),
(127, 9, 29, 1, '李火旺', '個人', '感謝祖先庇佑，願我在學業上有所突破，為家族增光。', '2022-11-05 11:08:14'),
(128, 11, 29, 2, '李雅婷', '個人', '祈求祖先庇佑，子孫勤學，代代昌盛，家族永續繁榮。', '2023-01-19 21:41:59'),
(129, 9, 28, 3, '李文德', '妻、長女', '感謝祖先，幼子聰明伶俐，(o)/萬歲，ありがとう，사랑합니다，Orz', '2023-05-16 04:15:32'),
(130, 8, 29, 3, '李美華', '妻、長女', '祈求祖先庇佑，子孫勤學，代代昌盛，家族永續繁榮。', '2023-02-13 15:37:26'),
(131, 11, 28, 3, '李志強', '妻、長女', '感謝祖先庇佑，願我在學業上有所突破，為家族增光，囧，ありがとう。', '2023-09-03 22:50:11'),
(132, 6, 28, 3, '李春枝', '個人', '祈願祖先庇佑，事業順遂，生活安穩，心境平和。', '2025-02-01 08:12:44'),
(133, 7, 28, 2, '李家明', '妻、二子', '感謝祖先庇佑，生活平安幸福，-_-沒什麼反應，ありがとう，Stay cool! Orz', '2024-01-19 14:29:53'),
(134, 8, 28, 2, '李雅如', '妻、三子', '感謝祖先，願幼子聰明伶俐，健康快樂。妻子辛勤持家，我希望她能健康長壽，與我一同見證孩子的成長。祖先的精神是我們的指引，願我們一家人能在祖德庇佑下，代代幸福美滿。願幼子在祖德庇佑下，能在未來的道路上，無論遇到何種挑戰，都能以堅毅之志克服，讓祖德在他身上延續。', '2024-07-23 17:04:18'),
(135, 8, 29, 3, '李天送', '個人', '感謝祖先，願我能在事業上有所突破，生活安穩。', '2024-04-18 11:51:36'),
(136, 8, 28, 2, '李建宏', '妻、幼子', '祈求祖先保佑，家庭和樂，子女勤學，代代昌盛，Orz，ありがとう。', '2024-10-09 23:16:25'),
(137, 9, 27, 3, '李秀英', '妻、二子', '祈願祖先庇佑，讓我在生活中能保持心境平和，無論遇到何種挑戰，都能以堅毅之志克服。祖先的精神提醒我：勤勞、誠信、仁愛是立身之本。我願以此為座右銘，無論在事業或人際交往中，都能以誠待人，以信立世。願祖先庇佑我能在未來的挑戰中不退縮，勇敢面對，並以自身的努力為家族增添榮光。', '2025-06-13 05:43:09'),
(138, 10, 29, 2, '李志遠', '妻、二子', '祈願祖先庇佑，事業順遂，生活安穩。', '2025-11-17 13:22:47'),
(139, 10, 30, 2, '李阿土', '個人', '小時候總聽阿公說『吃米擔水要思源』。長大後進了都市工作，每當遇到挫折，想到祖先們當年渡海與對抗天災的堅韌，就覺得自己充滿力量。', '2025-11-17 20:08:54'),
(140, 10, 27, 3, '李美玲', '妻、三女', '每到秋收時節，看著金黃色的稻浪，就彷彿看見公媽們微笑的臉龐。願李氏家族就像這棵大樹一樣，根深蒂固，開花結果。', '2025-11-18 09:41:13'),
(141, 10, 29, 1, '李冠宇', '個人', '謝謝阿太、阿公、阿嬤一輩子守護這片土地，讓我們有家可回、有根可尋。每當回到三合院，心裡就無比踏實。', '2025-05-19 16:55:32'),
(142, 5, 30, 3, '李淑惠', '個人', '感念曾祖父當年用一雙長滿繭的手，一鋤一鋤地在這片荒地挖出水田。如今我們雖然不再務農，但那份刻苦耐勞、腳踏實地的家風，我一定會繼續傳給下一代。', '1982-08-19 22:14:06'),
(143, 7, 26, 0, '李文雄', '個人', 'ID 加 1 計算：在後端先取得當前資料表內最大的 $max_id，並計算出 $next_id = $max_id + 1。', '0000-00-00 00:00:00'),
(144, 3, 22, 0, '李文雄', NULL, '送出前動態寫入時間：利用 JavaScript 的 submit 事件監聽器，在表單正式送出前的「那一瞬間」，動態獲取當下的系統時間，並格式化為 YYYY-MM-DD HH:MM:SS 格式填入該隱藏欄位。', '2026-06-04 14:05:07'),
(145, 5, 24, 0, '李文雄', '本人與大女兒', '1. 守護家族的三棵大樹（根深蒂固）\r\n在村莊內，三棵大樹分別代表著家族的三大房。大樹在傳統家族文化中，象徵著「根深蒂固、開花結果」的生命力。它不僅是守護整個家族的圖騰，也是後代子孫齊聚、尋根與祈願的精神對象。', '2026-06-04 14:27:31'),
(146, 4, 23, 0, '李文雄', '全家', '1. 守護家族的三棵大樹（根深蒂固）\r\n在村莊內，三棵大樹分別代表著家族的三大房。大樹在傳統家族文化中，象徵著「根深蒂固、開花結果」的生命力。它不僅是守護整個家族的圖騰，也是後代子孫齊聚、尋根與祈願的精神對象。', '2026-06-04 14:39:11'),
(147, 6, 25, 0, '李文雄', '本人與大女兒', '1. 守護家族的三棵大樹（根深蒂固）\r\n在村莊內，三棵大樹分別代表著家族的三大房。大樹在傳統家族文化中，象徵著「根深蒂固、開花結果」的生命力。它不僅是守護整個家族的圖騰，也是後代子孫齊聚、尋根與祈願的精神對象。', '2026-06-04 14:51:01'),
(149, 8, 29, 3, '李美華', '全家', '沒有話要說', '2026-06-05 06:44:13'),
(150, 10, 30, 3, '李家銘', '全家', '<p><img alt=\"\" src=\"https://www.tad.ntpc.gov.tw/uploads/images/%E8%A3%81%E7%BD%B0%E5%9F%BA%E6%BA%96%E8%A1%A8(%E8%99%95%E7%BD%B0%E6%A2%9D%E4%BE%8B%E7%AC%AC44%E6%A2%9D%E7%AC%AC2%E9%A0%85).jpg\" style=\"box-sizing: border-box; border: 0px; vertical-align: middle; color: rgb(51, 51, 51); font-family: &quot;Myriad Pro&quot;, &quot;Microsoft JhengHei&quot;, &quot;sans-serif&quot;; font-size: 19.008px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial; width: 500px; height: 338px;\"><br style=\"box-sizing: border-box; color: rgb(51, 51, 51); font-family: &quot;Myriad Pro&quot;, &quot;Microsoft JhengHei&quot;, &quot;sans-serif&quot;; font-size: 19.008px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><span style=\"color: rgb(51, 51, 51); font-family: &quot;Myriad Pro&quot;, &quot;Microsoft JhengHei&quot;, &quot;sans-serif&quot;; font-size: 19.008px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial; display: inline !important; float: none;\">&nbsp;<span>&nbsp;</span></span><strong style=\"box-sizing: border-box; font-weight: 700; color: rgb(51, 51, 51); font-family: &quot;Myriad Pro&quot;, &quot;Microsoft JhengHei&quot;, &quot;sans-serif&quot;; font-size: 19.008px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\">裁罰基準表(處罰條例第44條第2項)</strong><br></p>', '2026-06-05 15:12:10'),
(153, 6, 30, 1, '李國華', '全家', '<p><img src=\"/icon/20260605_212659_6a2323036a4b6.webp\" width=\"200px\"></p><p style=\"color: rgb(34, 34, 34); font-family: Arial, Helvetica, sans-serif; font-size: 13.3333px; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial; text-align: center;\"><span style=\"font-weight: bold; color: red;\">[ 本信件為系統自動發送 , 請勿直接回信 ]</span></p><table border=\"0\" style=\"color: rgb(34, 34, 34); font-family: Arial, Helvetica, sans-serif; font-size: 10pt; font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; font-weight: 400; letter-spacing: normal; orphans: 2; text-align: start; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; white-space: normal; background-color: rgb(255, 255, 255); text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;\"><tbody><tr><td style=\"margin: 0px;\"><strong>親愛的客戶，您好：</strong></td></tr><tr><td style=\"margin: 0px;\"><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;請開啟附加檔案瀏覽您本期的電子帳單。</p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;若您有任何疑問，<wbr>歡迎您隨時電洽本公司免付費服務電話：0809-080081。</p></td></tr><tr><td style=\"margin: 0px;\"><br></td><br></tr></tbody></table>', '2026-06-06 03:27:47'),
(154, 4, 30, 2, '李建智', '你好', '<p><img src=\"/icon/20260605_215456_6a23299032d6a.jpg\" width=\"200px\"></p>', '2026-06-06 03:55:32'),
(157, 10, 29, 2, '李志遠', '121', '<p><img src=\"/icon/20260606_015949_6a2362f5e5ff3.png\" width=\"200px\"><br><br></p><iframe width=\"400\" height=\"345\" src=\"https://www.youtube.com/embed/IXqOH6lQXAI\" frameborder=\"0\" allowfullscreen=\"\"></iframe>', '2026-06-06 08:00:44'),
(159, 8, 29, 1, '李佩芬', '本人', '<p>(三)辦理方式： 1.親自辦理：檢具應備文件至應到案之監理所站違章窗口辦理。 </p><p>2.郵繳即時銷案：凡於違反道路交通管理事件通知單左上角「得採郵繳或向郵</p><p>託之金融機構繳納罰鍰」打「ˇ」記號之案件，可至各地郵局以即時銷案方式繳納。<br><img src=\"/icon/20260606_063345_6a23a32977fdd.png\" width=\"200px\"></p>', '2026-06-06 12:33:58'),
(160, 3, 28, 3, '李志明', '本人', '<p>為了徹底解決「有白底、但文字沒有完全變黑」的問題，我加強了 CSS 的選取器範圍，</p><p>將文字顏色（color）<strong data-path-to-node=\"1\" data-index-in-node=\"53\">與</strong>文字陰影（text-shadow，防止發光字效果）強制覆寫，</p><p>並將所有可能干擾的語法高亮標籤全部強制轉為黑色。<br><img src=\"/icon/20260606_072039_6a23ae279f4aa.png\" width=\"200px\"></p>', '2026-06-06 13:21:00'),
(161, 11, 29, 3, '李金木', '本人', '<p>我已經將原本 CSS 中會導致原始碼畫面死白的 <code data-path-to-node=\"1\" data-index-in-node=\"24\">.jodit-source *</code> 與 <code data-path-to-node=\"1\" data-index-in-node=\"42\">.ace_editor *</code> 萬用字元移除，</p>\r\n<p>並替換為<strong data-path-to-node=\"1\" data-index-in-node=\"67\">精準安全的 Ace 編輯器樣式修正</strong>。</p>\r\n<p>現在切換 <code data-path-to-node=\"1\" data-index-in-node=\"90\">&lt;/&gt;</code> 原始碼模式時，可以完美呈現純白底色，</p>\r\n<p>且寫過的 HTML 內容與標籤文字都能正常顯示。<br><br><img src=\"/icon/20260606_073129_6a23b0b18bd11.png\" width=\"200px\"></p>', '2026-06-06 13:31:46'),
(162, 7, 30, 1, '李淑珍', '本人', '<p>&lt;?php</p><p>// ==========================================</p><p>// 1. 資料庫連線設定 (本機 lee)</p><p>// ==========================================</p><p>$servername = \"localhost\";</p><p>$username = \"root\";<br><img src=\"/icon/20260606_075511_6a23b63f17f62.png\" width=\"200px\"></p><p><a href=\"/icon/20260606_075439_6a23b61f511b3.pdf\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 20260606_075439_6a23b61f511b3.pdf</a>&nbsp;<br><a href=\"/icon/20260606_075439_6a23b61f5185a.sql\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 20260606_075439_6a23b61f5185a.sql</a><br>&nbsp;<a href=\"/icon/20260606_075439_6a23b61f520c6.sql\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 20260606_075439_6a23b61f520c6.sql</a>&nbsp;<br><a href=\"/icon/20260606_075439_6a23b61f52970.php\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 20260606_075439_6a23b61f52970.php</a>&nbsp;<br></p>', '2026-06-06 13:55:27'),
(164, 6, 30, 1, '李國華', '本人', '<h1 class=\"style-scope ytd-watch-metadata\" style=\"margin: 0px; padding: 0px; border: 0px; background: rgb(255, 255, 255); word-break: break-word; font-family: Roboto, Arial, sans-serif; line-height: 2.8rem; font-weight: 700; overflow: hidden; max-height: 5.6rem; -webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; text-overflow: ellipsis; white-space: normal; color: rgb(15, 15, 15); font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial; font-size: 2rem;\"><yt-formatted-string force-default-style=\"\" class=\"style-scope ytd-watch-metadata\" title=\"MULTI SUB《量販店通异界，我在修真各界搞倒賣》1~77集🔥少年經營量販店卻背負巨額債務，竟意外發現量販店連通异界，用米麵換珍寶法器，還靠异界修士還清債務！ #漫劇 #動漫 #無盡動漫社\" style=\"\"><span dir=\"auto\" class=\"style-scope yt-formatted-string\" style=\"margin: 0px; padding: 0px; border: 0px; background: transparent; font-family: &quot;Microsoft JhengHei&quot;, sans-serif; font-size: 16px;\">量販店通异界，我在修真各界搞倒賣》1~77集</span></yt-formatted-string></h1>\r\n\r\n<iframe width=\"400\" height=\"345\" src=\"https://www.youtube.com/embed/2pl0gmBf23c\" frameborder=\"0\" allowfullscreen=\"\"></iframe>', '2026-06-07 04:04:20'),
(165, 11, 29, 3, '李美麗', '本人與先生', '<iframe width=\"400\" height=\"345\" src=\"https://www.youtube.com/embed/255uuZSF65Q\" frameborder=\"0\" allowfullscreen=\"\"></iframe><h1 class=\"style-scope ytd-watch-metadata\" style=\"margin: 0px; padding: 0px; border: 0px; background: rgb(255, 255, 255); word-break: break-word; font-family: Roboto, Arial, sans-serif; line-height: 2.8rem; font-weight: 700; overflow: hidden; max-height: 5.6rem; -webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; text-overflow: ellipsis; white-space: normal; color: rgb(15, 15, 15); font-style: normal; font-variant-ligatures: normal; font-variant-caps: normal; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial; font-size: 2rem;\"><yt-formatted-string force-default-style=\"\" class=\"style-scope ytd-watch-metadata\" title=\"【全程字幕】必看！台大畢業致詞感動全場！ 淚謝珍古德博士改變一生 ‪@ChinaTimes‬\" style=\"\"><span dir=\"auto\" class=\"style-scope yt-formatted-string\" style=\"margin: 0px; padding: 0px; border: 0px; background: transparent; font-family: DFKai-SB, serif; font-size: 16px;\">台大畢業致詞感動全場！ 淚謝珍古德博士改變一生<br>台大畢業致詞感動全場！ 淚謝珍古德博士改變一生</span></yt-formatted-string></h1>', '2026-05-01 09:23:00'),
(166, 3, 28, 3, '李文山', '全家', '<p>🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)<br><img src=\"/icon/20260608_071601_6a265011c461c_龍蝦1.png\" width=\"200px\"></p>', '2026-06-08 13:16:00'),
(167, 9, 29, 3, '李秋香', '全家', '<p><img src=\"/icon/20260611_151646_6a2ab53eab73b_大頭照_男01.webp\" width=\"200px\"><br>🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)</p><p><a href=\"/icon/20260611_151713_6a2ab559e92de_會員入會申請表_李寶珠.xls\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 會員入會申請表_李寶珠.xls</a>&nbsp;<br><a href=\"/icon/20260611_151713_6a2ab559e9b26_會員入會申請表_李寶珠 (1).doc\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 會員入會申請表_李寶珠 (1).doc</a>&nbsp;<br><a href=\"/icon/20260611_151713_6a2ab559ea080_2026_AI_Navigator.pptx\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 2026_AI_Navigator.pptx</a>&nbsp;<br></p>', '2026-06-11 21:17:29'),
(168, 7, 28, 2, '李家明', '1p3bp6', '<p>🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)</p><video width=\"640\" height=\"360\" controls=\"\" autoplay=\"\" muted=\"\">  <source src=\"http://localhost/icon/20260612_101115_6a2bbf23aadf3_2026_AI_%E7%99%BC%E5%B1%95%E7%89%88%E5%9C%96%E8%88%87%E5%AF%A6%E7%94%A8%E5%B7%A5%E5%85%B7.mp4\" type=\"video/mp4\">  您的瀏覽器不支援 HTML5 影片播放。</video><p><br></p><p><a href=\"/icon/20260612_101046_6a2bbf06689fc_祭祀公業李武略派下現員名冊.zip\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 祭祀公業李武略派下現員名冊.zip</a>&nbsp;<br><a href=\"/icon/20260612_101057_6a2bbf11158e2_2026_AI_發展版圖與實用工具.pptx\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 2026_AI_發展版圖與實用工具.pptx</a>&nbsp;<br><a href=\"/icon/20260612_101115_6a2bbf23aadf3_2026_AI_發展版圖與實用工具.mp4\" target=\"_blank\" style=\"color: #0284c7; text-decoration: underline;\">📎 下載附件: 2026_AI_發展版圖與實用工具.mp4</a>&nbsp;<br></p>', '2026-06-12 16:11:00'),
(169, 5, 30, 3, '李淑惠', '全家', '<p><img src=\"/icon/20260613_000528_6a2c82a86fcd8_祭祖是否出席統計資料_明信片.jpg\" width=\"200px\"><br>🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)</p>', '2026-06-13 06:05:50'),
(169, 9, 29, 3, '李秋香', '1p3bp6', '<p>\r\n                                    <video width=\"640\" height=\"360\" controls=\"\" autoplay=\"\" muted=\"\">\r\n                                        <source src=\"/icon/20260612_113856_6a2bd3b0b6e60_2026_AI_發展版圖與實用工具.mp4\" type=\"video/mp4\">\r\n                                        您的瀏覽器不支援 HTML5 影片播放。\r\n                                    </video><br>&nbsp;🌿 祈願話語進階編輯 (可批次上傳多個、不限格式檔案)</p>', '2026-06-12 17:39:18');

-- --------------------------------------------------------

--
-- 資料表結構 `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL COMMENT '流水號',
  `receive_date` date DEFAULT NULL COMMENT '收件日期',
  `old_member` varchar(50) DEFAULT NULL COMMENT '前會員號',
  `new_member` varchar(50) DEFAULT NULL COMMENT '現在會員號',
  `generation` int(11) NOT NULL COMMENT '世代(大甲算起)',
  `emperor_shizu` int(11) NOT NULL COMMENT '世祖(台灣算起)',
  `number_of_houses` int(11) NOT NULL COMMENT '房數',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `id_card_num` varchar(20) DEFAULT NULL COMMENT '身分證字號',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `placeOfBirth` varchar(100) DEFAULT NULL COMMENT '出生地或籍貫(祖籍地)',
  `education` varchar(100) DEFAULT NULL COMMENT '學歷',
  `experience` text DEFAULT NULL COMMENT '現職/經歷',
  `address` varchar(255) DEFAULT NULL COMMENT '地址',
  `zip_code` text NOT NULL COMMENT '郵遞區號6碼',
  `mobile_phone` varchar(20) DEFAULT NULL COMMENT '行動電話',
  `home_phone` varchar(20) DEFAULT NULL COMMENT '住家電話',
  `company_phone` varchar(20) DEFAULT NULL COMMENT '公司電話',
  `email` varchar(100) DEFAULT NULL COMMENT 'E-mail',
  `introducer` varchar(50) DEFAULT NULL COMMENT '介紹人',
  `SendSubordinates` text DEFAULT NULL COMMENT '派下員狀態說明',
  `living_status` enum('存','亡','未知') NOT NULL DEFAULT '未知' COMMENT '生存狀態(存/亡/未知)',
  `status` tinyint(1) DEFAULT 1 COMMENT '帳號停用或使用中(1:使用中, 0:停用)',
  `remarks` text DEFAULT NULL COMMENT '備註',
  `update_time` datetime DEFAULT NULL COMMENT '最後更新時間',
  `last_updater` varchar(50) DEFAULT NULL COMMENT '最後更新者'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `members`
--

INSERT INTO `members` (`id`, `receive_date`, `old_member`, `new_member`, `generation`, `emperor_shizu`, `number_of_houses`, `name`, `gender`, `id_card_num`, `birthday`, `placeOfBirth`, `education`, `experience`, `address`, `zip_code`, `mobile_phone`, `home_phone`, `company_phone`, `email`, `introducer`, `SendSubordinates`, `living_status`, `status`, `remarks`, `update_time`, `last_updater`) VALUES
(1, '2021-04-12', 'OLD001', '1', 7, 29, 3, '李明輝', '男', 'A123456789', '1975-03-12', '台北市萬華區', '大學', '電子業專案經理', '台北市中正區重慶南路一段10號', '100-005', '0912-345678', '02-23456789', '02-87654321', 'minghui.lee@gmail.com', '李建國', '正常派下員', '存', 1, '常參與春季祭祖\r\n生存狀態(亡)、派下權(無), 派下員狀態, 狀態, 2026-06-14 11:56:02 修改\r\n生存狀態(存)、派下權(有), 派下員狀態, 2026-06-14 11:56:28 修改\r\n', '2026-06-14 11:56:28', '李明輝 1'),
(2, '2020-03-15', 'OLD002', '2', 5, 30, 1, '李美麗', '女', 'F201234567', '1932-08-25', '新北市', '高中', '家管(退休)', '新北市板橋區文化路二段50號', '220-413', '0921-987654', '02-29512345', '', 'meili.lee@yahoo.com.tw', '', '權利停權中', '存', 1, '民國112年除籍，家屬已辦理過戶\r\n生存狀態, 狀態, 2026-06-14 08:44:47 修改\r\n', '2026-06-17 20:02:27', '2 李美麗'),
(3, '2020-08-22', 'OLD003', '3', 3, 27, 2, '李大山', '男', 'B187654321', '1960-11-30', '台中市', '專科', '營造業負責人', '台中市西屯區台灣大道三段99號', '407-610', '0935-111222', '04-23114567', '04-24556677', 'dashan.lee@gmail.com', '李文山', '正常派下員', '存', 1, '目前失聯中，書面信件遭退件', NULL, NULL),
(4, '2022-01-10', 'OLD004', '4', 11, 28, 3, '李志強', '男', 'E112233445', '1968-05-14', '高雄市', '碩士', '大學副教授', '高雄市苓雅區四維三路2號', '802-721', '0910-555666', '07-3311223', '07-7172000', 'cq.lee@univ.edu.tw', '李大山', '正常派下員', '存', 1, '現任地方宗親會理事', NULL, NULL),
(5, '2023-05-18', 'OLD005', '5', 9, 29, 3, '李秋香', '女', 'D223344556', '1979-09-08', '台南市', '大學', '連鎖餐飲店長', '台南市安平區安平路100號', '708-005', '0988-777888', '06-2288999', '06-2211223', 'chsh.lee@gmail.com', NULL, '正常派下員', '存', 1, '聯絡電話有更新', NULL, NULL),
(6, '2020-02-11', 'OLD006', '6', 6, 29, 3, '李文雄', '男', 'H100223344', '1925-01-22', '桃園市', '初中', '農業(退休)', '桃園市桃園區中正路300號', '330-001', '0911-333444', '03-3322111', NULL, 'wenxiong@yahoo.com.tw', NULL, '喪失權利', '亡', 0, '派下權已由長子繼承', '2026-06-17 20:26:17', NULL),
(7, '2021-11-05', 'OLD007', '7', 10, 27, 3, '李美玲', '女', 'O221144332', '1983-04-17', '新竹市', '碩士', '竹科外商工程師', '新竹市東區光復路二段101號', '300-044', '0972-666777', '03-5712233', NULL, 'meiling.l@techcorp.com', '李志強', '正常派下員', '亡', 0, '出境國外未歸，狀態待確認', '2026-06-17 20:29:46', NULL),
(8, '2022-07-07', 'OLD008', '8', 8, 27, 1, '李建國', '男', 'N122335544', '1972-12-05', '彰化縣', '大學', '自營傳統零售', '彰化縣彰化市中山路二段500號', '500-015', '0928-333221', '04-7223344', '04-7255566', 'jianguo.lee@gmail.com', '李文雄', '正常派下員', '存', 1, '每年固定出席大會', NULL, NULL),
(9, '2024-02-14', 'OLD009', '9', 7, 30, 1, '李淑珍', '女', 'T221199887', '1988-07-11', '屏東縣', '大學', '小學教師', '屏東縣屏東市自由路80號', '900-002', '0952-444555', '08-7321122', '08-7334455', 'shujen88@gmail.com', NULL, '正常派下員', '存', 1, '無', NULL, NULL),
(10, '2020-05-20', 'OLD010', '10', 3, 28, 3, '李文山', '男', 'I100998877', '1935-10-02', '嘉義市', '高中', '公務員(退休)', '嘉義市西區中山路200號', '600-001', '0919-222111', '05-2233445', NULL, 'wenshan@gmail.com', NULL, '正常派下員', '存', 1, '除籍資料已備查', NULL, NULL),
(11, '2023-09-01', 'OLD011', '11', 9, 27, 3, '李秀英', '女', 'C221133557', '1981-02-19', '基隆市', '專科', '美容美髮設計師', '基隆市仁愛區仁一路15號', '200-011', '0932-114477', '02-24223344', '02-24255566', 'xiuying.lee@gmail.com', '李淑珍', '保留權利', '存', 1, '需補件親屬關係證明', NULL, NULL),
(12, '2022-08-19', 'OLD012', '12', 11, 28, 3, '李建華', '男', 'M122334499', '1978-06-26', '南投縣', '大學', '南投縣政府約聘人員', '南投縣南投市中興路60號', '540-008', '0915-666888', '049-2223344', '049-2251122', 'jianhua@nantou.gov.tw', '李建國', '正常派下員', '存', 1, '無', NULL, NULL),
(13, '2024-06-30', 'OLD013', '13', 2, 28, 3, '李家豪', '男', 'P122446688', '1991-09-15', '雲林縣', '碩士', '自由程式設計師', '雲林縣斗六市大學路三段1號', '640-001', '0963-123456', '05-5321122', NULL, 'jiahao.lee@gmail.com', NULL, '正常派下員', '存', 1, '戶籍有變動，尚在查證中', NULL, NULL),
(14, '2023-11-12', 'OLD014', '14', 5, 30, 1, '李美麗', '女', 'K221133445', '1984-11-04', '苗栗縣', '大學', '苗栗縣立醫院護理師', '苗栗縣苗栗市府前路1號', '360-453', '0975-999000', '037-321122', '037-357111', 'meili.ml@gmail.com', '李建華', '正常派下員', '存', 1, '主要通訊聯絡人\r\n派下員狀態, 生存狀態, 狀態, 派下權, 2026-06-14 08:50:50 修改\r\n', '2026-06-14 08:50:50', '14 李美麗'),
(15, '2020-04-05', 'OLD015', '15', 11, 28, 1, '李文傑', '男', 'G100554433', '1943-08-18', '宜蘭縣', '初中', '造船廠技工(退休)', '宜蘭縣宜蘭市神農路一段1號', '260-007', '0918-555444', '03-9321122', NULL, 'wenjie.lee@yahoo.com.tw', NULL, '喪失權利', '亡', 0, '生前已聲明拋棄衍生權利', NULL, NULL),
(16, '2024-01-20', 'OLD016', '16', 8, 29, 1, '李佩芬', '女', 'U221144556', '1989-05-29', '花蓮縣', '大學', '花蓮在地民宿經營者', '花蓮縣花蓮市府前路10號', '970-006', '0926-888999', '03-8221122', '03-8234455', 'peifen.hualien@gmail.com', '李秋香', '正常派下員', '存', 1, '無', NULL, NULL),
(17, '2022-03-14', 'OLD017', '17', 4, 30, 2, '李建智', '男', 'J122336655', '1980-04-13', '新竹縣', '碩士', '工研院副研究員', '新竹縣竹北市光明六路10號', '302-049', '0912-777666', '03-5511223', '03-5911111', 'jianzhi.lee@itri.org.tw', '李美玲', '正常派下員', '存', 1, '無', NULL, NULL),
(18, '2020-09-09', 'OLD018', '18', 5, 30, 3, '李淑惠', '女', 'H221144553', '1951-12-21', '桃園市', '高中', '紡織廠領班(退休)', '桃園市中壢區延平路200號', '320-418', '0933-444555', '03-4221122', NULL, 'shuhui@yahoo.com.tw', NULL, '正常派下員於民國114年歿', '亡', 0, '民國114年歿', NULL, NULL),
(19, '2023-04-18', 'OLD019', '19', 10, 29, 2, '李志遠', '男', 'K122445566', '1987-02-07', '苗栗縣', '大學', '房地產經紀人', '苗栗縣頭份市中央路50號', '351-540', '0955-333444', '037-661122', '037-678899', 'zhiyuan.lee@gmail.com', '李美麗', '正常派下員', '存', 1, '無', NULL, NULL),
(20, '2025-01-05', 'OLD020', '20', 10, 30, 3, '李家銘', '男', 'A129988776', '1995-10-24', '台北市', '大學', '銀行理財專員', '台北市大安區信義路四段100號', '106-815', '0987-111222', '02-27011234', '02-23495555', 'jiaming.lee@chb.com.tw', '李明輝', '正常派下員', '存', 1, '青年代表成員', NULL, NULL),
(21, '2022-05-11', 'OLD021', '21', 8, 27, 3, '李麗君', '女', 'F221144778', '1983-06-03', '新北市', '專科', '會計事務所助理', '新北市三重區重新路三段1號', '241-414', '0911-777888', '02-29811223', NULL, 'lijun.lee@yahoo.com.tw', NULL, '保留權利', '存', 1, '目前地址查無此人', NULL, NULL),
(22, '2021-06-17', 'OLD022', '22', 5, 30, 3, '李志誠', '男', 'B122334455', '1970-07-19', '台中市', '大學', '機械加工廠廠長', '台中市豐原區中正路100號', '420-558', '0936-222333', '04-25223344', '04-25255566', 'zhicheng.lee@gmail.com', '李大山', '正常派下員', '存', 1, '無', NULL, NULL),
(23, '2020-01-15', 'OLD023', '23', 10, 28, 2, '李秀蘭', '女', 'M200112233', '1927-01-15', '南投縣', '小學', '傳統市場攤商(退休)', '南投縣草屯鎮中正路500號', '542-536', '0910-222333', '049-2331122', NULL, 'xiulan@gmail.com', NULL, '正常派下員', '存', 1, '已故資深會員', NULL, NULL),
(24, '2023-10-02', 'OLD024', '24', 6, 30, 3, '李建勳', '男', 'N122558899', '1986-09-02', '彰化縣', '大學', '彰化銀行辦事員', '彰化縣鹿港鎮中山路150號', '505-004', '0978-444555', '04-7771122', '04-7754433', 'jianxun.lee@chb.com.tw', '李建國', '正常派下員', '存', 1, '無', NULL, NULL),
(25, '2024-05-06', 'OLD025', '25', 8, 29, 3, '李美華', '女', 'P221155998', '1992-03-14', '雲林縣', '大學', '網頁視覺設計師', '雲林縣北港鎮中山路80號', '651-003', '0965-333221', '05-7831122', NULL, 'meihua.lee@gmail.com', '李家豪', '正常派下員', '存', 1, '無', NULL, NULL),
(26, '2020-06-25', 'OLD026', '26', 3, 28, 3, '李志明', '男', 'Q100223344', '1944-11-08', '嘉義縣', '高中', '客運司機(退休)', '嘉義縣太保市太保一路1號', '612-001', '0920-111222', '05-3621122', NULL, 'zhiming.lee@yahoo.com.tw', NULL, '喪失權利', '亡', 0, '後代已移居海外未辦繼承', NULL, NULL),
(27, '2023-08-14', 'OLD027', '27', 2, 30, 1, '李英傑', '男', 'E122339988', '1988-04-23', '高雄市', '碩士', '軟體後端工程師', '高雄市鳳山區光遠路50號', '830-001', '0919-888777', '07-7461122', '07-5361122', 'yingjie.lee@gmail.com', '李志強', '正常派下員', '存', 1, '無', NULL, NULL),
(28, '2022-12-01', 'OLD028', '28', 5, 27, 2, '李淑芬', '女', 'T221144665', '1981-08-31', '屏東縣', '大學', '連鎖藥局藥師', '屏東縣潮州鎮延平路10號', '920-007', '0937-555444', '08-7891122', '08-7895566', 'shufen.lee@gmail.com', '李淑珍', '正常派下員', '存', 1, '無', NULL, NULL),
(29, '2021-02-28', 'OLD029', '29', 2, 28, 1, '李大同', '男', 'V100334455', '1963-05-12', '台東縣', '專科', '貨運物流司機', '台東縣台東市中山路100號', '950-004', '0912-888999', '089-321122', NULL, 'datong.lee@yahoo.com.tw', NULL, '正常派下員', '存', 1, '失蹤多年，待法院宣告', NULL, NULL),
(30, '2025-03-12', 'OLD030', '30', 3, 27, 2, '李麗婷', '女', 'X221133445', '1994-12-01', '澎湖縣', '大學', '旅行社社群企劃', '澎湖縣馬公市中正路20號', '880-003', '0982-111333', '06-9271122', '06-9264455', 'leting.lee@gmail.com', NULL, '正常派下員', '存', 1, '青年代表成員', NULL, NULL),
(31, '2023-02-19', 'OLD031', '31', 3, 28, 3, '李志明', '男', 'B122445599', '1982-10-14', '台中市', '大學', '汽車維修技師', '台中市北區進化北路200號', '404-541', '0905-123456', '04-22334455', '04-22336677', 'zhiming31@gmail.com', '李志誠', '正常派下員', '存', 1, '近期更換手機號碼', NULL, NULL),
(32, '2020-07-04', 'OLD032', '32', 3, 29, 0, '李阿鑾', '女', 'N200554433', '1929-04-05', '彰化縣', '不詳', '家管', '彰化縣鹿港鎮成功路35號', '505-010', '0911-000111', '04-7781122', NULL, NULL, NULL, '正常派下員', '存', 1, '派下權由其次子辦理繼承中', NULL, NULL),
(33, '2022-05-05', 'OLD033', '33', 8, 28, 2, '李建宏', '男', 'A122445566', '1977-12-25', '台北市', '碩士', '金控公司部門主管', '台北市信義區松仁路88號', '110-013', '0935-777888', '02-27201122', '02-27558888', 'jianhong.lee@fubon.com', '李明輝', '正常派下員', '存', 1, '大會重要幹部', NULL, NULL),
(34, '2021-10-10', 'OLD034', '34', 10, 27, 3, '李美玲', '女', 'F222334455', '1965-07-19', '新北市', '專科', '百貨專櫃人員', '新北市淡水區中正路12-1號', '251-016', '0918-222333', '02-26211122', NULL, 'meiling34@gmail.com', NULL, '保留權利', '存', 1, '通知信件查無此人', NULL, NULL),
(35, '2024-04-01', 'OLD035', '35', 6, 30, 1, '李國華', '男', 'E122556644', '1985-02-02', '高雄市', '大學', '中鋼基層領班', '高雄市三民區九如一路50號', '807-380', '0963-777222', '07-3811122', '07-8021111', 'guohua.lee@csc.com.tw', '李英傑', '正常派下員', '存', 1, '無', NULL, NULL),
(36, '2020-11-20', 'OLD036', '36', 11, 29, 3, '李金木', '男', 'H100335544', '1938-09-11', '桃園市', '高中', '木工裝潢師傅(退休)', '桃園市八德區介壽路二段120號', '334-016', '0921-333222', '03-3651122', NULL, 'jinmu.lee@gmail.com', NULL, '喪失權利', '亡', 0, '民國113年歿，無直系血親繼承', NULL, NULL),
(37, '2024-07-15', 'OLD037', '37', 5, 27, 2, '李淑芬', '女', 'J221155443', '1990-06-30', '新竹縣', '大學', '網拍服飾店長', '新竹縣竹東鎮長春路三段90號', '310-008', '0975-666333', '03-5961122', NULL, 'shufen37@gmail.com', '李建智', '正常派下員', '存', 1, '無', NULL, NULL),
(38, '2022-09-03', 'OLD038', '李志豪', 5, 28, 3, '38', '男', 'D122336655', '1973-11-08', '台南市', '專科', '室內設計師', '台南市中西區府前路二段300號', '700-019', '0932-555666', '06-2211122', '06-2233445', 'zhihao.lee@gmail.com', '李秋香', '正常派下員', '存', 1, '定期繳納宗親會費\r\n生存狀態, 狀態, 2026-06-14 08:34:17 修改\r\n', '2026-06-14 08:34:17', '38 李志豪'),
(39, '2020-08-08', 'OLD039', '李寶珠', 6, 25, 1, '39', '女', 'C200113344', '1941-03-18', '基隆市', '高中', '成衣廠女工(退休)', '基隆市中正區正濱路45號', '202-005', '0910-777666', '02-24281122', '02-23079747', 'baozhu@yahoo.com.tw', '李明輝', '正常派下員', '存', 1, '已除籍，證明文件備查\r\n派下員狀態, 生存狀態, 2026-06-13 23:47:54 修改\r\n生存狀態, 狀態, 2026-06-14 08:39:18 修改\r\n', '2026-06-14 08:39:18', '39 李寶珠'),
(40, '2021-03-25', 'OLD040', '40', 11, 28, 3, '李水源', '男', 'M122665544', '1969-05-17', '南投縣', '大學', '埔里在地果農', '南投縣埔里鎮中山路二段100號', '545-001', '0956-111222', '049-2981122', '', 'shuiyuan@gmail.com', '李建華', '正常派下員', '存', 1, '生存狀態, 狀態, 2026-06-14 07:59:02 修改\r\n', '2026-06-14 07:59:02', '40 李水源'),
(41, '2023-01-12', 'OLD041', '41', 2, 28, 3, '李家豪', '男', 'G122554433', '1988-08-08', '宜蘭縣', '碩士', '貿易公司國外業務', '宜蘭縣羅東鎮純精路一段50號', '265-015', '0912-000999', '03-9541122', '02-25012345', 'jiahao41@gmail.com', NULL, '保留權利', '存', 1, '出國工作，家屬代為聯絡', NULL, NULL),
(42, '2023-06-20', 'OLD042', '42', 5, 28, 1, '李佩君', '女', 'K222554411', '1983-01-12', '苗栗縣', '大學', '竹南科學園區HR', '苗栗縣竹南鎮博愛街15號', '350-001', '0988-111333', '037-471122', '037-586677', 'peijun.lee@tech.com', '李美麗', '正常派下員', '存', 1, '青年委員會代表', NULL, NULL),
(43, '2022-11-11', 'OLD043', '43', 2, 30, 2, '李俊傑', '男', 'P122554466', '1976-04-26', '雲林縣', '大學', '西螺果菜市場批發商', '雲林縣西螺鎮延平路60號', '648-005', '0928-666555', '05-5861122', NULL, 'junjie43@gmail.com', '李家豪', '正常派下員', '存', 1, '無', NULL, NULL),
(44, '2020-05-05', 'OLD044', '44', 9, 29, 1, '李火旺', '男', 'Q100112233', '1931-12-03', '嘉義縣', '小學', '碾米廠老闆(退休)', '嘉義縣民雄鄉建國路二段10號', '621-411', '0919-888999', '05-2261122', NULL, 'huowang@yahoo.com.tw', NULL, '正常派下員(民國111年壽終正寢)', '亡', 0, '民國111年壽終正寢', NULL, NULL),
(45, '2024-03-18', 'OLD045', '45', 11, 29, 2, '李雅婷', '女', 'T222445566', '1992-09-14', '屏東縣', '大學', '墾丁飯店行銷專員', '屏東縣恆春鎮中山路200號', '946-003', '0972-333444', '08-8861122', '08-8862345', 'yating.kenting@gmail.com', '李淑珍', '正常派下員', '存', 1, '通訊地址有變更', NULL, NULL),
(46, '2021-07-07', 'OLD046', '46', 9, 28, 3, '李文德', '男', 'U122334455', '1962-02-28', '花蓮縣', '專科', '水電工程承包商', '花蓮縣吉安鄉中央路三段50號', '973-013', '0933-111222', '03-8521122', '03-8534455', 'wende.lee@gmail.com', '李佩芬', '正常派下員', '存', 1, '無', NULL, NULL),
(47, '2022-02-14', 'OLD047', '47', 8, 29, 3, '李美華', '女', 'V221155443', '1971-10-05', '台東縣', '大學', '保險業務員', '台東縣卑南鄉更生北路80號', '954-001', '0911-555666', '089-221122', NULL, 'meihua47@gmail.com', NULL, '正常派下員', '存', 1, '失聯會員，正由鄰里長協助尋找', NULL, NULL),
(48, '2022-10-10', 'OLD048', '48', 11, 28, 3, '李志強', '男', 'N122778899', '1980-07-22', '彰化縣', '大學', '食品工廠生管人員', '彰化縣員林市大同路二段150號', '510-008', '0937-222888', '04-7112233', '04-7124455', 'zhiqiang48@gmail.com', '李建勳', '正常派下員', '存', 1, '無', NULL, NULL),
(49, '2020-04-20', 'OLD049', '49', 6, 28, 3, '李春枝', '女', 'M200334455', '1936-05-30', '南投縣', '初中', '茶農(退休)', '南投縣竹山鎮大明路50號', '557-005', '0920-555666', '049-2641122', NULL, 'chunzhi@gmail.com', NULL, '正常派下員', '存', 1, '已故資深會員', NULL, NULL),
(50, '2023-12-25', 'OLD050', '50', 7, 28, 2, '李家明', '男', 'O122334455', '1987-11-11', '新竹市', '碩士', '新竹科學園區工程師', '新竹市北區東大路二段300號', '300-058', '0915-333111', '03-5311122', '03-5771122', 'jiaming.o@tsmc.com', '李美玲', '正常派下員', '存', 1, '無', NULL, NULL),
(51, '2025-02-02', 'OLD051', '51', 8, 28, 2, '李雅如', '女', 'A229955443', '1995-03-03', '台北市', '大學', '廣告行銷文案', '台北市松山區民生東路五段10號', '105-011', '0981-222333', '02-27611234', NULL, 'yaru.lee@gmail.com', '李家銘', '正常派下員', '存', 1, '無', NULL, NULL),
(52, '2020-03-03', 'OLD052', '52', 8, 29, 3, '李天送', '男', 'E100112233', '1928-08-18', '高雄市', '小學', '營造業工人(退休)', '高雄市鳳山區五甲二路100號', '830-032', '0910-444555', '07-7411122', NULL, 'tiansong@yahoo.com.tw', NULL, '喪失權利', '亡', 0, '已由代書辦理繼承登錄', NULL, NULL),
(53, '2022-08-01', 'OLD053', '53', 8, 28, 2, '李建宏', '男', 'H122665544', '1979-06-14', '桃園市', '專科', '物流中心組長', '桃園市平鎮區延平路二段40號', '324-411', '0936-777111', '03-4611122', NULL, 'jianhong53@gmail.com', NULL, '保留權利', '存', 1, '戶籍地查無此人', NULL, NULL),
(54, '2021-05-14', 'OLD054', '54', 9, 27, 3, '李秀英', '女', 'K221155667', '1966-12-21', '苗栗縣', '高中', '超市收銀員', '苗栗縣後龍鎮成功路88號', '356-004', '0912-444333', '037-721122', NULL, 'xiuying54@gmail.com', '李美麗', '正常派下員', '存', 1, '無', NULL, NULL),
(55, '2023-07-19', 'OLD055', '55', 10, 29, 2, '李志遠', '男', 'F122554499', '1984-01-29', '新北市', '大學', '連鎖超商加盟主', '新北市板橋區文化路一段200號', '220-412', '0952-666555', '02-29611223', '02-29644556', 'zhiyuan55@gmail.com', '李麗君', '正常派下員', '存', 1, '無', NULL, NULL),
(56, '2020-06-01', 'OLD056', '56', 10, 30, 2, '李阿土', '男', 'I100445566', '1933-10-02', '嘉義市', '不詳', '自營農業(退休)', '嘉義市東區垂楊路50號', '600-006', '0918-111222', '05-2241122', NULL, NULL, NULL, '正常派下員', '存', 1, '民國110年過世', NULL, NULL),
(57, '2024-11-01', 'OLD057', '57', 10, 27, 3, '李美玲', '女', 'J222445566', '1991-05-12', '新竹縣', '大學', '幼兒園教師', '新竹縣竹北市縣政九路10號', '302-044', '0978-111000', '03-5561122', '03-5563344', 'meiling57@gmail.com', '李建智', '正常派下員', '存', 1, '無', NULL, NULL),
(58, '2022-04-30', 'OLD058', '58', 10, 29, 1, '李冠宇', '男', 'P122774433', '1978-04-03', '雲林縣', '碩士', '雲林縣政府科員', '雲林縣虎尾鎮林森路二段15號', '632-008', '0933-999888', '05-6321122', '05-6332211', 'guanyu.lee@yunlin.gov.tw', '李美華', '正常派下員', '存', 1, '無', NULL, NULL),
(59, '2021-09-25', 'OLD059', '59', 5, 30, 3, '李淑惠', '女', 'C222445511', '1982-09-24', '基隆市', '專科', '通訊行門市人員', '基隆市信義區信一路120號', '201-013', '0926-444555', '02-24211122', NULL, 'shuhui59@gmail.com', NULL, '正常派下員', '存', 1, '通訊處更動未申報', NULL, NULL),
(60, '2021-01-15', 'OLD060', '60', 2, 28, 1, '李大同', '男', 'X100224433', '1960-03-31', '澎湖縣', '高中', '遠洋漁業(退休)', '澎湖縣馬公市新生路80號', '880-004', '0910-999888', '06-9211122', NULL, 'datong60@yahoo.com.tw', NULL, '正常派下員', '存', 1, '每年固定委託代表出席', NULL, NULL);

-- --------------------------------------------------------

--
-- 替換檢視表以便查看 `member_family_view`
-- (請參考以下實際畫面)
--
CREATE TABLE `member_family_view` (
`member_id` int(11)
,`new_member` varchar(50)
,`name` varchar(50)
,`gender` varchar(10)
,`birthday` date
,`placeOfBirth` varchar(100)
,`address` varchar(255)
,`living_status` enum('存','亡','未知')
,`father` varchar(50)
,`mother` varchar(50)
,`adoptiveFather` varchar(50)
,`fosterMother` varchar(50)
,`spouse` varchar(50)
,`brothers` text
,`sisters` text
,`FamilySituation` text
,`son1` text
,`son2` text
,`son3` text
,`son4` text
,`son5` text
,`son6` text
,`son7` text
,`son8` text
,`son9` text
,`daughter1` text
,`daughter2` text
,`daughter3` text
,`daughter4` text
,`daughter5` text
,`daughter6` text
,`daughter7` text
,`daughter8` text
,`daughter9` text
,`member_remarks` text
,`family_remarks` text
);

-- --------------------------------------------------------

--
-- 資料表結構 `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL COMMENT '訊息流水號',
  `file_id` int(11) DEFAULT NULL COMMENT '關聯到 files 資料表的 id',
  `from_id` varchar(100) DEFAULT NULL COMMENT '發信者編號 (new_member)',
  `from_name` varchar(255) DEFAULT NULL COMMENT '發信者姓名',
  `to_type` enum('user','generation','houses','role') NOT NULL COMMENT '收件種類 (個人/世代/大房/角色)',
  `to_target` varchar(255) NOT NULL COMMENT '收件目標識別值 (如會員號、admin、或第幾代)',
  `is_read` tinyint(1) DEFAULT 0 COMMENT '0=未讀, 1=已讀',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '留言日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='祈願信箱發送紀錄表';

--
-- 傾印資料表的資料 `messages`
--

INSERT INTO `messages` (`id`, `file_id`, `from_id`, `from_name`, `to_type`, `to_target`, `is_read`, `created_at`) VALUES
(0, 87, '李美麗', '2', 'user', '5', 1, '2026-06-15 18:18:10'),
(0, 87, '李美麗', '2', 'role', 'admin', 1, '2026-06-15 18:18:10'),
(0, 87, '李美麗', '2', 'role', 'user', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'role', 'clan', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'generation', '6', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'generation', '7', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'generation', '8', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'generation', '9', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'generation', '10', 1, '2026-06-15 18:18:11'),
(0, 87, '李美麗', '2', 'generation', '11', 1, '2026-06-15 18:18:11'),
(0, 88, '李美麗', '2', 'user', '26', 1, '2026-06-15 18:23:34'),
(0, 88, '李美麗', '2', 'role', 'admin', 1, '2026-06-15 18:23:34'),
(0, 88, '李美麗', '2', 'role', 'user', 1, '2026-06-15 18:23:34'),
(0, 88, '李美麗', '2', 'role', 'clan', 1, '2026-06-15 18:23:34'),
(0, 89, '李美麗', '2', 'user', '6', 1, '2026-06-15 18:47:18'),
(0, 89, '李美麗', '2', 'role', 'admin', 1, '2026-06-15 18:47:18'),
(0, 89, '李美麗', '2', 'role', 'user', 1, '2026-06-15 18:47:18'),
(0, 89, '李美麗', '2', 'role', 'clan', 1, '2026-06-15 18:47:18'),
(0, 90, '李美麗', '2', 'user', '15', 0, '2026-06-15 18:50:18'),
(0, 90, '李美麗', '2', 'role', 'user', 0, '2026-06-15 18:50:18');

-- --------------------------------------------------------

--
-- 資料表結構 `village-chief`
--

CREATE TABLE `village-chief` (
  `id` int(11) NOT NULL COMMENT '編號',
  `city` varchar(50) NOT NULL COMMENT '縣市',
  `district` varchar(50) NOT NULL COMMENT '區別',
  `village` varchar(50) NOT NULL COMMENT '里別',
  `chief_name` varchar(50) NOT NULL COMMENT '里長姓名',
  `office_address` varchar(255) NOT NULL COMMENT '里辦公處地址',
  `office_phone` varchar(20) DEFAULT NULL COMMENT '里辦公室電話',
  `chief_mobile` varchar(20) DEFAULT NULL COMMENT '里長手機'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `village-chief`
--

INSERT INTO `village-chief` (`id`, `city`, `district`, `village`, `chief_name`, `office_address`, `office_phone`, `chief_mobile`) VALUES
(1, '台中市', '大甲區', '朝陽里', '林立偉', '臺中市大甲區朝陽里１３鄰新政路２之８號', '04-26860369', NULL),
(2, '台中市', '大甲區', '大甲里', '黃勝裕', '臺中市大甲區大甲里１５鄰順天路１３２號', '04-26881034', NULL),
(3, '台中市', '大甲區', '順天里', '陳威宇', '臺中市大甲區順天里１８鄰光明路２０４號', '04-26872881', NULL),
(4, '台中市', '大甲區', '孔門里', '吳柏坤', '臺中市大甲區孔門里６鄰文武路１５２號', '04-26879410', NULL),
(5, '台中市', '大甲區', '平安里', '周寶惜', '臺中市大甲區平安里８鄰三民路１４５號', '04-26870470', NULL),
(6, '台中市', '大甲區', '庄美里', '魏李嫌', '臺中市大甲區庄美里９鄰經國路３７５巷36號', '04-26881882', NULL),
(7, '台中市', '大甲區', '新美里', '王生源', '臺中市大甲區新美里１４鄰光明路２８１巷２６號', '04-26882758', NULL),
(8, '台中市', '大甲區', '岷山里', '林清良', '臺中市大甲區岷山里１１鄰中山路一段９０８巷１０之６１號', '04-26882243', NULL),
(9, '台中市', '大甲區', '中山里', '廖嘉佐', '臺中市大甲區中山里４鄰甲后路5段391號', '04-26765578', NULL),
(10, '台中市', '大甲區', '南陽里', '黃火塗', '臺中市大甲區南陽里９鄰水源路２５號', '04-26870303', NULL),
(11, '台中市', '大甲區', '薰風里', '王秋芬', '臺中市大甲區薰風里９鄰中山路一段８２３巷２３號', '04-26870316', NULL),
(12, '台中市', '大甲區', '義和里', '洪黃素貞', '臺中市大甲區義和里１５鄰重義三路１３巷５６號', '04-26881088', NULL),
(13, '台中市', '大甲區', '武陵里', '方如玉', '臺中市大甲區武陵里９鄰文曲路１１０巷５號', '04-26715088', NULL),
(14, '台中市', '大甲區', '文曲里', '林寶彩', '臺中市大甲區文曲里２鄰東安路５０１巷１４號', '04-26711375', NULL),
(15, '台中市', '大甲區', '武曲里', '蘇月鳳', '臺中市大甲區武曲里１０鄰大安港路１８８號', '04-26875071', NULL),
(16, '台中市', '大甲區', '文武里', '葉淑惠', '臺中市大甲區文武里１４鄰德興路５２號', '04-26873110', NULL),
(17, '台中市', '大甲區', '奉化里', '白見發', '臺中市大甲區奉化里５鄰賢仁路５９號', '04-26879599', NULL),
(18, '台中市', '大甲區', '德化里', '李柄杉', '臺中市大甲區德化里８鄰南北四路１８０巷１５號', '04-26805820', NULL),
(19, '台中市', '大甲區', '江南里', '李文登', '臺中市大甲區江南里７鄰東西五路一段2號', '04-26868037', NULL),
(20, '台中市', '大甲區', '頂店里', '黃安鎮', '臺中市大甲區頂店里２１鄰雁門路３３0號', '04-26875918', NULL),
(21, '台中市', '大甲區', '太白里', '游招滿', '臺中市大甲區太白里５鄰通天路７１號之２', '04-26820229', NULL),
(22, '台中市', '大甲區', '孟春里', '黃湘榕', '臺中市大甲區孟春里１鄰中山路２段５６號', '04-26816090', NULL),
(23, '台中市', '大甲區', '幸福里', '林晨彬', '臺中市大甲區幸福里４鄰長安路３７號', '04-26812673', NULL),
(24, '台中市', '大甲區', '日南里', '邱大豪', '臺中市大甲區日南里１５鄰中山路二段９４０之１６號', '04-26816286', NULL),
(25, '台中市', '大甲區', '龍泉里', '陳廷旺', '臺中市大甲區龍泉里６鄰順帆一街１８５號', '04-26813675', NULL),
(26, '台中市', '大甲區', '西岐里', '羅永宗', '臺中市大甲區西岐里７鄰順帆路110號', '04-26818147', NULL),
(27, '台中市', '大甲區', '銅安里', '林玉生', '臺中市大甲區銅安里７鄰長壽路56-6號', '04-26811478', NULL),
(28, '台中市', '大甲區', '福德里', '莊金波', '臺中市大甲區福德里５鄰順帆路２０６號', '04-26815842', NULL),
(29, '台中市', '大甲區', '建興里', '蔡明河', '臺中市大甲區建興里３鄰臨江路９１巷１２號', '04-26811422', NULL),
(30, '台北市', '松山區', '莊敬里', '周政諭', '105075臺北市松山區撫遠街394-1號', '02-2760-6793', '0920309000'),
(31, '台北市', '松山區', '東榮里', '鄭玉梅', '105010臺北市松山區民生東路五段69巷3號', '02-2767-6931', '0933902948'),
(32, '台北市', '松山區', '三民里', '謝翠鈴', '105013臺北市松山區民生東路五段163-1號8樓', '02-2767-1370', '0910380103'),
(33, '台北市', '松山區', '新益里', '王敏茹', '105073臺北市松山區民權東路五段102號對面(新益臨時里民活動場所）', '02-87877641', '0928261503'),
(34, '台北市', '松山區', '富錦里', '李煥中', '105070臺北市松山區富錦街515號1樓', '02-2763-8234', '0912546233'),
(35, '台北市', '松山區', '新東里', '華國雄', '105067臺北市松山區延壽街3號1樓', '02-2766-4552', '0928881898'),
(36, '台北市', '松山區', '富泰里', '吳學毅', '105064臺北市松山區新東街1巷15號', '02-2748-0836', '0910270087'),
(37, '台北市', '松山區', '介壽里', '蔡錦文', '105015臺北市松山區介壽里民生東路五段36巷4弄6號1樓', '02-2760-8616', '0921120401'),
(38, '台北市', '松山區', '精忠里', '賴美旭', '105412臺北市松山區民生東路四段133號', '02-2712-6798', '0935934403'),
(39, '台北市', '松山區', '東光里', '許秀雲', '105032臺北市松山區南京東路五段123巷41號', '02-2762-5716', '0933727761'),
(40, '台北市', '松山區', '龍田里', '黃淑芬', '105026臺北市松山區光復北路230巷21號地下一層', '02-2547-4135', '0933764244'),
(41, '台北市', '松山區', '東昌里', '陳志明', '105016臺北市松山區民生東路四段112巷7弄24號', '02-27154357', '0932321795'),
(42, '台北市', '松山區', '東勢里', '王叔珍', '臺北市松山區南京東路四段133巷8弄4號', '02-27170369', '0937545333'),
(43, '台北市', '松山區', '中華里', '游吉興', '臺北市松山區敦化北路145巷23弄1號', '27195969', '0910837937'),
(44, '台北市', '松山區', '民有里', '林國瑞', '105004臺北市松山區民權東路三段140巷15號4樓', '27128037', '0931380505'),
(45, '台北市', '松山區', '民福里', '李麗容', '臺北市松山區民權東路三段103巷5弄5號', '02-27160109', '0936-344488'),
(46, '台北市', '松山區', '松基里', '黃建賓', '105021臺北市松山區長春路339巷2號', '02-2716-0515', '0968393627'),
(47, '台北市', '松山區', '慈祐里', '李祖民', '105057臺北市松山區市民大道六段9號', '02-27657700', '0937838889'),
(48, '台北市', '松山區', '安平里', '洪温滿', '臺北市松山區南京東路5段291巷44弄23-1號5樓', '02-2766-0988', '0910236723'),
(49, '台北市', '松山區', '鵬程里', '許顏建', '105068臺北市松山區健康路401號', '27562668', '0933918609'),
(50, '台北市', '松山區', '自強里', '李台華', '105028臺北市松山區延壽街330巷17弄2號1樓', '02-2528-3924', '0910282299'),
(51, '台北市', '松山區', '吉祥里', '陳永昌', '105052臺北市松山區八德路4段245巷52弄5號', '02-2762-7830', '0933727821'),
(52, '台北市', '松山區', '新聚里', '郭秀鳳', '105055臺北市松山區南京東路五段250巷9之7號', '27685997', '0972212997'),
(53, '台北市', '松山區', '復盛里', '張晉維', '105051臺北市松山區市民大道五段99之3號', '02-2528-6727', '0919322079'),
(54, '台北市', '松山區', '中正里', '洪樸陽', '105040臺北市松山區長安東路二段229號12樓', '02-27717533', '0937031518'),
(55, '台北市', '松山區', '中崙里', '張陳碧雲', '臺北市松山區八德路二段410巷16弄6號', '02-27518291', '0933727832'),
(56, '台北市', '松山區', '美仁里', '林志創', '105037臺北市松山區八德路三段99巷10號1樓', '02-2577-9609', '0933925258'),
(57, '台北市', '松山區', '吉仁里', '李全長', '105046臺北市松山區八德路三段76號3樓之3', '02-2578-3233', '0977252805'),
(58, '台北市', '松山區', '敦化里', '蘇榮文', '105044臺北市松山區八德路三段12巷51弄30號1樓', '02-25798196', '0930793998'),
(59, '台北市', '松山區', '復源里', '温福義', '臺北市松山區八德路3段158巷12號', '02-25787766', '0932158311'),
(60, '台北市', '松山區', '復建里', '林坤信', '105048臺北市松山區光復南路66巷34號', '02-2577-3067', '0916527940'),
(61, '台北市', '松山區', '復勢里', '徐麗雲', '105035臺北市松山區光復北路120巷16號', '02-2578-5507', '0910183724'),
(62, '台北市', '松山區', '福成里', '葉佐良', '臺北市松山區敦化南路1段100巷5弄4號', '02-27312851', '0958788130'),
(63, '台北市', '信義區', '西村里', '鄭伊恬(代理)', '110052臺北市信義區區基隆路1段364巷22號', '02-2758-6396', '0975-171-571'),
(64, '台北市', '信義區', '正和里', '董玲娥', '110051臺北市信義區區仁愛路4段452巷3號', '2758-2836', '0939-415-358'),
(65, '台北市', '信義區', '興隆里', '吉娃斯‧吉果', '110052臺北市信義區區基隆路1段350之11號', '02-27583255', '0910364392'),
(66, '台北市', '信義區', '中興里', '趙惠美', '110007臺北市信義區區嘉興街58號', '02-2758-6300', '0936-055-159'),
(67, '台北市', '信義區', '新仁里', '吳建德', '110055臺北市信義區區忠孝東路4段553巷22弄21號', '02-2764-8030', '0987-296-720'),
(68, '台北市', '信義區', '興雅里', '林素琴', '110058臺北市信義區區松隆路26號', '02-2768-8328', '0910-168-085'),
(69, '台北市', '信義區', '敦厚里', '方聰杰', '110067臺北市信義區區永吉路30巷102弄14號', '02-2761-3983', '0910-002-203'),
(70, '台北市', '信義區', '廣居里', '楊玉堂', '110061臺北市信義區區忠孝東路5段372巷27弄75之1號', '02-2345-2011', '0933-705-902'),
(71, '台北市', '信義區', '安康里', '萬宇恆', '110032臺北市信義區區虎林街232巷55號1樓', '02-2727-3989', '0937-853-597'),
(72, '台北市', '信義區', '六藝里', '丁禾溱', '110臺北市信義區區永吉路120巷43弄3號', '02-2767-6839', '0913-817-158'),
(73, '台北市', '信義區', '雅祥里', '宋美玲', '110臺北市信義區區基隆路1段35巷7弄35號', '02-8787-1665', '0933-162-191'),
(74, '台北市', '信義區', '五常里', '游育靖', '110臺北市信義區區松信路58號', '02-2756-5848', '0925-198-033'),
(75, '台北市', '信義區', '五全里', '吳秀好', '110臺北市信義區區永吉路278巷1弄13號', '02-2762-0007', '0921-126-652'),
(76, '台北市', '信義區', '永吉里', '陳永昌', '臺北市信義區區永吉路517巷1弄10號', '02-2764-1381', '0933-205-922'),
(77, '台北市', '信義區', '長春里', '許錦忠', '110臺北市信義區區忠孝東路5段423巷47號', '02-2762-4699', '0937-042-279'),
(78, '台北市', '信義區', '四育里', '連圀堂', '110臺北市信義區區松山路180號', '02-2768-2426', '0937-080-019'),
(79, '台北市', '信義區', '四維里', '劉耀平', '110臺北市信義區區虎林街70巷1號', '02-2769-2390', '0915-252-215'),
(80, '台北市', '信義區', '永春里', '許蕙蘭', '松山路287巷3弄21號', '02-2765-0054', '0910-150-308'),
(81, '台北市', '信義區', '富台里', '羅至佑', '臺北市忠孝東路5段423巷4弄22號', '無', '0901156880'),
(82, '台北市', '信義區', '國業里', '林銘賢', '臺北市信義區區虎林街222巷15號', '02-2726-2518', '0932-237-293'),
(83, '台北市', '信義區', '松隆里', '黃俊龍', '臺北市信義區區松山路650巷15弄臨18之6號', '0933-892-098', '0933-892-098'),
(84, '台北市', '信義區', '松友里', '張海秋', '臺北市信義區路六段76巷2弄22號', '02-2759-1599', '0952-688-388'),
(85, '台北市', '信義區', '松光里', '蔡福來', '臺北市忠孝東路5段492巷32號', '02-2727-8925', '0925-129-159'),
(86, '台北市', '信義區', '中坡里', '曾傳達', '臺北市信義區區福德街125號', '02-2726-9963', '0922-314-835'),
(87, '台北市', '信義區', '中行里', '林美君', '110041臺北市信義區區福德街268巷32號1樓', '02-2727-4777', '0987-445-009'),
(88, '台北市', '信義區', '大道里', '毛水月', '信義區區忠孝東路5段790巷62弄4號', '02-2726-7933', '0933-720-485'),
(89, '台北市', '信義區', '大仁里', '蔡桂清', '臺北市信義區區福德街86號3樓', '02-8726-1376', '0919-220-939'),
(90, '台北市', '信義區', '景新里', '孫積德', '臺北市信義區區莊敬路178巷20號3樓', '02-2758-0905', '0963-139-975'),
(91, '台北市', '信義區', '惠安里', '周葉長', '110020臺北市信義區區吳興街506巷21號', '02-2758-8650', '0921-126-430'),
(92, '台北市', '信義區', '三張里', '李伯壽', '臺北市莊敬路423巷6弄1號', '02-2722-2828', '0933-889-579'),
(93, '台北市', '信義區', '三犁里', '翁信利', '信義區路5段150巷14弄6號1樓', '02-8789-0990', '0911-822-428'),
(94, '台北市', '信義區', '六合里', '陳盈如', '臺北市松仁路240巷19號3樓', '02-2758-4465', '0975-778-025'),
(95, '台北市', '信義區', '泰和里', '林正義', '臺北市信義區區松仁路308巷63號', '02-8786-2076', '0952-332-886'),
(96, '台北市', '信義區', '景聯里', '洪福周', '臺北市吳興街106巷9號', '02-2737-0088', '0933-721-676'),
(97, '台北市', '信義區', '景勤里', '王雷凱', '臺北市信義區區信安街15巷46弄6號', '02-2732-1533', '0961-567-893'),
(98, '台北市', '信義區', '雙和里', '宋祥輝', '吳興街284巷24弄16號', '02-2737-0683', '0919-951-082'),
(99, '台北市', '信義區', '嘉興里', '鄭智耀', '嘉興街175巷6號', '02-2732-4052', '0933-267-062'),
(100, '台北市', '信義區', '黎順里', '卓秀娟', '崇德街15號', '02-2377-0037', NULL),
(101, '台北市', '信義區', '黎平里', '楊介舟', '富陽街55巷9號', '02-2732-9518', '0936-112-627'),
(102, '台北市', '信義區', '黎忠里', '黃建智', '臺北市信義區區和平東路三段391巷16號2樓', '無', '0938-196-598'),
(103, '台北市', '信義區', '黎安里', '劉梅菊', '110001臺北市和平東路3段627巷41弄8之1號', '02-2377-1017', '0928-290-062'),
(104, '台北市', '大安區', '德安里', '蔡銘宗', '10691臺北市大安區四維路78號6樓', '02-2704-9366', '0918-499-478'),
(105, '台北市', '大安區', '仁慈里', '陳麒中', '10650臺北市大安區復興南路1段295巷18號', '02-2706-1268', '0988-136600'),
(106, '台北市', '大安區', '和安里', '翁德東', '10658臺北市大安區信義區路三段109之6號', '02-2704-0069', '0933-849729'),
(107, '台北市', '大安區', '民炤里', '周暉泰', '10650臺北市大安區信義區路3段31巷5號', '02-2700-8406', '0981-950606'),
(108, '台北市', '大安區', '仁愛里', '林宜蓁', '10688臺北市大安區復興南路一段191-1號', '無', '0920728688'),
(109, '台北市', '大安區', '義村里', '潘明勳', '10654臺北市大安區忠孝東路3段248巷7弄4號', '02-2711-9084', '0910380479'),
(110, '台北市', '大安區', '民輝里', '陳威禎', '10651臺北市大安區濟南路3段9號3樓', '02-2711-3757', '0937921537'),
(111, '台北市', '大安區', '昌隆里', '王志剛', '10653臺北市大安區市民大道三段206號4樓之1', '02-2751-2118', '0953663565'),
(112, '台北市', '大安區', '誠安里', '李有福', '10666臺北市大安區安東街28號', '02-2771-5532', '0932154320'),
(113, '台北市', '大安區', '光武里', '韓修和', '10691臺北市敦化南路1段160巷20號', '02-8771-6300', '0933200966'),
(114, '台北市', '大安區', '龍坡里', '黃世詮', '10648臺北市大安區泰順街13之1號', '02-2362-3674', '0933155369'),
(115, '台北市', '大安區', '龍泉里', '龎維良', '10645臺北市大安區雲和街49號', '02-2366-1135', '0939529273'),
(116, '台北市', '大安區', '古風里', '孔憲娟', '10645臺北市大安區師大路135巷1號', '02-3365-1622', '0912599858'),
(117, '台北市', '大安區', '古莊里', '吳宗雄', '10646臺北市大安區浦城街16巷2號', '02-2369-6193', '0937834863'),
(118, '台北市', '大安區', '龍安里', '洪秋甲', '10644臺北市大安區和平東路一段199巷4-9號', '02-2351-0479', '0933122328'),
(119, '台北市', '大安區', '錦安里', '龔志慧', '10642臺北市大安區潮州街130巷3號', '02-2351-5967', '0928494909'),
(120, '台北市', '大安區', '福住里', '李欣芠', '10650臺北市大安區永康街47巷10之1號', '02-2322-2581', '0936173519'),
(121, '台北市', '大安區', '永康里', '李明螢', '10650臺北市大安區金華街199巷3弄6號', '02-2321-9653', '0910-213772'),
(122, '台北市', '大安區', '光明里', '張惠銓', '10641臺北市大安區信義區路2段86巷26號', '02-2341-3350', '0919933237'),
(123, '台北市', '大安區', '錦泰里', '范燕淇', '10641臺北市大安區杭州南路二段65巷10號', '02-2391-8123', '0963098579'),
(124, '台北市', '大安區', '錦華里', '溫美珠', '10643台北市大安區杭州南路二段93巷1-1號', '02-2392-3999', '0937066733'),
(125, '台北市', '大安區', '龍圖里', '陳煒光(代理)', '106029臺北市大安區瑞安街176號', '02-27085234', '0901349057'),
(126, '台北市', '大安區', '新龍里', '李淑梅', '10659臺北市大安區新龍里信義區路3段134巷82號 2樓', '02-2706-7771', '0920588374'),
(127, '台北市', '大安區', '龍陣里', '游菊英', '10661臺北市大安區瑞安街75號3樓', '02-2706-0389', '0958918669'),
(128, '台北市', '大安區', '龍雲里', '黃廣明', '10665臺北市大安區復興南路二段151巷43號', '02-2754-7302', '0978381953'),
(129, '台北市', '大安區', '龍生里', '沈鳳雲', '10664臺北市大安區和平東路二段141號', '02-2706-4529', '0910246384'),
(130, '台北市', '大安區', '住安里', '歐秀珠', '10683臺北市大安區信義區路4段30巷27弄23號', '02-2755-0387', '0920649877'),
(131, '台北市', '大安區', '義安里', '劉威志', '10681臺北市大安區敦化南路二段11巷25-1號', '02-2700-5611', '0933922981'),
(132, '台北市', '大安區', '通化里', '周進財', '10679臺北市大安區通化街19巷19號', '02-2701-9336', '0932210203'),
(133, '台北市', '大安區', '通安里', '鄭水波', '106053臺北市大安區通安街98號', '02-27325548', '0920652487'),
(134, '台北市', '大安區', '臨江里', '劉勇雄', '10677臺北市大安區通化街143巷33號', '02-2738-8269', '0932-029070'),
(135, '台北市', '大安區', '法治里', '林佩燕', '10677臺北市大安區通化街201號', '02-2738-6436', '0980-403536'),
(136, '台北市', '大安區', '全安里', '連馬世驍', '10675臺北市大安區和平東路三段119巷25號', '02-2732-1102', '0921-126-993'),
(137, '台北市', '大安區', '群賢里', '翁鴻源', '10670臺北市大安區和平東路二段311巷43弄36號', '02-2700-3623', '0930069850'),
(138, '台北市', '大安區', '群英里', '石忠勝', '10670臺北市大安區四維路198巷30弄9號', '02-2754-7207', '0933894266'),
(139, '台北市', '大安區', '虎嘯里', '詹仲琪', '10669臺北市大安區和平東路三段46之5號', '02-2737-3718', '0912263574'),
(140, '台北市', '大安區', '臥龍里', '邱奕承', '10667臺北市大安區復興南路二段355號', '02-2736-7001', '0908-415053'),
(141, '台北市', '大安區', '龍淵里', '汪吉秋', '10663臺北市大安區和平東路2段118巷2弄6號', '02-2377-3535', '0932031417'),
(142, '台北市', '大安區', '龍門里', '黃絹家', '10662臺北市大安區和平東路2段54號2樓(里民活動場所：和平東路2段18巷3弄4號)', '02-2363-5638', '0920-439-233'),
(143, '台北市', '大安區', '大學里', '吳沛璇', '10660臺北市大安區溫州街74巷2弄4號', '02-2362-2674', '0910110400'),
(144, '台北市', '大安區', '芳和里', '黃正浪', '10675臺北市大安區樂業街99號B1', '02-8732-1187', '0985698758'),
(145, '台北市', '大安區', '黎元里', '蘇偉彬', '106040臺北市臥龍街187號', '02-8732-0168', '0939728869'),
(146, '台北市', '大安區', '黎孝里', '方丁輝', '10676臺北市大安區臥龍街267之10號', '02-2377-2022', '0919323201'),
(147, '台北市', '大安區', '黎和里', '李啟宏', '10676臺北市大安區富陽街111號', '02-23779090', '0931297990'),
(148, '台北市', '大安區', '建安里', '溫志維', '10690臺北市大安區敦化南路一段161巷69弄4號2樓', '02-2771-6279', '0982543811'),
(149, '台北市', '大安區', '建倫里', '林正達', '10688臺北市大安區安和路1段21巷24號6樓', '02-2711-8085', '0988058967'),
(150, '台北市', '大安區', '敦安里', '王 復', '10689臺北市大安區仁愛路4段122巷44-1號', '02-2784-9293', '0910088886'),
(151, '台北市', '大安區', '正聲里', '陳秀蘭', '10694臺北市大安區正聲里光復南路260巷41-5號(6樓)', '02-2711-3890', '0937985839'),
(152, '台北市', '大安區', '敦煌里', '傅吉田', '10689臺北市大安區安和路一段135巷8號', '02-2704-3110', '0932396218'),
(153, '台北市', '大安區', '華聲里', '陳金花', '10694臺北市大安區市民大道四段248號B1', '02-27715309', '0933-720223'),
(154, '台北市', '大安區', '車層里', '李易儒', '10692臺北市大安區仁愛路4段345巷5弄15號1樓', NULL, '0906238287'),
(155, '台北市', '大安區', '光信里', '楊珮菱', '10696臺北市大安區延吉街236巷17號2樓', '02-2703-2357', '0916-563248'),
(156, '台北市', '大安區', '學府里', '李淳琳', '10673臺北市大安區羅斯福路四段119巷66弄8號', '02-8732-1732', '0922989922'),
(157, '台北市', '中山區', '正守里', '陳育群', '臺北市中山區林森北路67巷7號2樓', '02-2562-2169', '0975875967'),
(158, '台北市', '中山區', '正義里', '李志勇', '臺北市中山區林森北路133巷78號', '02-2531-8228', '0988723237'),
(159, '台北市', '中山區', '正得里', '吳美瑩', '臺北市中山區林森北路100號8樓', '02-2551-8951', '0982111389'),
(160, '台北市', '中山區', '民安里', '王俊堯', '臺北市中山區中山北路一段112巷5-2號', '02-2581-7770', '0983672893'),
(161, '台北市', '中山區', '康樂里', '王金富', '臺北市中山區南京東路一段35號1樓', '02-2562-9158', '0932001952'),
(162, '台北市', '中山區', '中山里', '曾瓊梅', '臺北市中山區長春路37號1樓', '02-2581-1236', '0932940210'),
(163, '台北市', '中山區', '聚盛里', '舒贑臺', '臺北市中山區錦州街50號1樓', '02-2541-1398', '0970058151'),
(164, '台北市', '中山區', '集英里', '賴政庸', '臺北市中山區天祥路9號', '02-2536-4260', '0933124851'),
(165, '台北市', '中山區', '聚葉里', '游啟業', '臺北市中山區中山北路二段137巷35號1樓', '02-2581-2320', '0952080608'),
(166, '台北市', '中山區', '恆安里', '黃志昌', '臺北市中山區中山北路二段183巷9號', '02-2585-3352', '0918000050'),
(167, '台北市', '中山區', '晴光里', '林姿吟', '臺北市中山區雙城街18巷20號1樓', '02-2596-6342', '0981201026'),
(168, '台北市', '中山區', '圓山里', '葉建輝', '臺北市中山區新生北路三段82巷32號1樓', '02-2593-0718', '0953211827'),
(169, '台北市', '中山區', '劍潭里', '黃開芳', '臺北市中山區通北街143號', '02-2533-5530', '0952630327'),
(170, '台北市', '中山區', '大直里', '曹邦全', '臺北市中山區北安路573巷6號1樓', '02-2533-3676', '0918050253'),
(171, '台北市', '中山區', '成功里', '李清水', '臺北市中山區北安路676號1樓（大直計程車休息站內）', '02-2532-2340', '0933908508'),
(172, '台北市', '中山區', '永安里', '唐楷瀚', '臺北市中山區明水路389號', NULL, '0910857885'),
(173, '台北市', '中山區', '大佳里', '莊欽億', '臺北市中山區松江路581巷53號1樓、民族東路85至89號1樓', '02-2501-9605', '0989168356'),
(174, '台北市', '中山區', '新喜里', '吳昇陽', '臺北市中山區德惠街170巷19號1樓', '02-2592-8750', '0921139977'),
(175, '台北市', '中山區', '新庄里', '鄧麗珠', '臺北市中山區農安街125巷43號1樓', '02-2593-5220', '0933166086'),
(176, '台北市', '中山區', '新福里', '童勝輝', '臺北市中山區中原街142號2樓之1', '02-2586-4846', '0910195039'),
(177, '台北市', '中山區', '松江里', '蔣築諠', '臺北市中山區松江路297巷9號1樓', '02-2517-0155', '0935226835'),
(178, '台北市', '中山區', '新生里', '曾國益', '臺北市中山區新生北路二段137巷41號', '02-2564-2183', '0932187925'),
(179, '台北市', '中山區', '中庄里', '陳建中', '臺北市中山區吉林路199巷29號', '02-2581-7916', '0926379768'),
(180, '台北市', '中山區', '行政里', '羅仲瑜(代理)', '臺北市中山區松江路367號5樓', '25031369#516', '0972295624'),
(181, '台北市', '中山區', '行仁里', '陳德賢', '臺北市中山區龍江路356巷45號1樓', '02-2517-0557', '0928216147'),
(182, '台北市', '中山區', '行孝里', '呂勗淇', '臺北市中山區民族東路282號5樓', '02-2517-2230', '0952094088'),
(183, '台北市', '中山區', '下埤里', '施士凱', '臺北市中山區龍江路429巷10號2樓', '02-2508-4192', '0908112320'),
(184, '台北市', '中山區', '江寧里', '徐信洲', '臺北市中山區合江街137號4樓', '02-2509-7688', '0933091409'),
(185, '台北市', '中山區', '江山里', '劉陽劍', '臺北市中山區建國北路二段129號2樓', '02-2506-1832', '0933892067'),
(186, '台北市', '中山區', '中吉里', '林德勝', '臺北市中山區松江路204巷65號1樓', '02-2560-1553', '0932157126'),
(187, '台北市', '中山區', '中原里', '羅崇華', '臺北市中山區新生北路二段53號2樓', '02-2531-5557', '0972245777'),
(188, '台北市', '中山區', '興亞里', '林芳薇', '臺北市中山區吉林路26巷5號2樓', '02-2551-7846', '0935735035'),
(189, '台北市', '中山區', '中央里', '李陳金綉', '臺北市中山區伊通街125巷11之1號1樓', '02-2504-1483', '0925421115'),
(190, '台北市', '中山區', '朱馥里', '卓志勇', '臺北市中山區龍江路168號地下1樓', '02-2503-3200', '0982032207'),
(191, '台北市', '中山區', '龍洲里', '陳世明', '臺北市中山區興安街83號', '02-2517-9995', '0932201912'),
(192, '台北市', '中山區', '朱園里', '李林耀', '臺北市中山區渭水路50號4樓', '02-2751-6216', '0922161726'),
(193, '台北市', '中山區', '埤頭里', '黃基淋', '臺北市中山區安東街5-2號', '02-2752-1080', '0913011212'),
(194, '台北市', '中山區', '朱崙里', '高銘鴻', '臺北市中山區龍江路21巷2號', '02-2781-5504', '0936096604'),
(195, '台北市', '中山區', '力行里', '沈銀德', '臺北市中山區龍江路37巷19號', '02-2721-7218', '0939269398'),
(196, '台北市', '中山區', '復華里', '黃展輝', '臺北市中山區遼寧街108巷14號', '02-2712-5934', '0933908512'),
(197, '台北市', '中山區', '金泰里', '游進義', '臺北市中山區敬業三路160號2樓', '02-8509-8472', '0932357865'),
(198, '台北市', '中山區', '北安里', '陳小康', '臺北市中山區北安路759巷6號1樓', '02-2533-7936', '0932054131'),
(199, '台北市', '中正區', '水源里', '林全義', '10090臺北市中正區水源里羅斯福路4段52巷16弄7號', '02-2369-0339', '0935381941'),
(200, '台北市', '中正區', '富水里', '陳麗真', '10087臺北市中正區富水里永春街185-3號', '02-2365-6541', '0933958335'),
(201, '台北市', '中正區', '文盛里', '温紹宏', '10045臺北市中正區羅斯福路3段286巷4弄4號1樓', '02-2367-7325', '0919246218'),
(202, '台北市', '中正區', '林興里', '吳寶燕', '10086臺北市中正區林興里水源路21號', '02-2368-6919', '0933213180'),
(203, '台北市', '中正區', '河堤里', '鄒士根', '10088臺北市中正區河堤里金門街11-1號', '02-2364-1783', '0932386041'),
(204, '台北市', '中正區', '頂東里', '王曜樹', '100033臺北市中正區頂東里金門街9之1號', '02-2364-8892', '0937042108'),
(205, '台北市', '中正區', '網溪里', '夏萬浪', '10082臺北市中正區網溪里牯嶺街165號', '02-3365-1928', '0919940027'),
(206, '台北市', '中正區', '板溪里', '黃國輝', '10085 臺北市中正區板溪里汀州路2段151號1樓', '02-2367-6960', '0928840912'),
(207, '台北市', '中正區', '螢圃里', '陳木松', '10083臺北市中正區螢圃里重慶南路三段83巷3號', '02-2365-7968', '0939133669'),
(208, '台北市', '中正區', '螢雪里', '陳文質', '10077臺北市中正區螢雪里福州街59號', '02-2305-2201', '0933826949'),
(209, '台北市', '中正區', '永功里', '陳宏明', '10071臺北市中正區永功里汀州路一段324號', '02-2337-9808', '0933016366'),
(210, '台北市', '中正區', '永昌里', '陳玟如', '臺北市中正區汀州路一段242巷5號', '2307-3916', '0958971396'),
(211, '台北市', '中正區', '龍興里', '陳月容', '10079臺北市中正區三元街131號4樓', '02-23032845', '0917522883'),
(212, '台北市', '中正區', '忠勤里', '方荷生', '100062臺北市中正區忠勤里中華路2段303巷14號', '02-2305-6741', '0935920329'),
(213, '台北市', '中正區', '廈安里', '涂光宇', '10062臺北市中正區廈安里中華路二段175巷24號', '02-2302-7494', '0932028027'),
(214, '台北市', '中正區', '愛國里', '周德潤', '10066臺北市中正區愛國里延平南路192巷6號', '02-2388-1662', '0933028353'),
(215, '台北市', '中正區', '南門里', '郭有賢', '100057臺北市中正區南門里博愛路163號', '02-2381-8302', '0928490157'),
(216, '台北市', '中正區', '龍光里', '陳萬龍', '10002臺北市中正區和平西路二段45號', '02-2303-3498', '0937888899'),
(217, '台北市', '中正區', '南福里', '許益明', '10078臺北市中正區南福里和平西路1段55巷6號', '2392-8982', '0937855870'),
(218, '台北市', '中正區', '龍福里', '鄭珍珍', '10066臺北市中正區龍福里牯嶺街35號', '02-2391-2025', '0933763131'),
(219, '台北市', '中正區', '新營里', '莊柏辰', '10092臺北市中正區金華街20號1樓', '02-3393-7573', '0966415705'),
(220, '台北市', '中正區', '建國里', '許瀞尹', '10050臺北市中正區衡陽路78號', '02-2314-1308', '0930886725'),
(221, '台北市', '中正區', '光復里', '王麗美', '10042臺北市中正區光復里開封街一段105-2號1樓', '02-2361-3966', '0955526140'),
(222, '台北市', '中正區', '黎明里', '鄭燕宗', '10041臺北市中正區黎明里忠孝西路一段29巷2號1、2樓', '02-2382-0387', '0988333679'),
(223, '台北市', '中正區', '梅花里', '吳崑山', '100013臺北市中正區紹興北街23號1樓', '02-2393-3800', '0921100970'),
(224, '台北市', '中正區', '幸福里', '蘇宏仁', '10049臺北市中正區幸福里北平東路24號', '02-2341-1328', '0919588497'),
(225, '台北市', '中正區', '幸市里', '林禎吉', '100臺北市中正區新生南路一段54巷18-3號', '02-2351-5824', '0933016568'),
(226, '台北市', '中正區', '東門里', '牟桂富', '臺北市中正區東門里仁愛路一段53號', '(02)2321-5659', '0932-388608'),
(227, '台北市', '中正區', '文北里', '陳余秀卿', '10055臺北市中正區杭州南路一段77巷3號', '02-2321-8919', '0939561355'),
(228, '台北市', '中正區', '文祥里', '李淑珍', '10020臺北市中正區金山南路一段100號', '02-23944599', '0919319818'),
(229, '台北市', '中正區', '三愛里', '陳仁志', '10064臺北市中正區臨沂街75巷19號', '02-2391-2222', '0938205215'),
(230, '台北市', '大同區', '大有里', '許美智', '103006臺北市大同區民樂街115號', '02-2557-7229', '0931903250'),
(231, '台北市', '大同區', '光能里', '陳靜筠', '103018臺北市大同區承德路二段33號1樓', '02-2559-8984', '0933714724'),
(232, '台北市', '大同區', '老師里', '許宮銘', '103036臺北市大同區哈密街158號', '02-2592-5168', '0936222200'),
(233, '台北市', '大同區', '國慶里', '高大陸', '103023臺北市大同區民族西路260號1樓', '02-25954836', '0930-858876'),
(234, '台北市', '大同區', '鄰江里', '陳豊祥', '103043臺北市大同區酒泉街158號', '02-2591-0903', '0920496193'),
(235, '台北市', '大同區', '景星里', '吳若屏', '103027臺北市大同區伊寧街75巷4號', NULL, '0981962269'),
(236, '台北市', '大同區', '建明里', '王振宇', '103013臺北市大同區華陰街99號', NULL, '0965121696'),
(237, '台北市', '大同區', '建泰里', '陳鵬程', '103014臺北市大同區承德路一段101號', '02-2558-3377', '0925583377'),
(238, '台北市', '大同區', '至聖里', '楊秀龍', '103033臺北市大同區民族西路169巷38號1樓', '02-2597-2808', '0923655787'),
(239, '台北市', '大同區', '雙連里', '洪振恒', '103023臺北市大同區歸綏街126號', '02-2552-8098', '0910222552'),
(240, '台北市', '大同區', '民權里', '陳玉女', '103024臺北市大同區寧夏路131號', '02-2550-1178', '0912284889'),
(241, '台北市', '大同區', '永樂里', '林宗穎', '103002臺北市大同區南京西路239巷31號', '02-2555-4396', '0930087087'),
(242, '台北市', '大同區', '朝陽里', '呂国維', '103008臺北市大同區南京西路167巷3-5號', '02-25523101', '0936229268'),
(243, '台北市', '大同區', '南芳里', '賴徐玉枝', '103004臺北市大同區迪化街1段312巷7號', '02-2553-0668', '0939505458'),
(244, '台北市', '大同區', '揚雅里', '吳振明', '103026臺北市大同區重慶北路三段113巷56號2樓之2', NULL, '0928271908'),
(245, '台北市', '大同區', '斯文里', '江雪卿', '103031臺北市大同區承德路3段129巷11號3樓', NULL, '0922526429'),
(246, '台北市', '大同區', '重慶里', '張艷鴻', '103030臺北市大同區重慶里重慶北路三段347號二樓之2', '02-2595-1038', '0911062198'),
(247, '台北市', '大同區', '保安里', '張賦脈', '103030臺北市大同區重慶北路3段295巷6號2樓', '02-2591-0582', '0930743156'),
(248, '台北市', '大同區', '玉泉里', '羅素真', '103001臺北市大同區西寧北路11號', NULL, '0979305833'),
(249, '台北市', '大同區', '蓬萊里', '何英輝', '10346臺北市大同區大龍街10號', '02-2593-6524', '0936229837'),
(250, '台北市', '大同區', '建功里', '周志賢', '103016臺北市大同區重慶北路1段83巷37號', '02-2556-4651', '0952167609'),
(251, '台北市', '大同區', '延平里', '郭逸斌', '103009臺北市大同區甘州街12號', '02-2552-8918', '0928823380'),
(252, '台北市', '大同區', '星明里', '劉福永', '103020臺北市大同區太原路161巷4號', '02-2556-2463', '0938631165'),
(253, '台北市', '大同區', '隆和里', '沈春華', '103041臺北市大同區景化街46號', NULL, '0922799261'),
(254, '台北市', '大同區', '國順里', '陳穎慧', '103028臺北市大同區迪化街二段163號', '02-2585-3129', '0952158077'),
(255, '台北市', '萬華區', '和德里', '洪天化', '108036臺北市萬華區區西園路2段372巷10號(和德里)', '02-23395285', '0937896753'),
(256, '台北市', '萬華區', '仁德里', '許淑惠', '108212臺北市萬華區區西寧南路192-2號1樓(仁德里)', '02-2331-6860', '0920408935'),
(257, '台北市', '萬華區', '福星里', '李黃玉根', '108001臺北市萬華區區西寧南路14之3號1樓', '02-2331-2566', '0910128896'),
(258, '台北市', '萬華區', '興德里', '吳政侑', '108056臺北市萬大路449巷28弄7號(興德里)', '02-2301-6199', '0912834555'),
(259, '台北市', '萬華區', '榮德里', '陳欣漢', '108047臺北市萬華區區武成街80巷61號(榮德里)', '02-2303-7876', '0928118833'),
(260, '台北市', '萬華區', '忠貞里', '林錫祺', '108053臺北市萬華區區萬大路277巷44弄1之3號(忠貞里)', '02-23093320', '0953190521'),
(261, '台北市', '萬華區', '全德里', '趙素美', '108033臺北市德昌街21號(全德里)', '02-2303-0059', '0910075948'),
(262, '台北市', '萬華區', '華中里', '黃世豪', '108046 臺北市萬華區區萬大路534巷42號(華中里)', '02-2337-6162', '0966530656'),
(263, '台北市', '萬華區', '日善里', '蕭進德', '108234臺北市東園街19號二樓(日善里)', '23324633', '0954075413'),
(264, '台北市', '萬華區', '錦德里', '張菀庭', '108038臺北市寶興街80巷18號(錦德里)', '23070898', '0982877775'),
(265, '台北市', '萬華區', '壽德里', '陳志雄', '108054臺北市萬華區區長泰街45號', '2332-8377', '0922-087-388'),
(266, '台北市', '萬華區', '日祥里', '張宏聖', '10882臺北市萬華區區青年路180號(日祥里)', '02-23090136', '0932007328'),
(267, '台北市', '萬華區', '華江里', '洪佳君', '10858長順區民活動中心(長順街臨127號)', '02-23022987', '0970-747-926'),
(268, '台北市', '萬華區', '新忠里', '邱文龍', '108048臺北市西藏路125巷7之2號(新忠里)', '23392806', '0953009862'),
(269, '台北市', '萬華區', '福音里', '蔡岳樺', '108012 臺北市萬華區區永福街77號(福音里)', '02-2311-3699', '0979608343'),
(270, '台北市', '萬華區', '和平里', '吳淑芬', '108021臺北市萬華區區艋舺大道362號(和平里)', '02-23361001', '0928552882'),
(271, '台北市', '萬華區', '銘德里', '吳啟源', '108042臺北市萬華區區長泰街219號1樓(銘德里)', '2305-2959', '0935935974'),
(272, '台北市', '萬華區', '頂碩里', '溫宗霖', '10860臺北市萬華區區興寧街2號3樓(頂碩里)', '02-2302-7320', '0963038625'),
(273, '台北市', '萬華區', '雙園里', '許惠城', '10860臺北市萬華區區萬大路96號(雙園里)', '23066865', '0955234834'),
(274, '台北市', '萬華區', '保德里', '楊美女', '108040臺北市萬華區區東園街154巷9弄2號(保德里)', '02-2307-4702', '0936048525'),
(275, '台北市', '萬華區', '新起里', '陳鴻祥', '108011臺北市萬華區區貴陽街二段87號(新起里)', '2388-9096', '0932187378'),
(276, '台北市', '萬華區', '柳鄉里', '蔡和益', '108018臺北市桂林路242巷42之1號(柳鄉里)', '02-23089196', '0938079989'),
(277, '台北市', '萬華區', '萬壽里', '吳美玟', '臺北市峨眉街137號2樓(萬壽里)', '2381-7481', '0970778508'),
(278, '台北市', '萬華區', '西門里', '葉敏惠', '108006臺北市萬華區區西寧南路90巷15號(西門里)', '2331-8031', '0963720679'),
(279, '台北市', '萬華區', '菜園里', '蔡蕙而', '108010臺北市萬華區區內江街150號(菜園里)', '02-2388-8990', '0925000932'),
(280, '台北市', '萬華區', '青山里', '李昭成', '臺北市萬華區區108014華西街21巷9號(青山里)', '02-2331-9570', '0933121742'),
(281, '台北市', '萬華區', '富民里', '范添成', '108010臺北市萬華區區康定路160號1樓', '02-2361-1726', '0910038567'),
(282, '台北市', '萬華區', '富福里', '許文輝', '108020臺北市和平西路3段30巷臨5-1號(富福里)', '2302-6455', '0935198759'),
(283, '台北市', '萬華區', '糖廍里', '葉玲瑜', '10856臺北市大理街106號1樓(糖廍里)', '02-2302-0211', '0937925081'),
(284, '台北市', '萬華區', '綠堤里', '邱郁惠', '臺北市雙園街122巷14號(綠堤里)', '02-2302-3658', '0955195123'),
(285, '台北市', '萬華區', '忠德里', '施育婷', '108040臺北市東園街66巷24弄8號(忠德里)', '02-2305-0886', '0913303616'),
(286, '台北市', '萬華區', '孝德里', '李明芬', '108043臺北市萬華區區德昌街261巷16號(孝德里)', '02-23321818', '0932-015015'),
(287, '台北市', '萬華區', '新和里', '邱惠雯', '108052臺北市萬華區區中華路二段416巷13之1號(新和里)', '02-23390506', '0966766777'),
(288, '台北市', '萬華區', '新安里', '郭正中', '108037臺北市萬華區區萬大路187巷1弄9號(新安里)', '02-23049324', '0936077184'),
(289, '台北市', '萬華區', '凌霄里', '游秋蓮', '108050臺北市中華路2段504巷20號(凌霄里)', '02-2337-6040', '0916129935'),
(290, '台北市', '萬華區', '騰雲里', '李重華', '108051臺北市萬華區區中華路二段596巷1號(騰雲里)', '23096837', '0928628781'),
(291, '台北市', '文山區', '景行里', '陳千惠', '116052臺北市文山區羅斯福路六段393號7樓', '02-8663-6123', '0983569319'),
(292, '台北市', '文山區', '景東里', '高鳳謙', '116067臺北市文山區景興路153巷1號', '02-8663-8808', '0912950669'),
(293, '台北市', '文山區', '景美里', '林賢錡', '116053臺北市文山區景美里育英街57巷13號1樓', '02-2934-2273', '0932119689'),
(294, '台北市', '文山區', '景慶里', '蔡岳澄', '116055臺北市文山區景福街281號', '02-2933-5558', '0937075741'),
(295, '台北市', '文山區', '景仁里', '何進輝', '116056臺北市文山區景福街21巷3弄11號', '02-2930-6175', '0966570861'),
(296, '台北市', '文山區', '景華里', '黃培堯', '116052 臺北市文山區羅斯福路六段315號', '02-2934-9967', '0937819808'),
(297, '台北市', '文山區', '萬有里', '黃麗霓', '116052臺北市文山區興隆路一段238之1號', '02-2933-6757', '0988869926'),
(298, '台北市', '文山區', '萬祥里', '張紅木', '116063臺北市文山區羅斯福路五段255號1樓', '02-2933-0009', '0910130087'),
(299, '台北市', '文山區', '萬隆里', '林宗弘', '116057臺北市文山區萬隆街25號1樓', '02-2934-5047', '0921383216'),
(300, '台北市', '文山區', '萬年里', '邱秋芳', '116059臺北市文山區羅斯福路五段150巷35號1樓', '02-8663-7176', '0921923357'),
(301, '台北市', '文山區', '萬和里', '吳祚榮', '116058臺北市文山區汀州路4段251號、文山區溪洲街12號5樓之10', '02-8931-0117', '0932915189'),
(302, '台北市', '文山區', '萬盛里', '徐福進', '116063臺北市文山區羅斯福路五段127號7樓', '02-2934-3389', '0932061855'),
(303, '台北市', '文山區', '興豐里', '余鴻儒', '116062臺北市文山區興隆路一段229巷2號2樓、興隆路二段95巷8號8樓', '02-2932-4127', '0928807121'),
(304, '台北市', '文山區', '興光里', '洪正平', '116075臺北市文山區辛亥路四段209巷6、8號2樓', '02-2933-0357', '0926273695'),
(305, '台北市', '文山區', '興家里', '劉宗勳', '116080臺北市文山區興隆路三段223-2號1樓', '02-2230-4327', '0910121013'),
(306, '台北市', '文山區', '興得里', '曾瑞城', '116078臺北市文山區興隆路三段112巷2弄8號B1', '02-8931-2525', '0920664489'),
(307, '台北市', '文山區', '興業里', '凌錦成', '116072臺北市文山區興隆路二段160號10樓', '02-8663-5005', '0938307588'),
(308, '台北市', '文山區', '興安里', '林文權', '116070臺北市文山區興隆路二段88之2號1樓', '02-2933-0156', '0935035522'),
(309, '台北市', '文山區', '興福里', '呂晴芸', '116069臺北市文山區景華街121巷7弄1號', '02-2932-3399', '0937967679'),
(310, '台北市', '文山區', '興旺里', '蘇瓏議', '116074 臺北市文山區福興路27號', '02-2933-6691', '0922548930'),
(311, '台北市', '文山區', '興泰里', '施志聰', '116081 臺北市文山區辛亥路四段252巷5號1樓', '02-2932-7577', '0911509888'),
(312, '台北市', '文山區', '興昌里', '吳融昊', '116075臺北市文山區辛亥路四段199號', '02-2931-1528', '0960604305'),
(313, '台北市', '文山區', '試院里', '廖欽銅', '116001臺北市文山區和興路88號2樓', '02-2236-5228', '0920075552'),
(314, '台北市', '文山區', '華興里', '陳峙穎', '116005臺北市文山區木柵路一段264號', '02-2936-1780', '0905991580'),
(315, '台北市', '文山區', '明義里', '仇世屏', '116006臺北市文山區興隆路四段42巷1號', '02-2938-5198', '0932111117'),
(316, '台北市', '文山區', '明興里', '鄢健民', '116007臺北市文山區木柵路二段109巷25弄6號', '02-2234-0391', '0917556568'),
(317, '台北市', '文山區', '木柵里', '林志勤', '臺北市文山區久康街24巷42號', '02-2939-2969', '0928504139'),
(318, '台北市', '文山區', '木新里', '鄭明通', '116024臺北市文山區木新路二段50號', '02-2939-7162', '0916334606'),
(319, '台北市', '文山區', '順興里', '楊培釧', '116010臺北市文山區保儀路138巷28號2樓(暫定)', '02-2937-5586', '0934143675'),
(320, '台北市', '文山區', '樟林里', '陳文乾', '116023臺北市文山區興隆路四段66巷32號1樓', '02-2937-8896', '0910068718'),
(321, '台北市', '文山區', '樟新里', '歐陽禾英', '116789臺北市文山區一壽街22號9樓', '02-2937-6288', '0988050107'),
(322, '台北市', '文山區', '樟腳里', '高林玉嬌', '116018臺北市文山區恆光街45號7樓', '02-8661-5958', '0937017516'),
(323, '台北市', '文山區', '萬芳里', '陳姿秀', '116025臺北市文山區萬美街一段51號3樓', '02-2239-3399', '0938112938'),
(324, '台北市', '文山區', '博嘉里', '吳坤輝', '116027臺北市文山區木柵路四段157-1號1樓', '02-2239-3029', '0910097128'),
(325, '台北市', '文山區', '萬興里', '林志冠', '116012臺北市文山區秀明路二段112巷14號', '02-2939-2996', '0906926657'),
(326, '台北市', '文山區', '指南里', '張佳南', '116026臺北市文山區指南路3段33巷34號', '02-2234-2099', '0911841871'),
(327, '台北市', '文山區', '老泉里', '周良富', '116026臺北市文山區老泉里老泉街15號', '02-2936-8966', '0955500528'),
(328, '台北市', '文山區', '忠順里', '曾寧旖', '116021 臺北市文山區興隆路四段145巷30號1樓', '02-2939-9569', '0932152115'),
(329, '台北市', '文山區', '萬美里', '鄧瑞興', '116014臺北市文山區萬美街二段2巷6號', '02-2239-8880', '0939139033'),
(330, '台北市', '文山區', '政大里', '鄭文綺', '116026臺北市文山區萬壽路63-2號1樓', '02-2939-2799', '0958557370'),
(331, '台北市', '文山區', '樟文里', '林淑珠', '116022臺北市文山區木新路三段278巷7弄6號1樓', '02-2234-6605', '0952663096'),
(332, '台北市', '文山區', '興邦里', '張朝雄', '116071臺北市文山區興隆路二段203巷2弄10號', '02-8663-3016', '0919323040'),
(333, '台北市', '文山區', '樟樹里', '陳再炯', '116023臺北市文山區興隆路四段74巷22號1樓', '02-2937-6856', '0922686598'),
(334, '台北市', '南港區', '中南里', '謝沁荷', '115011臺北市南港區中南街123巷3號', '2788-3350', '0932140430'),
(335, '台北市', '南港區', '南港里', '李志錦', '115018臺北市南港區南港路1段227號', '02-2782-3009', '0920791666'),
(336, '台北市', '南港區', '新富里', '許睦燦', '115021臺北市南港區富康街59號', '02-2782-8495', '0952167703'),
(337, '台北市', '南港區', '三重里', '江輝吉', '115023臺北市南港區重陽路504巷1弄9號', '02-2783-4039', '0933032079'),
(338, '台北市', '南港區', '東新里', '蘇俊強課長', '115001臺北市南港區興南街43號', '02-2789-2969', '0972087657'),
(339, '台北市', '南港區', '新光里', '龔睿堉', '115020臺北市南港區昆陽街140巷42弄4號', '02-2788-6626', '0931399999'),
(340, '台北市', '南港區', '東明里', '曾漢祺', '115029臺北市南港區南港路2段128號1樓', '02-2783-2539', '0935979955'),
(341, '台北市', '南港區', '西新里', '胡家瑒', '115020臺北市南港區成功路1段99號', '02-2651-8665', '0908809805'),
(342, '台北市', '南港區', '玉成里', '黃聰智', '115027臺北市南港區南港路3段314巷5號', '02-2782-6939', '0932322435'),
(343, '台北市', '南港區', '合成里', '巫永仁', '115025臺北市南港區忠孝東路6段225巷1弄3號', '02-2785-2187', '0952861379'),
(344, '台北市', '南港區', '成福里', '王秋娥', '115019臺北市南港區東新街80巷9弄11號', '02-2655-9155', '0935602255'),
(345, '台北市', '南港區', '萬福里', '林建華', '115025臺北市南港區忠孝東路6段70巷21弄8號', '02-2785-5945', '0933035700'),
(346, '台北市', '南港區', '鴻福里', '劉明康', '115009臺北市南港區成福路84號', '02-2786-3893', '0970384868'),
(347, '台北市', '南港區', '百福里', '李雯馨', '115009臺北市南港區成福路170號3樓之5', '02-2783-8377', '0932281165'),
(348, '台北市', '南港區', '聯成里', '李品君', '115006臺北市南港區東新街77巷25號1樓', '02-2785-2963', '0909918013'),
(349, '台北市', '南港區', '舊莊里', '張瑞芳', '115022臺北市南港區舊莊街1段91巷11號', '02-2788-4906', '0922133922'),
(350, '台北市', '南港區', '中研里', '謝志勇', '115014臺北市南港區研究院路2段2巷2號', '02-2651-7179', '0910139682'),
(351, '台北市', '南港區', '九如里', '蘇國賢', '115031臺北市南港區研究院路3段157之1號', '02-2788-3329', '0932217257'),
(352, '台北市', '南港區', '仁福里', '吳肇輝', '115019臺北市南港區福德街443號1樓', '02-2654-2254', '0919619949'),
(353, '台北市', '南港區', '重陽里', '馬敬忠', '115023臺北市南港區重陽路199巷24之2號', '02-2782-6500', '0928643083'),
(354, '台北市', '內湖區', '西湖里', '黃俊隆', '11445臺北市內湖路一段285號7樓', '02-8797-6191', '0955408398'),
(355, '台北市', '內湖區', '西康里', '王興國', '11446臺北市內湖區內湖路一段61巷2號', '02-2797-5996', '0935025117'),
(356, '台北市', '內湖區', '西安里', '李國榮', '11442臺北市內湖區內湖路一段91巷110號', '02-2799-3070', '0928612351'),
(357, '台北市', '內湖區', '港墘里', '魏景城', '11446臺北市內湖區內湖路一段552號', '02-2799-1168', '0935281458'),
(358, '台北市', '內湖區', '港都里', '江水龍', '11448臺北市內湖區內湖路一段629巷39號', '02-2799-5389', '0910128329'),
(359, '台北市', '內湖區', '港富里', '李安邦', '11449臺北市內湖區麗山街47號', NULL, '0932018939'),
(360, '台北市', '內湖區', '港華里', '劉達逢', '11449臺北市內湖區環山路二段68巷14號1樓', '02-2657-2255', '0936887878'),
(361, '台北市', '內湖區', '內湖里', '許昌華', '11464臺北市內湖區內湖路二段355巷21號2樓', '02-2792-8228', '0917828272'),
(362, '台北市', '內湖區', '湖濱里', '陳尤雪', '11449臺北市內湖區內湖路二段346號', '02-2790-6289', '0938082264'),
(363, '台北市', '內湖區', '紫星里', '謝佳惠', '11460臺北市內湖區成功路三段187巷3弄4號', '02-8791-1313', '0955187847'),
(364, '台北市', '內湖區', '大湖里', '郭坤祥', '11456臺北市內湖區大湖山莊街117號3樓', '02-2790-6599', '0939-020275'),
(365, '台北市', '內湖區', '金龍里', '吳欣芸', '11489臺北市內湖區內湖路3段75號', '02-87925455', '0912341689'),
(366, '台北市', '內湖區', '金瑞里', '羅世甫', '11455臺北市內湖區金龍路218號1樓', '02-8792-6024', '0936286399'),
(367, '台北市', '內湖區', '碧山里', '曹連燊', '11453臺北市內湖區內湖路三段60巷6號B1', '02-2791-0355', '0937087608'),
(368, '台北市', '內湖區', '紫雲里', '夏亦芳', '11461臺北市內湖區康寧路一段228號', '02-2791-3337', '0933405308'),
(369, '台北市', '內湖區', '清白里', '陳東源', '11462臺北市內湖區星雲街208號', '02-2791-9637', '0935028280'),
(370, '台北市', '內湖區', '葫洲里', '曾宏昌', '11483臺北市內湖區民權東路六段280巷60號', '02-2630-5172', '0932030046'),
(371, '台北市', '內湖區', '紫陽里', '謝源德', '114035臺北市內湖區文德路210巷30弄27號', '02-2798-6724', '0939149180'),
(372, '台北市', '內湖區', '瑞陽里', '江光輝', '11476臺北市內湖區文德路66巷69弄9號1樓', '02-2799-1392', '0932392312'),
(373, '台北市', '內湖區', '瑞光里', '黃崇賢', '11468臺北市內湖區陽光街291號', '02-2659-6457', '0926969880'),
(374, '台北市', '內湖區', '五分里', '王海枝', '11484臺北市內湖區安康路388號', '02-2634-1171', '0920704780'),
(375, '台北市', '內湖區', '東湖里', '邱朝祿', '11480臺北市內湖區康樂街72巷17弄63號1樓', '02-2633-0218', '0918031567'),
(376, '台北市', '內湖區', '樂康里', '張碧玉', '11479臺北市內湖區康樂街150號6樓之1', '02-2630-7502', '0952653505'),
(377, '台北市', '內湖區', '內溝里', '王曉芳', '11480臺北市內湖區康樂街201巷8號', '02-2631-3178', '0963092126'),
(378, '台北市', '內湖區', '週美里', '丘麗玲', '11471臺北市內湖區南京東路六段330巷18弄12號1樓', '02-2793-8003', '0921140488'),
(379, '台北市', '內湖區', '行善里', '沈茂松', '11466臺北市內湖區新明路520號', '02-2796-0038', '0933925700'),
(380, '台北市', '內湖區', '石潭里', '廖煒國', '11471臺北市內湖區成功路二段1號', '02-2793-7177', '0962013662'),
(381, '台北市', '內湖區', '湖興里', '蔡穎峰', '114臺北市內湖區成功路二段320巷25號', '02-2791-4586', '0933206109'),
(382, '台北市', '內湖區', '湖元里', '林明源', '11465臺北市內湖區民權東路六段136巷48號', '02-8792-7677', '0958180106'),
(383, '台北市', '內湖區', '安湖里', '尤樹旺', '11488臺北市內湖區東湖路1號9樓', '02-26306552', '0936450530'),
(384, '台北市', '內湖區', '秀湖里', '謝明毅', '11457臺北市內湖區成功路四段323巷16號', '02-2794-0052', '0936797875'),
(385, '台北市', '內湖區', '安泰里', '何素蓮', '11477臺北市內湖區安泰街49巷1號1樓', '02-2631-2428', '0955427405'),
(386, '台北市', '內湖區', '金湖里', '邱金波', '11477臺北市內湖區成功路五段71號1樓', '02-2632-4329', '0926306286'),
(387, '台北市', '內湖區', '康寧里', '鄭秀鳳', '11485臺北市內湖區康寧路三段99巷39弄70號', '02-2633-2488', '0939887465'),
(388, '台北市', '內湖區', '明湖里', '謝明均', '11485臺北市內湖區康寧路三段99巷25號', '02-2632-5326', '0905067041'),
(389, '台北市', '內湖區', '蘆洲里', '陳明霖', '11484臺北市內湖區安康路95號', '02-2792-5574', '0928856630'),
(390, '台北市', '內湖區', '麗山里', '林國榮', '11442臺北市內湖區內湖路一段411巷78號', '02-2627-4625', '0965030560'),
(391, '台北市', '內湖區', '寶湖里', '邱顯松', '11490臺北市內湖區民權東路六段208號', '02-2795-1117', '0955680322'),
(392, '台北市', '內湖區', '南湖里', '洪宜河', '11486臺北市內湖區康寧路三段56巷130號', '02-2632-0538', '0955900998'),
(393, '台北市', '士林區', '仁勇里', '洪銘鎮', '大東路63號2樓', '02-28812220', '0926055186'),
(394, '台北市', '士林區', '義信里', '許立丕', '基河路132號地下1樓', '02-88611910', '0922850657'),
(395, '台北市', '士林區', '福林里', '江美珠', '中正路187巷26號', '02-28821136', '0936606115'),
(396, '台北市', '士林區', '福德里', '盛琳惠', '福德路81巷14號', '02-28839758', '0987357879'),
(397, '台北市', '士林區', '福志里', '邱邱田', '中正路104巷2號', '02-28349568', '0972168118'),
(398, '台北市', '士林區', '舊佳里', '陳明松', '中山北路5段773之2號', '02-28321958', '0937903104'),
(399, '台北市', '士林區', '福佳里', '許木春', '美崙街190號', NULL, '0983049957'),
(400, '台北市', '士林區', '後港里', '紀建漢', '大南路423號', '02-28807588', '0955886356'),
(401, '台北市', '士林區', '福中里', '王德利', '福港街151號9樓', '02-28838207', '0932305183'),
(402, '台北市', '士林區', '前港里', '陳瑞華', '大南路251巷18號1樓', '02-28829686', '0916058978'),
(403, '台北市', '士林區', '百齡里', '翁淑穎', '福港街259巷13弄10號1樓', '02-28802985', '0935966272'),
(404, '台北市', '士林區', '承德里', '陳洲平', '劍潭路80號', '02-28859257', '0928225383'),
(405, '台北市', '士林區', '福華里', '楊小莉', '和豐街36號1樓', '02-28838325', '0936986345'),
(406, '台北市', '士林區', '明勝里', '張永棟', '承德路4段5之1號', '02-28855911', '0933920380'),
(407, '台北市', '士林區', '福順里', '林家卉', '延平北路5段136巷24號', '02-28160989', '0958830605'),
(408, '台北市', '士林區', '富光里', '高毓紋', '葫蘆街62號1樓', '02-28160879', '0909092282'),
(409, '台北市', '士林區', '葫蘆里', '許振禮', '葫蘆街137號1樓', '02-28102336', '0958972685'),
(410, '台北市', '士林區', '葫東里', '郭淑玲', '重慶北路4段215號', '02-88113989', '0933716265'),
(411, '台北市', '士林區', '社子里', '陳明雄', '社正路11號2樓', '02-28111133', '0963226526'),
(412, '台北市', '士林區', '社新里', '陳淑玲', '社子街78號', '02-28114458', '0927272285'),
(413, '台北市', '士林區', '社園里', '陳飛熊', '社中街279號', '02-28163389', '0928233559'),
(414, '台北市', '士林區', '永倫里', '石林麗惠', '永平街1號1樓', '02-28128208', '0982752527'),
(415, '台北市', '士林區', '福安里', '謝文加', '延平北路7段107巷10號', '02-28110838', '0932353134'),
(416, '台北市', '士林區', '富洲里', '陳惠民', '延平北路8段109號', '02-28161629', '0988538777'),
(417, '台北市', '士林區', '岩山里', '林美雯', '芝玉路1段197巷1號', '02-28317790', '0935807277'),
(418, '台北市', '士林區', '名山里', '薛群秀', '雨聲街68號', '02-28366501', '0936466316'),
(419, '台北市', '士林區', '聖山里', '吳三勇', '德行東路190巷6弄2號1樓', '02-28323118', '0937809009'),
(420, '台北市', '士林區', '芝山里', '魏雅郁', '德行東路308號', '02-28366179', '0939074659'),
(421, '台北市', '士林區', '東山里', '陳文賢', '士東路320巷2號', '02-28312348', '0986666969'),
(422, '台北市', '士林區', '忠誠里', '曾坤來', '德行東路74巷15弄1號1樓', '02-28310943', '0988269345'),
(423, '台北市', '士林區', '德行里', '張僡美', '德行西路30號', '02-88662865', '0939931903'),
(424, '台北市', '士林區', '德華里', '林明儀(代)', '中正路439號9樓', '02-28826200', '0972295827'),
(425, '台北市', '士林區', '蘭雅里', '萬天榮', '中山北路6段290巷7弄25號1樓', '02-28322021', '0939710597'),
(426, '台北市', '士林區', '蘭興里', '林文龍', '福華路180號2樓', '02-88664298', '0935389389'),
(427, '台北市', '士林區', '三玉里', '羅志傑', '士東路200巷62號', '02-28315689', '0937022969'),
(428, '台北市', '士林區', '天福里', '江啓南', '天母東路8巷31弄6號1樓', '02-28725515', '0928118653'),
(429, '台北市', '士林區', '天祿里', '李錦琿', '中山北路6段728巷8號1樓', '02-28718465', '0911729171'),
(430, '台北市', '士林區', '天壽里', '陳伯同', '中山北路6段405巷50號2樓', '02-28716832', '0955088580'),
(431, '台北市', '士林區', '天和里', '蔡明松', '中山北路7段154巷6號4樓', '02-28754030', '0937022800'),
(432, '台北市', '士林區', '天山里', '陳永鴻', '中山北路7段154巷6號4樓', '02-28723220', '0919318119'),
(433, '台北市', '士林區', '天玉里', '張心怡(代理)', '中正路439號9樓', '02-28826200', '0972295840'),
(434, '台北市', '士林區', '天母里', '李陳菜蓮', '中山北路7段191巷23號', '02-28715077', '0922805985'),
(435, '台北市', '士林區', '永福里', '林國柱', '莊頂路113號隔壁', '02-28611288', '0958596558'),
(436, '台北市', '士林區', '公館里', '葉進財', '永公路412號之1', '02-28618987', '0976955282'),
(437, '台北市', '士林區', '新安里', '陳韋甫', '仰德大道3段106巷21號', '02-28610562', '0958275999'),
(438, '台北市', '士林區', '陽明里', '黃裕倉', '菁山路34巷1號', '02-28618717', '0920756571'),
(439, '台北市', '士林區', '菁山里', '何德寬', '菁山路101巷34號', '02-28611717', '0937851848'),
(440, '台北市', '士林區', '平等里', '徐明忠', '平菁街106巷18號', '02-28616899', '0919312369'),
(441, '台北市', '士林區', '溪山里', '黃慧芬', '至善路3段258號旁', '02-28413687', '0933898089'),
(442, '台北市', '士林區', '翠山里', '陳之貴', '中社路1段36巷2之1號', '02-28412197', '0922626557'),
(443, '台北市', '士林區', '臨溪里', '郭肇富', '至善路2段149號', '02-28819429', '0932240702'),
(444, '台北市', '北投區', '建民里', '簡妤年', '112023臺北市北投區文林北路36號', '02-2828-2580', '0906-905-025'),
(445, '台北市', '北投區', '文林里', '李珮緁', '112051臺北市北投區致遠一路1段9號1樓', '02-2823-1156', '0905-717-866'),
(446, '台北市', '北投區', '石牌里', '洪美惠', '112048臺北市北投區建民路116巷13號1樓', '02-2820-1369', '0921-895-893'),
(447, '台北市', '北投區', '福興里', '蔡柳池', '112050臺北市北投區自強街21號1樓', '02-2823-9721', '0932-201-491'),
(448, '台北市', '北投區', '榮光里', '蘇寶鈴', '112052臺北市北投區石牌路一段166巷43弄15號', '02-2823-1593', '0910-911-378'),
(449, '台北市', '北投區', '榮華里', '何漢清', '112038臺北市北投區明德路136巷16號1樓', '02-2821-6350', '0932-251-780'),
(450, '台北市', '北投區', '裕民里', '莊振寧', '112041臺北市北投區懷德街14巷17號一樓', '02-2826-1139', '0932-252-523'),
(451, '台北市', '北投區', '振華里', '郭崇儀', '112042臺北市北投區振華街15號1樓', '02-2823-6110', '0922-843-121'),
(452, '台北市', '北投區', '永和里', '曾富榆', '112043臺北市北投區行義路63號', '02-2875-3298', '0928-868-298'),
(453, '台北市', '北投區', '永欣里', '潘萬生', '112043臺北市北投區石牌路二段334-1號1樓', '02-2874-5213', '0932-257-559'),
(454, '台北市', '北投區', '永明里', '何麗玲', '112026臺北市北投區義理街65號', '02-2821-3214', '0920-646-338'),
(455, '台北市', '北投區', '東華里', '袁大祥', '112025臺北市北投區致遠三路158號2樓', '02-2822-6937', '0955-825-788'),
(456, '台北市', '北投區', '吉利里', '劉序剛', '112031臺北市北投區石牌路一段39巷120弄7號', '02-2828-1088', '0933-881-532'),
(457, '台北市', '北投區', '吉慶里', '黃勝宗', '112034臺北市北投區致遠二路61巷2-2號', '02-2827-5233', '0936-222-517'),
(458, '台北市', '北投區', '尊賢里', '張勻通', '112023臺北市北投區尊賢街302巷19號', '02-2827-0945', '0927-955-059'),
(459, '台北市', '北投區', '立賢里', '邱福銀', '112036臺北市北投區尊賢街249巷23號', '02-2828-2688', '0932-079-096'),
(460, '台北市', '北投區', '立農里', '潘建榮', '112032臺北市北投區立農街一段319號', '02-2826-2408', '0923-103-747'),
(461, '台北市', '北投區', '八仙里', '黃永清', '112030臺北市北投區北投路一段臨20號', '02-2898-5252', '0920-228-823'),
(462, '台北市', '北投區', '洲美里', '陳照梅', '112037臺北市北投區福美路203號', '02-2828-9798', '0933-765-939'),
(463, '台北市', '北投區', '奇岩里', '賴進利', '112005臺北市北投區公舘路249之1號', '02-2896-2888', '0937-023-674'),
(464, '台北市', '北投區', '清江里', '李炳亷', '112024臺北市北投區清江路201號', '02-2892-2543', '0939-847-928'),
(465, '台北市', '北投區', '中央里', '陳國樑', '112030臺北市北投區北投路一段93巷3號', '02-2892-4106', '0937-023-654'),
(466, '台北市', '北投區', '長安里', '陳章生', '112009臺北市北投區育仁路111巷9號', '02-2893-3157', '0939-611-019'),
(467, '台北市', '北投區', '大同里', '陳文鈴', '112009臺北市北投區大同街133號', '02-2894-0356', '0932-355-806'),
(468, '台北市', '北投區', '溫泉里', '許智全', '112023臺北市北投區溫泉路68巷6弄15之2號', '02-2897-3645', '0928-770-878'),
(469, '台北市', '北投區', '林泉里', '陳惠華', '112003臺北市北投區中心街24號', '02-2897-8157', '0958-904-106'),
(470, '台北市', '北投區', '中心里', '陳力彰', '112028臺北市北投區珠海路15號', '02-2896-0063', '0927-764-370'),
(471, '台北市', '北投區', '中庸里', '藍靜莉', '112013臺北市北投區永興路1段12號', '02-2897-5299', '0926-060-018'),
(472, '台北市', '北投區', '開明里', '左麗芳', '112011臺北市北投區永興路二段8號', '02-2893-2400', '0912-818-252'),
(473, '台北市', '北投區', '中和里', '黃景煌', '112011臺北市北投區中和街365號', '02-2894-2200', '0919-210-306'),
(474, '台北市', '北投區', '智仁里', '許世傳', '112010臺北市北投區杏林二路74巷3號', '02-2895-9722', '0932-141-019'),
(475, '台北市', '北投區', '秀山里', '陳壽彭', '112023臺北市北投區中和街502巷15號', '02-2894-1666', '0937-022-555'),
(476, '台北市', '北投區', '文化里', '周世英', '112053臺北市北投區文化三路55號1樓', '02-2891-7488', '0952-914-338'),
(477, '台北市', '北投區', '豐年里', '周江雪珠', '112061臺北市北投區中央北路二段95巷1弄1號', '02-2891-7441', '0928-030-047'),
(478, '台北市', '北投區', '稻香里', '黃永瀚(代理)', '112016臺北市北投區稻香路81號6樓之2', '02-2894-1818', '0909-804-597'),
(479, '台北市', '北投區', '桃源里', '陳仲宏', '112023臺北市北投區新興路158巷17號', '02-2893-3131', '0939-669-313'),
(480, '台北市', '北投區', '一德里', '黃玉樹', '112055臺北市北投區中央北路四段538號2樓', '02-2897-2600', '0973-717-772'),
(481, '台北市', '北投區', '關渡里', '張淑綢', '112055臺北市北投區知行路316巷20弄15號', '02-2858-1878', '0989-006-537'),
(482, '台北市', '北投區', '泉源里', '陳志成', '112007臺北市北投區泉源路242號', '02-2891-9539', '0920-883-960'),
(483, '台北市', '北投區', '湖山里', '李秋霞', '112091臺北市北投區湖山路一段48之2號', '02-2862-0288', '0932-923-070'),
(484, '台北市', '北投區', '大屯里', '張天恩', '112008臺北市北投區復興三路521巷18號', '02-2891-1750', '0985-325-333'),
(485, '台北市', '北投區', '湖田里', '曹昌正', '112092臺北市北投區竹子湖路35之1號', '02-2862-8787', '0932-137-123');

-- --------------------------------------------------------

--
-- 資料表結構 `youbike`
--

CREATE TABLE `youbike` (
  `station_id` varchar(20) NOT NULL COMMENT 'YouBike官方站點編號(主鍵)',
  `city_name` varchar(20) NOT NULL COMMENT '縣市(例如：臺中市、臺北市、新北市)',
  `sarea` varchar(50) NOT NULL COMMENT '行政區(例如：大甲區、大安區、板橋區)',
  `station_name_tw` varchar(100) NOT NULL COMMENT '中文站點名稱',
  `lat` decimal(10,8) NOT NULL COMMENT '緯度',
  `lng` decimal(11,8) NOT NULL COMMENT '經度',
  `address` varchar(255) DEFAULT NULL COMMENT '詳細地址',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '系統首次偵測並建立時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='YouBike站點基本資料表';

-- --------------------------------------------------------

--
-- 檢視表結構 `account_members_view`
--
DROP TABLE IF EXISTS `account_members_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `account_members_view`  AS SELECT `a`.`id` AS `account_id`, `a`.`new_member` AS `account_new_member`, `a`.`old_member` AS `account_old_member`, `a`.`name` AS `account_name`, `a`.`gender` AS `account_gender`, `a`.`email` AS `account_email`, `a`.`password` AS `password`, `a`.`role` AS `role`, `a`.`join_date` AS `join_date`, `a`.`status` AS `account_status`, `a`.`discontinued_date` AS `discontinued_date`, `a`.`remarks` AS `account_remarks`, `a`.`menu_id` AS `menu_id`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `m`.`id` AS `member_id`, `m`.`receive_date` AS `receive_date`, `m`.`old_member` AS `member_old_member`, `m`.`new_member` AS `member_new_member`, `m`.`generation` AS `generation`, `m`.`emperor_shizu` AS `emperor_shizu`, `m`.`number_of_houses` AS `number_of_houses`, `m`.`name` AS `member_name`, `m`.`gender` AS `member_gender`, `m`.`id_card_num` AS `id_card_num`, `m`.`birthday` AS `birthday`, `m`.`placeOfBirth` AS `placeOfBirth`, `m`.`education` AS `education`, `m`.`experience` AS `experience`, `m`.`address` AS `address`, `m`.`zip_code` AS `zip_code`, `m`.`mobile_phone` AS `mobile_phone`, `m`.`home_phone` AS `home_phone`, `m`.`company_phone` AS `company_phone`, `m`.`email` AS `member_email`, `m`.`introducer` AS `introducer`, `m`.`SendSubordinates` AS `SendSubordinates`, `m`.`living_status` AS `living_status`, `m`.`status` AS `member_status`, `m`.`remarks` AS `member_remarks`, `m`.`update_time` AS `update_time`, `m`.`last_updater` AS `last_updater` FROM (`account` `a` join `members` `m` on(`a`.`id` = `m`.`id` and `a`.`name` = `m`.`name`)) ;

-- --------------------------------------------------------

--
-- 檢視表結構 `member_family_view`
--
DROP TABLE IF EXISTS `member_family_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `member_family_view`  AS SELECT `m`.`id` AS `member_id`, `m`.`new_member` AS `new_member`, `m`.`name` AS `name`, `m`.`gender` AS `gender`, `m`.`birthday` AS `birthday`, `m`.`placeOfBirth` AS `placeOfBirth`, `m`.`address` AS `address`, `m`.`living_status` AS `living_status`, `f`.`father` AS `father`, `f`.`mother` AS `mother`, `f`.`adoptiveFather` AS `adoptiveFather`, `f`.`fosterMother` AS `fosterMother`, `f`.`spouse` AS `spouse`, `f`.`brothers` AS `brothers`, `f`.`sisters` AS `sisters`, `f`.`FamilySituation` AS `FamilySituation`, `f`.`son1` AS `son1`, `f`.`son2` AS `son2`, `f`.`son3` AS `son3`, `f`.`son4` AS `son4`, `f`.`son5` AS `son5`, `f`.`son6` AS `son6`, `f`.`son7` AS `son7`, `f`.`son8` AS `son8`, `f`.`son9` AS `son9`, `f`.`daughter1` AS `daughter1`, `f`.`daughter2` AS `daughter2`, `f`.`daughter3` AS `daughter3`, `f`.`daughter4` AS `daughter4`, `f`.`daughter5` AS `daughter5`, `f`.`daughter6` AS `daughter6`, `f`.`daughter7` AS `daughter7`, `f`.`daughter8` AS `daughter8`, `f`.`daughter9` AS `daughter9`, `m`.`remarks` AS `member_remarks`, `f`.`remarks` AS `family_remarks` FROM (`members` `m` join `family` `f` on(`m`.`new_member` = `f`.`new_member` and `m`.`name` = `f`.`name`)) ;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `bike_logs`
--
ALTER TABLE `bike_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_station_time` (`station_id`,`recorded_at`) COMMENT '加速特定站點的時間範圍查詢';

--
-- 資料表索引 `dev_tracking`
--
ALTER TABLE `dev_tracking`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `family`
--
ALTER TABLE `family`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`);

--
-- 資料表索引 `makeawish`
--
ALTER TABLE `makeawish`
  ADD PRIMARY KEY (`id`,`generation`,`emperor_shizu`,`number_of_houses`);

--
-- 資料表索引 `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `village-chief`
--
ALTER TABLE `village-chief`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `youbike`
--
ALTER TABLE `youbike`
  ADD PRIMARY KEY (`station_id`),
  ADD KEY `idx_city_sarea` (`city_name`,`sarea`) COMMENT '加速縣市與行政區的篩選查詢';

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `bike_logs`
--
ALTER TABLE `bike_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '流水號(主鍵)';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `dev_tracking`
--
ALTER TABLE `dev_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `family`
--
ALTER TABLE `family`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '流水號', AUTO_INCREMENT=61;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `files`
--
ALTER TABLE `files`
  MODIFY `file_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '檔案編號', AUTO_INCREMENT=92;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '流水號', AUTO_INCREMENT=61;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `bike_logs`
--
ALTER TABLE `bike_logs`
  ADD CONSTRAINT `fk_station_id` FOREIGN KEY (`station_id`) REFERENCES `youbike` (`station_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
