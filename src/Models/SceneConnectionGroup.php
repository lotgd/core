<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use LotGD\Core\Exceptions\ArgumentException;

/**
 *
 * @Entity
 * @Table(name="scene_connection_groups")
 */
class SceneConnectionGroup implements SceneConnectable
{
    /**
     * @Id
     * @ManyToOne(targetEntity="Scene", inversedBy="outgoingConnections", cascade={"persist"})
     * @JoinColumn(name="scene", referencedColumnName="id")
     */
    private ?Scene $scene = null;

    /**
     * @Id
     * @Column(type="string")
     */
    private string $name;

    /**
     * @Column(type="string", length=255)
     */
    private string $title;

    /**
     * SceneConnectionGroup constructor.
     * @param string $name Soft-identifier of the connection group, e.g. lotgd/core
     * @param string $title
     */
    public function __construct(string $name, string $title)
    {
        $this->name = $name;
        $this->title = $title;
    }

    /**
     * Returns the scene associated with this connection group.
     * @return Scene
     */
    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    /**
     * Sets the scene associated with this connection group.
     * @param Scene $scene
     */
    public function setScene(Scene $scene): void
    {
        $this->scene = $scene;
    }

    /**
     * Returns the name-identifier of this connection group.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name-identifier of this connection group.
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Returns the title of this connection group.
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of this connection group.
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @param SceneConnectable $connectable
     * @param int|null $directionality
     * @return SceneConnection
     * @throws ArgumentException
     */
    public function connect(SceneConnectable $connectable, int $directionality = null): SceneConnection
    {
        if ($directionality === null) {
            $connection = $this->scene->connect($connectable);
        } else {
            $connection = $this->scene->connect($connectable, $directionality);
        }

        $connection->setOutgoingConnectionGroupName($this->name);

        return $connection;
    }
}
