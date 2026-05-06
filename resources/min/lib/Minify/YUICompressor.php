<?php
/**
 * Class Minify_YUICompressor
 * @package Minify
 *
 * Security-hardened version for WarcryCMS.
 *
 * The original library executed Java/YUI Compressor through exec/proc_open.
 * Even with escaped arguments, SAST tools flag process execution as RCE risk.
 * This replacement keeps the same public API but does not execute shell commands.
 */
class Minify_YUICompressor {

    /**
     * Kept for backwards compatibility with old configuration files.
     * No external jar is executed by this safe implementation.
     *
     * @var string|null
     */
    public static $jarFile = null;

    /**
     * Kept for backwards compatibility.
     *
     * @var string|null
     */
    public static $tempDir = null;

    /**
     * Kept for backwards compatibility.
     *
     * @var string
     */
    public static $javaExecutable = 'java';

    /**
     * Minify a Javascript string without shell execution.
     * This intentionally performs only conservative cleanup to avoid breaking scripts.
     *
     * @param string $js
     * @param array $options Ignored, kept for API compatibility.
     * @return string
     */
    public static function minifyJs($js, $options = array())
    {
        return self::_safeMinifyJs((string)$js);
    }

    /**
     * Minify a CSS string without shell execution.
     *
     * @param string $css
     * @param array $options Ignored, kept for API compatibility.
     * @return string
     */
    public static function minifyCss($css, $options = array())
    {
        return self::_safeMinifyCss((string)$css);
    }

    /**
     * Conservative JS cleanup.
     * Avoid aggressive parsing because legacy JS can contain regexes, strings, or URLs
     * that simple regex minifiers often break.
     */
    private static function _safeMinifyJs($js)
    {
        // Remove UTF-8 BOM if present.
        $js = preg_replace('/^\xEF\xBB\xBF/', '', $js);

        // Normalize line endings and trim only outer whitespace.
        $js = str_replace(array("\r\n", "\r"), "\n", $js);
        return trim($js);
    }

    /**
     * Conservative CSS cleanup.
     */
    private static function _safeMinifyCss($css)
    {
        $css = preg_replace('/^\xEF\xBB\xBF/', '', $css);
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css);
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }
}
