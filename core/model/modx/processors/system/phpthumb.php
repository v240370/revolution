<?php
/**
 * Generate a thumbnail
 *
 * @package modx
 * @subpackage processors.system
 */
if (!isset($modx)) die();

/* get modFileHandler service */
$wctx = isset($scriptProperties['wctx']) && !empty($scriptProperties['wctx']) ? $scriptProperties['wctx'] : $modx->context->get('key');
$modx->getService('fileHandler','modFileHandler','',array('context' => $wctx));

/* filter path */
$src = $modx->getOption('src',$scriptProperties,'');
if (empty($src)) return '';

$source = $modx->getOption('source',$scriptProperties,1);

/** @var modMediaSource|modFileMediaSource $source */
$source = $modx->getObject('modMediaSource',$source);
if (empty($source)) return '';

if (!$source->getWorkingContext()) {
    return $modx->error->failure($modx->lexicon('permission_denied'));
}
$source->setRequestProperties($scriptProperties);
$source->initialize();
$bases = $source->getBases($src);


/* dont strip stuff for absolute URLs */
if (substr($src,0,4) != 'http') {
    $valid = true;
    if (!empty($scriptProperties['baseUrlPrependCheckSlash'])) {
        $valid = !(substr($src,0,1) == '/' || substr($src,0,7) == 'http://' || substr($src,0,8) == 'https://');
    }
    if ($valid) {
        $src = $bases['pathFull'];
        /* strip out double slashes */
        $src = str_replace(array('///','//'),'/',$src);

        /* check for file existence if local url */
        if (empty($src) || !file_exists($src)) {
            if (file_exists('/'.$src)) {
                $src = '/'.$src;
            } else {
                return '';
            }
        }
    }
}

/* load phpThumb */
if (!$modx->loadClass('modPhpThumb',$modx->getOption('core_path').'model/phpthumb/',true,true)) {
    $modx->log(modX::LOG_LEVEL_ERROR,'Could not load modPhpThumb class.');
    return '';
}
$phpThumb = new modPhpThumb($modx,$scriptProperties);
/* do initial setup */
$phpThumb->initialize();
/* set source and generate thumbnail */
$phpThumb->set($src);

/* check to see if there's a cached file of this already */
if ($phpThumb->checkForCachedFile()) {
    $phpThumb->loadCache();
    return '';
}

/* generate thumbnail */
$phpThumb->generate();

/* cache the thumbnail and output */
$phpThumb->cache();
$phpThumb->output();

return '';
