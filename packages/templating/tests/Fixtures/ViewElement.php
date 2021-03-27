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

namespace Biurad\UI\Tests\Fixtures;

use Biurad\UI\Html;
use Biurad\UI\Html\HtmlElement;

$html = Html\html([
    Html\head([
        Html\title('Biurad World'),
        Html\meta('UTF-8'),
        Html\style([
            '.home' => 'background: #fefefe;',
            'article div p' => ['font-size' => '1.2rem'],
            '@media only screen and (max-width: 500px)' => [
                'p > button#btn' => 'border: 1px solid grey;',
                'ul li a' => [
                    'font-size' => '22px',
                    'padding-top' => '20px',
                ],
            ],
        ]),
    ]),
    Html\body([
        Html\h1('Hello World'),
        //Html\br([Html\condition('if', '$headLine ?? \'ggg\' . $__dsd')]),
        Html\hr(/* [Html\condition('else')] */),
        Html\comment('This is the main content'),
        Html\div([
            Html\a('biurad.com', [], ['target' => '__self']),
            Html\p('Welcome to Biurad Templating'),
        ]),
        //Html\expr(
        //    Html\div(Html\span('Another content to display.')),
        //    [Html\condition('if', '\'HelloWorld\' === :ucfirst|\trim(%s, **_\n_) << ($hello)')]
        //),
        Html\ul([
            //Html\li(
            //    [
            //        Html\expr('', [Html\conditions(['if' => '$item->number is even', 'break'])], true),
            //        Html\expr('
            //        {% if ($hello is defined): %}
            //            {% echo =($item->name)= %}
            //        {% endif; %}
            //        ', [Html\condition('literal')]),
            //    ],
            //    [Html\conditions(['foreach' => '$items as $item'])]
            //),
            Html\li('Hello World'),
            Html\li('Hi World'),
        ]),
        //Html\expr('', [Html\condition('import', 'includes.input_base')], true),
        //Html\expr(
        //    Html\span('Item #{% echo $i %}', [Html\condition('literal')]),
        //    [Html\condition('for', '$i = 0; $i < 10; $i++')]
        //),
        Html\loop(['Train', 'Plane', 'Car', 'Differently'], function (string $value, int $key) {
            if (3 !== $key) {
                $value = 'By ' . $value;
            }

            return Html\span('Traveled ' . $value) . Html\br();
        }),
        //Html\expr([
        //    Html\span('By Train', [Html\conditions(['case' => '\'train\'', 'break'])]),
        //    Html\span('By Plane', [Html\condition('case', '\'plane\'')]),
        //    Html\span('By Car', [Html\condition('case', '\'car\'')]),
        //    Html\span('Differently', [Html\condition('default')]),
        //], [
            //Html\condition('if', '$hello, $world is defined and $jojo is true'),
            //Html\conditions(['switch' => '$transport.name.3.', 'if' => '$jojo is >=6']),
            //Html\condition('switch', '$transport.name.3.'),
            //Html\condition('if', '$jojo is >=6'),
        //]),
    ], ['style' => ['margin' => '0px'], /* 'php:if' => '$foo ??= 34 && $do is not empty' */]),
], ['lang' => 'en-us']);

return HtmlElement::renderHtml($html);
