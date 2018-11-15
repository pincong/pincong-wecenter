<?php

class Services_VideoUrlParser
{

    static public function parse($url)
    {
        if (strpos($url, 'https://') !== 0 && strpos($url, 'http://') !== 0)
        {
            return $url;
        }
        return "<a href=\"$url\" rel=\"nofollow noreferrer noopener\" target=\"_blank\">$url</a>";
    }

}
