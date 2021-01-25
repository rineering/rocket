<?php

namespace html\game;

use html\helper\HtmlHelper;

/**
 * Class Page
 * @package html\game
 */
class Page
{
    /** @var string title of page */
    public $pagetitle = 'Страница';

    /** @var array of css files */
    public $css = [];

    /** @var array of meta tags */
    public $metatags = [];

    /**
     * Установить title страницы
     * @param string $title - title
     */
    public function setTitle($title = '')
    {
        if (is_string($title) && strlen($title)) {
            $this->pagetitle = $title;
        }
    }

    /**
     * Добавляет ссылки на css для страницы
     * @param mixed $add - файлы к добавлению
     */
    public function addCss($add)
    {
        $add = is_array($add) ? $add : (is_string($add) ? [$add] : []);
        foreach ($add as $filepath) {
            if (file_exists($filepath)) {
                $this->css[] = $filepath;
            }
        }
    }

    /**
     * Добавляет мета теги для страницы
     * @param mixed $meta
     */
    public function addMeta($meta)
    {
        $meta = is_array($meta) ? $meta : [$meta];
        foreach ($meta as $str) {
            if (is_string($str) && strlen($str)) {
                $temp = explode(' ', $str);
                $alreadyExist = false;
                foreach ($this->metatags as $metatag) {
                    $alreadyExist = isset($temp[1])
                        ? (stripos($metatag, $temp[0]) !== false || stripos($metatag, $temp[1]) !== false)
                        : stripos($metatag, $temp[0]) !== false;
                }
                if (!$alreadyExist) {
                    $this->metatags[] = $str;
                }
            }
        }
    }

    /**
     * Опустошает массив метатегов для страницы
     */
    public function deleteAllMeta()
    {
        $this->metatags = [];
    }

    /**
     * Вывод шапки страницы
     * @return string - html начало страницы
     */
    public function getBeforeContent()
    {
        $links = $metas = '';
        foreach ($this->css as $link) {
            $links .= HtmlHelper::putCssLink($link) . "\n";
        }
        foreach ($this->metatags as $tag) {
            $metas .= HtmlHelper::putMetaString($tag) . "\n";
        }
        return HtmlHelper::startHtml()
            . HtmlHelper::startHead($this->pagetitle)
            . $metas
            . $links
            . HtmlHelper::endHead();
    }
}