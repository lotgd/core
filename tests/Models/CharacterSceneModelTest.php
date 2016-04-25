<?php
declare(strict_types=1);

namespace LotGD\Core\Tests\Models;

use LotGD\Core\Models\Character;
use LotGD\Core\Models\CharacterScene;
use LotGD\Core\Models\Scene;
use LotGD\Core\Tests\ModelTestCase;

/**
 * Tests the management of CharacterScenes
 */
class CharacterSceneModelTest extends ModelTestCase
{
    /** @var string default data set */
    protected $dataset = "characterScenes";
    
    public function testGetters() {
        $em = $this->getEntityManager();
        
        // Test character with a characterScene
        $testCharacter = $em->getRepository(Character::class)->find(2);
        $this->assertSame(2, $testCharacter->getId());
        $characterScene = $testCharacter->getCharacterScene();
        
        $this->assertInstanceOf(CharacterScene::class, $characterScene);
        $this->assertSame("The Village", $characterScene->getTitle());
        $this->assertSame("This is the village.", $characterScene->getDescription());
        
        // Test character without a characterScene
        $testCharacter = $em->getRepository(Character::class)->find(1);
        $this->assertSame(1, $testCharacter->getId());
        $characterScene = $testCharacter->getCharacterScene();
        
        $this->assertInstanceOf(CharacterScene::class, $characterScene);

        $em->flush();
    }
    
    // Tests if a scene can be changed correctly.
    public function testSceneChange() {
        $em = $this->getEntityManager();
        
        $testCharacters = [
            $em->getRepository(Character::class)->find(1),
            $em->getRepository(Character::class)->find(2)
        ];
        
        $testScenes = [
            $em->getRepository(Scene::class)->find(1),
            $em->getRepository(Scene::class)->find(2),
        ];
        
        $this->assertSame("{No scene set}", $testCharacters[0]->getCharacterScene()->getTitle());
        $this->assertSame("The Village", $testCharacters[1]->getCharacterScene()->getTitle());
        
        $testCharacters[0]->getCharacterScene()->changeFromScene($testScenes[0]);
        $testCharacters[1]->getCharacterScene()->changeFromScene($testScenes[1]);
        
        $this->assertSame("The Village", $testCharacters[0]->getCharacterScene()->getTitle());
        $this->assertSame("The Forest", $testCharacters[1]->getCharacterScene()->getTitle());
        
        $em->flush();
    }
}
