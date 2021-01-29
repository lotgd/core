<?php
declare(strict_types=1);

namespace LotGD\Core\Tools\Model;

use Doctrine\ORM\Mapping\Column;

trait UserAssignable
{
    /** @Column(type="boolean", options={"default"=true}) */
    protected bool $userAssignable = true;

    /**
     * Changes whether the template should be able to get manually assigned to a template or not.
     * @param bool $flag
     */
    public function setUserAssignable(bool $flag = true)
    {
        $this->userAssignable = $flag;
    }

    /**
     * @return bool
     */
    public function isUserAssignable(): bool
    {
        return $this->userAssignable;
    }
}