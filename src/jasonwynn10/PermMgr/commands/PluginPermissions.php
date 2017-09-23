<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class PluginPermissions extends PluginCommand {
	/**
	 * ReloadPermissions constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("pluginpermissions.name"), $plugin);
		$this->setPermission("PermManager.command.pluginpermissions");
		$this->setUsage($plugin->getLanguage()->get("pluginpermissions.usage"));
		$this->setAliases([$plugin->getLanguage()->get("pluginpermissions.alias")]);
		$this->setDescription($plugin->getLanguage()->get("pluginpermissions.desc"));
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
		$source = (strtolower($args[0]) === 'pocketmine' or strtolower($args[0]) === 'pmmp') ? 'pocketmine' : $this->getPlugin()->getServer()->getPluginManager()->getPlugin($args[0]);
		/** @var Permission[] $permissionObjects */
		$permissionObjects = ($source !== "pocketmine" and $source instanceof Plugin) ? $source->getDescription()->getPermissions() : $this->getPlugin()->getPocketMinePermissions();
		/** @var string[] $permissions */
		$permissions = [];
		foreach($permissionObjects as $permission) {
			$permissions[] = $permission->getName();
		}
		if(empty($permissions)) {
			$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("error")); //TODO: make translation string for invalid plugin
			return true;
		}
		sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		foreach($permissions as $permission) {
			$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("listuserpermission.list", [$permission]));
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
		$names = ["pocketmine"];
		foreach($this->getPlugin()->getServer()->getPluginManager()->getPlugins() as $plugin) {
			$names[] = $plugin->getName();
		}
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "plugin",
				"type" => "stringenum",
				"optional" => true,
				"enum_values" => $names
			]
		];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}