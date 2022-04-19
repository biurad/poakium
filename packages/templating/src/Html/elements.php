<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\UI\Html {

    /**
     * The `<canvas>` HTML element with either the canvas scripting API or
     * the WebGL API to draw graphics and animations.
     *
     * @param array<int,string>   $children   The Element children
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/canvas
     */
    function canvas($children, array $attributes = []): string
    {
        return createElement('canvas', $attributes + ['children' => $children]);
    }

    /**
     * The `<script>` HTML element is used to embed executable code or data.
     *
     * @param string $src         The internal/external scripts to apply
     * @param array<string,mixed> $attributes The attributes for script tag
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/script
     */
    function script(string $src, bool $external = true, array $attributes = []): string
    {
        return createElement('script', $attributes + [$external ? 'src' : 'children' => $src]);
    }

    /**
     * The `<noscript>` HTML element defines a section of HTML to be inserted if a script type
     * on the page is unsupported or if scripting is currently turned off in the browser.
     *
     * @param array<int,string>   $children   The Element children
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/noscript
     */
    function noscript($children, array $attributes = []): string
    {
        return createElement('noscript', $attributes + ['children' => $children]);
    }

    /**
     * The `<html>` HTML element represents the root (top-level element) of an HTML document.
     *
     * @param array<int,string>   $children   The Element children
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/html
     */
    function html(array $children, array $attributes = []): string
    {
        return createElement('!Doctype html', [], false) . createElement('html', $attributes + ['children' => $children]);
    }

    /**
     * The `<head>` HTML element contains machine-readable information (metadata) about the document.
     *
     * @param array<int,string>   $children   The Element children
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/head
     */
    function head(array $children, array $attributes = []): string
    {
        return createElement('head', $attributes + ['children' => $children]);
    }

    /**
     * The `<base>` HTML element specifies the base URL to use for all relative URLs in a document.
     *
     * This element must come before other elements with attribute values of URLs,
     * such as `<link>`’s href attribute.
     *
     * @param string              $href       The base URL to be used throughout the document for relative URLs
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/base
     */
    function base(string $href, array $attributes = []): string
    {
        return createElement('base', $attributes + ['target' => '__self', 'href' => $href], false);
    }

    /**
     * The `<title>` HTML element defines the document's title that is shown in a Browser's title bar.
     *
     * @param string              $content    The Document Title
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/title
     */
    function title(string $content, array $attributes = []): string
    {
        return createElement('title', $attributes + ['children' => $content]);
    }

    /**
     * The `<link>` HTML element specifies relationships between the current document and an external resource.
     *
     * @param string              $href       This attribute specifies the URL of the linked resource
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link
     */
    function link(string $href, array $attributes = []): string
    {
        return createElement('link', $attributes + ['href' => $href, 'rel' => 'stylesheet'], false);
    }

    /**
     * The `<meta>` HTML element represents metadata that cannot be represented
     * by other HTML meta-related elements.
     *
     * @param string              $content    the charset value if empty $attributes
     *                                        else passed as the content attribute of the (meta) tag
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/meta
     */
    function meta(string $content, array $attributes = []): string
    {
        return createElement('meta', $attributes + [empty($attributes) ? 'charset' : 'content' => $content], false);
    }

    /**
     * Import stylesheet or Use a css styling for the `<style>` HTML Element.
     *
     * @param string|array<string,string> $children   The styles/stylesheet to apply
     * @param array<string,mixed>         $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/style
     */
    function style($children, array $attributes = []): string
    {
        if (\is_array($children)) {
            $children = HtmlElement::cssFromArray($children);
        } elseif (\file_exists($children)) {
            $children = \file_get_contents($children);
        }

        return createElement('style', $attributes + ['children' => $children]);
    }

    /**
     * The `<body>` HTML element represents the content of an HTML document.
     *
     * @param array<int,string>   $children   The Element children
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/body
     */
    function body(array $children, array $attributes = []): string
    {
        return createElement('body', $attributes + ['children' => $children]);
    }

    /**
     * The `<address>` HTML element indicates that the enclosed HTML provides contact
     * information for a person or people, or for an organization.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/address
     */
    function address($children, array $attributes = []): string
    {
        return createElement('address', $attributes + ['children' => $children]);
    }

    /**
     * The `<article>` HTML element represents a self-contained composition in a
     * document, page, application, or site, which is intended to be independently
     * distributable or reusable (e.g., in syndication).
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/article
     */
    function article($children, array $attributes = []): string
    {
        return createElement('article', $attributes + ['children' => $children]);
    }

    /**
     * The `<aside>` HTML element represents a portion of a document whose content
     * is only indirectly related to the document's main content.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/aside
     */
    function aside($children, array $attributes = []): string
    {
        return createElement('aside', $attributes + ['children' => $children]);
    }

    /**
     * The `<header>` HTML element represents introductory content,
     * typically a group of introductory or navigational aids.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/header
     */
    function header($children, array $attributes = []): string
    {
        return createElement('header', $attributes + ['children' => $children]);
    }

    /**
     * The `<footer>` HTML element represents a footer for its nearest
     * sectioning content or sectioning root element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/footer
     */
    function footer($children, array $attributes = []): string
    {
        return createElement('footer', $attributes + ['children' => $children]);
    }

    /**
     * The `<h6>` HTML Section Heading element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements
     */
    function h6($children, array $attributes = []): string
    {
        return createElement('h6', $attributes + ['children' => $children]);
    }

    /**
     * The `<h5>` HTML Section Heading element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements
     */
    function h5($children, array $attributes = []): string
    {
        return createElement('h5', $attributes + ['children' => $children]);
    }

    /**
     * The `<h4>` HTML Section Heading element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements
     */
    function h4($children, array $attributes = []): string
    {
        return createElement('h4', $attributes + ['children' => $children]);
    }

    /**
     * The `<h3>` HTML Section Heading element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements
     */
    function h3($children, array $attributes = []): string
    {
        return createElement('h3', $attributes + ['children' => $children]);
    }

    /**
     * The `<h2>` HTML Section Heading element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements
     */
    function h2($children, array $attributes = []): string
    {
        return createElement('h2', $attributes + ['children' => $children]);
    }

    /**
     * The `<h1>` HTML Section Heading element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements
     */
    function h1($children, array $attributes = []): string
    {
        return createElement('h1', $attributes + ['children' => $children]);
    }

    /**
     * The `<nav>` HTML element represents a section of a page whose purpose is to provide
     * navigation links, either within the current document or to other documents.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/nav
     */
    function nav($children, array $attributes = []): string
    {
        return createElement('nav', $attributes + ['children' => $children]);
    }

    /**
     * The `<main>` HTML element represents the dominant content of the body of a document.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/main
     */
    function main($children, array $attributes = []): string
    {
        return createElement('main', $attributes + ['children' => $children]);
    }

    /**
     * The `<section>` HTML element represents a generic standalone section of a document,
     * which doesn't have a more specific semantic element to represent it.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/section
     */
    function section($children, array $attributes = []): string
    {
        return createElement('section', $attributes + ['children' => $children]);
    }

    /**
     * The `<blockquote>` HTML element indicates that the enclosed text is an extended quotation.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/blockquote
     */
    function blockquote($children, array $attributes = []): string
    {
        return createElement('blockquote', $attributes + ['children' => $children]);
    }

    /**
     * The `<dd>` HTML element provides the description, definition,
     * or value for the preceding term (dt) in a description list (dl).
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dd
     */
    function dd($children, array $attributes = []): string
    {
        return createElement('dd', $attributes + ['children' => $children]);
    }

    /**
     * The `<div>` HTML element is the generic container for flow content.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/div
     */
    function div($children, array $attributes = []): string
    {
        return createElement('div', $attributes + ['children' => $children]);
    }

    /**
     * The `<dl>` HTML element represents a description list.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dl
     */
    function dl($children, array $attributes = []): string
    {
        return createElement('dl', $attributes + ['children' => $children]);
    }

    /**
     * The `<dt>` HTML element specifies a term in a description or definition list,
     * and as such must be used inside a dl element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dt
     */
    function dt($children, array $attributes = []): string
    {
        return createElement('dt', $attributes + ['children' => $children]);
    }

    /**
     * The `<figcaption>` HTML element represents a caption or legend describing
     * the rest of the contents of its parent figure element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/figcaption
     */
    function figcaption($children, array $attributes = []): string
    {
        return createElement('figcaption', $attributes + ['children' => $children]);
    }

    /**
     * The `<figure>` HTML element represents self-contained content, potentially
     * with an optional caption, which is specified using the figcaption element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/figcaption
     */
    function figure($children, array $attributes = []): string
    {
        return createElement('figure', $attributes + ['children' => $children]);
    }

    /**
     * The `<hr>` HTML element represents a thematic break between paragraph-level elements.
     *
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/hr
     */
    function hr(array $attributes = []): string
    {
        return createElement('hr', $attributes, false);
    }

    /**
     * The `<li>` HTML element is used to represent an item in a list.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/li
     */
    function li($children, array $attributes = []): string
    {
        return createElement('li', $attributes + ['children' => $children]);
    }

    /**
     * The `<ol>` HTML element represents an ordered list of items — typically rendered as a numbered list.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/ol
     */
    function ol($children, array $attributes = []): string
    {
        return createElement('ol', $attributes + ['children' => $children]);
    }

    /**
     * The `<p>` HTML element represents a paragraph.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/p
     */
    function p($children, array $attributes = []): string
    {
        return createElement('p', $attributes + ['children' => $children]);
    }
    /**
     * The <pre> HTML element represents preformatted text which is to be presented exactly as written in the HTML file.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/pre
     */
    function pre($children, array $attributes = []): string
    {
        return createElement('pre', $attributes + ['children' => $children]);
    }

    /**
     * The `<ul>` HTML element represents an unordered list of items, typically rendered as a bulleted list.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/ul
     */
    function ul($children, array $attributes = []): string
    {
        return createElement('ul', $attributes + ['children' => $children]);
    }

    /**
     * The `<a>` HTML element (or anchor element), with its href attribute, creates a hyperlink.
     *
     * @param string                   $href       The URL that the hyperlink points to
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a
     */
    function a(string $href, $children = [], array $attributes = []): string
    {
        return createElement('a', $attributes + ['href' => $href, 'children' => $children]);
    }

    /**
     * The `<abbr>` HTML element represents an abbreviation or acronym;
     * the optional title attribute can provide an expansion or description for the abbreviation.
     *
     * @param string|array<int,string> $children   The Element children
     * @param string|null              $title      if present, must contain this full description
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/abbr
     */
    function abbr($children = [], string $title = null, array $attributes = []): string
    {
        return createElement('abbr', $attributes + ['title' => $title, 'children' => $children]);
    }

    /**
     * The `<b>` HTML element is used to draw the reader's attention to the element's contents,
     * which are not otherwise granted special importance.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/b
     */
    function b($children = [], array $attributes = []): string
    {
        return createElement('b', $attributes + ['children' => $children]);
    }

    /**
     * The `<bdi>` HTML element tells the browser's bidirectional algorithm to treat
     * the text it contains in isolation from its surrounding text.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/bdi
     */
    function bdi($children, array $attributes = []): string
    {
        return createElement('bdi', $attributes + ['children' => $children]);
    }

    /**
     * The `<bdi>` HTML element tells the browser's bidirectional algorithm to treat
     * the text it contains in isolation from its surrounding text.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/bdo
     */
    function bdo($children, array $attributes = []): string
    {
        return createElement('bdo', $attributes + ['children' => $children]);
    }

    /**
     * The `<br>` HTML element produces a line break in text (carriage-return).
     *
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/br
     */
    function br(array $attributes = []): string
    {
        return createElement('br', $attributes, true);
    }

    /**
     * The `<cite>` HTML element is used to describe a reference to a cited creative work,
     * and must include the title of that work.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/cite
     */
    function cite($children, array $attributes = []): string
    {
        return createElement('cite', $attributes + ['children' => $children]);
    }

    /**
     * The `<code>` HTML element displays its contents styled in a fashion intended to
     * indicate that the text is a short fragment of computer code.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/code
     */
    function code($children, array $attributes = []): string
    {
        return createElement('code', $attributes + ['children' => $children]);
    }

    /**
     * The `<code>` HTML element displays its contents styled in a fashion intended to
     * indicate that the text is a short fragment of computer code.
     *
     * @param string                   $value      specifies the machine-readable translation
     *                                             of the content of the element
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/data
     */
    function data(string $value, $children, array $attributes = []): string
    {
        $attributes['value'] = $value;

        return createElement('data', $attributes + ['children' => $children]);
    }

    /**
     * The `<dfn>` HTML element is used to indicate the term being defined
     * within the context of a definition phrase or sentence.
     *
     * @param string|array<int,string> $children   The Element children
     * @param string|null              $title      id preset, it must contain the term being
     *                                             defined and no other text
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dfn
     */
    function dfn($children, string $title = null, array $attributes = []): string
    {
        return createElement('dfn', $attributes + ['title' => $title, 'children' => $children]);
    }

    /**
     * The `<em>` HTML element marks text that has stress emphasis.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/em
     */
    function em($children, array $attributes = []): string
    {
        return createElement('em', $attributes + ['children' => $children]);
    }

    /**
     * The `<i>` HTML element represents a range of text that is set off from the normal
     * text for some reason, such as idiomatic text, technical terms, taxonomical designations, among others.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/i
     */
    function i($children, array $attributes = []): string
    {
        return createElement('i', $attributes + ['children' => $children]);
    }

    /**
     * The `<kbd>` HTML element represents a span of inline text denoting textual user
     * input from a keyboard, voice input, or any other text entry device.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/kbd
     */
    function kbd($children, array $attributes = []): string
    {
        return createElement('kbd', $attributes + ['children' => $children]);
    }

    /**
     * The `<mark>` HTML element represents text which is marked or highlighted for reference or
     * notation purposes, due to the marked passage's relevance or importance in the enclosing context.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/mark
     */
    function mark($children, array $attributes = []): string
    {
        return createElement('mark', $attributes + ['children' => $children]);
    }

    /**
     * The `<q>` HTML element indicates that the enclosed text is a short inline quotation.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/q
     */
    function q($children, array $attributes = []): string
    {
        return createElement('q', $attributes + ['children' => $children]);
    }
    /**
     * The  `<rp>` HTML element is used to provide fall-back parentheses for browsers
     * that do not support display of ruby annotations using the ruby element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/rp
     */
    function rp($children, array $attributes = []): string
    {
        return createElement('rp', $attributes + ['children' => $children]);
    }
    /**
     * The `<rt>` HTML element specifies the ruby text component of a ruby annotation,
     * which is used to provide pronunciation, translation, or transliteration
     * information for East Asian typography.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/rt
     */
    function rt($children, array $attributes = []): string
    {
        return createElement('rt', $attributes + ['children' => $children]);
    }
    /**
     * The `<ruby>` HTML element represents small annotations that are rendered above, below,
     * or next to base text, usually used for showing the pronunciation of East Asian characters.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/ruby
     */
    function ruby($children, array $attributes = []): string
    {
        return createElement('ruby', $attributes + ['children' => $children]);
    }
    /**
     * The `<s>` HTML element renders text with a strikethrough, or a line through it.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/s
     */
    function s($children, array $attributes = []): string
    {
        return createElement('s', $attributes + ['children' => $children]);
    }
    /**
     * The `<samp>` HTML element is used to enclose inline text which represents
     * sample (or quoted) output from a computer program.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/samp
     */
    function samp($children, array $attributes = []): string
    {
        return createElement('samp', $attributes + ['children' => $children]);
    }
    /**
     * The `<small>` HTML element represents side-comments and small print,
     * like copyright and legal text, independent of its styled presentation.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/small
     */
    function small($children, array $attributes = []): string
    {
        return createElement('small', $attributes + ['children' => $children]);
    }

    /**
     * The `<span>` HTML element is a generic inline container for phrasing content,
     * which does not inherently represent anything.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/span
     */
    function span($children, array $attributes = []): string
    {
        return createElement('span', $attributes + ['children' => $children]);
    }

    /**
     * The `<strong>` HTML element indicates that its contents have strong importance, seriousness, or urgency.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/strong
     */
    function strong($children, array $attributes = []): string
    {
        return createElement('strong', $attributes + ['children' => $children]);
    }

    /**
     * The `<sub>` HTML element specifies inline text which should be displayed as
     * subscript for solely typographical reasons.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/sub
     */
    function sub($children, array $attributes = []): string
    {
        return createElement('sub', $attributes + ['children' => $children]);
    }

    /**
     * The `<sup>` HTML element specifies inline text which is to be displayed as
     * superscript for solely typographical reasons.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/sup
     */
    function sup($children, array $attributes = []): string
    {
        return createElement('sub', $attributes + ['children' => $children]);
    }

    /**
     * The `<time>` HTML element represents a specific period in time.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/time
     */
    function time(string $datetime, $children, array $attributes = []): string
    {
        return createElement('time', $attributes + ['datetime' => $datetime, 'children' => $children]);
    }

    /**
     * The `<u>` HTML element represents an unarticulated annotation (Underline).
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/u
     */
    function u($children, array $attributes = []): string
    {
        return createElement('u', $attributes + ['children' => $children]);
    }

    /**
     * The `<var>` HTML element represents the name of a variable in a mathematical expression or a programming context.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/var
     */
    function _var($children, array $attributes = []): string
    {
        return createElement('var', $attributes + ['children' => $children]);
    }

    /**
     * The `<wbr>` HTML element represents a word break opportunity.
     *
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/wbr
     */
    function wbr(array $attributes = []): string
    {
        return createElement('wbr', $attributes, true);
    }

    /**
     * The `<area>` HTML element represents an image map area element.
     *
     * @param string              $shape      defines the values rect, which defines a rectangular region
     * @param string|null         $coord      the coords attribute details the coordinates of the shape attribute in
     *                                        size, shape, and placement of an area
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/area
     */
    function area(string $shape, string $coord = null, array $attributes = []): string
    {
        return createElement('area', $attributes + ['shape' => $shape, 'coord' => $coord], true);
    }

    /**
     * The `<audio>` HTML element represents an embed audio element.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/audio
     */
    function audio($children, array $attributes = []): string
    {
        return createElement('audio', $attributes + ['children' => $children]);
    }

    /**
     * The `<img>` HTML element represents an image embed element.
     *
     * @param string              $src        is required, and contains the path to the image
     * @param string              $alt        holds a text description of the image
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img
     */
    function img(string $src, string $alt = '', array $attributes = []): string
    {
        return createElement('img', $attributes + ['src' => $src, 'alt' => $alt], true);
    }

    /**
     * The `<map>` HTML element represents an image map element.
     *
     * @param string                   $name       gives the map a name so that it can be referenced
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/map
     */
    function map(string $name, $children, array $attributes = []): string
    {
        return createElement('map', $attributes + ['name' => $name, 'children' => $children]);
    }

    /**
     * The `<track>` HTML element represents an embed text track element.
     *
     * @param string              $src        Address of the track (.vtt file). Must be a valid URL
     * @param string              $kind       How the text track is meant to be used
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/track
     */
    function track(string $src, string $kind, array $attributes = []): string
    {
        return createElement('track', $attributes + ['default' => true, 'kind' => $kind, 'src' => $src], true);
    }

    /**
     * The `<video>` HTML element represents an image map element.
     *
     * @param string                   $src        The URL of the video to embed
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/video
     */
    function video(string $src, $children, array $attributes = []): string
    {
        return createElement('video', $attributes + ['src' => $src, 'children' => $children, 'autoplay' => 'true']);
    }

    /**
     * The `<embed>` HTML element represents external content at the specified point in the document.
     *
     * @param string              $src        The URL of the resource being embedded
     * @param string              $type       The MIME type to use to select the plug-in to instantiate
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/embed
     */
    function embed(string $src, string $type, array $attributes = []): string
    {
        return createElement('embed', $attributes + ['type' => $type, 'src' => $src], true);
    }

    /**
     * The `<iframe>` HTML element represents a nested browsing context,
     * embedding another HTML page into the current one.
     *
     * @param string              $src        The URL of the page to embed
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe
     */
    function iframe(string $src = 'about:blank', array $attributes = []): string
    {
        return createElement('iframe', $attributes + ['src' => $src]);
    }

    /**
     * The `<object>` HTML element represents an external resource treated as image.
     *
     * @param string                   $data       The address of the resource as a valid URL
     * @param string                   $type       The content type of the resource specified by data
     * @param string|array<int,string> $children   The Element children (only param, del and ins tags)
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/object
     */
    function object(string $data, string $type, $children, array $attributes = []): string
    {
        return createElement('object', $attributes + ['data' => $data, 'children' => $children, 'type' => $type]);
    }

    /**
     * The `<param>` HTML element defines parameters for an object element.
     *
     * @param string              $name       Name of the parameter
     * @param string              $value      Name of the parameter
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/param
     */
    function param(string $name, string $value, array $attributes = []): string
    {
        return createElement('param', $attributes + ['name' => $name, 'value' => $value], true);
    }

    /**
     * The `<picture>` HTML element contains zero or more `<source>` elements and one `<img>` element,
     * to offer alternative versions of an image for different display/device scenarios.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/picture
     */
    function picture($children, array $attributes = []): string
    {
        return createElement('picture', $attributes + ['children' => $children]);
    }

    /**
     * The `<source>` HTML element specifies multiple media resources for the `<picture>`,
     * the `<audio>` element, or the `<video>` element.
     *
     * If the $type parameter is a picture media supported attribute content,
     * The src attribute changes to srcset while type to media.
     *
     * @param string              $src        The URL of the resource
     * @param string              $type       The MIME media type of the resource, optionally with a codecs parameter
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/source
     */
    function source(string $src, string $type, array $attributes = []): string
    {
        $attributes += ('(' === @$type[0] ? ['srcset' => $src, 'media' => $type] : ['type' => $type, 'src' => $src]);

        return createElement('source', $attributes, true);
    }

    /**
     * The `<del>` HTML element represents a range of text that has been deleted from a document.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/del
     */
    function del($children, array $attributes = []): string
    {
        return createElement('del', $attributes + ['children' => $children]);
    }

    /**
     * The `<ins>` HTML element represents a range of text that has been added to a document.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/ins
     */
    function ins($children, array $attributes = []): string
    {
        $attributes += ['children' => $children];

        return createElement('ins', $attributes + ['children' => $children]);
    }

    /**
     * The `<caption>` HTML element specifies the caption (or title) of a table.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/caption
     */
    function caption($children, array $attributes = []): string
    {
        return createElement('caption', $attributes + ['children' => $children]);
    }

    /**
     * The `<col>` HTML element defines a column within a table and is used for
     * defining common semantics on all common cells.
     *
     * @param int                 $span       contains a positive integer indicating for columns
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/col
     */
    function col(int $span = 1, array $attributes = []): string
    {
        return createElement('col', $attributes + ['span' => (string) $span], true);
    }

    /**
     * The `<colgroup>` HTML element defines a group of columns within a table.
     *
     * @param int                      $span       contains a positive integer indicating for columns
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/colgroup
     */
    function colgroup($children, int $span = 1, array $attributes = []): string
    {
        return createElement('colgroup', $attributes + ['span' => (string) $span, 'children' => $children]);
    }

    /**
     * The `<table>` HTML element represents tabular data — that is, information presented in a
     * two-dimensional table comprised of rows and columns of cells containing data.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/table
     */
    function table($children, array $attributes = []): string
    {
        return createElement('table', $attributes + ['children' => $children]);
    }

    /**
     * The `<tbody>` HTML element encapsulates a set of table rows (`<tr>` elements),
     * indicating that they comprise the body of the table (`<table>`).
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/tbody
     */
    function tbody($children, array $attributes = []): string
    {
        return createElement('tbody', $attributes + ['children' => $children]);
    }

    /**
     * The `<td>` HTML element defines a cell of a table that contains data.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/td
     */
    function td($children, array $attributes = []): string
    {
        return createElement('td', $attributes + ['children' => $children]);
    }

    /**
     * The `<tfoot>` HTML element defines a set of rows summarizing the columns of the table.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/tfoot
     */
    function tfoot($children, array $attributes = []): string
    {
        return createElement('tfoot', $attributes + ['children' => $children]);
    }

    /**
     * The `<th>` HTML element defines a cell as header of a group of table cells.
     *
     * @param string                   $scope      Defines a cell as header of a group of table cells
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/th
     */
    function th(string $scope, $children, array $attributes = []): string
    {
        return createElement('th', $attributes + ['scope' => $scope, 'children' => $children]);
    }

    /**
     * The `<thead>` HTML element defines a set of rows defining the head of the columns of the table.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/thead
     */
    function thead($children, array $attributes = []): string
    {
        return createElement('thead', $attributes + ['children' => $children]);
    }

    /**
     * The `<tr>` HTML element defines a row of cells in a table.
     *
     * @param string|array<int,string> $children   The Element children
     * @param array<string,mixed>      $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/tr
     */
    function tr($children, array $attributes = []): string
    {
        return createElement('tr', $attributes + ['children' => $children]);
    }

    /**
     * Create a grouped.
     *
     * @param array<int,string|\Stringable> $children
     */
    function elements(array $children): string
    {
        return \implode('', $children);
    }

    /**
     * Renders content as html comment. (eg: `<!-- Hello World -->`).
     *
     * @param string|array<int,string> $content The content for rendering
     */
    function comment($content): string
    {
        if (\is_array($content)) {
            return \implode('', \array_map(__FUNCTION__, $content));
        }

        return '<!-- ' . $content . ' -->';
    }

    /**
     * Loop over an array of items.
     *
     * @param array<int|string,mixed> $array   The array to loop through
     * @param callable                $handler Call back function to run per iteration
     */
    function loop(array $array, callable $handler): string
    {
        $element = '';

        foreach ($array as $key => $value) {
            $element .= $handler($value, $key);
        }

        return $element;
    }

    /**
     * Create an HTML element.
     *
     * @param \Stringable|string|array<string,mixed> $attributes Attributes for the element
     */
    function createElement(string $tag, $attributes = [], bool $selfClosing = null): string
    {
        $html = '<' . $tag;

        if (\is_array($attributes)) {
            if (\is_array($children = $attributes['children'] ?? '')) {
                $children = \implode('', $children);
            }

            unset($attributes['children']);
            $html .= HtmlElement::renderAttributes($attributes);
        } elseif (\is_string($attributes) || $attributes instanceof \Stringable) {
            $children = (string) $attributes;
        }

        if (null !== $selfClosing) {
            return $html . ($selfClosing ? '/' : '') . '>';
        }

        return $html . '>' . ($children ?? null) . '</' . $tag . '>';
    }
}
