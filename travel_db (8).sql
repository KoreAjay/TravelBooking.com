-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2025 at 08:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travel_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', NULL, 'Admin@123', '2025-06-21 10:49:07'),
(3, 'Rahul', NULL, 'Rahul@123', '2025-06-26 10:39:55'),
(4, 'Muskan', 'muskan@gmail.com', '$2y$10$T5jwiAr05EyGcVi.K34SyOrxlqp80uFn3wWzTkCZaEfQDgh7t6WBK', '2025-07-01 06:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `resume_path` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_title`, `full_name`, `email`, `phone`, `resume_path`, `message`, `applied_at`) VALUES
(1, 'Senior Frontend Developer', 'Rahul Bhoge', 'rahul@gmail.com', '9876543210', 'resumes/resume_685a9a0bedaae.pdf', '', '2025-06-24 12:29:00'),
(2, 'Senior Frontend Developer', 'xyz', 'xyz@gmail.com', '9876543210', 'resumes/resume_685bc32b9571b.pdf', '', '2025-06-25 09:36:43');

-- --------------------------------------------------------

--
-- Table structure for table `bus_bookings`
--

CREATE TABLE `bus_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `seat_number` varchar(50) DEFAULT NULL,
  `mobile_number` varchar(15) DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `booking_time` time DEFAULT NULL,
  `from_city` varchar(100) DEFAULT NULL,
  `to_city` varchar(100) DEFAULT NULL,
  `fare_amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_bookings`
--

INSERT INTO `bus_bookings` (`id`, `user_id`, `schedule_id`, `name`, `age`, `seat_number`, `mobile_number`, `booking_date`, `booking_time`, `from_city`, `to_city`, `fare_amount`, `payment_method`, `status`, `created_at`) VALUES
(0, 5, 5022, 'Ajay Shivputra Kore', 21, 'Any', '8856935188', '0000-00-00', '10:22:00', 'Pune', 'Nagpur', 799.00, 'Debit Card', 'Confirmed', '2025-07-01 04:52:49');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `phone`, `created_at`) VALUES
(1, 'kunal rav', 'kunal@gmail.com', 'my booking is not confirm', '8585798585', '2025-06-21 08:42:22');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `feedback` text NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `name`, `city`, `feedback`, `message`, `created_at`) VALUES
(1, 'ajay', 'Pune', 'I had a smooth experience booking tickets on Sair Karo. The UI is user-friendly and fast.', 'I had a smooth experience booking tickets on Sair Karo. The UI is user-friendly and fast.', '2025-06-21 07:30:29'),
(2, 'Rahul Bhoge', 'Nagpur', 'Amazing Experience on this application to book tic ket 24 hours coustomer supports', 'Fast Support\', \'I contacted support and got a quick resolution to my issue. Much appreciated!', '2025-06-21 10:37:31'),
(4, 'Mayush Tandekar', 'Nagpur', 'Fast Support\', \'I contacted support and got a quic...\n', NULL, '2025-06-22 09:10:45');

-- --------------------------------------------------------

--
-- Table structure for table `plane_bookings`
--

CREATE TABLE `plane_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `from_city` varchar(100) NOT NULL,
  `to_city` varchar(100) NOT NULL,
  `travel_date` date NOT NULL,
  `travel_time` time NOT NULL,
  `travel_class` varchar(50) NOT NULL,
  `seat_preference` varchar(50) NOT NULL,
  `fare_amount` decimal(10,2) DEFAULT NULL,
  `seat_number` int(11) NOT NULL,
  `pnr_number` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_fare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plane_bookings`
--

INSERT INTO `plane_bookings` (`id`, `user_id`, `schedule_id`, `from_city`, `to_city`, `travel_date`, `travel_time`, `travel_class`, `seat_preference`, `fare_amount`, `seat_number`, `pnr_number`, `payment_method`, `name`, `age`, `mobile`, `created_at`, `total_fare`, `status`, `booking_date`) VALUES
(5, 7, 5019, 'Goa', 'Mumbai', '2025-06-30', '12:00:00', 'Business', 'Window', 2200.00, 0, 'AX4FZ9', '0', 'Rahul Bhoge', 50, '9876543210', '2025-06-30 12:35:14', 0.00, 'Confirmed', '2025-06-30 18:40:14'),
(8, 5, 5024, 'Pune', 'Nagpur', '2025-07-01', '18:22:00', 'First Class', 'Aisle', 9500.00, 0, 'Q78VXA', '0', 'Ajay Shivputra Kore', 21, '9898959959', '2025-07-01 04:53:37', 0.00, 'Confirmed', '2025-07-01 04:53:37');

-- --------------------------------------------------------

--
-- Table structure for table `profile_images`
--

CREATE TABLE `profile_images` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile_images`
--

INSERT INTO `profile_images` (`id`, `user_id`, `image_path`, `uploaded_at`) VALUES
(1, 3, 'uploads/profile_pics/profile_6857af12414e5.jpeg', '2025-06-22 07:21:30'),
(3, 1, 'uploads/profile_pics/profile_6858160014e7d.jpg', '2025-06-22 14:41:04'),
(5, 4, 'uploads/profile_pics/profile_685e84a42c08e.jpg', '2025-06-27 11:46:44');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `mode` enum('train','bus','plane') DEFAULT NULL,
  `from_city` varchar(100) DEFAULT NULL,
  `to_city` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `coach_class` varchar(50) DEFAULT NULL,
  `fare_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_seats` int(11) DEFAULT NULL,
  `available_seats` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `mode`, `from_city`, `to_city`, `date`, `time`, `coach_class`, `fare_amount`, `total_seats`, `available_seats`) VALUES
(1, 'train', 'Pune', 'Mumbai', '2025-06-30', '14:45:19', 'AC First Class', 585.00, 25, 9),
(2, 'train', 'Delhi', 'Agra', '2025-06-30', '15:45:19', 'Sleeper', 1500.00, 50, 20),
(3, 'bus', 'Nashik', 'Aurangabad', '2025-06-30', '15:15:19', 'Volvo AC', 766.00, 80, 24),
(12, 'train', 'Pune', 'Solapur', '2025-06-30', '01:11:00', 'AC 2 Tier', 210.00, 1, 4),
(13, 'train', 'Pune', 'Solapur', '2025-06-30', '02:13:00', 'Sleeper', 210.00, 6, 6),
(15, 'plane', 'Pune', 'Dubai', '2025-06-28', '01:20:00', 'First Class', 25000.00, 60, 56),
(16, 'bus', 'Solapur', 'Mumbai', '2025-06-30', '02:27:00', 'Volvo AC', 1200.00, 35, 35),
(17, 'bus', 'Bhandara', 'Pune', '2025-06-30', '16:59:00', 'Volvo AC', 686.99, 10, 9),
(18, 'bus', 'Pune', 'Nagpur', '2025-06-26', '20:00:00', 'Volvo AC', 12000.00, 50, 48),
(19, 'train', 'Pune', 'Mumbai', '2025-06-26', '18:20:00', 'Sleeper', 299.00, 25, 25),
(20, 'train', 'Pune', 'Solapur', '2025-06-30', '18:23:00', 'Sleeper', 220.00, 30, 30),
(21, 'train', 'Pune', 'Delhi', '2025-06-26', '21:23:00', 'AC 3 Tier', 485.00, 15, 0),
(22, 'train', 'Bengaluru', 'Solapur', '2025-06-26', '18:24:00', 'Sleeper', 699.00, 45, 0),
(5001, 'bus', 'Pune', 'Nagpur', '2025-06-27', '08:00:00', 'AC Seater', 750.00, 40, 35),
(5002, 'bus', 'Mumbai', 'Ahmedabad', '2025-06-28', '10:30:00', 'Non-AC Sleeper', 900.00, 30, 28),
(5003, 'bus', 'Bengaluru', 'Chennai', '2025-06-27', '14:00:00', 'AC Sleeper', 1200.00, 30, 0),
(5004, 'bus', 'Delhi', 'Chandigarh', '2025-06-28', '06:45:00', 'AC Seater', 650.00, 45, 40),
(5005, 'bus', 'Kolkata', 'Bhubaneswar', '2025-06-28', '11:15:00', 'Non-AC Seater', 580.00, 50, 0),
(5006, 'bus', 'Hyderabad', 'Vijayawada', '2025-06-27', '18:30:00', 'AC Seater', 820.00, 40, 30),
(5007, 'bus', 'Jaipur', 'Udaipur', '2025-06-27', '09:00:00', 'Non-AC Seater', 450.00, 50, 0),
(5008, 'train', 'Delhi', 'Mumbai', '0000-00-00', '16:00:00', 'Sleeper', 1500.00, 100, 75),
(5009, 'train', 'Chennai', 'Kochi', '2025-06-27', '07:30:00', 'AC 3 Tier', 1800.00, 80, 60),
(5010, 'train', 'Bengaluru', 'Hyderabad', '2025-06-27', '20:45:00', 'AC 2 Tier', 2500.00, 50, 30),
(5011, 'train', 'Lucknow', 'Patna', '2025-06-28', '05:00:00', 'General', 300.00, 200, 15),
(5012, 'train', 'Ahmedabad', 'Pune', NULL, '13:20:00', 'Sleeper', 1100.00, 90, 0),
(5013, 'train', 'Bhopal', 'Indore', NULL, '09:50:00', 'AC Chair Car', 400.00, 60, 53),
(5014, 'train', 'Surat', 'Vadodara', NULL, '17:10:00', 'AC Executive', 750.00, 40, 38),
(5015, 'plane', 'Delhi', 'Bengaluru', '2025-06-28', '19:00:00', 'Economy', 4500.00, 180, 120),
(5016, 'plane', 'Mumbai', 'Kolkata', '2025-06-28', '06:00:00', 'Business', 9800.00, 20, 15),
(5017, 'plane', 'Hyderabad', 'Chennai', '2025-06-28', '10:10:00', 'Economy', 2800.00, 150, 100),
(5018, 'plane', 'Pune', 'Delhi', '2025-06-28', '14:55:00', 'Economy', 3800.00, 170, 90),
(5019, 'plane', 'Goa', 'Mumbai', '2025-06-30', '12:00:00', 'Economy', 2200.00, 120, 109),
(5020, 'plane', 'Lucknow', 'Mumbai', '2025-06-29', '08:40:00', 'Economy', 4100.00, 160, 130),
(5021, 'bus', 'Pune', 'Mumbai', '2025-06-30', '20:14:00', 'Non AC Seater', 399.00, 10, 9),
(5022, 'bus', 'Pune', 'Nagpur', '2025-07-01', '10:22:00', 'Non AC Sleeper', 799.00, 10, 8),
(5023, 'train', 'Pune', 'Nagpur', '2025-07-01', '17:22:00', 'AC 3 Tier', 399.00, 5, 2),
(5024, 'plane', 'Pune', 'Nagpur', '2025-07-01', '18:22:00', 'Premium Economy', 9500.00, 12, 11);

-- --------------------------------------------------------

--
-- Table structure for table `train_bookings`
--

CREATE TABLE `train_bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `from_city` varchar(100) NOT NULL,
  `to_city` varchar(100) NOT NULL,
  `travel_date` date NOT NULL,
  `travel_time` time NOT NULL,
  `coach` varchar(20) NOT NULL,
  `seat_preference` varchar(50) NOT NULL,
  `fare_amount` decimal(10,2) DEFAULT NULL,
  `seat_number` int(11) NOT NULL,
  `pnr_number` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_fare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `coach_class` varchar(50) DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `train_bookings`
--

INSERT INTO `train_bookings` (`id`, `user_id`, `schedule_id`, `from_city`, `to_city`, `travel_date`, `travel_time`, `coach`, `seat_preference`, `fare_amount`, `seat_number`, `pnr_number`, `payment_method`, `name`, `age`, `mobile`, `created_at`, `total_fare`, `status`, `coach_class`, `booking_date`) VALUES
(12, 5, 5023, 'Pune', 'Nagpur', '2025-07-01', '00:00:00', '', 'Aisle', 399.00, 46, 'F0282B8E', 'Net Banking', '0', 21, '8856935188', '2025-07-01 04:53:14', 0.00, 'Confirmed', 'AC 3 Tier', '2025-07-01 04:53:14');

-- --------------------------------------------------------

--
-- Table structure for table `trip_plans`
--

CREATE TABLE `trip_plans` (
  `plan_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `interests` text DEFAULT NULL,
  `generated_content` text DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_plans`
--

INSERT INTO `trip_plans` (`plan_id`, `user_id`, `destination`, `start_date`, `end_date`, `interests`, `generated_content`, `generated_at`) VALUES
(1, 1, 'Dubai', '2025-06-26', '2025-06-30', 'Relaxation, Food & Cuisine, Nature & Wildlife, Shopping', '\r\n                <p><strong>Proposed Itinerary for Dubai</strong></p>\r\n                <p><strong>Dates:</strong> 26 Jun, 2025 to 30 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine, Nature &amp;amp; Wildlife, Shopping</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Dubai. Check into your accommodation. Explore a local market or a famous landmark like the Dubai City Palace.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Depending on your interests (Relaxation, Food &amp;amp; Cuisine, Nature &amp;amp; Wildlife, Shopping), consider a nature hike, a visit to a wildlife sanctuary, or a thrilling adventure activity.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in the local culture. Visit historical sites, museums, and indulge in the local cuisine. Perhaps a cooking class if \'Food\' is an interest!</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-21 14:42:31'),
(2, 1, 'Dubai', '2025-06-26', '2025-06-30', 'Relaxation, Food & Cuisine, Nature & Wildlife, Shopping', '\r\n                <p><strong>Proposed Itinerary for Dubai</strong></p>\r\n                <p><strong>Dates:</strong> 26 Jun, 2025 to 30 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine, Nature &amp;amp; Wildlife, Shopping</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Dubai. Check into your accommodation. Explore a local market or a famous landmark like the Dubai City Palace.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Depending on your interests (Relaxation, Food &amp;amp; Cuisine, Nature &amp;amp; Wildlife, Shopping), consider a nature hike, a visit to a wildlife sanctuary, or a thrilling adventure activity.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in the local culture. Visit historical sites, museums, and indulge in the local cuisine. Perhaps a cooking class if \'Food\' is an interest!</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-21 14:43:20'),
(3, 1, 'Goa', '2025-06-23', '2025-06-26', 'Relaxation, Food & Cuisine', '\r\n                <p><strong>Proposed Itinerary for Goa</strong></p>\r\n                <p><strong>Dates:</strong> 23 Jun, 2025 to 26 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Goa. Check into your accommodation. Explore a local market or a famous landmark.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Enjoy a nature hike or visit a wildlife sanctuary based on your interests.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in local culture and taste authentic cuisine.</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-21 14:45:18'),
(4, 1, 'Goa', '2025-06-23', '2025-06-26', 'Relaxation, Food & Cuisine', '\r\n                <p><strong>Proposed Itinerary for Goa</strong></p>\r\n                <p><strong>Dates:</strong> 23 Jun, 2025 to 26 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Goa. Check into your accommodation. Explore a local market or a famous landmark.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Enjoy a nature hike or visit a wildlife sanctuary based on your interests.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in local culture and taste authentic cuisine.</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-21 14:46:11'),
(5, 1, 'Goa', '2025-06-23', '2025-06-26', 'Relaxation, Food & Cuisine', '\r\n                <p><strong>Proposed Itinerary for Goa</strong></p>\r\n                <p><strong>Dates:</strong> 23 Jun, 2025 to 26 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Goa. Check into your accommodation. Explore a local market or a famous landmark.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Enjoy a nature hike or visit a wildlife sanctuary based on your interests.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in local culture and taste authentic cuisine.</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-21 14:47:55'),
(6, 1, 'Goa', '2025-06-23', '2025-06-26', 'Relaxation, Food & Cuisine', '\r\n                <p><strong>Proposed Itinerary for Goa</strong></p>\r\n                <p><strong>Dates:</strong> 23 Jun, 2025 to 26 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Goa. Check into your accommodation. Explore a local market or a famous landmark.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Enjoy a nature hike or visit a wildlife sanctuary based on your interests.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in local culture and taste authentic cuisine.</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-21 14:51:19'),
(7, 1, 'Goa', '2025-06-28', '2025-06-30', 'Relaxation, Food & Cuisine, Nature & Wildlife, Shopping', '\r\n                <p><strong>Proposed Itinerary for Goa</strong></p>\r\n                <p><strong>Dates:</strong> 28 Jun, 2025 to 30 Jun, 2025</p>\r\n                <p><strong>Interests:</strong> Relaxation, Food &amp;amp; Cuisine, Nature &amp;amp; Wildlife, Shopping</p>\r\n                <hr>\r\n                <h5>Day 1: Arrival & Exploration</h5>\r\n                <p>Arrive in Goa. Check into your accommodation. Explore a local market or a famous landmark.</p>\r\n                <h5>Day 2: Adventure & Nature</h5>\r\n                <p>Enjoy a nature hike or visit a wildlife sanctuary based on your interests.</p>\r\n                <h5>Day 3: Culture & Cuisine</h5>\r\n                <p>Immerse yourself in local culture and taste authentic cuisine.</p>\r\n                <p class=\'text-muted mt-3\'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>\r\n            ', '2025-06-27 09:41:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `mobile`, `address`, `created_at`) VALUES
(1, 'ajay', 'ajay@gmail.com', '', '9665209461', 'Marunji Pune', '2025-06-21 07:27:39'),
(3, 'Mayush Tandekar', 'mayusht@gmail.com', '', '9898525256', 'Bhandara', '2025-06-22 07:13:39'),
(4, 'muskan', 'muskan@gmail.com', '', '9898959959', 'Wardha', '2025-06-27 11:43:37'),
(5, 'Ajay Kore', 'ajaykore45@gmail.com', '$2y$10$4kSAjlbGWmHAiYmQS5ug0u6KezQ9oCFNL6J6EESdpSfxLuEh0uZdO', NULL, NULL, '2025-06-30 09:45:34'),
(7, 'laflame', 'rahulbhoge@gmail.com', '$2y$10$SqYGuuGmLcGIcVYv6kRlau68uK.Syqa9sDlcCnn4/WX7eiCvCi962', NULL, NULL, '2025-06-30 12:32:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plane_bookings`
--
ALTER TABLE `plane_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pnr_number` (`pnr_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `travel_date` (`travel_date`),
  ADD KEY `from_city` (`from_city`),
  ADD KEY `to_city` (`to_city`);

--
-- Indexes for table `profile_images`
--
ALTER TABLE `profile_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `train_bookings`
--
ALTER TABLE `train_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_plans`
--
ALTER TABLE `trip_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `plane_bookings`
--
ALTER TABLE `plane_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `profile_images`
--
ALTER TABLE `profile_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5025;

--
-- AUTO_INCREMENT for table `train_bookings`
--
ALTER TABLE `train_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `trip_plans`
--
ALTER TABLE `trip_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `plane_bookings`
--
ALTER TABLE `plane_bookings`
  ADD CONSTRAINT `plane_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_images`
--
ALTER TABLE `profile_images`
  ADD CONSTRAINT `profile_images_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_plans`
--
ALTER TABLE `trip_plans`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
