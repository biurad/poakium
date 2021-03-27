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

    use Biurad\UI\Renders\PhpHtmlRender;

    /**
     * Import/Use a script.
     *
     * @param string|array<int,string> $src        The internal/external scripts to apply
     * @param array<string,mixed>      $attributes The attributes for script tag
     *
     * @link https://www.w3.org/TR/html52/semantics-scripting.html#the-script-element
     */
    function script($src, array $attributes = []): string
    {
        if (\is_string($src)) {
            $attributes['src'] = $src;
        } else {
            $attributes['children'] = $src;
            $attributes += ['type' => 'text/javascript'];
        }

        return createElement('script', $attributes);
    }

    /**
     * NoScript Element.
     *
     * @link https://html.spec.whatwg.org/multipage/scripting.html#the-noscript-element
     */
    function noscript(string $children): string
    {
        return createElement('noscript', ['children' => $children]);
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
        $attributes['children'] = $children;

        return createElement('!Doctype html', [], false) . createElement('html', $attributes);
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
        $attributes['children'] = $children;

        return createElement('head', $attributes);
    }

    /**
     * The `<base>` HTML element specifies the base URL to use for all relative URLs in a document.
     *
     * This element must come before other elements with attribute values of URLs,
     * such as `<link>`’s href attribute.
     *
     * @param string $href                    The base URL to be used throughout the document for relative URLs
     * @param array<string,mixed> $attributes Attributes for the element
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/base
     */
    function base(string $href, array $attributes = []): string
    {
        $attributes += ['target' => '__self', 'href' => $href];

        return createElement('base', $attributes, false);
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
        $attributes['children'] = $content;

        return createElement('title', $attributes);
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
        $attributes += ['href' => $href, 'rel' => 'stylesheet'];

        return createElement('link', $attributes, false);
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
        $attributes[empty($attributes) ? 'charset' : 'content'] = $content;

        return createElement('meta', $attributes, false);
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

        $attributes['children'] = $children;
        $attributes += ['type' => 'text/javascript'];

        return createElement('style', $attributes);
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
        $attributes['children'] = $children;

        return createElement('body', $attributes);
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
        $attributes['children'] = $children;

        return createElement('address', $attributes);
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
        $attributes['children'] = $children;

        return createElement('article', $attributes);
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
        $attributes['children'] = $children;

        return createElement('aside', $attributes);
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
        $attributes['children'] = $children;

        return createElement('header', $attributes);
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
        $attributes['children'] = $children;

        return createElement('footer', $attributes);
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
        $attributes['children'] = $children;

        return createElement('h6', $attributes);
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
        $attributes['children'] = $children;

        return createElement('h5', $attributes);
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
        $attributes['children'] = $children;

        return createElement('h4', $attributes);
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
        $attributes['children'] = $children;

        return createElement('h3', $attributes);
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
        $attributes['children'] = $children;

        return createElement('h2', $attributes);
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
        $attributes['children'] = $children;

        return createElement('h1', $attributes);
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
        $attributes['children'] = $children;

        return createElement('nav', $attributes);
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
        $attributes['children'] = $children;

        return createElement('main', $attributes);
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
        $attributes['children'] = $children;

        return createElement('section', $attributes);
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
        $attributes['children'] = $children;

        return createElement('blockquote', $attributes);
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
        $attributes['children'] = $children;

        return createElement('dd', $attributes);
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
        $attributes['children'] = $children;

        return createElement('div', $attributes);
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
        $attributes['children'] = $children;

        return createElement('dl', $attributes);
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
        $attributes['children'] = $children;

        return createElement('dt', $attributes);
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
        $attributes['children'] = $children;

        return createElement('figcaption', $attributes);
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
        $attributes['children'] = $children;

        return createElement('figure', $attributes);
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
        $attributes['children'] = $children;

        return createElement('li', $attributes);
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
        $attributes['children'] = $children;

        return createElement('ol', $attributes);
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
        $attributes['children'] = $children;

        return createElement('p', $attributes);
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
        $attributes['children'] = $children;

        return createElement('pre', $attributes);
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
        $attributes['children'] = $children;

        return createElement('ul', $attributes);
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
        $attributes += ['href' => $href, 'children' => $children];

        return createElement('a', $attributes);
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
        if (null !== $title) {
            $attributes['title'] = $title;
        }

        $attributes['children'] = $children;

        return createElement('abbr', $attributes);
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
        $attributes['children'] = $children;

        return createElement('b', $attributes);
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
        $attributes['children'] = $children;

        return createElement('bdi', $attributes);
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
        $attributes['children'] = $children;

        return createElement('bdo', $attributes);
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
        $attributes['children'] = $children;

        return createElement('cite', $attributes);
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
        $attributes['children'] = $children;

        return createElement('code', $attributes);
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
        $attributes['children'] = $children;

        return createElement('data', $attributes);
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
        if (null !== $title) {
            $attributes['title'] = $title;
        }

        $attributes['children'] = $children;

        return createElement('dfn', $attributes);
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
        $attributes['children'] = $children;

        return createElement('em', $attributes);
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
        $attributes['children'] = $children;

        return createElement('i', $attributes);
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
        $attributes['children'] = $children;

        return createElement('kbd', $attributes);
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
        $attributes['children'] = $children;

        return createElement('mark', $attributes);
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
        $attributes['children'] = $children;

        return createElement('q', $attributes);
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
        $attributes['children'] = $children;

        return createElement('rp', $attributes);
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
        $attributes['children'] = $children;

        return createElement('rt', $attributes);
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
        $attributes['children'] = $children;

        return createElement('ruby', $attributes);
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
        $attributes['children'] = $children;

        return createElement('s', $attributes);
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
        $attributes['children'] = $children;

        return createElement('samp', $attributes);
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
        $attributes['children'] = $children;

        return createElement('small', $attributes);
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
        $attributes['children'] = $children;

        return createElement('span', $attributes);
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
        $attributes['children'] = $children;

        return createElement('strong', $attributes);
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
        $attributes['children'] = $children;

        return createElement('sub', $attributes);
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
        $attributes['children'] = $children;

        return createElement('sub', $attributes);
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
        $attributes['datetime'] = $datetime;
        $attributes['children'] = $children;

        return createElement('time', $attributes);
    }

    /**
     * Create a grouped
     *
     * @param array $children
     *
     * @return string
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
     * @param array<string,mixed> $attributes Attributes for the element
     */
    function createElement(string $tag, $attributes = [], bool $selfClosing = null): string
    {
        $html = '<' . $tag;

        if (!empty($attributes)) {
            if (\is_string($attributes)) {
                $attributes = ['children' => $attributes];
            }

            $children = $attributes['children'] ?? '';
            unset($attributes['children']);

            $html .= HtmlElement::renderAttributes($attributes);
        }

        if (null !== $selfClosing) {
            return $html . ($selfClosing ? '/' : '') . '>';
        }

        if (isset($children)) {
            $children = (\is_array($children) ? \implode('', $children) : $children);
        }

        return $html . '>' . ($children ?? null) . '</' . $tag . '>';
    }
}
