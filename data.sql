-- phpMyAdmin SQL Dump
-- version 4.4.15.5
-- http://www.phpmyadmin.net
--

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `biosounds`
--

--
-- Dumping data for table `index_type`
--
INSERT INTO `index_type` (`index_id`, `name`, `param`, `description`, `URL`)
VALUES ('1', 'acoustic_complexity_index', '', 'Compute the Acoustic Complexity Index (ACI) from a spectrogram.', 'https://scikit-maad.github.io/generated/maad.features.acoustic_complexity_index.html'),
       ('2', 'soundscape_index', 'flim_bioPh|1000,10000!flim_antroPh|0,1000!R_compatible|soundecology', 'Compute the Normalized Difference Soundscape Index from a power spectrogram.',
        'https://scikit-maad.github.io/generated/maad.features.soundscape_index.html'),
       ('3', 'temporal_median', 'mode|fast!Nt|512', 'Computes the median of the envelope of an audio signal.', 'https://scikit-maad.github.io/generated/maad.features.temporal_median.html'),
       ('4', 'temporal_entropy', 'mode|fast!Nt|512', 'Computes the entropy of the envelope of an audio signal.', 'https://scikit-maad.github.io/generated/maad.features.temporal_entropy.html'),
       ('5', 'temporal_activity', 'dB_threshold|3!mode|fast!Nt|512', 'Compute the acoustic activity index in temporal domain.', 'https://scikit-maad.github.io/generated/maad.features.temporal_activity.html'),
       ('6', 'temporal_events', 'dB_threshold|3!rejectDuration|None!mode|fast!Nt|512!display|False', 'Compute the acoustic event index from an audio signal', 'https://scikit-maad.github.io/generated/maad.features.temporal_events.html'),
       ('7', 'frequency_entropy', 'compatibility|QUT', 'Computes the spectral entropy of a power spectral density (1d) or power spectrogram density (2d).', 'https://scikit-maad.github.io/generated/maad.features.frequency_entropy.html'),
       ('8', 'number_of_peaks', 'mode|dB!min_peak_val|None!min_freq_dist|200!slopes|1,1!prominence|None!display|False', 'Count the number of frequency peaks on a mean spectrum.',
        'https://scikit-maad.github.io/generated/maad.features.number_of_peaks.html'),
       ('9', 'spectral_entropy', 'flim|None!display|False', 'Compute different entropies based on the average spectrum, its variance, and its maxima', 'https://scikit-maad.github.io/generated/maad.features.spectral_entropy.html'),
       ('10', 'spectral_activity', 'dB_threshold|6', 'Compute the acoustic activity on a spectrogram.', 'https://scikit-maad.github.io/generated/maad.features.spectral_activity.html'),
       ('11', 'spectral_cover', 'flim_LF|0,1000!flim_MF|1000,10000!flim_HF|10000,20000',
        'Compute the proportion (cover) of the spectrogram above a threshold for three bandwidths : low frequency band (LF), medium frequency band (MF) and high frequency band (HF)',
        'https://scikit-maad.github.io/generated/maad.features.spectral_cover.html'),
       ('12', 'bioacoustics_index', 'flim|2000,15000!R_compatible|soundecology', 'Compute the Bioacoustics Index from a spectrogram', 'https://scikit-maad.github.io/generated/maad.features.bioacoustics_index.html'),
       ('13', 'acoustic_diversity_index', 'fmin|0!fmax|20000!bin_step|500!dB_threshold|-50!index|shannon', 'Compute the Acoustic Diversity Index (ADI) from a spectrogram',
        'https://scikit-maad.github.io/generated/maad.features.acoustic_diversity_index.html'),
       ('14', 'acoustic_eveness_index', 'fmin|0!fmax|20000!bin_step|500!dB_threshold|-50', 'Compute the Acoustic Eveness Index (AEI) from a spectrogram', 'https://scikit-maad.github.io/generated/maad.features.acoustic_eveness_index.html'),
       ('15', 'temporal_leq', 'gain|42!Vadc|2!sensitivity|-35!dBref|94!dt|1', 'Computes the Equivalent Continuous Sound level (Leq) of an audio signal in the time domain.', 'https://scikit-maad.github.io/generated/maad.features.temporal_leq.html'),
       ('16', 'spectral_leq', 'gain|42!Vadc|2!sensitivity|-35!dBref|94!pRef|20e-6', 'Computes the Equivalent Continuous Sound level (Leq) from a power spectrum (1d) or power spectrogram (2d).',
        'https://scikit-maad.github.io/generated/maad.features.spectral_leq.html'),
       ('17', 'tfsd', 'flim|2000,8000!mode|thirdOctave!display|False', 'Compute the Time frequency derivation index (tfsd) from a spectrogram.', 'https://scikit-maad.github.io/generated/maad.features.tfsd.html'),
       ('18', 'more_entropy_time', 'order|3!axis|0', 'Compute the entropy of an audio signal using multiple methods.', 'https://scikit-maad.github.io/generated/maad.features.more_entropy.html'),
       ('19', 'acoustic_gradient_index', 'norm|per_bin', 'Compute the Acoustic Gradient Index (AGI) from a raw spectrogram.', 'https://scikit-maad.github.io/generated/maad.features.acoustic_gradient_index.html'),
       ('20', 'frequency_raoq', 'bin_step|1000', 'Compute Rao`s quadratic entropy on a power spectrum (1d).', 'https://scikit-maad.github.io/generated/maad.features.frequency_raoq.html'),
       ('21', 'more_entropy_spectral', 'order|3!axis|0', 'Compute the entropy of an audio signal using multiple methods.', 'https://scikit-maad.github.io/generated/maad.features.more_entropy.html');

--
-- Dumping data for table `collection`
--
INSERT INTO `collection` (`collection_id`, `project_id`, `name`, `user_id`, `doi`, `note`, `view`, `public_access`, `public_tags`)
VALUES (1, 101, 'Demo collection', 100, '', 'open access', 'gallery', '1', '1');

--
-- Dumping data for table `license`
--
INSERT INTO `license` (`license_id`, `name`, `link`)
VALUES (1, 'Copyright', ''),
       (2, 'CC0', 'https://creativecommons.org/publicdomain/zero/1.0/'),
       (3, 'CC-BY', 'https://creativecommons.org/licenses/by/4.0'),
       (4, 'CC-BY-SA', 'https://creativecommons.org/licenses/by-sa/4.0/'),
       (5, 'CC-BY-NC', 'https://creativecommons.org/licenses/by-nc/4.0'),
       (6, 'CC-BY-NC-SA', 'https://creativecommons.org/licenses/by-nc-sa/4.0'),
       (7, 'CC-BY-ND', 'https://creativecommons.org/licenses/by-nd/4.0/'),
       (8, 'CC-BY-NC-ND', 'https://creativecommons.org/licenses/by-nc-nd/4.0');

--
-- Dumping data for table `setting`
--
INSERT INTO `setting` (`name`, `value`)
VALUES ('allow_upload', '0'),
       ('cores_to_use', '1'),
       ('default_qf', '0'),
       ('fft', '512'),
       ('filesLicense', 'Copyright'),
       ('filesLicenseDetail', 'Kevin Darras'),
       ('googleanalytics_ID', ''),
       ('googlemaps_key', ''),
       ('googlemaps3_key', ''),
       ('guests_can_dl', '0'),
       ('guests_can_open', '0'),
       ('hide_latlon_guests', '0'),
       ('map_only', '0'),
       ('projectDescription', ''),
       ('projectName', 'SoundEFForTS'),
       ('public_leveldata', '0'),
       ('sidetoside_comp', '1'),
       ('sox_version', '14.4.1'),
       ('spectrogram_palette', '1'),
       ('temp_add_dir', ''),
       ('use_chorus', '0'),
       ('use_googlemaps', '0'),
       ('use_tags', '0'),
       ('use_xml', '1'),
       ('wav_toflac', '1'),
       ('server_name', 'test'),
       ('shared', '0');

--
-- Dumping data for table `sound_type`
--
INSERT INTO `sound_type` (`sound_type_id`, `name`, `taxon_class`, `taxon_order`)
VALUES (1, '(Bird) Call - unspecific', 'AVES', ''),
       (2, '(Bird) Song', 'AVES', ''),
       (3, '(Bird) Non-vocal', 'AVES', ''),
       (4, '(Bat) Searching', 'MAMMALIA', 'CHIROPTERA'),
       (5, '(Bat) Feeding', 'MAMMALIA', 'CHIROPTERA'),
       (6, '(Bat) Social', 'MAMMALIA', 'CHIROPTERA'),
       (7, 'Unknown', '', ''),
       (8, '(Bird) Call - contact', 'AVES', ''),
       (9, '(Bird) Call - flight', 'AVES', ''),
       (10, '(Bird) Call - begging', 'AVES', ''),
       (11, '(Amphibia) Courtship', 'AMPHIBIA', ''),
       (12, '(Amphibia) Advertisement towards males', 'AMPHIBIA', ''),
       (13, '(Amphibia) Acquisition/defense of reproductive territories', 'AMPHIBIA', ''),
       (14, '(Amphibia) Discouraging takeover attempts by other males during amplexus', 'AMPHIBIA', ''),
       (15, '(Amphibia) defense of diurnal retreats not used for reproduction', 'AMPHIBIA', ''),
       (16, '(Primate) Agonistic', 'MAMMALIA', 'PRIMATA'),
       (17, '(Primate) Affiliative', 'MAMMALIA', 'PRIMATA'),
       (18, '(Primate) Contact', 'MAMMALIA', 'PRIMATA'),
       (20, '(Primate) Song', 'MAMMALIA', 'PRIMATA'),
       (21, '(Primate) Advertisement - territory', 'MAMMALIA', 'PRIMATA'),
       (22, '(Primate) Advertisement - mating', 'MAMMALIA', 'PRIMATA'),
       (23, '(Primate) Foraging', 'MAMMALIA', 'PRIMATA'),
       (24, '(Primate) Alarm', 'MAMMALIA', 'PRIMATA'),
       (25, '(Primate) Begging', 'MAMMALIA', 'PRIMATA'),
       (26, '(Primate) Adult - offspring', 'MAMMALIA', 'PRIMATA');

--
-- Dumping data for table `species`
--
INSERT INTO `species` (`species_id`, `binomial`, `genus`, `family`, `taxon_order`, `class`, `common_name`, `level`,`source`)
VALUES (1, 'Unknown', '', '', '', '', '', 1,''),
       (2, 'Test bird', 'Test Genus', 'Test Family', '', 'AVES', 'common bird name', 1,''),
       (3, 'Test amphibian', 'Test Genus', 'Test Family', '', 'AMPHIBIA', 'common amphibian name', 1,''),
       (4, 'Test primate', 'Test Genus', 'Test Family', 'PRIMATA', 'MAMMALIA', 'common primate name', 1,'');
--
-- Dumping data for table `tag_review_status`
--
INSERT INTO `tag_review_status` (`tag_review_status_id`, `name`)
VALUES (1, 'Accepted'),
       (2, 'Corrected'),
       (3, 'Rejected'),
       (4, 'Uncertain');
--
-- Dumping data for table `models`
--
INSERT INTO `models` (`tf_model_id`,`name`,`tf_model_path`,`labels_path`,`source_URL`,`description`,`parameter`)
VALUES (1, 'BirdNET-Analyzer', '/BirdNET-Analyzer', '/BirdNET-Analyzer', 'https://github.com/kahst/BirdNET-Analyzer', 'Automated scientific audio data processing and bird ID.', 'sensitivity@Values in [0.5, 1.5]. Defaults to 1.0.$min_conf@Values in [0.01, 0.99]. Defaults to 0.1.$overlap@Values in [0.0, 2.9]. Defaults to 0.0.$sf_thresh@values in [0.01, 0.99]. Defaults to 0.03.'),
       (2, 'batdetect2', '/batdetect2', '/batdetect2', 'https://github.com/macaodha/batdetect2.git', 'Code for detecting and classifying bat echolocation calls in high frequency audio recordings.', 'detection_threshold@Values in [0, 1.0]. Defaults to 0.3.');

--
-- Dumping data for table `project`
--
INSERT INTO `project` (`project_id`, `name`, `description`, `description_short`, `creator_id`, `url`, `picture_id`, `active`)
VALUES (101, 'Testing Project', 'This is a test project, You can set this up via the administration page.', 'testing description', '100', 'https://github.com/ecomontec/ecoSound-web', null, 1);
--
-- Dumping data for table `role`
--
INSERT INTO `role` (`role_id`, `name`)
VALUES (1, 'Administrator'),
       (2, 'User');

--
-- Dumping data for table `user`
--
INSERT INTO `user` (`user_id`, `role_id`, `project_id`, `username`, `password`, `name`, `email`, `color`, `active`, `fft`)
VALUES (100, 1, 101, 'admin', 'JDJ5JDEwJHguRG9TQmZ5dmtiRTRPUEkxRlRKR3VRMTFXUmVNZWVDZkRDcy5QTDRSdENiMWpMNVF6TlMu', 'Administrator', 'admin@biosounds.admin', '#bd2929', 1, 512);

--
-- Dumping data for table `label`
--
INSERT INTO label (label_id, name, type, creation_date)
VALUES (1, 'not analysed', 'public', NOW()),
       (2, 'tagged', 'public', NOW()),
       (3, 'reviewed', 'public', NOW());

--
-- Dumping data for table `permission`
--
INSERT INTO `permission` (`permission_id`, `name`)
VALUES (1, 'View'),
       (2, 'Review'),
       (3, 'Access'),
       (4, 'Manage');
--
-- Dumping data for table `iucn_get`
--
INSERT INTO `iucn_get` (`iucn_get_id`, `pid`, `name`, `level`)
VALUES ('1', '0', 'Terrestrial', '1'),
       ('2', '0', 'Freshwater', '1'),
       ('3', '0', 'Subterranean', '1'),
       ('4', '0', 'Marine', '1'),
       ('5', '0', 'Marine-Terrestrial', '1'),
       ('6', '0', 'Subterranean-Freshwater', '1'),
       ('7', '0', 'Terrestrial-Freshwater', '1'),
       ('8', '0', 'Subterranean-Marine', '1'),
       ('9', '0', 'Marine-Freshwater-Terrestrial', '1'),
       ('10', '0', 'Freshwater-Marine', '1'),
       ('11', '1', 'Tropical-subtropical forests biome', '2'),
       ('12', '1', 'Temperate-boreal forests and woodlands biome', '2'),
       ('13', '1', 'Shrublands and shrubby woodlands biome', '2'),
       ('14', '1', 'Savannas and grasslands biome', '2'),
       ('15', '1', 'Deserts and semi-deserts biome', '2'),
       ('16', '1', 'Polar/alpine (cryogenic) biome', '2'),
       ('17', '1', 'Intensive land-use biome', '2'),
       ('18', '2', 'Rivers and streams biome', '2'),
       ('19', '2', 'Lakes biome', '2'),
       ('20', '2', 'Artificial wetlands biome', '2'),
       ('21', '3', 'Subterranean lithic biome', '2'),
       ('22', '3', 'Anthropogenic subterranean voids biome', '2'),
       ('23', '4', 'Marine shelf biome', '2'),
       ('24', '4', 'Pelagic ocean waters biome', '2'),
       ('25', '4', 'Deep sea floors biome', '2'),
       ('26', '4', 'Anthropogenic marine biome', '2'),
       ('27', '5', 'Shorelines biome', '2'),
       ('28', '5', 'Supralittoral coastal biome', '2'),
       ('29', '5', 'Anthropogenic shorelines biome', '2'),
       ('30', '6', 'Subterranean freshwaters biome', '2'),
       ('31', '6', 'Anthropogenic subterranean freshwaters biome', '2'),
       ('32', '7', 'Palustrine wetlands biome', '2'),
       ('33', '8', 'Subterranean tidal biome', '2'),
       ('34', '9', 'Brackish tidal biome', '2'),
       ('35', '10', 'Semi-confined transitional waters biome', '2'),
       ('36', '11', 'Tropical/Subtropical lowland rainforests', '3'),
       ('37', '11', 'Tropical/Subtropical dry forests and thickets', '3'),
       ('38', '11', 'Tropical/Subtropical montane rainforests', '3'),
       ('39', '11', 'Tropical heath forests', '3'),
       ('40', '12', 'Boreal and temperate high montane forests and woodlands', '3'),
       ('41', '12', 'Deciduous temperate forests', '3'),
       ('42', '12', 'Oceanic cool temperate rainforests', '3'),
       ('43', '12', 'Warm temperate laurophyll forests', '3'),
       ('44', '12', 'Temperate pyric humid forests', '3'),
       ('45', '12', 'Temperate pyric sclerophyll forests and woodlands', '3'),
       ('46', '13', 'Seasonally dry tropical shrublands', '3'),
       ('47', '13', 'Seasonally dry temperate heath and shrublands', '3'),
       ('48', '13', 'Cool temperate heathlands', '3'),
       ('49', '13', 'Young rocky pavements, lava flows and screes', '3'),
       ('50', '14', 'Trophic savannas', '3'),
       ('51', '14', 'Pyric tussock savannas', '3'),
       ('52', '14', 'Hummock savannas', '3'),
       ('53', '14', 'Temperate woodlands', '3'),
       ('54', '14', 'Temperate subhumid grasslands', '3'),
       ('55', '15', 'Semi-desert steppe', '3'),
       ('56', '15', 'Succulent or Thorny deserts and semi-deserts', '3'),
       ('57', '15', 'Sclerophyll hot deserts and semi-deserts', '3'),
       ('58', '15', 'Cool deserts and semi-deserts', '3'),
       ('59', '15', 'Hyper-arid deserts', '3'),
       ('60', '16', 'Ice sheets, glaciers and perennial snowfields', '3'),
       ('61', '16', 'Polar/alpine cliffs, screes, outcrops and lava flows', '3'),
       ('62', '16', 'Polar tundra and deserts', '3'),
       ('63', '16', 'Temperate alpine grasslands and shrublands', '3'),
       ('64', '16', 'Tropical alpine grasslands and herbfields', '3'),
       ('65', '17', 'Annual croplands', '3'),
       ('66', '17', 'Sown pastures and fields', '3'),
       ('67', '17', 'Plantations', '3'),
       ('68', '17', 'Urban and industrial ecosystems Realm', '3'),
       ('69', '17', 'Derived semi-natural pastures and old fields', '3'),
       ('70', '18', 'Permanent upland streams', '3'),
       ('71', '18', 'Permanent lowland rivers', '3'),
       ('72', '18', 'Freeze-thaw rivers and streams', '3'),
       ('73', '18', 'Seasonal upland streams', '3'),
       ('74', '18', 'Seasonal lowland rivers', '3'),
       ('75', '18', 'Episodic arid rivers', '3'),
       ('76', '18', 'Large lowland rivers', '3'),
       ('77', '19', 'Large permanent freshwater lakes', '3'),
       ('78', '19', 'Small permanent freshwater lakes', '3'),
       ('79', '19', 'Seasonal freshwater lakes', '3'),
       ('80', '19', 'Freeze-thaw freshwater lakes', '3'),
       ('81', '19', 'Ephemeral freshwater lakes', '3'),
       ('82', '19', 'Permanent salt and soda lakes', '3'),
       ('83', '19', 'Ephemeral salt lakes', '3'),
       ('84', '19', 'Artesian springs and oases', '3'),
       ('85', '19', 'Geothermal pools and wetlands', '3'),
       ('86', '19', 'Subglacial lakes', '3'),
       ('87', '20', 'Large reservoirs', '3'),
       ('88', '20', 'Constructed lacustrine wetlands', '3'),
       ('89', '20', 'Rice paddies', '3'),
       ('90', '20', 'Freshwater aquafarms', '3'),
       ('91', '20', 'Canals, ditches and drains', '3'),
       ('92', '21', 'Aerobic caves', '3'),
       ('93', '21', 'Endolithic systems', '3'),
       ('94', '22', 'Anthropogenic subterranean voids', '3'),
       ('95', '23', 'Seagrass meadows', '3'),
       ('96', '23', 'Kelp forests', '3'),
       ('97', '23', 'Photic coral reefs', '3'),
       ('98', '23', 'Shellfish beds and reefs', '3'),
       ('99', '23', 'Photo-limited marine animal forests', '3'),
       ('100', '23', 'Subtidal rocky reefs', '3'),
       ('101', '23', 'Subtidal sand beds', '3'),
       ('102', '23', 'Subtidal mud plains', '3'),
       ('103', '23', 'Upwelling zones', '3'),
       ('104', '24', 'Epipelagic ocean waters', '3'),
       ('105', '24', 'Mesopelagic ocean water', '3'),
       ('106', '24', 'Bathypelagic ocean waters', '3'),
       ('107', '24', 'Abyssopelagic ocean waters', '3'),
       ('108', '24', 'Sea ice', '3'),
       ('109', '25', 'Continental and island slopes', '3'),
       ('110', '25', 'Submarine canyons', '3'),
       ('111', '25', 'Abyssal plains', '3'),
       ('112', '25', 'Seamounts, ridges and plateaus', '3'),
       ('113', '25', 'Deepwater biogenic beds', '3'),
       ('114', '25', 'Hadal trenches and troughs', '3'),
       ('115', '25', 'Chemosynthetic-based-ecosystems (CBE)', '3'),
       ('116', '26', 'Submerged artificial structures', '3'),
       ('117', '26', 'Marine aquafarms', '3'),
       ('118', '27', 'Rocky Shorelines', '3'),
       ('119', '27', 'Muddy Shorelines', '3'),
       ('120', '27', 'Sandy Shorelines', '3'),
       ('121', '27', 'Boulder and cobble shores', '3'),
       ('122', '28', 'Coastal shrublands and grasslands', '3'),
       ('123', '29', 'Artificial shorelines', '3'),
       ('124', '30', 'Underground streams and pools', '3'),
       ('125', '30', 'Groundwater ecosystems', '3'),
       ('126', '31', 'Water pipes and subterranean canals', '3'),
       ('127', '31', 'Flooded mines and other voids', '3'),
       ('128', '32', 'Tropical flooded forests and peat forests', '3'),
       ('129', '32', 'Subtropical/temperate forested wetlands', '3'),
       ('130', '32', 'Permanent marshes', '3'),
       ('131', '32', 'Seasonal floodplain marshes', '3'),
       ('132', '32', 'Episodic arid floodplains', '3'),
       ('133', '32', 'Boreal, temperate and montane peat bogs', '3'),
       ('134', '32', 'Boreal and temperate fens', '3'),
       ('135', '33', 'Anchialine caves', '3'),
       ('136', '33', 'Anchialine pools', '3'),
       ('137', '33', 'Sea caves', '3'),
       ('138', '34', 'Coastal river deltas', '3'),
       ('139', '34', 'Intertidal forests and shrublands', '3'),
       ('140', '34', 'Coastal saltmarshes and reedbeds', '3'),
       ('141', '35', 'Deepwater coastal inlets', '3'),
       ('142', '35', 'Permanently open riverine estuaries and bays', '3'),
       ('143', '35', 'Intermittently closed and open lakes and lagoons', '3');

--
-- Dumping data for table `site`
--
INSERT INTO `site` (`site_id`, `name`, `user_id`, `creation_date_time`, `longitude_WGS84_dd_dddd`, `latitude_WGS84_dd_dddd`, `topography_m`, `freshwater_depth_m`, `GADM0`, `GADM1`, `GADM2`, `realm_id`, `biome_id`, `functional_type_id`)
VALUES (1, 'Demo site', 100, now(), 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Dumping data for table `site_collection`
--
INSERT INTO site_collection (site_id, collection_id)
VALUES (1, 1);

--
-- Dumping data for table `sound`
--
INSERT INTO sound (soundscape_component, sound_type)
VALUES ('biophony', 'fish chorus'),
       ('biophony', 'bat swarm'),
       ('biophony', 'insect broadband noise'),
       ('biophony', ''),
       ('anthropophony', 'test anthropophony'),
       ('anthropophony', 'human voices'),
       ('anthropophony', 'mining'),
       ('anthropophony', ''),
       ('geophony', 'wind'),
       ('geophony', 'wave'),
       ('geophony', 'earthquake'),
       ('geophony', 'rain'),
       ('geophony', ''),
       ('unknown', 'test unknown');

--
-- Dumping data for table `recorder`
--
INSERT INTO `recorder`
VALUES (1, 'µRUDAR-mk2', NULL, 'Cetacean Research Technology', '22');
INSERT INTO `recorder`
VALUES (2, 'AAD Moored Acoustic Recorder', NULL, NULL, '13');
INSERT INTO `recorder`
VALUES (3, 'Audiomoth 1.0.0', NULL, 'Open Acoustic Devices', '4');
INSERT INTO `recorder`
VALUES (4, 'AudioMoth 1.1.0', NULL, 'Open Acoustic Devices', '5');
INSERT INTO `recorder`
VALUES (5, 'Audiomoth 1.2.0', NULL, 'Open Acoustic Devices', '6');
INSERT INTO `recorder`
VALUES (6, 'BAR-LT', NULL, 'Frontier Labs', '12');
INSERT INTO `recorder`
VALUES (7, 'COLMEIA', NULL, 'Laboratoire Géosciences Océan', '13');
INSERT INTO `recorder`
VALUES (8, 'Curtin Underwater Sound Recorder', NULL, NULL, '15');
INSERT INTO `recorder`
VALUES (9, 'DR-05', NULL, 'Tascam', '7,17');
INSERT INTO `recorder`
VALUES (10, 'DR-07', NULL, 'Tascam', '8');
INSERT INTO `recorder`
VALUES (11, 'DR-44WL', NULL, 'Tascam', '1');
INSERT INTO `recorder`
VALUES (12, 'DS-850', NULL, 'Olympus', '2');
INSERT INTO `recorder`
VALUES (13, 'DSG-ST', NULL, 'Loggerhead', '15');
INSERT INTO `recorder`
VALUES (14, 'F6', NULL, 'Zoom', '3');
INSERT INTO `recorder`
VALUES (15, 'H4n', NULL, 'Zoom', '10');
INSERT INTO `recorder`
VALUES (16, 'H5', NULL, 'Zoom', '3');
INSERT INTO `recorder`
VALUES (17, 'HYDROMOMAR', NULL, 'Laboratoire Géosciences Océan', '13');
INSERT INTO `recorder`
VALUES (18, 'LG L70', NULL, 'LG', '18');
INSERT INTO `recorder`
VALUES (19, 'LS-P4', NULL, 'Olympus', '14');
INSERT INTO `recorder`
VALUES (20, 'Nomad Jukebox', NULL, 'Creative', '15');
INSERT INTO `recorder`
VALUES (21, 'PMD 661', NULL, 'Marantz Professional', '21');
INSERT INTO `recorder`
VALUES (22, 'PMEL AUH/Haruphone', NULL, NULL, '16');
INSERT INTO `recorder`
VALUES (23, 'Recoti recorder', NULL, NULL, '20');
INSERT INTO `recorder`
VALUES (24, 'SOLO recorder', NULL, 'Self-built', '9');
INSERT INTO `recorder`
VALUES (25, 'Song Meter Mini', NULL, 'Wildlife Acoustics', '33');
INSERT INTO `recorder`
VALUES (26, 'Song Meter SM1', NULL, 'Wildlife Acoustics', '23');
INSERT INTO `recorder`
VALUES (27, 'Song Meter SM2', NULL, 'Wildlife Acoustics', '15,30,31');
INSERT INTO `recorder`
VALUES (28, 'Song Meter SM2+', NULL, 'Wildlife Acoustics', '30,31');
INSERT INTO `recorder`
VALUES (29, 'Song Meter SM2Bat+', NULL, 'Wildlife Acoustics', '19,29,30,31');
INSERT INTO `recorder`
VALUES (30, 'Song Meter SM3', NULL, 'Wildlife Acoustics', '24,26');
INSERT INTO `recorder`
VALUES (31, 'Song Meter SM3 Bat', NULL, 'Wildlife Acoustics', '26');
INSERT INTO `recorder`
VALUES (32, 'Song Meter SM4', NULL, 'Wildlife Acoustics', '25');
INSERT INTO `recorder`
VALUES (33, 'Song Meter SM4 Bat FS', NULL, 'Wildlife Acoustics', '15,27,28,32');
INSERT INTO `recorder`
VALUES (34, 'Soundscape Explorer', NULL, 'Lunilettronik', '11');
INSERT INTO `recorder`
VALUES (35, 'SoundTrap ST300 HF', NULL, 'OceanInstruments ', '34');
INSERT INTO `recorder`
VALUES (36, 'SoundTrap ST300 STD', NULL, 'OceanInstruments ', '34');
INSERT INTO `recorder`
VALUES (37, 'SoundTrap ST600 STD', NULL, 'OceanInstruments ', '34');
INSERT INTO `recorder`
VALUES (38, 'SWIFT', NULL, 'The Cornell lab of Ornithology', '35');
INSERT INTO `recorder`
VALUES (39, 'SwiftOne', NULL, 'The Cornell lab of Ornithology', '35');

--
-- Dumping data for table `microphone`
--
INSERT INTO `microphone`
VALUES (1, 'AED-2010', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (2, 'AquaSound AQH-020D', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (3, 'Audio H2a', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (4, 'Audiomoth 1.0.0 built-in', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (5, 'Audiomoth 1.1.0 built-in', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (6, 'Audiomoth 1.2.0 built-in', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (7, 'DR-05', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (8, 'DR-07 MKII', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (9, 'EM172', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (10, 'EM-2800A', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (11, 'EMY-63M/P', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (12, 'FrontierLabs Standard Black Microphone', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (13, 'HTI-90-U', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (14, 'HTI-94-SSQ', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (15, 'HTI-96-MIN', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (16, 'ITC-1032', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (17, 'JRF', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (18, 'Model 600200', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (19, 'Pavo', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (20, 'Recoti Microphone', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (21, 'Sennheiser ME62', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (22, 'Sensor Technology SQ26-08', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (23, 'SM1 standard microphone', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (24, 'SM3 stub microphone (built-in)', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (25, 'SM4 stub microphone (built-in)', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (26, 'SMM-A1', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (27, 'SMM-U1', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (28, 'SMM-U2', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (29, 'SMO', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (30, 'SMX-II (after 2014)', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (31, 'SMX-II (before 2014)', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (32, 'SMX-U1', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (33, 'Song Meter Mini built-in', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (34, 'SoundTrap', NULL, NULL, NULL);
INSERT INTO `microphone`
VALUES (35, 'SWIFT', NULL, NULL, NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;