<?php

trait BaseSetting
{
    /** @Id @Column(type="string", length=255) */
    private $settingName;
    /** @Column(type="text") */
    private $settingValue;
}