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
		parent::__construct($plugin->getLanguage()->get("ununsetgrouppermission.name"), $plugin);
		$this->setPermission("PermManager.command.ununsetgrouppermission");
		$this->setUsage($plugin->getLanguage()->get("ununsetgrouppermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("ununsetgrouppermission.alias")]);
		$this->setDescription($plugin->getLanguage()->get("ununsetgrouppermission.desc"));
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
		$group = $args[0];
		if(!in_array($group, array_keys($this->getPlugin()->getGroups()->getAll()))) {
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("invalidgroup", [$group]));
			return true;
		}
		if(isset($args[1])) {
			$permString = $args[1];
			$permString = str_replace("-","", $permString);
			if($permString === "*") {
				foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
					$this->getPlugin()->removeGroupPermission($group, $permission);
				}
				$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
				return true;
			}else{
				$permission = new Permission($permString);
				if(!$this->getPlugin()->removeGroupPermission($group, $permission)) {
					$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
				}else{
					$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
				}
				return true;
			}
		}else{
			return false;
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
		$groups = [];
		foreach($this->getPlugin()->getGroups()->getAll() as $group => $data) {
			$groups[] = $group;
		}
		sort($groups, SORT_FLAG_CASE);
		$worlds = [];
		foreach($this->getPlugin()->getServer()->getLevels() as $level) {
			if(!$level->isClosed()) {
				$worlds[] = $level->getName();
			}
		}
		sort($worlds, SORT_FLAG_CASE);
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