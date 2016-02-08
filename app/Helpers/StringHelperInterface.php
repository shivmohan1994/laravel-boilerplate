<?php namespace App\Helpers;

interface StringHelperInterface
{

    /**
     * @param  string $input
     * @return string
     */
    public function camel2Snake($input);

    /**
     * @param  string $singular
     * @return string mixed
     */
    public function pluralize($singular);

}
