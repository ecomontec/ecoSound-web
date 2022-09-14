import optparse
import maad
from maad import sound, rois

def getMaad(path, filename, index_type, param, channel, minTime, maxTime, minFrequency, maxFrequency):
    parameter = {}
    s, fs = maad.sound.load(path + filename + '.wav', channel=channel)
    if index_type == "acoustic_complexity_index":
        Sxx, tn, fn, ext = maad.sound.spectrogram(s, fs, mode='amplitude')
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        _, _, ACI_sum = maad.features.acoustic_complexity_index(Lxx_crop)
        print("ACI_sum?" + str(ACI_sum))
    elif index_type == "soundscape_index":
        parameter['flim_bioPh'] = '1000,10000'
        parameter['flim_antroPh'] = '0,1000'
        parameter['R_compatible'] = 'soundecology'
        if param != '' and param is not None:
            for p in param.split('@'):
                parameter[p.split('?')[0]] = p.split('?')[1]
        flim_bioPh = parameter['flim_bioPh']
        flim_antroPh = parameter['flim_antroPh']
        flim_bioPh = (float(flim_bioPh.split(',')[0]), float(flim_bioPh.split(',')[1]))
        flim_antroPh = (float(flim_antroPh.split(',')[0]), float(flim_antroPh.split(',')[1]))
        Sxx_power, tn, fn, ext = maad.sound.spectrogram(s, fs)
        Lxx_crop, tn_crop, fn_crop = maad.util.crop_image(Sxx_power, tn, fn, fcrop=(float(minFrequency), float(maxFrequency)), tcrop=(float(minTime), float(maxTime)))
        NDSI, ratioBA, antroPh, bioPh = maad.features.soundscape_index(Lxx_crop, fn_crop, flim_bioPh=flim_bioPh, flim_antroPh=flim_antroPh, R_compatible=parameter['R_compatible'])
        print("NDSI?" + str(NDSI) + "!ratioBA?" + str(ratioBA) + "!antroPh?" + str(antroPh) + "!bioPh?" + str(bioPh))
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
