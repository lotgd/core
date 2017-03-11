<?php
declare(strict_types=1);

namespace LotGD\Core\Models;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 *
 * @Entity
 * @Table(name="scene_connections")
 */
class SceneConnection
{
    /**
     * @Id
     * @ManyToOne(targetEntity="Scene")
     * @JoinColumn(name="outgoingScene", referencedColumnName="id")
     */
    private $outgoingScene;

    /**
     * @Id
     * @ManyToOne(targetEntity="Scene")
     * @JoinColumn(name="incomingScene", referencedColumnName="id")
     */
    private $incomingScene;

    /**
     * @Column(type="integer", options={"default":0})
     */
    private $directionality = 0;

    /**
     * @Column(type="string", nullable=True)
     */
    private $outgoingConnectionGroupName;

    /**
     * @Column(type="string", nullable=True)
     */
    private $incomingConnectionGroupName;

    /**
     *
     * @param \LotGD\Core\Models\Scene $outgoing
     * @param \LotGD\Core\Models\Scene $incoming
     * @param int $directionality
     */
    public function __construct(
        Scene $outgoing,
        Scene $incoming,
        int $directionality
    ) {
        $this->outgoingScene = $outgoing;
        $this->incomingScene = $incoming;
        $this->directionality = $directionality;
    }

    /**
     * Sets the connection group name identifier of the outgoing connection.
     * @param null|string $name The identifier name of the outgoing connection group.
     */
    public function setOutgoingConnectionGroupName(?string $name): void
    {
        $this->outgoingConnectionGroupName = $name;
    }

    /**
     * Returns the connection from name identifier of the outgoing connection.
     * @return null|string
     */
    public function getOutgoingConnectionGroupName(): ?string
    {
        return $this->outgoingConnectionGroupName;
    }

    /**
     * Returns the outgoing Scene of this connection.
     * @return Scene
     */
    public function getOutgoingScene(): Scene
    {
        return $this->outgoingScene;
    }

    /**
     * Sets the connection group name identifier of the incoming connection.
     * @param null|string $name The identifier name of the incoming connection group.
     */
    public function setIncomingConnectionGroupName(?string $name)
    {
        $this->incomingConnectionGroupName = $name;
    }

    /**
     * Returns the connection group name identifier of the incoming connection.
     * @return null|string
     */
    public function getIncomingConnectionGroupName(): ?string
    {
        return $this->incomingConnectionGroupName;
    }

    /**
     * Returns the incoming Scene of this connection.
     * @return Scene
     */
    public function getIncomingScene(): Scene
    {
        return $this->incomingScene;
    }

    /**
     * Returns if the directionality of this entity is as given as the first parameter.
     * @param int $directionality
     * @return bool
     */
    public function isDirectionality(int $directionality): bool
    {
        if ($this->directionality === $directionality) {
            return true;
        } else {
            return false;
        }
    }
}
