<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Exceptions\ArgumentException;
use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

/**
 * Represents a permission.
 *
 * @Entity()
 * @Table(name="permissions")
 */
class Permission implements CreateableInterface
{
    use Creator;
    use Deletor;
    
    /** @Id @Column(type="string"); */
    private $id;
    /** @Column(type="string") */
    private $library;
    /** @Column(type="string") */
    private $name;
    
    static $fillable = [
        "id",
        "library",
        "name"
    ];
    
    /**
     * Returns the id of this entity.
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Sets this entity's id if it's not set yet.
     * @param string $id
     * @throws ArgumentException
     */
    public function setId(string $id)
    {
        if (empty($this->id)) {
            $this->id = $id;
        }
        else {
            throw new ArgumentException("Cannot reset id.");
        }
    }
    
    /**
     * Returns the library this permission belongs to.
     * @return string
     */
    public function getLibrary(): string
    {
        return $this->library;
    }
    
    /**
     * Sets the library this permission belongs to.
     * @param string $library
     */
    public function setLibrary(string $library)
    {
        $this->library = $library;
    }
    
    /**
     * Gets this entity's human readable name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Sets this entity's human readable name.
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }
}
