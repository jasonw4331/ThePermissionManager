<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ListUserPermissions extends PluginCommand {
	/**
	 * ListUserPermissions constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("listuserpermissions.name"), $plugin);
		$this->setPermission("PermManager.command.listuserpermissions");
		$this->setUsage($plugin->getLanguage()->get("listuserpermissions.usage"));
		$this->setAliases([$plugin->getLanguage()->get("listuserpermissions.alias")]);
		$this->setDescription($plugin->getLanguage()->get("listuserpermissions.desc"));
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
		$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0])->getPlayer() ?? $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
		$permissions = [];
		if($this->getPlugin()->getConfig()->get("enable-multiworld-perms", false) and isset($args[1])) {
			$world = $args[1];
			if($this->getPlugin()->getServer()->isLevelGenerated($world)) {
				$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("invalidworld", [$world]));
				return true;
			}
			foreach($this->getPlugin()->getPlayerProvider()->getAllPlayerPermissions($player, $world) as $permission) {
				if($this->getPlugin()->sortPermissionConfigStrings($permission)) {
					$permissions[] = $permission;
				}
			}
			sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		}else{
			foreach($this->getPlugin()->getPlayerProvider()->getAllPlayerPermissions($player) as $permission) {
				if($this->getPlugin()->sortPermissionConfigStrings($permission)) {
					$permissions[] = $permission;
				}
			}
			sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		}
		foreach($permissions as $permission) {
			$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("listuserpermissions.list", [$permission]));
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
		$players = [$player->getName()];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getName();
		}
		sort($players, SORT_FLAG_CASE);
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