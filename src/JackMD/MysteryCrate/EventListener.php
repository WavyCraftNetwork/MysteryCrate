<?php
declare(strict_types = 1);

/**
 * ___  ___          _                  _____           _
 * |  \/  |         | |                /  __ \         | |
 * | .  . |_   _ ___| |_ ___ _ __ _   _| /  \/_ __ __ _| |_ ___
 * | |\/| | | | / __| __/ _ \ '__| | | | |   | '__/ _` | __/ _ \
 * | |  | | |_| \__ \ ||  __/ |  | |_| | \__/\ | | (_| | ||  __/
 * \_|  |_/\__, |___/\__\___|_|   \__, |\____/_|  \__,_|\__\___|
 *          __/ |                  __/ |
 *         |___/                  |___/  By @JackMD for PMMP
 *
 * MysteryCrate, a Crate plugin for PocketMine-MP
 * Copyright (c) 2018 JackMD  < https://github.com/JackMD >
 *
 * Discord: JackMD#3717
 * Twitter: JackMTaylor_
 *
 * This software is distributed under "GNU General Public License v3.0".
 * This license allows you to use it and/or modify it but you are not at
 * all allowed to sell this plugin at any cost. If found doing so the
 * necessary action required would be taken.
 *
 * MysteryCrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License v3.0 for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-3.0>.
 * ------------------------------------------------------------------------
 */

namespace JackMD\MysteryCrate;

use JackMD\MysteryCrate\lang\Lang;
use pocketmine\block\BlockTypeIds;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\particle\LavaParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class EventListener implements Listener{

	/** @var Main */
	private $plugin;

	/** @var array */
	private const CRATE_BLOCKS = [
		BlockTypeIds::CHEST,
		BlockTypeIds::ENDER_CHEST,
		BlockTypeIds::TRAPPED_CHEST
	];

	/**
	 * EventListener constructor.
	 *
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param BlockBreakEvent $event
	 * @priority        HIGHEST
	 */
	public function onBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName((string) $this->plugin->getConfig()->get("crateWorld"));
		if(!($player->hasPermission("mc.crates.destroy"))){
			if($this->plugin->isCrateBlock($block->getTypeId())){
				if(in_array($block->getWorld()->getBlock($block->getPostion()->add(0, 1))->getTypeId(), self::CRATE_BLOCKS)){
					$player->sendMessage(Lang::$no_perm_destroy);
					$event->cancel();
				}
			}elseif(in_array($block->getTypeId(), self::CRATE_BLOCKS)){
				$typeBlock = $block->getWorld()->getBlock($block->getPostion()->subtract(0, 1));
				if($this->plugin->isCrateBlock($typeBlock->getTypeId())){
					$player->sendMessage(Lang::$no_perm_destroy);
					$event->cancel();
				}
			}
		}else{
			if(in_array($block->getTypeId(), self::CRATE_BLOCKS)){
				if($player->getWorld() === $level){
					$typeBlock = $block->getWorld()->getBlock($block->getPostion()->subtract(0, 1));
					if($type = $this->plugin->isCrateBlock($typeBlock->getTypeId())){
						$config = $this->plugin->getBlocksConfig();
						if(!empty($config->get($type))){
							$config->remove($type);
							$config->remove($type . ".x");
							$config->remove($type . ".y");
							$config->remove($type . ".z");
							$config->save();
							if(isset($this->plugin->getTextParticles()[$type])){
								unset($this->plugin->getTextParticles()[$type]);
								$this->plugin->initTextParticle();
							}
							$player->sendMessage(Lang::$crate_destroy_successful);
						}
					}
				}
			}
		}
	}

	/**
	 * @param BlockPlaceEvent $event
	 * @priority        HIGHEST
	 */
	public function onPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlockAgainst();
		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName((string) $this->plugin->getConfig()->get("crateWorld"));
		if(!($player->hasPermission("mc.crates.create"))){
			if($this->plugin->isCrateBlock($block->getTypeId())){
				if(in_array($block->getWorld()->getBlock($block->getPostion()->add(0, 1))->getTypeId(), self::CRATE_BLOCKS)){
					$player->sendMessage(Lang::$no_perm_create);
					$event->cancel();
				}
			}elseif(in_array($block->getTypeId(), self::CRATE_BLOCKS)){
				$typeBlock = $block->getWorld()->getBlock($block->getPostion()->subtract(0, 1));
				if($this->plugin->isCrateBlock($typeBlock->getTypeId())){
					$player->sendMessage(Lang::$no_perm_create);
					$event->cancel();
				}
			}
		}else{
			if(in_array($block->getTypeId(), self::CRATE_BLOCKS)){
				if($player->getWorld() === $level){
					$typeBlock = $block->getWorld()->getBlock($block->getPostion()->subtract(0, 1));
					if($type = $this->plugin->isCrateBlock($typeBlock->getTypeId())){
						$x = $block->getPostion()->getX();
						$y = $block->getPostion()->getY();
						$z = $block->getPostion()->getZ();
						$config = $this->plugin->getBlocksConfig();
						if(empty($config->get($type))){
							$config->set($type, TextFormat::GOLD . ucfirst($type) . TextFormat::GREEN . " Crate");
							$config->set($type . ".x", $x);
							$config->set($type . ".y", $y);
							$config->set($type . ".z", $z);
							$config->save();
							$player->sendMessage(Lang::$crate_place_successful);
						}
					}
				}
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority        HIGHEST
	 */
	public function onInteract(PlayerInteractEvent $event){
		$level = $this->plugin->getServer()->getWorldManager()->getWorldByName((string) $this->plugin->getConfig()->get("crateWorld"));
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$typeBlock = $block->getWorld()->getBlock($block->subtract(0, 1));
		$item = $event->getItem();
		if($player->getWorld() === $level){
			if((in_array($block->getTypeId(), self::CRATE_BLOCKS)) && ($type = $this->plugin->isCrateBlock($typeBlock->getTypeId())) !== false){
				$event->cancel();

				if(!$player->hasPermission("mc.crates.use")){
					$player->sendMessage(Lang::$no_perm_use_crate);

					return;
				}else{
					if($player->isSneaking()){
						$player->sendMessage(Lang::$error_sneak);

						return;
					}
					if(!($keytype = $this->plugin->isCrateKey($item)) || $keytype !== $type){
						$player->sendMessage(str_replace(["%TYPE%"], [ucfirst($type)], Lang::$no_key));

						return;
					}

					$t_delay = $this->plugin->getConfig()->get("tickDelay") * 20;

					$item = $player->getInventory()->getItemInHand();
					$item->setCount($item->getCount() - 1);
					$player->getInventory()->setItemInHand($item);

					$this->plugin->getScheduler()->scheduleRepeatingTask(new UpdaterEvent($this->plugin, $player, $block, $t_delay), (int) $this->plugin->getConfig()->get("scrollSpeed"));

					if($this->plugin->isBroadcastEnabled($type)){
						$cmd = $this->plugin->getBroadcastMessage($type);
						$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("%PLAYER%", $player->getName(), $cmd));
					}

					//Particle upon opening chest
					$cx = $block->getPostion()->getX() + 0.5;
					$cy = $block->getPostion()->getY() + 1.2;
					$cz = $block->getPostion()->getZ() + 0.5;
					$radius = (int) 1;
					for($i = 0; $i < 361; $i += 1.1){
						$x = $cx + ($radius * cos($i));
						$z = $cz + ($radius * sin($i));
						$pos = new Vector3($x, $cy, $z);
						$block->getWorld()->addParticle(new LavaParticle($pos));
					}
				}
			}
		}
	}

	/**
	 * @param EntityLevelChangeEvent $event
	 */
	public function onLevelChange(EntityTeleportEvent $event) {
            $targetLevel = $event->getTo(); // Correctly reference the target level (destination)
            $crateLevel = $this->plugin->getConfig()->get("crateWorld");

            if (!empty($this->plugin->getTextParticles())) {
                $particles = $this->plugin->getTextParticles();

                foreach ($particles as $particle) {
                    if ($particle instanceof FloatingTextParticle) {
                        if ($targetLevel->getFolderName() === $crateLevel) {
                            $particle->setInvisible(false); // Show particle
                            $targetLevel->addParticle($particle, [$event->getEntity()]);
                        } else {
                            $particle->setInvisible(true); // Hide particle
                            $targetLevel->addParticle($particle, [$event->getEntity()]);
                        }
                  }
              }
        }
    }


	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event){
		$this->plugin->addParticles($event->getPlayer());
	}
}
