<?php

namespace LotGD\Core\Models;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\SceneBasics;

/**
 * A CharacterScene is the current Scene a character is experiencing with
 * all changes from modules included.
 * @Entity
 * @Table(name="character_scene")
 */
class CharacterScene {
    use Creator;
    use SceneBasics;
    
    /** @Id @OneToOne(targetEntity="Character") */
    private $owner;
    
    /** @var array */
    private static $fillable = [
        "owner"
    ];
    
    /**
     * Returns the owner
     * @return \LotGD\Core\Models\Character
     */
    public function getOwner(): Character
    {
        return $this->owner;
    }
    
    /**
     * Sets the owner
     * @param \LotGD\Core\Models\Character $owner
     */
    public function setOwner(Character $owner)
    {
        $this->owner = $owner;
    }
    
    /**
     * Copies the static data from a scene to a characterScene entity
     * @param \LotGD\Core\Models\Scene $scene
     */
    public function changeFromScene(Scene $scene) {
        $this->setTitle($scene->getTitle());
        $this->setDescription($scene->getDescription());
    }
}
