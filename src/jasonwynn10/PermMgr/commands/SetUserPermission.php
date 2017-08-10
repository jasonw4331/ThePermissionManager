<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class SetUserPermission extends PluginCommand {
	/**
	 * SetUserPermission constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("setuserpermission.name"), $plugin);
		$this->setPermission("PermManager.command.setuserpermission");
		$this->setUsage($plugin->getLanguage()->get("setuserpermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("setuserpermission.alias")]);
		$this->setDescription($plugin->getLanguage()->get("setuserpermission.desc"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args)) {
			return false;
		}
		var_dump($args[0]);
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			if(isset($args[1])) {
				$permString = $args[1];
				if($this->getPlugin()->sortPermissionConfigStrings($permString)) {
					if($permString === "*") {
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->addPlayerPermission($player, $permission, false);
						}
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
						return true;
					}
					$permission = new Permission($permString);
					if(!$this->getPlugin()->addPlayerPermission($player, $permission, false)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					}
					return true;
				}else{
					if($permString === "*") {
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->removePlayerPermission($player, $permission, false);
						}
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
						return true;
					}
					$permission = new Permission($permString);
					if(!$this->getPlugin()->removePlayerPermission($player, $permission, false)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					}
					return true;
				}
			}else{
				return false;
			}
		} else {
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("playeroffline", [$player->getName()]));
			return true;
		}
	}

	/**
	 * @return ThePermissionManager
	 */
	public function getPlugin() : Plugin {
		return parent::getPlugin();
	}

	/**
	 * @param Player $player
	 *
	 * @return array
	 */
	public function generateCustomCommandData(Player $player) : array {
		$commandData = parent::generateCustomCommandData($player);
		$worlds = [];
		foreach($this->getPlugin()->getServer()->getLevels() as $level) {
			if(!$level->isClosed()) {
				$worlds[] = $level->getName();
			}
		}
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "player",
				"type" => "target",
				"optional" => false
			],
			[
				"name" => "permission",
				"type" => "rawtext",
				"optional" => false
			],
			[
				"name" => "world",
				"type" => "stringenum",
				"optional" => true,
				"enum_values" => $worlds
			]
		];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}