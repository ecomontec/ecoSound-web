import optparse
import maad
from maad import sound, rois
import numpy


def getMaad(path, filename, index_type, param, channel, minTime, maxTime, minFrequency, maxFrequency):
    parameter = {}
    s, fs = maad.sound.load(path + filename + '.wav', channel=channel)
    if index_type == "acoustic_complexity_index":
        # zoom
        Sxx, tn, fn, ext = maad.sound.spectrogram(s, fs, mode='amplitude')
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        _, _, ACI_sum = maad.features.acoustic_complexity_index(Lxx_crop)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        NDSI, ratioBA, antroPh, bioPh = maad.features.soundscape_index(Lxx_crop, fn_crop, flim_bioPh=flim_bioPh, flim_antroPh=flim_antroPh, R_compatible=parameter['R_compatible'])
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
        # zoom
        s_slice = sound.trim(s, fs, min_t=float(minTime), max_t=float(maxTime))
        # index
        med = maad.features.temporal_median(s_slice, mode=mode, Nt=Nt)
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
        # zoom
        s_slice = sound.trim(s, fs, min_t=float(minTime), max_t=float(maxTime))
        # index
        Ht = maad.features.temporal_entropy(s_slice, mode=mode, Nt=Nt)
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
        # zoom
        s_slice = sound.trim(s, fs, min_t=float(minTime), max_t=float(maxTime))
        # index
        ACTfract, ACTcount, ACTmean = maad.features.temporal_activity(s_slice, dB_threshold=dB_threshold, mode=mode, Nt=Nt)
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
        # zoom
        s_slice = sound.trim(s, fs, min_t=float(minTime), max_t=float(maxTime))
        # index
        EVNtFract, EVNmean, EVNcount, _ = maad.features.temporal_events(s_slice, fs, dB_threshold=dB_threshold, rejectDuration=rejectDuration, mode=mode, Nt=Nt, display=display)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        Hf, Ht_per_bin = maad.features.frequency_entropy(Lxx_crop, compatibility=compatibility)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        NBPeaks = maad.features.number_of_peaks(Lxx_crop, fn_crop, mode=mode, min_peak_val=min_peak_val, min_freq_dist=min_freq_dist, slopes=slopes, prominence=prominence, display=display)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        EAS, ECU, ECV, EPS, EPS_KURT, EPS_SKEW = maad.features.spectral_entropy(Lxx_crop, fn_crop, flim=flim, display=display)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        Sxx_noNoise = maad.sound.median_equalizer(Lxx_crop, display=True, extent=ext)
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
        dB_threshold = float(parameter['dB_threshold'])
        flim_LF = parameter['flim_LF'] if parameter['flim_LF'] == None else (float(parameter['flim_LF'].split(',')[0]), float(parameter['flim_LF'].split(',')[1]))
        flim_MF = parameter['flim_MF'] if parameter['flim_MF'] == None else (float(parameter['flim_MF'].split(',')[0]), float(parameter['flim_MF'].split(',')[1]))
        flim_HF = parameter['flim_HF'] if parameter['flim_HF'] == None else (float(parameter['flim_HF'].split(',')[0]), float(parameter['flim_HF'].split(',')[1]))
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        Sxx_noNoise = maad.sound.median_equalizer(Lxx_crop, display=True, extent=ext)
        Sxx_dB_noNoise = maad.util.power2dB(Sxx_noNoise)
        LFC, MFC, HFC = maad.features.spectral_cover(Sxx_dB_noNoise, fn_crop, flim_LF=flim_LF, flim_MF=flim_MF, flim_HF=flim_HF)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        BI = maad.features.bioacoustics_index(Lxx_crop, fn_crop, flim=flim, R_compatible=R_compatible)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        ADI = maad.features.acoustic_diversity_index(Lxx_crop, fn_crop, fmin=fmin, fmax=fmax, bin_step=bin_step, dB_threshold=dB_threshold, index=index)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        AEI = maad.features.acoustic_eveness_index(Lxx_crop, fn_crop, fmin=fmin, fmax=fmax, bin_step=bin_step, dB_threshold=dB_threshold)
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
        # zoom
        s_slice = sound.trim(s, fs, min_t=float(minTime), max_t=float(maxTime))
        # index
        Leq = maad.features.temporal_leq(s_slice, fs, gain=gain, Vadc=Vadc, sensitivity=sensitivity, dBref=dBref, dt=dt)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        Leqf, _ = maad.features.spectral_leq(Lxx_crop, gain=42, Vadc=Vadc, sensitivity=sensitivity, dBref=dBref, pRef=pRef)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        tfsd = maad.features.tfsd(Lxx_crop, fn_crop, tn_crop)
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
        # zoom
        s_slice = sound.trim(s, fs, min_t=float(minTime), max_t=float(maxTime))
        env = maad.sound.envelope(s_slice)
        # index
        Ht_Havrda, Ht_Renyi, Ht_pairedShannon, Ht_gamma, Ht_GiniSimpson = maad.features.more_entropy(env ** 2, order=order, axis=axis)
        # print
        print("Ht_Havrda?" + str(Ht_Havrda) + "!Ht_Renyi?" + str(Ht_Renyi) + "!Ht_pairedShannon?" + str(Ht_pairedShannon) + "!Ht_gamma?" + str(Ht_gamma) + "!Ht_GiniSimpson?" + str(Ht_GiniSimpson))
    elif index_type == "more_entropy_spectral ":
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        Hf_Havrda, Hf_Renyi, Hf_pairedShannon, Hf_gamma, Hf_GiniSimpson = maad.features.more_entropy(Lxx_crop, order=order, axis=axis)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        _, _, AGI_mean, AGI_sum = maad.features.acoustic_gradient_index(Lxx_crop, tn_crop[1] - tn_crop[0], norm=norm)
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
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        S_power = maad.sound.avg_power_spectro(Lxx_crop)
        RAOQ = maad.features.frequency_raoq(S_power, fn_crop, bin_step=bin_step)
        # print
        print("RAOQ?" + str(RAOQ))
    elif index_type == "region_of_interest_index":
        # param
        parameter['smooth_param1'] = "1"
        parameter['mask_mode'] = "relative"
        parameter['mask_param1'] = "6"
        parameter['mask_param2'] = "0.5"
        parameter['min_roi'] = None
        parameter['max_roi'] = None
        parameter['remove_rain'] = False
        parameter['display'] = False
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        smooth_param1 = float(parameter['smooth_param1'])
        mask_mode = parameter['mask_mode']
        mask_param1 = float(parameter['mask_param1'])
        mask_param2 = float(parameter['mask_param2'])
        min_roi = parameter['min_roi'] if parameter['min_roi'] == None else float(parameter['min_roi'])
        max_roi = parameter['max_roi'] if parameter['max_roi'] == None else float(parameter['max_roi'])
        remove_rain = parameter['remove_rain']
        display = parameter['display']
        # zoom
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        # index
        Sxx_noNoise = maad.sound.median_equalizer(Lxx_crop)
        Sxx_dB_noNoise = maad.util.power2dB(Sxx_noNoise)
        ROItotal, ROIcover = maad.features.region_of_interest_index(Sxx_dB_noNoise, tn_crop, fn_crop, smooth_param1=smooth_param1, mask_mode=mask_mode, mask_param1=mask_param1,
                                                                    mask_param2=mask_param2, min_roi=min_roi, max_roi=max_roi, remove_rain=remove_rain, display=display)
        # print
        print("ROItotal?" + str(ROItotal) + "!ROIcover?" + str(ROIcover))
    else:
        print(0)


if __name__ == '__main__':
    parser = optparse.OptionParser()
    parser.add_option('-p', '--path', type="string", dest='path')
    parser.add_option('-f', '--filename', type="string", dest='filename')
    parser.add_option('--it', type="string", dest='index_type')
    parser.add_option('--pa', type="string", dest='param')
    parser.add_option('--ch', type="string", dest='channel')
    parser.add_option('--mint', type="string", dest='minTime')
    parser.add_option('--maxt', type="string", dest='maxTime')
    parser.add_option('--minf', type="string", dest='minFrequency')
    parser.add_option('--maxf', type="string", dest='maxFrequency')
    parser.set_defaults(path=None, filename=None, index_type=None, param=None, channel="1", minTime=None, maxTime=None, minFrequency=None, maxFrequency=None)

    (options, args) = parser.parse_args()
    getMaad(options.path, options.filename, options.index_type, options.param, options.channel, options.minTime, options.maxTime, options.minFrequency, options.maxFrequency)
