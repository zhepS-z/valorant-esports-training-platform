-- Migration: Add valorant_agents and valorant_maps tables for admin management
-- Run this in phpMyAdmin or MySQL

-- --------------------------------------------------------
-- Table: valorant_agents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `valorant_agents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `role` enum('Controller','Sentinel','Initiator','Duelist') NOT NULL,
  `image_url` varchar(500) NOT NULL COMMENT 'URL or path: valorant-api URL or img/agents/filename',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: valorant_maps
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `valorant_maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `image_filename` varchar(100) NOT NULL COMMENT 'e.g. ascent.png - used in img/maps/ and img/maps_button/',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed: valorant_agents (from existing agent.php mapping)
-- --------------------------------------------------------
INSERT IGNORE INTO `valorant_agents` (`name`, `role`, `image_url`, `display_order`) VALUES
('Jett', 'Duelist', 'https://media.valorant-api.com/agents/add6443a-41bd-e414-f6ad-e58d267f4e95/displayicon.png', 1),
('Raze', 'Duelist', 'https://media.valorant-api.com/agents/f94c3b30-42be-e959-889c-5aa313dba261/displayicon.png', 2),
('Breach', 'Initiator', 'https://media.valorant-api.com/agents/5f8d3a7f-467b-97f3-062c-13acf203c006/displayicon.png', 3),
('Omen', 'Controller', 'https://media.valorant-api.com/agents/8e253930-4c05-31dd-1b6c-968525494517/displayicon.png', 4),
('Brimstone', 'Controller', 'https://media.valorant-api.com/agents/9f0d8ba9-4140-b941-57d3-a7ad57c6b417/displayicon.png', 5),
('Phoenix', 'Duelist', 'https://media.valorant-api.com/agents/eb93336a-449b-9c1b-0a54-a891f7921d69/displayicon.png', 6),
('Sage', 'Sentinel', 'https://media.valorant-api.com/agents/569fdd95-4d10-43ab-ca70-79becc718b46/displayicon.png', 7),
('Sova', 'Initiator', 'https://media.valorant-api.com/agents/320b2a48-4d9b-a075-30f1-1f93a9b638fa/displayicon.png', 8),
('Viper', 'Controller', 'https://media.valorant-api.com/agents/707eab51-4836-f488-046a-cda6bf494859/displayicon.png', 9),
('Cypher', 'Sentinel', 'https://media.valorant-api.com/agents/117ed9e3-49f3-6512-3ccf-0cada7e3823b/displayicon.png', 10),
('Reyna', 'Duelist', 'https://media.valorant-api.com/agents/a3bfb853-43b2-7238-a4f1-ad90e9e46bcc/displayicon.png', 11),
('Killjoy', 'Sentinel', 'https://media.valorant-api.com/agents/1e58de9c-4950-5125-93e9-a0aee9f98746/displayicon.png', 12),
('Skye', 'Initiator', 'https://media.valorant-api.com/agents/6f2a04ca-43e0-be17-7f36-b3908627744d/displayicon.png', 13),
('Yoru', 'Duelist', 'https://media.valorant-api.com/agents/7f94d92c-4234-0a36-9646-3a87eb8b5c89/displayicon.png', 14),
('Astra', 'Controller', 'https://media.valorant-api.com/agents/41fb69c1-4189-7b37-f117-bcaf1e96f1bf/displayicon.png', 15),
('KAY/O', 'Initiator', 'https://media.valorant-api.com/agents/601dbbe7-43ce-be57-2a40-4abd24953621/displayicon.png', 16),
('Chamber', 'Sentinel', 'https://media.valorant-api.com/agents/22697a3d-45bf-8dd7-4fec-84a9e28c69d7/displayicon.png', 17),
('Neon', 'Duelist', 'https://media.valorant-api.com/agents/bb2a4828-46eb-8cd1-e765-15848195d751/displayicon.png', 18),
('Fade', 'Initiator', 'https://media.valorant-api.com/agents/dade69b4-4f5a-8528-247b-219e5a1facd6/displayicon.png', 19),
('Harbor', 'Controller', 'https://media.valorant-api.com/agents/95b78ed7-4637-86d9-7e41-71ba8c293152/displayicon.png', 20),
('Gekko', 'Initiator', 'https://media.valorant-api.com/agents/e370fa57-4757-3604-3648-499e1f642d3f/displayicon.png', 21),
('Deadlock', 'Sentinel', 'https://media.valorant-api.com/agents/cc8b64c8-4b25-4ff9-6e7f-37b4da43d235/displayicon.png', 22),
('Iso', 'Duelist', 'https://media.valorant-api.com/agents/0e38b510-41a8-5780-5e8f-568b2a4f2d6c/displayicon.png', 23),
('Clove', 'Controller', 'https://media.valorant-api.com/agents/1dbf2edd-4729-0984-3115-daa5eed44993/displayicon.png', 24);

-- --------------------------------------------------------
-- Seed: valorant_maps (from existing img/maps)
-- --------------------------------------------------------
INSERT IGNORE INTO `valorant_maps` (`name`, `image_filename`, `display_order`) VALUES
('Ascent', 'ascent.png', 1),
('Bind', 'bind.png', 2),
('Haven', 'haven.png', 3),
('Split', 'split.png', 4),
('Icebox', 'icebox.png', 5),
('Breeze', 'breeze.png', 6),
('Fracture', 'fracture.png', 7),
('Pearl', 'pearl.png', 8),
('Lotus', 'lotus.png', 9),
('Sunset', 'sunset.png', 10),
('Abyss', 'abyss.png', 11);
