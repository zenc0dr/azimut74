<?php namespace Mcmraak\Rivercrs\Classes;

class CheckinHtmlCleaner
{
    /**
     * Очищает HTML-описание заезда от внешних ссылок/оберток.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }
        
        $cleaned = $html;
        
        // Убираем ссылки, сохраняя текст внутри.
        $cleaned = preg_replace('/<a\b[^>]*>(.*?)<\/a>/is', '$1', $cleaned);
        
        // Убираем служебные обертки, которые часто приезжают из источников.
        $cleaned = preg_replace('/<span\b[^>]*>(.*?)<\/span>/is', '$1', $cleaned);
        $cleaned = preg_replace('/<u\b[^>]*>(.*?)<\/u>/is', '$1', $cleaned);
        
        return $cleaned;
    }
}
