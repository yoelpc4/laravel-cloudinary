<?php

namespace Yoelpc4\LaravelCloudinary\Concerns;

interface ConcernAware
{
    /**
     * Handle concern process
     *
     * @return $this;
     */
    public function handle();

    /**
     * Get concern value
     *
     * @return mixed
     */
    public function value();
}
