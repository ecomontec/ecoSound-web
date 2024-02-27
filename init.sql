-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 11, 2021 at 07:48 AM
-- Server version: 10.4.20-MariaDB
-- PHP Version: 7.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
DROP DATABASE `biosounds`;
--
-- Database: `biosounds`
--
CREATE DATABASE IF NOT EXISTS `biosounds` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `biosounds`;

SELECT concat('DROP TABLE IF EXISTS ', table_name, ';')
FROM information_schema.tables
WHERE table_schema = 'biosounds';
-- --------------------------------------------------------

--
-- Table structure for table `index_log`
--
CREATE TABLE `index_log`
(
    `log_id`         int(11) NOT NULL,
    `recording_id`   int(11) NOT NULL,
    `user_id`        int(11) NOT NULL,
    `index_id`       int(11) NOT NULL,
    `version`        varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `min_time`       varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `max_time`       varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `min_frequency`  varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `max_frequency`  varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `input_number`   int(11) NOT NULL,
    `input_name`     varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `input_value`    varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `output_name`    varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `output_value`   varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `creation_date`  timestamp NOT NULL                   DEFAULT current_timestamp(),
     INDEX `log_id`(`log_id` ASC, `recording_id` ASC, `index_id` ASC) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `models`
--
CREATE TABLE `models`
(
    `tf_model_id`   int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name`          varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `tf_model_path` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `labels_path`   varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `source_URL`    varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
    `parameter` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `index_type`
--
CREATE TABLE `index_type`
(
    `index_id`    int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name`        varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `param`       varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `URL`         varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `collection`
--
CREATE TABLE `collection`
(
    `collection_id` int(11) NOT NULL,
    `project_id`    int(11) NOT NULL DEFAULT 101,
    `name`          varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `user_id`       int(11) NOT NULL,
    `doi`           varchar(255) COLLATE utf8_unicode_ci          DEFAULT NULL COMMENT 'Citation in scientific format or full URL',
    `note`          text COLLATE utf8_unicode_ci                  DEFAULT NULL,
    `view`          enum('gallery','list','timeline') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'gallery',
    `sphere`        varchar(100) COLLATE utf8_unicode_ci          DEFAULT NULL,
    `public_access` tinyint(1) NOT NULL DEFAULT 0,
    `public_tags`   tinyint(1) NOT NULL DEFAULT 0,
    `creation_date` timestamp                            NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `file_upload`
--
CREATE TABLE `file_upload`
(
    `file_upload_id` int(11) NOT NULL,
    `path`           text COLLATE utf8_unicode_ci         NOT NULL,
    `status`         int(11) NOT NULL DEFAULT 1,
    `filename`       varchar(250) COLLATE utf8_unicode_ci NOT NULL,
    `doi`            varchar(255) COLLATE utf8_unicode_ci          DEFAULT NULL,
    `license_id`     int(11) NOT NULL,
    `date`           date                                          DEFAULT NULL,
    `time`           time                                          DEFAULT NULL,
    `recording_id`   int(11) DEFAULT NULL,
    `site_id`        int(11) DEFAULT NULL,
    `collection_id`  int(11) NOT NULL,
    `directory`      int(11) NOT NULL,
    `recorder_id`    int(11) DEFAULT NULL,
    `microphone_id`  int(11) DEFAULT NULL,
    `species_id`     int(11) DEFAULT NULL,
    `sound_type_id`  int(11) DEFAULT NULL,
    `subtype`        char(1) COLLATE utf8_unicode_ci               DEFAULT NULL,
    `rating`         enum('A','B','C','D','E') COLLATE utf8_unicode_ci DEFAULT NULL,
    `type`           varchar(50) COLLATE utf8_unicode_ci           DEFAULT NULL,
    `medium`         varchar(50) COLLATE utf8_unicode_ci           DEFAULT NULL,
    `user_id`        int(11) NOT NULL,
    `error`          text COLLATE utf8_unicode_ci                  DEFAULT NULL,
    `creation_date`  timestamp                            NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp ()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `license`
--
CREATE TABLE `license`
(
    `license_id` int(11) NOT NULL,
    `name`       varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `link`       varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `news`
--
CREATE TABLE `news`
(
    `news_id`       int(10) UNSIGNED NOT NULL,
    `title`         varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `content`       text COLLATE utf8_unicode_ci         NOT NULL,
    `creation_date` datetime                             NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `permission`
--
CREATE TABLE `permission`
(
    `permission_id` int(11) NOT NULL,
    `name`          varchar(128) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `play_log`
--
CREATE TABLE `play_log`
(
    `play_log_id`  int(11) NOT NULL,
    `recording_id` int(11) NOT NULL,
    `user_id`      int(11) NOT NULL,
    `start_time`   datetime NOT NULL,
    `stop_time`    datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `project`
--
CREATE TABLE `project`
(
    `project_id`        int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name`              varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `creator_id`        int(11) NOT NULL,
    `url`               varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `picture_id`        varchar(255) COLLATE utf8_unicode_ci,
    `description`       longblob,
    `description_short` longblob,
    `public`            tinyint(1) NOT NULL DEFAULT 1,
    `active`            tinyint(1) NOT NULL DEFAULT 1,
    `creation_date`     timestamp                            NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `recording`
--
CREATE TABLE `recording`
(
    `recording_id`  int(11) NOT NULL,
    `data_type`     enum('meta-data','audio data') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'audio data',
    `col_id`        int(11) NOT NULL,
    `directory`     int(11) DEFAULT NULL,
    `filename`      varchar(150) COLLATE utf8_unicode_ci DEFAULT NULL,
    `name`          varchar(160) COLLATE utf8_unicode_ci DEFAULT NULL,
    `user_id`       int(11) DEFAULT NULL,
    `site_id`       int(11) DEFAULT NULL,
    `recorder_id`   int(11) DEFAULT NULL,
    `microphone_id` int(11) DEFAULT NULL,
    `license_id`    int(11) DEFAULT NULL,
    `type`          varchar(50) COLLATE utf8_unicode_ci  DEFAULT NULL,
    `medium`        varchar(50) COLLATE utf8_unicode_ci  DEFAULT NULL,
    `note`          varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
    `file_date`     date                                 DEFAULT NULL,
    `file_time`     time                                 DEFAULT NULL,
    `file_size`     int(11) DEFAULT NULL,
    `md5_hash`      char(32) COLLATE utf8_unicode_ci     DEFAULT NULL COMMENT 'MD5 hash of the file, to verify that the file has not been changed.',
    `DOI`           varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `sampling_rate` int(11) NOT NULL DEFAULT 44100,
    `bitrate`       int(11) DEFAULT NULL DEFAULT 16,
    `channel_num`   int(1) DEFAULT NULL DEFAULT 1,
    `duration`      float     NOT NULL,
    `creation_date` timestamp NOT NULL                   DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `role`
--
CREATE TABLE `role`
(
    `role_id` int(11) NOT NULL,
    `name`    varchar(128) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `setting`
--
CREATE TABLE `setting`
(
    `name`  varchar(20) COLLATE utf8_unicode_ci  NOT NULL,
    `value` varchar(250) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `site`
--

CREATE TABLE `site`
(
    `site_id`                 int(11) NOT NULL,
    `user_id`                 int(11) NOT NULL,
    `name`                    varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `longitude_WGS84_dd_dddd` double DEFAULT NULL,
    `latitude_WGS84_dd_dddd`  double DEFAULT NULL,
    `topography_m`            double DEFAULT NULL,
    `freshwater_depth_m`      double DEFAULT NULL,
    `gadm0`                   varchar(100),
    `gadm1`                   varchar(100),
    `gadm2`                   varchar(100),
    `realm_id`                int(11),
    `biome_id`                int(11),
    `functional_type_id`      int(11),
    `creation_date_time`      datetime                             NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `sound`
--
CREATE TABLE `sound`
(
    `sound_id`   int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `phony`      varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
    `sound_type` varchar(30) COLLATE utf8_unicode_ci  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `recorder`
--
CREATE TABLE `recorder`
(
    `recorder_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `model`       varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `version`     varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `brand`       varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `microphone`  varchar(300) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `microphone`
--
CREATE TABLE `microphone`
(
    `microphone_id`         int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name`                  varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `microphone_element`    varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `sensitivity`           int(11) DEFAULT NULL,
    `signal_to_noise_ratio` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `sound_type`
--
CREATE TABLE `sound_type`
(
    `sound_type_id` int(11) NOT NULL,
    `name`          varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `taxon_class`   varchar(20) COLLATE utf8_unicode_ci  NOT NULL,
    `taxon_order`   varchar(20) COLLATE utf8_unicode_ci  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `species`
--
CREATE TABLE `species`
(
    `species_id`  int(11) NOT NULL,
    `binomial`    varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `genus`       varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `family`      varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `taxon_order` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `class`       varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
    `common_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
    `level`       int(11) NOT NULL,
    `source`      varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `spectrogram`
--
CREATE TABLE `spectrogram`
(
    `spectrogram_id` int(11) NOT NULL,
    `recording_id`   int(11) NOT NULL,
    `filename`       varchar(150) COLLATE utf8_unicode_ci NOT NULL,
    `type`           enum('spectrogram','waveform','spectrogram-small','waveform-small','spectrogram-large','waveform-large','spectrogram-player') COLLATE utf8_unicode_ci NOT NULL,
    `max_frequency`  int(11) DEFAULT NULL,
    `fft`            int(11) NOT NULL DEFAULT 1024
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `tag`
--
CREATE TABLE `tag`
(
    `tag_id`                 int(11) NOT NULL,
    `sound_id`               int(11) NOT NULL,
    `recording_id`           int(11) NOT NULL,
    `user_id`                int(11) NOT NULL,
    `creator_type`           varchar(128) COLLATE utf8_unicode_ci         DEFAULT 'user',
    `confidence`             float                                        DEFAULT NULL,
    `min_time`               float                               NOT NULL,
    `max_time`               float                               NOT NULL,
    `min_freq`               float                               NOT NULL,
    `max_freq`               float                               NOT NULL,
    `species_id`             int(11) DEFAULT NULL,
    `uncertain`              tinyint(1) DEFAULT NULL COMMENT 'data lost before 18.10.2015',
    `sound_distance_m`       int(4) DEFAULT NULL,
    `distance_not_estimable` tinyint(1) DEFAULT NULL,
    `individuals`            int(2) NOT NULL,
    `animal_sound_type`      varchar(128) COLLATE utf8_unicode_ci         DEFAULT NULL,
    `reference_call`         tinyint(1) NOT NULL,
    `comments`               varchar(500) COLLATE utf8_unicode_ci         DEFAULT NULL,
    `creation_date`          timestamp                           NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `tag_review`
--
CREATE TABLE `tag_review`
(
    `tag_id`               int(11) NOT NULL,
    `user_id`              int(11) NOT NULL,
    `tag_review_status_id` int(11) NOT NULL,
    `species_id`           int(11) DEFAULT NULL,
    `note`                 varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
    `creation_date`        timestamp NOT NULL                   DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `tag_review_status`
--
CREATE TABLE `tag_review_status`
(
    `tag_review_status_id` int(11) NOT NULL,
    `name`                 varchar(128) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user`
--
CREATE TABLE `user`
(
    `user_id`    int(11) NOT NULL,
    `role_id`    int(11) NOT NULL,
    `project_id` int(11) NOT NULL DEFAULT 101,
    `username`   varchar(20) COLLATE utf8_unicode_ci  NOT NULL,
    `password`   varchar(150) COLLATE utf8_unicode_ci NOT NULL,
    `name`       varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `orcid`      varchar(100) COLLATE utf8_unicode_ci          DEFAULT NULL,
    `email`      varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `color`      varchar(7) COLLATE utf8_unicode_ci   NOT NULL DEFAULT '#FFFFFF',
    `active`     tinyint(1) NOT NULL DEFAULT 1,
    `fft`        int(11) NOT NULL DEFAULT 512
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_permission`
--
CREATE TABLE `user_permission`
(
    `user_id`       int(11) NOT NULL,
    `collection_id` int(11) NOT NULL,
    `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `site_collection`
--
CREATE TABLE `site_collection`
(
    `site_id`       int(11) NOT NULL,
    `collection_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `recording_fft`
--
CREATE TABLE `recording_fft`
(
    `user_id`      int(11) NOT NULL,
    `recording_id` int(11) NOT NULL,
    `fft`          int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `label`
--
CREATE TABLE `label`
(
    `label_id`      int(11) NOT NULL AUTO_INCREMENT,
    `name`          varchar(20) COLLATE utf8_unicode_ci NOT NULL,
    `creator_id`    int(11) NOT NULL default -1,
    `type`          enum('private', 'public') NOT NULL DEFAULT 'private',
    `creation_date` datetime                            NOT NULL,
    PRIMARY KEY (`label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `label_association`
--
CREATE TABLE `label_association`
(
    `recording_id` int(11) NOT NULL,
    `user_id`      int(11) NOT NULL,
    `label_id`     int(11) NOT NULL,
    KEY            `recording_id_idx` (`recording_id`) USING BTREE,
    KEY            `user_id_idx` (`user_id`) USING BTREE,
    KEY            `label_id_idx` (`label_id`) USING BTREE,
    UNIQUE `label_association_uniq`(`recording_id`, `user_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `iucn_get`
--
CREATE TABLE `iucn_get`
(
    `iucn_get_id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `pid`         int(11) NOT NULL,
    `name`        varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `level`       int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for queue
--
CREATE TABLE `queue`
(
    `queue_id`      int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `type`  varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `user_id` int(11) NOT NULL,
    `completed` int(11) NOT NULL default 0,
    `total` int(11) NOT NULL default 0,
    `status` int(11) NOT NULL default 0,
    `start_time` datetime,
    `stop_time` datetime,
    `error`  text COLLATE utf8_unicode_ci,
    `warning`  text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `collection`
--
ALTER TABLE `collection`
    ADD PRIMARY KEY (`collection_id`);

--
-- Indexes for table `file_upload`
--
ALTER TABLE `file_upload`
    ADD PRIMARY KEY (`file_upload_id`);

--
-- Indexes for table `license`
--
ALTER TABLE `license`
    ADD PRIMARY KEY (`license_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
    ADD PRIMARY KEY (`news_id`);

--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
    ADD PRIMARY KEY (`permission_id`);

--
-- Indexes for table `play_log`
--
ALTER TABLE `play_log`
    ADD PRIMARY KEY (`play_log_id`) USING BTREE,
  ADD KEY `recording_id` (`recording_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recording`
--
ALTER TABLE `recording`
    ADD PRIMARY KEY (`recording_id`),
  ADD KEY `site_id_idx` (`site_id`) USING BTREE,
  ADD KEY `col_id_idx` (`col_id`) USING BTREE;

--
-- Indexes for table `role`
--
ALTER TABLE `role`
    ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `ID` (`role_id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
    ADD PRIMARY KEY (`name`);

--
-- Indexes for table `site`
--
ALTER TABLE `site`
    ADD PRIMARY KEY (`site_id`),
  ADD KEY `user_id_idx` (`user_id`) USING BTREE;

--
-- Indexes for table `sound_type`
--
ALTER TABLE `sound_type`
    ADD PRIMARY KEY (`sound_type_id`);

--
-- Indexes for table `species`
--
ALTER TABLE `species`
    ADD PRIMARY KEY (`species_id`);

--
-- Indexes for table `spectrogram`
--
ALTER TABLE `spectrogram`
    ADD PRIMARY KEY (`spectrogram_id`),
  ADD KEY `recording_id_idx` (`recording_id`) USING BTREE;

--
-- Indexes for table `tag`
--
ALTER TABLE `tag`
    ADD PRIMARY KEY (`tag_id`),
  ADD KEY `species_id_idx` (`species_id`) USING BTREE,
  ADD KEY `user_id` (`user_id`) USING BTREE,
  ADD KEY `recording_id_idx` (`recording_id`);

--
-- Indexes for table `tag_review`
--
ALTER TABLE `tag_review`
    ADD PRIMARY KEY (`tag_id`, `user_id`),
  ADD KEY `tag_review_status_id_idx` (`tag_review_status_id`) USING BTREE,
  ADD KEY `tag_review_user_id_idx` (`user_id`) USING BTREE,
  ADD KEY `tag_review_species_id_idx` (`species_id`) USING BTREE;

--
-- Indexes for table `tag_review_status`
--
ALTER TABLE `tag_review_status`
    ADD PRIMARY KEY (`tag_review_status_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
    ADD PRIMARY KEY (`user_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_permission`
--
ALTER TABLE `user_permission`
    ADD PRIMARY KEY (`user_id`, `collection_id`),
  ADD KEY `permission` (`permission_id`),
  ADD KEY `collection` (`collection_id`);

--
-- AUTO_INCREMENT for table `collection`
--
ALTER TABLE `collection`
    MODIFY `collection_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_upload`
--
ALTER TABLE `file_upload`
    MODIFY `file_upload_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `license`
--
ALTER TABLE `license`
    MODIFY `license_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
    MODIFY `news_id` int (10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
    MODIFY `permission_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `play_log`
--
ALTER TABLE `play_log`
    MODIFY `play_log_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recording`
--
ALTER TABLE `recording`
    MODIFY `recording_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
    MODIFY `role_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site`
--
ALTER TABLE `site`
    MODIFY `site_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sound`
--
ALTER TABLE `sound`
    MODIFY `sound_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sound_type`
--
ALTER TABLE `sound_type`
    MODIFY `sound_type_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spectrogram`
--
ALTER TABLE `spectrogram`
    MODIFY `spectrogram_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tag`
--
ALTER TABLE `tag`
    MODIFY `tag_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tag_review_status`
--
ALTER TABLE `tag_review_status`
    MODIFY `tag_review_status_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
    MODIFY `user_id` int (11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `play_log`
--
ALTER TABLE `play_log`
    ADD CONSTRAINT `recording_id_fk` FOREIGN KEY (`recording_id`) REFERENCES `recording` (`recording_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON
DELETE
CASCADE ON
UPDATE CASCADE;

--
-- Constraints for table `recording`
--
ALTER TABLE `recording`
    ADD CONSTRAINT `col_id_fk` FOREIGN KEY (`col_id`) REFERENCES `collection` (`collection_id`) ON UPDATE CASCADE;

--
-- Constraints for table `spectrogram`
--
ALTER TABLE `spectrogram`
    ADD CONSTRAINT `image_recording_id_fk` FOREIGN KEY (`recording_id`) REFERENCES `recording` (`recording_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tag`
--
ALTER TABLE `tag`
    ADD CONSTRAINT `tag_recording_id_fk` FOREIGN KEY (`recording_id`) REFERENCES `recording` (`recording_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tag_species_id_fk` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`) ON
DELETE
CASCADE ON
UPDATE CASCADE,
    ADD CONSTRAINT `tag_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
ON
DELETE
CASCADE ON
UPDATE CASCADE;

--
-- Constraints for table `tag_review`
--
ALTER TABLE `tag_review`
    ADD CONSTRAINT `tag_review_species_id_fk` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tag_review_status_id_fk` FOREIGN KEY (`tag_review_status_id`) REFERENCES `tag_review_status` (`tag_review_status_id`) ON
DELETE
CASCADE ON
UPDATE CASCADE,
    ADD CONSTRAINT `tag_review_tag_id_fk` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`tag_id`)
ON
DELETE
CASCADE ON
UPDATE CASCADE,
    ADD CONSTRAINT `tag_review_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
ON
DELETE
CASCADE ON
UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
    ADD CONSTRAINT `role_id_fk` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_permission`
--
ALTER TABLE `user_permission`
    ADD CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`permission_id`) ON
UPDATE CASCADE,
    ADD CONSTRAINT `user_permission_ibfk_3` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`collection_id`)
ON
UPDATE CASCADE;

--
-- Constraints for table `site`
--
ALTER TABLE `site`
    ADD CONSTRAINT `site_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `label_association`
--
ALTER TABLE `label_association`
    ADD CONSTRAINT `label_id_assoc_fk` FOREIGN KEY (`label_id`) REFERENCES `label` (`label_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `recording_id_assoc_fk` FOREIGN KEY (`recording_id`) REFERENCES `recording` (`recording_id`) ON
UPDATE CASCADE,
    ADD CONSTRAINT `user_id_assoc_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
ON
UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

