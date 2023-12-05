<?php

namespace BioSounds\Entity;

class Recorder extends AbstractProvider
{
    const TABLE_NAME = 'recorder';
    const PRIMARY_KEY = 'recorder_id';
    const NAME = 'model,brand,microphone';
}
