<?php

class Services_VideoUrlParser
{

    static public function parse($url)
    {
        if (stripos($url, 'https://') !== 0 && stripos($url, 'http://') !== 0)
        {
            return $url;
        }
        if (!H::content_url_whitelist_check($url))
        {
            return "<a href=\"$url\" rel=\"nofollow noreferrer noopener\" target=\"_blank\">$url</a>";
        }
        return "<video controls preload=\"none\" src=\"$url\" style=\"max-width:100%\"></video>";
    }

}
