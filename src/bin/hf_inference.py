#!/usr/bin/env python3
"""
HuggingFace model inference script for audio classification.
Downloads models from HuggingFace Hub and runs inference on audio files.
"""

# CRITICAL: Block torchvision BEFORE any other imports
# This must happen before torch/transformers are imported anywhere
import sys
import types
import importlib.util
from enum import IntEnum

# Install a dummy torchvision module to prevent circular import errors
# when transformers tries to import torchvision (which has broken ops in this container)
dummy_tv = types.ModuleType("torchvision")
dummy_tv.__version__ = "0.0.0"
dummy_tv.__spec__ = importlib.util.spec_from_loader("torchvision", loader=None)
dummy_tv.__path__ = []  # Make it a package
dummy_tv.extension = types.ModuleType("torchvision.extension")
dummy_tv.ops = types.ModuleType("torchvision.ops")
dummy_tv.transforms = types.ModuleType("torchvision.transforms")
dummy_tv.models = types.ModuleType("torchvision.models")
dummy_tv.io = types.ModuleType("torchvision.io")
dummy_tv.datasets = types.ModuleType("torchvision.datasets")
dummy_tv._internally_replaced_module = True

# Add InterpolationMode enum that some transformers code checks for
class InterpolationMode(IntEnum):
    NEAREST = 0
    NEAREST_EXACT = 0
    BILINEAR = 2
    BICUBIC = 3
    BOX = 4
    HAMMING = 5
    LANCZOS = 1

dummy_tv.transforms.InterpolationMode = InterpolationMode
dummy_tv.transforms.functional = types.ModuleType("torchvision.transforms.functional")
dummy_tv.transforms.v2 = types.ModuleType("torchvision.transforms.v2")
dummy_tv.transforms.v2.functional = types.ModuleType("torchvision.transforms.v2.functional")
dummy_tv.transforms.__path__ = []  # Make transforms a package
dummy_tv.transforms.v2.__path__ = []  # Make v2 a package

sys.modules["torchvision"] = dummy_tv
sys.modules["torchvision.extension"] = dummy_tv.extension
sys.modules["torchvision.ops"] = dummy_tv.ops
sys.modules["torchvision.transforms"] = dummy_tv.transforms
sys.modules["torchvision.transforms.functional"] = dummy_tv.transforms.functional
sys.modules["torchvision.transforms.v2"] = dummy_tv.transforms.v2
sys.modules["torchvision.transforms.v2.functional"] = dummy_tv.transforms.v2.functional
sys.modules["torchvision.models"] = dummy_tv.models
sys.modules["torchvision.io"] = dummy_tv.io
sys.modules["torchvision.datasets"] = dummy_tv.datasets

# Now proceed with normal imports
import json
import os
from pathlib import Path
import warnings
warnings.filterwarnings('ignore')

# Ensure Transformers uses PyTorch only and stays quiet
os.environ.setdefault("TRANSFORMERS_NO_TF", "1")
os.environ.setdefault("TRANSFORMERS_NO_JAX", "1")
os.environ.setdefault("TOKENIZERS_PARALLELISM", "false")
os.environ.setdefault("HF_HUB_DISABLE_PROGRESS_BARS", "1")
os.environ.setdefault("TRANSFORMERS_NO_TORCHVISION", "1")

def install_requirements():
    """Install required packages if not present."""
    try:
        import torch
        import transformers
        import librosa
        import soundfile
    except ImportError as e:
        print(f"Installing required packages (missing: {e})...", file=sys.stderr)
        import subprocess
        try:
            subprocess.check_call([sys.executable, "-m", "pip", "install", "--upgrade",
                                  "torch", "transformers", "librosa", "soundfile"])
        except subprocess.CalledProcessError as install_err:
            return {
                "success": False,
                "error": f"Failed to install dependencies: {install_err}"
            }

def run_inference(model_id, audio_path, params=None):
    """
    Run inference on an audio file using a HuggingFace model.
    
    Args:
        model_id: HuggingFace model ID (e.g., "facebook/wav2vec2-base-960h")
        audio_path: Path to the audio file
        params: Dictionary of model parameters (confidence threshold, etc.)
    
    Returns:
        Dictionary with inference results
    """
    try:
        import transformers
        import librosa
    except ImportError as e:
        return {
            "success": False,
            "error": f"Failed to import required modules: {str(e)}. Ensure transformers, librosa, and torch are installed."
        }
    
    try:
        if params is None:
            params = {}
        
        # Check if audio file exists
        if not os.path.exists(audio_path):
            return {
                "success": False,
                "error": f"Audio file not found: {audio_path}"
            }
        
        # Determine segment to analyze (avoid loading entire long files)
        try:
            total_duration = librosa.get_duration(filename=audio_path)
        except Exception:
            total_duration = None

        # Time window parameters
        start_sec = float(params.get('start_sec', 0.0)) if params else 0.0
        end_sec = params.get('end_sec') if params else None
        duration_sec = params.get('duration_sec') if params else None
        max_duration_sec = float(params.get('max_duration_sec', 15.0)) if params else 15.0

        # Compute effective duration
        if duration_sec is None and end_sec is not None:
            try:
                duration_sec = float(end_sec) - float(start_sec)
            except Exception:
                duration_sec = None

        if duration_sec is None:
            # If not specified, bound by max_duration_sec (default 15s)
            if total_duration is not None:
                remaining = max(0.0, float(total_duration) - float(start_sec))
                duration_sec = min(float(max_duration_sec), remaining) if max_duration_sec else remaining
            else:
                duration_sec = float(max_duration_sec) if max_duration_sec else None

        # Sanity clamp
        if total_duration is not None:
            start_sec = max(0.0, min(float(start_sec), float(total_duration)))
            if duration_sec is not None:
                duration_sec = max(0.0, min(float(duration_sec), float(total_duration) - start_sec))

        # Load audio segment
        try:
            audio, sample_rate = librosa.load(audio_path, sr=16000, offset=start_sec, duration=duration_sec)
        except Exception as e:
            return {
                "success": False,
                "error": f"Failed to load audio: {str(e)}"
            }
        
        # Try high-level transformers pipeline first; if it triggers torchvision import issues,
        # fall back to manual model+processor inference that avoids image utilities entirely.
        try:
            from transformers.pipelines import pipeline as hf_pipeline
            # Determine pipeline task from model config or default to audio-classification
            task = params.get('task', 'audio-classification')
            classifier = hf_pipeline(
                task=task,
                model=model_id,
                device=-1,
                framework="pt"
            )
            top_k = params.get('top_k', None) if params else None
            if top_k is not None:
                predictions = classifier(audio, sampling_rate=sample_rate, top_k=int(top_k))
            else:
                predictions = classifier(audio, sampling_rate=sample_rate)
        except Exception as pipeline_err:
            # Manual inference path (no torchvision dependencies)
            try:
                import torch
                import torch.nn.functional as F
                from transformers import AutoFeatureExtractor, AutoConfig

                # Load feature extractor for audio
                try:
                    processor = AutoFeatureExtractor.from_pretrained(model_id)
                except Exception as e:
                    return {
                        "success": False,
                        "error": f"Failed to load feature extractor: {str(e)}; pipeline error was: {str(pipeline_err)}"
                    }

                # Prepare inputs
                if hasattr(processor, "__call__"):
                    inputs = processor(audio, sampling_rate=sample_rate, return_tensors="pt")
                else:
                    return {
                        "success": False,
                        "error": "Loaded processor does not support call for audio inputs"
                    }

                # Load config to determine model architecture
                try:
                    config = AutoConfig.from_pretrained(model_id)
                    model_type = config.model_type if hasattr(config, 'model_type') else None
                except Exception:
                    model_type = None

                # Load model - try multiple strategies
                model = None
                model_error = None
                
                # Strategy 1: Try standard Auto classes with use_safetensors to avoid torch.load warning
                for auto_class_name in ['AutoModelForAudioClassification', 'AutoModelForSequenceClassification']:
                    try:
                        auto_class = getattr(__import__('transformers', fromlist=[auto_class_name]), auto_class_name)
                        model = auto_class.from_pretrained(model_id, use_safetensors=True)
                        break
                    except Exception as e:
                        model_error = str(e)
                        continue
                
                # Strategy 2: If Auto classes failed, try loading specific model class based on model_type
                if model is None and model_type:
                    specific_classes = []
                    if model_type == 'hubert':
                        specific_classes = ['HubertForSequenceClassification', 'HubertModel']
                    elif model_type == 'wav2vec2':
                        specific_classes = ['Wav2Vec2ForSequenceClassification', 'Wav2Vec2ForAudioFrameClassification', 'Wav2Vec2Model']
                    elif model_type == 'wavlm':
                        specific_classes = ['WavLMForSequenceClassification', 'WavLMModel']
                    elif model_type == 'audio-spectrogram-transformer':
                        specific_classes = ['ASTForAudioClassification', 'ASTModel']
                    
                    for class_name in specific_classes:
                        try:
                            model_class = getattr(__import__('transformers', fromlist=[class_name]), class_name)
                            model = model_class.from_pretrained(model_id, use_safetensors=True)
                            break
                        except Exception as e:
                            model_error = str(e)
                            continue
                
                if model is None:
                    return {
                        "success": False,
                        "error": f"Failed to load model (type: {model_type}): {model_error}; pipeline error was: {str(pipeline_err)}"
                    }
                
                model.eval()
                with torch.no_grad():
                    outputs = model(**inputs)
                    logits = outputs.logits
                    probs = F.softmax(logits, dim=-1)[0]

                id2label = getattr(model.config, 'id2label', None) or {i: str(i) for i in range(probs.shape[-1])}

                # Determine top_k - clamp to number of available classes
                num_classes = probs.shape[-1]
                requested_top_k = int(params.get('top_k', 5)) if params else 5
                top_k = min(requested_top_k, num_classes)
                topk = torch.topk(probs, k=top_k)
                predictions = [
                    {"label": id2label.get(int(idx), str(int(idx))), "score": float(score)}
                    for score, idx in zip(topk.values.tolist(), topk.indices.tolist())
                ]
            except Exception as manual_err:
                return {
                    "success": False,
                    "error": f"Failed manual inference: {str(manual_err)}; pipeline error was: {str(pipeline_err)}"
                }

        # Filter by confidence threshold if provided
        confidence_threshold = params.get('confidence', 0.0)
        if isinstance(predictions, list):
            filtered_predictions = [
                p for p in predictions 
                if p.get('score', 0) >= confidence_threshold
            ]
        else:
            filtered_predictions = predictions

        result = {
            "success": True,
            "predictions": filtered_predictions,
            "model_id": model_id,
            "audio_file": audio_path,
            "sample_rate": sample_rate,
            "audio_duration": len(audio) / sample_rate
        }
        # Add segment metadata
        if total_duration is not None:
            result["total_duration"] = float(total_duration)
        result["segment_start_sec"] = float(start_sec)
        result["segment_duration_sec"] = float(len(audio) / sample_rate)
        return result
    
    except Exception as e:
        return {
            "success": False,
            "error": f"Unexpected error: {str(e)}"
        }

def main():
    """Main entry point for the script."""
    if len(sys.argv) < 3:
        print(json.dumps({
            "success": False,
            "error": "Usage: hf_inference.py <model_id> <audio_path> [params_json]"
        }))
        sys.exit(1)
    
    model_id = sys.argv[1]
    audio_path = sys.argv[2]
    
    # Parse optional parameters
    params = {}
    if len(sys.argv) > 3:
        try:
            params = json.loads(sys.argv[3])
        except json.JSONDecodeError:
            print(json.dumps({
                "success": False,
                "error": "Invalid JSON parameters"
            }))
            sys.exit(1)
    
    # Run inference
    result = run_inference(model_id, audio_path, params)
    
    # Output result as JSON
    print(json.dumps(result, indent=2))
    
    # Exit with appropriate code
    sys.exit(0 if result.get("success") else 1)

if __name__ == "__main__":
    # Try to install requirements if needed
    try:
        install_requirements()
    except Exception as e:
        print(json.dumps({
            "success": False,
            "error": f"Failed to install dependencies: {str(e)}"
        }))
        sys.exit(1)
    
    main()
