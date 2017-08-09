<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ReloadPermissions extends PluginCommand {
	/**
	 * ReloadPermissions constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("reloadpermissions.name"), $plugin);
		$this->setPermission("PermManager.command.reloadpermissions");
		$this->setUsage($plugin->getLanguage()->get("reloadpermissions.usage"));
		$this->setAliases([$plugin->getLanguage()->get("reloadpermissions.alias")]);
		$this->setDescription($plugin->getLanguage()->get("reloadpermissions.desc"));
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
		$this->getPlugin()->reloadGroupPermissions();
		$this->getPlugin()->reloadPlayerPermissions();
		$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("reloadpermissions.success"));
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
		$commandData["overloads"]["default"]["input"]["parameters"] = [];
		return $commandData;
	}
}