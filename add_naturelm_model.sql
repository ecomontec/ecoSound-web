-- Add NatureLM-audio model to the models table
-- Run this after init.sql and before data.sql, or run it manually

INSERT INTO `models` (`tf_model_id`,`name`,`tf_model_path`,`labels_path`,`source_URL`,`description`,`parameter`)
VALUES (3, 'NatureLM-audio', '/bin/naturelm_inference.py', '', 'https://huggingface.co/EarthSpeciesProject/NatureLM-audio', 'Bioacoustic foundation model for audio understanding. Trained on diverse natural soundscapes.', 'threshold@Confidence threshold. Values in [0.0, 1.0]. Defaults to 0.1.$top_k@Number of top predictions. Values in [1, 20]. Defaults to 5.');
