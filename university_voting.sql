-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2026 at 03:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `university_voting`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `achievement` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `candidateID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `achievements`
--

INSERT INTO `achievements` (`id`, `achievement`, `description`, `candidateID`) VALUES
(7, 'Student Council Officer', 'Served as class representative for two consecutive academic years.', 3),
(8, 'Leadership Seminar Graduate', 'Completed university leadership and governance training program.', 3),
(9, 'Dean\'s Lister', 'Maintained academic excellence while actively participating in campus activities.', 4),
(10, 'Organization President', 'Led a university-wide student organization with 200+ members.', 4),
(11, 'Community Outreach Volunteer', 'Organized barangay literacy programs and university outreach drives.', 5),
(12, 'Event Coordinator', 'Managed logistics for major university events and student assemblies.', 5),
(13, 'Student Council Officer', 'Served as class representative for two consecutive academic years.', 6),
(14, 'Leadership Seminar Graduate', 'Completed university leadership and governance training program.', 6),
(15, 'Dean\'s Lister', 'Maintained academic excellence while actively participating in campus activities.', 7),
(16, 'Organization President', 'Led a university-wide student organization with 200+ members.', 7),
(17, 'Community Outreach Volunteer', 'Organized barangay literacy programs and university outreach drives.', 8),
(18, 'Event Coordinator', 'Managed logistics for major university events and student assemblies.', 8),
(19, 'Student Council Officer', 'Served as class representative for two consecutive years.', 9),
(20, 'Leadership Seminar Graduate', 'Completed university leadership and governance training.', 9);

-- --------------------------------------------------------

--
-- Table structure for table `candidateinfo`
--

CREATE TABLE `candidateinfo` (
  `id` int(11) NOT NULL,
  `profilePicture` varchar(500) DEFAULT NULL,
  `platform` longtext DEFAULT NULL,
  `partylist` varchar(100) DEFAULT NULL,
  `position` enum('President','Vice President','Secretary','Treasurer','Auditor') NOT NULL,
  `status` enum('approved','rejected','pending') NOT NULL DEFAULT 'pending',
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `userID` int(11) NOT NULL,
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidateinfo`
--

INSERT INTO `candidateinfo` (`id`, `profilePicture`, `platform`, `partylist`, `position`, `status`, `createdAt`, `updatedAt`, `userID`, `documents`) VALUES
(3, NULL, 'Lead with transparency and inclusive student governance. I will strengthen student services, open budget discussions, and create regular town halls so every voice is heard.', 'Progressive Alliance', 'President', 'approved', '2026-05-31 20:06:58', '2026-05-31 20:06:58', 11, NULL),
(4, NULL, 'Support the president in delivering meaningful campus reforms while coordinating student organizations and ensuring accountability across all student council initiatives.', 'Unity Party', 'Vice President', 'approved', '2026-05-31 20:06:58', '2026-05-31 20:06:58', 8, NULL),
(5, NULL, 'Keep accurate records, improve communication between the student body and council, and streamline document access for all university organizations.', 'Student Reform Coalition', 'Secretary', 'approved', '2026-05-31 20:06:58', '2026-05-31 20:06:58', 12, NULL),
(6, NULL, 'Ensure responsible fund management with clear reporting, fair allocation of resources, and student-led oversight of all council expenditures.', 'Progressive Alliance', 'Treasurer', 'approved', '2026-05-31 20:06:58', '2026-05-31 20:06:58', 13, NULL),
(7, NULL, 'Promote fiscal accountability through regular audits, transparent reporting, and strict compliance with university financial policies.', 'Independent', 'Auditor', 'approved', '2026-05-31 20:06:58', '2026-05-31 20:06:58', 9, NULL),
(8, NULL, 'Lead with transparency and inclusive student governance. I will strengthen student services, open budget discussions, and create regular town halls so every voice is heard.', 'Unity Party', 'President', 'approved', '2026-05-31 20:06:58', '2026-05-31 20:06:58', 14, NULL),
(9, NULL, 'Lead with transparency and inclusive student governance. I will strengthen student services, open budget discussions, and create regular town halls so every voice is heard.', 'Progressive Alliance', 'President', 'approved', '2026-05-31 20:18:58', '2026-05-31 20:18:58', 18, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `studentlist`
--

CREATE TABLE `studentlist` (
  `id` int(11) NOT NULL,
  `schoolID` varchar(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `mi` char(1) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `program` varchar(100) NOT NULL,
  `department` enum('College of Accountancy','College of Business Adminstration','College of Teacher Education','College of Arts and Science','College of Tourism and Hospitality Management','College of Computer Science and Engineering') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `studentlist`
--

INSERT INTO `studentlist` (`id`, `schoolID`, `firstname`, `mi`, `lastname`, `suffix`, `program`, `department`) VALUES
(1, '2024-0001', 'Juan', 'S', 'Dela Cruz', NULL, 'Bachelor of Science in Computer Science', 'College of Computer Science and Engineering'),
(2, '2024-0002', 'Maria', 'G', 'Reyes', NULL, 'Bachelor of Science in Business Administration', 'College of Business Adminstration'),
(3, '2024-0003', 'Carlos', 'L', 'Bautista', 'Jr.', 'Bachelor of Arts in Communication', 'College of Arts and Science'),
(4, '2024-0004', 'Anna', 'C', 'Santos', NULL, 'Bachelor of Secondary Education', 'College of Teacher Education'),
(5, '2024-0005', 'Miguel', 'R', 'Fernandez', '', '', ''),
(6, '2024-0006', 'Sofia', 'M', 'Torres', NULL, 'Bachelor of Science in Tourism Management', 'College of Tourism and Hospitality Management'),
(7, '2024-0101', 'Liza', 'A', 'Mendoza', NULL, 'Bachelor of Science in Information Technology', 'College of Computer Science and Engineering'),
(8, '2024-0102', 'Mark', 'D', 'Villanueva', NULL, 'Bachelor of Science in Business Administration', 'College of Business Adminstration'),
(9, '2024-0103', 'Grace', 'P', 'Lim', NULL, 'Bachelor of Arts in Communication', 'College of Arts and Science'),
(10, '2024-0201', 'Demo', 'R', 'Candidate', NULL, 'Bachelor of Science in Information Technology', 'College of Computer Science and Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `mi` char(1) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `roles` enum('candidate','student','admin') NOT NULL DEFAULT 'student',
  `password` varchar(255) NOT NULL,
  `isFirstVote` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `lastLogin` datetime DEFAULT NULL,
  `loginID` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `lastname`, `firstname`, `mi`, `suffix`, `email`, `roles`, `password`, `isFirstVote`, `createdAt`, `lastLogin`, `loginID`) VALUES
(8, 'Reyes', 'Maria', 'G', NULL, 'christianity0213@gmail.com', 'candidate', '$2y$10$rhhsP4p.IWt2rkceIvc3U.rOL4k4ew63q7WR2Avq9.zjVZWFlRiua', 1, '2026-05-23 10:35:43', '2026-05-23 11:12:12', '2024-0002'),
(9, 'Fernandez', 'Miguel', 'R', '', 'fernandez@gmail.com', 'candidate', '$2y$10$cMzTtu8UjYjNAbRF/q/J9eVoukwshlctf2kDmGhSiTk8At./YumDy', 1, '2026-05-31 19:29:07', '2026-05-31 20:41:39', '2024-0005'),
(10, 'Administrator', 'System', NULL, NULL, 'admin@university.edu', 'admin', '$2y$10$zqumWaGoirVgizp4nSWgxO4lqL3n1qrDnDfqnyD1X4WzVwZUGR3TK', 1, '2026-05-31 19:33:55', '2026-05-31 20:58:55', 'admin'),
(11, 'Dela Cruz', 'Juan', 'S', '', 'uanelaruz.20240001@university.edu', 'candidate', '$2y$10$SQiACARFFIgaWY4KRpOlxuEhkCJ5ehANXickrKPKxurXnqAmyZrta', 1, '2026-05-31 20:06:58', '2026-05-31 21:08:43', '2024-0001'),
(12, 'Bautista', 'Carlos', 'L', 'Jr.', 'arlosautista.20240003@university.edu', 'candidate', '$2y$10$fS.VLWWWlc2Y6FjZ0T.CneoCD8BdwSUMXLZmma1PWrHDfVfa1DdXS', 1, '2026-05-31 20:06:58', NULL, '2024-0003'),
(13, 'Santos', 'Anna', 'C', '', 'nnaantos.20240004@university.edu', 'candidate', '$2y$10$D4dcn7UGqfyIYnwwboVdGODaJ3RQIoTnGb6KPMNQ8k9cJ6pWVPzTW', 1, '2026-05-31 20:06:58', NULL, '2024-0004'),
(14, 'Torres', 'Sofia', 'M', '', 'ofiaorres.20240006@university.edu', 'candidate', '$2y$10$/FUpIIgQ.sBYwhZ36J9x1OHi9BdC35JtFp1NoMU7IkmSHnricHNRK', 1, '2026-05-31 20:06:58', NULL, '2024-0006'),
(15, 'Mendoza', 'Liza', 'A', '', 'liza.mendoza@university.edu', 'student', '$2y$10$0yz8B1ufk1Nu0yhVbJzSq.g.22/G0cXRs1rN6VlH4m2EbbvhnVNra', 1, '2026-05-31 20:12:42', NULL, '2024-0101'),
(16, 'Villanueva', 'Mark', 'D', '', 'mark.villanueva@university.edu', 'student', '$2y$10$4mZIpJeCYNsKa5JyJAWVFeh4CgFhO/BfY4qkiPVsec1olKP0HWKSu', 1, '2026-05-31 20:12:42', NULL, '2024-0102'),
(17, 'Lim', 'Grace', 'P', '', 'grace.lim@university.edu', 'student', '$2y$10$RNvlHIZH7cVJFuAc6Y1RXO1qU2sJGiN041gKPdt3ekiLbkDoWa9g6', 1, '2026-05-31 20:12:42', '2026-05-31 21:04:12', '2024-0103'),
(18, 'Candidate', 'Demo', 'R', '', 'candidate@university.edu', 'candidate', '$2y$10$y.lr5bZGEryiQgTTpXGAyOAAonLKG09L1F68M7rTqU.aj0ycbHwKy', 1, '2026-05-31 20:18:58', '2026-05-31 21:05:11', '2024-0201');

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `candidateID` int(11) NOT NULL,
  `votedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `votes`
--

INSERT INTO `votes` (`id`, `userID`, `candidateID`, `votedAt`) VALUES
(1, 17, 3, '2026-05-31 20:15:45'),
(2, 17, 4, '2026-05-31 20:15:45'),
(3, 17, 5, '2026-05-31 20:15:45'),
(4, 17, 6, '2026-05-31 20:15:45'),
(5, 17, 7, '2026-05-31 20:15:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidateID` (`candidateID`);

--
-- Indexes for table `candidateinfo`
--
ALTER TABLE `candidateinfo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userID` (`userID`);

--
-- Indexes for table `studentlist`
--
ALTER TABLE `studentlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `schoolID` (`schoolID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `loginID` (`loginID`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`userID`,`candidateID`),
  ADD KEY `candidateID` (`candidateID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `candidateinfo`
--
ALTER TABLE `candidateinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `studentlist`
--
ALTER TABLE `studentlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achievements`
--
ALTER TABLE `achievements`
  ADD CONSTRAINT `achievements_ibfk_1` FOREIGN KEY (`candidateID`) REFERENCES `candidateinfo` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidateinfo`
--
ALTER TABLE `candidateinfo`
  ADD CONSTRAINT `candidateinfo_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`candidateID`) REFERENCES `candidateinfo` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
