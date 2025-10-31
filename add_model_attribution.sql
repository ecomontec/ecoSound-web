-- Add attribution column to models table
ALTER TABLE `models` ADD COLUMN `attribution` TEXT AFTER `description`;

-- Update existing models with attribution (with HTML links)
UPDATE `models` SET `attribution` = '' WHERE `tf_model_id` = 1; -- BirdNET has no special attribution needs
UPDATE `models` SET `attribution` = '' WHERE `tf_model_id` = 2; -- batdetect2 has no special attribution needs  
UPDATE `models` SET `attribution` = 'Powered by <a href="https://github.com/microsoft/unilm/tree/master/beats" target="_blank">BEATs</a> and <a href="https://huggingface.co/meta-llama/Meta-Llama-3.1-8B-Instruct" target="_blank">Llama 3.1</a>' WHERE `tf_model_id` = 4;
