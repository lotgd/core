<?php
declare(strict_types=1);

namespace LotGD\Core;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection
};

use LotGD\Core\Models\{
    Buff,
    Character,
    BattleEvents\BuffMessageEvent
};

/**
 * Description of BuffList
 */
class BuffList
{
    protected $buffs;
    protected $buffsBySlot;
    protected $activeBuffs;
    
    protected $activated = false;
    protected $badguyInvulnurable = false;
    protected $badguyDamageModifier = 1;
    protected $badguyAttackModifier = 1;
    protected $badguyDefenseModifier = 1;
    protected $goodguyInvulnurable = false;
    protected $goodguyDamageModifier = 1;
    protected $goodguyAttackModifier = 1;
    protected $goodguyDefenseModifier = 1;
    
    protected $events;
    protected $loaded = false;
    
    public function __construct(Collection $buffs)
    {
        $this->buffs = $buffs;
        $this->events = new ArrayCollection();
    }
    
    public function loadBuffs()
    {
        if ($this->loaded === false) {
            foreach($this->buffs as $buff) {
                $this->buffsBySlot[$buff->getSlot()] = $buff;
            }
        }
    }
    
    public function activate(): Collection
    {
        if ($this->activated === true) {
            throw new BuffListAlreadyActivatedException("You can activate the buff list only once.");
        }
        
        $this->activeBuffs = new ArrayCollection();
        $activationEvents = new ArrayCollection();
        
        foreach ($this->buffs as $buff) {
            // Only look at buffs that are activated in battle.
            if ($buff->getsActivatedAt(Buff::ACTIVATE_NONE)) {
                continue;
            }
            
            $this->activeBuffs->add($buff);
            
            if ($buff->hasBeenStarted() === false) {
                $activationMessage = $buff->getStartMessage();
                if ($activationMessage !== "") {
                    $activationEvents->add(new BuffMessageEvent($activationMessage));
                }
                $buff->setHasBeenStarted();
            }
            else {
                $roundMessage = $buff->getRoundMessage();
                if ($roundMessage !== "") {
                    $activationEvents->add(new BuffMessageEvent($roundMessage));
                }
            }
        }
        
        return $activationEvents;
    }
    
    public function expireOneRound(): Collection
    {
        $endEvents = new ArrayCollection();
        
        foreach($this->activeBuffs as $buff) {
            $roundsLeft = $buff->getRounds() - 1;
            $buff->setRounds($roundsLeft);
            
            if ($roundsLeft === 0) {
                $endMessage = $buff->getEndMessage();
                
                if ($endMessage !== "") {
                    $endEvents->add(new BuffMessageEvent($endMessage));
                }
                
                $this->remove($buff);
            }
        }
        
        return $endEvents;
    }
    
    public function remove(Buff $buff)
    {
        unset($this->buffsBySlot[$buff->getSlot()]);
        $this->buffs->removeElement($buff);
        $this->activeBuffs->removeElement($buff);
    }
    
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
}
