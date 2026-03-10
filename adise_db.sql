-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 10 Μαρ 2026 στις 08:59:06
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `adise_db`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `games`
--

CREATE TABLE `games` (
  `ID` int(11) NOT NULL,
  `Status` enum('waiting','playing','finished') NOT NULL,
  `Player1_id` int(11) NOT NULL,
  `Player2_id` int(11) DEFAULT NULL,
  `Turn_Player_id` int(11) DEFAULT NULL,
  `State_json` longtext DEFAULT NULL,
  `Winner_id` int(11) DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Άδειασμα δεδομένων του πίνακα `games`
--

INSERT INTO `games` (`ID`, `Status`, `Player1_id`, `Player2_id`, `Turn_Player_id`, `State_json`, `Winner_id`, `Created_At`, `Updated_At`) VALUES
(2, 'waiting', 1, NULL, NULL, NULL, NULL, '2026-02-22 17:52:42', '2026-02-22 17:52:42'),
(3, 'waiting', 1, NULL, NULL, NULL, NULL, '2026-02-22 17:57:39', '2026-02-22 17:57:39'),
(4, 'finished', 1, 2, 1, '{\"table\":[],\"deck\":[\"8D\",\"6D\",\"5S\"],\"last_capturer\":2,\"captured\":{\"1\":[\"8H\",\"JC\",\"2C\",\"KH\",\"QH\",\"10C\",\"AS\",\"2H\",\"9H\",\"4C\",\"5D\",\"2D\",\"JH\",\"JD\"],\"2\":[\"6C\",\"KD\",\"4H\",\"10S\",\"10H\"]},\"xeri_count\":{\"1\":0,\"2\":0},\"xeri_j_count\":{\"1\":0,\"2\":0}}', 2, '2026-02-22 18:05:35', '2026-03-02 18:05:48'),
(12, 'finished', 11, 12, 11, '{\"table\":[\"KH\"],\"captured\":[],\"xeri_count\":[],\"last_capturer\":null,\"deck\":[\"3D\",\"6C\",\"3S\",\"10S\",\"8H\",\"2H\",\"JS\",\"9S\",\"AH\",\"KD\",\"AS\",\"10D\",\"5S\",\"10C\",\"2D\",\"3C\",\"10H\",\"QS\",\"8S\",\"JH\",\"9C\",\"JC\",\"QC\",\"4C\",\"7C\",\"2S\",\"3H\",\"QH\",\"6D\",\"9H\",\"AD\",\"5C\",\"8C\",\"7D\",\"KH\",\"2C\",\"7S\",\"6S\",\"JD\",\"6H\"]}', 11, '2026-03-09 08:59:58', '2026-03-09 09:34:00'),
(13, 'finished', 13, 14, 13, '{\"table\":[\"QH\"],\"captured\":[],\"xeri_count\":[],\"last_capturer\":null,\"deck\":[]}', 14, '2026-03-09 10:16:02', '2026-03-09 11:06:55');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `game_players`
--

CREATE TABLE `game_players` (
  `ID` int(11) NOT NULL,
  `Game_id` int(11) NOT NULL,
  `Player_id` int(11) NOT NULL,
  `Seat` tinyint(4) NOT NULL,
  `Hand_json` longtext NOT NULL,
  `Cards_left` int(11) NOT NULL,
  `Xeri_count` int(11) NOT NULL,
  `Xeri_jack_count` int(11) NOT NULL,
  `Score` int(11) NOT NULL,
  `Captured_json` longtext DEFAULT NULL,
  `Joinned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Άδειασμα δεδομένων του πίνακα `game_players`
--

INSERT INTO `game_players` (`ID`, `Game_id`, `Player_id`, `Seat`, `Hand_json`, `Cards_left`, `Xeri_count`, `Xeri_jack_count`, `Score`, `Captured_json`, `Joinned_at`) VALUES
(1, 4, 1, 1, '[]', 0, 0, 0, 2, '[\"9D\",\"4D\",\"10D\",\"4S\",\"KS\",\"6H\",\"6S\"]', '2026-02-22 18:05:35'),
(3, 6, 2, 1, '[]', 0, 0, 0, 0, NULL, '2026-02-22 18:18:06'),
(4, 4, 2, 2, '[]', 0, 0, 1, 32, '[\"7S\",\"3H\",\"3C\",\"9S\",\"7C\",\"QC\",\"QD\",\"AD\",\"JS\",\"2S\",\"3S\",\"QS\",\"5H\",\"7H\",\"5C\",\"8S\",\"3D\",\"7D\",\"8C\",\"9C\",\"KC\",\"AH\",\"AC\"]', '2026-02-22 18:19:28'),
(12, 12, 11, 1, '[\"QD\",\"9D\",\"8D\",\"4S\",\"AC\",\"5D\"]', 6, 2, 1, 57, '[\"7C\",\"3D\",\"5C\",\"3C\",\"JS\",\"4H\",\"JH\",\"8S\",\"10D\",\"10S\",\"QD\",\"QH\",\"QC\",\"10H\",\"KD\",\"KC\",\"2H\",\"2D\",\"QS\",\"6H\",\"3S\",\"2C\",\"8C\",\"9S\",\"6C\",\"6D\",\"AS\",\"4S\",\"3H\",\"4C\",\"9D\",\"10C\",\"4D\",\"5H\",\"AH\",\"7D\",\"8D\",\"8H\"]', '2026-03-09 08:59:58'),
(13, 12, 12, 2, '[\"KS\",\"5H\",\"7H\",\"4H\",\"KC\",\"4D\"]', 6, 0, 0, 6, '[\"AD\",\"2S\",\"KS\",\"AC\",\"5D\",\"5S\",\"JC\",\"7S\",\"JD\",\"7H\",\"6S\",\"9H\",\"9C\"]', '2026-03-09 09:00:34'),
(14, 13, 13, 1, '[]', 0, 2, 0, 24, '[\"6D\",\"KH\",\"8S\",\"AS\",\"JH\",\"2H\",\"2D\",\"8H\",\"8C\",\"2C\",\"3H\",\"5D\",\"4S\",\"5S\",\"7C\",\"9D\",\"10C\",\"4D\",\"9H\",\"JD\"]', '2026-03-09 10:16:02'),
(15, 13, 14, 2, '[]', 0, 1, 1, 45, '[\"3D\",\"6H\",\"KD\",\"3C\",\"10H\",\"6S\",\"7S\",\"5C\",\"7D\",\"QD\",\"6C\",\"QS\",\"5H\",\"QC\",\"8D\",\"JS\",\"4C\",\"7H\",\"9C\",\"9S\",\"KC\",\"KS\",\"4H\",\"10D\",\"3S\",\"10S\",\"2S\",\"AC\",\"AD\",\"AH\",\"JC\"]', '2026-03-09 10:17:22');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `moves`
--

CREATE TABLE `moves` (
  `ID` int(11) NOT NULL,
  `Game_id` int(11) NOT NULL,
  `Player_id` int(11) NOT NULL,
  `Action` enum('play','capture','deal','finish') NOT NULL,
  `Card` varchar(3) DEFAULT NULL,
  `Move_json` text DEFAULT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Turn_no` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Άδειασμα δεδομένων του πίνακα `moves`
--

INSERT INTO `moves` (`ID`, `Game_id`, `Player_id`, `Action`, `Card`, `Move_json`, `Created_At`, `Turn_no`) VALUES
(1, 4, 1, 'play', 'JC', '{\"card\":\"JC\",\"from_hand\":true}', '2026-02-26 11:01:49', 0),
(2, 4, 2, 'play', '2C', '{\"card\":\"2C\",\"from_hand\":true}', '2026-02-26 11:08:05', 0),
(3, 4, 1, 'play', 'KH', '{\"card\":\"KH\",\"from_hand\":true}', '2026-02-26 11:13:30', 0),
(4, 4, 2, 'play', 'QH', '{\"card\":\"QH\",\"from_hand\":true}', '2026-02-26 11:16:23', 0),
(5, 4, 1, 'play', '10C', '{\"card\":\"10C\",\"from_hand\":true}', '2026-02-26 11:17:16', 0),
(6, 4, 2, 'play', 'AS', '{\"card\":\"AS\",\"from_hand\":true}', '2026-02-26 11:17:52', 0),
(7, 4, 1, 'play', '2H', '{\"card\":\"2H\",\"from_hand\":true}', '2026-02-26 11:18:28', 0),
(8, 4, 2, 'play', '9H', '{\"card\":\"9H\",\"from_hand\":true}', '2026-02-26 11:18:58', 0),
(9, 4, 1, 'play', '4C', '{\"card\":\"4C\",\"from_hand\":true}', '2026-02-26 11:19:29', 0),
(10, 4, 2, 'play', '5D', '{\"card\":\"5D\",\"from_hand\":true}', '2026-02-26 11:19:48', 0),
(11, 4, 1, 'play', '2D', '{\"card\":\"2D\",\"from_hand\":true}', '2026-02-26 11:20:14', 0),
(12, 4, 2, 'play', 'JH', '{\"card\":\"JH\",\"from_hand\":true}', '2026-02-26 11:20:32', 0),
(13, 4, 1, 'capture', 'JD', '{\"did_capture\":true,\"is_xeri\":false}', '2026-02-26 12:38:52', 0),
(14, 4, 2, 'play', '6C', '{\"did_capture\":false,\"is_xeri\":false}', '2026-02-26 12:39:47', 0),
(15, 4, 1, 'play', 'KD', '{\"did_capture\":false,\"is_xeri\":false}', '2026-02-26 12:40:20', 0),
(16, 4, 2, 'play', '4H', '{\"did_capture\":false,\"is_xeri\":false}', '2026-02-26 12:40:47', 0),
(17, 4, 1, 'play', '10S', '{\"did_capture\":false,\"is_xeri\":false}', '2026-02-26 12:41:17', 0),
(18, 4, 2, 'capture', '10H', '{\"did_capture\":true,\"is_xeri\":false}', '2026-02-26 12:41:34', 0),
(19, 4, 1, 'play', '9D', '{\"card\":\"9D\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:28:18', 19),
(20, 4, 2, 'play', '4D', '{\"card\":\"4D\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:30:57', 20),
(21, 4, 1, 'play', '10D', '{\"card\":\"10D\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:31:44', 21),
(22, 4, 2, 'play', '4S', '{\"card\":\"4S\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:32:09', 22),
(23, 4, 1, 'play', 'KS', '{\"card\":\"KS\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:32:26', 23),
(24, 4, 2, 'play', '6H', '{\"card\":\"6H\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:32:43', 24),
(25, 4, 1, 'capture', '6S', '{\"card\":\"6S\",\"did_capture\":true,\"is_xeri\":false}', '2026-03-02 17:47:43', 25),
(26, 4, 2, 'play', '7S', '{\"card\":\"7S\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:49:41', 26),
(27, 4, 1, 'play', '3H', '{\"card\":\"3H\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:50:03', 27),
(28, 4, 2, 'capture', '3C', '{\"card\":\"3C\",\"did_capture\":true,\"is_xeri\":false}', '2026-03-02 17:50:25', 28),
(29, 4, 1, 'play', '9S', '{\"card\":\"9S\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:50:59', 29),
(30, 4, 2, 'play', '7C', '{\"card\":\"7C\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:51:15', 30),
(31, 4, 1, 'play', 'QC', '{\"card\":\"QC\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:51:34', 31),
(32, 4, 2, 'capture', 'QD', '{\"card\":\"QD\",\"did_capture\":true,\"is_xeri\":false}', '2026-03-02 17:51:47', 32),
(33, 4, 1, 'play', 'AD', '{\"card\":\"AD\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:52:12', 33),
(34, 4, 2, 'capture', 'JS', '{\"card\":\"JS\",\"did_capture\":true,\"is_xeri\":true}', '2026-03-02 17:52:32', 34),
(35, 4, 1, 'play', '2S', '{\"card\":\"2S\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:52:54', 35),
(36, 4, 2, 'play', '3S', '{\"card\":\"3S\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:54:03', 36),
(37, 4, 1, 'play', 'QS', '{\"card\":\"QS\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:56:33', 37),
(38, 4, 2, 'play', '5H', '{\"card\":\"5H\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:56:49', 38),
(39, 4, 1, 'play', '7H', '{\"card\":\"7H\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:57:01', 39),
(40, 4, 2, 'play', '5C', '{\"card\":\"5C\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:57:12', 40),
(41, 4, 1, 'play', '8S', '{\"card\":\"8S\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:57:30', 41),
(42, 4, 2, 'play', '3D', '{\"card\":\"3D\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:57:43', 42),
(43, 4, 1, 'play', '7D', '{\"card\":\"7D\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:58:15', 43),
(44, 4, 2, 'play', '8C', '{\"card\":\"8C\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:58:28', 44),
(45, 4, 1, 'play', '9C', '{\"card\":\"9C\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:58:44', 45),
(46, 4, 2, 'play', 'KC', '{\"card\":\"KC\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:59:29', 46),
(47, 4, 1, 'play', 'AH', '{\"card\":\"AH\",\"did_capture\":false,\"is_xeri\":false}', '2026-03-02 17:59:49', 47),
(48, 4, 2, 'capture', 'AC', '{\"card\":\"AC\",\"did_capture\":true,\"is_xeri\":false}', '2026-03-02 18:00:34', 48),
(159, 12, 11, 'play', '5D', '{\"card\":\"5D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:07:06', 1),
(160, 12, 12, 'capture', '5S', '{\"card\":\"5S\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:08:06', 2),
(161, 12, 11, 'play', '7C', '{\"card\":\"7C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:08:35', 3),
(162, 12, 12, 'play', '3D', '{\"card\":\"3D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:08:53', 4),
(163, 12, 11, 'play', '5C', '{\"card\":\"5C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:09:13', 5),
(164, 12, 12, 'play', '3C', '{\"card\":\"3C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:09:33', 6),
(165, 12, 11, 'capture', 'JS', '{\"card\":\"JS\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:10:24', 7),
(166, 12, 12, 'play', '4H', '{\"card\":\"4H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:10:45', 8),
(167, 12, 11, 'capture', 'JH', '{\"card\":\"JH\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":true}', '2026-03-09 09:11:11', 9),
(168, 12, 12, 'play', 'JC', '{\"card\":\"JC\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:11:45', 10),
(169, 12, 11, 'play', '7S', '{\"card\":\"7S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:11:57', 11),
(170, 12, 12, 'capture', 'JD', '{\"card\":\"JD\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:12:25', 12),
(171, 12, 11, 'play', '7H', '{\"card\":\"7H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:15:14', 13),
(172, 12, 12, 'play', '6S', '{\"card\":\"6S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:15:36', 14),
(173, 12, 11, 'play', '9H', '{\"card\":\"9H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:15:55', 15),
(174, 12, 12, 'capture', '9C', '{\"card\":\"9C\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:16:29', 16),
(175, 12, 11, 'play', '8S', '{\"card\":\"8S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:16:50', 17),
(176, 12, 12, 'play', '10D', '{\"card\":\"10D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:17:11', 18),
(177, 12, 11, 'capture', '10S', '{\"card\":\"10S\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:17:37', 19),
(178, 12, 12, 'play', 'QD', '{\"card\":\"QD\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:18:01', 20),
(179, 12, 11, 'capture', 'QH', '{\"card\":\"QH\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":false}', '2026-03-09 09:18:30', 21),
(180, 12, 12, 'play', 'QC', '{\"card\":\"QC\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:19:10', 22),
(181, 12, 11, 'play', '10H', '{\"card\":\"10H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:19:17', 23),
(182, 12, 12, 'play', 'KD', '{\"card\":\"KD\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:19:43', 24),
(183, 12, 11, 'capture', 'KC', '{\"card\":\"KC\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:22:12', 25),
(184, 12, 12, 'play', '2H', '{\"card\":\"2H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:22:41', 26),
(185, 12, 11, 'capture', '2D', '{\"card\":\"2D\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":false}', '2026-03-09 09:23:03', 27),
(186, 12, 12, 'play', 'QS', '{\"card\":\"QS\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:23:21', 28),
(187, 12, 11, 'play', '6H', '{\"card\":\"6H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:24:02', 29),
(188, 12, 12, 'play', '3S', '{\"card\":\"3S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:24:09', 30),
(189, 12, 11, 'play', '2C', '{\"card\":\"2C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:24:18', 31),
(190, 12, 12, 'play', '8C', '{\"card\":\"8C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:24:54', 32),
(191, 12, 11, 'play', '9S', '{\"card\":\"9S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:25:01', 33),
(192, 12, 12, 'play', '6C', '{\"card\":\"6C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:25:23', 34),
(193, 12, 11, 'capture', '6D', '{\"card\":\"6D\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:25:56', 35),
(194, 12, 12, 'play', 'AS', '{\"card\":\"AS\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:26:09', 36),
(195, 12, 11, 'play', '4S', '{\"card\":\"4S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:28:13', 37),
(196, 12, 12, 'play', '3H', '{\"card\":\"3H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:28:38', 38),
(197, 12, 11, 'play', '4C', '{\"card\":\"4C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:28:55', 39),
(198, 12, 12, 'play', '9D', '{\"card\":\"9D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:29:12', 40),
(199, 12, 11, 'play', '10C', '{\"card\":\"10C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:29:39', 41),
(200, 12, 12, 'play', '4D', '{\"card\":\"4D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:29:59', 42),
(201, 12, 11, 'play', '5H', '{\"card\":\"5H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:30:19', 43),
(202, 12, 12, 'play', 'AH', '{\"card\":\"AH\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:30:46', 44),
(203, 12, 11, 'play', '7D', '{\"card\":\"7D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:31:04', 45),
(204, 12, 12, 'play', '8D', '{\"card\":\"8D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:31:25', 46),
(205, 12, 11, 'capture', '8H', '{\"card\":\"8H\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:31:49', 47),
(206, 12, 12, 'play', 'KH', '{\"card\":\"KH\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 09:32:17', 48),
(207, 13, 13, 'play', '10H', '{\"card\":\"10H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:29:02', 1),
(208, 13, 14, 'play', '6S', '{\"card\":\"6S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:29:40', 2),
(209, 13, 13, 'play', '7S', '{\"card\":\"7S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:29:48', 3),
(210, 13, 14, 'play', '5C', '{\"card\":\"5C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:29:57', 4),
(211, 13, 13, 'play', '7D', '{\"card\":\"7D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:30:05', 5),
(212, 13, 14, 'play', 'QD', '{\"card\":\"QD\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:30:22', 6),
(213, 13, 13, 'play', '6C', '{\"card\":\"6C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:30:32', 7),
(214, 13, 14, 'play', 'QS', '{\"card\":\"QS\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:30:40', 8),
(215, 13, 13, 'play', '5H', '{\"card\":\"5H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:30:48', 9),
(216, 13, 14, 'play', 'QC', '{\"card\":\"QC\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:30:59', 10),
(217, 13, 13, 'play', '8D', '{\"card\":\"8D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:31:07', 11),
(218, 13, 14, 'capture', 'JS', '{\"card\":\"JS\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:31:16', 12),
(219, 13, 13, 'play', '4C', '{\"card\":\"4C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:38:21', 13),
(220, 13, 14, 'play', '7H', '{\"card\":\"7H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:38:35', 14),
(221, 13, 13, 'play', '9C', '{\"card\":\"9C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:38:43', 15),
(222, 13, 14, 'capture', '9S', '{\"card\":\"9S\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:38:52', 16),
(223, 13, 13, 'play', 'KC', '{\"card\":\"KC\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:39:07', 17),
(224, 13, 14, 'capture', 'KS', '{\"card\":\"KS\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":false}', '2026-03-09 10:39:14', 18),
(225, 13, 13, 'play', '6D', '{\"card\":\"6D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:39:46', 19),
(226, 13, 14, 'play', 'KH', '{\"card\":\"KH\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:39:53', 20),
(227, 13, 13, 'play', '8S', '{\"card\":\"8S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:40:01', 21),
(228, 13, 14, 'play', 'AS', '{\"card\":\"AS\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:40:09', 22),
(229, 13, 13, 'capture', 'JH', '{\"card\":\"JH\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:40:18', 23),
(230, 13, 14, 'play', '2H', '{\"card\":\"2H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:40:33', 24),
(231, 13, 13, 'capture', '2D', '{\"card\":\"2D\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":false}', '2026-03-09 10:46:19', 25),
(232, 13, 14, 'play', '8H', '{\"card\":\"8H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:46:35', 26),
(233, 13, 13, 'capture', '8C', '{\"card\":\"8C\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":false}', '2026-03-09 10:46:43', 27),
(234, 13, 14, 'play', '4H', '{\"card\":\"4H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:01', 28),
(235, 13, 13, 'play', '10D', '{\"card\":\"10D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:07', 29),
(236, 13, 14, 'play', '3S', '{\"card\":\"3S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:15', 30),
(237, 13, 13, 'play', '10S', '{\"card\":\"10S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:25', 31),
(238, 13, 14, 'play', '2S', '{\"card\":\"2S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:34', 32),
(239, 13, 13, 'play', 'AC', '{\"card\":\"AC\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:42', 33),
(240, 13, 14, 'capture', 'AD', '{\"card\":\"AD\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:47:49', 34),
(241, 13, 13, 'play', 'AH', '{\"card\":\"AH\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:48:04', 35),
(242, 13, 14, 'capture', 'JC', '{\"card\":\"JC\",\"did_capture\":true,\"is_xeri\":true,\"is_jack_xeri\":true}', '2026-03-09 10:48:11', 36),
(243, 13, 13, 'play', '2C', '{\"card\":\"2C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:52:22', 37),
(244, 13, 14, 'play', '3H', '{\"card\":\"3H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:52:34', 38),
(245, 13, 13, 'play', '5D', '{\"card\":\"5D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:52:43', 39),
(246, 13, 14, 'play', '4S', '{\"card\":\"4S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:52:49', 40),
(247, 13, 13, 'play', '5S', '{\"card\":\"5S\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:52:56', 41),
(248, 13, 14, 'play', '7C', '{\"card\":\"7C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:53:02', 42),
(249, 13, 13, 'play', '9D', '{\"card\":\"9D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:53:25', 43),
(250, 13, 14, 'play', '10C', '{\"card\":\"10C\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:54:07', 44),
(251, 13, 13, 'play', '4D', '{\"card\":\"4D\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:54:20', 45),
(252, 13, 14, 'play', '9H', '{\"card\":\"9H\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:54:28', 46),
(253, 13, 13, 'capture', 'JD', '{\"card\":\"JD\",\"did_capture\":true,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:54:38', 47),
(254, 13, 14, 'play', 'QH', '{\"card\":\"QH\",\"did_capture\":false,\"is_xeri\":false,\"is_jack_xeri\":false}', '2026-03-09 10:54:57', 48);

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `players`
--

CREATE TABLE `players` (
  `ID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Άδειασμα δεδομένων του πίνακα `players`
--

INSERT INTO `players` (`ID`, `Username`, `Created_At`) VALUES
(1, 'Paktitis', '2026-02-22 17:43:37'),
(2, 'Maria', '2026-02-22 18:07:29'),
(11, 'Spiros', '2026-03-09 08:59:58'),
(12, 'Ana', '2026-03-09 09:00:34'),
(13, 'Teo', '2026-03-09 10:16:02'),
(14, 'Gio', '2026-03-09 10:17:22');

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`ID`);

--
-- Ευρετήρια για πίνακα `game_players`
--
ALTER TABLE `game_players`
  ADD PRIMARY KEY (`ID`);

--
-- Ευρετήρια για πίνακα `moves`
--
ALTER TABLE `moves`
  ADD PRIMARY KEY (`ID`);

--
-- Ευρετήρια για πίνακα `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `games`
--
ALTER TABLE `games`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT για πίνακα `game_players`
--
ALTER TABLE `game_players`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT για πίνακα `moves`
--
ALTER TABLE `moves`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=255;

--
-- AUTO_INCREMENT για πίνακα `players`
--
ALTER TABLE `players`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
