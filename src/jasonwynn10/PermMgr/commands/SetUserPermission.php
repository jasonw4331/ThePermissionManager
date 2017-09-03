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
		if(empty($args) or count($args) < 2) {
			return false;
		}
		var_dump($args); //TODO remove
		if($sender instanceof Player) {
			$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[1])->getPlayer() ?? $this->getPlugin()->getServer()->getOfflinePlayer($args[1]);
			$permString = $args[1];
			if($this->getPlugin()->sortPermissionConfigStrings($permString)) {
				if($permString === "*") {
					if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
						$world = $args[2];
						if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
							$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
							return true;
						}
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->addPlayerPermission($player, $permission, false, $world);
						}
					}else{
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->addPlayerPermission($player, $permission, false);
						}
					}
					$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					return true;
				}
				if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
					$world = $args[2];
					$permission = new Permission($permString);
					if(!$this->getPlugin()->addPlayerPermission($player, $permission, false, $world)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					}
				}else{
					$permission = new Permission($permString);
					if(!$this->getPlugin()->addPlayerPermission($player, $permission, false)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					}
				}
				return true;
			}else{
				if($permString === "*") {
					if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
						$world = $args[2];
						if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
							$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
							return true;
						}
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->removePlayerPermission($player, $permission, false, $world);
						}
					}else{
						foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
							$this->getPlugin()->removePlayerPermission($player, $permission, false);
						}
					}
					$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					return true;
				}
				if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
					$world = $args[2];
					$permission = new Permission($permString);
					if(!$this->getPlugin()->removePlayerPermission($player, $permission, false, $world)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					}
				}else{
					$permission = new Permission($permString);
					if(!$this->getPlugin()->removePlayerPermission($player, $permission, false)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
					}
				}
				return true;
			}
		}else{
			$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
			if($player instanceof Player) {
				if(isset($args[1])) {
					$permString = $args[1];
					if($this->getPlugin()->sortPermissionConfigStrings($permString)) {
						if($permString === "*") {
							if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
								$world = $args[2];
								if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
									$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
									return true;
								}
								foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
									$this->getPlugin()->addPlayerPermission($player, $permission, false, $world);
								}
							}else{
								foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
									$this->getPlugin()->addPlayerPermission($player, $permission, false);
								}
							}
							$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
							return true;
						}
						if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
							$world = $args[2];
							$permission = new Permission($permString);
							if(!$this->getPlugin()->addPlayerPermission($player, $permission, false, $world)) {
								$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
							}else{
								$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
							}
						}else{
							$permission = new Permission($permString);
							if(!$this->getPlugin()->addPlayerPermission($player, $permission, false)) {
								$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
							}else{
								$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
							}
						}
						return true;
					}else{
						if($permString === "*") {
							if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
								$world = $args[2];
								if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
									$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
									return true;
								}
								foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
									$this->getPlugin()->removePlayerPermission($player, $permission, false, $world);
								}
							}else{
								foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
									$this->getPlugin()->removePlayerPermission($player, $permission, false);
								}
							}
							$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
							return true;
						}
						if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[2])) {
							$world = $args[2];
							$permission = new Permission($permString);
							if(!$this->getPlugin()->removePlayerPermission($player, $permission, false, $world)) {
								$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
							}else{
								$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
							}
						}else{
							$permission = new Permission($permString);
							if(!$this->getPlugin()->removePlayerPermission($player, $permission, false)) {
								$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
							}else{
								$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setuserpermission.success", [$player->getName()]));
							}
						}
						return true;
					}
				}else{
					return false;
				}
			} else {
				$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("playeroffline", [$args[0]]));
				return true;
			}
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
		$players = [$player->getName()];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getName();
		}
		sort($players, SORT_FLAG_CASE);
		$permissions = [];
		$playerPerms = $this->getPlugin()->getPlayerProvider()->getAllPlayerPermissions($player);
		foreach($this->getPlugin()->getServer()->getPluginManager()->getPermissions() as $permission) {
			if(!in_array($permission->getName(), $playerPerms))
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
				"name" => "player",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $players
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