<?php


/**
 * @author: timbroder
 * Date: 4/11/16
 * @copyright 2015 Kidfund Inc
 */
class DummyModelEncrypting extends DummyModel
{
    protected $encrypts = [
        'phone',
        'cell'
    ];
}
