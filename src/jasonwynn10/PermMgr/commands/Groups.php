<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class Groups extends PluginCommand {
	/**
	 * Groups constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("groups.name"), $plugin);
		$this->setPermission("PermManager.command.groups");
		$this->setUsage($plugin->getLanguage()->get("groups.usage"));
		$this->setDescription($plugin->getLanguage()->get("groups.desc"));
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
		$groups = $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true);
		//sort($groups, SORT_NATURAL | SORT_FLAG_CASE); //comment out to sort by time of creation
		foreach($groups as $group) {
			$sender->sendMessage($this->getPlugin()->getLanguage()->translateString("groups.list", [$group]));
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
		$commandData["overloads"]["default"]["input"]["parameters"] = [];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}