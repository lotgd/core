<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

use LotGD\Core\Tools\Model\Creator;
use LotGD\Core\Tools\Model\Deletor;

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
     * @JoinColumn(name="scene_id", referencedColumnName="id")
     */
    private $scene;

    /**
     * @Id
     * @Column(type="string")
     */
    private $name;

    /**
     * @Column(type="string", length=255)
     */
    private $title;

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
     * @return \LotGD\Core\Models\Scene
     */
    public function getScene(): ?Scene
    {
        return $this->scene;
    }

    /**
     * Sets the scene associated with this connection group.
     * @param \LotGD\Core\Models\Scene $scene
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
     * @inheritDoc
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
