<?php namespace Srw\Catalog\Controllers;

use Backend\Classes\Controller;
use Cms\Classes\Page as Pg;
use Cms\Classes\Theme;
use System\Classes\PluginManager;
use Srw\Catalog\Components\Catalogtree;
use Srw\Catalog\Models\Items;
use Request;

class Sitemap extends Controller
{
    public static $sitemapOut,
                  $domain,
                  $theme,
                  $catalogSection;

  public static function getDomain ()
  {
      if (Request::secure())
      {
         return 'https://'.$_SERVER['HTTP_HOST'];
      } else {
         return 'http://'.$_SERVER['HTTP_HOST'];
      }
  }

    public static function generate ()
    {
        self::$domain = self::getDomain();
        self::$theme = $theme = Theme::getEditTheme();
        self::$sitemapOut = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
        self::$sitemapOut .= self::getCmsPages();

        if(PluginManager::instance()->hasPlugin('RainLab.Pages'))
        self::$sitemapOut .= self::getStaticPages();

        self::$sitemapOut .= self::getCatalogPages();
        self::$sitemapOut .= '</urlset>';

        return self::$sitemapOut;
    }

    public static function blockChain ($url,$lastmod) {
        $domain = self::$domain;
        return "<url>
                    <loc>$domain$url</loc>
                    <lastmod>$lastmod</lastmod>
                    <changefreq>weekly</changefreq>
                    <priority>1</priority>
                </url>";
    }

    public static function getCmsPages ()
    {
        $section = '';
        $cmsPages = Pg::listInTheme(self::$theme, true);
        foreach ($cmsPages as $cmsPage) {
            $lastmod = $cmsPage->attributes['mtime'];
            $lastmod = date('Y-m-d', $lastmod);
            $code = $cmsPage['original']['code'];
            preg_match('/^#sitemap\{(.*)\}/i', $code, $match);
            $url = '';
            if($match) {
                $url = $match[1];
                if(!$url) $url = $cmsPage->settings['url'];
                $section .= self::blockChain($url, $lastmod);
            }
        }
        return $section;
    }

    public static function getStaticPages ()
    {
        $section = '';
        $staticPages = new \RainLab\Pages\Classes\PageList(self::$theme);
        $Pages = $staticPages->listPages();
        foreach ($Pages as $Page) {
            $lastmod = $Page->attributes['mtime'];
            $lastmod = date('Y-m-d', $lastmod);
            $url = $Page->viewBag['url'];
            $section .= self::blockChain($url, $lastmod);
        }
        return $section;
    }

    public static function getCatalogPages ()
    {
        $section = '';
        $categories = new Catalogtree;
        $tree = $categories->getCatalog();
        $lastmod = date('Y-m-d');

        self::rExplorer ($tree, $lastmod, '/produktsiya');
        $section = self::$catalogSection;
        return $section;
    }

    public static function rExplorer ($tree, $lastmod, $url = '')
    {
        foreach ($tree as $record) {
            $newUrl = $url . $record->slug;

            self::$catalogSection .=
            self::blockChain($newUrl, $lastmod);

            self::$catalogSection .=
            self::getCatalogItems($record->id, $newUrl);

            if($record->items !== null) {
                self::$catalogSection .=
                self::rExplorer ($record->items, $lastmod, $newUrl);
            }
        }
    }

    public static function getCatalogItems ($category_id, $urlBefore)
    {
        $section = '';
        $items = Items::where('category_id', $category_id)->get();

        if($items)
        foreach ($items as $item) {
            $lastmod  = date('Y-m-d');
            $section .= self::blockChain($urlBefore.$item->slug, $lastmod);
        }

        return $section;
    }

    public static function robots ()
    {
        $domain = self::getDomain();
        return "User-agent: *\n"
              ."Аllow: /\n"
              ."Host: $domain\n"
              ."Sitemap: $domain/sitemap.xml";
    }

}
