<?php

class DeepZoomHooks
{

    /**
     * Adds .dzi to the list of allowed file extensions for upload.
     *
     * @param array &$allowedExtensions The array of allowed file extensions
     * @param User &$user The user attempting the upload
     * @param string $file The file name being uploaded
     * @param string &$error The error message in case of failure
     * @return bool
     */
    public static function onUploadVerifyFileExtensions(array &$allowedExtensions, &$user, &$file, &$error)
    {
        $allowedExtensions[] = 'dzi';
        return true;
    }

    /**
     * Registers the MIME type for .dzi files as image/dzi and maps it explicitly.
     *
     * @param MimeAnalyzer &$mime The MIME analyzer to modify
     * @return bool
     */
    public static function onMimeMagicInit(&$mimeMagic)
    {
        // Register the MIME type for .dzi files and associate it explicitly
        $mimeMagic->addExtraTypes('image/dzi dzi');
        $mimeMagic->addExtraInfo('image/dzi [BITMAP]');
        $mimeMagic->mExtToMime['dzi'] = 'image/dzi';
    }

    public static function onMimeMagicImproveFromExtension($mimeMagic, $ext, &$mime)
    {
        if ($ext == 'dzi') {
            $mime = 'image/dzi';
        }
    }

    /**
     * Force MIME type to image/dzi when MediaWiki guesses MIME type from file content.
     */
    public static function onMimeMagicGuessFromContent($mimeMagic, &$head, &$tail, $file, &$mime)
    {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'dzi') {
            $mime = 'image/dzi';
        }
    }


    /**
     * Force MIME type for .dzi files during upload verification.
     */
    public static function onUploadVerifyFile($upload, &$mime, &$error)
    {
        // TODO
    }
}
