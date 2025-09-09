<?php

class SocialLinkModule
{
    public static function IsYoutubeLink($link): string
    {
        $pattern = '/https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|playlist\?list=)|youtu\.be\/)([a-zA-Z0-9_-]+)/i';
        if (preg_match($pattern, $link, $matches)) {
            $videoId = $matches[1]; // Eşleşen video ID'si
            return $videoId;
        } else {
            return "";
        }
    }

}