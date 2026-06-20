-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2026 at 02:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `pam`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `name` int(11) NOT NULL,
  `deposite_scheme` int(11) NOT NULL,
  `deposite_date` date NOT NULL,
  `renewal_date` date NOT NULL,
  `period` int(11) NOT NULL,
  `period_type` enum('d','m','y') NOT NULL,
  `maturity_date` date NOT NULL,
  `rate_of_interest` float NOT NULL,
  `interest_type` int(11) NOT NULL DEFAULT 1,
  `deposite_amount` float NOT NULL,
  `total_interest` float NOT NULL,
  `maturity_amount` float NOT NULL,
  `is_active` int(11) NOT NULL,
  `ref_id` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `accounts_history`
--

CREATE TABLE `accounts_history` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `ref_id` varchar(200) NOT NULL,
  `name` varchar(100) NOT NULL,
  `deposite_scheme` int(11) NOT NULL,
  `deposite_date` date NOT NULL,
  `renewal_date` date NOT NULL,
  `period` int(11) NOT NULL,
  `period_type` enum('d','m','y') NOT NULL,
  `maturity_date` date NOT NULL,
  `rate_of_interest` float NOT NULL,
  `interest_type` int(11) NOT NULL DEFAULT 0,
  `deposite_amount` float NOT NULL,
  `total_interest` float NOT NULL,
  `maturity_amount` float NOT NULL,
  `action` varchar(255) DEFAULT NULL,
  `closed_date` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `acc_users`
--

CREATE TABLE `acc_users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` enum('y','n') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `auth_users`
--

CREATE TABLE `auth_users` (
  `id` int(11) NOT NULL,
  `Firstname` varchar(20) NOT NULL,
  `Lastname` varchar(20) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(50) NOT NULL,
  `is_super` enum('0','1') NOT NULL,
  `is_active` enum('0','1') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `bank_fd_summary`
-- (See below for the actual view)
--
CREATE TABLE `bank_fd_summary` (
`name` varchar(100)
,`scheme_name` varchar(20)
,`ref_id` varchar(100)
,`deposite_date` date
,`renewal_date` date
,`period` int(11)
,`period_type` enum('d','m','y')
,`maturity_date` date
,`rate_of_interest` float
,`deposite_amount` float
,`total_interest` float
,`maturity_amount` float
,`is_active` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `check_this_month_details`
-- (See below for the actual view)
--
CREATE TABLE `check_this_month_details` (
`name` varchar(100)
,`scheme_name` varchar(20)
,`ref_id` varchar(100)
,`deposite_date` date
,`renewal_date` date
,`maturity_date` date
,`period` int(11)
,`period_type` enum('d','m','y')
,`rate_of_interest` float
,`deposite_amount` float
,`total_interest` float
,`maturity_amount` float
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `current_amount_status`
-- (See below for the actual view)
--
CREATE TABLE `current_amount_status` (
`deposites` varchar(417)
,`interest` varchar(417)
,`maturity` varchar(417)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `datewise_amount_details`
-- (See below for the actual view)
--
CREATE TABLE `datewise_amount_details` (
`maturity_date` date
,`deposite_amount` double
,`maturity_amount` double
,`total_interest` double
,`total_fds` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `datewise_fd_plan`
-- (See below for the actual view)
--
CREATE TABLE `datewise_fd_plan` (
`maturity_date` date
,`deposite_amount` double
,`maturity_amount` double
);

-- --------------------------------------------------------

--
-- Table structure for table `deposite_schemes`
--

CREATE TABLE `deposite_schemes` (
  `id` int(11) NOT NULL,
  `scheme_name` varchar(20) NOT NULL,
  `is_active` enum('y','n') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Stand-in structure for view `interestwise_details`
-- (See below for the actual view)
--
CREATE TABLE `interestwise_details` (
`roi` double(19,2)
,`total_fds` bigint(21)
,`deposite_amount` double(19,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `interestwise_details_new`
-- (See below for the actual view)
--
CREATE TABLE `interestwise_details_new` (
`roi` double(19,2)
,`total_fds` bigint(21)
,`round(deposite_amount, 1)` double(18,1)
);

-- --------------------------------------------------------

--
-- Table structure for table `interest_period`
--

CREATE TABLE `interest_period` (
  `id` int(11) NOT NULL,
  `scheme` int(11) NOT NULL,
  `user_type` enum('0','1') NOT NULL DEFAULT '0',
  `start` int(11) NOT NULL,
  `end` int(11) NOT NULL,
  `interest_rate` float NOT NULL,
  `comments` varchar(200) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_interest_received`
-- (See below for the actual view)
--
CREATE TABLE `monthly_interest_received` (
`maturity_month` varchar(7)
,`total_fds` bigint(21)
,`total_deposites` double(17,0)
,`total_interest` double(17,0)
,`total_maturity` double(17,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_investment`
--

CREATE TABLE `monthly_investment` (
  `id` int(11) NOT NULL,
  `month` varchar(8) NOT NULL,
  `excepted` int(11) NOT NULL,
  `actual` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthwise_amount_details`
-- (See below for the actual view)
--
CREATE TABLE `monthwise_amount_details` (
`month` varchar(37)
,`deposite_amount` double
,`maturity_amount` double
,`total_interest` double
,`total_fds` decimal(42,0)
,`days` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `name_wise_details`
-- (See below for the actual view)
--
CREATE TABLE `name_wise_details` (
`name` varchar(100)
,`deposite_amount` double
,`total_interest` double
,`maturity_amount` double
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `schemewise_amount_details`
-- (See below for the actual view)
--
CREATE TABLE `schemewise_amount_details` (
`scheme_name` varchar(20)
,`deposite_amount` double
,`total_interest` double
,`maturity_amount` double
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `scheme_wise_details`
-- (See below for the actual view)
--
CREATE TABLE `scheme_wise_details` (
`name` varchar(100)
,`scheme_name` varchar(20)
,`deposite_amount` double
,`total_interest` double
,`maturity_amount` double
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `scheme_wise_details_active`
-- (See below for the actual view)
--
CREATE TABLE `scheme_wise_details_active` (
`name` varchar(100)
,`scheme_name` varchar(20)
,`deposite_amount` double
,`total_interest` double
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `scheme_wise_details_active_new`
-- (See below for the actual view)
--
CREATE TABLE `scheme_wise_details_active_new` (
`name` varchar(100)
,`scheme_name` varchar(20)
,`deposite_amount` double(18,1)
,`total_interest` double(18,1)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `upcoming_bank_fd_maturities`
-- (See below for the actual view)
--
CREATE TABLE `upcoming_bank_fd_maturities` (
`DATE_FORMAT(maturity_date, '%m-%Y')` varchar(7)
,`scheme_name` varchar(20)
,`sum(deposite_amount)` double
);

-- --------------------------------------------------------

--
-- Structure for view `bank_fd_summary`
--
DROP TABLE IF EXISTS `bank_fd_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `bank_fd_summary`  AS SELECT `u`.`name` AS `name`, `ds`.`scheme_name` AS `scheme_name`, `a`.`ref_id` AS `ref_id`, `a`.`deposite_date` AS `deposite_date`, `a`.`renewal_date` AS `renewal_date`, `a`.`period` AS `period`, `a`.`period_type` AS `period_type`, `a`.`maturity_date` AS `maturity_date`, `a`.`rate_of_interest` AS `rate_of_interest`, `a`.`deposite_amount` AS `deposite_amount`, `a`.`total_interest` AS `total_interest`, `a`.`maturity_amount` AS `maturity_amount`, `a`.`is_active` AS `is_active` FROM ((`accounts` `a` join `deposite_schemes` `ds` on(`ds`.`id` = `a`.`deposite_scheme`)) join `acc_users` `u` on(`u`.`id` = `a`.`name`)) WHERE `a`.`maturity_date` between '2026-04-01' and '2028-03-31' AND `a`.`deposite_scheme` not in (22,24,25) AND `a`.`deposite_amount` > 0 ORDER BY `a`.`maturity_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `check_this_month_details`
--
DROP TABLE IF EXISTS `check_this_month_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `check_this_month_details`  AS SELECT `u`.`name` AS `name`, `d`.`scheme_name` AS `scheme_name`, `a`.`ref_id` AS `ref_id`, `a`.`deposite_date` AS `deposite_date`, `a`.`renewal_date` AS `renewal_date`, `a`.`maturity_date` AS `maturity_date`, `a`.`period` AS `period`, `a`.`period_type` AS `period_type`, `a`.`rate_of_interest` AS `rate_of_interest`, `a`.`deposite_amount` AS `deposite_amount`, `a`.`total_interest` AS `total_interest`, `a`.`maturity_amount` AS `maturity_amount` FROM ((`accounts` `a` join `acc_users` `u` on(`u`.`id` = `a`.`name`)) join `deposite_schemes` `d` on(`d`.`id` = `a`.`deposite_scheme`)) WHERE `a`.`maturity_date` between '2026-05-01' and '2026-05-31' ORDER BY `a`.`maturity_date` ASC, `u`.`name` ASC, `d`.`scheme_name` ASC, `a`.`maturity_amount` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `current_amount_status`
--
DROP TABLE IF EXISTS `current_amount_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `current_amount_status`  AS SELECT format(sum(`a`.`deposite_amount`),2) AS `deposites`, format(sum(`a`.`total_interest`),2) AS `interest`, format(sum(`a`.`maturity_amount`),2) AS `maturity` FROM (`accounts` `a` join `deposite_schemes` `s` on(`a`.`deposite_scheme` = `s`.`id`)) WHERE `s`.`is_active` = 'y' ;

-- --------------------------------------------------------

--
-- Structure for view `datewise_amount_details`
--
DROP TABLE IF EXISTS `datewise_amount_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `datewise_amount_details`  AS SELECT `accounts`.`maturity_date` AS `maturity_date`, sum(`accounts`.`deposite_amount`) AS `deposite_amount`, sum(`accounts`.`maturity_amount`) AS `maturity_amount`, sum(`accounts`.`total_interest`) AS `total_interest`, count(1) AS `total_fds` FROM `accounts` WHERE `accounts`.`is_active` = 1 GROUP BY `accounts`.`maturity_date` ORDER BY `accounts`.`maturity_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `datewise_fd_plan`
--
DROP TABLE IF EXISTS `datewise_fd_plan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `datewise_fd_plan`  AS SELECT `datewise_amount_details`.`maturity_date` AS `maturity_date`, `datewise_amount_details`.`deposite_amount` AS `deposite_amount`, `datewise_amount_details`.`maturity_amount` AS `maturity_amount` FROM `datewise_amount_details` WHERE `datewise_amount_details`.`maturity_date` >= current_timestamp() ORDER BY `datewise_amount_details`.`maturity_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `interestwise_details`
--
DROP TABLE IF EXISTS `interestwise_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `interestwise_details`  AS SELECT round(`a`.`rate_of_interest`,2) AS `roi`, count(0) AS `total_fds`, round(sum(`a`.`deposite_amount`),2) AS `deposite_amount` FROM (`accounts` `a` join `deposite_schemes` `d` on(`d`.`id` = `a`.`deposite_scheme`)) WHERE `d`.`is_active` = 'y' AND `a`.`is_active` = 1 AND `a`.`renewal_date` <= current_timestamp() GROUP BY round(`a`.`rate_of_interest`,2) ORDER BY round(`a`.`rate_of_interest`,2) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `interestwise_details_new`
--
DROP TABLE IF EXISTS `interestwise_details_new`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `interestwise_details_new`  AS SELECT `interestwise_details`.`roi` AS `roi`, `interestwise_details`.`total_fds` AS `total_fds`, round(`interestwise_details`.`deposite_amount`,1) AS `round(deposite_amount, 1)` FROM `interestwise_details` ;

-- --------------------------------------------------------

--
-- Structure for view `monthly_interest_received`
--
DROP TABLE IF EXISTS `monthly_interest_received`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthly_interest_received`  AS SELECT date_format(`accounts_history`.`maturity_date`,'%Y-%m') AS `maturity_month`, count(0) AS `total_fds`, round(sum(`accounts_history`.`deposite_amount`),0) AS `total_deposites`, round(sum(`accounts_history`.`total_interest`),0) AS `total_interest`, round(sum(`accounts_history`.`maturity_amount`),0) AS `total_maturity` FROM `accounts_history` WHERE `accounts_history`.`maturity_date` <= current_timestamp() GROUP BY date_format(`accounts_history`.`maturity_date`,'%Y-%m') ORDER BY date_format(`accounts_history`.`maturity_date`,'%Y-%m') DESC LIMIT 0, 12 ;

-- --------------------------------------------------------

--
-- Structure for view `monthwise_amount_details`
--
DROP TABLE IF EXISTS `monthwise_amount_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthwise_amount_details`  AS SELECT date_format(`datewise_amount_details`.`maturity_date`,'%b-%Y') AS `month`, sum(`datewise_amount_details`.`deposite_amount`) AS `deposite_amount`, sum(`datewise_amount_details`.`maturity_amount`) AS `maturity_amount`, sum(`datewise_amount_details`.`total_interest`) AS `total_interest`, sum(`datewise_amount_details`.`total_fds`) AS `total_fds`, count(1) AS `days` FROM `datewise_amount_details` GROUP BY date_format(`datewise_amount_details`.`maturity_date`,'%b-%Y') ORDER BY str_to_date(concat('01-',`month`),'%d-%b-%Y') ASC ;

-- --------------------------------------------------------

--
-- Structure for view `name_wise_details`
--
DROP TABLE IF EXISTS `name_wise_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `name_wise_details`  AS SELECT `u`.`name` AS `name`, sum(`a`.`deposite_amount`) AS `deposite_amount`, sum(`a`.`total_interest`) AS `total_interest`, sum(`a`.`maturity_amount`) AS `maturity_amount` FROM (`accounts` `a` join `acc_users` `u` on(`u`.`id` = `a`.`name`)) GROUP BY `u`.`name` ORDER BY `u`.`name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `schemewise_amount_details`
--
DROP TABLE IF EXISTS `schemewise_amount_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `schemewise_amount_details`  AS SELECT `scheme_wise_details`.`scheme_name` AS `scheme_name`, sum(`scheme_wise_details`.`deposite_amount`) AS `deposite_amount`, sum(`scheme_wise_details`.`total_interest`) AS `total_interest`, sum(`scheme_wise_details`.`maturity_amount`) AS `maturity_amount` FROM `scheme_wise_details` GROUP BY `scheme_wise_details`.`scheme_name` ;

-- --------------------------------------------------------

--
-- Structure for view `scheme_wise_details`
--
DROP TABLE IF EXISTS `scheme_wise_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `scheme_wise_details`  AS SELECT `u`.`name` AS `name`, `s`.`scheme_name` AS `scheme_name`, sum(`a`.`deposite_amount`) AS `deposite_amount`, sum(`a`.`total_interest`) AS `total_interest`, sum(`a`.`maturity_amount`) AS `maturity_amount` FROM ((`accounts` `a` join `acc_users` `u` on(`u`.`id` = `a`.`name`)) join `deposite_schemes` `s` on(`s`.`id` = `a`.`deposite_scheme`)) WHERE `a`.`renewal_date` < current_timestamp() GROUP BY `u`.`name`, `a`.`deposite_scheme` ORDER BY `s`.`scheme_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `scheme_wise_details_active`
--
DROP TABLE IF EXISTS `scheme_wise_details_active`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `scheme_wise_details_active`  AS SELECT `scheme_wise_details`.`name` AS `name`, `scheme_wise_details`.`scheme_name` AS `scheme_name`, `scheme_wise_details`.`deposite_amount` AS `deposite_amount`, `scheme_wise_details`.`total_interest` AS `total_interest` FROM (`scheme_wise_details` join `deposite_schemes` on(`deposite_schemes`.`scheme_name` = `scheme_wise_details`.`scheme_name`)) WHERE `deposite_schemes`.`is_active` = 'y' ORDER BY `scheme_wise_details`.`name` ASC, `scheme_wise_details`.`scheme_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `scheme_wise_details_active_new`
--
DROP TABLE IF EXISTS `scheme_wise_details_active_new`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `scheme_wise_details_active_new`  AS SELECT `scheme_wise_details_active`.`name` AS `name`, `scheme_wise_details_active`.`scheme_name` AS `scheme_name`, round(`scheme_wise_details_active`.`deposite_amount`,1) AS `deposite_amount`, round(`scheme_wise_details_active`.`total_interest`,1) AS `total_interest` FROM `scheme_wise_details_active` ;

-- --------------------------------------------------------

--
-- Structure for view `upcoming_bank_fd_maturities`
--
DROP TABLE IF EXISTS `upcoming_bank_fd_maturities`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `upcoming_bank_fd_maturities`  AS SELECT date_format(`a`.`maturity_date`,'%m-%Y') AS `DATE_FORMAT(maturity_date, '%m-%Y')`, `s`.`scheme_name` AS `scheme_name`, sum(`a`.`deposite_amount`) AS `sum(deposite_amount)` FROM (`accounts` `a` join `deposite_schemes` `s` on(`s`.`id` = `a`.`deposite_scheme`)) WHERE `a`.`deposite_amount` > 0 AND `a`.`deposite_scheme` not in (4,22,24,25) GROUP BY date_format(`a`.`maturity_date`,'%m-%Y'), `s`.`scheme_name` ORDER BY `a`.`maturity_date` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_acc_users` (`name`);

--
-- Indexes for table `accounts_history`
--
ALTER TABLE `accounts_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `acc_users`
--
ALTER TABLE `acc_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auth_users`
--
ALTER TABLE `auth_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposite_schemes`
--
ALTER TABLE `deposite_schemes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `interest_period`
--
ALTER TABLE `interest_period`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_investment`
--
ALTER TABLE `monthly_investment`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accounts_history`
--
ALTER TABLE `accounts_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acc_users`
--
ALTER TABLE `acc_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_users`
--
ALTER TABLE `auth_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposite_schemes`
--
ALTER TABLE `deposite_schemes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interest_period`
--
ALTER TABLE `interest_period`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monthly_investment`
--
ALTER TABLE `monthly_investment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
