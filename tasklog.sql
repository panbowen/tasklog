SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `invoices` (
`id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rate` float NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `logs` (
`id` int(11) NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'Development',
  `created_by` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `date` date NOT NULL,
  `hours` float NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `projects` (
`id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `bill_to_name` varchar(100) DEFAULT NULL,
  `bill_to_address` varchar(500) DEFAULT NULL,
  `bill_to_department` varchar(100) DEFAULT NULL,
  `bill_to_campus` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
`id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `roles` int(255) unsigned NOT NULL DEFAULT '1',
  `password` varchar(500) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `last_config` varchar(500) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


ALTER TABLE `invoices`
 ADD PRIMARY KEY (`id`), ADD KEY `created_by` (`created_by`,`project_id`), ADD KEY `project_id` (`project_id`);

ALTER TABLE `logs`
 ADD PRIMARY KEY (`id`), ADD KEY `created_by` (`created_by`,`project_id`), ADD KEY `project_id` (`project_id`);

ALTER TABLE `projects`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`), ADD KEY `created_by` (`created_by`), ADD KEY `updated_by` (`updated_by`);

ALTER TABLE `users`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `email` (`email`), ADD KEY `created_by` (`created_by`), ADD KEY `updated_by` (`updated_by`);


ALTER TABLE `invoices`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `projects`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `invoices`
ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`);

ALTER TABLE `logs`
ADD CONSTRAINT `logs_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
ADD CONSTRAINT `logs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `projects`
ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `users`
ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;