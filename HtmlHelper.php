<?php

namespace html\helper;

/**
 * Class HtmlHelper
 * @package html\helper
 */
class HtmlHelper
{
    /**
     * @return string - begin html
     */
    public static function startHtml()
    {
        return '<!DOCTYPE html><html>';
    }

    /**
     * @return string - finish html
     */
    /*public static function endHtml()
    {
        return '</body></html>';
    }*/

    /**
     * @param string $ttl - title
     * @return string - begin head
     */
    public static function startHead($ttl)
    {
        return "<head><title>$ttl</title>";
    }

    /**
     * @return string - finish head, begin body
     */
    public static function endHead()
    {
        return '</head><body>';
    }

    /**
     * @param string $link - css file
     * @return string - <link> for add css to page
     */
    public static function putCssLink($link)
    {
        $v = 2;
        return "<link rel='stylesheet' type='text/css' href='$link?$v' />";
    }

    /**
     * @param string $tag
     * @return string - add meta tag to page
     */
    public static function putMetaString($tag)
    {
        return "<meta $tag>";
    }
}