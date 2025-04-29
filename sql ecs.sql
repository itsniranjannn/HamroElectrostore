-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2025 at 09:18 AM
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
-- Database: `electronics_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gadget_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `gadget_id`, `quantity`, `created_at`) VALUES
(14, 2, 15, 1, '2025-04-28 15:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `gadgets`
--

CREATE TABLE `gadgets` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gadgets`
--

INSERT INTO `gadgets` (`id`, `name`, `category`, `description`, `price`, `stock`, `image_path`, `created_at`) VALUES
(1, 'dell latitude 3380', 'Laptops', 'this is descent laptop with some basics requirements', 60000.00, 7, 'uploads/download.jfif', '2025-04-23 08:33:36'),
(2, 'Apple MacBook M4', 'Laptops', 'The Apple MacBook Pro 13-inch model with the M4 chip is available for purchase, offering enhanced performance and features. It includes a superfast M4 chip, which improves graphics and gaming performance, and supports second-generation hardware-accelerated ray tracing for more realistic images.', 175000.00, 6, 'uploads/Macbook m4.jpg', '2025-04-26 06:43:58'),
(3, 'hp spectre x360', 'Laptops', 'The HP Spectre x360 is a premium 2-in-1 laptop known for its performance and portability.(14\" 2.8K OLED, 16GB RAM, 1TB SSD)', 200000.00, 7, 'uploads/hpspectra.jpeg', '2025-04-26 06:47:25'),
(4, 'ASUS ZenBook S 14', 'Laptops', 'This model features a 14-inch OLED touch screen, Intel Core Ultra 7 258V processor, 32GB DDR5 RAM, Intel Arc 140V Graphics, and a 512GB SSD. It is praised for its lightweight design, battery efficiency, and AI capabilities', 230000.00, 10, 'uploads/asus zenbook.webp', '2025-04-26 06:50:13'),
(5, 'Samsung Galaxy S24 FE (8/256GB', 'Smartphones', 'Offers a refined glass-and-metal construction with Gorilla Glass Victus+ protection, a 6.7-inch AMOLED display with 120Hz refresh rate, and a 50MP primary camera with 12MP ultrawide and 5MP macro', 94999.00, 9, 'uploads/samsung Galaxy S24.webp', '2025-04-26 06:55:47'),
(6, 'OnePlus 13R ', 'Smartphones', 'Features a 6.7-inch Fluid AMOLED display with 120Hz refresh rate, a 50MP primary camera with OIS, 8MP ultrawide, and 2MP macro. It also has a 5000mAh battery with 100W SuperVOOC charging with (12/256GB)', 74999.00, 6, 'uploads/oneplus 13r.jfif', '2025-04-26 06:58:46'),
(7, 'X-Age Earphone type-c', 'Accessories', 'X-AGE ConvE Acoustic W5 Type C Wired Earphone - (XWE05) is compatible with all devices that feature a Type-C connector, including smartphones, tablets, laptops', 700.00, 1, 'uploads/xage typc.jfif', '2025-04-26 07:01:18'),
(8, 'JBL Live 770NC', 'Accessories', 'True Adaptive Noise Cancelling technology automatically uses noise sensing mics to adjust to your surroundings in real-time', 15000.00, 4, 'uploads/jbl.webp', '2025-04-26 07:03:13'),
(9, 'Ipad Mini', 'Tablets', 'The iPad Mini is a line of small tablet computers developed and marketed by Apple Inc., featuring screen sizes of 7.9 inches and 8.3 inches.', 106000.00, 4, 'uploads/ipad mini.webp', '2025-04-26 07:06:43'),
(10, 'Samsung Galaxy M52 5G', 'Smartphones', 'Galaxy M52 5G features a powerful Octa-core processor and 6/8GB of RAM, giving you plenty of power for heavy multitasking or gaming.', 45999.00, 12, 'uploads/m52.jfif', '2025-04-26 07:09:19'),
(11, 'Xiaomi 14T ', 'Smartphones', 'Equipped with a 6.67-inch AMOLED display, a 108MP primary camera, 8MP ultrawide, and 2MP macro. It has a 5000mAh battery with 120W HyperCharge.', 64999.00, 4, 'uploads/Xiaomi 14T.jfif', '2025-04-26 07:11:46'),
(12, 'Ultima Boom 141 ANC', 'Accessories', 'Introducing Boom 141 ANC Earbuds – Dual Tone Matte Finish, 10m Range, 30dB ANC, 45H Playtime, Fast Charge. Available in Space Black & Serene White.', 2499.00, 17, 'uploads/ultima.webp', '2025-04-26 07:14:03'),
(13, 'Samsung Galaxy Tab S9+', 'Tablets', ' S9+ is a tablet computer in the Samsung Galaxy Tab series, first announced on July 26, 2023, and released on August 11, 2023.\r\n It features a 12.4-inch Dynamic AMOLED 2X display with a 1752x2800 pixel resolution and a 120Hz refresh rate.\r\n', 140000.00, 3, 'uploads/samsyng tab s9.webp', '2025-04-26 07:16:12'),
(14, 'Nvidia Shield Tablet ', 'Tablets', 'Powered by NVIDIA Tegra K1 processor with 192-core NVIDIA Kepler GPU and 2.2 GHz quad-core CPU; Full HD 1080p, 8-inch display and dual-front facing speakers', 125000.00, 5, 'uploads/nvidia.jfif', '2025-04-26 07:17:48'),
(15, 'infinix gt 20 pro', 'Smartphones', 'Processor:MediaTek Dimensity 8200 5G · Storage:256 GB · Battery:5000mAh with 45W fast charging · Color:Black,Blue · Camera:Triple (108MP primary, 2MP macro, 2MP', 47999.00, 8, 'uploads/infinix gt 20 pro.jfif', '2025-04-26 07:18:59');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','delivered','failed','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `status`, `payment_method`, `payment_status`, `shipping_address`, `contact_number`, `updated_at`) VALUES
(43, 2, '2025-04-29 06:56:33', 700.00, 'pending', 'esewa', 'pending', 'bbb', '9816767996', '2025-04-29 06:56:33'),
(44, 2, '2025-04-29 07:08:02', 700.00, 'pending', 'esewa', 'pending', 'asdfghjkl', '9816767996', '2025-04-29 07:08:02'),
(45, 2, '2025-04-29 07:13:40', 700.00, 'pending', 'esewa', 'pending', 'ganeshaye namha', '9816767996', '2025-04-29 07:13:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `gadget_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `gadget_id`, `quantity`, `unit_price`) VALUES
(8, 8, 12, 1, 2499.00),
(9, 9, 12, 1, 2499.00),
(10, 10, 12, 1, 2499.00),
(11, 11, 12, 1, 2499.00),
(12, 12, 12, 1, 2499.00),
(13, 13, 12, 1, 2499.00),
(14, 14, 12, 1, 2499.00),
(15, 15, 12, 1, 2499.00),
(16, 16, 12, 1, 2499.00),
(17, 17, 12, 1, 2499.00),
(18, 18, 11, 1, 64999.00),
(19, 19, 11, 1, 64999.00),
(20, 20, 11, 1, 64999.00),
(21, 21, 3, 1, 200000.00),
(22, 22, 3, 1, 200000.00),
(23, 23, 7, 1, 700.00),
(24, 24, 7, 1, 700.00),
(25, 25, 7, 1, 700.00),
(26, 26, 7, 1, 700.00),
(27, 27, 7, 1, 700.00),
(28, 28, 7, 1, 700.00),
(29, 29, 7, 1, 700.00),
(30, 30, 7, 1, 700.00),
(31, 31, 7, 1, 700.00),
(32, 32, 7, 1, 700.00),
(33, 33, 7, 1, 700.00),
(34, 34, 7, 1, 700.00),
(35, 35, 7, 1, 700.00),
(36, 36, 7, 1, 700.00),
(37, 37, 12, 1, 2499.00),
(38, 38, 7, 1, 700.00),
(39, 39, 7, 1, 700.00),
(40, 40, 7, 1, 700.00),
(41, 41, 7, 1, 700.00),
(42, 42, 7, 1, 700.00),
(43, 43, 7, 1, 700.00),
(44, 44, 7, 1, 700.00),
(45, 45, 7, 1, 700.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `transaction_id`, `payment_method`, `amount`, `status`, `created_at`) VALUES
(38, 41, 'ESW17459087648104', 'esewa', 700.00, 'pending', '2025-04-29 06:39:24'),
(39, 42, 'ESW17459091397523', 'esewa', 700.00, 'pending', '2025-04-29 06:45:39'),
(40, 43, 'ESW17459097939600', 'esewa', 700.00, 'pending', '2025-04-29 06:56:33'),
(41, 44, 'ESW17459104824825', 'esewa', 700.00, 'pending', '2025-04-29 07:08:02'),
(42, 45, 'ESW17459108206121', 'esewa', 700.00, 'pending', '2025-04-29 07:13:40');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gadget_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_code` varchar(6) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `is_verified`, `verification_code`, `reset_token`, `role`, `created_at`) VALUES
(1, 'Niranjan', 'katwalniranjan7@gmail.com', '$2y$10$QOQnxjN8y..kau9rxBeItuDoOilbwnevQWh4ApTuOXbUHkH1VbIf6', 1, '439233', '80589120a1a56d7f351e4e3ec916a16f0f143d5edf2576aa38e256600edae93069d58bb52bdb1463a4c0677c655432de90dd', 'admin', '2025-04-22 08:30:10'),
(2, 'Niran-jan', 'katwalniranjan40@gmail.com', '$2y$10$KJR4nYm3n4jZmAu1EICuweHK5l3N7pvxo2Sz6SzK3crHuGJn8JCQm', 1, '807377', NULL, 'user', '2025-04-22 08:48:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`gadget_id`),
  ADD KEY `gadget_id` (`gadget_id`);

--
-- Indexes for table `gadgets`
--
ALTER TABLE `gadgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `gadget_id` (`gadget_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`),
  ADD KEY `fk_gadget` (`gadget_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `gadgets`
--
ALTER TABLE `gadgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`gadget_id`) REFERENCES `gadgets` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`gadget_id`) REFERENCES `gadgets` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_gadget` FOREIGN KEY (`gadget_id`) REFERENCES `gadgets` (`id`),
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`gadget_id`) REFERENCES `gadgets` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
