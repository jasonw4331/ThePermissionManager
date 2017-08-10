<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
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
		$plugin = (strtolower($args[0]) === 'pocketmine' or strtolower($args[0]) === 'pmmp') ? 'pocketmine' : $this->getPlugin()->getServer()->getPluginManager()->getPlugin($args[0]);
		/** @var Permission[] $permissionObjects */
		$permissionObjects = ($plugin instanceof PluginBase) ? $plugin->getDescription()->getPermissions() : $this->getPlugin()->getPocketMinePerms();
		/** @var string[] $permissions */
		$permissions = [];
		foreach($permissionObjects as $permission) {
			$permissions[] = $permission->getName();
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
		$names = [];
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