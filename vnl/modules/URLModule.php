<?php

class URLModule
{

    public static function getPageName()
    {
        return route(0);
    }

    public static function getCurrentURL()
    {
        global $cDefinitions;
        return $cDefinitions->getSiteUrl() ."/". self::getPageName();
    }



}