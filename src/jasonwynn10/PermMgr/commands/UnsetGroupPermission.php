<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
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
		if(!in_array($group, $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true)) and !$this->getPlugin()->isAlias($group)) {
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("invalidgroup", [$group]));
			return true;
		}
		if(in_array($group, $this->getPlugin()->getSuperAdmins())) {
			if($sender instanceof ConsoleCommandSender) {
				if(isset($args[1])) {
					$permString = $args[1];
					$permString = str_replace("-","", $permString);
					if($permString === "*") {
						if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", true) and isset($args[2])) {
							$world = $args[2];
							if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
								$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
								return true;
							}
							foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
								$this->getPlugin()->removeGroupPermission($group, $permission, $world);
							}
						}else{
							foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
								$this->getPlugin()->removeGroupPermission($group, $permission);
							}
						}
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
						return true;
					}else{
						if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", true) and isset($args[2])) {
							$world = $args[2];
							if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
								$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
								return true;
							}
							$permission = new Permission($permString);
							if(!$this->getPlugin()->removeGroupPermission($group, $permission, $world)) {
								$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
							}else{
								$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
							}
						}else{
							$permission = new Permission($permString);
							if(!$this->getPlugin()->removeGroupPermission($group, $permission)) {
								$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
							}else{
								$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
							}
						}
						return true;
					}
				}else{
					return false;
				}
			}else{
				$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
				return true;
			}
		}
		if(isset($args[1])) {
			$permString = $args[1];
			$permString = str_replace("-","", $permString);
			if($permString === "*") {
				if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", true) and isset($args[2])) {
					$world = $args[2];
					if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
						$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
						return true;
					}
					foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
						$this->getPlugin()->removeGroupPermission($group, $permission, $world);
					}
				}else{
					foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
						$this->getPlugin()->removeGroupPermission($group, $permission);
					}
				}
				$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
				return true;
			}else{
				if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", true) and isset($args[2])) {
					$world = $args[2];
					if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
						$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
						return true;
					}
					$permission = new Permission($permString);
					if(!$this->getPlugin()->removeGroupPermission($group, $permission, $world)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
					}
				}else{
					$permission = new Permission($permString);
					if(!$this->getPlugin()->removeGroupPermission($group, $permission)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetgrouppermission.success", [$group]));
					}
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
		$groups = $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true);
		sort($groups, SORT_FLAG_CASE);
		$permissions = [];
		$groupPerms = $this->getPlugin()->getGroups()->getAllGroupPermissions($this->getPlugin()->getPlayerProvider()->getGroup($player));
		foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
			if(in_array($permission->getName(), $groupPerms))
				$permissions[] = $permission->getName();
		}
		sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
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
				"enum_values" => $groups
			],
			[
				"name" => "permission",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $permissions
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