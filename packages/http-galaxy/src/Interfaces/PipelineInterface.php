<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
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

namespace BiuradPHP\Http\Interfaces;

use Closure;
use SplDoublyLinkedList;

interface PipelineInterface
{
    /**
     * Set the traveler object being sent on the pipeline.
     *
     * @param mixed $traveler
     *
     * @return $this
     */
    public function send($traveler);

    /**
     * Set the stops of the pipeline.
     *
     * @param array|dynamic|SplDoublyLinkedList $stops
     *
     * @return $this
     */
    public function through($stops);

    /**
     * Set the method to call on the stops.
     *
     * @param string $method
     *
     * @return $this
     */
    public function via($method);

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure $destination
     *
     * @return $this|mixed
     */
    public function then(Closure $destination);

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn();
}
