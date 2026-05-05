import optparse
import maad
from maad import sound, util
from maad.rois import template_matching
from maad.features import shape_features
import numpy
import pandas as pd
from pathlib import Path
import configparser
import sys
import soundfile as sf


def getMaad(filename, index_type, param, channel, minTime, maxTime, minFrequency, maxFrequency):
    parameter = {}
    s, fs = maad.sound.load(filename + '.wav', channel=channel)
    if index_type == "acoustic_complexity_index":
        # zoom
        Sxx, tn, fn, ext = maad.sound.spectrogram(s, fs, mode='amplitude')
        # index
        _, _, ACI_sum = maad.features.acoustic_complexity_index(Sxx)
        # print
        print("ACI_sum?" + str(ACI_sum))
    elif index_type == "soundscape_index":
        # param
        parameter['flim_bioPh'] = '1000,10000'
        parameter['flim_antroPh'] = '0,1000'
        parameter['R_compatible'] = 'soundecology'
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        flim_bioPh = (float(parameter['flim_bioPh'].split(',')[0]), float(parameter['flim_bioPh'].split(',')[1]))
        flim_antroPh = (float(parameter['flim_antroPh'].split(',')[0]), float(parameter['flim_antroPh'].split(',')[1]))
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        NDSI, ratioBA, antroPh, bioPh = maad.features.soundscape_index(Sxx_power, fn, flim_bioPh=flim_bioPh, flim_antroPh=flim_antroPh, R_compatible=parameter['R_compatible'])
        # print
        print("NDSI?" + str(NDSI) + "!ratioBA?" + str(ratioBA) + "!antroPh?" + str(antroPh) + "!bioPh?" + str(bioPh))
    elif index_type == "temporal_median":
        # param
        parameter['mode'] = 'fast'
        parameter['Nt'] = '512'
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        mode = parameter['mode']
        Nt = int(parameter['Nt'])
        # index
        med = maad.features.temporal_median(s, mode=mode, Nt=Nt)
        # print
        print("med?" + str(med))
    elif index_type == "temporal_entropy":
        # param
        parameter['mode'] = 'fast'
        parameter['Nt'] = '512'
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        mode = parameter['mode']
        Nt = int(parameter['Nt'])
        # index
        Ht = maad.features.temporal_entropy(s, mode=mode, Nt=Nt)
        # print
        print("Ht?" + str(Ht))
    elif index_type == "temporal_activity":
        # param
        parameter['dB_threshold'] = '3'
        parameter['mode'] = 'fast'
        parameter['Nt'] = '512'
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        dB_threshold = float(parameter['dB_threshold'])
        mode = parameter['mode']
        Nt = int(parameter['Nt'])
        # index
        ACTfract, ACTcount, ACTmean = maad.features.temporal_activity(s, dB_threshold=dB_threshold, mode=mode, Nt=Nt)
        # print
        print("ACTfract?" + str(ACTfract) + "!ACTcount?" + str(ACTcount) + "!ACTmean?" + str(ACTmean))
    elif index_type == "temporal_events":
        # param
        parameter['dB_threshold'] = '3'
        parameter['rejectDuration'] = None
        parameter['mode'] = 'fast'
        parameter['Nt'] = '512'
        parameter['display'] = False
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        dB_threshold = float(parameter['dB_threshold'])
        rejectDuration = parameter['rejectDuration']
        mode = parameter['mode']
        Nt = int(parameter['Nt'])
        display = parameter['display']
        # index
        EVNtFract, EVNmean, EVNcount, _ = maad.features.temporal_events(s, fs, dB_threshold=dB_threshold, rejectDuration=rejectDuration, mode=mode, Nt=Nt, display=display)
        # print
        print("EVNtFract?" + str(EVNtFract) + "!EVNmean?" + str(EVNmean) + "!EVNcount?" + str(EVNcount))
    elif index_type == "frequency_entropy":
        # param
        parameter['compatibility'] = 'QUT'
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        compatibility = parameter['compatibility']
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        Hf, Ht_per_bin = maad.features.frequency_entropy(Sxx_power, compatibility=compatibility)
        # print
        print("Hf?" + str(Hf) + "!Length of Ht_per_bin?" + str(len(Ht_per_bin)))
    elif index_type == "number_of_peaks":
        # param
        parameter['mode'] = 'dB'
        parameter['min_peak_val'] = None
        parameter['min_freq_dist'] = '200'
        parameter['slopes'] = '1,1'
        parameter['prominence'] = '0'
        parameter['display'] = False
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        mode = parameter['mode']
        min_peak_val = parameter['min_peak_val'] if parameter['min_peak_val'] == None else float(parameter['min_peak_val'])
        min_freq_dist = float(parameter['min_freq_dist'])
        slopes = (float(parameter['slopes'].split(',')[0]), float(parameter['slopes'].split(',')[1]))
        prominence = float(parameter['prominence'])
        display = parameter['display']
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        NBPeaks = maad.features.number_of_peaks(Sxx_power, fn, mode=mode, min_peak_val=min_peak_val, min_freq_dist=min_freq_dist, slopes=slopes, prominence=prominence, display=display)
        # print
        print("NBPeaks?" + str(NBPeaks))
    elif index_type == "spectral_entropy":
        # param
        parameter['flim'] = None
        parameter['display'] = False
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        flim = parameter['flim'] if parameter['flim'] == None else (float(parameter['flim'].split(',')[0]), float(parameter['flim'].split(',')[1]))
        display = parameter['display']
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        EAS, ECU, ECV, EPS, EPS_KURT, EPS_SKEW = maad.features.spectral_entropy(Sxx_power, fn, flim=flim, display=display)
        # print
        print("EAS?" + str(EAS) + "!ECU?" + str(ECU) + "!ECV?" + str(ECV) + "!EPS?" + str(EPS) + "!EPS_KURT?" + str(EPS_KURT) + "!EPS_SKEW?" + str(EPS_SKEW))
    elif index_type == "spectral_activity":
        # param
        parameter['dB_threshold'] = "6"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        dB_threshold = float(parameter['dB_threshold'])
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        Sxx_noNoise = maad.sound.median_equalizer(Sxx_power, display=True, extent=ext)
        Sxx_dB_noNoise = maad.util.power2dB(Sxx_noNoise)
        _, _, ACTspmean_per_bin = maad.features.spectral_activity(Sxx_dB_noNoise, dB_threshold=dB_threshold)
        # print
        print("ACTspmean_per_bin?" + str(ACTspmean_per_bin))
    elif index_type == "spectral_cover":
        # param
        parameter['dB_threshold'] = "6"
        parameter['flim_LF'] = "0,1000"
        parameter['flim_MF'] = "1000,10000"
        parameter['flim_HF'] = "10000,20000"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        flim_LF = parameter['flim_LF'] if parameter['flim_LF'] == None else (float(parameter['flim_LF'].split(',')[0]), float(parameter['flim_LF'].split(',')[1]))
        flim_MF = parameter['flim_MF'] if parameter['flim_MF'] == None else (float(parameter['flim_MF'].split(',')[0]), float(parameter['flim_MF'].split(',')[1]))
        flim_HF = parameter['flim_HF'] if parameter['flim_HF'] == None else (float(parameter['flim_HF'].split(',')[0]), float(parameter['flim_HF'].split(',')[1]))
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        Sxx_noNoise = maad.sound.median_equalizer(Sxx_power, display=True, extent=ext)
        Sxx_dB_noNoise = maad.util.power2dB(Sxx_noNoise)
        LFC, MFC, HFC = maad.features.spectral_cover(Sxx_dB_noNoise, fn, flim_LF=flim_LF, flim_MF=flim_MF, flim_HF=flim_HF)
        # print
        print("LFC?" + str(LFC) + "!MFC?" + str(MFC) + "!HFC?" + str(HFC))
    elif index_type == "bioacoustics_index":
        # param
        parameter['flim'] = "2000,15000"
        parameter['R_compatible'] = "soundecology"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        flim = parameter['flim'] if parameter['flim'] == None else (float(parameter['flim'].split(',')[0]), float(parameter['flim'].split(',')[1]))
        R_compatible = parameter['R_compatible']
        # zoom
        Sxx, tn, fn, ext = maad.sound.spectrogram(s, fs, mode='amplitude')
        # index
        BI = maad.features.bioacoustics_index(Sxx, fn, flim=flim, R_compatible=R_compatible)
        # print
        print("BI?" + str(BI))
    elif index_type == "acoustic_diversity_index":
        # param
        parameter['fmin'] = "0"
        parameter['fmax'] = "20000"
        parameter['bin_step'] = "500"
        parameter['dB_threshold'] = "-50"
        parameter['index'] = "shannon"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        fmin = float(parameter['fmin'])
        fmax = float(parameter['fmax'])
        bin_step = float(parameter['bin_step'])
        dB_threshold = float(parameter['dB_threshold'])
        index = parameter['index']
        # zoom
        Sxx, tn, fn, ext = maad.sound.spectrogram(s, fs, mode='amplitude')
        # index
        ADI = maad.features.acoustic_diversity_index(Sxx, fn, fmin=fmin, fmax=fmax, bin_step=bin_step, dB_threshold=dB_threshold, index=index)
        # print
        print("ADI?" + str(ADI))
    elif index_type == "acoustic_eveness_index":
        # param
        parameter['fmin'] = "0"
        parameter['fmax'] = "20000"
        parameter['bin_step'] = "500"
        parameter['dB_threshold'] = "-50"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        fmin = float(parameter['fmin'])
        fmax = float(parameter['fmax'])
        bin_step = float(parameter['bin_step'])
        dB_threshold = float(parameter['dB_threshold'])
        # zoom
        Sxx, tn, fn, ext = maad.sound.spectrogram(s, fs, mode='amplitude')
        # index
        AEI = maad.features.acoustic_eveness_index(Sxx, fn, fmin=fmin, fmax=fmax, bin_step=bin_step, dB_threshold=dB_threshold)
        # print
        print("AEI?" + str(AEI))
    elif index_type == "temporal_leq":
        # param
        parameter['gain'] = "42"
        parameter['Vadc'] = "2"
        parameter['sensitivity'] = "-35"
        parameter['dBref'] = "94"
        parameter['dt'] = "1"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        gain = float(parameter['gain'])
        Vadc = float(parameter['Vadc'])
        sensitivity = float(parameter['sensitivity'])
        dBref = float(parameter['dBref'])
        dt = float(parameter['dt'])
        # index
        Leq = maad.features.temporal_leq(s, fs, gain=gain, Vadc=Vadc, sensitivity=sensitivity, dBref=dBref, dt=dt)
        # print
        print("Leq?" + str(Leq))
    elif index_type == "spectral_leq":
        # param
        parameter['gain'] = "42"
        parameter['Vadc'] = "2"
        parameter['sensitivity'] = "-35"
        parameter['dBref'] = "94"
        parameter['pRef'] = "20e-6"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        gain = float(parameter['gain'])
        Vadc = float(parameter['Vadc'])
        sensitivity = float(parameter['sensitivity'])
        dBref = float(parameter['dBref'])
        pRef = float(parameter['pRef'])
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        Leqf, _ = maad.features.spectral_leq(Sxx_power, gain=gain, Vadc=Vadc, sensitivity=sensitivity, dBref=dBref, pRef=pRef)
        # print
        print("Leqf?" + str(Leqf))
    elif index_type == "tfsd":
        # param
        parameter['flim'] = "2000,8000"
        parameter['mode'] = "thirdOctave"
        parameter['display'] = False
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        flim = (float(parameter['flim'].split(',')[0]), float(parameter['flim'].split(',')[1]))
        mode = parameter['mode']
        display = parameter['display']
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        tfsd = maad.features.tfsd(Sxx_power, fn, tn, flim=flim, mode=mode, display=display)
        # print
        print("tfsd?" + str(tfsd))
    elif index_type == "more_entropy_time":
        # param
        parameter['order'] = "3"
        parameter['axis'] = "0"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        order = float(parameter['order'])
        axis = int(parameter['axis'])
        # index
        env = maad.sound.envelope(s)
        Ht_Havrda, Ht_Renyi, Ht_pairedShannon, Ht_gamma, Ht_GiniSimpson = maad.features.more_entropy(env ** 2, order=order, axis=axis)
        # print
        print("Ht_Havrda?" + str(Ht_Havrda) + "!Ht_Renyi?" + str(Ht_Renyi) + "!Ht_pairedShannon?" + str(Ht_pairedShannon) + "!Ht_gamma?" + str(Ht_gamma) + "!Ht_GiniSimpson?" + str(Ht_GiniSimpson))
    elif index_type == "more_entropy_spectral":
        # param
        parameter['order'] = "3"
        parameter['axis'] = "0"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        order = float(parameter['order'])
        axis = int(parameter['axis'])
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        S_power = maad.sound.avg_power_spectro(Sxx_power)
        Hf_Havrda, Hf_Renyi, Hf_pairedShannon, Hf_gamma, Hf_GiniSimpson = maad.features.more_entropy(S_power, order=order, axis=axis)
        # print
        print("Hf_Havrda?" + str(Hf_Havrda) + "!Hf_Renyi?" + str(Hf_Renyi) + "!Hf_pairedShannon?" + str(Hf_pairedShannon) + "!Hf_gamma?" + str(Hf_gamma) + "!Hf_GiniSimpson?" + str(Hf_GiniSimpson))
    elif index_type == "acoustic_gradient_index":
        # param
        parameter['norm'] = "per_bin"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        norm = parameter['norm']
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        _, _, AGI_mean, AGI_sum = maad.features.acoustic_gradient_index(Sxx_power, tn[1] - tn[0], norm=norm)
        # print
        print("AGI_mean?" + str(AGI_mean) + "!AGI_sum?" + str(AGI_sum))
    elif index_type == "frequency_raoq":
        # param
        parameter['bin_step'] = "1000"
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        bin_step = int(parameter['bin_step'])
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        # index
        S_power = maad.sound.avg_power_spectro(Sxx_power)
        RAOQ = maad.features.frequency_raoq(S_power, fn, bin_step=bin_step)
        # print
        print("RAOQ?" + str(RAOQ))
    elif index_type == "template_matching":
        # param
        parameter['peak_th'] = "0.5"
        parameter['peak_distance'] = None
        parameter['chunk_duration'] = "30.0"  # Default chunk size in seconds
        if param != '' and param is not None:
            for p in param.split('@'):
                if '?' in p:
                    parameter[p.split('?')[0]] = p.split('?')[1]
        
        # Debug: Print what parameters we received
        print(f"DEBUG: Received parameters: {parameter}", file=sys.stderr)
        print(f"DEBUG: filename arg: {filename}", file=sys.stderr)
        print(f"DEBUG: sounds_dir: {sounds_dir}", file=sys.stderr)
        
        # Validate that we have selection coordinates
        required_params = ['selection_min_time', 'selection_max_time', 'selection_min_freq', 'selection_max_freq']
        for param_name in required_params:
            if param_name not in parameter:
                print(f"ERROR: Missing required parameter: {param_name}", file=sys.stderr)
                raise ValueError(f"Missing required parameter: {param_name}. Please select a region on the spectrogram before running template matching.")
        
        # Get selection coordinates (template region)
        sel_min_time = float(parameter['selection_min_time'])
        sel_max_time = float(parameter['selection_max_time'])  
        sel_min_freq = float(parameter['selection_min_freq'])
        sel_max_freq = float(parameter['selection_max_freq'])
        
        template_duration = sel_max_time - sel_min_time
        
        print(f"DEBUG: Selection (template): time={sel_min_time:.2f}-{sel_max_time:.2f}s, freq={sel_min_freq}-{sel_max_freq}Hz, duration={template_duration:.2f}s", file=sys.stderr)
        print(f"DEBUG: Search area (view): time={minTime}-{maxTime}s, freq={minFrequency}-{maxFrequency}Hz", file=sys.stderr)
        
        # Construct path to the original audio file
        try:
            audio_file_path = str(Path(__file__).resolve().parent.parent) + '/' + sounds_dir + '/' + parameter['collection_id'] + '/' + parameter['recording_directory'] + '/' + parameter['filename'].rsplit('.', 1)[0] + '.wav'
            print(f"DEBUG: Full audio path: {audio_file_path}", file=sys.stderr)
            print(f"DEBUG: Full audio exists: {Path(audio_file_path).exists()}", file=sys.stderr)
            
            view_min_time = float(minTime)
            view_max_time = float(maxTime)
            view_duration = view_max_time - view_min_time
            
            # Get file info without loading data
            with sf.SoundFile(audio_file_path) as f:
                fs_wav = f.samplerate
                channels = f.channels
            
            print(f"DEBUG: File info - sample_rate: {fs_wav}Hz, channels: {channels}", file=sys.stderr)
            
            # Estimate memory requirements
            # Audio: samples * 4 bytes (float32), Spectrogram: ~samples/nperseg * freq_bins * 8 bytes (float64)
            nperseg_estimate = 1024  # Default nperseg used by maad
            audio_mb = (view_duration * fs_wav * 4) / (1024 * 1024)
            spec_time_bins = int(view_duration / (nperseg_estimate / fs_wav))
            spec_freq_bins = nperseg_estimate // 2 + 1
            spec_mb = (spec_time_bins * spec_freq_bins * 8) / (1024 * 1024)
            total_estimated_mb = audio_mb + spec_mb + 300  # +300 MB overhead for template, cross-correlation, etc.
            
            print(f"DEBUG: Estimated memory for full view: {total_estimated_mb:.1f}MB (audio: {audio_mb:.1f}MB, spec: {spec_mb:.1f}MB)", file=sys.stderr)
            
            # Memory threshold: use chunking if estimated usage > 2GB (conservative for 8GB system)
            USE_CHUNKING = total_estimated_mb > 2000 or view_duration > 60
            
            if USE_CHUNKING:
                chunk_duration = float(parameter['chunk_duration'])
                
                # Validation: chunk must be >= template duration
                if chunk_duration < template_duration:
                    raise ValueError(f"chunk_duration ({chunk_duration}s) must be >= template duration ({template_duration:.2f}s)")
                
                # Set overlap to template duration (minimum safe overlap)
                chunk_overlap = template_duration
                
                # Additional validation
                if chunk_overlap >= chunk_duration:
                    raise ValueError(f"Template duration ({template_duration:.2f}s) is too large for chunk_duration ({chunk_duration}s). Increase chunk_duration.")
                
                print(f"DEBUG: Using CHUNKED processing - chunk_duration: {chunk_duration}s, overlap: {chunk_overlap:.2f}s", file=sys.stderr)
                
                # First, load the template from the selection
                # We need to load the template region from the file
                template_start_frame = int(sel_min_time * fs_wav)
                template_stop_frame = int(sel_max_time * fs_wav)
                
                with sf.SoundFile(audio_file_path) as f:
                    f.seek(template_start_frame)
                    template_audio_data = f.read(template_stop_frame - template_start_frame)
                    
                    if channels > 1:
                        if channel == 'left':
                            s_template = template_audio_data[:, 0]
                        elif channel == 'right':
                            s_template = template_audio_data[:, 1]
                        else:
                            s_template = template_audio_data[:, 0]
                    else:
                        s_template = template_audio_data if template_audio_data.ndim == 1 else template_audio_data.flatten()
                
                print(f"DEBUG: Template audio loaded - duration: {len(s_template)/fs_wav:.2f}s, samples: {len(s_template)}", file=sys.stderr)
                
                # Create template spectrogram (relative to template audio, so tlims start at 0)
                Sxx_template, _, _, _ = sound.spectrogram(s_template, fs_wav, flims=(sel_min_freq, sel_max_freq))
                print(f"DEBUG: Template spectrogram shape: {Sxx_template.shape}", file=sys.stderr)
                
                # Process in chunks
                all_rois = []
                chunk_start = view_min_time
                chunk_num = 0
                
                while chunk_start < view_max_time:
                    chunk_end = min(chunk_start + chunk_duration, view_max_time)
                    actual_chunk_duration = chunk_end - chunk_start
                    
                    print(f"DEBUG: Processing chunk {chunk_num}: {chunk_start:.2f}s to {chunk_end:.2f}s (duration: {actual_chunk_duration:.2f}s)", file=sys.stderr)
                    
                    # Load chunk audio
                    chunk_start_frame = int(chunk_start * fs_wav)
                    chunk_stop_frame = int(chunk_end * fs_wav)
                    
                    with sf.SoundFile(audio_file_path) as f:
                        f.seek(chunk_start_frame)
                        chunk_audio_data = f.read(chunk_stop_frame - chunk_start_frame)
                        
                        if channels > 1:
                            if channel == 'left':
                                s_chunk = chunk_audio_data[:, 0]
                            elif channel == 'right':
                                s_chunk = chunk_audio_data[:, 1]
                            else:
                                s_chunk = chunk_audio_data[:, 0]
                        else:
                            s_chunk = chunk_audio_data if chunk_audio_data.ndim == 1 else chunk_audio_data.flatten()
                    
                    # Create chunk spectrogram
                    Sxx_chunk, tn, fn, ext = sound.spectrogram(s_chunk, fs_wav, flims=(float(minFrequency), float(maxFrequency)))
                    print(f"DEBUG: Chunk {chunk_num} spectrogram shape: {Sxx_chunk.shape}", file=sys.stderr)
                    
                    # Run template matching on this chunk
                    peak_th = float(parameter['peak_th'])
                    peak_distance = parameter['peak_distance'] if parameter['peak_distance'] == None else float(parameter['peak_distance'])
                    
                    xcorrcoef, chunk_rois = maad.rois.template_matching(
                        Sxx=Sxx_chunk,
                        Sxx_template=Sxx_template,
                        tn=tn,
                        fn=fn,
                        ext=ext,
                        peak_th=peak_th,
                        peak_distance=peak_distance,
                        display=False
                    )
                    
                    print(f"DEBUG: Chunk {chunk_num} found {len(chunk_rois)} matches", file=sys.stderr)
                    
                    # Adjust ROI times to absolute file times
                    if len(chunk_rois) > 0:
                        chunk_rois['min_t'] = chunk_rois['min_t'] + chunk_start
                        chunk_rois['max_t'] = chunk_rois['max_t'] + chunk_start
                        chunk_rois['peak_time'] = chunk_rois['peak_time'] + chunk_start
                        all_rois.append(chunk_rois)
                    
                    # Move to next chunk (with overlap)
                    chunk_start += (chunk_duration - chunk_overlap)
                    chunk_num += 1
                
                # Merge all ROIs
                if len(all_rois) > 0:
                    rois = pd.concat(all_rois, ignore_index=True)
                    print(f"DEBUG: Total matches before deduplication: {len(rois)}", file=sys.stderr)
                    
                    # Deduplicate matches in overlap regions
                    # If two matches have peak_time within overlap_duration, keep the one with higher xcorrcoef
                    if len(rois) > 1:
                        rois = rois.sort_values('peak_time').reset_index(drop=True)
                        to_remove = []
                        for i in range(len(rois) - 1):
                            time_diff = rois.loc[i + 1, 'peak_time'] - rois.loc[i, 'peak_time']
                            if time_diff < chunk_overlap:
                                # These might be duplicates - keep the one with higher correlation
                                if rois.loc[i, 'xcorrcoef'] >= rois.loc[i + 1, 'xcorrcoef']:
                                    to_remove.append(i + 1)
                                else:
                                    to_remove.append(i)
                        
                        if to_remove:
                            rois = rois.drop(to_remove).reset_index(drop=True)
                            print(f"DEBUG: Removed {len(to_remove)} duplicate matches in overlaps", file=sys.stderr)
                    
                    print(f"DEBUG: Final total: {len(rois)} matches after deduplication", file=sys.stderr)
                else:
                    # Create empty DataFrame with correct columns
                    rois = pd.DataFrame(columns=['peak_time', 'xcorrcoef', 'min_t', 'max_t', 'min_f', 'max_f'])
                    print(f"DEBUG: No matches found in any chunk", file=sys.stderr)
            
            else:
                # NON-CHUNKED processing for small views
                print(f"DEBUG: Using NON-CHUNKED processing (view is small enough)", file=sys.stderr)
                
                # Load the view segment
                start_frame = int(view_min_time * fs_wav)
                stop_frame = int(view_max_time * fs_wav)
                
                print(f"DEBUG: Loading audio segment from {view_min_time}s to {view_max_time}s (duration: {view_duration:.2f}s)", file=sys.stderr)
                
                with sf.SoundFile(audio_file_path) as f:
                    f.seek(start_frame)
                    audio_data = f.read(stop_frame - start_frame)
                    
                    if channels > 1:
                        if channel == 'left':
                            s_wav = audio_data[:, 0]
                        elif channel == 'right':
                            s_wav = audio_data[:, 1]
                        else:
                            s_wav = audio_data[:, 0]
                    else:
                        s_wav = audio_data if audio_data.ndim == 1 else audio_data.flatten()
                
                print(f"DEBUG: Audio segment loaded - duration: {len(s_wav)/fs_wav:.2f}s, sample_rate: {fs_wav}Hz, samples: {len(s_wav)}", file=sys.stderr)
                
                # Adjust selection times to be relative to the loaded segment
                seg_sel_min_time = sel_min_time - view_min_time
                seg_sel_max_time = sel_max_time - view_min_time
                
                print(f"DEBUG: Adjusted selection for segment: {seg_sel_min_time:.2f}s to {seg_sel_max_time:.2f}s", file=sys.stderr)
                
                # Create template spectrogram
                print(f"DEBUG: Creating template spectrogram...", file=sys.stderr)
                Sxx_template, _, _, _ = sound.spectrogram(s_wav, fs_wav, flims=(sel_min_freq, sel_max_freq), tlims=(seg_sel_min_time, seg_sel_max_time))
                print(f"DEBUG: Template spectrogram shape: {Sxx_template.shape}", file=sys.stderr)
                
                # Create search area spectrogram
                print(f"DEBUG: Creating search area spectrogram...", file=sys.stderr)
                Sxx_audio, tn, fn, ext = sound.spectrogram(s_wav, fs_wav, flims=(float(minFrequency), float(maxFrequency)))
                print(f"DEBUG: Search area spectrogram shape: {Sxx_audio.shape}", file=sys.stderr)
                
                # Run template matching
                print(f"DEBUG: Running template_matching...", file=sys.stderr)
                peak_th = float(parameter['peak_th'])
                peak_distance = parameter['peak_distance'] if parameter['peak_distance'] == None else float(parameter['peak_distance'])
                
                xcorrcoef, rois = maad.rois.template_matching(
                    Sxx=Sxx_audio,
                    Sxx_template=Sxx_template,
                    tn=tn,
                    fn=fn,
                    ext=ext,
                    peak_th=peak_th,
                    peak_distance=peak_distance,
                    display=True
                )
                print(f"DEBUG: template_matching completed. Found {len(rois)} matches", file=sys.stderr)
                
                # Adjust ROI times back to absolute times
                if len(rois) > 0:
                    rois['min_t'] = rois['min_t'] + view_min_time
                    rois['max_t'] = rois['max_t'] + view_min_time  
                    rois['peak_time'] = rois['peak_time'] + view_min_time
                    print(f"DEBUG: Adjusted ROI times by offset {view_min_time}s", file=sys.stderr)
        
        except KeyError as e:
            print(f"ERROR: Missing required parameter: {e}", file=sys.stderr)
            raise
        except FileNotFoundError as e:
            print(f"ERROR: Audio file not found: {e}", file=sys.stderr)
            raise
        except Exception as e:
            print(f"ERROR: template_matching failed: {type(e).__name__}: {e}", file=sys.stderr)
            import traceback
            traceback.print_exc(file=sys.stderr)
            raise
        
        # print
        print(rois)
    else:
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        result = numpy.where(Sxx_power == numpy.max(Sxx_power))
        print(int(fn[result[0][0]]))


if __name__ == '__main__':
    # Read config to get SOUNDS_DIR
    config = configparser.ConfigParser()
    config.read(str(Path(__file__).resolve().parent.parent) + '/config/config.ini')
    sounds_dir = config.get('Directories', 'SOUNDS_DIR', fallback='sounds/sounds')
    # Strip quotes if present in the config value
    sounds_dir = sounds_dir.strip("'\"")
    
    parser = optparse.OptionParser()
    parser.add_option('-f', '--filename', type="string", dest='filename')
    parser.add_option('--it', type="string", dest='index_type')
    parser.add_option('--pa', type="string", dest='param')
    parser.add_option('--ch', type="string", dest='channel')
    parser.add_option('--mint', type="string", dest='minTime')
    parser.add_option('--maxt', type="string", dest='maxTime')
    parser.add_option('--minf', type="string", dest='minFrequency')
    parser.add_option('--maxf', type="string", dest='maxFrequency')
    parser.set_defaults(filename=None, index_type=None, param=None, channel="left", minTime=None, maxTime=None, minFrequency=None, maxFrequency=None)

    (options, args) = parser.parse_args()
    getMaad(options.filename, options.index_type, options.param, options.channel, options.minTime, options.maxTime, options.minFrequency, options.maxFrequency)
