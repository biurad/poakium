<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rade\DI\Extensions;

return [
    [
        [Extensions\CoreExtension::class, [__DIR__ . '/../']],
        //Extensions\Symfony\EventDispatcherExtension::class,
        //Extensions\Symfony\CacheExtension::class,
        //Extensions\AnnotationExtension::class,
        //Extensions\TemplateExtension::class,
    ],
    [
        //'config' => [
        //    'locale' => 'en',
        //    'paths' => ['%project_dir%/resources/config'],
        //],
        //'core' => [
        //    //'var_path' => '%project_dir%/var',
        //    //'events_dispatcher' => Symfony\Component\EventDispatcher\EventDispatcher::class,
        //],
        //'cache' => [
        //    'directory' => '%project.var_dir%/cache', // Recommended to be uncomment to use symfony's cache
        //],
        //'annotation' => [
        //    'resources' => ['%project_dir%/app/Handlers'],
        //],
        //'templating' => [
        //    'cache_dir' => '%project.var_dir%/views',
        //    'paths' => '%project_dir%/resources/templates',
        //    'renders' => [
        //        Biurad\UI\Renders\PhpNativeRender::class => ['php', 'phtml'],
        //        Biurad\UI\Renders\LatteRender::class,
        //    ],
        //],
        'http_galaxy' => [
            //'cookie' => true,
            //'session' => [
            //    'enabled' => true,
            //    'save_path' => '%project.var_dir%/sessions',
            //],
            'headers' => [
                'response' => [
                    'Transfer-Encoding' => 'gzip, deflate', // A work around header for tracy debugger
                ],
            ],
        ],
        //'routing' => [
        //    //'cache' => '%project.var_dir%/app/load_CachedRoutes.php',
        //    'pipes' => [
        //        //Biurad\Http\Middlewares\ContentTypeOptionsMiddleware::class,
        //        //Biurad\Http\Middlewares\ContentLengthMiddleware::class,
        //    ],
        //],
    ],
];
