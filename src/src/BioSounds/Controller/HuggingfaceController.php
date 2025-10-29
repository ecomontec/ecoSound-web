<?php

namespace BioSounds\Controller;

// Shim to satisfy PSR-4/autoloader when it looks for
// BioSounds\Controller\HuggingfaceController (note lowercase 'f').
// It simply requires the real controller file so the class
// (and its compatibility alias) are defined.

require_once __DIR__ . '/HuggingFaceController.php';

// Declare alias here (only in the shim) to avoid duplicate class declarations
// when the real controller file is loaded directly or via this shim.
if (!class_exists(__NAMESPACE__ . '\\HuggingfaceController', false)) {
	class HuggingfaceController extends HuggingFaceController {}
}
