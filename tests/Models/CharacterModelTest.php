<?php

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Tests\ModelTestCase;

use Doctrine\ORM\Mapping as ORM;

/**
 * Description of CharacterModelTest
 *
 * @author Basilius Sauter
 */
class CharacterModelTest extends ModelTestCase {
    /** @var array */
    protected $entities = [Character::class];
            
    public function testCreationQuery() {
        $queryBuilder = $this->_em->getRepository(Character::class)->find(1);
        
        $character = new Character();
        $character->setName("Test");
    }
}
