<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class UnsetUserPermission extends PluginCommand {
	/**
	 * UnsetUserPermission constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin){
		parent::__construct($plugin->getLanguage()->get("unsetuserpermission.name"), $plugin);
		$this->setPermission("PermManager.command.unsetuserpermission");
		$this->setUsage($plugin->getLanguage()->get("unsetuserpermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("unsetuserpermission.alias")]);
		$this->setDescription($plugin->getLanguage()->get("unsetuserpermission.desc"));
		$this->setPermissionMessage($plugin->getLanguage()->get("nopermission"));
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
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			if(isset($args[1])) {
				$permString = $args[1];
				$permString = str_replace("-","", $permString);
				if($permString === "*") {
					foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
						$this->getPlugin()->removePlayerPermission($player, $permission, false);
					}
					$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetuserpermission.success", [$player->getName()]));
					return true;
				}else{
					$permission = new Permission($permString);
					if(!$this->getPlugin()->removePlayerPermission($player, $permission, false)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetuserpermission.success", [$player->getName()]));
					}
					return true;
				}
			}else{
				return false;
			}
		} else {
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("playeroffline", [$args[0]]));
		}
		return true;
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
		$players = [];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getName();
		}
		sort($players, SORT_FLAG_CASE);
		$worlds = [];
		foreach($this->getPlugin()->getServer()->getLevels() as $level) {
			if(!$level->isClosed()) {
				$worlds[] = $level->getName();
			}
		}
		sort($worlds, SORT_FLAG_CASE);
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "player",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $players
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