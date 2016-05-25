<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Collections\Collection;

use LotGD\Core\Models\Buff;
use LotGD\Core\Models\Character;

/**
 * Description of BuffList
 */
class BuffList
{
    private $buffs;
    
    public function __construct(Collection $buffs) {
        $this->buffs = $buffs;
    }
}
