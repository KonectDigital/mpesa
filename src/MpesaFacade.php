<?php

namespace konectdigital\Mpesa;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Konectdigital\Mpesa\Skeleton\SkeletonClass
 */
class MpesaFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mpesa';
    }
}
