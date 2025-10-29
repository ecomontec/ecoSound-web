# HuggingFace Local Inference - Requirements

## Python Dependencies

The inference system requires the following Python packages:

```bash
pip install torch transformers librosa soundfile
```

Or use the provided script which auto-installs dependencies:
```bash
python3 src/bin/hf_inference.py
```

## System Requirements

- Python 3.7 or higher
- At least 2GB RAM (4GB recommended for larger models)
- Internet connection for first-time model downloads

## Model Cache

Models are cached by HuggingFace Transformers in:
- Linux/Mac: `~/.cache/huggingface/hub/`
- Windows: `%USERPROFILE%\.cache\huggingface\hub\`

First-time model execution will download the model. Subsequent runs use the cached version.

## Testing

Test the inference script directly:

```bash
# Basic test with a sample model
python3 src/bin/hf_inference.py \
  "facebook/wav2vec2-base-960h" \
  "/path/to/audio.wav" \
  '{"confidence": 0.5}'
```

## Supported Models

The system supports HuggingFace models with the following pipeline tasks:
- `audio-classification` (recommended for ecoSound)
- `automatic-speech-recognition`
- `audio-to-audio`

Check model compatibility at: https://huggingface.co/models?pipeline_tag=audio-classification

## Performance Notes

- First run per model: Downloads model (~300MB-1GB depending on model size)
- Subsequent runs: Uses cached model (fast)
- CPU inference: ~1-5 seconds per audio file
- GPU inference: ~0.1-1 seconds per audio file (if CUDA available)
