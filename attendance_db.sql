-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 26, 2024 at 03:59 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `attendance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `ip_address`, `date`) VALUES
(6, '20200204019', '172.16.100.49', '2024-12-12'),
(7, '20200104141', '172.16.112.12', '2024-12-12'),
(8, '20200204047', '172.16.67.199', '2024-12-12'),
(9, '20200204006', '172.16.101.67', '2024-12-12'),
(10, '20200204023', '172.16.149.157', '2024-12-12'),
(11, '20200204049', '172.16.64.72', '2024-12-12'),
(12, '20200204023', '172.16.64.74', '2024-12-12'),
(13, '20200104063', '172.16.64.61', '2024-12-12'),
(14, '20200204003', '172.16.149.233', '2024-12-12'),
(15, '190104005', '172.16.112.18', '2024-12-12'),
(16, '20200204038', '172.16.64.76', '2024-12-12'),
(17, '180104100', '172.16.64.62', '2024-12-12'),
(18, '20200204042', '172.16.64.63', '2024-12-12'),
(19, '20200204048', '172.16.64.68', '2024-12-12'),
(20, '20200204005', '172.16.149.235', '2024-12-12'),
(21, '190204112', '172.16.130.213', '2024-12-12'),
(22, '20200204037', '172.16.130.145', '2024-12-12'),
(23, '20200204051', '172.16.149.247', '2024-12-12'),
(24, '20200204012', '172.16.67.239', '2024-12-12'),
(25, '20200204027', '172.16.64.84', '2024-12-12'),
(26, '20200204039', '172.16.64.82', '2024-12-12'),
(27, '20200204010', '172.16.61.226', '2024-12-12'),
(28, '20200204032', '172.16.149.238', '2024-12-12'),
(29, '20200204020', '172.16.64.95', '2024-12-12'),
(30, '20200204044', '172.16.149.237', '2024-12-12'),
(31, '20200204024', '172.16.149.246', '2024-12-12'),
(32, '20200204029', '172.16.67.127', '2024-12-12'),
(33, '20200204025', '172.16.67.148', '2024-12-12'),
(36, '20200204041', '172.16.66.199', '2024-12-18'),
(37, '20200204005', '172.16.151.136', '2024-12-18'),
(38, '20200204027', '172.16.66.85', '2024-12-18'),
(39, '20200204037', '172.16.135.213', '2024-12-18'),
(40, '190204112', '172.16.128.88', '2024-12-18'),
(41, '180104100', '172.16.66.65', '2024-12-18'),
(42, '190104005', '172.16.66.253', '2024-12-18'),
(43, '20200204018', '172.16.151.181', '2024-12-18'),
(44, '20200204032', '172.16.66.216', '2024-12-18'),
(45, '20200204020', '172.16.66.80', '2024-12-18'),
(46, '20200204010', '172.16.61.128', '2024-12-18'),
(47, '20200204024', '172.16.151.177', '2024-12-18'),
(48, '20200204039', '172.16.65.195', '2024-12-18'),
(49, '20200204038', '172.16.65.185', '2024-12-18'),
(50, '20200204049', '172.16.66.249', '2024-12-18'),
(51, '20200204028', '172.16.67.10', '2024-12-18'),
(52, '20200204048', '172.16.67.13', '2024-12-18'),
(53, '20200204029', '172.16.100.34', '2024-12-18'),
(54, '20200204025', '172.16.100.246', '2024-12-18'),
(55, '20200204003', '172.16.112.79', '2024-12-18'),
(56, '20200204023', '172.16.67.98', '2024-12-18'),
(57, '20200204047', '172.16.67.90', '2024-12-18'),
(58, '20200104063', '172.16.67.114', '2024-12-18'),
(59, '20200104141', '172.16.112.87', '2024-12-18'),
(60, '20200204051', '172.16.112.99', '2024-12-18'),
(61, '20200104062', '172.16.101.20', '2024-12-18'),
(62, '20200204037', '172.16.132.179', '2024-12-19'),
(63, '20200204038', '172.16.67.57', '2024-12-19'),
(64, '20200204020', '172.16.67.8', '2024-12-19'),
(65, '20200204010', '172.16.61.73', '2024-12-19'),
(66, '20200204029', '172.16.102.46', '2024-12-19'),
(67, '20200204027', '172.16.67.67', '2024-12-19'),
(68, '180104100', '172.16.66.221', '2024-12-19'),
(69, '20200204024', '172.16.150.239', '2024-12-19'),
(70, '20200204023', '172.16.66.210', '2024-12-19'),
(71, '20200204039', '172.16.67.130', '2024-12-19'),
(72, '20200204005', '172.16.150.242', '2024-12-19'),
(73, '20200104141', '172.16.67.151', '2024-12-19'),
(74, '20200204025', '172.16.102.164', '2024-12-19'),
(75, '20200204003', '172.16.150.225', '2024-12-19'),
(76, '20200204048', '172.16.67.172', '2024-12-19'),
(77, '20200204012', '172.16.150.231', '2024-12-19'),
(78, '20200204028', '172.16.67.195', '2024-12-19'),
(79, '20200204041', '172.16.67.210', '2024-12-19'),
(80, '20200204049', '172.16.67.225', '2024-12-19'),
(81, '20200204032', '172.16.64.146', '2024-12-19'),
(87, '20200204027', '172.16.65.136', '2024-12-24'),
(88, '20200204020', '172.16.65.253', '2024-12-24'),
(89, '20200204038', '172.16.103.153', '2024-12-24');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `section` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `name`, `phone_number`, `email`, `section`) VALUES
(1, '20200204003', 'Md. Tahiadur Rahman', '1534303356', 'rahman.cse.20200204003@aust.edu', 'A'),
(2, '20200204005', 'Sumiya Ansari', '1795946789', 'sumiya.cse.20200204005@aust.edu', 'A'),
(3, '20200204006', 'Mahmudul Haque', '1741645372', 'mahmudul.cse.20200204006@aust.edu', 'A'),
(4, '20200204010', 'Naushin Mamun', '1687009912', 'naushin.cse.20200204010@aust.edu', 'A'),
(5, '20200204012', 'Adiba Amin', '1721119418', 'adiba.cse.20200204012@aust.edu', 'A'),
(6, '20200204018', 'Saeeda Tasneem Zeenat', '1628830284', 'saeeda.cse.20200204018@aust.edu', 'A'),
(7, '20200204019', 'Shadman Sadiq Chowdhury', '1319061314', 'shadman.cse.20200204019@aust.edu', 'A'),
(8, '20200204020', 'Nafisa Tasnim Neha', '1820321506', 'nafisa.cse.20200204020@aust.edu', 'A'),
(9, '20200204023', 'Md. Asif Rahman', '1301720823', 'rahman.cse.20200204023@aust.edu', 'A'),
(10, '20200204024', 'Md. Ashiquzzaman Joy', '1676659428', 'ashiq.cse.20200204024@aust.edu', 'A'),
(11, '20200204025', 'MD. Shiful Islam Fahad', '1631452320', 'shiful.cse.20200204025@aust.edu', 'A'),
(12, '20200204027', 'Tabia Morshed', '1534817016', 'tabia.cse.20200204027@aust.edu', 'A'),
(13, '20200204028', 'Protiva Roy', '1870136742', 'protiva.cse.20200204028@aust.edu', 'A'),
(14, '20200204029', 'Adibul Haque', '1631633644', 'adibul.cse.20200204029@aust.edu', 'A'),
(15, '20200204032', 'Tahsin Shadman', '1748874838', 'tahsin.cse.20200204032@aust.edu', 'A'),
(16, '20200204034', 'Tanvir Md. Raiyan', '1770447063', 'tanvir.cse.20200204034@aust.edu', 'A'),
(17, '20200204037', 'MD. Yousuf Ali', '1405594862', 'yousuf.cse.20200204037@aust.edu', 'A'),
(18, '20200204038', 'Miftahul Sheikh', '1535763995', 'miftahul.cse.20200204038@aust.edu', 'A'),
(19, '20200204039', 'Misbahul Sheikh', '1927901747', 'misbahul.cse.20200204039@aust.edu', 'A'),
(20, '20200204041', 'Maisha Islam', '1915705758', 'maisha.cse.20200204041@aust.edu', 'A'),
(21, '20200204042', 'Sabrina Tabassum', '1979866160', 'sabrina.cse.20200204042@aust.edu', 'A'),
(22, '20200204044', 'Samia Habib', '1317267099', 'samia.cse.20200204044@aust.edu', 'A'),
(23, '20200204047', 'Efti Hossain', '1759774071', 'hossain.cse.20200204047@aust.edu', 'A'),
(24, '20200204048', 'Hafsa Binte Rashid', '1712913232', 'hafsa.cse.20200204048@aust.edu', 'A'),
(25, '20200204049', 'Md Musaddique Ali Erfan', '1793515517', 'musaddique.cse.20200204049@aust.edu', 'A'),
(26, '20200204051', 'MD Rafiu Alam Rafi', '1749508694', 'rafi.cse.20200204051@aust.edu', 'A'),
(27, '20200104062', 'Sakib Shahriar Rafi', '1703088289', 'sakib.cse.20200104062@aust.edu', 'A'),
(28, '20200104141', 'Purna Chandra Saha', '1612201645', 'purno.cse.20200104141@aust.edu', 'A'),
(29, '180104100', 'Marshia Mehjabin Lamisa', '1552334975', '180104100@aust.edu', 'A'),
(30, '20200104063', 'Sourov Roy', '', 'sourov.cse.200104063@aust.edu', 'A'),
(31, '190204112', 'Arif Siddique', '', '190204112@aust.edu', 'A'),
(32, '190104005', 'Antika Ghosh', '', '190104005@aust.edu', 'A'),
(33, '20200204053', 'Ajrin Khanam Orpa', '1782355054', 'ajrin.cse.20200204053@aust.edu', 'B'),
(34, '20200204054', 'Shaikh Ramisha Maliyat', '1780005688', 'shaikh.cse.20200204054@aust.edu', 'B'),
(35, '20200204055', 'Hasin Md. Daiyan', '1533376565', 'hasin.cse.20200204055@aust.edu', 'B'),
(36, '20200204056', 'Amit Karmakar', '1796770187', 'amit.cse.20200204056@aust.edu', 'B'),
(37, '20200204058', 'Rebeka Sultana', '1568116719', 'rebeka.cse.20200204058@aust.edu', 'B'),
(38, '20200204061', 'Aryan Ahnaf', '1777883228', 'aryan.cse.20200204061@aust.edu', 'B'),
(39, '20200204062', 'Rafiul Awal', '1709033015', 'rafiul.cse.20200204062@aust.edu', 'B'),
(40, '20200204063', 'Ahmed Al Nahian', '1711424928', 'ahmed.cse.20200204063@aust.edu', 'B'),
(41, '20200204065', 'Nabila Rahman', '1831131143', 'nabila.cse.20200204065@aust.edu', 'B'),
(42, '20200204066', 'Nafsun Haider', '1781334488', 'nafsun.cse.20200204066@aust.edu', 'B'),
(43, '20200204067', 'Arijit Paul', '1777645699', 'arijit.cse.20200204067@aust.edu', 'B'),
(44, '20200204068', 'Sabrin Sultana Chadni', '1911949956', 'sabrin.cse.20200204068@aust.edu', 'B'),
(45, '20200204073', 'Maisha Fahmida Hossain', '1318603524', 'maisha.cse.20200204073@aust.edu', 'B'),
(46, '20200204075', 'Md. Ashraful Islam', '1894410481', 'asraful.cse.20200204075@aust.edu', 'B'),
(47, '20200204080', 'Tahiya Tahsin', '1881625966', 'tahiya.cse.20200204080@aust.edu', 'B'),
(48, '20200204090', 'Arif Absar Ahmad Shafi', '1405342908', 'arif.cse.20200204090@aust.edu', 'B'),
(49, '20200204091', 'Jannatul Ferdous Shormy', '1537520778', 'jannatul.cse.20200204091@aust.edu', 'B'),
(50, '20200204092', 'Sameen Ajreen Rahman', '1996014804', 'sameen.cse.20200204092@aust.edu', 'B'),
(51, '20200204093', 'Syeda Tanjuma Tasnim Mayisha', '1749908227', 'syeda.cse.20200204093@aust.edu', 'B'),
(52, '20200204096', 'Mahiba Nafia', '1777684055', 'mahiba.cse.20200204096@aust.edu', 'B'),
(53, '20200204097', 'Shahariar Hossain Remon', '1688208230', 'shahariar.cse.20200204097@aust.edu', 'B'),
(54, '20200204098', 'Saif Saruwar', '1870801955', 'saif.cse.20200204098@aust.edu', 'B'),
(55, '20200204100', 'Sadia Sabrin Neha', '1626168596', 'sadia.cse.20200204100@aust.edu', 'B'),
(56, '20200204101', 'Meherunnesa Hossain Ibnath', '1320680548', 'meherunnesa.cse.20200204101@aust.edu', 'B'),
(57, '20200204103', 'Ekramul Huda Chowdhury', '1863980193', 'ekramul.cse.20200204103@aust.edu', 'B'),
(58, '20200204104', 'Rehenuma Tabassum', '1776171978', 'rehenuma.cse.20200204104@aust.edu', 'B'),
(59, '20200204106', 'Hasan Farabi', '1935487944', 'hasan.cse.20200204106@aust.edu', 'B'),
(60, '20200204108', 'Apu Das', '1751908837', 'apu.cse.20200204108@aust.edu', 'B');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`,`date`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
