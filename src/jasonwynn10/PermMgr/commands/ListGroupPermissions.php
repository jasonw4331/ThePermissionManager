<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ListGroupPermissions extends PluginCommand {
	/**
	 * ListGroupPermissions constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("listgrouppermissions.name"), $plugin);
		$this->setPermission("PermManager.command.listgrouppermissions");
		$this->setUsage($plugin->getLanguage()->get("listgrouppermissions.usage"));
		$this->setAliases([$plugin->getLanguage()->get("listgrouppermissions.alias")]);
		$this->setDescription($plugin->getLanguage()->get("listgrouppermissions.desc"));
		$this->setPermissionMessage($plugin->getLanguage()->get("nopermission"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args)) {
			return false;
		}
		$group = $args[0];
		if(!in_array($group, $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true)) and !$this->getPlugin()->isAlias($group)) {
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("invalidgroup", [$group]));
			return true;
		}
		$permissions = [];
		if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[1])) {
			$world = $args[1];
			if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
				$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
				return true;
			}
			foreach($this->getPlugin()->getGroups()->getAllGroupPermissions($group, $world) as $permission) {
				if($this->getPlugin()->sortPermissionConfigStrings($permission)) {
					$permissions[] = $permission;
				}
			}
			sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		}else{
			foreach($this->getPlugin()->getGroups()->getAllGroupPermissions($group) as $permission) {
				if($this->getPlugin()->sortPermissionConfigStrings($permission)) {
					$permissions[] = $permission;
				}
			}
			sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		}
		foreach($permissions as $permission) {
			$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("listgrouppermissions.list", [$permission]));
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
		$groups = $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true);
		$worlds = [];
		foreach($this->getPlugin()->getServer()->getLevels() as $level) {
			$worlds[] = $level->getName();
		}
		sort($worlds, SORT_FLAG_CASE);
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "group",
				"type" => "stringenum",
				"optional" => false,
				"enumtext" => $groups
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