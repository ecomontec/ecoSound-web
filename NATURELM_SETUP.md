# NatureLM-audio Integration

## Overview

NatureLM-audio is a bioacoustic foundation model from the Earth Species Project, designed for understanding natural soundscapes. It's integrated into ecoSound-web as a local AI model alongside BirdNET and BatDetect2.

## Installation

### 1. Install Python Dependencies

```bash
pip install transformers torch torchaudio
```

### 2. Add Model to Database

Run the SQL migration to add NatureLM to the models table:

```bash
# If setting up fresh database
# The model is already included in data.sql

# If updating existing database
mysql -u your_user -p your_database < add_naturelm_model.sql
```

Or manually insert into your database:

```sql
INSERT INTO `models` (`tf_model_id`,`name`,`tf_model_path`,`labels_path`,`source_URL`,`description`,`parameter`)
VALUES (3, 'NatureLM-audio', '/bin/naturelm_inference.py', '', 'https://huggingface.co/EarthSpeciesProject/NatureLM-audio', 'Bioacoustic foundation model for audio understanding. Trained on diverse natural soundscapes.', 'threshold@Confidence threshold. Values in [0.0, 1.0]. Defaults to 0.1.$top_k@Number of top predictions. Values in [1, 20]. Defaults to 5.');
```

### 3. First Run (Model Download)

The first time you run NatureLM, it will automatically download the model from HuggingFace (~1-2GB). This happens automatically but requires internet connection.

## Usage

### From the Web Interface

1. Open a recording in the spectrogram player
2. Click the "AI models" link (robot icon) or access from the sidebar
3. Select "NatureLM-audio" from the dropdown
4. Configure parameters:
   - **threshold**: Minimum confidence threshold (0.0-1.0, default 0.1)
   - **top_k**: Number of top predictions to return (1-20, default 5)
5. Click "Submit"

The model will analyze the current spectrogram view (time/frequency range) and insert detected sounds as tags.

### Parameters

- **threshold** (default: 0.1): Minimum confidence score for a prediction to be included
  - Lower values: More detections, potentially more false positives
  - Higher values: Fewer, more confident detections

- **top_k** (default: 5): Maximum number of different sound classes to detect
  - Adjust based on how many different sounds you expect in your audio

### From Command Line

You can also run inference directly:

```bash
python3 /path/to/bin/naturelm_inference.py \
  path/to/audio.wav \
  --start-sec 10.0 \
  --duration-sec 30.0 \
  --threshold 0.15 \
  --top-k 10 \
  --output results.json
```

## How It Works

1. **Audio Processing**: The script extracts the specified time segment from your audio file
2. **Resampling**: Audio is resampled to 16kHz (NatureLM's expected rate)
3. **Inference**: The model analyzes the audio and predicts sound classes
4. **Tag Creation**: Detected sounds are inserted as tags in the database
   - If the species exists in your database, it's linked to the species
   - If not found, it's added as a comment with "uncertain" flag

## Model Details

- **Model**: EarthSpeciesProject/NatureLM-audio
- **Type**: Audio classification (transformer-based)
- **Input**: 16kHz mono audio
- **Max Duration**: 30 seconds (adjustable)
- **Training**: Diverse natural soundscapes and bioacoustic data
- **Output**: Sound class labels with confidence scores

## Differences from BirdNET/BatDetect2

| Feature | BirdNET | BatDetect2 | NatureLM |
|---------|---------|------------|----------|
| **Focus** | Birds | Bats | General bioacoustics |
| **Input Rate** | 48kHz | High freq | 16kHz |
| **Coverage** | Bird species | Bat species | Natural sounds |
| **Processing** | Whole file | Whole file | Time-windowed |
| **Metadata** | Location/date | None | None |

## Troubleshooting

### Model Download Issues

If the model fails to download:
1. Check internet connection
2. Check HuggingFace status: https://status.huggingface.co
3. Manually download: `python3 -c "from transformers import AutoModel; AutoModel.from_pretrained('EarthSpeciesProject/NatureLM-audio')"`

### Memory Issues

NatureLM requires ~2-4GB RAM for inference. If you encounter memory errors:
- Process shorter audio segments
- Close other applications
- Consider using a machine with more RAM

### No Detections

If NatureLM returns no detections:
- Lower the threshold parameter (try 0.05)
- Check audio quality (is it actually natural sound?)
- Verify audio isn't silent or corrupted
- Try a different time segment

### CUDA/GPU Issues

NatureLM will automatically use GPU if available, otherwise CPU:
- GPU: Faster (~2-5 seconds per inference)
- CPU: Slower (~10-30 seconds per inference)

Both work fine; GPU is just faster.

## Technical Notes

### Audio Segmentation

Unlike BirdNET which processes entire files, NatureLM analyzes the specific time/frequency window you've zoomed into in the spectrogram viewer. This allows for:
- Faster processing
- Focused analysis
- Better memory efficiency

### Species Matching

The model's output labels are matched against your species database. Unmatched species are still inserted as tags with comments, allowing you to:
- Review unknown species
- Add them to your database later
- Track what NatureLM is detecting

### Confidence Scores

Confidence scores indicate how certain the model is:
- **>0.5**: High confidence, marked as certain
- **<0.5**: Lower confidence, marked as uncertain
- All scores are shown in tag comments for reference

## Future Enhancements

Potential improvements:
- Batch processing multiple recordings
- Custom thresholds per sound class
- Integration with species database for better matching
- Fine-tuning on your specific soundscape
- Real-time processing mode

## Support

- Model documentation: https://huggingface.co/EarthSpeciesProject/NatureLM-audio
- Earth Species Project: https://www.earthspecies.org/
- Issues: Report via GitHub issues for ecoSound-web

## Citation

If you use NatureLM in your research, please cite:

```
Earth Species Project (2024). NatureLM-audio: A Foundation Model for Bioacoustic Understanding.
https://huggingface.co/EarthSpeciesProject/NatureLM-audio
```
