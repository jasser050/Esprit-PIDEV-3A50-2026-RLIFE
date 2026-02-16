-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 08 fév. 2026 à 21:36
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Structure de la table `coping_session`
--

CREATE TABLE `coping_session` (
  `id` int(11) NOT NULL,
  `tool_key` varchar(50) NOT NULL,
  `tool_name` varchar(120) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `actual_seconds` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `started_at` varchar(255) NOT NULL,
  `finished_at` varchar(255) DEFAULT NULL,
  `created_at` varchar(255) NOT NULL,
  `updated_at` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `question_stress` (
  `id` int(11) NOT NULL,
  `question_number_ques` int(11) NOT NULL,
  `question_text_ques` longtext NOT NULL,
  `is_active_ques` tinyint(1) NOT NULL,
  `created_at_ques` datetime NOT NULL,
  `updated_at_ques` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quiz_stress`
--

CREATE TABLE `quiz_stress` (
  `id` int(11) NOT NULL,
  `quiz_date_quiz` datetime NOT NULL,
  `answers_quiz` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers_quiz`)),
  `total_score_quiz` int(11) NOT NULL,
  `stress_level_quiz` varchar(50) NOT NULL,
  `interpretation_quiz` longtext NOT NULL,
  `created_with_ai_quiz` tinyint(1) NOT NULL,
  `ai_model_quiz` varchar(255) DEFAULT NULL,
  `ai_prompt_version_quiz` varchar(255) DEFAULT NULL,
  `created_at_quiz` datetime NOT NULL,
  `updated_at_quiz` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recommendation_stress`
--

CREATE TABLE `recommendation_stress` (
  `id` int(11) NOT NULL,
  `quiz_stress_id` int(11) NOT NULL,
  `recommendation_type_rec` varchar(100) NOT NULL,
  `content_rec` longtext NOT NULL,
  `priority_rec` varchar(50) NOT NULL,
  `generation_date_rec` datetime NOT NULL,
  `source_rec` varchar(100) NOT NULL,
  `status_rec` varchar(50) NOT NULL,
  `created_at_rec` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `revision_flashcard`
--

CREATE TABLE `revision_flashcard` (

CREATE TABLE `well_being` (
  `id` int(11) NOT NULL,
  `entry_date_well` datetime NOT NULL,
  `mood_well` varchar(50) NOT NULL,
  `stress_level_well` int(11) NOT NULL,
  `energy_level_well` int(11) NOT NULL,
  `sleep_hours_well` double NOT NULL,
  `note_well` longtext DEFAULT NULL,
  `created_at_well` datetime NOT NULL,
  `updated_at_well` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `well_being`
--

INSERT INTO `well_being` (`id`, `entry_date_well`, `mood_well`, `stress_level_well`, `energy_level_well`, `sleep_hours_well`, `note_well`, `created_at_well`, `updated_at_well`) VALUES
(1, '2026-02-07 23:36:15', 'stressed', 10, 2, 4, 'so hello hello', '2026-02-07 23:36:15', NULL),
(3, '2026-02-08 00:02:20', 'okay', 1, 10, 8, 'ok', '2026-02-08 00:02:20', '2026-02-08 00:03:48'),
(4, '2026-02-08 01:10:43', 'okay', 5, 7, 7, 'ok', '2026-02-08 01:10:43', NULL),

-- Index pour la table `coping_session`
--
ALTER TABLE `coping_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3155C679A76ED395` (`user_id`);

--

-- AUTO_INCREMENT pour la table `coping_session`
--
ALTER TABLE `coping_session`
