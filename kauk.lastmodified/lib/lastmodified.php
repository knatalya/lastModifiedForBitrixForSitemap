<?php
namespace Kauk\LastModified;

class LastModified
{
    protected static $defaultSitemapPath = '/sitemap.xml';

    public static function getSitemapPath()
    {
        $robotsPath = $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
        $sitemapPath = self::$defaultSitemapPath;

        if (file_exists($robotsPath)) {
            $robotsContent = file($robotsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($robotsContent as $line) {
                if (stripos($line, 'Sitemap:') === 0) {
                    $sitemapPath = trim(str_ireplace('Sitemap: ', '', $line));
                    break;
                }
            }
        }

        if (strpos($sitemapPath, 'https') === false) {
            $sitemapPath = $_SERVER['DOCUMENT_ROOT'] . $sitemapPath;
        } else {
            $sitemapPath = self::downloadSitemap($sitemapPath);
        }

        return $sitemapPath;
    }

    protected static function downloadSitemap($url)
    {
        $tempPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/sitemap.xml';
        $sitemapContent = file_get_contents($url);

        if ($sitemapContent) {
            file_put_contents($tempPath, $sitemapContent);
            return $tempPath;
        }

        return null;
    }

    public static function getLastModified($url)
    {
        $sitemapPath = self::getSitemapPath();
        if (!$sitemapPath || !file_exists($sitemapPath)) {
            return null;
        }

        return self::searchSitemapForLastModified($sitemapPath, $url);
    }

    protected static function searchSitemapForLastModified($sitemapPath, $url)
    {
        $sitemapContent = file_get_contents($sitemapPath);
        
        // Check if this is a sitemap index
        if (preg_match_all('#<sitemap>\s*<loc>([^<]+)</loc>\s*<lastmod>([^<]+)</lastmod>\s*</sitemap>#', $sitemapContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $nestedSitemapUrl = $match[1];
                $downloadedPath = self::downloadSitemap($nestedSitemapUrl);
                
                if ($downloadedPath) {
                    $lastModified = self::searchSitemapForLastModified($downloadedPath, $url);
                    if ($lastModified) {
                        return $lastModified;
                    }
                }
            }
        }

        // Check for the URL in the current sitemap
        $pattern = sprintf('#<url>\s*<loc>%s</loc>\s*<lastmod>([^<]+)</lastmod>#', preg_quote($url, '#'));

        if (preg_match($pattern, $sitemapContent, $matches)) {
            return strtotime($matches[1]);
        }

        return null;
    }

    public static function setLastModifiedHeader($url)
    {
        $lastModified = self::getLastModified($url);
        if ($lastModified) {
            // Handle If-Modified-Since header
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($ifModifiedSince >= $lastModified) {
                    header('HTTP/1.1 304 Not Modified');
                    exit; // Stop script execution
                }
            }

            // Set Last-Modified header
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        }
    }

    public static function onPageStartHandler()
    {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        self::setLastModifiedHeader($url);
    }
}
