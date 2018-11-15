<?php

class Services_ImageUrlParser
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
        return "<img src=\"$url\" alt=\"$url\" style=\"max-width:100%\">";
    }

}
