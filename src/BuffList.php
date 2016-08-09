<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};

use LotGD\Core\Exceptions\{
    ArgumentException,
    BuffListAlreadyActivatedException
};
use LotGD\Core\Models\{
    Buff,
    Character,
    FighterInterface,
    BattleEvents\BuffMessageEvent,
    BattleEvents\DamageLifetapEvent,
    BattleEvents\DamageReflectionEvent,
    BattleEvents\RegenerationBuffEvent,
    BattleEvents\MinionDamageEvent
};


/**
 * Description of BuffList
 */
class BuffList
{
    protected $buffs;
    protected $buffsBySlot;
    protected $activeBuffs = [];
    /** @var Doctrine\Common\Collections\ArrayCollection */
    protected $usedBuffs;

    /** @var boolean True of the modifiers have already been calculated */
    protected $modifiersCalculated = false;
    /** @var boolean True if the badguy is invulnurable */
    protected $badguyInvulnurable = false;
    /** @var float */
    protected $badguyDamageModifier = 1.;
    /** @var float */
    protected $badguyAttackModifier = 1.;
    /** @var float */
    protected $badguyDefenseModifier = 1.;
    /** @var boolean True if the goodguy is invulnurable */
    protected $goodguyInvulnurable = false;
    /** @var float */
    protected $goodguyDamageModifier = 1.;
    /** @var float */
    protected $goodguyAttackModifier = 1.;
    /** @var float */
    protected $goodguyDefenseModifier = 1.;

    protected $events;
    protected $loaded = false;

    /**
     * Initiates some variables
     * @param Collection $buffs
     */
    public function __construct(Collection $buffs)
    {
        $this->buffs = $buffs;
        $this->events = new ArrayCollection();
        $this->usedBuffs = new ArrayCollection();
    }

    /**
     * Loads all buffs (since it's a lazy correlation)
     */
    public function loadBuffs()
    {
        if ($this->loaded === false) {
            foreach ($this->buffs as $buff) {
                $this->buffsBySlot[$buff->getSlot()] = $buff;
            }
        }
    }

    /**
     * Returns true if the given buff has already been used this round.
     * @param Buff $buff
     * @return bool
     */
    protected function hasBuffBeenUsed(Buff $buff): bool
    {
        if ($this->usedBuffs->contains($buff)) {
            $used = true;
        } else {
            $used = false;
        }

        return $used;
    }

    /**
     * Marks the given buff as used
     * @param Buff $buff
     */
    protected function useBuff(Buff $buff)
    {
        $this->usedBuffs->add($buff);
    }

    /**
     * Returns the buff's start or round message
     * @param Buff $buff
     * @return string
     */
    protected function getBuffMessage(Buff $buff): string
    {
        $return = "";
        $used = $this->hasBuffBeenUsed($buff);
        if ($buff->hasBeenStarted() === false && $used === false) {
            $return = $buff->getStartMessage();
            $buff->setHasBeenStarted();
        } elseif ($used === false) {
            $return = $buff->getRoundMessage();
        }

        return $return;
    }

    /**
     * Resets the buff usage for a new round
     */
    public function resetBuffUsage()
    {
        $this->activeBuffs = [];
        $this->usedBuffs = new ArrayCollection();
        $this->modifiersCalculated = false;
    }

    /**
     * Returns whether any buffs are in use.
     */
    public function hasBuffsInUse(): bool
    {
        return count($this->usedBuffs) > 0 ? true : false;
    }

    /**
     * Activates all buffs that activate upon the given activation parameter.
     * @param int $activation
     * @return Collection
     * @throws ArgumentException
     * @throws BuffListAlreadyActivatedException
     */
    public function activate(int $activation): Collection
    {
        if ($activation%2 !== 0 && $activation !== 1) {
            throw new ArgumentException("You can only activate one activation type at a time.");
        }

        if (!empty($this->activeBuffs[$activation])) {
            throw new BuffListAlreadyActivatedException("You can activate the buff list for the given activation step only once.");
        }

        $this->activeBuffs[$activation] = new ArrayCollection();
        $activationEvents = new ArrayCollection();

        foreach ($this->iterateBuffList() as $buff) {
            // Continue to next buff if the activation is not in this round.
            if ($buff->getsActivatedAt($activation) === false) {
                continue;
            }

            $this->activeBuffs[$activation]->add($buff);

            // Returns start or roundMessage if the buff has not been used yet.
            $buffMessage = $this->getBuffMessage($buff);
            if ($buffMessage !== "") {
                $activationEvents->add(new BuffMessageEvent($buffMessage));
            }

            // Needs to come at the end
            if ($this->hasBuffBeenUsed($buff) === false) {
                $this->useBuff($buff);
            }
        }

        return $activationEvents;
    }

    /**
     * Decreases the rounds left on all used buffs
     * @return Collection A Collection containing expire messages (if there are any)
     */
    public function expireOneRound(): Collection
    {
        /* @var $endEvents Collection */
        $endEvents = new ArrayCollection();

        foreach ($this->usedBuffs as $buff) {
            /* @var $roundsLeft int */
            $roundsLeft = $buff->getRounds() - 1;
            $buff->setRounds($roundsLeft);

            if ($roundsLeft === 0) {
                /* @var $endMessage string */
                $endMessage = $buff->getEndMessage();

                if ($endMessage !== "") {
                    $endEvents->add(new BuffMessageEvent($endMessage));
                }

                $this->remove($buff);
            }
        }

        return $endEvents;
    }

    /**
     * Removes a buff from the buff list.
     * @param Buff $buff
     */
    public function remove(Buff $buff)
    {
        unset($this->buffsBySlot[$buff->getSlot()]);
        $this->buffs->removeElement($buff);
        $this->usedBuffs->removeElement($buff);
    }

    /**
     * Adds a buff to the buff list, occupying the slot.
     * @param Buff $buff
     * @throws BuffSlotOccupiedException if the slot is already occupied. Use renew instead.
     */
    public function add(Buff $buff)
    {
        $this->loadBuffs();
        $slot = $buff->getSlot();

        if (isset($this->buffsBySlot[$buff->getSlot()])) {
            throw new BuffSlotOccupiedException("The slot {$slot} is already occupied.");
        }

        $this->buffs->add($buff);
        $this->buffsBySlot[$buff->getSlot()] = $buff;
    }

    /**
     * Renews a buff.
     * @param Buff $buff
     */
    public function renew(Buff $buff)
    {
        $this->loadBuffs();
        $slot = $buff->getSlot();

        if (isset($this->buffsBySlot[$buff->getSlot()])) {
            $this->buffs->removeElement($buff);
        }

        $this->buffs->add($buff);
        $this->buffsBySlot[$buff->getSlot()] = $buff;
    }

    /**
     * Calculates all total modifiers
     * @return type
     */
    protected function calculateModifiers()
    {
        if ($this->modifiersCalculated === true) {
            return;
        }

        $this->badguyAttackModifier = 1.;
        $this->badguyDamageModifier = 1.;
        $this->badguyDefenseModifier = 1.;
        $this->badguyInvulnurable = false;
        $this->goodguyAttackModifier = 1.;
        $this->goodguyDamageModifier = 1.;
        $this->goodguyDefenseModifier = 1.;
        $this->goodguyInvulnurable = false;

        /* @var $buff \LotGD\Core\Model\Buff */
        foreach ($this->iterateBuffList() as $buff) {
            $this->badguyAttackModifier *= $buff->getBadguyAttackModifier();
            $this->badguyDefenseModifier *= $buff->getBadguyDefenseModifier();
            $this->badguyDamageModifier *= $buff->getBadguyDamageModifier();
            $this->badguyInvulnurable = $this->badguyInvulnurable || $buff->badguyIsInvulnurable();
            $this->goodguyAttackModifier *= $buff->getGoodguyAttackModifier();
            $this->goodguyDefenseModifier *= $buff->getGoodguyDefenseModifier();
            $this->goodguyDamageModifier *= $buff->getGoodguyDamageModifier();
            $this->goodguyInvulnurable = $this->goodguyInvulnurable || $buff->goodguyIsInvulnurable();
        }
    }

    /**
     * Iterates over every buff that gets activated at one point during a round.
     * @return Generator|\LotGD\Core\Model\Buff[]
     */
    protected function iterateBuffList()
    {
        foreach ($this->buffs as $buff) {
            // Only look at buffs that are activated in battle.
            if ($buff->getsActivatedAt(Buff::ACTIVATE_NONE)) {
                continue;
            } else {
                yield $buff;
            }
        }
    }

    /**
     * Returns the badguy attack modifier calculated over the whole bufflist
     * @return float
     */
    public function getBadguyAttackModifier(): float
    {
        $this->calculateModifiers();
        return $this->badguyAttackModifier;
    }

     /**
     * Returns the badguy defense modifier calculated over the whole bufflist
     * @return float
     */
    public function getBadguyDefenseModifier(): float
    {
        $this->calculateModifiers();
        return $this->badguyDefenseModifier;
    }

    /**
     * Returns the badguy damage modifier calculated over the whole bufflist
     * @return float
     */
    public function getBadguyDamageModifier(): float
    {
        $this->calculateModifiers();
        return $this->badguyDamageModifier;
    }

    /**
     * Returns true if the badguy is invulnurable
     * @return bool
     */
    public function badguyIsInvulnurable(): bool
    {
        $this->calculateModifiers();
        return $this->badguyInvulnurable;
    }

    /**
     * Returns the badguy attack modifier calculated over the whole bufflist
     * @return float
     */
    public function getGoodguyAttackModifier(): float
    {
        $this->calculateModifiers();
        return $this->goodguyAttackModifier;
    }

     /**
     * Returns the badguy defense modifier calculated over the whole bufflist
     * @return float
     */
    public function getGoodguyDefenseModifier(): float
    {
        $this->calculateModifiers();
        return $this->goodguyDefenseModifier;
    }

     /**
     * Returns the badguy damage modifier calculated over the whole bufflist
     * @return float
     */
    public function getGoodguyDamageModifier(): float
    {
        $this->calculateModifiers();
        return $this->goodguyDamageModifier;
    }

    /**
     * Returns true if the goodguy is invulnurable
     * @return bool
     */
    public function goodguyIsInvulnurable(): bool
    {
        $this->calculateModifiers();
        return $this->goodguyInvulnurable;
    }

    /**
     * Processes buffs that do direct damage or regeneration
     * @param int $activation
     * @param \LotGD\Core\Game $game
     * @param FighterInterface $goodguy
     * @param FighterInterface $badguy
     * @return Collection
     */
    public function processDirectBuffs(
        int $activation,
        Game $game,
        FighterInterface $goodguy,
        FighterInterface $badguy
    ): Collection {
        $events = [];

        foreach ($this->activeBuffs[$activation] as $buff) {
            // Add good guy regeneration
            if ($buff->getGoodguyRegeneration() !== 0) {
                $events[] = new RegenerationBuffEvent(
                    $goodguy,
                    $buff->getGoodguyRegeneration(),
                    $buff->getEffectSucceedsMessage(),
                    $buff->getNoEffectMessage()
                );
            }

            // Add bad guy regeneration
            if ($buff->getBadguyRegeneration() !== 0) {
                $events[] = new RegenerationBuffEvent(
                    $badguy,
                    $buff->getGoodguyRegeneration(),
                    $buff->getEffectSucceedsMessage(),
                    $buff->getNoEffectMessage()
                );
            }

            // Minion buff
            if ($buff->getNumberOfMinions() > 0) {
                /* @var $n int */
                $n = $buff->getNumberOfMinions();
                /* @var $attacksOne bool */
                $attacksOne = ($buff->getMinionMinGoodguyDamage() || $buff->getMinionMaxGoodguyDamage() !== 0)
                    || ($buff->getMinionMinBadguyDamage() || $buff->getMinionMaxBadguyDamage() !== 0);
                /* @var $attacksBoth bool */
                $attacksBoth = ($buff->getMinionMinGoodguyDamage() || $buff->getMinionMaxGoodguyDamage() !== 0)
                    && ($buff->getMinionMinBadguyDamage() || $buff->getMinionMaxBadguyDamage() !== 0);

                // Faulty buff - if minions attack no one, it's better to have no minions at all. Or they will just do... nothing.
                if ($attacksOne === false) {
                    $n = 0;
                }

                // Add a minion event for every single minion
                for ($i = 0; $i < $n; $i++) {
                    // If the buff is setup to attack both good and badguy, we throw a dice to decide who the minion attacks
                    /* @var $who int Who the minion attacks. 1: Goodguy, 2: Badguy */
                    if ($attacksBoth === true) {
                        if ($game->getDiceBag()->chance(0.5)) {
                            $who = 1;
                        } else {
                            $who = -1;
                        }
                    } elseif ($buff->getMinionMaxGoodguyDamage() !== 0 || $buff->getMinionMinGoodguyDamage() !== 0) {
                        $who = 1;
                    } else {
                        $who = -1;
                    }

                    if ($who === 1) {
                        // Minion does damage to the goodguy
                        $damage = $game->getDiceBag()->normal($buff->getMinionMinGoodguyDamage(), $buff->getMinionMaxGoodguyDamage());
                        $target = $goodguy;
                    } else {
                        // Minion does damage to the badguy
                        $damage = $game->getDiceBag()->normal($buff->getMinionMinBadguyDamage(), $buff->getMinionMaxBadguyDamage());
                        $target = $badguy;
                    }

                    if ($damage < 0) {
                        $message = $buff->getEffectFailsMessage();
                    } elseif ($damage > 0) {
                        $message = $buff->getEffectSucceedsMessage();
                    } else {
                        $message = $buff->getNoEffectMessage();
                    }

                    $events[] = new MinionDamageEvent(
                        $target,
                        (int)round($damage, 0),
                        $message
                    );
                }
            }
        }

        return new ArrayCollection(
            $events
        );
    }

    /**
     * Processes buffs that are dependant on the damage done in one round
     * @param int $activation
     * @param int $damage Positive damage is applied to the badguy, negative damage is applied to the goodguy
     * @param \LotGD\Core\Game $game
     * @param FighterInterface $goodguy
     * @param FighterInterface $badguy
     * @return Collection
     */
    public function processDamageDependentBuffs(
        int $activation,
        int $damage,
        Game $game,
        FighterInterface $goodguy,
        FighterInterface $badguy
    ): Collection {
        $events = [];

        foreach ($this->activeBuffs[$activation] as $buff) {
            if ($buff->getGoodguyDamageReflection() !== 0.) {
                if ($damage > 0) {
                    // Damage is > 0, so badguy takes damage. We cannot reflect anything, since this buff
                    // reflects only damage applied to the goodguy.
                    $reflectedDamage = 0;
                    $message = $buff->getEffectFailsMessage();
                } elseif ($damage == 0) {
                    $reflectedDamage = 0;
                    $message = $buff->getNoEffectMessage();
                } else {
                    $reflectedDamage = (int)round($buff->getGoodguyDamageReflection() * $damage * -1, 0);
                    if ($reflectedDamage === 0) {
                        $message = $buff->getNoEffectMessage();
                    } else {
                        $message = $buff->getEffectSucceedsMessage();
                    }
                }

                $events[] = new DamageReflectionEvent(
                    $badguy,
                    $reflectedDamage,
                    $message
                );
            }

            if ($buff->getBadguyDamageReflection() !== 0.) {
                if ($damage > 0) {
                    // Damage is > 0, so badguy takes damage, we can normally reflect
                    $reflectedDamage = (int)round($buff->getGoodguyDamageReflection() * $damage, 0);
                    if ($reflectedDamage === 0) {
                        $message = $buff->getNoEffectMessage();
                    } else {
                        $message = $buff->getEffectSucceedsMessage();
                    }
                } elseif ($damage == 0) {
                    $reflectedDamage = 0;
                    $message = $buff->getNoEffectMessage();
                } else {
                    // Damage is < 0, so goodguy takes damage. This buff cannot reflect.
                    $reflectedDamage = 0;
                    $message = $buff->getEffectFailsMessage();
                }

                $events[] = new DamageReflectionEvent(
                    $goodguy,
                    $reflectedDamage,
                    $message
                );
            }

            if ($buff->getGoodguyLifetap() !== 0.) {
                if ($damage > 0) {
                    // Damage is > 0, badguy takes damage. Goodguy lifetap works only upon damage to the goodguy.
                    $healAmount = 0;
                    $message = $buff->getEffectFailsMessage();
                } elseif ($damage < 0) {
                    // Damage is < 0, goodguy takes damage. We act upon this.
                    $healAmount = (int)round($damage * -$buff->getBadguyLifetap(), 0);
                    if ($healAmount === 0) {
                        $message = $buff->getNoEffectMessage();
                    } else {
                        $message = $buff->getEffectSucceedsMessage();
                    }
                } else {
                    $healAmount = 0;
                    $message = $buff->getNoEffectMessage();
                }

                $events[] = new DamageLifetapEvent(
                    $badguy,
                    $healAmount,
                    $message
                );
            }

            if ($buff->getBadguyLifetap() !== 0.) {
                if ($damage > 0) {
                    // Damage is > 0, badguy takes damage. We act upon this to heal goodguy.
                    $healAmount = (int)round($damage * $buff->getBadguyLifetap(), 0);
                    if ($healAmount === 0) {
                        $message = $buff->getNoEffectMessage();
                    } else {
                        $message = $buff->getEffectSucceedsMessage();
                    }
                } elseif ($damage < 0) {
                    // Damage is < 0, goodguy takes damage. Badguy lifetap works only upon damage to the goodguy.
                    $healAmount = 0;
                    $message = $buff->getEffectFailsMessage();
                } else {
                    $healAmount = 0;
                    $message = $buff->getNoEffectMessage();
                }

                $events[] = new DamageLifetapEvent(
                    $goodguy,
                    $healAmount,
                    $message
                );
            }
        }

        return new ArrayCollection($events);
    }
}
