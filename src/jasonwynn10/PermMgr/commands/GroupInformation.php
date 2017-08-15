<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class GroupInformation extends PluginCommand {
	/**
	 * GroupInformation constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("groupinformation.name"), $plugin);
		$this->setPermission("PermManager.command.groupinformation");
		$this->setUsage($plugin->getLanguage()->get("groupinformation.usage"));
		$this->setDescription($plugin->getLanguage()->get("groupinformation.desc"));
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
		$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("groupinformation.header", [$group]));
		$config = $this->getPlugin()->getGroups()->getGroupsConfig();
		if(!empty($config->get($group.".alias", ''))) {
			$alias = $config->get($group.".alias");
			$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("groupinformation.aliasinfo", [$alias]));
		}
		if($config->get($group.".isDefault", false)) {
			$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("groupinformation.default", [$group]));
		}
		$parents = implode(", ", $config->get($group.".inheritance", []));
		$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("groupinformation.parents", [$parents]));
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
		foreach($this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true) as $group) {
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