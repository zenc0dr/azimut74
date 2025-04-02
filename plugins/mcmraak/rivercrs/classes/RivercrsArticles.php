<?php namespace Mcmraak\Rivercrs\Classes;

class RivercrsArticles
{
    public static function articles($cruise): array
    {
        $articles = [];
        foreach ($cruise->seo_articles as $article) {
            $articles[] = [
                'seo_hash' => $article['seo_hash'],
                'title' => $article['seo_title'],
                'text' => $article['seo_text']
            ];
        }
        return $articles;
    }
}
