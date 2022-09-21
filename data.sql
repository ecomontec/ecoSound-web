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
VALUES ('1','acoustic_complexity_index','','Compute the Acoustic Complexity Index (ACI) from a spectrogram.','https://scikit-maad.github.io/generated/maad.features.acoustic_complexity_index.html')
    ('2','soundscape_index','flim_bioPh|1000,10000!flim_antroPh|0,1000!R_compatible|soundecology','Compute the Normalized Difference Soundscape Index from a power spectrogram.','https://scikit-maad.github.io/generated/maad.features.soundscape_index.html')
('3','temporal_median','mode|fast!Nt|512','Computes the median of the envelope of an audio signal.','https://scikit-maad.github.io/generated/maad.features.temporal_median.html')
('4','temporal_entropy','mode|fast!Nt|512','Computes the entropy of the envelope of an audio signal.','https://scikit-maad.github.io/generated/maad.features.temporal_entropy.html')
('5','temporal_activity','dB_threshold|3!mode|fast!Nt|512','Compute the acoustic activity index in temporal domain.','https://scikit-maad.github.io/generated/maad.features.temporal_activity.html')
('6','temporal_events','dB_threshold|3!rejectDuration|None!mode|fast!Nt|512!display|False','Compute the acoustic event index from an audio signal','https://scikit-maad.github.io/generated/maad.features.temporal_events.html')
('7','frequency_entropy','compatibility|QUT','Computes the spectral entropy of a power spectral density (1d) or power spectrogram density (2d).','https://scikit-maad.github.io/generated/maad.features.frequency_entropy.html')
('8','number_of_peaks','mode|dB!min_peak_val|None!min_freq_dist|200!slopes|1,1!prominence|None!display|False','Count the number of frequency peaks on a mean spectrum.','https://scikit-maad.github.io/generated/maad.features.number_of_peaks.html')
('9','spectral_entropy','flim|None!display|False','Compute different entropies based on the average spectrum, its variance, and its maxima','https://scikit-maad.github.io/generated/maad.features.spectral_entropy.html')
('10','spectral_activity','dB_threshold|6','Compute the acoustic activity on a spectrogram.','https://scikit-maad.github.io/generated/maad.features.spectral_activity.html')
('12','spectral_cover','flim_LF|0,1000!flim_MF|1000,10000!flim_HF|10000,20000','Compute the proportion (cover) of the spectrogram above a threshold for three bandwidths : low frequency band (LF), medium frequency band (MF) and high frequency band (HF)','https://scikit-maad.github.io/generated/maad.features.spectral_cover.html')
('13','bioacoustics_index','flim|2000,15000!R_compatible|soundecology','Compute the Bioacoustics Index from a spectrogram','https://scikit-maad.github.io/generated/maad.features.bioacoustics_index.html')
('14','acoustic_diversity_index','fmin|0!fmax|20000!bin_step|500!dB_threshold|-50!index|shannon','Compute the Acoustic Diversity Index (ADI) from a spectrogram','https://scikit-maad.github.io/generated/maad.features.acoustic_diversity_index.html')
('15','acoustic_eveness_index','fmin|0!fmax|20000!bin_step|500!dB_threshold|-50','Compute the Acoustic Eveness Index (AEI) from a spectrogram','https://scikit-maad.github.io/generated/maad.features.acoustic_eveness_index.html')
('17','temporal_leq','gain|42!Vadc|2!sensitivity|-35!dBref|94!dt|1','Computes the Equivalent Continuous Sound level (Leq) of an audio signal in the time domain.','https://scikit-maad.github.io/generated/maad.features.temporal_leq.html')
('18','spectral_leq','gain|42!Vadc|2!sensitivity|-35!dBref|94!pRef|20e-6','Computes the Equivalent Continuous Sound level (Leq) from a power spectrum (1d) or power spectrogram (2d).','https://scikit-maad.github.io/generated/maad.features.spectral_leq.html')
('20','tfsd','flim|2000,8000!mode|thirdOctave!display|False','Compute the Time frequency derivation index (tfsd) from a spectrogram.','https://scikit-maad.github.io/generated/maad.features.tfsd.html')
('21','more_entropy_time','order|3!axis|0','Compute the entropy of an audio signal using multiple methods.','https://scikit-maad.github.io/generated/maad.features.more_entropy.html')
('22','acoustic_gradient_index','norm|per_bin','Compute the Acoustic Gradient Index (AGI) from a raw spectrogram.','https://scikit-maad.github.io/generated/maad.features.acoustic_gradient_index.html')
('23','frequency_raoq','bin_step|1000','Compute Rao`s quadratic entropy on a power spectrum (1d).','https://scikit-maad.github.io/generated/maad.features.frequency_raoq.html')
('24','region_of_interest_index','smooth_param1|1!mask_mode|relative!mask_param1|6!mask_param2|0.5!min_roi|None!max_roi|None!remove_rain|False!display|False','Compute an acoustic activity index based on the regions of interested detected on a spectrogram.','https://scikit-maad.github.io/generated/maad.features.region_of_interest_index.html')
('25','more_entropy_spectral','order|3!axis|0','Compute the entropy of an audio signal using multiple methods.','https://scikit-maad.github.io/generated/maad.features.more_entropy.html')

--
-- Dumping data for table `collection`
--
INSERT INTO `collection` (`collection_id`, `project_id`, `name`, `user_id`, `doi`, `note`, `view`, `public`)
VALUES (1, 101, 'Demo collection', 100, '', 'open access', 'gallery', '1');

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
-- Dumping data for table `sensor`
--
INSERT INTO `sensor` (`sensor_id`, `name`, `microphone`, `recorder`, `note`)
VALUES (1, 'None', '', NULL, ''),
       (2, 'pre-2014 SMX-II (Wildlife acoustics)', 'WM61A (Panasonic)', 'SM2+', 'info according to WA, but several types were found'),
       (3, 'SMX-U1 (Wildlife acoustics)', 'FG-23629-C36-1 (Knowles)', 'SM2Bat+', ''),
       (4, 'SMX-US (Wildlife acoustics)', 'SPM0404UD5 (Knowles)', 'SM2Bat+', ''),
       (5, 'BMX-U1 (Biotope.fr)', 'SPU0410LR5H-QB (Knowles)', 'SM2Bat+', ''),
       (6, 'SMO1 (Sonitor Parus precursor)', 'SPU0410LR5H-QB (Knowles)', 'SM2Bat+', ''),
       (7, 'Primo EM172', 'Primo EM172', 'Solo', ''),
       (8, 'mixed', '', NULL, ''),
       (9, 'Audiomoth 1.0, 1.1', 'SPM0408LE5H-TB (<6) (Knowles)', 'Audiomoth 1.0, 1.1', ''),
       (10, 'Sennheiser ME66', '', NULL, ''),
       (11, 'RØDE VideoMic', '', NULL, ''),
       (12, 'Olympus LS-P4', '', 'Olympus LS-P4', ''),
       (13, 'Sony Ericsson K600i', '', 'Sony Ericsson K600i', ''),
       (14, 'Roland R05', '', 'Roland R05', ''),
       (15, 'SM4 (internal acoustic)', '', 'SM4 (Wildlife Acoustics)', ''),
       (16, 'post-2014 SMX-II (Wildlife acoustics)', 'unknown but similar to PUI mic', 'SM2+', ''),
       (17, 'Audiomoth 1.2', 'SPM0408LE5H-TB (>5) (Knowles)', 'Audiomoth 1.2', ''),
       (18, 'Petterson D240X', 'unspecified', 'Petterson D240X', '');


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
       ('wav_toflac', '1');

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
INSERT INTO `species` (`species_id`, `binomial`, `genus`, `family`, `taxon_order`, `class`, `common_name`, `level`, `region`)
VALUES (1, 'Test species', 'Test Genus', 'Test Family', 'Test Order', 'Test Class', 'Test common name', 1, 'Test region');

--
-- Dumping data for table `tag_review_status`
--
INSERT INTO `tag_review_status` (`tag_review_status_id`, `name`)
VALUES (1, 'Accepted'),
       (2, 'Corrected'),
       (3, 'Deleted'),
       (4, 'Uncertain');

--
-- Dumping data for table `role`
--
INSERT INTO `role` (`role_id`, `name`)
VALUES (1, 'Administrator'),
       (2, 'User');

--
-- Dumping data for table `user`
--
INSERT INTO `user` (`user_id`, `role_id`, `project_id`, `username`, `password`, `name`, `email`, `color`, `active`)
VALUES (100, 1, 101, 'admin', 'JDJ5JDEwJHguRG9TQmZ5dmtiRTRPUEkxRlRKR3VRMTFXUmVNZWVDZkRDcy5QTDRSdENiMWpMNVF6TlMu', 'Administrator', 'admin@biosounds.admin', '#bd2929', 1);

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
       (3, 'Access');
--
-- Dumping data for table `explore`
--
INSERT INTO `explore` (`explore_id`, `pid`, `name`, `level`)
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
INSERT INTO `site` (`site_id`, `name`, `user_id`, `creation_date_time`, `longitude_WGS84_dd_dddd`, `latitude_WGS84_dd_dddd`, `GADM1`, `GADM2`, `GADM3`, `realm_id`, `biome_id`, `functional_group_id`, `centroid`)
VALUES (1, 'Demo site', 100, now(), 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'false');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
