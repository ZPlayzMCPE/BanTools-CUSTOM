<?php

namespace Tool;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\utils\TextFormat as T;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Main extends PluginBase implements Listener{
	
	public $freeze = array();
	public $chat = array();
	
	public function onEnable(){
	$this->getServer()->getPluginManager()->registerEvents($this, $this);
	$this->getLogger()->notice("§b@BEcraft_MCPE");
	@mkdir($this->getDataFolder());
	$config = new Config($this->getDataFolder()."Config.yml", Config::YAML, [
"Developer" => "StrafelessPvP",
"Left-Message" => "§7[§c-§7]§c {player}",
"Join-Message" => "§7[§a+§7]§a {player}",
"Block-Long-Damage" => false,
"Long-Distance" => 6,
"Alert-long-distance" => true,
]);
	$this->c = $config;
	$this->c->reload();
	$this->c->save();
	}
	
	public function onQuit(PlayerQuitEvent $e){
		if(in_array($e->getPlayer()->getName(), $this->freeze)){
			unset($this->freeze[$e->getPlayer()->getName()]);
			$config = new Config($this->getDataFolder().$e->getPlayer()->getName().".yml", Config::YAML);
			$reason = "You left the game while you was freezed";
            $config->set("Datos", array($e->getPlayer()->getName(), $e->getPlayer()->getClientId(), $e->getPlayer()->getAddress(), $reason));
            $config->save();
			$this->getServer()->getNameBans()->addBan($e->getPlayer()->getName(), $reason, null, null);
			$this->getServer()->getIPBans()->addBan($e->getPlayer()->getAddress(), $reason, null, null);
			$this->getServer()->getNetwork()->blockAddress($e->getPlayer()->getAddress(), -1);
			}
			if(in_array($e->getPlayer()->getName(), $this->chat)){
				unset($this->chat[$e->getPlayer()->getName()]);
				}
		}
		
	public function onBreak(BlockBreakEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->freeze)){
		$e->setCancelled(true);
		}
	}
		
	public function onPlace(BlockPlaceEvent $e){
	$p = $e->getPlayer();
	if(in_array($p->getName(), $this->freeze)){
		$e->setCancelled(true);
		}
	}
	
	public function onMove(PlayerMoveEvent $e){
		$p = $e->getPlayer();
		if(in_array($p->getName(), $this->freeze)){
			$to = clone $e->getFrom();
			$to->yaw = $e->getTo()->yaw;
			$to->pitch = $e->getTo()->pitch;
			$e->setTo($to);
			$p->sendPopup("§cYou cant move!");
			}
		}
		
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		if(in_array($player->getName(), $this->chat)){
			foreach($this->getServer()->getOnlinePlayers() as $players){
				if(in_array($players->getName(), $this->chat)){
					$players->sendMessage("§7[§6OP§7-§bCHAT§7] §e".$player->getName()." §7|| §a".$event->getMessage());
					$event->setCancelled(true);
					}
				}
			}
		}
		
	public function onDamage(EntityDamageEvent $e){
    if($e instanceof EntityDamageByEntityEvent){
    if($e->getEntity() instanceof Player and $e->getDamager() instanceof Player){
    $entity = $e->getEntity();
    $damager = $e->getDamager();
    if($entity->distance($damager) >= $this->c->get("Long-Distance")){
    	if($this->c->get("Block-long-damage", true)){
    	$e->setCancelled(true);
    	}
    foreach($this->getServer()->getOnlinePlayers() as $players){
    if($players->isOp()){
    	if($this->c->get("Alert-long-distance", true)){
    $players->sendPopup("§cWarning: §a".$damager->getName()."§7[§a".$entity->distance($damager)."§7]");
    }
    }
    }
    }
    if((in_array($entity->getName(), $this->freeze)) and (!in_array($damager->getName(), $this->freeze))){
    	$damager->sendMessage("§cWarning: §7You cant hit this player!");
    $e->setCancelled(true);
    }
    	if((!in_array($entity->getName(), $this->freeze)) and (in_array($damager->getName(), $this->freeze))){
    $damager->sendMessage("§cWarning: §7You cant hit this player!");
    $e->setCancelled(true);
    }
    
    }
    }
    }
    
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
	switch($cmd){
	case "invisible":
	if($sender->hasPermission("voidminerpe.invisible")){
		if($sender instanceof Player){
			$cast = $this->c->get("Left-Message");
			$cast = str_replace("{player}", $sender->getName(), $cast);
			$this->getServer()->broadcastMessage($cast);
			$sender->sendMessage("§eSpy§6Mode §aEnabled");
			foreach($this->getServer()->getOnlinePlayers() as $players){
				$players->hidePlayer($sender);
				$sender->setDisplayName("");
				$sender->setNameTag("");
				$sender->despawnFromAll();
				$sender->setAllowFlight(true);
				$sender->setFlying(true);
				}
			}else{$sender->sendMessage("§cRun on game...");}
	}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	case "visible":
	if($sender->hasPermission("voidminerpe.visible")){
		if($sender instanceof Player){
			$cast = $this->c->get("Join-Message");
			$cast = str_replace("{player}", $sender->getName(), $cast);
			$this->getServer()->broadcastMessage($cast);
			$sender->sendMessage("§eSpy§6Mode §cDisabled");
			foreach($this->getServer()->getOnlinePlayers() as $players){
				$players->showPlayer($sender);
				$sender->spawnToAll();
				$sender->setNameTag("§a".$sender->getName());
				$sender->setDisplayName($sender->getName());
				$sender->setAllowFlight(false);
				$sender->setFlying(false);
				}
			}else{$sender->sendMessage("§cRun on game...");}
	}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	case "vmban":
	if($sender->hasPermission("voidminerpe.ban")){
		if(isset($args[0])){
			$p = array_shift($args);
		$player = $sender->getServer()->getPlayer($p);
		//if(isset($args[1])){
			$reason = null;
			for($i = 0; $i < count($args); $i++){
				$reason .= $args[$i];
				$reason .= " ";
				}
	if($player instanceof Player){
@mkdir($this->getDataFolder());
$config = new Config($this->getDataFolder().$player->getName().".yml", Config::YAML);
$ip = $player->getAddress();
$id = $player->getClientId();
$config->set("Datos", array($player->getName(), $player->getClientId(), $player->getAddress(), $reason));
$config->save();
		$sender->getServer()->getNameBans()->addBan($player->getName(), $reason, null, $sender->getName());
		if($this->getServer()->getName() === "Genisys"){
		$sender->getServer()->getCIDBans()->addBan($player->getClientId(), $reason, null, $sender->getName());
		}
		$sender->getServer()->getIPBans()->addBan($player->getAddress(), $reason, null, $sender->getName());
		$sender->getServer()->getNetwork()->blockAddress($player->getAddress(), -1);
		$this->getServer()->broadcastMessage("§a".$player->getName()." §7has been banned, reason: §6".$reason);
	    $player->kick("§7[§ax§7]§cYou have been banned§7[§ax§7] \n§6Banned by: §e{$sender->getName()}\n§6Reason: §e{$reason}\n§7If you think this ban is incorrect or\nyou have any question please contact us\nat §b@{$this->c->get("Twitter")} §7thanks for play!", false);
		}else{$sender->sendMessage("§cNot player found...");}
		//}else{$sender->sendMessage("§cuse: /eb <player> <reason>");}
		}else{$sender->sendMessage("§cuse: /eb <player> <reason>");}
	}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	case "vmpardon":
	if($sender->hasPermission("voidminerpe.pardon")){
		if(isset($args[0])){
		$player = $args[0];
		if(file_exists($this->getDataFolder().$player.".yml")){
			$config = new Config($this->getDataFolder().$player.".yml", Config::YAML);
			$datos = $config->get("Datos");
			/*pardon name*/
			$sender->getServer()->getNameBans()->remove($datos[0]);
			/*pardon ip*/
			if($this->getServer()->getName() === "Genisys"){
			$sender->getServer()->getNetwork()->unblockAddress($datos[2]);
			}
			$sender->getServer()->getIPBans()->remove($datos[2]);
			/*pardon cid*/
			if($this->getServer()->getName() === "Genisys"){
			$sender->getServer()->getCIDBans()->remove($datos[1]);
			}
			//remove file
			@unlink($this->getDataFolder().$player.".yml");
			$sender->sendMessage("§ePardon: §a".$player."\n§aCompleted!");
			}else{$sender->sendMessage("§cSorry this player hasn't been banned by this plugin,\n§cuse default command...");}
			}else{$sender->sendMessage("§cuse: /ep <player>");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
	
	case "vminfo":
	if($sender->hasPermission("voidminerpe.info")){
		if(isset($args[0])){
			$player = $sender->getServer()->getPlayer($args[0]);
			if($player instanceof Player){
				$health = $player->getHealth();
				if($player->getGameMode() == 0){
					$game = "Survival";
					}else if($player->getGamemode() == 1){
						$game = "Creative";
						}else if($player->getGamemode() == 2){
							$game = "Adventure";
							}else if($player->getGamemode() == 3){
								$game = "Spectator";
								}
								if($player->isOp()){
									$op = "true";
									}else{
										$op = "false";
										}
								$ip = $player->getAddress();
if($this->getServer()->getName() === "PocketMine-MP"){
	$sender->sendMessage(
								"§7Name: §a".$player->getName()."\n".
								"§7Health: §a".$health."\n".
								"§7Gamemode: §a".$game."\n".
								"§7OP: §a".$op."\n".
								"§7Address: §a".$ip."\n".
								"§7ClientID: §a".$player->getClientId()
);
	}else{
		$sender->sendMessage(
								"§7Name: §a".$player->getName()."\n".
								"§7Health: §a".$health."\n".
								"§7Gamemode: §a".$game."\n".
								"§7OP: §a".$op."\n".
								"§7Address: §a".$ip
);
		}
				}else{$sender->sendMessage("§2Cannot find player.");}
			}else{$sender->sendMessage("§7Please use: §e/info <player>");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
		
	case "vmfreeze":
	if($sender->hasPermission("voidminerpe.freeze")){
		if(isset($args[0])){
			$player = $sender->getServer()->getPlayer($args[0]);
			if($player instanceof Player){
				if(!in_array($player->getName(), $this->freeze)){
					$sender->sendMessage("§e".$player->getName()." §ahas been freezed!");
					$player->sendMessage("§cYou have been freezed, please dont log out!");
					$this->freeze[$player->getName()] = $player->getName();
					}else{
						$sender->sendMessage("§e".$player->getName()." §ahas been unfreezed");
						$player->sendMessage("§aYou can move right now");
						unset($this->freeze[$player->getName()]);
						}
				}else{$sender->sendMessage("§cNo player found");}
			}else{$sender->sendMessage("§cuse /fre [player]");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
		
	case "co":
	if($sender->hasPermission("voidminerpe.adminchat"()){
		if($sender instanceof Player){
		if(!in_array($sender->getName(), $this->chat)){
			$sender->sendMessage("§aYou've joined §4The Admin chat!");
			$this->chat[$sender->getName()] = $sender->getName();
			foreach($this->getServer()->getOnlinePlayers() as $players){
				if(in_array($players->getName(), $this->chat)){
					$players->sendMessage("§a".$sender->getName()." §bjoined the Admin-chat!");
					}
				}
			}else{
				foreach($this->getServer()->getOnlinePlayers() as $players){
				if(in_array($players->getName(), $this->chat)){
					$players->sendMessage("§4".$sender->getName()." §cleft the Admin-chat!");
					}
				}
				$sender->sendMessage("§cYou left the Admin-CHAT!");
				unset($this->chat[$sender->getName()]);
				}
				}else{$sender->sendMessage("§cRun only in game!");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
		return true;
		break;
	
	case "tools":
	if($sender->isOp()){
		$sender->sendMessage("§6Void§bMiner§cPE §dTools §eHelp§7[§21§6/§21]");
		$sender->sendMessage("§b/visible - §aMake you visible to other players!");
		$sender->sendMessage("§b/invisible - §a[§aBe like a ghost!");
		$sender->sendMessage("§b/vmban [player] [reason] - §aBan any player from this server!");
		$sender->sendMessage("§b/vmpardon [player] - §aPardon any player which is banned!");
		$sender->sendMessage("§b/adminchat §aJoin / Leave the Admin chat");
		$sender->sendMessage("§b/vmfreeze - §aFreeze and Unfreeze any player");
		$sender->sendMessage("§b/vminfo [player] §aCheck any player's information");
		$sender->sendMessage("§b/tools - §aCheck all commands");
		$sender->sendMessage("§b/bancheck <name> - §aCheck banned players information");
		$sender->sendMessage("§eAuthor: §bYoTils123");
		$sender->sendMessage("§dThis plugin is based from The Void Network.");
		}else{$sender->sendMessage("§cYou dont have permission to use this command");}
		return true;
		break;
	
	case "bancheck":
	if($sender->hasPermission("voidminerpe.bancheck")){
		if(isset($args[0])){
		$banned = $args[0];
		if(file_exists($this->getDataFolder().$banned.".yml")){
			$config = new Config($this->getDataFolder().$banned.".yml", Config::YAML);
			$datos = $config->get("Datos");
			$sender->sendMessage("§7-=] §e".$banned."'s §6ban info §7[=-\n§7Address: §6".$datos[2]."\n§7Client ID: §6".$datos[1]."\n§7Reason: §c".$datos[3]);
			}else{$sender->sendMessage("§cthere is not any player banned with name §a".$banned."§ccheck at next time!");}
			}else{$sender->sendMessage("§cuse /bancheck <name>");}
		}else{$sender->sendMessage("§cYou dont have permission to use this command...");}
	return true;
	break;
	
	}
	}
  
    public function onBanned(PlayerPreLoginEvent $event){
    $player = $event->getPlayer();
    if($player->isBanned()){
    if(file_exists($this->getDataFolder().$player->getName().".yml")){
    $config = new Config($this->getDataFolder().$player->getName().".yml", Config::YAML);
    $datos = $config->get("Datos");
    $event->setKickMessage("§cSorry §a".$player->getName()."§c You are banned from this server...\n§eName: §7".$datos[0]."\n§eReason: §7".$datos[3]);
    $event->setCancelled(true);
    }else{
    $event->setKickMessage("§cSorry §a".$player->getName()."§c You are banned from this server...");
    $event->setCancelled(true);
    }
    }
    }
    
	}
