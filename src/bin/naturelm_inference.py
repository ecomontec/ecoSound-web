#!/usr/bin/env python3
"""
NatureLM-audio inference script for bioacoustic analysis.
Uses EarthSpeciesProject/NatureLM-audio model for audio understanding.
"""

import sys
import json
import argparse
import os
import configparser

def run_inference(audio_path, params=None):
    """
    Run NatureLM-audio inference on an audio file.
    
    Args:
        audio_path: Path to the audio file
        params: Dictionary of parameters (optional)
    
    Returns:
        Dictionary with success status and results
    """
    try:
        import torch
        import soundfile as sf
        from NatureLM.models import NatureLM
        from NatureLM.infer import Pipeline
    except ImportError as e:
        return {
            "success": False,
            "error": f"Failed to import required modules: {str(e)}. Install NatureLM packages: pip install git+https://github.com/earthspecies/beans-zero.git git+https://github.com/earthspecies/naturelm-audio.git@auth_tok soundfile"
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
        
        # Parameters
        start_sec = float(params.get('start_sec', 0.0))
        duration_sec = params.get('duration_sec')
        max_duration_sec = float(params.get('max_duration_sec', 30.0))  # NatureLM can handle longer clips
        top_k = int(params.get('top_k', 5))
        threshold = float(params.get('threshold', 0.1))
        
        model_name = "EarthSpeciesProject/NatureLM-audio"
        
        print(f"Loading NatureLM model from {model_name}...", file=sys.stderr)
        
        # Read HuggingFace token from config
        config_path = os.path.join(os.path.dirname(__file__), '..', 'config', 'config.ini')
        config = configparser.ConfigParser()
        config.read(config_path)
        hf_token = config.get('HuggingFace', 'HUGGINGFACE_API_TOKEN', fallback=None)

        # Allow surrounding quotes in config.ini values (users often put quoted strings there)
        if hf_token is not None:
            hf_token = hf_token.strip().strip('"\'')

        if not hf_token:
            return {
                "success": False,
                "error": "HuggingFace API token not configured. Please set HUGGINGFACE_API_TOKEN in config/config.ini. Token must have access to meta-llama/Meta-Llama-3.1-8B-Instruct."
            }
        
        # Authenticate with HuggingFace
        print(f"Authenticating with HuggingFace...", file=sys.stderr)
        try:
            from huggingface_hub import login
            login(token=hf_token, add_to_git_credential=False)
        except Exception as e:
            print(f"Warning: HuggingFace login failed: {e}", file=sys.stderr)
        
        # Load model using NatureLM API
        device = "cuda" if torch.cuda.is_available() else "cpu"
        
        try:
            # Load NatureLM model (multimodal: BEATs + QFormer + LLaMA 3.1-8B)
            print(f"Loading NatureLM on {device}...", file=sys.stderr)
            model = NatureLM.from_pretrained(
                model_name,
                device=device
            )
            print(f"Model loaded successfully on {device}", file=sys.stderr)
            
        except Exception as e:
            import traceback as tb
            return {
                "success": False,
                "error": f"Failed to load NatureLM model: {str(e)}",
                "traceback": tb.format_exc()
            }
        
        # Load audio
        print(f"Loading audio from {audio_path}...", file=sys.stderr)
        audio_array, sample_rate = sf.read(audio_path)
        
        # Get audio duration
        audio_duration = len(audio_array) / sample_rate
        
        # Calculate actual segment
        if duration_sec is None:
            duration_sec = min(max_duration_sec, audio_duration - start_sec)
        
        # Extract segment
        start_frame = int(start_sec * sample_rate)
        end_frame = int((start_sec + duration_sec) * sample_rate)
        segment = audio_array[start_frame:end_frame]
        
        # Convert stereo to mono if needed
        if len(segment.shape) > 1 and segment.shape[1] > 1:
            segment = segment.mean(axis=1)
        
        print(f"Processing {duration_sec:.2f}s audio segment (sample_rate={sample_rate})...", file=sys.stderr)
        
        # Create inference pipeline
        # NatureLM can do classification, detection, and captioning
        # For bioacoustics, we'll use classification mode
        print("Creating NatureLM inference pipeline...", file=sys.stderr)
        
        # Use local minimal config file (inference.yml in same directory as this script)
        config_yml_path = os.path.join(os.path.dirname(__file__), 'inference.yml')
        pipeline = Pipeline(model=model, cfg_path=config_yml_path)
        
        # Create a classification query
        # NatureLM uses natural language queries to guide inference
        query = params.get('query', "Identify the animal or natural sounds in this audio recording.")
        
        print(f"Running NatureLM inference with query: '{query}'", file=sys.stderr)
        
        # Run inference
        # Pipeline expects a list of audio arrays and a query
        results = pipeline(
            audios=[segment],
            queries=[query],
            input_sample_rate=sample_rate
        )
        
        print(f"Inference complete. Results: {results}", file=sys.stderr)
        
        # Format results for ecoSound-web
        # NatureLM returns text descriptions/labels
        predictions = []
        
        # Helper function to parse NatureLM output format
        def parse_naturelm_output(text):
            """
            Parse NatureLM output format: '#0.00s - 10.00s#: Species Name'
            Returns: (start_time, end_time, label) or (None, None, text) if no timestamp found
            Note: NatureLM predictions span the full frequency range (no frequency localization)
            """
            import re
            # Clean up text first
            text = text.strip()
            
            # Find ALL timestamp patterns in the text
            # Pattern: #START.XXs - END.XXs#: Label
            pattern = r'#(\d+\.\d+)s\s*-\s*(\d+\.\d+)s#:\s*([^\n#]+)'
            matches = re.findall(pattern, text)
            
            if matches:
                # Return list of (start, end, label) tuples for all matches
                results = []
                for match in matches:
                    start = float(match[0])
                    end = float(match[1])
                    label = match[2].strip()
                    results.append((start, end, label))
                return results
            
            # If no timestamp patterns found, check for text without timestamps
            # This handles output like "Song Thrush" on first line before timestamps
            lines = text.strip().split('\n')
            if lines:
                first_line = lines[0].strip()
                # Only use first line if it doesn't contain timestamp pattern
                if not re.search(r'#\d+\.\d+s\s*-\s*\d+\.\d+s#:', first_line):
                    return [(None, None, first_line)]
            
            # Fall back to returning the whole text
            return [(None, None, text)]
        
        if results and len(results) > 0:
            result = results[0]  # Get first result (we only passed one audio)
            
            # NatureLM can return different formats depending on the task
            # Parse the result and create predictions
            if isinstance(result, str):
                # Parse ALL timestamps and labels from NatureLM output (now returns a list)
                parsed_list = parse_naturelm_output(result)
                
                # Iterate through all parsed segments
                for pred_start, pred_end, label in parsed_list:
                    # If NatureLM provided timestamps, use them; otherwise use input segment times
                    if pred_start is not None and pred_end is not None:
                        # NatureLM timestamps are relative to the audio segment we passed
                        # Add the segment's start time to get absolute times in the recording
                        predictions.append({
                            "label": label,
                            "confidence": None,
                            "start_time": start_sec + pred_start,
                            "end_time": start_sec + pred_end,
                            "type": "classification"
                        })
                    else:
                        # No timestamp in output, use segment boundaries
                        predictions.append({
                            "label": label,
                            "confidence": None,
                            "start_time": start_sec,
                            "end_time": start_sec + duration_sec,
                            "type": "classification"
                        })
            elif isinstance(result, dict):
                # Structured response
                label = result.get('label', result.get('text', str(result)))
                confidence = result.get('confidence', result.get('score', 1.0))
                parsed_list = parse_naturelm_output(str(label))
                
                # Iterate through all parsed segments
                for pred_start, pred_end, clean_label in parsed_list:
                    if pred_start is not None and pred_end is not None:
                        predictions.append({
                            "label": clean_label,
                            "confidence": float(confidence),
                            "start_time": start_sec + pred_start,
                            "end_time": start_sec + pred_end,
                            "type": "classification"
                        })
                    else:
                        predictions.append({
                            "label": clean_label,
                            "confidence": float(confidence),
                            "start_time": start_sec,
                            "end_time": start_sec + duration_sec,
                            "type": "classification"
                        })
            elif isinstance(result, list):
                # Multiple predictions
                for item in result[:top_k]:
                    if isinstance(item, str):
                        parsed_list = parse_naturelm_output(item)
                        
                        for pred_start, pred_end, clean_label in parsed_list:
                            if pred_start is not None and pred_end is not None:
                                predictions.append({
                                    "label": clean_label,
                                    "confidence": None,
                                    "start_time": start_sec + pred_start,
                                    "end_time": start_sec + pred_end,
                                    "type": "classification"
                                })
                            else:
                                predictions.append({
                                    "label": clean_label,
                                    "confidence": None,
                                    "start_time": start_sec,
                                    "end_time": start_sec + duration_sec,
                                    "type": "classification"
                                })
                    elif isinstance(item, dict):
                        label = item.get('label', item.get('text', str(item)))
                        confidence = item.get('confidence', item.get('score', 1.0))
                        parsed_list = parse_naturelm_output(str(label))
                        
                        for pred_start, pred_end, clean_label in parsed_list:
                            if confidence >= threshold:
                                if pred_start is not None and pred_end is not None:
                                    predictions.append({
                                        "label": clean_label,
                                        "confidence": float(confidence),
                                        "start_time": start_sec + pred_start,
                                        "end_time": start_sec + pred_end,
                                        "type": "classification"
                                    })
                                else:
                                    predictions.append({
                                        "label": clean_label,
                                    "confidence": float(confidence),
                                    "start_time": start_sec,
                                    "end_time": start_sec + duration_sec,
                                    "type": "classification"
                                })
        
        return {
            "success": True,
            "predictions": predictions,
            "model": model_name,
            "audio_duration": float(audio_duration),
            "segment_analyzed": {
                "start": start_sec,
                "end": start_sec + duration_sec,
                "duration": duration_sec
            },
            "device": device
        }
        
    except Exception as e:
        import traceback
        return {
            "success": False,
            "error": str(e),
            "traceback": traceback.format_exc()
        }


def main():
    parser = argparse.ArgumentParser(description='Run NatureLM-audio inference')
    parser.add_argument('audio_path', help='Path to audio file')
    parser.add_argument('--start-sec', type=float, default=0.0, help='Start time in seconds')
    parser.add_argument('--duration-sec', type=float, help='Duration in seconds')
    parser.add_argument('--max-duration-sec', type=float, default=30.0, help='Maximum duration')
    parser.add_argument('--top-k', type=int, default=5, help='Number of top predictions')
    parser.add_argument('--threshold', type=float, default=0.1, help='Confidence threshold')
    parser.add_argument('--query', type=str, help='Custom query/prompt for the model')
    parser.add_argument('--output', help='Output JSON file (default: stdout)')
    
    args = parser.parse_args()
    
    params = {
        'start_sec': args.start_sec,
        'duration_sec': args.duration_sec,
        'max_duration_sec': args.max_duration_sec,
        'top_k': args.top_k,
        'threshold': args.threshold
    }
    
    # Only add query if explicitly provided
    if args.query is not None:
        params['query'] = args.query
    
    result = run_inference(args.audio_path, params)
    
    json_output = json.dumps(result, indent=2)
    
    if args.output:
        with open(args.output, 'w') as f:
            f.write(json_output)
        print(f"Results written to {args.output}", file=sys.stderr)
    else:
        print(json_output)
    
    return 0 if result.get('success') else 1


if __name__ == '__main__':
    sys.exit(main())
