<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class UnsetGroupPermission extends PluginCommand {
	/**
	 * UnsetGroupPermission constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin){
		parent::__construct($plugin->getLanguage()->get("unsetgrouppermission.name"), $plugin);
		$this->setPermission("PermManager.command.unsetgrouppermission");
		$this->setUsage($plugin->getLanguage()->get("unsetgrouppermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("unsetgrouppermission.alias")]);
		$this->setDescription($plugin->getLanguage()->get("unsetgrouppermission.desc"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args)) {
			return false;
		}
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			$permString = str_replace("-","", $args[1]);
			if($permString === "*") {
				foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
					$this->getPlugin()->removePlayerPermission($player, $permission);
				}
			}else{
				$perm = new Permission($args[1]);
				$this->getPlugin()->removePlayerPermission($player, $perm);
			}
			$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$args[0]]));
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
		$groups = [];
		foreach($this->getPlugin()->getGroups() as $group => $data) {
			$groups[] = $group;
		}
		$worlds = [];
		foreach($this->getPlugin()->getServer()->getLevels() as $level) {
			if(!$level->isClosed()) {
				$worlds[] = $level->getName();
			}
		}
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "group",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $groups
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